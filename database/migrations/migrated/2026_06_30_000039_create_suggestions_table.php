<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('suggestions')) {
            Schema::create('suggestions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->text('title');
                $table->longText('description');
                $table->longText('departments')->charset('utf8mb4')->collation('utf8mb4_bin')->nullable();
                $table->longText('purpose')->charset('utf8mb4')->collation('utf8mb4_bin');
                $table->longText('rule')->charset('utf8mb4')->collation('utf8mb4_bin');
                $table->text('attachment')->nullable();
                $table->enum('stage', ['pending', 'team_remarks', 'dept_remarks', 'awaiting_decision', 'accepted', 'rejected', 'under_review', 'closed'])->default('pending');
                $table->boolean('self_fill')->default(0);
                $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
                $table->text('comments')->nullable();
                $table->boolean('abort')->default(0);
                $table->unsignedBigInteger('user_id');
                $table->timestamps();
                $table->index('abort', 'suggestions_abort_index');
                $table->index('user_id', 'suggestions_user_id_index');
                $table->index('stage', 'suggestions_stage_index');
                $table->index('priority', 'suggestions_priority_index');
                $table->index('self_fill', 'suggestions_self_fill_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('suggestions');
    }
};