<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicator_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicator_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('version');
            $table->date('valid_from');
            $table->date('valid_to')->nullable();
            $table->string('value_type', 30);
            $table->string('unit', 50)->nullable();
            $table->json('validation_rules')->nullable();
            $table->string('formula_key', 100)->nullable();
            $table->json('formula_metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['indicator_id', 'version']);
            $table->index(['indicator_id', 'is_active', 'valid_from', 'valid_to'], 'definition_effective_index');
        });

        Schema::table('observations', function (Blueprint $table) {
            $table->dropUnique('observations_identity_unique');
            $table->unique(
                ['region_id', 'reporting_snapshot_id', 'indicator_id', 'dimension_key'],
                'observations_identity_unique'
            );
        });

        Schema::table('observation_revisions', function (Blueprint $table) {
            $table->foreignId('indicator_definition_id')->nullable()->after('observation_id')
                ->constrained()->restrictOnDelete();
            $table->foreignId('data_source_id')->nullable()->after('data_owner_id')
                ->constrained()->restrictOnDelete();
            $table->text('change_note')->nullable()->after('input_method');
        });

        Schema::create('revision_status_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('observation_revision_id')->constrained()->restrictOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['observation_revision_id', 'created_at'], 'revision_status_timeline_index');
        });

        Schema::create('published_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('observation_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('observation_revision_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('published_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('published_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('published_values');
        Schema::dropIfExists('revision_status_events');
        Schema::table('observation_revisions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('data_source_id');
            $table->dropConstrainedForeignId('indicator_definition_id');
            $table->dropColumn('change_note');
        });
        Schema::table('observations', function (Blueprint $table) {
            $table->dropUnique('observations_identity_unique');
            $table->unique(
                ['region_id', 'reporting_snapshot_id', 'indicator_id', 'data_source_id', 'dimension_key'],
                'observations_identity_unique'
            );
        });
        Schema::dropIfExists('indicator_definitions');
    }
};
