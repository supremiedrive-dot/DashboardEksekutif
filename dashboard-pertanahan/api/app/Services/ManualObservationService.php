<?php

namespace App\Services;

use App\Exceptions\RevisionConflictException;
use App\Models\DataSource;
use App\Models\Indicator;
use App\Models\Observation;
use App\Models\ObservationRevision;
use App\Models\Region;
use App\Models\ReportingSnapshot;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManualObservationService
{
    public function __construct(private DefinitionValueValidator $validator) {}

    public function create(User $actor, Indicator $indicator, Region $region, array $input): ObservationRevision
    {
        return DB::transaction(function () use ($actor, $indicator, $region, $input) {
            $definition = $this->definition($indicator, $input['as_of_date']);
            $source = $this->manualSource($indicator);
            $normalized = $this->validator->normalize($definition, $input['value']);
            $snapshot = ReportingSnapshot::firstOrCreate(['as_of_date'=>$input['as_of_date']], ['status'=>'open']);
            $observation = Observation::firstOrCreate([
                'region_id'=>$region->id, 'reporting_snapshot_id'=>$snapshot->id,
                'indicator_id'=>$indicator->id, 'data_source_id'=>$source->id,
                'dimension_key'=>'total',
            ]);
            $observation = Observation::whereKey($observation->id)->lockForUpdate()->firstOrFail();
            if ($observation->revisions()->exists()) throw new RevisionConflictException('Observation sudah ada; gunakan endpoint revision.');
            return $this->append($actor, $observation, $definition->id, $source, $normalized, $input, 1, 'manual.draft_created');
        }, 3);
    }

    public function revise(User $actor, Observation $observation, array $input): ObservationRevision
    {
        return DB::transaction(function () use ($actor, $observation, $input) {
            $observation = Observation::whereKey($observation->id)->lockForUpdate()->firstOrFail();
            $latest = $observation->revisions()->orderByDesc('revision_number')->firstOrFail();
            if ((int)$input['expected_revision'] !== $latest->revision_number) throw new RevisionConflictException();
            if ($latest->status === 'submitted') throw new RevisionConflictException('Revision submitted tidak dapat ditimpa.');
            $definition = $this->definition($observation->indicator, $observation->snapshot->as_of_date->toDateString());
            $source = $this->manualSource($observation->indicator);
            if ($observation->data_source_id !== $source->id) throw new AuthorizationException;
            $normalized = $this->validator->normalize($definition, $input['value']);
            $checksum = $this->checksum($definition->id, $source->id, $normalized, $input['note'] ?? null);
            if (hash_equals($latest->payload_checksum, $checksum)) throw new RevisionConflictException('Revision identik sudah ada.');
            return $this->append($actor, $observation, $definition->id, $source, $normalized, $input,
                $latest->revision_number + 1, 'manual.revision_created', $checksum);
        }, 3);
    }

    public function transition(User $actor, ObservationRevision $revision, string $to, int $expected, ?string $reason): ObservationRevision
    {
        return DB::transaction(function () use ($actor, $revision, $to, $expected, $reason) {
            $observation = Observation::whereKey($revision->observation_id)->lockForUpdate()->firstOrFail();
            $revision = ObservationRevision::whereKey($revision->id)->lockForUpdate()->firstOrFail();
            $latest = $observation->revisions()->max('revision_number');
            if ($expected !== $revision->revision_number || $latest !== $revision->revision_number) throw new RevisionConflictException();
            $allowed = ['draft'=>['submitted'], 'submitted'=>['published','rejected']];
            if (! in_array($to, $allowed[$revision->status] ?? [], true)) {
                throw ValidationException::withMessages(['status'=>['Transisi status tidak valid.']]);
            }
            $from = $revision->status;
            $revision->status = $to;
            $revision->save();
            DB::table('revision_status_events')->insert([
                'observation_revision_id'=>$revision->id, 'from_status'=>$from, 'to_status'=>$to,
                'actor_id'=>$actor->id, 'reason'=>$reason, 'created_at'=>now(),
            ]);
            if ($to === 'published') DB::table('published_values')->updateOrInsert(
                ['observation_id'=>$observation->id],
                ['observation_revision_id'=>$revision->id, 'published_by'=>$actor->id,
                    'published_at'=>now(), 'created_at'=>now(), 'updated_at'=>now()]
            );
            $this->audit($actor, 'manual.'.$to, $observation, $revision);
            return $revision->fresh(['definition','source']);
        }, 3);
    }

    private function append(User $actor, Observation $observation, int $definitionId, DataSource $source,
        array $normalized, array $input, int $number, string $action, ?string $checksum = null): ObservationRevision
    {
        $checksum ??= $this->checksum($definitionId, $source->id, $normalized, $input['note'] ?? null);
        $revision = $observation->revisions()->create([
            'indicator_definition_id'=>$definitionId, 'revision_number'=>$number,
            'data_owner_id'=>$observation->indicator->data_owner_id, 'data_source_id'=>$source->id,
            'created_by'=>$actor->id, 'input_method'=>'manual', 'change_note'=>$input['note'] ?? null,
            'status'=>'draft', 'raw_value'=>json_encode($input['value'], JSON_UNESCAPED_UNICODE|JSON_PRESERVE_ZERO_FRACTION),
            'payload_checksum'=>$checksum, ...$normalized,
        ]);
        DB::table('revision_status_events')->insert([
            'observation_revision_id'=>$revision->id, 'from_status'=>null, 'to_status'=>'draft',
            'actor_id'=>$actor->id, 'reason'=>null, 'created_at'=>now(),
        ]);
        $this->audit($actor, $action, $observation, $revision);
        return $revision->load(['definition','source']);
    }

    private function definition(Indicator $indicator, string $date)
    {
        $definitions = $indicator->definitions()->effectiveOn($date)->get();
        if ($definitions->count() !== 1) throw ValidationException::withMessages([
            'as_of_date'=>['Tidak ada tepat satu definition aktif untuk tanggal tersebut.'],
        ]);
        return $definitions->first();
    }

    private function manualSource(Indicator $indicator): DataSource
    {
        $expected = match ($indicator->owner->code) {
            'pemda'=>'manual_pemda', 'kantah'=>'manual_kantah', default=>null,
        };
        if (! $expected) throw new AuthorizationException;
        return DataSource::where('code', $expected)->where('is_active', true)->firstOrFail();
    }

    private function checksum(int $definitionId, int $sourceId, array $normalized, ?string $note): string
    {
        ksort($normalized);
        return hash('sha256', json_encode([$definitionId,$sourceId,$normalized,$note], JSON_UNESCAPED_UNICODE|JSON_PRESERVE_ZERO_FRACTION));
    }

    private function audit(User $actor, string $action, Observation $observation, ObservationRevision $revision): void
    {
        DB::table('audit_events')->insert([
            'actor_id'=>$actor->id, 'action'=>$action, 'entity_type'=>ObservationRevision::class,
            'entity_id'=>$revision->id, 'request_id'=>request()->header('X-Request-ID'),
            'reason'=>null, 'metadata'=>json_encode(['region_id'=>$observation->region_id,
                'indicator_id'=>$observation->indicator_id, 'observation_id'=>$observation->id,
                'revision_id'=>$revision->id, 'revision_number'=>$revision->revision_number,
                'status'=>$revision->status]), 'created_at'=>now(),
        ]);
    }
}
