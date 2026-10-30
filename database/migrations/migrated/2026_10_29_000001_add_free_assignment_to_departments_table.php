<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('departments') && !Schema::hasColumn('departments', 'free_assignment')) {
            Schema::table('departments', fn(Blueprint $table) => $table->boolean('free_assignment')->default(false));
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('departments') && Schema::hasColumn('departments', 'free_assignment')) {
            Schema::table('departments', fn(Blueprint $table) => $table->dropColumn('free_assignment'));
        }
    }
};