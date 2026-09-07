<?php
namespace App\Http\Controllers;
use App\Models\{Category,ObservationRevision}; use App\Services\ManualObservationService; use Illuminate\Http\Request;
class WebReviewController extends Controller
{
 public function __construct(private ManualObservationService $service) {}
 private function admin(): void { abort_unless(auth()->user()?->is_active && auth()->user()->hasActiveRole('super_admin'),403); }
 private function categories(){ return Category::with(['submenus'=>fn($q)=>$q->where('is_active',true)->orderBy('display_order')])->where('is_active',true)->orderBy('display_order')->get(); }
 public function index(){ $this->admin(); $revisions=ObservationRevision::with(['observation.indicator','observation.region','observation.snapshot','source','observation'])->where('status','submitted')->latest()->paginate(25); return view('review.index',['categories'=>$this->categories(),'revisions'=>$revisions,'selectedSubmenu'=>null,'selectedRegion'=>null,'selectedDate'=>null]); }
 private function transition(Request $r, ObservationRevision $revision, string $status){ $this->admin(); abort_unless($revision->status==='submitted',409); $this->service->transition($r->user(),$revision,$status,$revision->revision_number,$r->input('reason')); return back()->with('success',$status==='published'?'Data berhasil dipublikasikan.':'Data ditolak.'); }
 public function publish(Request $r, ObservationRevision $revision){ return $this->transition($r,$revision,'published'); }
 public function reject(Request $r, ObservationRevision $revision){ $r->validate(['reason'=>'required|string|max:1000']); return $this->transition($r,$revision,'rejected'); }
}
