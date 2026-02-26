<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->unsignedInteger('building_owner')->nullable()->after('address_line');
            $table->unsignedBigInteger('sector_node_id')->nullable()->after('area_id')->index();
            $table->unsignedBigInteger('block_node_id')->nullable()->after('sector_node_id')->index();
            $table->unsignedBigInteger('road_node_id')->nullable()->after('block_node_id')->index();
            $table->decimal('latitude', 10, 8)->nullable()->after('road_node_id');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');

            $table->boolean('has_gas')->default(false)->after('longitude');
            $table->boolean('has_generator')->default(false)->after('has_gas');
            $table->boolean('has_lift')->default(false)->after('has_generator');
            $table->boolean('has_cctv')->default(false)->after('has_lift');
            $table->boolean('has_security_guard')->default(false)->after('has_cctv');
            $table->boolean('has_parking')->default(false)->after('has_security_guard');
        });

        Schema::table('buildings', function (Blueprint $table) {
            $table->dropColumn(['road_no', 'block_no', 'avenue']);
        });
    }

    public function down(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->string('road_no')->nullable()->after('building_no');
            $table->string('block_no')->nullable()->after('road_no');
            $table->string('avenue')->nullable()->after('block_no');
        });

        Schema::table('buildings', function (Blueprint $table) {
            $table->dropColumn([
                'building_owner',
                'sector_node_id',
                'block_node_id',
                'road_node_id',
                'latitude',
                'longitude',
                'has_gas',
                'has_generator',
                'has_lift',
                'has_cctv',
                'has_security_guard',
                'has_parking',
            ]);
        });
    }
};
