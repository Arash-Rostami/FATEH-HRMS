<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('authorities')) {
            Schema::create('authorities', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('department_id')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->boolean('sub_duty');
                $table->longText('details')->charset('utf8mb4')->collation('utf8mb4_bin');
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');
                $table->index('sub_duty', 'authorities_sub_duty_index');
                $table->index(['user_id', 'department_id'], 'authorities_user_department_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('authorities');
    }
};
