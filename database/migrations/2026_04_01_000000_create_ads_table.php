<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ads', function (Blueprint $table) {
            $table->id();
            $table->string('position');
            $table->text('certificate')->nullable();
            $table->text('skill')->nullable();
            $table->text('experience')->nullable();
            $table->enum('gender', ['Male', 'Female', 'Any'])->default('Any');
            $table->string('link')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ads');
    }
};
