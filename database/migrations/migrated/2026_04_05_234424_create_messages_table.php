<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->json('attachments')->nullable();
            $table->foreignId('reply_to_id')->nullable()->constrained('messages')->nullOnDelete();
            $table->boolean('is_edited')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['sender_id', 'recipient_id', 'created_at'], 'idx_sender_recipient_created');
            $table->index(['recipient_id', 'sender_id', 'created_at'], 'idx_recipient_sender_created');
            $table->index(['recipient_id', 'read_at'], 'idx_recipient_read_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
