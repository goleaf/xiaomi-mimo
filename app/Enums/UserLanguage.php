<?php

namespace App\Enums;

enum UserLanguage: string
{
    case English = 'en';
    case Lithuanian = 'lt';
    case Russian = 'ru';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
