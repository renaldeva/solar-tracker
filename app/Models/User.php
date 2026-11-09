<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'force_password_change',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'force_password_change' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function isActive(): bool
    {
        return (bool) ($this->is_active ?? true);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin || $this->role?->value === 'admin';
    }

    public function isOperator(): bool
    {
        return $this->role === UserRole::Operator || $this->role?->value === 'operator';
    }

    public function isViewer(): bool
    {
        return $this->role === UserRole::Viewer || $this->role?->value === 'viewer';
    }

    public function canControl(): bool
    {
        return $this->isAdmin() || $this->isOperator();
    }

    public function canManageUsers(): bool
    {
        return $this->isAdmin();
    }

    public function commands(): HasMany
    {
        return $this->hasMany(Command::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function acknowledgedAlerts(): HasMany
    {
        return $this->hasMany(Alert::class, 'acknowledged_by');
    }
}
