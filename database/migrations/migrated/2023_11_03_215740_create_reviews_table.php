<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReviewsTable extends Migration
{

    public function up()
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->longText('comments')->nullable();
            $table->longText('actions')->nullable();
            $table->enum('feedback', ['agree', 'neutral', 'disagree', 'incomplete', 'unknown']);
            $table->text('department_id')->nullable();
            $table->boolean('complete')->default(false);
            $table->json('referral')->nullable();
            $table->foreignId('user_id')->constrained('users')->onDelete('set null');
            $table->foreignId('suggestion_id')->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('feedback');
            $table->index('department');
            $table->index('complete');
            $table->index('user_id');
            $table->index('suggestion_id');
        });
    }


    public function down()
    {
        Schema::dropIfExists('reviews');
    }
}
