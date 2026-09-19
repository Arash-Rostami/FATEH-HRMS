<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('profiles')) {
            Schema::create('profiles', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('personnel_id', 255)->nullable();
                $table->enum('gender', ['female', 'male']);
                $table->enum('employment_type', ['fulltime', 'parttime', 'contract']);
                $table->enum('marital_status', ['married', 'single']);
                $table->unsignedInteger('number_of_children')->nullable();
                $table->enum('employment_status', ['probational', 'working', 'terminated'])->default('probational');
                $table->string('id_card_number', 255)->nullable();
                $table->string('id_booklet_number', 255)->nullable();
                $table->enum('degree', ['undergraduate', 'graduate', 'postgraduate', 'doctorate'])->default('undergraduate');
                $table->string('field', 255)->nullable();
                $table->date('birthdate')->nullable();
                $table->string('landline', 255)->nullable();
                $table->string('cellphone', 255)->nullable();
                $table->string('license_plate', 255)->nullable();
                $table->string('zip_code', 255)->nullable();
                $table->text('address')->nullable();
                $table->text('accessibility')->nullable();
                $table->string('department_id', 10)->default('HR');
                $table->string('position', 255)->default('employee');
                $table->string('insurance', 255)->nullable();
                $table->string('emergency_phone', 255)->nullable();
                $table->string('emergency_relationship', 255)->nullable();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->text('work_experience')->nullable();
                $table->text('interests')->nullable();
                $table->string('image', 255)->nullable();
                $table->json('attachments')->nullable();
                $table->json('favorite_colors')->nullable();
                $table->json('about_me')->nullable();
                $table->unsignedBigInteger('user_id');
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');
                $table->unique('personnel_id', 'profiles_personnel_id_unique');
                $table->unique('id_card_number', 'profiles_id_card_number_unique');
                $table->unique('id_booklet_number', 'profiles_id_booklet_number_unique');
                $table->index('user_id', 'profiles_user_id_foreign');
                $table->index('employment_status', 'profiles_employment_status_index');
                $table->index('employment_type', 'profiles_employment_type_index');
                $table->index('position', 'profiles_position_index');
                $table->index('start_date', 'profiles_start_date_index');
                $table->index(['department_id', 'employment_status'], 'profiles_department_status_index');
                $table->foreign('department_id', 'fk_profiles_department')->references('code')->on('departments')->onUpdate('cascade');
                $table->foreign('user_id', 'profiles_user_id_foreign')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
