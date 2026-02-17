<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('feeds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('category')->nullable();
            $table->text('content');
            $table->json('media_paths')->nullable();
            $table->json('poll_options')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('category');
            $table->index('created_at');
        });

        Schema::create('reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('feed_id')->constrained()->onDelete('cascade');
            $table->string('emoji', 50);
            $table->timestamps();

            $table->unique(['user_id', 'feed_id', 'emoji']);
            $table->index('feed_id');
            $table->index(['user_id', 'feed_id']);
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('feed_id')->constrained()->onDelete('cascade');
            $table->text('content');
            $table->foreignId('parent_id')->nullable()->constrained('comments')->cascadeOnDelete();
            $table->timestamps();

            $table->index('feed_id');
            $table->index(['user_id', 'feed_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
        Schema::dropIfExists('reactions');
        Schema::dropIfExists('feeds');
    }
};
