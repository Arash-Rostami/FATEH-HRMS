<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->string('personnel_id')->nullable()->unique();
            $table->enum('gender', ['female', 'male']);
            $table->enum('employment_type', ['fulltime', 'parttime', 'contract']);
            $table->enum('marital_status', ['married', 'single']);
            $table->unsignedInteger('number_of_children')->nullable();
            $table->enum('employment_status', ['probational', 'working', 'terminated']);
            $table->string('id_card_number')->nullable()->unique();
            $table->string('id_booklet_number')->nullable()->unique();
            $table->enum('degree', ['undergraduate', 'graduate', 'postgraduate']);
            $table->string('field')->nullable();
            $table->date('birthdate')->nullable();
            $table->string('landline')->nullable();
            $table->string('cellphone')->nullable();
            $table->string('license_plate')->nullable();
            $table->string('zip_code')->nullable();
            $table->text('address')->nullable();
            $table->text('accessibility')->nullable();
            $table->string('department_id', 10)->default('HR');
            $table->string('position');
            $table->string('insurance')->nullable();
            $table->string('emergency_phone')->nullable();
            $table->string('emergency_relationship')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('work_experience')->nullable();
            $table->text('interests')->nullable();
            $table->string('image')->nullable();
            $table->json('attachments')->nullable();
            $table->json('favorite_colors')->nullable();
            $table->json('about_me')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->index('department_id');
            $table->index('employment_status');
            $table->index('employment_type');
            $table->index('position');
            $table->index('start_date');

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
