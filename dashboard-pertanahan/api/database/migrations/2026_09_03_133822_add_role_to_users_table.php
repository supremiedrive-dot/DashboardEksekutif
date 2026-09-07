<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Legacy compatibility is handled by the later normalization migration.
    }

    public function down(): void
    {
        // Intentionally empty.
    }
};
