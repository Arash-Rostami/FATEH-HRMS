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
        Schema::create('resources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // seat, spot, car, appointment
            $table->json('metadata')->nullable();
            $table->string('status')->default('active');
            $table->string('image')->nullable();
            $table->timestamps();

            $table->index('name', 'idx_resources_name');
            $table->index(['type', 'status'], 'idx_resources_type_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resources');
    }
};
