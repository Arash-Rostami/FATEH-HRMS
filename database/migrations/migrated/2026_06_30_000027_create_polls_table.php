<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('polls')) {
            Schema::create('polls', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('feed_id');
                $table->unsignedSmallInteger('option_index');
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');
                $table->unique(['user_id', 'feed_id', 'option_index'], 'polls_user_id_feed_id_option_index_unique');
                $table->index('feed_id', 'polls_feed_id_index');
                $table->foreign('feed_id', 'polls_feed_id_foreign')->references('id')->on('feeds')->onDelete('cascade');
                $table->foreign('user_id', 'polls_user_id_foreign')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('polls');
    }
};