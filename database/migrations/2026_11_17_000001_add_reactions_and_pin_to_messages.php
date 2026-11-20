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

        if (!Schema::hasTable('launchpads')) {
            Schema::create('launchpads', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->restrictOnDelete();
                $table->string('department_id', 10)->charset('utf8mb4')->collation('utf8mb4_unicode_ci');
                $table->foreign('department_id')->references('code')->on('departments')->cascadeOnUpdate();
                $table->string('kind', 10)->default('static');
                $table->string('title', 150);
                $table->string('description', 500)->nullable();
                $table->char('folder', 26)->nullable();
                $table->boolean('active')->default(true);
                $table->json('audience')->nullable();
                $table->json('seen_by')->nullable();
                $table->date('accessible_from')->nullable();
                $table->date('accessible_to')->nullable();
                $table->date('expire_at')->nullable();
                $table->json('settings')->nullable();
                $table->text('source')->nullable();
                $table->timestamp('refresh_due_at')->nullable()->index();
                $table->string('title_key', 150)->nullable()->virtualAs('IF(deleted_at IS NULL, title, NULL)');
                $table->softDeletes();
                $table->timestamps();
                $table->unique(['department_id', 'title_key'], 'launchpads_department_title_unique');
            });
        }

        if (!Schema::hasColumn('launchpads', 'source')) {
            Schema::table('launchpads', function (Blueprint $table) {
                $table->text('source')->nullable()->after('settings');
            });
        }

        if (!Schema::hasColumn('launchpads', 'refresh_due_at')) {
            Schema::table('launchpads', function (Blueprint $table) {
                $table->timestamp('refresh_due_at')->nullable()->index()->after('source');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('launchpads');

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
