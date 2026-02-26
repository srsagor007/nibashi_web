<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE temp_users MODIFY primary_role_id INT UNSIGNED NULL');
    }

    public function down(): void
    {
        DB::statement('UPDATE temp_users SET primary_role_id = 1 WHERE primary_role_id IS NULL');
        DB::statement('ALTER TABLE temp_users MODIFY primary_role_id INT UNSIGNED NOT NULL');
    }
};
