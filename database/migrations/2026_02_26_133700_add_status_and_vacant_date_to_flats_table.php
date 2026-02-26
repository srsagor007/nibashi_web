<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flats', function (Blueprint $table) {
            $table->enum('status', ['vacant', 'rent'])->default('vacant')->after('preferable');
            $table->date('vacant_date')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('flats', function (Blueprint $table) {
            $table->dropColumn(['status', 'vacant_date']);
        });
    }
};
