<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('notify_rules')) {
            Schema::create('notify_rules', function (Blueprint $table) {
                $table->id();
                $table->string('name', 191);
                $table->string('model', 191);
                $table->json('events');
                $table->json('channels');
                $table->json('user_ids')->nullable();
                $table->json('extra')->nullable();
                $table->string('subject', 191);
                $table->json('lines');
                $table->json('conditions')->nullable();
                $table->boolean('is_active')->default(true);
                $table->dateTime('active_from')->nullable();
                $table->dateTime('active_until')->nullable();
                $table->timestamps();
                $table->index(['model', 'is_active']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notify_rules');
    }
};
