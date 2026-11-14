<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('correspondences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('direction', 3)->default('out');
            $table->string('type', 32);
            $table->string('mode', 8)->default('simple');
            $table->string('status', 16)->default('draft');
            $table->string('classification', 16)->default('normal');
            $table->string('urgency', 16)->default('normal');
            $table->string('sign_mode', 12)->default('electronic');
            $table->string('route_mode', 12)->default('sequential');
            $table->string('sender_category', 16)->nullable();
            $table->unsignedTinyInteger('step_no')->nullable();
            $table->unsignedTinyInteger('steps_total')->nullable();
            $table->boolean('is_template')->default(false);
            $table->unsignedBigInteger('letterhead_id')->nullable();
            $table->unsignedBigInteger('in_reply_to_id')->nullable();
            $table->unsignedBigInteger('supersedes_id')->nullable();
            $table->string('related_type', 32)->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->string('subject', 200)->nullable();
            $table->string('number', 64)->nullable();
            $table->unsignedInteger('number_seq')->nullable();
            $table->string('letter_date', 10)->nullable();
            $table->json('spec')->nullable();
            $table->json('versions')->nullable();
            $table->json('recipients')->nullable();
            $table->json('parties')->nullable();
            $table->json('events')->nullable();
            $table->unsignedBigInteger('awaiting_user_id')->nullable();
            $table->dateTime('deadline_at')->nullable();
            $table->dateTime('shared_at')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->string('content_hash', 64)->nullable();
            $table->text('search_text')->nullable();
            $table->json('tags')->nullable();
            $table->json('meta')->nullable();
            $table->string('pdf_path')->nullable();
            $table->string('pdf_hash', 64)->nullable();
            $table->string('fail_reason', 32)->nullable();
            $table->string('model', 64)->nullable();
            $table->unsignedInteger('tokens_total')->default(0);
            $table->string('their_number', 64)->nullable();
            $table->string('their_date', 10)->nullable();
            $table->dateTime('received_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'is_template', 'updated_at']);
            $table->index(['awaiting_user_id', 'status']);
            $table->index('status');
            $table->unique('number');
            $table->index('letterhead_id');
            $table->index('in_reply_to_id');
            $table->index('supersedes_id');
            $table->index(['related_type', 'related_id']);
            $table->index(['direction', 'their_number']);
            $table->index('approved_at');
            $table->index('deleted_at');
            $table->index('created_at', 'idx_correspondences_created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('correspondences');
    }
};
