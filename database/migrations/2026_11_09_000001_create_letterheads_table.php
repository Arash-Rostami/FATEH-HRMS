<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('letterheads', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 8)->default('head');
            $table->string('name', 120);
            $table->string('size', 4)->default('a4');
            $table->string('lang', 2)->default('fa');
            $table->string('header_image')->nullable();
            $table->string('footer_image')->nullable();
            $table->string('stamp_image')->nullable();
            $table->string('watermark', 120)->nullable();
            $table->json('margins')->nullable();
            $table->json('sender_block')->nullable();
            $table->boolean('page_numbers')->default(false);
            $table->json('style')->nullable();
            $table->json('fields_box')->nullable();
            $table->json('config')->nullable();
            $table->unsignedTinyInteger('sign_layout')->default(2);
            $table->boolean('active')->default(true);
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('pattern', 120)->nullable();
            $table->unsignedInteger('start_at')->default(1);
            $table->json('counters')->nullable();
            $table->timestamps();
            $table->index('parent_id');
            $table->index(['kind', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('letterheads');
    }
};
