<?php

use App\Actions\CreateDatabaseBackup;
use App\Actions\RestoreDatabaseBackup;
use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use App\Services\BackupService;
use Illuminate\Foundation\ArrayMaintenanceMode;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\mock;

/**
 * @return array{directory: string, database: string, backups: string}
 */
function isolatedBackupDatabase(): array
{
    $directory = sys_get_temp_dir().'/xiaomi-mimo-backup-'.Str::uuid();
    $database = $directory.'/database.sqlite';
    $backups = $directory.'/backups';

    File::ensureDirectoryExists($backups);

    $sqlite = new SQLite3($database);
    $sqlite->enableExceptions(true);
    $sqlite->exec('PRAGMA foreign_keys = ON');
    $sqlite->exec('PRAGMA journal_mode = WAL');
    $sqlite->exec('CREATE TABLE migrations (id INTEGER PRIMARY KEY AUTOINCREMENT, migration TEXT NOT NULL, batch INTEGER NOT NULL)');
    $sqlite->exec("INSERT INTO migrations (migration, batch) VALUES ('0001_create_fixture', 1)");
    $sqlite->exec('CREATE TABLE users (id TEXT PRIMARY KEY, name TEXT NOT NULL)');
    $sqlite->exec('CREATE TABLE workspaces (id TEXT PRIMARY KEY, owner_id TEXT NOT NULL, FOREIGN KEY (owner_id) REFERENCES users (id))');
    $sqlite->exec('CREATE TABLE todos (id TEXT PRIMARY KEY, workspace_id TEXT NOT NULL, title TEXT NOT NULL, FOREIGN KEY (workspace_id) REFERENCES workspaces (id))');
    $sqlite->exec('CREATE TABLE backup_probe (value TEXT NOT NULL)');
    $sqlite->exec("INSERT INTO users (id, name) VALUES ('user-1', 'Owner')");
    $sqlite->exec("INSERT INTO workspaces (id, owner_id) VALUES ('workspace-1', 'user-1')");
    $sqlite->exec("INSERT INTO todos (id, workspace_id, title) VALUES ('todo-1', 'workspace-1', 'Included from WAL')");
    $sqlite->exec("INSERT INTO backup_probe (value) VALUES ('before')");
    $sqlite->close();

    return compact('directory', 'database', 'backups');
}

function removeIsolatedBackupDatabase(string $directory): void
{
    if (File::isDirectory($directory)) {
        File::deleteDirectory($directory);
    }
}

function configuredBackupOperator(): User
{
    $user = User::factory()->create();
    $workspace = Workspace::factory()->for($user, 'owner')->create();
    WorkspaceMember::factory()->for($workspace)->for($user)->create(['role' => WorkspaceRole::Owner]);

    config()->set('backup.operator_email', $user->email);

    return $user;
}

test('online backup captures committed WAL content and exposes only opaque inventory metadata', function () {
    $fixture = isolatedBackupDatabase();

    try {
        $writer = new SQLite3($fixture['database']);
        $writer->enableExceptions(true);
        $writer->exec('PRAGMA journal_mode = WAL');
        $writer->exec("INSERT INTO todos (id, workspace_id, title) VALUES ('todo-2', 'workspace-1', 'Uncheckpointed row')");

        $service = new BackupService($fixture['database'], $fixture['backups']);
        $backup = $service->create();
        $inventory = $service->listBackups();
        $snapshot = new SQLite3($service->verifiedPath($backup['id']), SQLITE3_OPEN_READONLY);

        expect($backup)
            ->toHaveKeys(['id', 'size', 'created_at'])
            ->not->toHaveKeys(['path', 'filename', 'checksum'])
            ->and(Str::isUuid($backup['id']))->toBeTrue()
            ->and($inventory)->toBe([$backup])
            ->and($snapshot->querySingle('SELECT COUNT(*) FROM todos'))->toBe(2)
            ->and($snapshot->querySingle('PRAGMA quick_check'))->toBe('ok');

        $snapshot->close();
        $writer->close();
    } finally {
        removeIsolatedBackupDatabase($fixture['directory']);
    }
});

test('backup verification rejects tampered snapshots', function () {
    $fixture = isolatedBackupDatabase();

    try {
        $service = new BackupService($fixture['database'], $fixture['backups']);
        $backup = $service->create();
        File::append($service->verifiedPath($backup['id']), 'tampered');

        expect(fn () => $service->verifiedPath($backup['id']))
            ->toThrow(RuntimeException::class, 'signed manifest');
    } finally {
        removeIsolatedBackupDatabase($fixture['directory']);
    }
});

test('backup creation fails safely while another operation holds the file lock', function () {
    $fixture = isolatedBackupDatabase();
    $lockHandle = null;

    try {
        $lockHandle = fopen($fixture['backups'].'/.database-backup.lock', 'c+');

        expect($lockHandle)->not->toBeFalse();

        if ($lockHandle === false) {
            return;
        }

        expect(flock($lockHandle, LOCK_EX | LOCK_NB))->toBeTrue();

        $service = new BackupService($fixture['database'], $fixture['backups'], 0);

        expect(fn () => $service->create())
            ->toThrow(RuntimeException::class, 'already running');
    } finally {
        if (is_resource($lockHandle)) {
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
        }

        removeIsolatedBackupDatabase($fixture['directory']);
    }
});

test('restore replaces the isolated database through the SQLite backup API', function () {
    $fixture = isolatedBackupDatabase();

    try {
        $service = new BackupService($fixture['database'], $fixture['backups']);
        $backup = $service->create();
        $database = new SQLite3($fixture['database']);
        $database->enableExceptions(true);
        $database->exec("UPDATE backup_probe SET value = 'after'");
        $database->close();

        $service->restore($backup['id']);

        $restored = new SQLite3($fixture['database'], SQLITE3_OPEN_READONLY);

        expect($restored->querySingle('SELECT value FROM backup_probe'))->toBe('before')
            ->and($restored->querySingle('PRAGMA quick_check'))->toBe('ok')
            ->and($restored->querySingle('PRAGMA foreign_key_check'))->toBeNull();

        $restored->close();
    } finally {
        removeIsolatedBackupDatabase($fixture['directory']);
    }
});

test('restore rejects a schema mismatch without changing the target database', function () {
    $fixture = isolatedBackupDatabase();

    try {
        $service = new BackupService($fixture['database'], $fixture['backups']);
        $backup = $service->create();
        $database = new SQLite3($fixture['database']);
        $database->enableExceptions(true);
        $database->exec("UPDATE backup_probe SET value = 'after'");
        $database->exec("INSERT INTO migrations (migration, batch) VALUES ('0002_new_schema', 2)");
        $database->close();

        expect(fn () => $service->restore($backup['id']))
            ->toThrow(RuntimeException::class, 'schema');

        $unchanged = new SQLite3($fixture['database'], SQLITE3_OPEN_READONLY);

        expect($unchanged->querySingle('SELECT value FROM backup_probe'))->toBe('after');

        $unchanged->close();
    } finally {
        removeIsolatedBackupDatabase($fixture['directory']);
    }
});

test('restore rolls back when post-restore validation fails', function () {
    $fixture = isolatedBackupDatabase();

    try {
        $service = new class($fixture['database'], $fixture['backups']) extends BackupService
        {
            private int $validationCount = 0;

            /** @return list<string> */
            protected function validateDatabase(string $path): array
            {
                $migrations = parent::validateDatabase($path);
                $this->validationCount++;

                if ($this->validationCount === 5) {
                    throw new RuntimeException('Simulated post-restore validation failure.');
                }

                return $migrations;
            }
        };
        $backup = $service->create();
        $database = new SQLite3($fixture['database']);
        $database->enableExceptions(true);
        $database->exec("UPDATE backup_probe SET value = 'after'");
        $database->close();

        expect(fn () => $service->restore($backup['id']))
            ->toThrow(RuntimeException::class, 'rolled back');

        $rolledBack = new SQLite3($fixture['database'], SQLITE3_OPEN_READONLY);

        expect($rolledBack->querySingle('SELECT value FROM backup_probe'))->toBe('after')
            ->and($rolledBack->querySingle('PRAGMA quick_check'))->toBe('ok');

        $rolledBack->close();
    } finally {
        removeIsolatedBackupDatabase($fixture['directory']);
    }
});

test('restore action always releases its maintenance guard', function () {
    $maintenance = new ArrayMaintenanceMode;
    $service = mock(BackupService::class);
    $service->shouldReceive('restore')->once()->with('backup-id')->andThrow(new RuntimeException('failed'));
    $action = new RestoreDatabaseBackup($service, $maintenance);

    expect(fn () => $action->handle('backup-id'))->toThrow(RuntimeException::class)
        ->and($maintenance->active())->toBeFalse();
});

test('web backup management is disabled until an operator is configured', function () {
    $owner = User::factory()->create();
    $workspace = Workspace::factory()->for($owner, 'owner')->create();
    WorkspaceMember::factory()->for($workspace)->for($owner)->create(['role' => WorkspaceRole::Owner]);
    config()->set('backup.operator_email', null);

    $this->actingAs($owner)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('backup.edit'))
        ->assertForbidden();

    $this->actingAs($owner)
        ->get(route('profile.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('capabilities.manageDatabaseBackups', false));

    expect(config('nativephp.cleanup_env_keys'))->toContain('BACKUP_OPERATOR_EMAIL');
});

test('configured operator must own a workspace and recently confirm their password', function () {
    $operator = User::factory()->create();
    config()->set('backup.operator_email', $operator->email);

    $this->actingAs($operator)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('backup.edit'))
        ->assertForbidden();

    $workspace = Workspace::factory()->for($operator, 'owner')->create();
    WorkspaceMember::factory()->for($workspace)->for($operator)->create(['role' => WorkspaceRole::Owner]);

    $this->actingAs($operator)
        ->get(route('profile.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('capabilities.manageDatabaseBackups', true));

    $this->actingAs($operator)
        ->withSession(['auth.password_confirmed_at' => 0])
        ->get(route('backup.edit'))
        ->assertRedirect(route('password.confirm'));
});

test('configured and password-confirmed operator sees an opaque backup inventory', function () {
    $operator = configuredBackupOperator();
    $backup = [
        'id' => (string) Str::uuid(),
        'size' => 2048,
        'created_at' => now()->timestamp,
    ];
    $service = mock(BackupService::class);
    $service->shouldReceive('listBackups')->once()->andReturn([$backup]);
    app()->instance(BackupService::class, $service);

    $this->actingAs($operator)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('backup.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/Backup')
            ->where('backups.0', $backup)
            ->missing('backups.0.path')
            ->missing('backups.0.filename'));
});

test('backup mutations deny ordinary workspace owners and reject filename-shaped identifiers', function () {
    $operator = configuredBackupOperator();
    $ordinaryOwner = User::factory()->create();
    $workspace = Workspace::factory()->for($ordinaryOwner, 'owner')->create();
    WorkspaceMember::factory()->for($workspace)->for($ordinaryOwner)->create(['role' => WorkspaceRole::Owner]);

    $this->actingAs($ordinaryOwner)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->post(route('backup.create'))
        ->assertForbidden();

    $this->actingAs($operator)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get('/backups/..%2Fdatabase%2Fdatabase.sqlite/download')
        ->assertNotFound();
});

test('operator backup creation uses the action and redirects without exposing a server path', function () {
    $operator = configuredBackupOperator();
    $backup = ['id' => (string) Str::uuid(), 'size' => 1024, 'created_at' => now()->timestamp];
    $action = mock(CreateDatabaseBackup::class);
    $action->shouldReceive('handle')->once()->andReturn($backup);
    app()->instance(CreateDatabaseBackup::class, $action);

    $response = $this->actingAs($operator)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->post(route('backup.create'));

    $response->assertRedirect()->assertSessionHas('success');
    expect($response->headers->get('X-Backup-Path'))->toBeNull();
});

test('operator receives a safe error when backup creation fails', function () {
    $operator = configuredBackupOperator();
    $action = mock(CreateDatabaseBackup::class);
    $action->shouldReceive('handle')->once()->andThrow(new RuntimeException('private failure'));
    app()->instance(CreateDatabaseBackup::class, $action);

    $this->actingAs($operator)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->post(route('backup.create'))
        ->assertRedirect()
        ->assertSessionHasErrors('backup');
});

test('operator restore uses the guarded action and returns to the dashboard', function () {
    $operator = configuredBackupOperator();
    $backupId = (string) Str::uuid();
    $action = mock(RestoreDatabaseBackup::class);
    $action->shouldReceive('handle')->once()->with($backupId);
    app()->instance(RestoreDatabaseBackup::class, $action);

    $this->actingAs($operator)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->post(route('backup.restore', ['backup' => $backupId]))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('success');
});

test('verified backup download is private and uses a safe public filename', function () {
    $operator = configuredBackupOperator();
    $backupId = (string) Str::uuid();
    $download = storage_path('framework/testing/'.$backupId.'.sqlite');
    File::ensureDirectoryExists(dirname($download));
    File::put($download, 'SQLite format 3');
    $service = mock(BackupService::class);
    $service->shouldReceive('verifiedPath')->once()->with($backupId)->andReturn($download);
    app()->instance(BackupService::class, $service);

    try {
        $this->actingAs($operator)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('backup.download', ['backup' => $backupId]))
            ->assertOk()
            ->assertHeader('Cache-Control', 'max-age=0, no-store, private')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertDownload();
    } finally {
        File::delete($download);
    }
});
