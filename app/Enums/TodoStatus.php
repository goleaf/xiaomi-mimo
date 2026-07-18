<?php

namespace App\Enums;

use Illuminate\Support\Str;

enum TodoStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::InProgress => 'In Progress',
            default => Str::title($this->value),
        };
    }
}
