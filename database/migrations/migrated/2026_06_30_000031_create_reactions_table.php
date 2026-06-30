<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('reactions')) {
            Schema::create('reactions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('feed_id');
                $table->string('emoji', 191);
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');
                $table->unique(['user_id', 'feed_id', 'emoji'], 'reactions_user_feed_emoji_unique');
                $table->index('feed_id', 'reactions_feed_id_index');
                $table->index(['user_id', 'feed_id'], 'reactions_user_feed_index');
                $table->index('created_at', 'reactions_created_at_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reactions');
    }
};