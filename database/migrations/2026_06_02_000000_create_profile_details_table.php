<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('profile_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->string('section', 64)->index();
            $table->string('key', 191)->index();
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['profile_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_details');
    }
};
