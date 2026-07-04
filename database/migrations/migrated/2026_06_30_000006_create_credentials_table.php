<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('credentials')) {
            Schema::create('credentials', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id');
                $table->string('app_name', 191);
                $table->string('username', 191);
                $table->text('password');
                $table->string('link', 191)->nullable();
                $table->text('note')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');
                $table->index(['user_id', 'app_name'], 'credentials_user_id_app_name_index');
                $table->index(['user_id', 'username'], 'credentials_user_id_username_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('credentials');
    }
};
