<?php

use App\Enums\TodoPriority;
use App\Enums\TodoStatus;
use App\Enums\WorkspaceRole;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Todo;
use App\Models\User;
use App\Models\UserPreference;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * @return array{0: User, 1: Workspace}
 */
function createWarmPrecisionContext(string $language = 'en'): array
{
    $user = User::factory()->create();
    $workspace = Workspace::factory()->for($user, 'owner')->create();

    WorkspaceMember::create([
        'workspace_id' => $workspace->id,
        'user_id' => $user->id,
        'role' => WorkspaceRole::Owner,
    ]);
    UserPreference::create([
        'user_id' => $user->id,
        'language' => $language,
        'timezone' => 'Europe/Vilnius',
    ]);

    return [$user, $workspace];
}

test('workspace pages expose semantic copy in the preferred language', function (string $routeName, string $component) {
    [$user, $workspace] = createWarmPrecisionContext('ru');

    $this->actingAs($user)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->get(route($routeName))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component($component)
            ->where('workspaceUi.projects.title', 'Проекты')
            ->where('workspaceUi.calendar.title', 'Календарь')
            ->where('workspaceUi.notifications.title', 'Уведомления')
            ->where('workspaceUi.activity.title', 'Активность')
            ->where('preferences.timezone', 'Europe/Vilnius'));
})->with([
    'activity' => ['activity', 'activity/Index'],
    'notifications' => ['notifications.index', 'notifications/Index'],
    'calendar' => ['calendar', 'calendar/Index'],
    'projects' => ['projects', 'projects/Index'],
]);

test('unsupported preferences use the English workspace copy fallback', function () {
    [$user, $workspace] = createWarmPrecisionContext('de');

    $this->actingAs($user)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->get(route('calendar'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('workspaceUi.calendar.title', 'Calendar')
            ->where('workspaceUi.projects.new_project', 'New project'));
});

test('calendar returns normalized dated tasks only from the selected workspace', function () {
    [$user, $workspace] = createWarmPrecisionContext();
    $project = Project::factory()->for($workspace)->create(['name' => 'Current project']);
    $todo = Todo::factory()->for($workspace)->for($project)->create([
        'title' => 'Current workspace deadline',
        'due_date' => now()->addDay(),
        'status' => TodoStatus::Pending,
        'priority' => TodoPriority::High,
    ]);

    $foreignWorkspace = Workspace::factory()->create();
    Todo::factory()->for($foreignWorkspace)->create([
        'title' => 'Foreign workspace deadline',
        'due_date' => now()->addDay(),
    ]);

    $this->actingAs($user)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->get(route('calendar'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('calendar/Index')
            ->has('todos', 1)
            ->where('todos.0.id', $todo->id)
            ->where('todos.0.due_date', now()->addDay()->toDateString())
            ->where('todos.0.project.name', 'Current project'));
});

test('project collection includes workspace scoped task totals', function () {
    [$user, $workspace] = createWarmPrecisionContext();
    $project = Project::factory()->for($workspace)->create(['name' => 'Launch']);
    Todo::factory()->count(2)->for($workspace)->for($project)->create();

    $foreignProject = Project::factory()->create(['name' => 'Foreign']);
    Todo::factory()->for($foreignProject->workspace)->for($foreignProject)->create();

    $this->actingAs($user)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->get(route('projects'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('projects/Index')
            ->where('workspace.id', $workspace->id)
            ->has('projects.data', 1)
            ->where('projects.data.0.id', $project->id)
            ->where('projects.data.0.todos_count', 2));
});

test('activity timeline excludes events from other workspaces', function () {
    [$user, $workspace] = createWarmPrecisionContext();
    $ownActivity = ActivityLog::create([
        'user_id' => $user->id,
        'workspace_id' => $workspace->id,
        'subject_type' => Todo::class,
        'subject_id' => Str::uuid()->toString(),
        'event' => 'created',
    ]);

    ActivityLog::create([
        'user_id' => $user->id,
        'workspace_id' => Workspace::factory()->create()->id,
        'subject_type' => Todo::class,
        'subject_id' => Str::uuid()->toString(),
        'event' => 'updated',
    ]);

    $this->actingAs($user)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->get(route('activity'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('activity/Index')
            ->has('activities.data', 1)
            ->where('activities.data.0.id', $ownActivity->id));
});

test('notification actions cannot mark another users notification as read', function () {
    [$user] = createWarmPrecisionContext();
    $foreignUser = User::factory()->create();
    $ownNotificationId = Str::uuid()->toString();
    $foreignNotificationId = Str::uuid()->toString();

    DB::table('notifications')->insert([
        [
            'id' => $ownNotificationId,
            'type' => 'App\\Notifications\\GenericNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode(['title' => 'Own notification']),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'id' => $foreignNotificationId,
            'type' => 'App\\Notifications\\GenericNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $foreignUser->id,
            'data' => json_encode(['title' => 'Foreign notification']),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $this->actingAs($user)
        ->post(route('notifications.markRead', ['id' => $foreignNotificationId]))
        ->assertRedirect();

    expect(DB::table('notifications')->where('id', $foreignNotificationId)->value('read_at'))->toBeNull();

    $this->post(route('notifications.markRead', ['id' => $ownNotificationId]))
        ->assertRedirect();

    expect(DB::table('notifications')->where('id', $ownNotificationId)->value('read_at'))->not->toBeNull();

    $secondOwnNotificationId = Str::uuid()->toString();
    DB::table('notifications')->insert([
        'id' => $secondOwnNotificationId,
        'type' => 'App\\Notifications\\GenericNotification',
        'notifiable_type' => User::class,
        'notifiable_id' => $user->id,
        'data' => json_encode(['title' => 'Second own notification']),
        'read_at' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->post(route('notifications.markAllRead'))->assertRedirect();

    expect(DB::table('notifications')->where('id', $secondOwnNotificationId)->value('read_at'))->not->toBeNull()
        ->and(DB::table('notifications')->where('id', $foreignNotificationId)->value('read_at'))->toBeNull();
});
