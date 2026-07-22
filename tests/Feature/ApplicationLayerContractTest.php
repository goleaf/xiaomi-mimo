<?php

use App\Actions\LogActivity;
use App\Enums\TodoStatus;
use App\Models\Todo;
use App\Models\User;
use App\Models\Workspace;
use App\Services\BackupService;
use App\Services\TodoSortService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

test('backup service does not expose legacy filename-based snapshots', function () {
    $directory = sys_get_temp_dir().'/xiaomi-mimo-backup-contract-'.Str::uuid();
    $backupDirectory = $directory.'/backups';
    $databasePath = $directory.'/database.sqlite';

    try {
        File::ensureDirectoryExists($backupDirectory);
        File::put($databasePath, '');
        File::put($backupDirectory.'/backup_2026-07-19_185715.sqlite', 'sqlite');

        expect((new BackupService($databasePath, $backupDirectory))->listBackups())->toBe([]);
    } finally {
        File::deleteDirectory($directory);
    }
});

test('todo sorting normalizes unsupported directions to ascending', function () {
    $workspace = Workspace::factory()->create();
    Todo::factory()->for($workspace)->create(['title' => 'Zulu']);
    Todo::factory()->for($workspace)->create(['title' => 'Alpha']);

    $titles = (new TodoSortService)
        ->apply(Todo::query()->whereBelongsTo($workspace), 'title', 'sideways')
        ->pluck('title')
        ->all();

    expect($titles)->toBe(['Alpha', 'Zulu']);
});

test('activity logging reads generic model keys without dynamic properties', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->for($user, 'owner')->create();
    $todo = Todo::factory()->for($workspace)->create();

    $activity = (new LogActivity)->handle(
        $todo,
        'updated',
        $user->id,
        properties: ['field' => 'title'],
    );

    expect($activity->subject_id)
        ->toBe($todo->id)
        ->and($activity->workspace_id)->toBe($workspace->id)
        ->and($activity->properties)->toBe(['field' => 'title']);
});

test('recurring task command creates a correctly typed pending task', function () {
    $workspace = Workspace::factory()->create();
    $todo = Todo::factory()->for($workspace)->create([
        'status' => TodoStatus::Completed,
        'is_recurring' => true,
        'recurring_rule' => 'FREQ=DAILY;INTERVAL=1',
        'completed_at' => now(),
    ]);

    $this->artisan('tasks:recurring')->assertSuccessful();

    expect($todo->refresh()->status)
        ->toBe(TodoStatus::Completed)
        ->and($todo->recurring_rule)->toBe('FREQ=DAILY')
        ->and(Todo::whereBelongsTo($workspace)->count())->toBe(2)
        ->and(Todo::whereBelongsTo($workspace)->whereKeyNot($todo->id)->firstOrFail()->status)
        ->toBe(TodoStatus::Pending);
});
