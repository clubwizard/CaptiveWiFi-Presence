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
        Schema::create('client_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('mac_address_hash', 64)->index(); // SHA256 hash of MAC address
            $table->foreignId('access_point_id')->constrained('access_points')->onDelete('cascade');
            $table->timestamp('connected_at')->index();
            $table->timestamp('disconnected_at')->nullable()->index();
            $table->integer('session_duration_seconds')->nullable();
            $table->integer('signal_strength')->nullable();
            $table->boolean('is_returning_visitor')->default(false)->index();
            $table->timestamp('first_seen_at')->index(); // When this MAC was first ever seen
            $table->timestamps();

            // Index for efficient queries
            $table->index(['mac_address_hash', 'connected_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_sessions');
    }
};
