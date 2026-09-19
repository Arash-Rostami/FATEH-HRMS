<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('projects')) {
            Schema::create('projects', function (Blueprint $table) {
                $table->id();
                $table->string('name', 255);
                $table->string('slug', 191)->unique();
                $table->unsignedBigInteger('owner_id')->nullable();
                $table->json('member_ids')->nullable();
                $table->json('departments')->nullable();
                $table->unsignedBigInteger('channel_id')->nullable();
                $table->json('settings')->nullable();
                $table->timestamp('archived_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');
                $table->foreign('owner_id', 'projects_owner_id_foreign')->references('id')->on('users')->nullOnDelete();
                $table->foreign('channel_id', 'projects_channel_id_foreign')->references('id')->on('channels')->nullOnDelete();
                $table->index('owner_id', 'projects_owner_id_index');
                $table->index('deleted_at', 'projects_deleted_at_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
