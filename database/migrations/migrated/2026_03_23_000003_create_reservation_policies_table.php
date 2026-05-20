<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_policies', function (Blueprint $table) {
            $table->id();
            $table->string('resource_type');
            $table->string('key');
            $table->json('value');
            $table->timestamps();

            $table->unique(['resource_type', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_policies');
    }
};
