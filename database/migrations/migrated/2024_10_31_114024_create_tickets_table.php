<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            // Request Information
            $table->foreignId('requester_id')->constrained('users')->onDelete('cascade');
            $table->string('request_type');
            $table->string('request_area')->nullable();
            $table->string('request_subject');
            $table->text('description');
            $table->string('priority')->default('low')->nullable();
            $table->string('attachment')->nullable();
            // Completion and Responsibility Details
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->text('additional_notes')->nullable();
            $table->dateTime('completion_deadline')->nullable();
            $table->dateTime('completion_date')->nullable();
            $table->text('action_result')->nullable();
            // Status and Effectiveness
            $table->string('status')->default('open');
            $table->string('effectiveness')->nullable();
            $table->unsignedTinyInteger('satisfaction_score')->nullable();
            // Extra field for additional information
            $table->json('requester_files')->nullable();
            $table->json('assignee_files')->nullable();
            $table->json('extra')->nullable();

            $table->index('requester_id');
            $table->index('assigned_to');
            $table->index('status');
            $table->index('priority');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tickets');
    }
}
