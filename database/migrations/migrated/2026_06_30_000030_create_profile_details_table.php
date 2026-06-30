<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('profile_details')) {
            Schema::create('profile_details', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('profile_id');
                $table->string('section', 64);
                $table->string('key', 191);
                $table->text('value')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');
                $table->unique(['profile_id', 'key'], 'profile_details_profile_id_key_unique');
                $table->index('section', 'profile_details_section_index');
                $table->index('key', 'profile_details_key_index');
                $table->foreign('profile_id', 'profile_details_profile_id_foreign')->references('id')->on('profiles')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_details');
    }
};