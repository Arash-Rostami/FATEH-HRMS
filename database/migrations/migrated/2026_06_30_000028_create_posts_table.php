<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('posts')) {
            Schema::create('posts', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('title', 191);
                $table->text('body');
                $table->string('image', 191)->nullable();
                $table->boolean('pinned')->default(0);
                $table->unsignedBigInteger('user_id');
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');
                $table->index('user_id', 'idx_user_id');
                $table->index('created_at', 'idx_posts_created_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};