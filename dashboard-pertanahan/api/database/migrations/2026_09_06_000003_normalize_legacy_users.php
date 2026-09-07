<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        foreach ([
            ['super_admin', 'Super Admin'], ['admin_data_bpn', 'Admin Data BPN'],
            ['operator_pemda', 'Operator Pemda'], ['operator_kantah', 'Operator Kantah'],
            ['viewer_eksekutif', 'Viewer Eksekutif'],
        ] as [$code, $name]) {
            DB::table('roles')->updateOrInsert(['code' => $code], [
                'name' => $name, 'is_active' => true, 'updated_at' => $now, 'created_at' => $now,
            ]);
        }
        if (! Schema::hasColumn('users', 'is_active')) {
            Schema::table('users', fn (Blueprint $table) => $table->boolean('is_active')->default(true)->index());
        }
        if (! Schema::hasColumn('users', 'password')) {
            Schema::table('users', fn (Blueprint $table) => $table->string('password')->nullable());
        }
        if (Schema::hasColumn('users', 'password_hash')) {
            DB::table('users')->select(['id', 'password', 'password_hash'])->orderBy('id')->chunkById(100, function ($users) {
                foreach ($users as $user) {
                    if (is_string($user->password) && $user->password !== '') continue;
                    $legacyHash = is_string($user->password_hash) ? $user->password_hash : '';
                    if ($legacyHash !== '' && password_get_info($legacyHash)['algoName'] !== 'unknown') {
                        DB::table('users')->where('id', $user->id)->update(['password' => $legacyHash]);
                    } else {
                        DB::table('users')->where('id', $user->id)->update(['is_active' => false]);
                    }
                }
            });
        }
        if (Schema::hasColumn('users', 'role')) {
            $known = ['super_admin', 'admin_data_bpn', 'operator_pemda', 'operator_kantah', 'viewer_eksekutif'];
            foreach ($known as $code) {
                $roleId = DB::table('roles')->where('code', $code)->value('id');
                if ($roleId) {
                    DB::table('users')->where('role', $code)->orderBy('id')->eachById(function ($user) use ($roleId) {
                        DB::table('user_roles')->insertOrIgnore([
                            'user_id' => $user->id, 'role_id' => $roleId, 'created_at' => now(), 'updated_at' => now(),
                        ]);
                    });
                }
            }
            Schema::table('users', fn (Blueprint $table) => $table->dropColumn('role'));
        }
        if (Schema::hasColumn('users', 'role_id')) {
            Schema::table('users', function (Blueprint $table) {
                try { $table->dropForeign(['role_id']); } catch (Throwable) {}
                $table->dropColumn('role_id');
            });
        }
        if (Schema::hasColumn('users', 'password_hash')) {
            Schema::table('users', fn (Blueprint $table) => $table->dropColumn('password_hash'));
        }
    }

    public function down(): void
    {
        // Credential/role legacy columns are intentionally not recreated.
    }
};
