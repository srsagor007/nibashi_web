<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flat_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('flat_id');
            $table->string('image_path');
            $table->unsignedTinyInteger('sort_order')->default(1);
            $table->timestamps();

            $table->foreign('flat_id')
                ->references('id')
                ->on('flats')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flat_images');
    }
};
