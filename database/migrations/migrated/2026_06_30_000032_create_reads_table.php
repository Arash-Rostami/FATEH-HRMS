<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('reads')) {
            Schema::create('reads', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('document_id');
                $table->boolean('read')->default(0);
                $table->integer('read_count')->default(0);
                $table->integer('combined_read_count')->default(0);
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');
                $table->index(['user_id', 'read', 'document_id', 'read_count'], 'idx_reads_user_read_doc');
                $table->index(['document_id', 'updated_at'], 'idx_reads_document_updated');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reads');
    }
};