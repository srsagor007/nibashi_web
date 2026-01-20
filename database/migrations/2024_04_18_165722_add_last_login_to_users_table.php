<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('gender')->nullable()->after('email')->comment('1 - male, 2 - female, 3 - other');
            $table->date('dob')->nullable()->after('gender');
            $table->boolean('is_active')->default(true)->after('dob');
            $table->string('phone', 60)->nullable()->after('is_active');
            $table->text('photo')->nullable()->after('phone');
            $table->text('address')->nullable()->after('photo');
            $table->boolean('is_superuser')->default(false)->after('is_active');
            $table->boolean('can_access_admin_panel')->default(false)->after('is_superuser');
            $table->timestamp('last_login')->nullable()->after('remember_token');
            $table->text('last_update_state')->nullable()->after('user_meta')->comment('Data difference for last update');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['last_login']);
        });
    }
};
