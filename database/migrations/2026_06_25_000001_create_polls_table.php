<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('polls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('feed_id')->constrained()->onDelete('cascade');
            $table->unsignedSmallInteger('option_index');
            $table->timestamps();

            $table->unique(['user_id', 'feed_id', 'option_index']);
            $table->index('feed_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('polls');
    }
};