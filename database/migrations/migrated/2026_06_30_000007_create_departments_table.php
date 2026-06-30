<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('departments')) {
            Schema::create('departments', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('code', 255)->charset('utf8mb4')->collation('utf8mb4_unicode_ci');
                $table->string('name', 255)->collation('utf8mb4_persian_ci');
                $table->string('description', 255)->collation('utf8mb4_persian_ci');
                $table->json('units')->nullable();
                $table->json('sections')->nullable();
                $table->json('ticket_options')->nullable();
                $table->timestamp('created_at')->useCurrent()->nullable(false);
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate()->nullable(false);
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_persian_ci');
                $table->unique('code', 'code');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};