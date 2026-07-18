<?php

namespace App\Enums;

enum TodoPriority: string
{
    case None = 'none';
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Urgent = 'urgent';

    public function weight(): int
    {
        return match ($this) {
            self::None => 0,
            self::Low => 1,
            self::Medium => 2,
            self::High => 3,
            self::Urgent => 4,
        };
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::None => 'gray',
            self::Low => 'blue',
            self::Medium => 'yellow',
            self::High => 'orange',
            self::Urgent => 'red',
        };
    }
}
