<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify the enum column using raw SQL since Doctrine DBAL doesn't support changing enum columns easily
        DB::statement("ALTER TABLE tasks MODIFY COLUMN status ENUM('todo', 'in-progress', 'pending', 'done') DEFAULT 'todo'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE tasks MODIFY COLUMN status ENUM('todo', 'in-progress', 'done') DEFAULT 'todo'");
    }
};
