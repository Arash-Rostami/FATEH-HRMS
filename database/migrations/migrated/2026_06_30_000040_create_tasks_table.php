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
                $table->unsignedBigInteger('user_id');
                $table->timestamps();
                $table->softDeletes();
                $table->index('user_id', 'idx_user_id');
                $table->index('assigned_to', 'idx_assigned_to');
                $table->index(['status', 'assigned_to'], 'idx_status_assigned');
                $table->index(['status', 'user_id'], 'idx_status_user');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
