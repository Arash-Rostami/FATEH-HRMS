<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('reviews')) {
            Schema::create('reviews', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->longText('comments')->nullable();
                $table->longText('actions')->nullable();
                $table->enum('feedback', ['agree', 'neutral', 'disagree', 'incomplete', 'unknown']);
                $table->text('department_id')->nullable();
                $table->boolean('complete')->default(0);
                $table->longText('referral')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('suggestion_id')->nullable();
                $table->timestamps();
                $table->index('feedback', 'reviews_feedback_index');
                $table->index('complete', 'reviews_complete_index');
                $table->index('user_id', 'reviews_user_id_index');
                $table->index('suggestion_id', 'reviews_suggestion_id_index');
                $table->index(['suggestion_id', 'complete'], 'reviews_suggestion_complete_index');
            });
            DB::statement('ALTER TABLE `reviews` ADD INDEX `reviews_department_index` (`department_id`(191))');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
