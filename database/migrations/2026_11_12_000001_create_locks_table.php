<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('locks', function (Blueprint $table) {
            $table->id();
            $table->string('lockable_type', 64);
            $table->unsignedBigInteger('lockable_id')->default(0);
            $table->string('slot', 16);
            $table->string('secret_hash');
            $table->json('extra')->nullable();
            $table->timestamps();

            $table->unique(['lockable_type', 'lockable_id', 'slot'], 'uq_locks_target_slot');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locks');
    }
};
