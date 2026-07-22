<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use JsonException;
use RuntimeException;
use SQLite3;
use Throwable;

class BackupService
{
    private const int MANIFEST_VERSION = 1;

    private string $databasePath;

    private string $backupDirectory;

    private int $lockTimeoutSeconds;

    private bool $usesDefaultDatabase;

    public function __construct(
        ?string $databasePath = null,
        ?string $backupDirectory = null,
        ?int $lockTimeoutSeconds = null,
    ) {
        $configuredDatabase = config('database.connections.sqlite.database');
        $configuredPath = is_string($configuredDatabase) ? $configuredDatabase : '';
        $this->databasePath = $this->resolveDatabasePath($databasePath ?? $configuredPath);
        $this->backupDirectory = rtrim(
            $backupDirectory ?? (string) config('backup.directory', storage_path('app/backups')),
            DIRECTORY_SEPARATOR,
        );
        $this->lockTimeoutSeconds = max(
            0,
            $lockTimeoutSeconds ?? (int) config('backup.lock_timeout', 10),
        );
        $this->usesDefaultDatabase = $databasePath === null
            && $this->databasePath === $this->resolveDatabasePath($configuredPath);
    }

    /** @return array{id: string, size: int, created_at: int} */
    public function create(): array
    {
        return $this->withExclusiveLock(function (): array {
            $id = (string) Str::uuid();
            $snapshotPath = $this->snapshotPath($id);
            $temporaryPath = $snapshotPath.'.tmp';
            $manifestPath = $this->manifestPath($id);
            $temporaryManifestPath = $manifestPath.'.tmp';

            try {
                $this->backupDatabase($this->databasePath, $temporaryPath);
                $migrations = $this->validateDatabase($temporaryPath);
                File::move($temporaryPath, $snapshotPath);
                chmod($snapshotPath, 0600);

                $manifest = $this->signedManifest(
                    $id,
                    $snapshotPath,
                    $migrations,
                    now()->getTimestamp(),
                );
                File::put(
                    $temporaryManifestPath,
                    json_encode($manifest, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
                );
                chmod($temporaryManifestPath, 0600);
                File::move($temporaryManifestPath, $manifestPath);

                return $this->publicMetadata($manifest);
            } catch (Throwable $exception) {
                File::delete([
                    $temporaryPath,
                    $snapshotPath,
                    $temporaryManifestPath,
                    $manifestPath,
                ]);

                throw $this->backupException('Database backup creation failed.', $exception);
            }
        });
    }

    /** @return list<array{id: string, size: int, created_at: int}> */
    public function listBackups(): array
    {
        $this->ensureBackupDirectory();

        $backups = collect(File::glob($this->backupDirectory.'/*.json'))
            ->map(function (string $manifestPath): ?array {
                $id = pathinfo($manifestPath, PATHINFO_FILENAME);

                if (! Str::isUuid($id)) {
                    return null;
                }

                try {
                    return $this->publicMetadata($this->verifiedManifest($id));
                } catch (Throwable) {
                    return null;
                }
            })
            ->filter()
            ->sortByDesc('created_at')
            ->values()
            ->all();

        return array_values($backups);
    }

    public function verifiedPath(string $id): string
    {
        $this->verifiedManifest($id);

        return $this->snapshotPath($id);
    }

    public function restore(string $id): void
    {
        try {
            $this->withExclusiveLock(function () use ($id): void {
                $manifest = $this->verifiedManifest($id);
                $snapshotPath = $this->snapshotPath($id);
                $backupMigrations = $this->validateDatabase($snapshotPath);
                $currentMigrations = $this->validateDatabase($this->databasePath);

                if (! hash_equals(
                    $this->schemaFingerprint($currentMigrations),
                    (string) $manifest['schema_fingerprint'],
                ) || $backupMigrations !== $currentMigrations) {
                    throw new RuntimeException('The backup schema does not match the current application database schema.');
                }

                $rollbackPath = $this->backupDirectory.'/.rollback-'.Str::uuid().'.sqlite';

                if ($this->usesDefaultDatabase) {
                    DB::disconnect();
                }

                try {
                    $this->backupDatabase($this->databasePath, $rollbackPath);
                    $this->validateDatabase($rollbackPath);

                    try {
                        $this->backupDatabase($snapshotPath, $this->databasePath);
                        $restoredMigrations = $this->validateDatabase($this->databasePath);

                        if ($restoredMigrations !== $currentMigrations) {
                            throw new RuntimeException('The restored database schema changed during restore.');
                        }
                    } catch (Throwable $restoreException) {
                        try {
                            $this->backupDatabase($rollbackPath, $this->databasePath);
                            $this->validateDatabase($this->databasePath);
                        } catch (Throwable $rollbackException) {
                            throw new RuntimeException(
                                'Database restore failed and the automatic rollback also failed.',
                                previous: $rollbackException,
                            );
                        }

                        throw new RuntimeException(
                            'Database restore failed and the original database was rolled back.',
                            previous: $restoreException,
                        );
                    }
                } finally {
                    File::delete($rollbackPath);

                    if ($this->usesDefaultDatabase) {
                        DB::reconnect();
                    }
                }
            });
        } catch (Throwable $exception) {
            throw $this->backupException('Database restore failed.', $exception);
        }
    }

    /**
     * @return list<string>
     */
    protected function validateDatabase(string $path): array
    {
        $database = $this->openDatabase($path, SQLITE3_OPEN_READONLY);

        try {
            $quickCheck = $database->querySingle('PRAGMA quick_check');

            if ($quickCheck !== 'ok') {
                throw new RuntimeException('The SQLite integrity check did not return ok.');
            }

            $foreignKeys = $database->query('PRAGMA foreign_key_check');

            if ($foreignKeys === false || $foreignKeys->fetchArray(SQLITE3_NUM) !== false) {
                throw new RuntimeException('The SQLite foreign key check found invalid relationships.');
            }

            $tables = [];
            $tableResult = $database->query("SELECT name FROM sqlite_schema WHERE type = 'table'");

            while ($tableResult !== false && ($row = $tableResult->fetchArray(SQLITE3_ASSOC)) !== false) {
                if (is_string($row['name'] ?? null)) {
                    $tables[] = $row['name'];
                }
            }

            foreach (['migrations', 'users', 'workspaces', 'todos'] as $requiredTable) {
                if (! in_array($requiredTable, $tables, true)) {
                    throw new RuntimeException("The backup is missing the required {$requiredTable} table.");
                }
            }

            $migrations = [];
            $migrationResult = $database->query('SELECT migration FROM migrations ORDER BY id');

            while ($migrationResult !== false && ($row = $migrationResult->fetchArray(SQLITE3_ASSOC)) !== false) {
                if (is_string($row['migration'] ?? null)) {
                    $migrations[] = $row['migration'];
                }
            }

            if ($migrations === []) {
                throw new RuntimeException('The backup does not contain an application migration history.');
            }

            return $migrations;
        } finally {
            $database->close();
        }
    }

    private function backupDatabase(string $sourcePath, string $destinationPath): void
    {
        $source = $this->openDatabase($sourcePath, SQLITE3_OPEN_READONLY);
        $destination = $this->openDatabase(
            $destinationPath,
            File::exists($destinationPath)
                ? SQLITE3_OPEN_READWRITE
                : SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE,
        );

        try {
            if (! $source->backup($destination)) {
                throw new RuntimeException('The SQLite online backup operation failed.');
            }
        } finally {
            $destination->close();
            $source->close();
        }
    }

    private function openDatabase(string $path, int $flags): SQLite3
    {
        $database = new SQLite3($path, $flags);
        $database->enableExceptions(true);
        $database->busyTimeout((int) config('database.connections.sqlite.busy_timeout', 10_000));

        return $database;
    }

    /**
     * @param  list<string>  $migrations
     * @return array{version: int, id: string, size: int, created_at: int, checksum: string, schema_fingerprint: string, signature: string}
     */
    private function signedManifest(
        string $id,
        string $snapshotPath,
        array $migrations,
        int $createdAt,
    ): array {
        $size = File::size($snapshotPath);
        $checksum = hash_file('sha256', $snapshotPath);

        if ($checksum === false) {
            throw new RuntimeException('The database backup checksum could not be calculated.');
        }

        $manifest = [
            'version' => self::MANIFEST_VERSION,
            'id' => $id,
            'size' => $size,
            'created_at' => $createdAt,
            'checksum' => $checksum,
            'schema_fingerprint' => $this->schemaFingerprint($migrations),
        ];

        return [
            ...$manifest,
            'signature' => $this->sign($manifest),
        ];
    }

    /**
     * @return array{version: int, id: string, size: int, created_at: int, checksum: string, schema_fingerprint: string, signature: string}
     */
    private function verifiedManifest(string $id): array
    {
        if (! Str::isUuid($id)) {
            throw new RuntimeException('The backup identifier is invalid.');
        }

        $manifestPath = $this->manifestPath($id);
        $snapshotPath = $this->snapshotPath($id);

        if (! File::isFile($manifestPath) || ! File::isFile($snapshotPath)) {
            throw new RuntimeException('The requested backup does not exist.');
        }

        try {
            $decoded = json_decode(File::get($manifestPath), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('The backup manifest is invalid.', previous: $exception);
        }

        if (! is_array($decoded)
            || ($decoded['version'] ?? null) !== self::MANIFEST_VERSION
            || ($decoded['id'] ?? null) !== $id
            || ! is_int($decoded['size'] ?? null)
            || ! is_int($decoded['created_at'] ?? null)
            || ! is_string($decoded['checksum'] ?? null)
            || ! is_string($decoded['schema_fingerprint'] ?? null)
            || ! is_string($decoded['signature'] ?? null)) {
            throw new RuntimeException('The backup manifest has an invalid structure.');
        }

        $signedData = [
            'version' => $decoded['version'],
            'id' => $decoded['id'],
            'size' => $decoded['size'],
            'created_at' => $decoded['created_at'],
            'checksum' => $decoded['checksum'],
            'schema_fingerprint' => $decoded['schema_fingerprint'],
        ];

        if (! hash_equals($decoded['signature'], $this->sign($signedData))) {
            throw new RuntimeException('The backup manifest signature is invalid.');
        }

        $size = File::size($snapshotPath);

        if ($size !== $decoded['size']) {
            throw new RuntimeException('The backup size does not match its signed manifest.');
        }

        $checksum = hash_file('sha256', $snapshotPath);

        if (! is_string($checksum) || ! hash_equals($decoded['checksum'], $checksum)) {
            throw new RuntimeException('The backup checksum does not match its signed manifest.');
        }

        /** @var array{version: int, id: string, size: int, created_at: int, checksum: string, schema_fingerprint: string, signature: string} */
        return $decoded;
    }

    /**
     * @param  array{version: int, id: string, size: int, created_at: int, checksum: string, schema_fingerprint: string, signature: string}  $manifest
     * @return array{id: string, size: int, created_at: int}
     */
    private function publicMetadata(array $manifest): array
    {
        return [
            'id' => $manifest['id'],
            'size' => $manifest['size'],
            'created_at' => $manifest['created_at'],
        ];
    }

    /** @param array<string, int|string> $manifest */
    private function sign(array $manifest): string
    {
        $key = config('app.key');

        if (! is_string($key) || $key === '') {
            throw new RuntimeException('The application key is required to sign database backups.');
        }

        return hash_hmac(
            'sha256',
            json_encode($manifest, JSON_THROW_ON_ERROR),
            $key,
        );
    }

    /** @param list<string> $migrations */
    private function schemaFingerprint(array $migrations): string
    {
        return hash('sha256', implode("\n", $migrations));
    }

    private function resolveDatabasePath(string $path): string
    {
        $resolved = realpath($path);

        if ($resolved === false || ! File::isFile($resolved)) {
            throw new RuntimeException('The configured SQLite database path is invalid.');
        }

        return $resolved;
    }

    private function ensureBackupDirectory(): void
    {
        File::ensureDirectoryExists($this->backupDirectory, 0750, true);
        chmod($this->backupDirectory, 0750);
    }

    private function snapshotPath(string $id): string
    {
        return $this->backupDirectory.DIRECTORY_SEPARATOR.$id.'.sqlite';
    }

    private function manifestPath(string $id): string
    {
        return $this->backupDirectory.DIRECTORY_SEPARATOR.$id.'.json';
    }

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    private function withExclusiveLock(callable $callback): mixed
    {
        $this->ensureBackupDirectory();
        $lockPath = $this->backupDirectory.DIRECTORY_SEPARATOR.'.database-backup.lock';
        $lockHandle = fopen($lockPath, 'c+');

        if ($lockHandle === false) {
            throw new RuntimeException('The database backup lock could not be opened.');
        }

        chmod($lockPath, 0600);
        $deadline = microtime(true) + $this->lockTimeoutSeconds;
        $locked = false;

        do {
            $locked = flock($lockHandle, LOCK_EX | LOCK_NB);

            if (! $locked) {
                usleep(50_000);
            }
        } while (! $locked && microtime(true) < $deadline);

        if (! $locked) {
            fclose($lockHandle);

            throw new RuntimeException('Another database backup or restore operation is already running.');
        }

        try {
            return $callback();
        } finally {
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
        }
    }

    private function backupException(string $message, Throwable $exception): RuntimeException
    {
        if ($exception instanceof RuntimeException) {
            return $exception;
        }

        return new RuntimeException($message, previous: $exception);
    }
}
