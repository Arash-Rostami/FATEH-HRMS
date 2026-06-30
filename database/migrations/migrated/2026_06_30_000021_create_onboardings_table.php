<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('onboardings')) {
            Schema::create('onboardings', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->longText('welcome')->nullable();
                $table->json('videos')->nullable();
                $table->longText('mission')->nullable();
                $table->longText('vision')->nullable();
                $table->json('guides')->nullable();
                $table->longText('schedule')->nullable();
                $table->json('extras')->nullable();
                $table->boolean('is_active')->default(1);
                $table->unsignedBigInteger('user_id');
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');
                $table->index('user_id', 'onboardings_user_id_foreign');
                $table->foreign('user_id', 'onboardings_user_id_foreign')->references('id')->on('users')->onUpdate('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('onboardings');
    }
};