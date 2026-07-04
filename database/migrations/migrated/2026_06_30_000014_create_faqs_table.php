<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('faqs')) {
            Schema::create('faqs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('department_id', 10)->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('category', 191);
                $table->text('question');
                $table->text('answer');
                $table->timestamps();
                $table->index('user_id', 'faqs_user_id_foreign');
                $table->index('department_id', 'idx_department_id');
                $table->foreign('user_id', 'faqs_user_id_foreign')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};
