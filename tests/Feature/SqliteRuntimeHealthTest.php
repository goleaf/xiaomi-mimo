<?php

use App\Http\Resources\ChecklistResource;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\Todo;
use App\Services\SqliteHealthService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

test('SQLite runtime settings are configurable and documented', function () {
    $environment = file_get_contents(base_path('.env.example'));

    expect(config('database.default'))->toBe('sqlite')
        ->and(config('database.connections.sqlite.database'))->toBe(':memory:')
        ->and(config('database.connections.sqlite.foreign_key_constraints'))->toBeTrue()
        ->and(config('database.connections.sqlite.busy_timeout'))->toBe(5000)
        ->and(config('database.connections.sqlite.journal_mode'))->toBe('WAL')
        ->and(config('database.connections.sqlite.synchronous'))->toBe('NORMAL')
        ->and(config('database.connections.sqlite.transaction_mode'))->toBe('DEFERRED')
        ->and(config('database.connections.sqlite.pragmas'))->toBe([
            'cache_size' => -20_000,
            'temp_store' => 'MEMORY',
            'wal_autocheckpoint' => 1000,
        ])
        ->and($environment)->toContain(
            '# DB_DATABASE=/absolute/path/to/database.sqlite',
            '# DB_SQLITE_ALLOWED_DIRECTORY=/absolute/path/to',
            'DB_FOREIGN_KEYS=true',
            'DB_BUSY_TIMEOUT=5000',
            'DB_JOURNAL_MODE=WAL',
            'DB_SYNCHRONOUS=NORMAL',
            'DB_TRANSACTION_MODE=DEFERRED',
            'DB_CACHE_SIZE=-20000',
            'DB_TEMP_STORE=MEMORY',
            'DB_WAL_AUTOCHECKPOINT=1000',
        );
});

test('health checks verify SQLite pragmas and integrity without exposing paths', function () {
    $report = app(SqliteHealthService::class)->report();

    expect($report['status'])->toBe('ok')
        ->and($report['driver'])->toBe('sqlite')
        ->and($report['checks'])->each->toBeTrue();

    $this->get('/up')->assertOk();

    expect(Artisan::call('app:database-health', ['--json' => true]))->toBe(0);

    $output = Artisan::output();

    expect($output)->toContain('"status":"ok"')
        ->not->toContain(base_path())
        ->not->toContain('database.sqlite');
});

test('SQLite path validation rejects relative and out of bounds database files', function () {
    config()->set('database.connections.sqlite.database', 'database/database.sqlite');

    expect(fn () => app(SqliteHealthService::class)->assertConfigurationIsSafe())
        ->toThrow(RuntimeException::class, 'must be an absolute');

    $outsideDatabase = tempnam(sys_get_temp_dir(), 'mimo-sqlite-');
    expect($outsideDatabase)->not->toBeFalse();

    try {
        config()->set('database.connections.sqlite.database', $outsideDatabase);
        config()->set('database.connections.sqlite.allowed_directory', database_path());

        expect(fn () => app(SqliteHealthService::class)->assertConfigurationIsSafe())
            ->toThrow(RuntimeException::class, 'outside the allowed directory');
    } finally {
        if (is_string($outsideDatabase)) {
            @unlink($outsideDatabase);
        }
    }
});

test('model and resource progress serialization does not execute hidden queries', function () {
    $todo = Todo::factory()->create();
    $checklist = Checklist::factory()->for($todo)->create();
    ChecklistItem::factory()->count(3)->for($checklist)->sequence(
        ['is_checked' => true],
        ['is_checked' => false],
        ['is_checked' => true],
    )->create();

    $checklist->load('items');
    $connection = DB::connection();
    $connection->flushQueryLog();
    $connection->enableQueryLog();

    $resource = (new ChecklistResource($checklist))->resolve(request());
    $queries = $connection->getQueryLog();
    $connection->disableQueryLog();

    expect($resource['progress'])->toBe(66.67)
        ->and($queries)->toBe([])
        ->and(file_get_contents(app_path('Models/Todo.php')))->not->toContain('getProgressAttribute')
        ->and(file_get_contents(app_path('Models/Checklist.php')))->not->toContain('getProgressAttribute');
});

test('bounded transactions retry SQLite locks but do not retry constraints', function () {
    $databasePath = tempnam(sys_get_temp_dir(), 'mimo-lock-');
    expect($databasePath)->not->toBeFalse();

    $connectionName = 'sqlite-lock-test';
    $locker = new PDO('sqlite:'.$databasePath);
    $locker->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $locker->exec('CREATE TABLE records (id INTEGER PRIMARY KEY, value TEXT NOT NULL UNIQUE)');
    $locker->beginTransaction();
    $locker->exec("INSERT INTO records (id, value) VALUES (1, 'locked')");

    config()->set("database.connections.{$connectionName}", [
        'driver' => 'sqlite',
        'database' => $databasePath,
        'prefix' => '',
        'foreign_key_constraints' => true,
        'busy_timeout' => 1,
        'transaction_mode' => 'DEFERRED',
    ]);

    $lockAttempts = 0;

    try {
        expect(function () use (&$lockAttempts, $connectionName): void {
            DB::connection($connectionName)->transaction(
                function () use (&$lockAttempts, $connectionName): void {
                    $lockAttempts++;
                    DB::connection($connectionName)->insert(
                        "INSERT INTO records (id, value) VALUES (2, 'blocked')",
                    );
                },
                3,
            );
        })->toThrow(QueryException::class);

        expect($lockAttempts)->toBe(3);

        $locker->rollBack();
        DB::purge($connectionName);
        DB::connection($connectionName)->insert(
            "INSERT INTO records (id, value) VALUES (1, 'unique')",
        );

        $constraintAttempts = 0;

        expect(function () use (&$constraintAttempts, $connectionName): void {
            DB::connection($connectionName)->transaction(
                function () use (&$constraintAttempts, $connectionName): void {
                    $constraintAttempts++;
                    DB::connection($connectionName)->insert(
                        "INSERT INTO records (id, value) VALUES (1, 'duplicate')",
                    );
                },
                3,
            );
        })->toThrow(QueryException::class);

        expect($constraintAttempts)->toBe(1);
    } finally {
        if ($locker->inTransaction()) {
            $locker->rollBack();
        }

        DB::purge($connectionName);

        if (is_string($databasePath)) {
            @unlink($databasePath);
        }
    }
});
