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
            $table->string('resource_type')->unique();
            $table->string('key');
            $table->json('value');
            $table->dropUnique(['resource_type']);

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
