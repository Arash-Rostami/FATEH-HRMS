<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('messages', 'reactions')) {
            Schema::table('messages', function (Blueprint $table) {
                $table->json('reactions')->nullable()->after('attachments');
            });
        }

        if (!Schema::hasTable('message_pins')) {
            Schema::create('message_pins', function (Blueprint $table) {
                $table->unsignedBigInteger('user_low_id');
                $table->unsignedBigInteger('user_high_id');
                $table->unsignedBigInteger('message_id');
                $table->primary(['user_low_id', 'user_high_id']);
                $table->foreign('message_id')->references('id')->on('messages')->cascadeOnDelete();
                $table->foreign('user_low_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('user_high_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        if (!Schema::hasColumn('channel_members', 'role')) {
            Schema::table('channel_members', function (Blueprint $table) {
                $table->string('role', 20)->default('member')->after('user_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('channel_members', 'role')) {
            Schema::table('channel_members', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        }

        Schema::dropIfExists('message_pins');

        if (Schema::hasColumn('messages', 'reactions')) {
            Schema::table('messages', function (Blueprint $table) {
                $table->dropColumn('reactions');
            });
        }
    }
};
