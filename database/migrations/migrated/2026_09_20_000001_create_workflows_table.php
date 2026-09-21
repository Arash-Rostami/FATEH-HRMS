<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('workflows')) {
            Schema::create('workflows', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name', 255);
                $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('project_id')->nullable()->constrained('projects')->cascadeOnDelete();
                $table->foreignId('template_id')->nullable()->constrained('workflows')->nullOnDelete();
                $table->json('steps');
                $table->unsignedTinyInteger('current_step')->nullable();
                $table->string('status', 20)->default('draft');
                $table->json('step_log')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
                $table->index(['project_id', 'status'], 'idx_workflows_project_status');
                $table->index('owner_id', 'idx_workflows_owner');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('workflows');
    }
};
