<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    use HasFactory;

    protected $table = 'alerts';

    protected $fillable = [
        'device_id',
        'severity',
        'code',
        'message',
        'status',
        'metadata',
        'acknowledged_by',
        'acknowledged_at',
        'opened_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'acknowledged_at' => 'datetime',
            'opened_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    /**
     * Render the alert message localized at runtime per event code and parameters.
     */
    public function renderMessage(): string
    {
        $codeKey = 'alerts.' . $this->code;
        if (\Illuminate\Support\Facades\Lang::has($codeKey)) {
            return __($codeKey, $this->metadata ?? []);
        }

        $jsonKey = __($this->code);
        if ($jsonKey !== $this->code) {
            return $jsonKey;
        }

        return __($this->message);
    }

    /**
     * Accessor for translated alert message at render time.
     */
    public function getDisplayMessageAttribute(): string
    {
        return $this->renderMessage();
    }

    /**
     * Scope for open alerts.
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open');
    }

    /**
     * Scope for active alerts (open or acknowledged, but not resolved).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['open', 'acknowledged']);
    }

    /**
     * Business rule: Only ONE open alert per (device_id, code).
     * If an open alert exists, update its metadata/message/timestamp.
     * Otherwise create a new open alert.
     */
    public static function raise(int $deviceId, string $severity, string $code, string $message, ?array $metadata = null): self
    {
        $existing = static::where('device_id', $deviceId)
            ->where('code', $code)
            ->whereIn('status', ['open', 'acknowledged'])
            ->first();

        if ($existing) {
            $existing->update([
                'severity' => $severity,
                'message' => $message,
                'metadata' => $metadata ? array_merge($existing->metadata ?? [], $metadata) : $existing->metadata,
            ]);

            return $existing;
        }

        return static::create([
            'device_id' => $deviceId,
            'severity' => $severity,
            'code' => $code,
            'message' => $message,
            'status' => 'open',
            'metadata' => $metadata,
            'opened_at' => Carbon::now('UTC'),
        ]);
    }

    /**
     * Resolve an open alert for a device and code.
     */
    public static function resolve(int $deviceId, string $code): ?self
    {
        $alert = static::where('device_id', $deviceId)
            ->where('code', $code)
            ->whereIn('status', ['open', 'acknowledged'])
            ->first();

        if ($alert) {
            $alert->update([
                'status' => 'resolved',
                'resolved_at' => Carbon::now('UTC'),
            ]);
        }

        return $alert;
    }
}
