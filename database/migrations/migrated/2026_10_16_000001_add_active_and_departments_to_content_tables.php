<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const TABLES = [
        'feeds' => ['departments' => true, 'index' => ['feeds_active_created_index', ['active', 'created_at']]],
        'posts' => ['departments' => true, 'index' => ['idx_posts_active_created', ['active', 'created_at']]],
        'photos' => ['departments' => false, 'index' => ['idx_photos_active_date', ['active', 'event_date']]],
    ];

    public function up(): void
    {
        foreach (self::TABLES as $name => $spec) {
            if (!Schema::hasTable($name)) {
                continue;
            }

            if (!Schema::hasColumn($name, 'active')) {
                Schema::table($name, fn(Blueprint $table) => $table->boolean('active')->default(true));
            }

            if ($spec['departments'] && !Schema::hasColumn($name, 'departments')) {
                Schema::table($name, fn(Blueprint $table) => $table->json('departments')->nullable());
            }

            [$index, $columns] = $spec['index'];

            if (!Schema::hasIndex($name, $index)) {
                Schema::table($name, fn(Blueprint $table) => $table->index($columns, $index));
            }
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $name => $spec) {
            if (!Schema::hasTable($name)) {
                continue;
            }

            [$index] = $spec['index'];

            if (Schema::hasIndex($name, $index)) {
                Schema::table($name, fn(Blueprint $table) => $table->dropIndex($index));
            }

            if (Schema::hasColumn($name, 'active')) {
                Schema::table($name, fn(Blueprint $table) => $table->dropColumn('active'));
            }

            if ($spec['departments'] && Schema::hasColumn($name, 'departments')) {
                Schema::table($name, fn(Blueprint $table) => $table->dropColumn('departments'));
            }
        }
    }
};
