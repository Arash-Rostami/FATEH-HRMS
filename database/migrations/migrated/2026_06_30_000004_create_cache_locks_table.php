<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('cache_locks')) {
            Schema::create('cache_locks', function (Blueprint $table) {
                $table->string('key');
                $table->string('owner');
                $table->integer('expiration');
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');
                $table->primary('key');
                $table->index('expiration', 'cache_locks_expiration_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cache_locks');
    }
};