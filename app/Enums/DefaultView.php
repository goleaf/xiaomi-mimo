<?php

namespace App\Enums;

enum DefaultView: string
{
    case List = 'list';
    case Board = 'board';
    case Calendar = 'calendar';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
