<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('dms', function (Blueprint $table) {
            $table->id();
            $table->string('file');
            $table->string('code');
            $table->string('version');
            $table->string('title');
            $table->enum('status', ['live', 'under_review', 'obsolete']);
            $table->json('owners')->nullable();
            $table->boolean('type')->nullable()->default(false);
            $table->json('users')->nullable();
            $table->text('revision')->nullable();
            $table->integer('combined_read_count')->default(0);
            $table->json('extra')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('owners');
            $table->index('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dms');
    }
};
