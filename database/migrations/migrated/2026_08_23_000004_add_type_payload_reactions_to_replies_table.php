<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('replies', function (Blueprint $table) {
            $table->string('type', 30)->nullable()->default('comment')->after('user_id');
            $table->json('payload')->nullable()->after('body');
            $table->json('reactions')->nullable()->after('payload');
        });

        DB::table('replies')->whereNull('type')->update(['type' => 'comment']);

        Schema::table('replies', function (Blueprint $table) {
            $table->dropIndex('idx_repliable_created');
            $table->index(['repliable_type', 'repliable_id', 'created_at', 'id'], 'idx_repliable_created_id');
        });
    }

    public function down(): void
    {
        Schema::table('replies', function (Blueprint $table) {
            $table->dropIndex('idx_repliable_created_id');
            $table->index(['repliable_type', 'repliable_id', 'created_at'], 'idx_repliable_created');
            $table->dropColumn(['type', 'payload', 'reactions']);
        });
    }
};
