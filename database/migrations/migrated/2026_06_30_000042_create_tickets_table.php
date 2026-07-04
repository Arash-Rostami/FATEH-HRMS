<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tickets')) {
            Schema::create('tickets', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('requester_id');
                $table->string('request_type', 191);
                $table->string('request_area', 191)->nullable();
                $table->string('request_subject', 191);
                $table->text('description');
                $table->string('priority', 191)->nullable()->default('low');
                $table->string('attachment', 191)->nullable();
                $table->text('additional_notes')->nullable();
                $table->unsignedBigInteger('assigned_to')->nullable();
                $table->dateTime('completion_deadline')->nullable();
                $table->dateTime('completion_date')->nullable();
                $table->text('action_result')->nullable();
                $table->string('status', 191)->default('open');
                $table->string('effectiveness', 191)->nullable();
                $table->unsignedTinyInteger('satisfaction_score')->nullable();
                $table->longText('requester_files')->charset('utf8mb4')->collation('utf8mb4_bin')->nullable();
                $table->longText('assignee_files')->charset('utf8mb4')->collation('utf8mb4_bin')->nullable();
                $table->longText('extra')->charset('utf8mb4')->collation('utf8mb4_bin')->nullable();
                $table->timestamps();
                $table->index('requester_id', 'idx_requester_id');
                $table->index(['status', 'assigned_to'], 'idx_status_assigned');
                $table->index(['completion_deadline', 'status'], 'idx_tickets_completion_deadline_status');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
