<?php

return [
    'directory' => storage_path('app/backups'),
    'operator_email' => env('BACKUP_OPERATOR_EMAIL'),
    'lock_timeout' => (int) env('BACKUP_LOCK_TIMEOUT', 10),
    'maintenance_retry' => (int) env('BACKUP_MAINTENANCE_RETRY', 60),
];
