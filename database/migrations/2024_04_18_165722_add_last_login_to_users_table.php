<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('dob');
            $table->boolean('is_superuser')->default(false)->after('is_active');
            $table->boolean('is_password_changed')->default(false)->after('is_superuser');
            $table->boolean('can_access_admin_panel')->default(false)->after('is_superuser');
            $table->timestamp('last_login')->nullable()->after('remember_token');
            $table->unsignedInteger('primary_role_id');
            
            
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['last_login']);
        });
    }
};
