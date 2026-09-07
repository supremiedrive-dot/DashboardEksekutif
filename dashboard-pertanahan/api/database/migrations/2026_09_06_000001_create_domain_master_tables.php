<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->string('description', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('user_roles', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['user_id', 'role_id']);
        });
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('regions')->restrictOnDelete();
            $table->string('level', 30);
            $table->string('internal_code', 80)->unique();
            $table->string('name', 150);
            $table->string('bps_code', 32)->nullable()->unique();
            $table->string('kemendagri_code', 32)->nullable()->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['parent_id', 'level']);
        });
        Schema::create('user_region_scopes', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('region_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['user_id', 'region_id']);
            $table->index(['region_id', 'user_id']);
        });
        Schema::create('data_owners', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 120);
            $table->string('organization_type', 50);
            $table->boolean('allows_manual_input')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('data_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_owner_id')->constrained()->restrictOnDelete();
            $table->string('code', 60)->unique();
            $table->string('name', 120);
            $table->string('channel', 30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 120);
            $table->unsignedSmallInteger('display_order');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('submenus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('code', 60)->unique();
            $table->string('name', 150);
            $table->unsignedSmallInteger('display_order');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['category_id', 'display_order']);
        });
        Schema::create('indicators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submenu_id')->constrained()->restrictOnDelete();
            $table->foreignId('data_owner_id')->constrained()->restrictOnDelete();
            $table->string('canonical_code', 80)->unique();
            $table->string('source_code', 80)->index();
            $table->unsignedSmallInteger('source_row')->nullable();
            $table->string('name', 255);
            $table->string('value_type', 30);
            $table->string('unit', 50)->nullable();
            $table->unsignedSmallInteger('display_order');
            $table->boolean('is_derived')->default(false);
            $table->boolean('allows_manual_input')->default(false);
            $table->boolean('is_feature')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('no_data_policy', 80)->default('explicit_no_data');
            $table->string('quality_status', 50)->default('pending_validation');
            $table->timestamps();
            $table->unique(['submenu_id', 'display_order']);
            $table->index(['data_owner_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicators');
        Schema::dropIfExists('submenus');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('data_sources');
        Schema::dropIfExists('data_owners');
        Schema::dropIfExists('user_region_scopes');
        Schema::dropIfExists('regions');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('roles');
    }
};
