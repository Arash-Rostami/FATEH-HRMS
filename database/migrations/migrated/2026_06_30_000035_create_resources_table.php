<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('resources')) {
            Schema::create('resources', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name', 255);
                $table->string('type', 255);
                $table->json('metadata')->nullable();
                $table->string('status', 255)->default('active');
                $table->string('image', 255)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');
                $table->index(['type', 'status'], 'idx_resources_type_status');
                $table->index('name', 'idx_resources_name');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('resources');
    }
};