<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id');
                $table->boolean('is_super_admin')->default(0);
                $table->json('abilities')->nullable();
                $table->json('excluded_modules')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');
                $table->index('user_id', 'permissions_user_id_foreign');
                $table->index('is_super_admin', 'idx_is_super_admin');
                $table->foreign('user_id', 'permissions_user_id_foreign')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};