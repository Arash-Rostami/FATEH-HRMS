<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSuggestionsTable extends Migration
{

    public function up()
    {
        Schema::create('suggestions', function (Blueprint $table) {
            $table->id();
            $table->text('title');
            $table->longText('description');
            $table->json('departments')->nullable();
            $table->json('purpose');
            $table->json('rule');
            $table->text('attachment')->nullable();
            $table->enum('stage', [
                'pending',
                'team_remarks',
                'dept_remarks',
                'awaiting_decision',
                'accepted',
                'rejected',
                'under_review',
                'closed'
            ])->default('pending');
            $table->boolean('self_fill')->default(false);
            $table->boolean('abort')->default(false);
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->text('comments')->nullable();
            $table->foreignId('user_id')->constrained();
            $table->timestamps();

            $table->index('user_id');
            $table->index('stage');
            $table->index('priority');
            $table->index('abort');
            $table->index('self_fill');
        });
    }


    public function down()
    {
        Schema::dropIfExists('suggestions');
    }
}
