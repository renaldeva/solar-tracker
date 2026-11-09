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
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('uid')->unique();
            $table->string('name');
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->string('mqtt_username')->nullable();
            $table->string('status')->default('offline'); // online, unstable, offline
            $table->string('mode')->default('auto'); // auto, manual, stow, fault
            $table->timestamp('last_seen_at')->nullable();
            $table->string('firmware')->default('1.0.0');
            $table->integer('rssi')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
