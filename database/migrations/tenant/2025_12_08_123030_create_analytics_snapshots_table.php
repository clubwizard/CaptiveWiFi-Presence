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
        Schema::create('analytics_snapshots', function (Blueprint $table) {
            $table->id();
            $table->date('snapshot_date')->index();
            $table->integer('snapshot_hour')->nullable()->index(); // 0-23, null for daily snapshots
            $table->integer('unique_visitors')->default(0);
            $table->integer('total_sessions')->default(0);
            $table->decimal('avg_dwell_time_minutes', 8, 2)->nullable();
            $table->integer('peak_connected_count')->default(0);
            $table->decimal('returning_visitor_percentage', 5, 2)->nullable();
            $table->integer('new_visitor_count')->default(0);
            $table->timestamps();

            // Unique constraint to prevent duplicate snapshots
            $table->unique(['snapshot_date', 'snapshot_hour']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_snapshots');
    }
};
