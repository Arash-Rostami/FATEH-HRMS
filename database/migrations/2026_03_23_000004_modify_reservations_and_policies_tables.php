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
        Schema::table('reservations', function (Blueprint $table) {
            $table->boolean('is_full_day')->default(false)->after('end_time');
        });

        Schema::table('reservation_policies', function (Blueprint $table) {
            $table->dropColumn(['booking_window_days', 'booking_window_hours']);
            $table->string('key')->after('resource_type');
            $table->json('value')->after('key');
            $table->dropUnique(['resource_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('is_full_day');
        });

        Schema::table('reservation_policies', function (Blueprint $table) {
            $table->integer('booking_window_days')->default(1);
            $table->integer('booking_window_hours')->default(6);
            $table->dropColumn(['key', 'value']);
            $table->unique('resource_type');
        });
    }
};
