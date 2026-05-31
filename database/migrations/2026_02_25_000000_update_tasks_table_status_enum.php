<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Changing string is safer. Enums don't get natively altered smoothly by Doctrine SQLite
    }

    public function down(): void
    {
    }
};
