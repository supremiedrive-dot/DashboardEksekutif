<?php

namespace App\Http\Controllers;

use App\Http\Requests\CatalogueIndexRequest;
use App\Models\Indicator;
use Illuminate\Http\JsonResponse;

class DomainCatalogueController extends Controller
{
    public function index(CatalogueIndexRequest $request): JsonResponse
    {
        $user = $request->user();
        $knownRole = $user->roles()->whereIn('code', [
            'super_admin','admin_data_bpn','operator_pemda','operator_kantah','viewer_eksekutif',
        ])->where('roles.is_active', true)->exists();
        if (! $knownRole || (! $user->hasActiveRole('super_admin') && ! $user->regionScopes()->where('regions.is_active', true)->exists())) abort(403);
        $date = $request->validated('as_of_date');
        $page = Indicator::query()->where('is_active', true)->with(['owner','submenu',
            'definitions'=>fn ($q) => $q->effectiveOn($date)->orderByDesc('version')])
            ->orderBy('display_order')->paginate($request->integer('per_page', 25));
        $page->through(fn (Indicator $indicator) => [
            'code'=>$indicator->canonical_code, 'source_code'=>$indicator->source_code,
            'name'=>$indicator->name, 'owner'=>$indicator->owner->code,
            'submenu'=>$indicator->submenu->code, 'is_derived'=>$indicator->is_derived,
            'is_feature'=>$indicator->is_feature, 'allows_manual_input'=>$indicator->allows_manual_input,
            'definition'=>$indicator->definitions->first() ? $this->definition($indicator->definitions->first()) : null,
        ]);
        return response()->json($page);
    }

    private function definition($definition): array
    {
        return ['version'=>$definition->version, 'valid_from'=>$definition->valid_from->toDateString(),
            'valid_to'=>$definition->valid_to?->toDateString(), 'value_type'=>$definition->value_type,
            'unit'=>$definition->unit, 'validation_rules'=>$definition->validation_rules,
            'formula_key'=>$definition->formula_key, 'formula_metadata'=>$definition->formula_metadata];
    }
}
