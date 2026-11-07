<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ai_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 200)->nullable();
            $table->string('status', 12)->default('generating');
            $table->string('fail_reason', 32)->nullable();
            $table->string('preset_type', 32)->nullable();
            $table->string('preset_length', 32)->nullable();
            $table->string('preset_audience', 32)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamp('saved_at')->nullable();
            $table->json('spec')->nullable();
            $table->json('versions')->nullable();
            $table->json('recipients')->nullable();
            $table->json('seen_by')->nullable();
            $table->string('model', 64)->nullable();
            $table->unsignedInteger('tokens_total')->default(0);
            $table->unsignedBigInteger('forked_from')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'saved_at']);
            $table->index('status');
            $table->index('created_at', 'idx_ai_reports_created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_reports');
    }
};
