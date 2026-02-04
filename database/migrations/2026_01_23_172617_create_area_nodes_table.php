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
        Schema::create('area_nodes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('area_id'); // parent Area
            $table->unsignedBigInteger('parent_id')->nullable(); // self reference
            $table->enum('type',['block','sector','road']);
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('area_nodes');
    }
};
