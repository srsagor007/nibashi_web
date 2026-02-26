<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('building_id');
            $table->string('flat_number');
            $table->unsignedSmallInteger('floor_no');
            $table->unsignedTinyInteger('bed_room')->default(0);
            $table->unsignedTinyInteger('bathroom')->default(0);
            $table->unsignedTinyInteger('balcony')->default(0);
            $table->unsignedTinyInteger('kitchen')->default(0);
            $table->unsignedTinyInteger('dining')->default(0);
            $table->unsignedTinyInteger('drawing')->default(0);
            $table->decimal('house_rent', 12, 2);
            $table->decimal('service_charge', 12, 2)->default(0);
            $table->boolean('is_furnished')->default(false);
            $table->enum('preferable', ['family', 'bachelor', 'office']);
            $table->decimal('total_flat_size', 10, 2)->nullable();
            $table->string('image')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('building_id')
                ->references('id')
                ->on('buildings')
                ->onDelete('cascade');

            $table->unique(['building_id', 'flat_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flats');
    }
};
