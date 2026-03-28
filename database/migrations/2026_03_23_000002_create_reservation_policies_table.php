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
        Schema::create('reservation_policies', function (Blueprint $table) {
            $table->id();
            $table->string('resource_type')->unique(); // seat, spot, car, appointment
            $table->integer('booking_window_days')->default(1);
            $table->integer('booking_window_hours')->default(6);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservation_policies');
    }
};
