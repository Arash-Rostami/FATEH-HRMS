<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('comments')) {
            Schema::create('comments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('feed_id')->constrained('feeds')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->text('content');
                $table->foreignId('parent_id')->nullable()->constrained('comments')->cascadeOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
