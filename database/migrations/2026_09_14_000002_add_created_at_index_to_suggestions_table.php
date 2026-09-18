<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('suggestions') && ! Schema::hasIndex('suggestions', 'idx_suggestions_created_at')) {
            Schema::table('suggestions', function (Blueprint $table) {
                $table->index('created_at', 'idx_suggestions_created_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('suggestions') && Schema::hasIndex('suggestions', 'idx_suggestions_created_at')) {
            Schema::table('suggestions', function (Blueprint $table) {
                $table->dropIndex('idx_suggestions_created_at');
            });
        }
    }
};