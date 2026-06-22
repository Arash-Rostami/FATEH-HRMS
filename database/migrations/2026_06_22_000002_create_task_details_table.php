<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('task_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('department_id')->nullable();
            $table->string('unit')->nullable();
            $table->string('section')->nullable();
            $table->string('project')->nullable();
            $table->string('scheme')->nullable();
            $table->text('action_source_domain')->nullable();
            $table->text('action_source')->nullable();
            $table->json('collaborators')->nullable();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('state')->nullable();
            $table->json('attachments')->nullable();
            $table->timestamps();

            $table->index('department_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_details');
    }
};
