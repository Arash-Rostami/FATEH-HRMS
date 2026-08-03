<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('skill_user')) {
            Schema::create('skill_user', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('skill_id');
                $table->string('status', 255)->default('pending');
                $table->string('requested_name', 255)->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->string('last_used_context', 255)->nullable();
                $table->boolean('is_private')->default(false);
                $table->boolean('is_mentoring')->default(false);
                $table->timestamp('approved_at')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->text('rejected_reason')->nullable();
                $table->json('endorsers')->nullable();
                $table->unsignedInteger('endorsements_count')->default(0);
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');
                $table->unique(['user_id', 'skill_id'], 'skill_user_user_skill_unique');
                $table->index('skill_id', 'skill_user_skill_id_idx');
                $table->index('status', 'skill_user_status_idx');
                $table->index('last_used_at', 'skill_user_last_used_at_idx');
                $table->index('is_mentoring', 'skill_user_is_mentoring_idx');
                $table->index('endorsements_count', 'skill_user_endorsements_count_idx');
                $table->index(['skill_id', 'status', 'is_private', 'is_mentoring', 'last_used_at'], 'skill_user_directory_idx');
                $table->index(['user_id', 'status'], 'skill_user_profile_idx');
                $table->index(['status', 'created_at'], 'skill_user_queue_idx');
                $table->foreign('user_id', 'fk_skill_user_user')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('skill_id', 'fk_skill_user_skill')->references('id')->on('skills')->onDelete('restrict');
                $table->foreign('approved_by', 'fk_skill_user_approved_by')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('skill_user');
    }
};