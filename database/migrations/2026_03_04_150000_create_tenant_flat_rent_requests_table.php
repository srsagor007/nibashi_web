<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_flat_rent_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('tenant_id');
            $table->unsignedBigInteger('flat_id');
            $table->unsignedBigInteger('building_id');
            $table->date('request_date');
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->timestamps();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('flat_id')
                ->references('id')
                ->on('flats')
                ->onDelete('cascade');

            $table->foreign('building_id')
                ->references('id')
                ->on('buildings')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_flat_rent_requests');
    }
};

