<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('release_requests')) {
            Schema::create('release_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('type', 50)->default('recommendation');
                $table->string('title', 191);
                $table->text('body');
                $table->json('attachments')->nullable();
                $table->string('status', 30)->default('open');
                $table->text('response')->nullable();
                $table->timestamps();
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');
                $table->foreign('user_id', 'release_requests_user_id_foreign')->references('id')->on('users')->nullOnDelete();
                $table->index('user_id', 'idx_release_requests_user_id');
                $table->index('status', 'idx_release_requests_status');
                $table->index(['type', 'status'], 'idx_release_requests_type_status');
                $table->index('created_at', 'idx_release_requests_created_at');
            });
            DB::statement('ALTER TABLE release_requests ENGINE=InnoDB ROW_FORMAT=DYNAMIC');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('release_requests');
    }
};
