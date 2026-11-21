<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('media')) {
            Schema::create('media', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('user_id')->constrained()->restrictOnDelete();
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->string('kind', 10);
                $table->string('title', 255);
                $table->string('topic', 160)->nullable();
                $table->longText('description')->nullable();
                $table->string('cover_image')->nullable();
                $table->string('file_path')->nullable();
                $table->string('transcript_path')->nullable();
                $table->string('chapters_path')->nullable();
                $table->string('icon', 40)->nullable();
                $table->unsignedInteger('duration_seconds')->nullable();
                $table->unsignedSmallInteger('position')->default(0);
                $table->boolean('active')->default(true);
                $table->boolean('pinned')->default(false);
                $table->string('department_id', 10)->nullable();
                $table->json('departments')->nullable();
                $table->date('report_date')->nullable();
                $table->date('expires_at')->nullable();
                $table->json('report_ids')->nullable();
                $table->timestamps();
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');
                $table->index(['parent_id', 'position']);
                $table->index('expires_at');
                $table->index(['pinned', 'created_at']);
            });

            Schema::table('media', function (Blueprint $table) {
                $table->foreign('parent_id')->references('id')->on('media')->restrictOnDelete();
            });
        }

        if (!Schema::hasTable('knowledge_progress')) {
            Schema::create('knowledge_progress', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('media_id')->nullable()->constrained('media')->cascadeOnDelete();
                $table->foreignId('report_id')->nullable()->constrained('reports')->cascadeOnDelete();
                $table->unsignedInteger('position')->default(0);
                $table->unsignedInteger('last_position')->default(0);
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('last_seen_at')->nullable();
                $table->timestamps();
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');
                $table->unique(['user_id', 'media_id']);
                $table->unique(['user_id', 'report_id']);
            });
        }

        if (!Schema::hasTable('media_notes')) {
            Schema::create('media_notes', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
                $table->unsignedInteger('position');
                $table->string('body', 500);
                $table->timestamps();
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');
                $table->index(['user_id', 'media_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('media_notes');
        Schema::dropIfExists('knowledge_progress');
        Schema::dropIfExists('media');
    }
};
