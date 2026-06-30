<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('personal_access_tokens')) {
            Schema::create('personal_access_tokens', function (Blueprint $table) {
                $table->unsignedBigInteger('id');
                $table->string('tokenable_type', 191);
                $table->unsignedBigInteger('tokenable_id');
                $table->string('name', 191);
                $table->string('token', 64);
                $table->text('abilities')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};