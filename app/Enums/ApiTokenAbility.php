<?php

namespace App\Enums;

enum ApiTokenAbility: string
{
    case WorkspacesRead = 'workspaces:read';
    case WorkspacesWrite = 'workspaces:write';
    case ProjectsRead = 'projects:read';
    case ProjectsWrite = 'projects:write';
    case TasksRead = 'tasks:read';
    case TasksWrite = 'tasks:write';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
