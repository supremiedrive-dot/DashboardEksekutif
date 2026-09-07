<?php

namespace Tests\Feature;

use App\Models\Indicator;
use App\Models\IndicatorDefinition;
use App\Models\Observation;
use App\Models\ObservationRevision;
use App\Models\Region;
use App\Models\Role;
use App\Models\User;
use App\Services\DefinitionValueValidator;
use Database\Seeders\IndicatorDefinitionSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ManualObservationApiTest extends TestCase
{
    use DatabaseTransactions;

    private Region $region;
    private Region $otherRegion;
    private Region $province;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('dashboard_pertanahan_test', DB::selectOne('SELECT DATABASE() AS db')->db);
        $this->assertNotSame('dashboard_pertanahan_dev', DB::connection()->getDatabaseName());
        $this->province = Region::where('level','province')->firstOrFail();
        $this->region = Region::where('level','regency_city')->orderBy('id')->firstOrFail();
        $this->otherRegion = Region::where('level','regency_city')->whereKeyNot($this->region->id)->firstOrFail();
    }

    private function user(string $role, ?Region $scope = null, bool $active = true): User
    {
        $user = User::create(['name'=>'API test','email'=>Str::uuid().'@example.test',
            'password'=>Str::random(40),'is_active'=>$active]);
        $user->roles()->attach(Role::where('code',$role)->firstOrFail());
        if ($scope) $user->regionScopes()->attach($scope);
        return $user;
    }

    private function login(User $user): void { Sanctum::actingAs($user, ['*']); }

    private function payload(string $code, mixed $value, ?Region $region = null,
        string $date = '2026-09-06'): array
    {
        return ['indicator_code'=>$code, 'region_id'=>($region ?? $this->region)->id,
            'as_of_date'=>$date, 'value'=>$value, 'note'=>'Catatan perubahan'];
    }

    public function test_definition_versions_resolve_by_date_without_changing_history_or_overlapping(): void
    {
        $indicator = Indicator::where('canonical_code','V.2')->firstOrFail();
        $v1 = $indicator->definitions()->firstOrFail();
        $originalType = $v1->value_type;
        $v1->update(['valid_to'=>'2026-12-31']);
        $v2 = IndicatorDefinition::create(['indicator_id'=>$indicator->id,'version'=>2,
            'valid_from'=>'2027-01-01','value_type'=>'decimal','unit'=>'Rp',
            'validation_rules'=>['type'=>'numeric'],'is_active'=>true]);
        $this->assertSame(1, $indicator->definitionEffectiveOn('2026-12-31')->version);
        $this->assertSame(2, $indicator->definitionEffectiveOn('2027-01-01')->version);
        $this->assertNull($indicator->definitionEffectiveOn('2026-08-03'));
        $this->assertSame($originalType, $v1->fresh()->value_type);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        IndicatorDefinition::create(['indicator_id'=>$indicator->id,'version'=>3,
            'valid_from'=>'2027-06-01','value_type'=>'decimal','unit'=>'Rp','is_active'=>true]);
    }

    public function test_definition_seeder_is_idempotent_and_excludes_map_feature(): void
    {
        $this->assertSame(57, IndicatorDefinition::count());
        $this->seed(IndicatorDefinitionSeeder::class);
        $this->assertSame(57, IndicatorDefinition::count());
        $this->assertSame(0, Indicator::where('canonical_code','MAP.1')->firstOrFail()->definitions()->count());
        $this->assertNotNull(Indicator::where('canonical_code','V.1')->firstOrFail()->definitions()->first()->formula_key);
        $this->assertNull(Indicator::where('canonical_code','XIII.4')->firstOrFail()->definitions()->first()->formula_key);
        $this->assertNull(Indicator::where('canonical_code','XIII.4')->firstOrFail()->definitions()->first()->formula_metadata);
    }

    public function test_value_validator_supports_documented_types_and_rejects_invalid_values(): void
    {
        $validator = app(DefinitionValueValidator::class);
        $definition = new IndicatorDefinition(['value_type'=>'percentage','validation_rules'=>['min'=>0,'max'=>100]]);
        $this->assertSame(['value_decimal'=>'25.500000'], $validator->normalize($definition, '25.5'));
        $definition->value_type = 'integer'; $definition->validation_rules = ['min'=>0];
        $this->assertSame(['value_integer'=>12], $validator->normalize($definition, 12));
        $definition->value_type = 'boolean'; $definition->validation_rules = [];
        $this->assertSame(['value_status_code'=>'true'], $validator->normalize($definition, true));
        $definition->value_type = 'text'; $definition->validation_rules = ['max_length'=>20];
        $this->assertSame(['value_text'=>'aman'], $validator->normalize($definition, 'aman'));
        $definition->value_type = 'percentage'; $definition->validation_rules = ['min'=>0,'max'=>100];
        try { $validator->normalize($definition, 101); $this->fail('Invalid percentage accepted.'); }
        catch (\Illuminate\Validation\ValidationException $e) { $this->assertArrayHasKey('value',$e->errors()); }
        $definition->value_type = 'integer';
        try { $validator->normalize($definition, 1.5); $this->fail('Invalid integer accepted.'); }
        catch (\Illuminate\Validation\ValidationException $e) { $this->assertArrayHasKey('value',$e->errors()); }
    }

    public function test_manual_ownership_role_scope_feature_derived_and_inactive_rules(): void
    {
        $cases = [
            ['operator_pemda',$this->region,'V.2',10,201],
            ['operator_pemda',$this->region,'XIII',10,403],
            ['operator_kantah',$this->region,'XIII',10,201],
            ['operator_kantah',$this->region,'V.2',10,403],
            ['admin_data_bpn',$this->region,'VI.1',10,403],
            ['viewer_eksekutif',$this->region,'V.2',10,403],
            ['operator_pemda',null,'V.2',10,403],
            ['operator_pemda',$this->otherRegion,'V.2',10,403],
            ['operator_pemda',$this->region,'V.1',10,403],
            ['operator_pemda',$this->region,'VI.3',10,403],
            ['operator_pemda',$this->region,'MAP.1',10,403],
        ];
        foreach ($cases as [$role,$scope,$code,$value,$status]) {
            $user = $this->user($role,$scope);
            $this->login($user);
            $response = $this->postJson('/api/domain/manual-observations',$this->payload($code,$value))->assertStatus($status);
            if ($status === 201) {
                $response->assertJsonPath('data.source_code', $role === 'operator_pemda' ? 'manual_pemda' : 'manual_kantah');
            }
        }
        $provinceUser = $this->user('operator_pemda',$this->province);
        $this->login($provinceUser);
        $this->postJson('/api/domain/manual-observations',$this->payload('V.3',20))->assertCreated();
        $inactive = $this->user('operator_pemda',$this->region,false);
        $this->login($inactive);
        $this->postJson('/api/domain/manual-observations',$this->payload('VI.2',2))->assertUnauthorized();
    }

    public function test_validation_rejects_invalid_value_date_source_and_forbidden_fields(): void
    {
        $this->login($this->user('operator_pemda',$this->region));
        $this->postJson('/api/domain/manual-observations',$this->payload('VI.2',1.5))->assertUnprocessable()->assertJsonValidationErrors('value');
        $this->postJson('/api/domain/manual-observations',$this->payload('V.2','not-number'))->assertUnprocessable()->assertJsonValidationErrors('value');
        $this->postJson('/api/domain/manual-observations',$this->payload('III.3','unknown'))->assertUnprocessable()->assertJsonValidationErrors('value');
        $this->postJson('/api/domain/manual-observations',$this->payload('V.2',2) + ['source_code'=>'manual_kantah'])
            ->assertUnprocessable()->assertJsonValidationErrors('request');
        $this->postJson('/api/domain/manual-observations',$this->payload('V.2',2,$this->region,'2026-08-03'))
            ->assertUnprocessable()->assertJsonValidationErrors('as_of_date');
        $payload = $this->payload('V.2',2) + ['user_id'=>999,'revision_number'=>99,'checksum'=>'x'];
        $this->postJson('/api/domain/manual-observations',$payload)->assertUnprocessable()->assertJsonValidationErrors('request');
    }

    public function test_draft_revision_lock_submit_publish_visibility_history_and_safe_audit(): void
    {
        $operator = $this->user('operator_pemda',$this->region);
        $this->login($operator);
        $draft = $this->postJson('/api/domain/manual-observations',$this->payload('V.2','100.25'))
            ->assertCreated()->assertJsonPath('data.revision_number',1)->assertJsonPath('data.status','draft')
            ->assertJsonPath('data.source_code','manual_pemda')->json('data');
        $observation = Observation::findOrFail($draft['observation_id']);
        $first = ObservationRevision::findOrFail($draft['id']);
        $this->assertSame('100.250000',$first->value_decimal);
        $this->assertSame(1,$first->definition->version);

        $this->postJson("/api/domain/observations/{$observation->id}/revisions",[
            'expected_revision'=>0,'value'=>101])
            ->assertStatus(422);
        $second = $this->postJson("/api/domain/observations/{$observation->id}/revisions",[
            'expected_revision'=>1,'value'=>101,'note'=>'private-note-marker'])
            ->assertCreated()->assertJsonPath('data.revision_number',2)->json('data');
        $this->assertSame('100.250000',$first->fresh()->value_decimal);
        try { $first->update(['value_decimal'=>999]); $this->fail('Immutable revision was updated.'); }
        catch (\LogicException $e) { $this->assertSame('Observation revision values are immutable.', $e->getMessage()); }
        $this->assertSame(2, ObservationRevision::where('observation_id',$observation->id)->count());
        $this->postJson("/api/domain/observations/{$observation->id}/revisions",[
            'expected_revision'=>1,'value'=>102])->assertConflict();
        $this->postJson("/api/domain/observations/{$observation->id}/revisions",[
            'expected_revision'=>2,'value'=>101,'note'=>'private-note-marker'])->assertConflict();

        $this->postJson("/api/domain/revisions/{$second['id']}/submit",['expected_revision'=>2,'reason'=>'Siap ditinjau'])
            ->assertOk()->assertJsonPath('data.status','submitted');
        $this->postJson("/api/domain/observations/{$observation->id}/revisions",[
            'expected_revision'=>2,'value'=>103])->assertConflict();
        $this->postJson("/api/domain/revisions/{$second['id']}/publish",['expected_revision'=>2])->assertForbidden();

        $viewer = $this->user('viewer_eksekutif',$this->region);
        $this->login($viewer);
        $this->getJson('/api/domain/observations?region_id='.$this->region->id.'&as_of_date=2026-09-06')
            ->assertOk()->assertJsonCount(0,'data');

        $admin = $this->user('super_admin');
        $this->login($admin);
        $this->postJson("/api/domain/revisions/{$second['id']}/publish",['expected_revision'=>2,'reason'=>'Disetujui'])
            ->assertOk()->assertJsonPath('data.status','published');
        $this->login($viewer);
        $this->getJson('/api/domain/observations?region_id='.$this->region->id.'&as_of_date=2026-09-06')
            ->assertOk()->assertJsonCount(1,'data')->assertJsonPath('data.0.revision.revision_number',2);
        $history = $this->getJson("/api/domain/observations/{$observation->id}/history")->assertOk();
        $history->assertJsonCount(1,'revisions.data')->assertJsonCount(0,'audit_events');

        $this->login($operator);
        $operatorHistory = $this->getJson("/api/domain/observations/{$observation->id}/history")->assertOk();
        $operatorHistory->assertJsonCount(2,'revisions.data');
        $auditJson = json_encode($operatorHistory->json('audit_events'));
        $this->assertStringNotContainsString('100.25',$auditJson);
        $this->assertStringNotContainsString('private-note-marker',$auditJson);
        $this->assertSame(4, DB::table('audit_events')->where('metadata->observation_id',$observation->id)->count());
        $this->assertSame(4, DB::table('revision_status_events')->whereIn('observation_revision_id',[$first->id,$second['id']])->count());
    }

    public function test_reject_requires_super_admin_and_invalid_transition_is_unprocessable(): void
    {
        $operator = $this->user('operator_kantah',$this->region); $this->login($operator);
        $draft = $this->postJson('/api/domain/manual-observations',$this->payload('XIII',5))->assertCreated()->json('data');
        $this->postJson("/api/domain/revisions/{$draft['id']}/reject",['expected_revision'=>1])->assertForbidden();
        $admin = $this->user('super_admin'); $this->login($admin);
        $this->postJson("/api/domain/revisions/{$draft['id']}/reject",['expected_revision'=>1])
            ->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->login($operator);
        $this->postJson("/api/domain/revisions/{$draft['id']}/submit",['expected_revision'=>1])->assertOk();
        $this->login($admin);
        $this->postJson("/api/domain/revisions/{$draft['id']}/reject",['expected_revision'=>1,'reason'=>'Perbaiki sumber'])
            ->assertOk()->assertJsonPath('data.status','rejected');
    }

    public function test_catalogue_is_paginated_and_date_filter_never_falls_back(): void
    {
        $viewer = $this->user('viewer_eksekutif',$this->region); $this->login($viewer);
        $this->getJson('/api/domain/indicators?as_of_date=2026-09-06&per_page=10')
            ->assertOk()->assertJsonCount(10,'data')->assertJsonStructure(['data','current_page','last_page','per_page','total']);
        $this->getJson('/api/domain/indicators?as_of_date=2026-08-03&per_page=100')
            ->assertOk()->assertJsonPath('data.0.definition',null);
        $this->getJson('/api/domain/observations?region_id='.$this->region->id.'&as_of_date=2030-01-01')
            ->assertOk()->assertJsonCount(0,'data');
        $this->getJson('/api/domain/observations?region_id='.$this->otherRegion->id.'&as_of_date=2026-09-06')->assertForbidden();
    }

    public function test_domain_api_requires_sanctum(): void
    {
        $this->getJson('/api/domain/indicators?as_of_date=2026-09-06')->assertUnauthorized();
        $this->postJson('/api/domain/manual-observations',$this->payload('V.2',1))->assertUnauthorized();
    }

    public function test_observation_identity_includes_data_source_id(): void
    {
        $snapshot = \App\Models\ReportingSnapshot::firstOrCreate(['as_of_date'=>'2026-09-06'], ['status'=>'open']);
        $indicator = Indicator::where('canonical_code', 'V.2')->firstOrFail();
        $region = $this->region;
        $sourceA = \App\Models\DataSource::where('code', 'manual_pemda')->firstOrFail();
        $sourceB = \App\Models\DataSource::where('code', 'manual_kantah')->firstOrFail();

        Observation::create([
            'region_id'=>$region->id,
            'reporting_snapshot_id'=>$snapshot->id,
            'indicator_id'=>$indicator->id,
            'data_source_id'=>$sourceA->id,
            'dimension_key'=>'total',
        ]);

        $second = Observation::create([
            'region_id'=>$region->id,
            'reporting_snapshot_id'=>$snapshot->id,
            'indicator_id'=>$indicator->id,
            'data_source_id'=>$sourceB->id,
            'dimension_key'=>'total',
        ]);

        $this->assertNotNull($second->id);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Observation::create([
            'region_id'=>$region->id,
            'reporting_snapshot_id'=>$snapshot->id,
            'indicator_id'=>$indicator->id,
            'data_source_id'=>$sourceA->id,
            'dimension_key'=>'total',
        ]);
    }
}
