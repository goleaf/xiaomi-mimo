<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class BackupService
{
    public function backup(): string
    {
        $dbPath = config('database.connections.sqlite.database');
        $backupDir = storage_path('app/backups');

        if (! File::isDirectory($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $filename = 'backup_'.now()->format('Y-m-d_His').'.sqlite';
        $backupPath = $backupDir.'/'.$filename;

        File::copy($dbPath, $backupPath);

        return $backupPath;
    }

    public function restore(string $backupPath): bool
    {
        if (! File::exists($backupPath)) {
            return false;
        }

        $dbPath = config('database.connections.sqlite.database');

        return File::copy($backupPath, $dbPath);
    }

    public function listBackups(): array
    {
        $backupDir = storage_path('app/backups');

        if (! File::isDirectory($backupDir)) {
            return [];
        }

        return collect(File::files($backupDir))
            ->filter(fn ($file) => $file->getExtension() === 'sqlite')
            ->map(fn ($file) => [
                'filename' => $file->getFilename(),
                'path' => $file->getPathname(),
                'size' => $file->getSize(),
                'created_at' => $file->getCreationTime(),
            ])
            ->sortByDesc('created_at')
            ->values()
            ->toArray();
    }
}
