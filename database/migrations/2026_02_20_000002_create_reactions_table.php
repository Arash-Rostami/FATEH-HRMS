<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('reactions')) {
            Schema::create('reactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('feed_id')->constrained('feeds')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('emoji');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reactions');
    }
};
