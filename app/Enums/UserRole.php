<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Operator = 'operator';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::Admin => __('Admin'),
            self::Operator => __('Operator'),
            self::Viewer => __('Pengamat'),
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Admin => 'bg-purple-50 text-purple-700 border-purple-200',
            self::Operator => 'bg-blue-50 text-blue-700 border-blue-200',
            self::Viewer => 'bg-slate-100 text-slate-700 border-slate-200',
        };
    }
}
