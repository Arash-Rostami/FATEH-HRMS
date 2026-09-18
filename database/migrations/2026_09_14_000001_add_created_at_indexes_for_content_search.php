<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private array $tables = ['tickets', 'dms', 'channel_messages', 'messages', 'reservations'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && ! Schema::hasIndex($table, "idx_{$table}_created_at")) {
                Schema::table($table, function (Blueprint $blueprint) use ($table) {
                    $blueprint->index('created_at', "idx_{$table}_created_at");
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && Schema::hasIndex($table, "idx_{$table}_created_at")) {
                Schema::table($table, function (Blueprint $blueprint) use ($table) {
                    $blueprint->dropIndex("idx_{$table}_created_at");
                });
            }
        }
    }
};