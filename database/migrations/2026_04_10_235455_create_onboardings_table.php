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
        Schema::create('onboardings', function (Blueprint $table) {
            $table->id();
            $table->longText('welcome')->nullable();
            $table->json('videos')->nullable();
            $table->longText('mission')->nullable();
            $table->longText('vision')->nullable();
            $table->json('guides')->nullable();
            $table->longText('schedule')->nullable();
            $table->json('extras')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('user_id')->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('onboardings');
    }
};
