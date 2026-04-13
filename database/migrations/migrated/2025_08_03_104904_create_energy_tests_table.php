<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEnergyTestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('energy_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->tinyInteger('mind_score');
            $table->tinyInteger('emotion_score');
            $table->tinyInteger('physique_score');
            $table->tinyInteger('soul_score');
            $table->tinyInteger('overall_score');

            $table->json('answers');
            $table->tinyInteger('month_index')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('mind_score');
            $table->index('emotion_score');
            $table->index('physique_score');
            $table->index('soul_score');
            $table->index('overall_score');
            $table->index('completed_at');

            $table->index(['user_id', 'completed_at']);
            $table->index(['overall_score', 'completed_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('energy_tests');
    }
}
