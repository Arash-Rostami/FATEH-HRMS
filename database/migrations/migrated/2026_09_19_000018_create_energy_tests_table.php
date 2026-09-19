<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('energy_tests')) {
            Schema::create('energy_tests', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id');
                $table->tinyInteger('mind_score');
                $table->tinyInteger('emotion_score');
                $table->tinyInteger('physique_score');
                $table->tinyInteger('soul_score');
                $table->tinyInteger('overall_score');
                $table->longText('answers')->charset('utf8mb4')->collation('utf8mb4_bin');
                $table->unsignedTinyInteger('month_index')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');
                $table->index('month_index', 'energy_tests_month_index_index');
                $table->index(['user_id', 'completed_at'], 'energy_tests_user_id_completed_at_index');
                $table->index(['overall_score', 'completed_at'], 'energy_tests_overall_score_completed_at_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('energy_tests');
    }
};
