<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('feeds')) {
            Schema::create('feeds', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id');
                $table->string('category', 191);
                $table->text('content')->nullable();
                $table->longText('media_paths')->charset('utf8mb4')->collation('utf8mb4_bin')->nullable();
                $table->longText('poll_options')->charset('utf8mb4')->collation('utf8mb4_bin')->nullable();
                $table->timestamps();
                $table->index('id', 'feeds_id_index');
                $table->index('user_id', 'feeds_user_id_index');
                $table->index('category', 'feeds_category_index');
                $table->index('created_at', 'feeds_created_at_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('feeds');
    }
};