<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('channels')) {
            Schema::create('channels', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('slug', 120);
                $table->string('description', 500)->nullable();
                $table->string('type', 20)->default('open');
                $table->unsignedBigInteger('owner_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');
                $table->foreign('owner_id', 'channels_owner_id_foreign')->references('id')->on('users')->nullOnDelete();
                $table->index('slug', 'channels_slug_index');
                $table->index('owner_id', 'channels_owner_id_index');
                $table->index('deleted_at', 'channels_deleted_at_index');
            });
            DB::statement('ALTER TABLE channels ENGINE=InnoDB ROW_FORMAT=DYNAMIC');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('channels');
    }
};