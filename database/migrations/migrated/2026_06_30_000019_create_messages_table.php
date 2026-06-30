<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('messages')) {
            Schema::create('messages', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('sender_id');
                $table->unsignedBigInteger('recipient_id');
                $table->text('body');
                $table->json('attachments')->nullable();
                $table->unsignedBigInteger('reply_to_id')->nullable();
                $table->boolean('is_edited')->default(0);
                $table->timestamp('read_at')->nullable();
                $table->timestamp('deleted_at')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');
                $table->index('reply_to_id', 'messages_reply_to_id_index');
                $table->index('deleted_at', 'messages_deleted_at_index');
                $table->index(['sender_id', 'deleted_at', 'recipient_id', 'id'], 'idx_sent_covering');
                $table->index(['recipient_id', 'deleted_at', 'read_at', 'sender_id', 'id'], 'idx_received_covering');
                $table->foreign('recipient_id', 'messages_recipient_id_foreign')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('reply_to_id', 'messages_reply_to_id_foreign')->references('id')->on('messages')->onDelete('set null');
                $table->foreign('sender_id', 'messages_sender_id_foreign')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};