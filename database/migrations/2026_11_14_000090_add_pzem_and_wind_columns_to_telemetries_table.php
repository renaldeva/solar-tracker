<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Rename 'telemetry' to 'telemetries' if needed
        if (Schema::hasTable('telemetry') && ! Schema::hasTable('telemetries')) {
            Schema::rename('telemetry', 'telemetries');
        }

        // 2. Add PZEM, Wind, and sensor fault columns to telemetries table
        Schema::table('telemetries', function (Blueprint $table) {
            if (! Schema::hasColumn('telemetries', 'device_uid')) {
                $table->string('device_uid')->nullable()->after('id');
            }

            if (! Schema::hasColumn('telemetries', 'pzem_voltage')) {
                $table->decimal('pzem_voltage', 8, 2)->nullable();
            }
            if (! Schema::hasColumn('telemetries', 'pzem_current')) {
                $table->decimal('pzem_current', 8, 2)->nullable();
            }
            if (! Schema::hasColumn('telemetries', 'pzem_power')) {
                $table->decimal('pzem_power', 10, 2)->nullable();
            }
            if (! Schema::hasColumn('telemetries', 'pzem_energy_wh')) {
                $table->decimal('pzem_energy_wh', 12, 2)->nullable();
            }

            if (! Schema::hasColumn('telemetries', 'wind_speed_ms')) {
                $table->decimal('wind_speed_ms', 6, 2)->nullable();
            }
            if (! Schema::hasColumn('telemetries', 'wind_speed_kmh')) {
                $table->decimal('wind_speed_kmh', 6, 2)->nullable();
            }
            if (! Schema::hasColumn('telemetries', 'wind_direction_deg')) {
                $table->decimal('wind_direction_deg', 6, 2)->nullable();
            }

            if (! Schema::hasColumn('telemetries', 'imu_pitch_elev')) {
                $table->decimal('imu_pitch_elev', 6, 2)->nullable();
            }
            if (! Schema::hasColumn('telemetries', 'imu_azimuth')) {
                $table->decimal('imu_azimuth', 6, 2)->nullable();
            }
            if (! Schema::hasColumn('telemetries', 'imu_roll')) {
                $table->decimal('imu_roll', 6, 2)->nullable();
            }

            if (! Schema::hasColumn('telemetries', 'gps_lat')) {
                $table->string('gps_lat')->nullable();
            }
            if (! Schema::hasColumn('telemetries', 'gps_lon')) {
                $table->string('gps_lon')->nullable();
            }
            if (! Schema::hasColumn('telemetries', 'gps_alt')) {
                $table->decimal('gps_alt', 8, 2)->nullable();
            }
            if (! Schema::hasColumn('telemetries', 'gps_satellites')) {
                $table->unsignedTinyInteger('gps_satellites')->nullable();
            }
            if (! Schema::hasColumn('telemetries', 'gps_fix')) {
                $table->boolean('gps_fix')->default(false);
            }
            if (! Schema::hasColumn('telemetries', 'gps_hdop')) {
                $table->decimal('gps_hdop', 4, 2)->nullable();
            }

            if (! Schema::hasColumn('telemetries', 'pzem_fault')) {
                $table->boolean('pzem_fault')->default(false);
            }
            if (! Schema::hasColumn('telemetries', 'wind_fault')) {
                $table->boolean('wind_fault')->default(false);
            }
            if (! Schema::hasColumn('telemetries', 'imu_fault')) {
                $table->boolean('imu_fault')->default(false);
            }
            if (! Schema::hasColumn('telemetries', 'actuator_fault')) {
                $table->boolean('actuator_fault')->default(false);
            }
            if (! Schema::hasColumn('telemetries', 'wind_alarm')) {
                $table->boolean('wind_alarm')->default(false);
            }

            $table->index(['device_uid', 'received_at']);
        });

        // 3. Make device_id nullable in telemetries so standalone ingestion without FK won't break
        Schema::table('telemetries', function (Blueprint $table) {
            $table->unsignedBigInteger('device_id')->nullable()->change();
        });

        // 4. Create backward-compatible VIEW 'telemetry'
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('CREATE VIEW IF NOT EXISTS telemetry AS SELECT * FROM telemetries;');
        } else {
            DB::statement('CREATE OR REPLACE VIEW telemetry AS SELECT * FROM telemetries;');
        }

        // 5. Create 'acks' table if not exists with unique cmd_id
        if (! Schema::hasTable('acks')) {
            Schema::create('acks', function (Blueprint $table) {
                $table->id();
                $table->uuid('cmd_id')->unique();
                $table->string('device_uid')->nullable();
                $table->string('status');
                $table->text('reason')->nullable();
                $table->json('payload')->nullable();
                $table->timestampTz('received_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS telemetry;');
        Schema::dropIfExists('acks');
        Schema::table('telemetries', function (Blueprint $table) {
            $table->dropIndex(['device_uid', 'received_at']);
            $table->dropColumn([
                'device_uid',
                'pzem_voltage',
                'pzem_current',
                'pzem_power',
                'pzem_energy_wh',
                'wind_speed_ms',
                'wind_speed_kmh',
                'wind_direction_deg',
                'imu_pitch_elev',
                'imu_azimuth',
                'imu_roll',
                'gps_lat',
                'gps_lon',
                'gps_alt',
                'gps_satellites',
                'gps_fix',
                'gps_hdop',
                'pzem_fault',
                'wind_fault',
                'imu_fault',
                'actuator_fault',
                'wind_alarm',
            ]);
        });
        if (Schema::hasTable('telemetries') && ! Schema::hasTable('telemetry')) {
            Schema::rename('telemetries', 'telemetry');
        }
    }
};
