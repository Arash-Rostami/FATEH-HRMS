<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('task_details')) {
            Schema::create('task_details', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('task_id');
                $table->string('department_id', 255)->nullable();
                $table->string('unit', 255)->nullable();
                $table->string('section', 255)->nullable();
                $table->string('project', 255)->nullable();
                $table->string('scheme', 255)->nullable();
                $table->text('action_source_domain')->nullable();
                $table->text('action_source')->nullable();
                $table->json('collaborators')->nullable();
                $table->unsignedBigInteger('responsible_user_id')->nullable();
                $table->string('state', 255)->nullable();
                $table->json('attachments')->nullable();
                $table->timestamps();
                $table->unique('task_id', 'task_details_task_id_unique');
                $table->index('department_id', 'task_details_department_id_index');
                $table->foreign('responsible_user_id', 'task_details_responsible_user_id_foreign')->references('id')->on('users')->onDelete('set null');
                $table->foreign('task_id', 'task_details_task_id_foreign')->references('id')->on('tasks')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('task_details');
    }
};