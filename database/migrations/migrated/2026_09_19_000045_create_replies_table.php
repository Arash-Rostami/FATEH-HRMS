<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('replies')) {
            Schema::create('replies', function (Blueprint $table) {
                $table->id();
                $table->string('repliable_type', 191);
                $table->unsignedBigInteger('repliable_id');
                $table->foreignId('user_id')->nullable()->constrained();
                $table->string('type', 30)->nullable()->default('comment');
                $table->text('body')->nullable();
                $table->json('payload')->nullable();
                $table->json('reactions')->nullable();
                $table->json('files')->nullable();
                $table->timestamps();
                $table->index(['repliable_type', 'repliable_id', 'created_at', 'id'], 'idx_repliable_created_id');
                $table->index(['user_id', 'type', 'created_at'], 'idx_replies_user_type_created');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('replies');
    }
};
