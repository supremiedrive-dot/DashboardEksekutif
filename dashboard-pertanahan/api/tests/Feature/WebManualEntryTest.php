<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\{User,Role,Region,Indicator,ObservationRevision};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\{DB,Hash};
use Illuminate\Support\Str;
class WebManualEntryTest extends TestCase
{
    use DatabaseTransactions;
    private Region $region;
    protected function setUp(): void { parent::setUp(); $this->assertSame('dashboard_pertanahan_test',DB::connection()->getDatabaseName()); $this->region=Region::where('level','regency_city')->firstOrFail(); }
    private function user(string $role): User { $u=User::create(['name'=>'Web test','email'=>Str::uuid().'@test.local','password'=>Hash::make('secret'),'is_active'=>true]); $u->roles()->attach(Role::where('code',$role)->firstOrFail()); $u->regionScopes()->attach($this->region); return $u; }
    public function test_guest_is_redirected_from_manual_entry(): void
    {
        $this->get('/data-entry')->assertRedirect('/login');
    }

    public function test_operator_pemda_can_open_form_with_scoped_data(): void
    {
        $this->actingAs($this->user('operator_pemda'))->get('/data-entry')->assertOk()->assertSee('Input Data Pertanahan')->assertSee('name="region_id"',false);
    }
    public function test_non_operator_roles_are_denied_and_sidebar_link_is_hidden(): void
    {
        foreach (['super_admin','viewer_eksekutif','admin_data_bpn'] as $role) $this->actingAs($this->user($role))->get('/data-entry')->assertForbidden();
    }
    public function test_operator_can_create_and_submit_draft_through_service(): void
    {
        $u=$this->user('operator_pemda'); $this->actingAs($u); $i=Indicator::where('canonical_code','V.2')->firstOrFail();
        $this->post('/data-entry',['indicator_code'=>$i->canonical_code,'region_id'=>$this->region->id,'as_of_date'=>'2026-09-06','value'=>12,'note'=>'web'])->assertRedirect()->assertSessionHas('success');
        $draft=ObservationRevision::where('created_by',$u->id)->firstOrFail(); $this->assertSame('draft',$draft->status);
        $this->post(route('data-entry.submit',$draft))->assertRedirect()->assertSessionHas('success');
        $this->assertSame('submitted',$draft->fresh()->status);
    }
    public function test_validation_and_csrf_are_enforced(): void
    {
        $this->actingAs($this->user('operator_kantah'))->from('/data-entry')->post('/data-entry',[])->assertRedirect('/data-entry')->assertSessionHasErrors(['indicator_code','region_id','as_of_date','value']);
    }
}
