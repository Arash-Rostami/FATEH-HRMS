<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('channel_messages', 'reactions')) {
            Schema::table('channel_messages', function (Blueprint $table) {
                $table->json('reactions')->nullable()->after('attachments');
            });
        }

        if (!Schema::hasColumn('channels', 'pinned_message_id')) {
            Schema::table('channels', function (Blueprint $table) {
                $table->unsignedBigInteger('pinned_message_id')->nullable()->after('owner_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('channel_messages', 'reactions')) {
            Schema::table('channel_messages', function (Blueprint $table) {
                $table->dropColumn('reactions');
            });
        }

        if (Schema::hasColumn('channels', 'pinned_message_id')) {
            Schema::table('channels', function (Blueprint $table) {
                $table->dropColumn('pinned_message_id');
            });
        }
    }
};
