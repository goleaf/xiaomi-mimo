<?php

namespace App\Console\Commands;

use App\Services\SqliteHealthService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use JsonException;
use Throwable;

#[Signature('app:database-health {--json : Return a machine-readable report}')]
#[Description('Verify the configured SQLite path, pragmas, and integrity')]
class DatabaseHealthCommand extends Command
{
    /** @throws JsonException */
    public function handle(SqliteHealthService $health): int
    {
        try {
            $report = $health->report();
        } catch (Throwable) {
            if ($this->option('json')) {
                $this->line(json_encode([
                    'status' => 'failed',
                    'driver' => 'sqlite',
                    'message' => 'SQLite health verification failed.',
                ], JSON_THROW_ON_ERROR));
            } else {
                $this->error('SQLite health verification failed.');
            }

            return self::FAILURE;
        }

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_THROW_ON_ERROR));
        } else {
            $this->info('SQLite health verification passed.');
        }

        return self::SUCCESS;
    }
}
