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
        Schema::create('telemetry_hourly', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            $table->timestampTz('hour_bucket'); // Truncated to the beginning of the hour (UTC)
            $table->decimal('avg_power_w', 8, 2)->nullable();
            $table->decimal('max_power_w', 8, 2)->nullable();
            $table->decimal('min_power_w', 8, 2)->nullable();
            $table->decimal('energy_delta_wh', 10, 2)->nullable(); // Energy generated during this hour
            $table->decimal('max_wind_ms', 6, 2)->nullable();
            $table->decimal('avg_wind_ms', 6, 2)->nullable();
            $table->decimal('uptime_percent', 5, 2)->nullable();
            $table->unsignedInteger('samples_count')->default(0);
            $table->timestamps();

            $table->unique(['device_id', 'hour_bucket']);
            $table->index('hour_bucket');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telemetry_hourly');
    }
};
