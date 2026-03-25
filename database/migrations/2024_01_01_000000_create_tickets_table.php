<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users')->onDelete('cascade');
            $table->string('request_type')->default('support');
            $table->string('request_area');
            $table->string('request_subject');
            $table->text('description');
            $table->string('priority')->default('low');
            $table->string('attachment')->nullable();
            $table->text('additional_notes')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('completion_deadline')->nullable();
            $table->timestamp('completion_date')->nullable();
            $table->text('action_result')->nullable();
            $table->string('status')->default('open');
            $table->float('effectiveness')->nullable();
            $table->float('satisfaction_score')->nullable();
            $table->json('requester_files')->nullable();
            $table->json('assignee_files')->nullable();
            $table->json('extra')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
