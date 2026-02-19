<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('photos', function (Blueprint $table) {
            $table->id();

            $table->json('path');
            $table->string('title', 255)->nullable();
            $table->string('department', 100)->nullable();
            $table->text('description')->nullable();
            $table->date('event_date')->nullable();

            $table->timestamps();

            $table->index('department', 'idx_department');
            $table->index('event_date', 'idx_event_date');
            $table->index(['department', 'event_date'], 'idx_dept_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('photos');
    }
};
