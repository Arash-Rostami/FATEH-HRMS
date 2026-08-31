<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('ticket_id')->constrained()->nullOnDelete();
            $table->json('labels')->nullable()->after('project_id');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->nullable()->after('labels');
            $table->string('rank', 40)->nullable()->after('priority');
            $table->index(['project_id', 'status', 'deleted_at', 'rank'], 'idx_project_status_rank');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('idx_project_status_rank');
            $table->dropConstrainedForeignId('project_id');
            $table->dropColumn(['labels', 'priority', 'rank']);
        });
    }
};
