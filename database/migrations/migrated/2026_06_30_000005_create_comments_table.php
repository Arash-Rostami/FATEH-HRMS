<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('comments')) {
            Schema::create('comments', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('feed_id');
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->text('content');
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');
                $table->index('id', 'comments_id_index');
                $table->index('user_id', 'comments_user_id_index');
                $table->index('feed_id', 'comments_feed_id_index');
                $table->index(['feed_id', 'created_at'], 'comments_feed_created_index');
                $table->index(['user_id', 'created_at'], 'comments_user_created_index');
                $table->index('parent_id', 'comments_parent_id_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};