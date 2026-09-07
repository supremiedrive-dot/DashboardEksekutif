<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reporting_snapshots', function (Blueprint $table) {
            $table->id();
            $table->date('as_of_date')->unique();
            $table->string('label', 120)->nullable();
            $table->string('status', 30)->default('open')->index();
            $table->timestamps();
        });
        Schema::create('observations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->constrained()->restrictOnDelete();
            $table->foreignId('reporting_snapshot_id')->constrained()->restrictOnDelete();
            $table->foreignId('indicator_id')->constrained()->restrictOnDelete();
            $table->foreignId('data_source_id')->constrained()->restrictOnDelete();
            $table->string('dimension_key', 128)->default('total');
            $table->timestamps();
            $table->unique(['region_id', 'reporting_snapshot_id', 'indicator_id', 'data_source_id', 'dimension_key'], 'observations_identity_unique');
            $table->index(['region_id', 'reporting_snapshot_id']);
            $table->index(['indicator_id', 'reporting_snapshot_id']);
        });
        Schema::create('observation_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('observation_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('revision_number');
            $table->foreignId('data_owner_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('input_method', 30);
            $table->string('status', 30)->default('draft')->index();
            $table->decimal('value_decimal', 24, 6)->nullable();
            $table->bigInteger('value_integer')->nullable();
            $table->text('value_text')->nullable();
            $table->string('value_status_code', 40)->nullable();
            $table->unsignedSmallInteger('value_year')->nullable();
            $table->decimal('value_min', 24, 6)->nullable();
            $table->decimal('value_max', 24, 6)->nullable();
            $table->string('value_class', 80)->nullable();
            $table->string('missing_reason', 40)->nullable();
            $table->date('source_as_of')->nullable();
            $table->string('source_period_text', 255)->nullable();
            $table->string('source_sheet', 150)->nullable();
            $table->unsignedInteger('source_row')->nullable();
            $table->string('source_column', 10)->nullable();
            $table->text('raw_value')->nullable();
            $table->text('raw_formula')->nullable();
            $table->text('raw_result')->nullable();
            $table->char('source_checksum', 64)->nullable();
            $table->char('payload_checksum', 64);
            $table->timestamps();
            $table->unique(['observation_id', 'revision_number']);
        });
        Schema::create('quality_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('observation_revision_id')->constrained()->cascadeOnDelete();
            $table->string('rule_code', 80);
            $table->string('severity', 20);
            $table->string('status', 30)->default('open');
            $table->json('evidence')->nullable();
            $table->timestamps();
            $table->unique(['observation_revision_id', 'rule_code']);
            $table->index(['rule_code', 'status']);
        });
        Schema::create('audit_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 80);
            $table->string('entity_type', 120);
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('request_id', 80)->nullable();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['entity_type', 'entity_id', 'created_at'], 'audit_entity_index');
            $table->index(['actor_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
        Schema::dropIfExists('quality_flags');
        Schema::dropIfExists('observation_revisions');
        Schema::dropIfExists('observations');
        Schema::dropIfExists('reporting_snapshots');
    }
};
