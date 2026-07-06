<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('channel_messages')) {
            Schema::create('channel_messages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('channel_id');
                $table->unsignedBigInteger('sender_id')->nullable();
                $table->text('body');
                $table->json('attachments')->nullable();
                $table->unsignedBigInteger('reply_to_id')->nullable();
                $table->boolean('is_edited')->default(false);
                $table->timestamps();
                $table->softDeletes();
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');
                $table->foreign('channel_id', 'channel_messages_channel_id_foreign')->references('id')->on('channels')->cascadeOnDelete();
                $table->foreign('sender_id', 'channel_messages_sender_id_foreign')->references('id')->on('users')->nullOnDelete();
                $table->foreign('reply_to_id', 'channel_messages_reply_to_id_foreign')->references('id')->on('channel_messages')->nullOnDelete();
                $table->index(['channel_id', 'deleted_at', 'id'], 'idx_channel_messages_covering');
                $table->index('reply_to_id', 'channel_messages_reply_to_id_index');
                $table->fullText('body', 'idx_channel_messages_body_fulltext');
            });
            DB::statement('ALTER TABLE channel_messages ENGINE=InnoDB ROW_FORMAT=DYNAMIC');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_messages');
    }
};
