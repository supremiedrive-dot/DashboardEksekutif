<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class LegacyUserNormalizationTest extends TestCase
{
    public function test_valid_legacy_hash_and_known_role_are_migrated_while_unknown_data_is_denied(): void
    {
        $this->assertSame('dashboard_pertanahan_test', DB::selectOne('SELECT DATABASE() AS db')->db);
        Schema::table('users', function (Blueprint $table) {
            $table->string('password_hash')->nullable();
            $table->string('role')->nullable();
        });
        $validHash = Hash::make(Str::random(40));
        $now = now();
        $knownId = DB::table('users')->insertGetId([
            'name'=>'Legacy known', 'email'=>Str::uuid().'@example.test', 'password'=>'',
            'password_hash'=>$validHash, 'role'=>'operator_pemda', 'is_active'=>true,
            'created_at'=>$now, 'updated_at'=>$now,
        ]);
        $unknownId = DB::table('users')->insertGetId([
            'name'=>'Legacy unknown', 'email'=>Str::uuid().'@example.test', 'password'=>'',
            'password_hash'=>'not-a-password-hash', 'role'=>'unknown_role', 'is_active'=>true,
            'created_at'=>$now, 'updated_at'=>$now,
        ]);

        $migration = require database_path('migrations/2026_09_06_000003_normalize_legacy_users.php');
        $migration->up();

        $known = DB::table('users')->find($knownId);
        $unknown = DB::table('users')->find($unknownId);
        $this->assertSame($validHash, $known->password);
        $this->assertTrue((bool) $known->is_active);
        $this->assertFalse((bool) $unknown->is_active);
        $this->assertSame(1, DB::table('user_roles')->where('user_id', $knownId)->count());
        $this->assertSame(0, DB::table('user_roles')->where('user_id', $unknownId)->count());
        $this->assertFalse(Schema::hasColumn('users', 'password_hash'));
        $this->assertFalse(Schema::hasColumn('users', 'role'));

        DB::table('users')->whereIn('id', [$knownId, $unknownId])->delete();
    }
}
