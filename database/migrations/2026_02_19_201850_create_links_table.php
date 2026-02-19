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
        Schema::create('links', function (Blueprint $table) {
            $table->id();
            $table->string('url');
            $table->string('url_title')->nullable();
            $table->text('url_description')->nullable();
            $table->string('internal_url')->nullable();
            $table->string('image')->nullable();
            $table->string('image_description')->nullable();
            $table->string('icon')->nullable();
            $table->string('icon_description')->nullable();
            $table->enum('link', ['internal', 'external'])->default('external');
            $table->integer('sequence')->default(0);
            $table->timestamps();

            // Indexes for performance optimization
            $table->index('link', 'idx_link_type');
            $table->index('internal_url', 'idx_internal_url');
            $table->index('sequence', 'idx_sequence');
            $table->index(['link', 'sequence'], 'idx_link_sequence');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('links');
    }
};
