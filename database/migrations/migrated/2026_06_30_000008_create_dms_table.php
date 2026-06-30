<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('dms')) {
            Schema::create('dms', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('file', 191);
                $table->string('code', 191);
                $table->string('version', 191);
                $table->string('title', 191);
                $table->enum('status', ['live', 'under_review', 'obsolete']);
                $table->boolean('type')->default(1);
                $table->longText('owners')->charset('utf8mb4')->collation('utf8mb4_bin')->nullable();
                $table->longText('users')->charset('utf8mb4')->collation('utf8mb4_bin')->nullable();
                $table->text('revision')->nullable();
                $table->integer('combined_read_count')->default(0);
                $table->longText('extra')->charset('utf8mb4')->collation('utf8mb4_bin')->nullable();
                $table->json('tags')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');
                $table->index('id', 'dms_id_index');
                $table->index('status', 'dms_status_index');
            });

            DB::statement('ALTER TABLE `dms` ADD INDEX `dms_owners_index` (`owners`(767))');
            DB::statement('ALTER TABLE `dms` ADD INDEX `dms_users_index` (`users`(767))');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dms');
    }
};