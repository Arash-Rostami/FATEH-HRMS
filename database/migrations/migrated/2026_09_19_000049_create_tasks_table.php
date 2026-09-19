<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tasks')) {
            Schema::create('tasks', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('title', 191);
                $table->text('description')->nullable();
                $table->enum('status', ['todo', 'in-progress', 'pending', 'done'])->default('todo');
                $table->dateTime('deadline')->nullable();
                $table->unsignedBigInteger('assigned_to')->nullable();
                $table->foreignId('ticket_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
                $table->json('labels')->nullable();
                $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->nullable();
                $table->string('rank', 40)->nullable();
                $table->unsignedBigInteger('user_id');
                $table->timestamp('archived_at')->nullable()->index();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('completed_at')->nullable()->index();
                $table->foreignId('approved_by')->nullable()->constrained(table: 'users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
                $table->index('user_id', 'idx_user_id');
                $table->index('assigned_to', 'idx_assigned_to');
                $table->index(['status', 'assigned_to'], 'idx_status_assigned');
                $table->index(['status', 'user_id'], 'idx_status_user');
                $table->index(['project_id', 'status', 'deleted_at', 'rank'], 'idx_project_status_rank');
                $table->index('deadline', 'idx_tasks_deadline');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
