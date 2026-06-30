<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('ads')) {
            Schema::create('ads', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('position')->nullable();
                $table->text('certificate')->nullable();
                $table->text('skill')->nullable();
                $table->text('experience')->nullable();
                $table->enum('gender', ['Male', 'Female', 'Any'])->default('Any');
                $table->string('link');
                $table->boolean('active')->default(true);
                $table->json('extra')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');
                $table->index('position', 'ads_position_index');
                $table->index('gender', 'ads_gender_index');
                $table->index('active', 'ads_active_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ads');
    }
};