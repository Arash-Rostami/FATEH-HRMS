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
        Schema::create('tasks', function (Blueprint ) {
            ->id();
            ->string('title');
            ->text('description')->nullable();
            ->string('status')->default('todo');
            ->timestamp('deadline')->nullable();
            ->foreignId('user_id')->constrained('users')->onDelete('cascade');
            ->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            ->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
