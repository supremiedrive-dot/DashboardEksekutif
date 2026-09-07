<?php
namespace Tests\Feature;
use App\Models\{Region,Role,User};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\{DB,Hash};
use Tests\TestCase;
class CreateDemoUserCommandTest extends TestCase
{
    use DatabaseTransactions;
    protected function setUp(): void { parent::setUp(); $this->assertSame('dashboard_pertanahan_test', DB::connection()->getDatabaseName()); }
    public function test_operator_command_assigns_exact_role_and_scope_without_secret_output(): void
    {
        $region=Region::where('level','regency_city')->firstOrFail();
        $this->artisan('dashboard:create-demo-user')->expectsQuestion('Name','Demo Pemda')->expectsQuestion('Email','pemda-test@example.test')->expectsQuestion('Password','secret')->expectsChoice('Role','operator_pemda',['super_admin','operator_pemda','operator_kantah','viewer_eksekutif'])->expectsChoice('Region',$region->name,Region::where('level','regency_city')->orderBy('name')->pluck('name')->all())->expectsOutputToContain('Role: operator_pemda')->doesntExpectOutput('secret')->assertExitCode(0)->run();
        $user=User::where('email','pemda-test@example.test')->firstOrFail(); $this->assertTrue(Hash::check('secret',$user->password)); $this->assertSame(['operator_pemda'],$user->roles()->pluck('code')->all()); $this->assertCount(1,$user->regionScopes);
    }
    public function test_role_change_requires_confirmation(): void
    {
        $user=User::create(['name'=>'Old','email'=>'old@example.test','password'=>Hash::make('old'),'is_active'=>true]); $user->roles()->attach(Role::where('code','super_admin')->firstOrFail());
        $this->artisan('dashboard:create-demo-user')->expectsQuestion('Name','Old')->expectsQuestion('Email','old@example.test')->expectsQuestion('Password','new')->expectsChoice('Role','operator_kantah',['super_admin','operator_pemda','operator_kantah','viewer_eksekutif'])->expectsConfirmation('Replace existing role?','no')->assertExitCode(1);
        $this->assertSame(['super_admin'],$user->fresh()->roles()->pluck('code')->all());
    }
}
