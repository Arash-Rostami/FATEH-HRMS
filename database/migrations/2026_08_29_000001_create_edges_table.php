<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('edges')) {
            Schema::create('edges', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id');
                $table->string('edge_key', 100);
                $table->string('subject_type', 150);
                $table->string('subject_id', 64);
                $table->string('icon')->nullable();
                $table->string('title');
                $table->text('body')->nullable();
                $table->string('url')->nullable();
                $table->timestamp('dismissed_at')->nullable();
                $table->timestamp('snoozed_until')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');
                $table->foreign('user_id', 'fk_edges_user_id')->references('id')->on('users')->onDelete('cascade');
                $table->unique(['user_id', 'edge_key', 'subject_type', 'subject_id'], 'uq_edges_user_key_subject');
                $table->index(['user_id', 'dismissed_at', 'snoozed_until'], 'idx_edges_user_dismiss_snooze');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('edges');
    }
};