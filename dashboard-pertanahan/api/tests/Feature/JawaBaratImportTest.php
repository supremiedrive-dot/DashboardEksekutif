<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\Indicator;
use App\Models\Observation;
use App\Models\ObservationRevision;
use App\Services\JawaBaratImportService;
use App\Services\JawaBaratWorkbookReader;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class JawaBaratImportTest extends TestCase
{
    use DatabaseTransactions;

    private string $workbook;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertTrue($this->app->environment('testing'));
        $this->assertSame('mysql', config('database.default'));
        $this->assertSame('dashboard_pertanahan_test', DB::selectOne('SELECT DATABASE() AS db')->db);
        $this->assertNotSame('dashboard_pertanahan_dev', DB::connection()->getDatabaseName());
        $this->workbook = dirname(base_path()).DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'source'
            .DIRECTORY_SEPARATOR.'Input Data Jawa Barat.xlsx';
    }

    public function test_reader_and_dry_run_preserve_workbook_evidence_without_writes(): void
    {
        $parsed = app(JawaBaratWorkbookReader::class)->read($this->workbook);
        $this->assertSame('JAWA BARAT (4 Agst)', $parsed['sheet_name']);
        $this->assertCount(25, $parsed['rows']);
        $this->assertSame(25, collect($parsed['rows'])->pluck('region_name')->unique()->count());
        $this->assertSame(292, $parsed['formula_count']);
        $this->assertNotNull($parsed['rows'][0]['cells']['BB']['formula']);
        $this->assertNotNull($parsed['rows'][0]['cells']['BB']['cached_value']);

        $before = $this->databaseCounts();
        $report = app(JawaBaratImportService::class)->import($this->workbook, '2026-08-04', true);
        $this->assertSame('dry_run', $report['status']);
        $this->assertSame(1350, $report['staged_values']);
        $this->assertSame(225, $report['promotable_values']);
        $this->assertSame([], $report['blocking_errors']);
        $this->assertSame(10, $report['quality_summary']['APL_AREA_DELTA']);
        $this->assertSame(2, $report['quality_summary']['NIB_NOP_TEXT_SIGN_MISMATCH']);
        $this->assertSame(8, $report['quality_summary']['RDTR_ZERO_DENOMINATOR']);
        $this->assertSame($before, $this->databaseCounts());
    }

    public function test_commit_stages_all_cells_promotes_only_allowed_values_and_is_idempotent(): void
    {
        $service = app(JawaBaratImportService::class);
        $first = $service->import($this->workbook, '2026-08-04', false);
        $this->assertSame('imported', $first['status']);
        $this->assertSame(25, DB::table('import_rows')->count());
        $this->assertSame(1350, DB::table('import_values')->count());
        $this->assertSame(446, DB::table('import_quality_flags')->count());
        $this->assertSame(225, Observation::count());
        $this->assertSame(225, ObservationRevision::count());
        $this->assertSame(25, DB::table('import_rows')->whereNotNull('region_id')->distinct()->count('region_id'));
        $this->assertSame(1, DB::table('audit_events')->where('action', 'excel.import_completed')->count());
        $this->assertSame(9, Observation::query()->distinct()->count('indicator_id'));

        $revision = ObservationRevision::whereNotNull('import_value_id')->whereNotNull('source_period_text')->firstOrFail();
        $this->assertSame('excel', $revision->input_method);
        $this->assertSame('draft', $revision->status);
        $this->assertNotNull($revision->source_checksum);
        $this->assertNotNull($revision->source_sheet);
        $this->assertNotNull($revision->source_row);
        $this->assertNotNull($revision->source_column);
        $this->assertGreaterThan(1, ObservationRevision::query()->distinct()->count('source_period_text'));

        $second = $service->import($this->workbook, '2026-08-04', false);
        $this->assertSame('idempotent', $second['status']);
        $this->assertSame($first['batch_id'], $second['batch_id']);
        $this->assertSame(1, ImportBatch::count());
        $this->assertSame(225, ObservationRevision::count());
    }

    public function test_changed_source_value_creates_one_revision_and_unchanged_values_do_not(): void
    {
        $reader = app(JawaBaratWorkbookReader::class);
        $service = app(JawaBaratImportService::class);
        $parsed = $reader->read($this->workbook);
        $service->importParsed($parsed, '2026-08-04', false);

        $original = (string)$parsed['rows'][0]['cells']['C']['value'];
        $changed = (string)(((float)$original) + 1);
        $parsed['rows'][0]['cells']['C']['value'] = $changed;
        $parsed['rows'][0]['cells']['C']['raw_value'] = $changed;
        $parsed['checksum'] = hash('sha256', $parsed['checksum'].'changed-C6');
        $result = $service->importParsed($parsed, '2026-08-04', false);

        $this->assertSame('imported', $result['status']);
        $this->assertSame(1, $result['promoted_values']);
        $this->assertSame(224, $result['unchanged_values']);
        $this->assertSame(225, Observation::count());
        $this->assertSame(226, ObservationRevision::count());
        $indicator = Indicator::where('canonical_code', 'I.1')->firstOrFail();
        $this->assertSame(2, Observation::where('indicator_id', $indicator->id)->firstOrFail()->revisions()->count());
    }

    public function test_blocking_errors_persist_staging_without_creating_observations(): void
    {
        $service = app(JawaBaratImportService::class);
        $reader = app(JawaBaratWorkbookReader::class);

        $unknownRegion = $reader->read($this->workbook);
        $unknownRegion['rows'][0]['region_name'] = 'Wilayah Tidak Terdaftar';
        $unknownRegion['checksum'] = hash('sha256', $unknownRegion['checksum'].'unknown-region');
        $report = $service->importParsed($unknownRegion, '2026-08-04', false);
        $this->assertSame('blocked', $report['status']);
        $this->assertContains('REGION_NOT_FOUND', array_column($report['blocking_errors'], 'code'));

        $missingCache = $reader->read($this->workbook);
        $missingCache['rows'][0]['cells']['BB']['cached_value'] = null;
        $missingCache['checksum'] = hash('sha256', $missingCache['checksum'].'missing-cache');
        $report = $service->importParsed($missingCache, '2026-08-04', false);
        $this->assertSame('blocked', $report['status']);
        $this->assertContains('FORMULA_CACHE_MISSING', array_column($report['blocking_errors'], 'code'));

        Indicator::where('canonical_code', 'I.1')->update(['canonical_code'=>'I.1-unavailable']);
        $unknownIndicator = $reader->read($this->workbook);
        $unknownIndicator['checksum'] = hash('sha256', $unknownIndicator['checksum'].'unknown-indicator');
        $report = $service->importParsed($unknownIndicator, '2026-08-04', false);
        $this->assertSame('blocked', $report['status']);
        $this->assertContains('INDICATOR_UNKNOWN', array_column($report['blocking_errors'], 'code'));
        $this->assertSame(3, ImportBatch::where('status', 'blocked')->count());
        $this->assertSame(75, DB::table('import_rows')->count());
        $this->assertSame(4050, DB::table('import_values')->count());
        foreach (['REGION_NOT_FOUND','FORMULA_CACHE_MISSING','INDICATOR_UNKNOWN'] as $code) {
            $this->assertGreaterThan(0, DB::table('import_quality_flags')->where('rule_code', $code)->count());
        }
        $this->assertSame(0, Observation::count());
        $this->assertSame(0, ObservationRevision::count());
        $this->assertSame(3, DB::table('audit_events')->where('action', 'excel.import_blocked')->count());
    }

    public function test_staging_distinguishes_no_data_zero_ownership_and_derived_values(): void
    {
        app(JawaBaratImportService::class)->import($this->workbook, '2026-08-04', false);
        $value = fn (string $column, int $row) => DB::table('import_values')
            ->where('cell_reference', $column.$row)->firstOrFail();

        $this->assertSame('no_data', $value('AG', 13)->status);
        $this->assertNull($value('AG', 13)->normalized_value);
        $this->assertSame('not_promoted', $value('H', 6)->status);
        $this->assertSame('OWNERSHIP_MISMATCH', $value('H', 6)->error_code);
        $this->assertSame('not_promoted', $value('F', 6)->status);
        $this->assertSame('DERIVED_NOT_PROMOTED', $value('F', 6)->error_code);
        $this->assertSame('promoted', $value('AO', 7)->status);
        $this->assertSame(['value_integer'=>0], json_decode($value('AO', 7)->normalized_value, true));
        $this->assertSame(0, Observation::whereHas('indicator', fn ($query) => $query->whereIn('canonical_code',
            ['V.1','V.2','V.3','VI.2','I.2','I.3','VIII.2','IX.2','IX.3','MAP.1']))->count());
    }

    public function test_artisan_dry_run_uses_canonical_workbook_and_never_writes(): void
    {
        $this->artisan('dashboard:import-jabar', [
            '--file'=>'data/source/Input Data Jawa Barat.xlsx',
            '--as-of-date'=>'2026-08-04', '--dry-run'=>true,
        ])->assertSuccessful();
        $this->assertSame(['batches'=>0,'rows'=>0,'values'=>0,'observations'=>0,'revisions'=>0], $this->databaseCounts());
    }

    private function databaseCounts(): array
    {
        return ['batches'=>DB::table('import_batches')->count(), 'rows'=>DB::table('import_rows')->count(),
            'values'=>DB::table('import_values')->count(), 'observations'=>Observation::count(),
            'revisions'=>ObservationRevision::count()];
    }
}
