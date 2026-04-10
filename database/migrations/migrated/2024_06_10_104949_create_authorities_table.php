<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAuthoritiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('authorities', function (Blueprint $table) {

            $table->id();
            $table->string('department_id')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->boolean('sub_duty')->nullable();
            $table->json('details')->nullable();
            $table->foreignId('user_id')->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('department_id');
            $table->index('user_id');
            $table->index('sub_duty');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('authorities');
    }
}
