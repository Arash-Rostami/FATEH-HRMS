<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('channel_members')) {
            Schema::create('channel_members', function (Blueprint $table) {
                $table->unsignedBigInteger('channel_id');
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('last_read_message_id')->nullable();
                $table->timestamp('joined_at')->useCurrent();
                $table->timestamp('entered_at')->nullable();
                $table->timestamps();
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');
                $table->primary(['user_id', 'channel_id'], 'channel_members_user_channel_primary');
                $table->index('channel_id', 'channel_members_channel_id_index');
                $table->foreign('channel_id', 'channel_members_channel_id_foreign')->references('id')->on('channels')->cascadeOnDelete();
                $table->foreign('user_id', 'channel_members_user_id_foreign')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('last_read_message_id', 'channel_members_last_read_message_id_foreign')->references('id')->on('channel_messages')->nullOnDelete();
            });
            DB::statement('ALTER TABLE channel_members ENGINE=InnoDB ROW_FORMAT=DYNAMIC');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_members');
    }
};
