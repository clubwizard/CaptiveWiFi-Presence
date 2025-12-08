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
        Schema::create('access_points', function (Blueprint $table) {
            $table->id();
            $table->string('ap_name');
            $table->string('mac_address')->unique();
            $table->string('location_description')->nullable();
            $table->string('floor')->nullable();
            $table->string('zone')->nullable(); // bar/restaurant/patio/etc
            $table->enum('status', ['online', 'offline', 'unknown'])->default('unknown');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('access_points');
    }
};
