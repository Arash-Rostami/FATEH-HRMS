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
        Schema::table('users', function (Blueprint $table) {
            $table->mediumInteger('maximum')->default(12)->after('password');
            $table->string('type')->default('employee')->after('maximum');
            $table->string('role')->default('user')->after('type');
            $table->string('status')->default('active')->after('role');
            $table->string('presence')->default('on-leave')->after('statusSwitcher');
            $table->string('booking')->default('all')->after('presence');
            $table->timestamp('last_seen')->nullable()->after('booking');
            $table->json('extra')->nullable()->after('last_seen');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'maximum',
                'type',
                'role',
                'statusSwitcher',
                'presence',
                'booking',
                'last_seen',
                'extra'
            ]);
        });
    }
};
