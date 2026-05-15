<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('is_super_admin')->default(false);
            $table->json('abilities')->nullable();
            $table->json('excluded_modules')->nullable();
            $table->timestamps();

            $table->rawIndex('is_super_admin, (CAST(abilities AS CHAR(255) ARRAY))', 'admin_abilities_idx');
            $table->rawIndex('is_super_admin, (CAST(excluded_modules AS CHAR(255) ARRAY))', 'admin_excluded_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
