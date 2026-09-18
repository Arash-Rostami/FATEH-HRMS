<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('reminders')) {
            Schema::create('reminders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->nullableMorphs('remindable');
                $table->string('title');
                $table->text('notes')->nullable();
                $table->dateTime('due_at')->index();
                $table->string('recurs')->default('none');
                $table->dateTime('snoozed_until')->nullable();
                $table->dateTime('completed_at')->nullable();
                $table->dateTime('notified_at')->nullable();
                $table->json('channels');
                $table->timestamps();
                $table->index(['user_id', 'completed_at', 'due_at'], 'idx_reminders_user_completed_due');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reminders');
    }
};
