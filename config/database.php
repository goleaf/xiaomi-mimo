<?php

use Illuminate\Support\Str;
use Pdo\Mysql;

$sqliteInteger = static function (string $key, int $default, int $minimum, int $maximum): int {
    $value = env($key, $default);

    if (filter_var($value, FILTER_VALIDATE_INT) === false) {
        throw new InvalidArgumentException("{$key} must be an integer.");
    }

    $value = (int) $value;

    if ($value < $minimum || $value > $maximum) {
        throw new InvalidArgumentException("{$key} must be between {$minimum} and {$maximum}.");
    }

    return $value;
};

$sqliteChoice = static function (string $key, string $default, array $allowed): string {
    $value = Str::upper(trim((string) env($key, $default)));

    if (! in_array($value, $allowed, true)) {
        throw new InvalidArgumentException("{$key} has an unsupported value.");
    }

    return $value;
};

return [

    'default' => env('DB_CONNECTION', 'sqlite'),

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DB_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
            'busy_timeout' => $sqliteInteger('DB_BUSY_TIMEOUT', 5000, 0, 60_000),
            'journal_mode' => $sqliteChoice(
                'DB_JOURNAL_MODE',
                'WAL',
                ['DELETE', 'TRUNCATE', 'PERSIST', 'MEMORY', 'WAL'],
            ),
            'synchronous' => $sqliteChoice(
                'DB_SYNCHRONOUS',
                'NORMAL',
                ['NORMAL', 'FULL', 'EXTRA'],
            ),
            'transaction_mode' => $sqliteChoice(
                'DB_TRANSACTION_MODE',
                'DEFERRED',
                ['DEFERRED', 'IMMEDIATE', 'EXCLUSIVE'],
            ),
            'allowed_directory' => env('DB_SQLITE_ALLOWED_DIRECTORY', database_path()),
            'pragmas' => [
                'cache_size' => $sqliteInteger('DB_CACHE_SIZE', -20_000, -1_000_000, 1_000_000),
                'temp_store' => $sqliteChoice('DB_TEMP_STORE', 'MEMORY', ['DEFAULT', 'FILE', 'MEMORY']),
                'wal_autocheckpoint' => $sqliteInteger('DB_WAL_AUTOCHECKPOINT', 1000, 0, 1_000_000),
            ],
        ],

        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                Mysql::ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => env('DB_SSLMODE', 'prefer'),
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
        ],

    ],

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug((string) env('APP_NAME', 'laravel')).'-database-'),
            'persistent' => env('REDIS_PERSISTENT', false),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
            'max_retries' => env('REDIS_MAX_RETRIES', 3),
            'backoff_algorithm' => env('REDIS_BACKOFF_ALGORITHM', 'decorrelated_jitter'),
            'backoff_base' => env('REDIS_BACKOFF_BASE', 100),
            'backoff_cap' => env('REDIS_BACKOFF_CAP', 1000),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
            'max_retries' => env('REDIS_MAX_RETRIES', 3),
            'backoff_algorithm' => env('REDIS_BACKOFF_ALGORITHM', 'decorrelated_jitter'),
            'backoff_base' => env('REDIS_BACKOFF_BASE', 100),
            'backoff_cap' => env('REDIS_BACKOFF_CAP', 1000),
        ],

    ],

];
