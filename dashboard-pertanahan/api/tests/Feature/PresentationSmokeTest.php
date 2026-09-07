<?php
namespace Tests\Feature;

use App\Models\{Region,Role,User};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\{DB,Hash};
use Illuminate\Support\Str;
use Tests\TestCase;

class PresentationSmokeTest extends TestCase
{
    use DatabaseTransactions;
    private Region $region;
    protected function setUp(): void { parent::setUp(); $this->assertSame('dashboard_pertanahan_test',DB::connection()->getDatabaseName()); $this->region=Region::where('level','regency_city')->firstOrFail(); }
    private function user(string $role, bool $active=true): User { $u=User::create(['name'=>'Smoke','email'=>Str::uuid().'@test.local','password'=>Hash::make('secret'),'is_active'=>$active]); $u->roles()->attach(Role::where('code',$role)->firstOrFail()); if($role!=='super_admin') $u->regionScopes()->attach($this->region); return $u; }
    public function test_guest_entry_points_redirect_to_login(): void { $this->get('/login')->assertOk(); foreach(['/dashboard','/data-entry','/review'] as $path) $this->get($path)->assertRedirect('/login'); }
    public function test_super_admin_can_dashboard_and_review_but_not_manual_entry(): void { $u=$this->user('super_admin'); $this->actingAs($u)->get('/dashboard')->assertOk(); $this->actingAs($u)->get('/review')->assertOk(); $this->actingAs($u)->get('/data-entry')->assertForbidden(); }
    public function test_operators_can_dashboard_and_entry_but_not_review(): void { foreach(['operator_pemda','operator_kantah'] as $role){$u=$this->user($role); $this->actingAs($u)->get('/dashboard')->assertOk(); $this->actingAs($u)->get('/data-entry')->assertOk(); $this->actingAs($u)->get('/review')->assertForbidden();} }
    public function test_viewer_is_read_only_and_inactive_account_is_rejected(): void { $viewer=$this->user('viewer_eksekutif'); $this->actingAs($viewer)->get('/dashboard')->assertOk(); $this->actingAs($viewer)->get('/data-entry')->assertForbidden(); $this->actingAs($viewer)->get('/review')->assertForbidden(); $this->actingAs($this->user('viewer_eksekutif',false))->get('/dashboard')->assertUnauthorized(); }
}
