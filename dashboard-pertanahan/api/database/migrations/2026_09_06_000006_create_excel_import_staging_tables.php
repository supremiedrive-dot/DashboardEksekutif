<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_source_id')->constrained()->restrictOnDelete();
            $table->char('file_checksum', 64);
            $table->char('header_checksum', 64);
            $table->string('mapping_version', 50);
            $table->date('as_of_date');
            $table->string('file_name');
            $table->string('sheet_name', 150);
            $table->string('status', 30)->index();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('valid_rows')->default(0);
            $table->unsignedInteger('error_rows')->default(0);
            $table->unsignedInteger('staged_values')->default(0);
            $table->unsignedInteger('promoted_values')->default(0);
            $table->unsignedInteger('unchanged_values')->default(0);
            $table->unsignedInteger('quality_flags_count')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['file_checksum', 'as_of_date', 'mapping_version'], 'import_batch_idempotency_unique');
        });

        Schema::create('import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('region_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('sheet_name', 150);
            $table->unsignedInteger('row_number');
            $table->string('raw_region_name', 180)->nullable();
            $table->string('status', 30)->index();
            $table->json('errors')->nullable();
            $table->timestamps();
            $table->unique(['import_batch_id', 'sheet_name', 'row_number'], 'import_row_identity_unique');
        });

        Schema::create('import_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_row_id')->constrained()->cascadeOnDelete();
            $table->foreignId('indicator_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('column_name', 3);
            $table->string('cell_reference', 12);
            $table->text('source_period_label')->nullable();
            $table->text('raw_value')->nullable();
            $table->text('cached_value')->nullable();
            $table->text('formula')->nullable();
            $table->string('formula_type', 30)->nullable();
            $table->unsignedInteger('shared_formula_index')->nullable();
            $table->string('number_format', 255)->nullable();
            $table->json('normalized_value')->nullable();
            $table->string('status', 40)->index();
            $table->string('error_code', 80)->nullable()->index();
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->unique(['import_row_id', 'column_name']);
        });

        Schema::create('import_quality_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('import_row_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('import_value_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('observation_revision_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('rule_code', 80);
            $table->string('severity', 20);
            $table->string('cell_reference', 30)->nullable();
            $table->json('evidence')->nullable();
            $table->string('status', 30)->default('open');
            $table->timestamps();
            $table->index(['import_batch_id', 'rule_code']);
            $table->index(['rule_code', 'status']);
        });

        Schema::table('observation_revisions', function (Blueprint $table) {
            $table->foreignId('import_value_id')->nullable()->after('data_source_id')
                ->constrained()->restrictOnDelete();
        });

        DB::table('indicator_definitions')->where('version', 1)
            ->whereDate('valid_from', '>', '2026-08-04')
            ->update(['valid_from' => '2026-08-04']);
    }

    public function down(): void
    {
        Schema::table('observation_revisions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('import_value_id');
        });
        Schema::dropIfExists('import_quality_flags');
        Schema::dropIfExists('import_values');
        Schema::dropIfExists('import_rows');
        Schema::dropIfExists('import_batches');
    }
};
