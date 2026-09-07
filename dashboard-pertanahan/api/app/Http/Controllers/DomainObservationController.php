<?php

namespace App\Http\Controllers;

use App\Http\Requests\ObservationIndexRequest;
use App\Http\Requests\ReviseObservationRequest;
use App\Http\Requests\StoreManualObservationRequest;
use App\Http\Requests\TransitionRevisionRequest;
use App\Models\Indicator;
use App\Models\Observation;
use App\Models\ObservationRevision;
use App\Models\Region;
use App\Models\ReportingSnapshot;
use App\Services\ManualObservationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DomainObservationController extends Controller
{
    public function __construct(private ManualObservationService $service) {}

    public function index(ObservationIndexRequest $request): JsonResponse
    {
        $region = Region::whereKey($request->integer('region_id'))->where('is_active', true)->firstOrFail();
        $this->ensureCanViewRegion($request, $region);
        $snapshot = ReportingSnapshot::where('as_of_date', $request->validated('as_of_date'))->first();
        $query = Observation::query()->where('region_id', $region->id)
            ->when($snapshot, fn ($q) => $q->where('reporting_snapshot_id', $snapshot->id), fn ($q) => $q->whereRaw('1=0'));
        $this->applyVisibility($query, $request);
        $page = $query->with(['snapshot','indicator.owner','latestRevision.definition','latestRevision.source','publishedValue'])
            ->orderBy('indicator_id')->paginate($request->integer('per_page', 25));
        $page->through(fn (Observation $observation) => $this->observation($observation, $request));
        return response()->json($page);
    }

    public function store(StoreManualObservationRequest $request): JsonResponse
    {
        $input = $request->validated();
        $indicator = Indicator::where('canonical_code', $input['indicator_code'])->where('is_active', true)->firstOrFail();
        $region = Region::whereKey($input['region_id'])->where('is_active', true)->firstOrFail();
        Gate::forUser($request->user())->authorize('update', [$indicator, $region]);
        $revision = $this->service->create($request->user(), $indicator, $region, $input);
        return response()->json(['data'=>$this->revision($revision)], 201);
    }

    public function revise(ReviseObservationRequest $request, Observation $observation): JsonResponse
    {
        $observation->loadMissing(['indicator.owner','region','snapshot']);
        Gate::forUser($request->user())->authorize('update', [$observation->indicator, $observation->region]);
        return response()->json(['data'=>$this->revision($this->service->revise($request->user(), $observation, $request->validated()))], 201);
    }

    public function submit(TransitionRevisionRequest $request, ObservationRevision $revision): JsonResponse
    {
        $revision->loadMissing('observation.indicator.owner','observation.region');
        Gate::forUser($request->user())->authorize('update', [$revision->observation->indicator, $revision->observation->region]);
        return response()->json(['data'=>$this->revision($this->service->transition(
            $request->user(), $revision, 'submitted', $request->integer('expected_revision'), $request->validated('reason')
        ))]);
    }

    public function publish(TransitionRevisionRequest $request, ObservationRevision $revision): JsonResponse
    {
        $this->requireSuperAdmin($request);
        return response()->json(['data'=>$this->revision($this->service->transition(
            $request->user(), $revision, 'published', $request->integer('expected_revision'), $request->validated('reason')
        ))]);
    }

    public function reject(TransitionRevisionRequest $request, ObservationRevision $revision): JsonResponse
    {
        $this->requireSuperAdmin($request);
        return response()->json(['data'=>$this->revision($this->service->transition(
            $request->user(), $revision, 'rejected', $request->integer('expected_revision'), $request->validated('reason')
        ))]);
    }

    public function history(Request $request, Observation $observation): JsonResponse
    {
        $observation->loadMissing(['indicator.owner','region']);
        Gate::forUser($request->user())->authorize('view', [$observation->indicator, $observation->region]);
        $viewerOnly = $request->user()->hasActiveRole('viewer_eksekutif') && ! $request->user()->hasActiveRole('super_admin');
        $revisions = $observation->revisions()->when($viewerOnly, fn ($q) => $q->where('status','published'))
            ->with(['definition','source'])->orderByDesc('revision_number')->paginate(min(max($request->integer('per_page', 25),1),100));
        $revisions->through(fn ($revision) => $this->revision($revision));
        $audits = $viewerOnly ? [] : \DB::table('audit_events')->where('metadata->observation_id', $observation->id)
            ->orderByDesc('id')->limit(100)->get(['id','actor_id','action','entity_type','entity_id','metadata','created_at']);
        return response()->json(['revisions'=>$revisions, 'audit_events'=>$audits]);
    }

    private function ensureCanViewRegion(Request $request, Region $region): void
    {
        $user = $request->user();
        $known = $user->roles()->whereIn('code',['super_admin','admin_data_bpn','operator_pemda','operator_kantah','viewer_eksekutif'])
            ->where('roles.is_active',true)->exists();
        if (! $known || (! $user->hasActiveRole('super_admin') && ! $user->hasRegionScope($region))) abort(403);
    }

    private function applyVisibility(Builder $query, Request $request): void
    {
        $user = $request->user();
        if ($user->hasActiveRole('super_admin')) return;
        $owners = [];
        if ($user->hasActiveRole('operator_pemda')) $owners[] = 'pemda';
        if ($user->hasActiveRole('operator_kantah')) $owners[] = 'kantah';
        if ($user->hasActiveRole('admin_data_bpn')) $owners[] = 'atr_bpn';
        $query->where(function ($q) use ($owners) {
            $q->whereHas('publishedValue');
            if ($owners) $q->orWhereHas('indicator.owner', fn ($owner) => $owner->whereIn('code',$owners));
        });
    }

    private function requireSuperAdmin(Request $request): void
    {
        if (! $request->user()->hasActiveRole('super_admin')) abort(403);
    }

    private function observation(Observation $observation, Request $request): array
    {
        $viewerOnly = $request->user()->hasActiveRole('viewer_eksekutif') && ! $request->user()->hasActiveRole('super_admin');
        $revision = $viewerOnly && $observation->publishedValue
            ? ObservationRevision::with(['definition','source'])->find($observation->publishedValue->observation_revision_id)
            : $observation->latestRevision;
        return ['id'=>$observation->id, 'region_id'=>$observation->region_id,
            'as_of_date'=>$observation->snapshot->as_of_date->toDateString(),
            'indicator_code'=>$observation->indicator->canonical_code,
            'revision'=>$revision ? $this->revision($revision) : null];
    }

    private function revision(ObservationRevision $revision): array
    {
        $valueColumns = ['value_decimal','value_integer','value_text','value_status_code','value_year','value_min','value_max','value_class'];
        $value = collect($revision->only($valueColumns))->filter(fn ($value) => $value !== null)->all();
        return ['id'=>$revision->id, 'observation_id'=>$revision->observation_id,
            'revision_number'=>$revision->revision_number, 'status'=>$revision->status,
            'definition_version'=>$revision->definition?->version, 'source_code'=>$revision->source?->code,
            'value'=>$value, 'note'=>$revision->change_note, 'created_at'=>$revision->created_at];
    }
}
