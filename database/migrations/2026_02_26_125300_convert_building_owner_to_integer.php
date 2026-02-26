<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('UPDATE buildings SET building_owner = created_by WHERE building_owner IS NULL OR building_owner = "" OR building_owner REGEXP "[^0-9]"');
        DB::statement('ALTER TABLE buildings MODIFY building_owner INT UNSIGNED NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE buildings MODIFY building_owner VARCHAR(255) NULL');
    }
};
