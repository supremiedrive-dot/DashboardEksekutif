<?php
namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Observation;
use App\Models\Region;
use App\Models\ReportingSnapshot;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $categories = Category::with(['submenus'=>fn($q)=>$q->where('is_active',true)->orderBy('display_order')->orderBy('id')])
            ->where('is_active',true)->orderBy('display_order')->orderBy('id')->get();
        $snapshots = ReportingSnapshot::orderBy('as_of_date')->get();
        $date = request('date','2026-08-04');
        $snapshot = ReportingSnapshot::whereDate('as_of_date',$date)->first();
        if (! $snapshot && request()->has('date')) abort(422, 'Periode tidak valid.');
        $snapshot ??= ReportingSnapshot::whereDate('as_of_date','2026-08-04')->first();
        $jabar = Region::where('name','Jawa Barat')->where('level','province')->first();
        $submenus = \App\Models\Submenu::where('is_active',true)->with('category')->orderBy('display_order')->get();
        $submenu = request()->filled('submenu') ? $submenus->firstWhere('id', (int) request('submenu')) : $submenus->first();
        if (request('submenu') && ! $submenu) abort(404);
        $allowed = $jabar?->children()->where('is_active',true)->orderBy('name')->get() ?? collect();
        if (! $user->hasActiveRole('super_admin')) $allowed = $allowed->filter(fn($r)=>$user->hasRegionScope($r));
        $isSuperAdmin = $user->hasActiveRole('super_admin');
        $region = $allowed->firstWhere('id',(int) request('region'));
        if (request('region') && ! $region) abort(403);
        $observations = collect();
        if ($snapshot && $jabar) {
            $indicators = $submenu->indicators()->where('is_active',true)->orderBy('display_order')->get();
            if (! $region) {
                $candidate = Observation::where('reporting_snapshot_id',$snapshot->id)->whereIn('region_id',$allowed->pluck('id'))->whereIn('indicator_id',$indicators->pluck('id'))->value('region_id');
                $region = $allowed->firstWhere('id',$candidate) ?? $allowed->first();
            }
            $query = Observation::with(['region','indicator','source','latestRevision','publishedValue'])->where('reporting_snapshot_id',$snapshot->id)->whereIn('indicator_id',$indicators->pluck('id'));
            if ($region) $query->where('region_id',$region->id); else $query->whereIn('region_id',$allowed->pluck('id'));
            $observations = $query->get()->filter(fn($o)=>$isSuperAdmin || $o->publishedValue);
            $observations = $indicators->map(fn($i)=>$observations->firstWhere('indicator_id',$i->id) ?? (object)['indicator'=>$i,'region'=>$region,'latestRevision'=>null,'publishedValue'=>null,'source'=>null]);
        }
        $selectedDate = $snapshot?->as_of_date?->format('Y-m-d');
        $selectedSubmenu = $submenu;
        $selectedRegion = $region;
        return view('dashboard', compact('categories','snapshot','snapshots','jabar','observations','submenus','submenu','selectedSubmenu','allowed','region','selectedRegion','selectedDate','isSuperAdmin'));
    }
}
