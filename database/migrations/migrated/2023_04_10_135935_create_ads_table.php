<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ads', function (Blueprint $table) {
            $table->id();
            $table->string('position')->nullable();
            $table->text('certificate')->nullable();
            $table->text('skill')->nullable();
            $table->text('experience')->nullable();
            $table->enum('gender', ['Male', 'Female', 'Any'])->default('Any');
            $table->string('link');
            $table->boolean('active')->default(true);
            $table->json('extra')->nullable();
            $table->timestamps();

            $table->index('position');
            $table->index('gender');
            $table->index('active');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ads');
    }
}
