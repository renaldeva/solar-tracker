<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TelemetryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uid' => $this->device_uid ?? $this->device?->uid ?? 'ST-DEMO-001',
            'mode' => $this->mode,
            'pzem' => $this->pzem_fault ? null : [
                'voltage' => $this->pzem_voltage ?? $this->voltage,
                'current' => $this->pzem_current ?? $this->current,
                'power' => $this->pzem_power ?? $this->power,
                'energy_wh' => $this->pzem_energy_wh ?? $this->energy_wh,
            ],
            'wind' => $this->wind_fault ? null : [
                'speed_ms' => $this->wind_speed_ms ?? $this->wind_speed,
                'speed_kmh' => $this->wind_speed_kmh,
                'direction_deg' => $this->wind_direction_deg,
            ],
            'flags' => [
                'pzem_fault' => (bool) $this->pzem_fault,
                'wind_fault' => (bool) $this->wind_fault,
                'imu_fault' => (bool) $this->imu_fault,
                'actuator_fault' => (bool) $this->actuator_fault,
                'wind_alarm' => (bool) $this->wind_alarm,
            ],
            'received_at' => $this->received_at?->toIso8601String(),
        ];
    }
}

