<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('notify_logs')) {
            Schema::create('notify_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('notify_rule_id')->nullable()->constrained('notify_rules')->cascadeOnDelete();
                $table->char('run_id', 26)->nullable()->index();
                $table->string('source', 16)->default('rule');
                $table->unsignedBigInteger('sender_id')->nullable()->index();
                $table->unsignedInteger('count')->default(1);
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('event', 16)->nullable();
                $table->string('record_id', 64)->nullable();
                $table->string('recipient', 191);
                $table->string('channel', 32);
                $table->string('status', 16);
                $table->string('error', 500)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->index(['notify_rule_id', 'created_at']);
                $table->index('created_at');
                $table->index(['status', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notify_logs');
    }
};
