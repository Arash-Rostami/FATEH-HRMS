<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('email');
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->integer('maximum')->default(12);
                $table->string('type')->default('employee');
                $table->string('role')->default('user');
                $table->string('status')->default('active');
                $table->string('presence')->default('remote');
                $table->json('booking')->nullable();
                $table->timestamp('last_seen')->nullable();
                $table->json('extra')->nullable();
                $table->text('two_factor_secret')->nullable();
                $table->text('two_factor_recovery_codes')->nullable();
                $table->timestamp('two_factor_confirmed_at')->nullable();
                $table->string('remember_token', 100)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');
                $table->unique('email', 'users_email_unique');
                $table->index('role', 'users_role_index');
                $table->index('status', 'users_status_index');
                $table->index('type', 'users_type_index');
                $table->index('last_seen', 'users_last_seen_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
