<?php

namespace App\Services;

use Illuminate\Config\Repository;
use Illuminate\Database\DatabaseManager;
use RuntimeException;
use Throwable;

class SqliteHealthService
{
    public function __construct(
        private DatabaseManager $database,
        private Repository $config,
    ) {}

    public function assertConfigurationIsSafe(): void
    {
        if ($this->config->string('database.default') !== 'sqlite') {
            throw new RuntimeException('SQLite must be the default database connection.');
        }

        $path = $this->databasePath();

        if ($this->isMemoryDatabase($path)) {
            return;
        }

        if (! $this->isAbsolutePath($path)) {
            throw new RuntimeException('DB_DATABASE must be an absolute SQLite database path.');
        }

        $resolvedPath = realpath($path);
        $allowedDirectory = realpath($this->config->string('database.connections.sqlite.allowed_directory'));

        if ($resolvedPath === false || ! is_file($resolvedPath)) {
            throw new RuntimeException('The configured SQLite database file does not exist.');
        }

        if ($allowedDirectory === false || ! is_dir($allowedDirectory)) {
            throw new RuntimeException('The configured SQLite allowed directory does not exist.');
        }

        if (! $this->isWithinDirectory($resolvedPath, $allowedDirectory)) {
            throw new RuntimeException('The SQLite database file is outside the allowed directory.');
        }

        if (! is_readable($resolvedPath) || ! is_writable($resolvedPath)) {
            throw new RuntimeException('The SQLite database file must be readable and writable.');
        }

        if (! is_writable(dirname($resolvedPath))) {
            throw new RuntimeException('The SQLite database directory must be writable.');
        }
    }

    /**
     * @return array{
     *     status: 'ok',
     *     driver: 'sqlite',
     *     checks: array{path: bool, foreign_keys: bool, journal_mode: bool, synchronous: bool, busy_timeout: bool, cache_size: bool, temp_store: bool, wal_autocheckpoint: bool, quick_check: bool, foreign_key_check: bool}
     * }
     */
    public function report(): array
    {
        $this->assertConfigurationIsSafe();

        $connection = $this->database->connection('sqlite');
        $memory = $this->isMemoryDatabase($this->databasePath());
        $expectedJournalMode = strtolower($this->config->string('database.connections.sqlite.journal_mode'));
        $expectedSynchronous = match ($this->config->string('database.connections.sqlite.synchronous')) {
            'NORMAL' => 1,
            'FULL' => 2,
            'EXTRA' => 3,
            default => -1,
        };
        $expectedTempStore = match ($this->config->string('database.connections.sqlite.pragmas.temp_store')) {
            'DEFAULT' => 0,
            'FILE' => 1,
            'MEMORY' => 2,
            default => -1,
        };

        $checks = [
            'path' => true,
            'foreign_keys' => $this->pragmaInteger('foreign_keys') === 1,
            'journal_mode' => $memory || $this->pragmaString('journal_mode') === $expectedJournalMode,
            'synchronous' => $this->pragmaInteger('synchronous') === $expectedSynchronous,
            'busy_timeout' => $this->pragmaInteger('busy_timeout') === $this->config->integer('database.connections.sqlite.busy_timeout'),
            'cache_size' => $this->pragmaInteger('cache_size') === $this->config->integer('database.connections.sqlite.pragmas.cache_size'),
            'temp_store' => $this->pragmaInteger('temp_store') === $expectedTempStore,
            'wal_autocheckpoint' => $this->pragmaInteger('wal_autocheckpoint') === $this->config->integer('database.connections.sqlite.pragmas.wal_autocheckpoint'),
            'quick_check' => $this->pragmaString('quick_check') === 'ok',
            'foreign_key_check' => $connection->select('PRAGMA foreign_key_check') === [],
        ];

        if (in_array(false, $checks, true)) {
            throw new RuntimeException('SQLite health verification failed.');
        }

        return [
            'status' => 'ok',
            'driver' => 'sqlite',
            'checks' => $checks,
        ];
    }

    public function assertHealthy(): void
    {
        try {
            $this->report();
        } catch (Throwable) {
            throw new RuntimeException('SQLite health verification failed.');
        }
    }

    private function databasePath(): string
    {
        $path = $this->config->get('database.connections.sqlite.database');

        if (! is_string($path) || trim($path) === '') {
            throw new RuntimeException('DB_DATABASE must contain a SQLite database path.');
        }

        return $path;
    }

    private function isMemoryDatabase(string $path): bool
    {
        return $path === ':memory:'
            || str_starts_with($path, 'file:')
            || str_contains($path, '?mode=memory')
            || str_contains($path, '&mode=memory');
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR)
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1;
    }

    private function isWithinDirectory(string $path, string $directory): bool
    {
        $directory = rtrim($directory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        return str_starts_with($path, $directory);
    }

    private function pragmaInteger(string $pragma): int
    {
        return (int) $this->pragmaValue($pragma);
    }

    private function pragmaString(string $pragma): string
    {
        return strtolower((string) $this->pragmaValue($pragma));
    }

    private function pragmaValue(string $pragma): mixed
    {
        $row = $this->database->connection('sqlite')->selectOne("PRAGMA {$pragma}");

        if (! is_object($row)) {
            throw new RuntimeException("SQLite did not return PRAGMA {$pragma}.");
        }

        return array_values((array) $row)[0] ?? null;
    }
}
