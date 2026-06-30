<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('reservation_policies')) {
            Schema::create('reservation_policies', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('resource_type', 255);
                $table->string('key', 255);
                $table->json('value')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');
                $table->unique(['resource_type', 'key'], 'reservation_policies_resource_type_key_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_policies');
    }
};