<?php

namespace App\Enums;

enum ReminderStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Delivered = 'delivered';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
}
