<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resource_id')->constrained('resources')->cascadeOnDelete();
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->boolean('is_full_day')->default(false);
            $table->string('status')->default('active');
            $table->foreignId('cancelled_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('reservations')->cascadeOnDelete();
            $table->timestamps();

            $table->index('start_time');
            $table->index('end_time');
            $table->index('status');
            $table->index('parent_id');

            $table->index(['status', 'start_time']);
            $table->index(['status', 'end_time']);
            $table->index(['user_id', 'status']);
            $table->index(['resource_id', 'start_time']);
            $table->index(['resource_id', 'end_time']);

            $table->index([
                'resource_id',
                'start_time',
                'end_time',
                'status',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
