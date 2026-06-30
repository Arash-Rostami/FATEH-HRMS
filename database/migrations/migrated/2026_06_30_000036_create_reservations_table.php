<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('reservations')) {
            Schema::create('reservations', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('resource_id');
                $table->timestamp('start_time')->nullable();
                $table->timestamp('end_time')->nullable();
                $table->boolean('is_full_day')->default(0);
                $table->string('status', 255)->default('active');
                $table->unsignedBigInteger('cancelled_by_id')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->text('cancel_reason')->nullable();
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');
                $table->index('start_time', 'idx_reservations_start_time');
                $table->index('end_time', 'idx_reservations_end_time');
                $table->index('status', 'idx_reservations_status');
                $table->index('parent_id', 'idx_reservations_parent_id');
                $table->index(['status', 'start_time'], 'idx_reservations_status_start_time');
                $table->index(['status', 'end_time'], 'idx_reservations_status_end_time');
                $table->index(['user_id', 'status'], 'idx_reservations_user_status');
                $table->index(['resource_id', 'start_time'], 'idx_reservations_resource_start_time');
                $table->index(['resource_id', 'end_time'], 'idx_reservations_resource_end_time');
                $table->index(['resource_id', 'start_time', 'end_time', 'status'], 'idx_reservations_resource_time_status');
                $table->foreign('cancelled_by_id', 'reservations_cancelled_by_id_foreign')->references('id')->on('users')->onDelete('set null');
                $table->foreign('parent_id', 'reservations_parent_id_foreign')->references('id')->on('reservations')->onDelete('cascade');
                $table->foreign('resource_id', 'reservations_resource_id_foreign')->references('id')->on('resources')->onDelete('cascade');
                $table->foreign('user_id', 'reservations_user_id_foreign')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};