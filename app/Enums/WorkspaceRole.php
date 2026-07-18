<?php

namespace App\Enums;

use Illuminate\Support\Str;

enum WorkspaceRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';

    public function label(): string
    {
        return Str::title($this->value);
    }
}
