<?php

use App\Models\Todo;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

function sqliteColumnType(string $table, string $column): ?string
{
    $definition = collect(DB::select("SELECT name, type FROM pragma_table_info('{$table}')"))
        ->firstWhere('name', $column);

    return $definition?->type;
}

/** @return list<object> */
function sqliteForeignKeys(string $table): array
{
    return DB::select("SELECT * FROM pragma_foreign_key_list('{$table}')");
}

test('fresh SQLite schema uses UUID relations and protects task parents', function () {
    expect(strtolower((string) sqliteColumnType('passkeys', 'user_id')))->toBe('varchar')
        ->and(strtolower((string) sqliteColumnType('notifications', 'notifiable_id')))->toBe('varchar')
        ->and(strtolower((string) sqliteColumnType('personal_access_tokens', 'tokenable_id')))->toBe('varchar');

    $passkeyForeignKey = collect(sqliteForeignKeys('passkeys'))->firstWhere('from', 'user_id');
    $parentForeignKey = collect(sqliteForeignKeys('todos'))->firstWhere('from', 'parent_id');
    $triggers = DB::table('sqlite_schema')
        ->where('type', 'trigger')
        ->whereIn('name', [
            'todos_parent_workspace_insert',
            'todos_parent_workspace_update',
            'todos_task_definitions_insert',
            'todos_task_definitions_update',
        ])
        ->pluck('name')
        ->sort()
        ->values()
        ->all();

    expect($passkeyForeignKey)
        ->not->toBeNull()
        ->and($passkeyForeignKey->table)->toBe('users')
        ->and(strtolower((string) $passkeyForeignKey->on_delete))->toBe('cascade')
        ->and($parentForeignKey)
        ->not->toBeNull()
        ->and($parentForeignKey->table)->toBe('todos')
        ->and(strtolower((string) $parentForeignKey->on_delete))->toBe('set null')
        ->and($triggers)->toBe([
            'todos_parent_workspace_insert',
            'todos_parent_workspace_update',
            'todos_task_definitions_insert',
            'todos_task_definitions_update',
        ]);
});

test('task parent constraints reject cross-workspace writes and null children on deletion', function () {
    $firstWorkspace = Workspace::factory()->create();
    $secondWorkspace = Workspace::factory()->create();
    $parent = Todo::factory()->for($firstWorkspace)->create();
    $child = Todo::factory()->for($firstWorkspace)->create(['parent_id' => $parent->id]);
    $foreignParent = Todo::factory()->for($secondWorkspace)->create();

    expect(fn () => DB::table('todos')
        ->where('id', $child->id)
        ->update(['parent_id' => $foreignParent->id]))
        ->toThrow(QueryException::class);

    expect(fn () => DB::table('todos')
        ->where('id', $child->id)
        ->update(['parent_id' => $child->id]))
        ->toThrow(QueryException::class);

    expect($child->fresh()->parent_id)->toBe($parent->id);

    $parent->forceDelete();

    expect($child->fresh()->parent_id)->toBeNull();
});

test('corrective migration preserves populated UUID relations through down and up', function () {
    $user = User::factory()->create();
    $passkeyCredentialId = 'credential-'.Str::uuid();
    $notificationId = (string) Str::uuid();
    $token = hash('sha256', (string) Str::uuid());

    DB::table('passkeys')->insert([
        'user_id' => $user->id,
        'name' => 'Migration fixture',
        'credential_id' => $passkeyCredentialId,
        'credential' => '{}',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('notifications')->insert([
        'id' => $notificationId,
        'type' => 'MigrationFixture',
        'notifiable_type' => User::class,
        'notifiable_id' => $user->id,
        'data' => '{}',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('personal_access_tokens')->insert([
        'tokenable_type' => User::class,
        'tokenable_id' => $user->id,
        'name' => 'Migration fixture',
        'token' => $token,
        'abilities' => '["tasks:read"]',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $migration = require database_path('migrations/2026_07_22_154915_correct_uuid_relations_and_task_parent_integrity.php');
    $migration->down();

    expect(strtolower((string) sqliteColumnType('passkeys', 'user_id')))->toBe('integer')
        ->and(strtolower((string) sqliteColumnType('notifications', 'notifiable_id')))->toBe('integer')
        ->and(strtolower((string) sqliteColumnType('personal_access_tokens', 'tokenable_id')))->toBe('integer');

    $migration->up();

    expect(DB::table('passkeys')->where('credential_id', $passkeyCredentialId)->value('user_id'))->toBe($user->id)
        ->and(DB::table('notifications')->where('id', $notificationId)->value('notifiable_id'))->toBe($user->id)
        ->and(DB::table('personal_access_tokens')->where('token', $token)->value('tokenable_id'))->toBe($user->id)
        ->and(strtolower((string) sqliteColumnType('passkeys', 'user_id')))->toBe('varchar')
        ->and(strtolower((string) sqliteColumnType('notifications', 'notifiable_id')))->toBe('varchar')
        ->and(strtolower((string) sqliteColumnType('personal_access_tokens', 'tokenable_id')))->toBe('varchar')
        ->and(DB::table('sqlite_schema')->where('type', 'index')->where('name', 'passkeys_credential_id_unique')->exists())->toBeTrue()
        ->and(DB::table('sqlite_schema')->where('type', 'index')->where('name', 'notifications_notifiable_type_notifiable_id_index')->exists())->toBeTrue()
        ->and(DB::table('sqlite_schema')->where('type', 'index')->where('name', 'personal_access_tokens_tokenable_type_tokenable_id_index')->exists())->toBeTrue()
        ->and(DB::table('sqlite_schema')->where('type', 'trigger')->where('name', 'todos_task_definitions_insert')->exists())->toBeTrue()
        ->and(DB::table('sqlite_schema')->where('type', 'trigger')->where('name', 'todos_task_definitions_update')->exists())->toBeTrue()
        ->and(DB::select('SELECT * FROM pragma_foreign_key_check'))->toBe([]);
});

test('corrective migration rejects invalid populated parent links before changing columns', function () {
    $firstWorkspace = Workspace::factory()->create();
    $secondWorkspace = Workspace::factory()->create();
    $child = Todo::factory()->for($firstWorkspace)->create();
    $foreignParent = Todo::factory()->for($secondWorkspace)->create();
    $migration = require database_path('migrations/2026_07_22_154915_correct_uuid_relations_and_task_parent_integrity.php');

    $migration->down();
    DB::table('todos')->where('id', $child->id)->update(['parent_id' => $foreignParent->id]);

    expect(fn () => $migration->up())
        ->toThrow(RuntimeException::class, 'contain invalid existing data')
        ->and(strtolower((string) sqliteColumnType('passkeys', 'user_id')))->toBe('integer')
        ->and(strtolower((string) sqliteColumnType('notifications', 'notifiable_id')))->toBe('integer')
        ->and(strtolower((string) sqliteColumnType('personal_access_tokens', 'tokenable_id')))->toBe('integer')
        ->and(collect(sqliteForeignKeys('todos'))->contains('from', 'parent_id'))->toBeFalse();
});
