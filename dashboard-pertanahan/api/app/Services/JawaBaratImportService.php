<?php

namespace App\Services;

use App\Models\DataSource;
use App\Models\ImportBatch;
use App\Models\ImportQualityFlag;
use App\Models\ImportRow;
use App\Models\ImportValue;
use App\Models\Indicator;
use App\Models\Observation;
use App\Models\Region;
use App\Models\ReportingSnapshot;
use App\Support\JawaBaratImportMapping;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class JawaBaratImportService
{
    public function __construct(
        private JawaBaratWorkbookReader $reader,
        private DefinitionValueValidator $validator,
    ) {}

    public function import(string $path, string $asOfDate, bool $dryRun): array
    {
        $parsed = $this->reader->read($path);
        return $this->importParsed($parsed, $asOfDate, $dryRun);
    }

    public function importParsed(array $parsed, string $asOfDate, bool $dryRun): array
    {
        $date = CarbonImmutable::createFromFormat('!Y-m-d', $asOfDate);
        if (! $date || $date->format('Y-m-d') !== $asOfDate) throw new RuntimeException('as-of-date harus YYYY-MM-DD yang valid.');
        if (($parsed['sheet_name'] ?? null) !== JawaBaratImportMapping::SHEET) throw new RuntimeException('Sheet sumber tidak sesuai mapping.');

        $plan = $this->plan($parsed, $asOfDate);
        $report = $this->report($parsed, $plan, $dryRun ? 'dry_run' : 'ready');
        if ($dryRun) return $report;

        return DB::transaction(function () use ($parsed, $plan, $asOfDate, $report) {
            $source = DataSource::where('code', 'excel_jawa_barat')->where('is_active', true)->firstOrFail();
            $identity = ['file_checksum'=>$parsed['checksum'], 'as_of_date'=>$asOfDate,
                'mapping_version'=>JawaBaratImportMapping::VERSION];
            $batch = ImportBatch::firstOrCreate($identity, [
                'data_source_id'=>$source->id, 'header_checksum'=>$parsed['header_checksum'],
                'file_name'=>$parsed['file_name'], 'sheet_name'=>$parsed['sheet_name'],
                'status'=>'processing', 'started_at'=>now(),
            ]);
            if (! $batch->wasRecentlyCreated) {
                return $this->batchReport($batch, 'idempotent');
            }

            $snapshot = ReportingSnapshot::firstOrCreate(['as_of_date'=>$asOfDate], ['status'=>'open']);
            $promoted = 0;
            $unchanged = 0;
            $blocked = (bool)$plan['blocking_errors'];
            $rowModels = [];
            $valueModels = [];

            foreach ($plan['rows'] as $rowPlan) {
                $row = ImportRow::create([
                    'import_batch_id'=>$batch->id, 'region_id'=>$rowPlan['region']?->id,
                    'sheet_name'=>$parsed['sheet_name'], 'row_number'=>$rowPlan['row_number'],
                    'raw_region_name'=>$rowPlan['region_name'],
                    'status'=>$rowPlan['errors'] ? 'error' : 'validated',
                    'errors'=>$rowPlan['errors'] ?: null,
                ]);
                $rowModels[$rowPlan['row_number']] = $row;
                foreach ($rowPlan['values'] as $column => $valuePlan) {
                    $cell = $valuePlan['cell'];
                    $value = ImportValue::create([
                        'import_row_id'=>$row->id, 'indicator_id'=>$valuePlan['indicator']?->id,
                        'column_name'=>$column, 'cell_reference'=>$cell['reference'],
                        'source_period_label'=>$valuePlan['period'], 'raw_value'=>$cell['raw_value'],
                        'cached_value'=>$cell['cached_value'], 'formula'=>$cell['formula'],
                        'formula_type'=>$cell['formula_type'],
                        'shared_formula_index'=>$cell['shared_formula_index'],
                        'number_format'=>$cell['number_format'],
                        'normalized_value'=>$valuePlan['normalized'], 'status'=>$valuePlan['status'],
                        'error_code'=>$valuePlan['error_code'] ?? null,
                        'error_message'=>$valuePlan['error_message'] ?? null,
                    ]);
                    $valueModels[$cell['reference']] = $value;
                    if ($valuePlan['status'] !== 'ready' || $blocked) continue;
                    [$didPromote, $revisionId] = $this->promote(
                        $rowPlan['region'], $snapshot, $source, $valuePlan, $value, $parsed, $asOfDate
                    );
                    if ($didPromote) $promoted++; else $unchanged++;
                    if ($revisionId) $valuePlan['revision_id'] = $revisionId;
                }
            }

            foreach ($plan['flags'] as $flag) {
                ImportQualityFlag::create([
                    'import_batch_id'=>$batch->id,
                    'import_row_id'=>isset($flag['row_number']) ? ($rowModels[$flag['row_number']]->id ?? null) : null,
                    'import_value_id'=>isset($flag['cell_reference']) ? ($valueModels[$flag['cell_reference']]->id ?? null) : null,
                    'observation_revision_id'=>null, 'rule_code'=>$flag['code'],
                    'severity'=>$flag['severity'], 'cell_reference'=>$flag['cell_reference'] ?? null,
                    'evidence'=>$flag['evidence'] ?? null, 'status'=>'open',
                ]);
            }

            $batch->update([
                'status'=>$blocked ? 'blocked' : 'completed', 'total_rows'=>count($plan['rows']),
                'valid_rows'=>count(array_filter($plan['rows'], fn ($row) => ! $row['errors'])),
                'error_rows'=>count(array_filter($plan['rows'], fn ($row) => (bool)$row['errors'])),
                'staged_values'=>$plan['staged_values'], 'promoted_values'=>$promoted,
                'unchanged_values'=>$unchanged, 'quality_flags_count'=>count($plan['flags']),
                'completed_at'=>now(),
            ]);
            DB::table('audit_events')->insert([
                'actor_id'=>null, 'action'=>$blocked ? 'excel.import_blocked' : 'excel.import_completed',
                'entity_type'=>ImportBatch::class, 'entity_id'=>$batch->id,
                'request_id'=>null, 'reason'=>null,
                'metadata'=>json_encode(['mapping_version'=>JawaBaratImportMapping::VERSION,
                    'as_of_date'=>$asOfDate, 'rows'=>count($plan['rows']),
                    'staged_values'=>$plan['staged_values'], 'promoted_values'=>$promoted,
                    'unchanged_values'=>$unchanged, 'quality_flags'=>count($plan['flags'])], JSON_THROW_ON_ERROR),
                'created_at'=>now(),
            ]);
            return $this->batchReport($batch->fresh(), $blocked ? 'blocked' : 'imported') + [
                'blocking_errors'=>$plan['blocking_errors'], 'quality_summary'=>$report['quality_summary'],
            ];
        }, 3);
    }

    private function plan(array $parsed, string $asOfDate): array
    {
        $mapping = JawaBaratImportMapping::columns();
        $regions = Region::query()->where('level', 'regency_city')->where('is_active', true)->get()
            ->groupBy(fn (Region $region) => $this->normalizeRegion($region->name));
        $indicators = Indicator::with(['owner','definitions'=>fn ($q) => $q->effectiveOn($asOfDate)])
            ->whereIn('canonical_code', array_values(array_filter(array_column($mapping, 'indicator'))))
            ->get()->keyBy('canonical_code');
        $blocking = [];
        $flags = [];
        $rows = [];
        $seenRegions = [];

        foreach ($mapping as $column => $definition) {
            if (! $this->periodIsClear($parsed['headers'][$column] ?? '')) {
                $flags[] = $this->flag('SOURCE_PERIOD_UNCLEAR', 'warning', null, $column,
                    ['column'=>$column, 'header_checksum'=>$parsed['header_checksum']]);
            }
            if (! ($definition['indicator'] ?? null)) {
                $flags[] = $this->flag('INDICATOR_UNMAPPED', 'info', null, $column, ['column'=>$column]);
            }
        }

        foreach ($parsed['rows'] as $parsedRow) {
            $rowNumber = (int)$parsedRow['row_number'];
            $regionName = trim((string)$parsedRow['region_name']);
            $matches = $regions->get($this->normalizeRegion($regionName), collect());
            $errors = [];
            $region = $matches->count() === 1 ? $matches->first() : null;
            if (! $region) {
                $code = $matches->count() > 1 ? 'REGION_DUPLICATE_MATCH' : 'REGION_NOT_FOUND';
                $errors[] = $code;
                $blocking[] = ['row_number'=>$rowNumber, 'code'=>$code];
                $flags[] = $this->flag($code, 'error', $rowNumber, "B{$rowNumber}", ['region_name'=>$regionName]);
            } elseif (isset($seenRegions[$region->id])) {
                $errors[] = 'REGION_DUPLICATE_ROW';
                $blocking[] = ['row_number'=>$rowNumber, 'code'=>'REGION_DUPLICATE_ROW'];
                $flags[] = $this->flag('REGION_DUPLICATE_ROW', 'error', $rowNumber, "B{$rowNumber}", ['region_id'=>$region->id]);
            } else {
                $seenRegions[$region->id] = true;
            }

            $values = [];
            foreach ($mapping as $column => $definition) {
                $cell = $parsedRow['cells'][$column] ?? $this->emptyCell($column, $rowNumber);
                $indicator = ($definition['indicator'] ?? null) ? ($indicators[$definition['indicator']] ?? null) : null;
                $valuePlan = $this->valuePlan($definition, $indicator, $cell, $parsed['headers'][$column] ?? '', $asOfDate);
                if (! empty($valuePlan['flag'])) {
                    $flags[] = $this->flag($valuePlan['flag'], $valuePlan['status'] === 'error' ? 'error' : 'warning',
                        $rowNumber, $cell['reference'], ['indicator_code'=>$definition['indicator'] ?? null]);
                }
                if ($valuePlan['status'] === 'error' && ($definition['promote'] ?? false)) {
                    $errors[] = $valuePlan['error_code'];
                    $blocking[] = ['row_number'=>$rowNumber, 'cell_reference'=>$cell['reference'],
                        'code'=>$valuePlan['error_code'], 'detail'=>$valuePlan['error_message']];
                }
                $values[$column] = $valuePlan + ['cell'=>$cell, 'indicator'=>$indicator,
                    'period'=>$parsed['headers'][$column] ?? null, 'column'=>$column];
            }
            $rows[] = ['row_number'=>$rowNumber, 'region_name'=>$regionName, 'region'=>$region,
                'errors'=>array_values(array_unique($errors)), 'values'=>$values];
            $this->rowAnomalyFlags($parsedRow, $flags);
        }

        if (count($rows) !== 25) $blocking[] = ['code'=>'ROW_COUNT_MISMATCH', 'actual'=>count($rows), 'expected'=>25];
        if (($parsed['formula_count'] ?? 0) !== 292) $blocking[] = ['code'=>'FORMULA_COUNT_MISMATCH', 'actual'=>$parsed['formula_count'] ?? null, 'expected'=>292];
        return ['rows'=>$rows, 'flags'=>$flags, 'blocking_errors'=>$blocking,
            'staged_values'=>count($rows) * count($mapping)];
    }

    private function valuePlan(array $mapping, ?Indicator $indicator, array $cell, string $period, string $date): array
    {
        $base = ['normalized'=>null];
        $raw = trim((string)$cell['value']);
        if ($cell['error']) return $base + ['status'=>'error', 'error_code'=>'EXCEL_CELL_ERROR',
            'error_message'=>'Cell Excel bertipe error.', 'flag'=>'VALUE_INVALID'];
        if ($cell['formula_type'] && $cell['cached_value'] === null) return $base + ['status'=>'error',
            'error_code'=>'FORMULA_CACHE_MISSING', 'error_message'=>'Formula tidak memiliki cached value.', 'flag'=>'FORMULA_CACHE_MISSING'];
        if ($raw === '' || in_array(strtolower($raw), ['-','na','n/a'], true)) return $base + ['status'=>'no_data'];
        if (! ($mapping['indicator'] ?? null)) return $base + ['status'=>'unmapped', 'error_code'=>'INDICATOR_UNMAPPED'];
        if (! $indicator) return $base + ['status'=>'error', 'error_code'=>'INDICATOR_UNKNOWN',
            'error_message'=>'Indicator mapping tidak tersedia di master.', 'flag'=>'INDICATOR_UNKNOWN'];
        if (! ($mapping['promote'] ?? false)) {
            $reason = $indicator->owner->code !== 'atr_bpn' ? 'OWNERSHIP_MISMATCH'
                : ($indicator->is_derived ? 'DERIVED_NOT_PROMOTED' : 'MAPPING_NOT_APPROVED');
            return $base + ['status'=>'not_promoted', 'error_code'=>$reason, 'flag'=>$reason];
        }
        if (! $indicator->is_active || $indicator->is_feature || $indicator->is_derived
            || $indicator->quality_status === 'definition_pending' || $indicator->owner->code !== 'atr_bpn') {
            return $base + ['status'=>'error', 'error_code'=>'PROMOTION_NOT_ALLOWED',
                'error_message'=>'Ownership atau metadata indicator tidak mengizinkan promosi.', 'flag'=>'OWNERSHIP_MISMATCH'];
        }
        if ($indicator->definitions->count() !== 1) return $base + ['status'=>'error',
            'error_code'=>'DEFINITION_NOT_EFFECTIVE', 'error_message'=>'Definition aktif tidak tepat satu.', 'flag'=>'DEFINITION_NOT_EFFECTIVE'];

        try {
            $input = $this->sourceValue($cell, $mapping, $indicator);
            $normalized = $this->validator->normalize($indicator->definitions->first(), $input);
            return ['status'=>'ready', 'normalized'=>$normalized,
                'definition'=>$indicator->definitions->first()] + $base;
        } catch (Throwable $exception) {
            return $base + ['status'=>'error', 'error_code'=>'VALUE_INVALID',
                'error_message'=>$exception->getMessage(), 'flag'=>'VALUE_INVALID'];
        }
    }

    private function sourceValue(array $cell, array $mapping, Indicator $indicator): mixed
    {
        $raw = trim((string)$cell['value']);
        if ($indicator->value_type === 'status') {
            $normalized = $this->normalizeText($raw);
            if (str_contains($normalized, 'tidak') || str_contains($normalized, 'belum')) return 'tidak_ada';
            if (str_contains($normalized, 'ada') || str_contains($normalized, 'aktif')) return 'ada';
            return $normalized;
        }
        if (! in_array($indicator->value_type, ['decimal','percentage','integer','year'], true)) return $raw;
        $numeric = $raw;
        if (! $cell['numeric']) {
            $numeric = preg_replace('/\s+/u', '', str_ireplace(['rp','idr','ha','m2','m²','%'], '', $raw));
            $numeric = str_replace(',', '.', $numeric);
        }
        if (($mapping['scale'] ?? null) === 'ratio') {
            if (! is_numeric($numeric)) return $numeric;
            $numeric = $this->decimalMultiply100((string)$numeric);
        }
        if (in_array($indicator->value_type, ['decimal','percentage'], true)) {
            $numeric = $this->quantizeDecimal((string)$numeric, 6);
        }
        return $numeric;
    }

    private function promote(Region $region, ReportingSnapshot $snapshot, DataSource $source, array $plan,
        ImportValue $importValue, array $parsed, string $asOfDate): array
    {
        $indicator = $plan['indicator'];
        $observation = Observation::firstOrCreate([
            'region_id'=>$region->id, 'reporting_snapshot_id'=>$snapshot->id,
            'indicator_id'=>$indicator->id, 'data_source_id'=>$source->id, 'dimension_key'=>'total',
        ]);
        $observation = Observation::whereKey($observation->id)->lockForUpdate()->firstOrFail();
        $latest = $observation->revisions()->orderByDesc('revision_number')->first();
        $normalized = $plan['normalized'];
        ksort($normalized);
        $checksum = hash('sha256', json_encode([$plan['definition']->id, $source->id, $normalized], JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
        if ($latest && hash_equals($latest->payload_checksum, $checksum)) {
            $importValue->update(['status'=>'unchanged']);
            return [false, null];
        }
        $cell = $plan['cell'];
        $revision = $observation->revisions()->create([
            'indicator_definition_id'=>$plan['definition']->id,
            'revision_number'=>($latest?->revision_number ?? 0) + 1,
            'data_owner_id'=>$indicator->data_owner_id, 'data_source_id'=>$source->id,
            'import_value_id'=>$importValue->id, 'created_by'=>null, 'input_method'=>'excel',
            'change_note'=>'Import '.JawaBaratImportMapping::VERSION, 'status'=>'draft',
            'source_as_of'=>null, 'source_period_text'=>$plan['period'],
            'source_sheet'=>$parsed['sheet_name'],
            'source_row'=>(int)preg_replace('/^[A-Z]+/', '', $cell['reference']),
            'source_column'=>$plan['column'],
            'raw_value'=>$cell['raw_value'], 'raw_formula'=>$cell['formula'],
            'raw_result'=>$cell['cached_value'], 'source_checksum'=>$parsed['checksum'],
            'payload_checksum'=>$checksum, ...$normalized,
        ]);
        $importValue->update(['status'=>'promoted']);
        return [true, $revision->id];
    }

    private function rowAnomalyFlags(array $row, array &$flags): void
    {
        $number = (int)$row['row_number'];
        $cells = $row['cells'];
        $c = $this->numeric($cells['C']['value'] ?? null);
        $d = $this->numeric($cells['D']['value'] ?? null);
        $e = $this->numeric($cells['E']['value'] ?? null);
        if ($c !== null && $d !== null && $e !== null) {
            $delta = ($c - $d) - $e;
            if (abs($delta) >= 0.5 && abs($delta) <= 1.5) {
                $flags[] = $this->flag('APL_AREA_DELTA', 'warning', $number, "E{$number}", ['delta_ha'=>round($delta, 6)]);
            }
        }
        $n = $this->numeric($cells['N']['value'] ?? null);
        $o = $this->numeric($cells['O']['value'] ?? null);
        $text = $this->normalizeText((string)($cells['P']['value'] ?? ''));
        if ($n !== null && $o !== null && $text !== '') {
            $expected = $n <=> $o;
            $stated = str_contains($text, '>') ? 1 : (str_contains($text, '<') ? -1 : (str_contains($text, '=') ? 0 : null));
            if ($stated !== null && $expected !== $stated) {
                $flags[] = $this->flag('NIB_NOP_TEXT_SIGN_MISMATCH', 'warning', $number, "P{$number}", ['numeric_sign'=>$expected]);
            }
        }
        $ao = $this->numeric($cells['AO']['value'] ?? null);
        if ($ao !== null && abs($ao) < 0.0000001) {
            $flags[] = $this->flag('RDTR_ZERO_DENOMINATOR', 'warning', $number, "AO{$number}",
                ['dependent_cells'=>["AQ{$number}", "AR{$number}"], 'policy'=>'no_data']);
        }
    }

    private function report(array $parsed, array $plan, string $status): array
    {
        return ['status'=>$plan['blocking_errors'] ? 'blocked' : $status, 'dry_run'=>$status === 'dry_run',
            'file'=>$parsed['file_name'], 'checksum'=>$parsed['checksum'], 'sheet'=>$parsed['sheet_name'],
            'mapping_version'=>JawaBaratImportMapping::VERSION, 'rows'=>count($plan['rows']),
            'formula_cells'=>$parsed['formula_count'], 'staged_values'=>$plan['staged_values'],
            'promotable_values'=>array_sum(array_map(fn ($row) => count(array_filter($row['values'], fn ($value) => $value['status'] === 'ready')), $plan['rows'])),
            'blocking_errors'=>$plan['blocking_errors'], 'quality_flags'=>count($plan['flags']),
            'quality_summary'=>array_count_values(array_column($plan['flags'], 'code'))];
    }

    private function batchReport(ImportBatch $batch, string $status): array
    {
        return ['status'=>$status, 'batch_id'=>$batch->id, 'dry_run'=>false,
            'mapping_version'=>$batch->mapping_version, 'rows'=>$batch->total_rows,
            'staged_values'=>$batch->staged_values, 'promoted_values'=>$batch->promoted_values,
            'unchanged_values'=>$batch->unchanged_values, 'quality_flags'=>$batch->quality_flags_count];
    }

    private function flag(string $code, string $severity, ?int $row, ?string $cell, array $evidence): array
    {
        return array_filter(['code'=>$code, 'severity'=>$severity, 'row_number'=>$row,
            'cell_reference'=>$cell, 'evidence'=>$evidence], fn ($value) => $value !== null);
    }

    private function emptyCell(string $column, int $row): array
    {
        return ['reference'=>$column.$row, 'value'=>'', 'raw_value'=>null, 'cached_value'=>null,
            'formula'=>null, 'formula_type'=>null, 'shared_formula_index'=>null,
            'numeric'=>false, 'error'=>false, 'percent_style'=>false, 'number_format'=>null];
    }

    private function normalizeRegion(string $value): string
    {
        $value = preg_replace('/\bkab\.?\s+/i', 'kabupaten ', $value);
        return $this->normalizeText($value);
    }

    private function normalizeText(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', preg_replace('/[^a-z0-9<>=]+/', ' ', strtolower($value))));
    }

    private function periodIsClear(string $header): bool
    {
        return (bool)preg_match('/(20\d{2}|\d{1,2}\s*\/\s*\d{1,2}\s*\/\s*20\d{2}|maret|agst|agustus)/i', $header);
    }

    private function numeric(mixed $value): ?float
    {
        if ($value === null) return null;
        $clean = trim((string)$value);
        if ($clean === '' || $clean === '-') return null;
        return is_numeric($clean) ? (float)$clean : null;
    }

    private function decimalMultiply100(string $value): string
    {
        if (! preg_match('/^(-?)(\d+)(?:\.(\d+))?$/D', $value, $parts)) return $value;
        $digits = ltrim($parts[2].($parts[3] ?? ''), '0') ?: '0';
        $scale = strlen($parts[3] ?? '') - 2;
        if ($scale <= 0) return ($parts[1] ?? '').$digits.str_repeat('0', -$scale);
        return ($parts[1] ?? '').substr(str_pad($digits, $scale + 1, '0', STR_PAD_LEFT), 0, -$scale)
            .'.'.substr(str_pad($digits, $scale + 1, '0', STR_PAD_LEFT), -$scale);
    }

    private function quantizeDecimal(string $value, int $scale): string
    {
        if (! preg_match('/^(-?)(\d+)(?:\.(\d+))?$/D', $value, $parts)) return $value;
        $fraction = $parts[3] ?? '';
        if (strlen($fraction) <= $scale) return $value;
        $kept = substr($fraction, 0, $scale);
        $digits = $parts[2].$kept;
        if ((int)$fraction[$scale] >= 5) {
            $carry = 1;
            for ($index = strlen($digits) - 1; $index >= 0 && $carry; $index--) {
                $next = ((int)$digits[$index]) + $carry;
                $digits[$index] = (string)($next % 10);
                $carry = intdiv($next, 10);
            }
            if ($carry) $digits = '1'.$digits;
        }
        $integerLength = strlen($digits) - $scale;
        $result = $integerLength > 0
            ? substr($digits, 0, $integerLength).'.'.substr($digits, $integerLength)
            : '0.'.str_repeat('0', -$integerLength).$digits;
        return ($parts[1] === '-' ? '-' : '').$result;
    }
}
