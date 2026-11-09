<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Command extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'commands';

    protected $fillable = [
        'id',
        'device_id',
        'user_id',
        'type',
        'params',
        'status',
        'reason',
        'issued_at',
        'expires_at',
        'accepted_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'params' => 'array',
            'issued_at' => 'datetime',
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
