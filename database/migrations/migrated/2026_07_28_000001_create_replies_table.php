<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('replies')) {
            Schema::create('replies', function (Blueprint $table) {
                $table->id();
                $table->string('repliable_type', 191);
                $table->unsignedBigInteger('repliable_id');
                $table->foreignId('user_id')->constrained();
                $table->text('body')->nullable();
                $table->json('files')->nullable();
                $table->timestamps();
                $table->index(['repliable_type', 'repliable_id', 'created_at'], 'idx_repliable_created');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('replies');
    }
};
