<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('events')) {
            Schema::create('events', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('title', 191);
                $table->boolean('private')->default(0);
                $table->unsignedTinyInteger('remind_hours')->nullable();
                $table->text('description')->nullable();
                $table->json('countdown')->nullable();
                $table->dateTime('date');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');
                $table->foreign('user_id', 'fk_events_user_id')->references('id')->on('users')->onDelete('set null');
                $table->index(['user_id', 'private', 'date'], 'idx_user_private_date');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
