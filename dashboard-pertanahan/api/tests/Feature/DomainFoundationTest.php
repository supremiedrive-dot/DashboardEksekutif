<?php

namespace Tests\Feature;

use App\Models\Indicator;
use App\Models\Region;
use App\Models\Role;
use App\Models\User;
use App\Policies\IndicatorPolicy;
use Database\Seeders\DomainMasterDataSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class DomainFoundationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $connection = DB::connection();
        $this->assertTrue($this->app->environment('testing'));
        $this->assertSame('mysql', config('database.default'));
        $this->assertSame('dashboard_pertanahan_test', $connection->getDatabaseName());
        $this->assertSame('dashboard_pertanahan_test', $connection->selectOne('SELECT DATABASE() AS db')->db);
        $this->assertNotSame('dashboard_pertanahan_dev', $connection->getDatabaseName());
    }

    private function user(string $role, ?Region $scope = null, bool $active = true): User
    {
        $user = User::create([
            'name'=>'Policy test', 'email'=>Str::uuid().'@example.test',
            'password'=>Str::random(40), 'is_active'=>$active,
        ]);
        if ($role !== '') $user->roles()->attach(Role::where('code', $role)->firstOrFail());
        if ($scope) $user->regionScopes()->attach($scope);
        return $user;
    }

    public function test_schema_was_built_from_migrations_and_contains_canonical_constraints(): void
    {
        $migrations = DB::table('migrations')->pluck('migration');
        $this->assertCount(11, $migrations);
        $this->assertTrue($migrations->contains('2026_09_06_000003_normalize_legacy_users'));
        $this->assertTrue($migrations->contains('2026_09_06_000005_reconcile_observation_identity_and_definition'));
        $this->assertTrue($migrations->contains('2026_09_06_000006_create_excel_import_staging_tables'));
        foreach (['roles','user_roles','regions','user_region_scopes','data_owners','data_sources',
            'categories','submenus','indicators','reporting_snapshots','observations',
            'observation_revisions','quality_flags','audit_events','indicator_definitions',
            'revision_status_events','published_values','import_batches','import_rows','import_values',
            'import_quality_flags'] as $table) {
            $this->assertTrue(DB::getSchemaBuilder()->hasTable($table), $table);
        }
        $this->assertFalse(DB::getSchemaBuilder()->hasColumn('users', 'role'));
        $this->assertFalse(DB::getSchemaBuilder()->hasColumn('users', 'role_id'));
        $this->assertFalse(DB::getSchemaBuilder()->hasColumn('users', 'password_hash'));
        $this->assertTrue(DB::getSchemaBuilder()->hasColumn('users', 'password'));
        $this->assertTrue(DB::getSchemaBuilder()->hasColumn('users', 'is_active'));
    }

    public function test_master_seed_is_complete_and_idempotent(): void
    {
        $expected = ['roles'=>5,'regions'=>26,'data_owners'=>5,'data_sources'=>6,
            'categories'=>3,'submenus'=>7,'indicators'=>58];
        foreach ($expected as $table=>$count) $this->assertSame($count, DB::table($table)->count());
        $this->seed(DomainMasterDataSeeder::class);
        foreach ($expected as $table=>$count) $this->assertSame($count, DB::table($table)->count());
        $this->assertSame(0, DB::table('observations')->count());
        $this->assertSame(0, DB::table('observation_revisions')->count());
        $this->assertSame(0, DB::table('users')->count());
    }

    public function test_region_hierarchy_contains_only_documented_levels_and_names(): void
    {
        $province = Region::where('internal_code', 'jawa-barat')->firstOrFail();
        $this->assertNull($province->parent_id);
        $this->assertSame('province', $province->level);
        $this->assertCount(25, $province->children);
        $this->assertTrue($province->children->every(fn ($region) => $region->level === 'regency_city'));
        $this->assertFalse(Region::where('name', 'Kabupaten Bogor')->exists());
        $this->assertFalse(Region::where('name', 'Kabupaten Pangandaran')->exists());
        $this->assertSame(0, Region::whereNotNull('bps_code')->count());
        $this->assertSame(0, Region::whereNotNull('kemendagri_code')->count());
    }

    public function test_map_normalization_and_all_physical_dictionary_rows_are_preserved(): void
    {
        $this->assertSame(58, Indicator::count());
        $this->assertSame(58, Indicator::distinct()->count('canonical_code'));
        $this->assertSame(2, Indicator::where('source_code', 'XIX.1')->count());
        $service = Indicator::where('canonical_code', 'XIX.1')->firstOrFail();
        $map = Indicator::where('canonical_code', 'MAP.1')->firstOrFail();
        $this->assertSame(15, $service->source_row);
        $this->assertSame('XIX.1', $map->source_code);
        $this->assertSame(62, $map->source_row);
        $this->assertTrue($map->is_feature);
        $this->assertFalse($map->allows_manual_input);
    }

    public function test_role_and_region_assignments_are_independent_and_unique(): void
    {
        $region = Region::where('level', 'regency_city')->firstOrFail();
        $user = $this->user('operator_pemda');
        $this->assertCount(1, $user->roles);
        $this->assertCount(0, $user->regionScopes);
        $user->regionScopes()->attach($region);
        $this->assertCount(1, $user->fresh()->regionScopes);
        $this->expectException(QueryException::class);
        DB::table('user_region_scopes')->insert(['user_id'=>$user->id,'region_id'=>$region->id,
            'created_at'=>now(),'updated_at'=>now()]);
    }

    public function test_foreign_keys_reject_orphans(): void
    {
        $this->expectException(QueryException::class);
        DB::table('user_roles')->insert(['user_id'=>999999,'role_id'=>999999,
            'created_at'=>now(),'updated_at'=>now()]);
    }

    public function test_policy_enforces_active_role_scope_owner_and_viewer_read_only(): void
    {
        $policy = new IndicatorPolicy;
        $province = Region::where('level', 'province')->firstOrFail();
        $region = Region::where('level', 'regency_city')->firstOrFail();
        $pemda = Indicator::where('canonical_code', 'V.2')->firstOrFail();
        $kantah = Indicator::where('canonical_code', 'XIII')->firstOrFail();
        $bpn = Indicator::where('canonical_code', 'VI.1')->firstOrFail();

        $pemdaUser = $this->user('operator_pemda', $region);
        $this->assertTrue($policy->update($pemdaUser, $pemda, $region));
        $this->assertFalse($policy->update($pemdaUser, $kantah, $region));
        $this->assertFalse($policy->import($pemdaUser, $bpn, $region));
        $this->assertFalse($policy->update($this->user('operator_pemda'), $pemda, $region));
        $this->assertFalse($policy->view($this->user('', $region), $pemda, $region));
        $unknownRole = Role::create(['code'=>'unknown_role','name'=>'Unknown','is_active'=>true]);
        $unknownUser = $this->user('', $region);
        $unknownUser->roles()->attach($unknownRole);
        $this->assertFalse($policy->view($unknownUser, $pemda, $region));
        $this->assertFalse($policy->view($this->user('operator_pemda', $region, false), $pemda, $region));

        $kantahUser = $this->user('operator_kantah', $province);
        $this->assertTrue($policy->update($kantahUser, $kantah, $region));
        $this->assertFalse($policy->update($kantahUser, $pemda, $region));
        $bpnUser = $this->user('admin_data_bpn', $region);
        $this->assertTrue($policy->import($bpnUser, $bpn, $region));
        $this->assertFalse($policy->update($bpnUser, $pemda, $region));
        $viewer = $this->user('viewer_eksekutif', $region);
        $this->assertTrue($policy->view($viewer, $pemda, $region));
        $this->assertFalse($policy->update($viewer, $pemda, $region));
        $this->assertFalse($policy->import($viewer, $bpn, $region));
        $unknownOwnerIndicator = Indicator::where('canonical_code', 'XII.3')->firstOrFail();
        $unknownOwnerIndicator->owner()->update(['code'=>'unknown_owner']);
        $this->assertFalse($policy->view($this->user('super_admin'), $unknownOwnerIndicator, $region));
    }

    public function test_super_admin_is_explicitly_cross_region_but_cannot_manually_write_bhumi_or_derived(): void
    {
        $policy = new IndicatorPolicy;
        $admin = $this->user('super_admin');
        $region = Region::where('level', 'regency_city')->firstOrFail();
        $this->assertTrue($policy->view($admin, Indicator::where('canonical_code','V.2')->firstOrFail(), $region));
        $this->assertTrue($policy->update($admin, Indicator::where('canonical_code','V.2')->firstOrFail(), $region));
        $this->assertFalse($policy->update($admin, Indicator::where('canonical_code','V.1')->firstOrFail(), $region));
        $this->assertFalse($policy->update($admin, Indicator::where('canonical_code','MAP.1')->firstOrFail(), $region));
    }

    public function test_inactive_account_cannot_login_and_existing_token_is_revoked(): void
    {
        $user = $this->user('', null, false);
        $password = Str::random(40);
        $user->update(['password'=>$password]);
        $this->postJson('/api/login', ['email'=>$user->email,'password'=>$password])
            ->assertUnauthorized()->assertExactJson(['message'=>'Email atau password salah']);
        $token = $user->createToken('inactive-test')->plainTextToken;
        $this->withToken($token)->getJson('/api/user')->assertUnauthorized();
        $this->assertSame(0, $user->tokens()->count());
    }
}
