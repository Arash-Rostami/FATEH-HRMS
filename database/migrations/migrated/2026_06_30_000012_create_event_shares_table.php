<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('event_shares')) {
            Schema::create('event_shares', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('event_id');
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('shared_by')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');
                $table->unique(['event_id', 'user_id'], 'event_shares_event_id_user_id_unique');
                $table->index('shared_by', 'event_shares_shared_by_foreign');
                $table->index(['user_id', 'event_id'], 'event_shares_user_id_event_id_index');
                $table->foreign('event_id', 'event_shares_event_id_foreign')->references('id')->on('events')->onDelete('cascade');
                $table->foreign('shared_by', 'event_shares_shared_by_foreign')->references('id')->on('users')->onDelete('set null');
                $table->foreign('user_id', 'event_shares_user_id_foreign')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('event_shares');
    }
};