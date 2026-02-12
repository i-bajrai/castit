<?php

namespace App\Enums;

enum CompanyRole: string
{
    case Admin = 'admin';
    case Engineer = 'engineer';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Engineer => 'Engineer',
            self::Viewer => 'Viewer',
        };
    }
}
