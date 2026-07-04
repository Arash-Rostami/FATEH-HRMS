<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('links')) {
            Schema::create('links', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('url', 191);
                $table->string('url_title', 255)->nullable();
                $table->string('url_description', 191)->nullable();
                $table->string('internal_url', 191)->nullable();
                $table->string('image', 255)->nullable();
                $table->string('image_description', 191)->nullable();
                $table->string('icon', 100)->nullable();
                $table->string('icon_description', 191)->nullable();
                $table->enum('link', ['internal', 'external']);
                $table->unsignedInteger('sequence')->default(0);
                $table->json('extra')->nullable();
                $table->timestamps();
                $table->index('internal_url', 'idx_internal_url');
                $table->index(['link', 'sequence'], 'idx_link_sequence');
            });

            DB::statement('ALTER TABLE `links` ADD INDEX `idx_url` (`url`(100))');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('links');
    }
};
