<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case ProjectManager = 'project_manager';
    case CostController = 'cost_controller';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::ProjectManager => 'Project Manager',
            self::CostController => 'Cost Controller',
            self::Viewer => 'Viewer',
        };
    }
}
