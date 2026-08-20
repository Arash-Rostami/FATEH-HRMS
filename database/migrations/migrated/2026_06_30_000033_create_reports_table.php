<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('reports')) {
            Schema::create('reports', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id');
                $table->string('title', 191);
                $table->text('description');
                $table->string('cover_image', 255)->nullable();
                $table->string('department_id', 10)->nullable();
                $table->json('departments')->nullable();
                $table->string('file_path', 191);
                $table->tinyInteger('active')->default(1);
                $table->boolean('pinned')->default(false);
                $table->date('report_date')->nullable();
                $table->date('expires_at')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');
                $table->index(['user_id', 'active'], 'idx_user_active');
                $table->index(['department_id', 'active'], 'idx_department_active');
                $table->foreign('user_id', 'reports_user_id_foreign')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
