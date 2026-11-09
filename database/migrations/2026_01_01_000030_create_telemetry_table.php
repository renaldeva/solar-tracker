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
        Schema::create('telemetry', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            $table->timestampTz('recorded_at'); // From RTC DS3231 in UTC
            $table->timestampTz('received_at'); // Server reception timestamp
            $table->string('firmware')->nullable();
            $table->integer('rssi')->nullable();
            $table->unsignedBigInteger('uptime_s')->nullable();
            $table->string('mode')->default('auto'); // auto, manual, stow, fault

            // PZEM-017 DC Solar Measurement Point
            $table->decimal('voltage', 8, 2)->nullable(); // V
            $table->decimal('current', 8, 2)->nullable(); // A
            $table->decimal('power', 8, 2)->nullable();   // W
            $table->decimal('energy_wh', 12, 2)->nullable(); // Wh

            // Wind Speed Sensor
            $table->decimal('wind_speed', 6, 2)->nullable(); // m/s

            // BNO055 IMU Orientation & Calibration
            $table->decimal('pitch_elev', 6, 2)->nullable(); // Pitch / Elevation (deg)
            $table->decimal('azimuth', 6, 2)->nullable();    // Heading / Azimuth (deg)
            $table->decimal('roll', 6, 2)->nullable();       // Roll (deg)
            $table->unsignedTinyInteger('cal_sys')->nullable();   // 0-3
            $table->unsignedTinyInteger('cal_gyro')->nullable();  // 0-3
            $table->unsignedTinyInteger('cal_accel')->nullable(); // 0-3
            $table->unsignedTinyInteger('cal_mag')->nullable();   // 0-3

            // Target Tracking Angles
            $table->decimal('target_elev', 6, 2)->nullable(); // Target Elevation (deg)
            $table->decimal('target_az', 6, 2)->nullable();   // Target Azimuth (deg)

            // BTS7960 Linear Actuator Feedback
            $table->string('elev_state')->nullable(); // idle, moving_up, moving_down, error
            $table->string('az_state')->nullable();   // idle, moving_east, moving_west, error
            $table->decimal('elev_current', 6, 2)->nullable(); // A
            $table->decimal('az_current', 6, 2)->nullable();   // A

            // GPS M8N
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('altitude', 8, 2)->nullable();
            $table->unsignedTinyInteger('satellites')->nullable();
            $table->boolean('fix')->default(false);
            $table->decimal('hdop', 4, 2)->nullable();

            // DS3231 RTC
            $table->decimal('temp_c', 5, 2)->nullable(); // Enclosure temperature from onboard RTC sensor
            $table->integer('drift_s')->nullable();      // Clock drift in seconds vs NTP/GPS

            // System Flags & Full Raw Payload
            $table->json('flags')->nullable();
            $table->json('raw_payload')->nullable();

            // High-throughput query indexes
            $table->index(['device_id', 'recorded_at']);
            $table->index('recorded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telemetry');
    }
};
