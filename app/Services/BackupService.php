<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\SplFileInfo;

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

    /** @return list<array{filename: string, path: string, size: int, created_at: int}> */
    public function listBackups(): array
    {
        $backupDir = storage_path('app/backups');

        if (! File::isDirectory($backupDir)) {
            return [];
        }

        $backups = collect(File::files($backupDir))
            ->filter(fn (SplFileInfo $file): bool => $file->getExtension() === 'sqlite')
            ->map(function (SplFileInfo $file): array {
                $size = $file->getSize();
                $modifiedAt = $file->getMTime();

                return [
                    'filename' => $file->getFilename(),
                    'path' => $file->getPathname(),
                    'size' => $size === false ? 0 : $size,
                    'created_at' => $modifiedAt === false ? 0 : $modifiedAt,
                ];
            })
            ->sortByDesc('created_at')
            ->all();

        return array_values($backups);
    }
}
