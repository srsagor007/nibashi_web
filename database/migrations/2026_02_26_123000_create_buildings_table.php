<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buildings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address_line')->nullable();
            $table->string('building_no')->nullable();
            $table->string('road_no')->nullable();
            $table->string('block_no')->nullable();
            $table->string('avenue')->nullable();
            $table->unsignedInteger('division_id')->nullable()->index();
            $table->unsignedInteger('district_id')->nullable()->index();
            $table->unsignedInteger('thana_id')->nullable()->index();
            $table->unsignedInteger('area_id')->nullable()->index();
            $table->unsignedInteger('created_by')->nullable()->index();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buildings');
    }
};
