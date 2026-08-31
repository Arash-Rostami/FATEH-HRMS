<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('replies', function (Blueprint $table) {
            $table->index(['user_id', 'type', 'created_at'], 'idx_replies_user_type_created');
        });
    }

    public function down(): void
    {
        Schema::table('replies', function (Blueprint $table) {
            $table->dropIndex('idx_replies_user_type_created');
        });
    }
};
