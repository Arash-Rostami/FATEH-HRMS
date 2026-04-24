<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCredentialsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('credentials', function (Blueprint $table) {

                $table->id();
                $table->foreignId('user_id')
                    ->constrained()
                    ->onDelete('cascade');
                $table->string('app_name');
                $table->string('username');
                $table->text('password');
                $table->string('link')->nullable();
                $table->text('note')->nullable();
                $table->timestamps();

                // Indexes
                $table->index('user_id');
                $table->index(['user_id', 'app_name']);
                $table->index(['user_id', 'username']);
            });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('credentials');
    }
}
