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
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            $table->string('severity'); // critical, warning, info
            $table->string('code');     // e.g. HIGH_WIND_STOW, IMU_UNHEALTHY, ACTUATOR_OVERCURRENT, RTC_DRIFT, COMM_LOSS, GPS_NO_FIX
            $table->text('message');
            $table->string('status')->default('open'); // open, acknowledged, resolved
            $table->json('metadata')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('acknowledged_at')->nullable();
            $table->timestampTz('opened_at');
            $table->timestampTz('resolved_at')->nullable();
            $table->timestamps();

            // Indexes for fast lookup and rule enforcement
            $table->index(['device_id', 'status']);
            $table->index(['device_id', 'code', 'status']);
            $table->index('opened_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
