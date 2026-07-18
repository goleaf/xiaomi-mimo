<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use Illuminate\Console\Command;

class CleanupActivities extends Command
{
    protected $signature = 'activities:cleanup {--days=90 : Number of days to keep}';
    protected $description = 'Prune old activity logs';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $deleted = ActivityLog::where('created_at', '<', now()->subDays($days))->delete();

        $this->info("Deleted {$deleted} activity logs older than {$days} days.");

        return self::SUCCESS;
    }
}
