<?php

namespace App\Enums;

enum ReminderType: string
{
    case Email = 'email';
    case InApp = 'in_app';
    case Browser = 'browser';
}
