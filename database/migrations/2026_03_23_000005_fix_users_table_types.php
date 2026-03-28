<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Handle SQLite syntax where direct modify of column type can fail.
        // This makes sure maximum is int and booking is JSON
        Schema::table('users', function (Blueprint $table) {
            $table->integer('maximum')->default(12)->change();
            $table->json('booking')->default('{"car": false, "seat": true, "spot": true, "meeting": true, "all": false}')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('maximum')->change();
            $table->string('booking')->change();
        });
    }
};
