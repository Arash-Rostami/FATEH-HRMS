<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('skills')) {
            Schema::create('skills', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name', 255);
                $table->string('name_en', 255)->nullable();
                $table->string('category', 255)->nullable();
                $table->text('description')->nullable();
                $table->string('icon', 255)->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_ghost')->default(false);
                $table->unsignedInteger('search_count')->default(0);
                $table->timestamp('last_searched_at')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');
                $table->unique('name', 'skills_name_unique');
                $table->index(['is_active', 'name'], 'skills_active_name_idx');
                $table->index(['is_active', 'name_en'], 'skills_active_name_en_idx');
                $table->index('category', 'skills_category_idx');
                $table->index(['is_ghost', 'last_searched_at'], 'skills_ghost_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('skills');
    }
};