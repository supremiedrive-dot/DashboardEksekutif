<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicateKey = DB::table('observations')
            ->select([
                'region_id',
                'reporting_snapshot_id',
                'indicator_id',
                'data_source_id',
                'dimension_key',
            ])
            ->groupBy('region_id', 'reporting_snapshot_id', 'indicator_id', 'data_source_id', 'dimension_key')
            ->havingRaw('COUNT(*) > 1')
            ->first();

        if ($duplicateKey) {
            throw new RuntimeException('Duplicate observation identities detected before reconciling the unique key. No data was removed or altered.');
        }

        Schema::table('observations', function (Blueprint $table) {
            $table->dropUnique('observations_identity_unique');
        });

        Schema::table('observations', function (Blueprint $table) {
            $table->unique([
                'region_id',
                'reporting_snapshot_id',
                'indicator_id',
                'data_source_id',
                'dimension_key',
            ], 'observations_identity_unique');
        });

        // The dictionary numerator for XIII.4 conflicts with its Roya label.
        // Remove the earlier placeholder formula without inventing a replacement.
        $indicatorId = DB::table('indicators')->where('canonical_code', 'XIII.4')->value('id');
        if ($indicatorId) {
            DB::table('indicator_definitions')->where('indicator_id', $indicatorId)->update([
                'formula_key' => null,
                'formula_metadata' => null,
            ]);
            DB::table('indicators')->where('id', $indicatorId)->update([
                'is_derived' => false,
                'quality_status' => 'definition_pending',
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('observations', function (Blueprint $table) {
            $table->dropUnique('observations_identity_unique');
        });

        Schema::table('observations', function (Blueprint $table) {
            $table->unique([
                'region_id',
                'reporting_snapshot_id',
                'indicator_id',
                'dimension_key',
            ], 'observations_identity_unique');
        });
    }
};
