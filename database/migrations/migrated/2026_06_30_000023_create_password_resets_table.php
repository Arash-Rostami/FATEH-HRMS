<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('password_resets')) {
            Schema::create('password_resets', function (Blueprint $table) {
                $table->string('email', 191);
                $table->string('token', 191);
                $table->timestamp('created_at')->nullable();
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('password_resets');
    }
};