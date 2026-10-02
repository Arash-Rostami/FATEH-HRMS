<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('reads')) {
            return;
        }

        $duplicates = DB::table('reads')
            ->selectRaw('document_id, user_id, COUNT(*) AS cnt')
            ->groupBy('document_id', 'user_id')
            ->havingRaw('cnt > 1')
            ->get();

        if ($duplicates->isNotEmpty()) {
            throw new RuntimeException(
                "Cannot add unique index on `reads` (document_id, user_id): {$duplicates->count()} duplicate group(s) exist. Resolve them manually before migrating."
            );
        }

        Schema::table('reads', function (Blueprint $table) {
            $table->unique(['document_id', 'user_id'], 'uniq_reads_document_user');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('reads')) {
            return;
        }

        Schema::table('reads', function (Blueprint $table) {
            $table->dropUnique('uniq_reads_document_user');
        });
    }
};