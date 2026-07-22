<?php

use App\Enums\ApiTokenAbility;
use App\Enums\WorkspaceRole;
use App\Models\Attachment;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Reminder;
use App\Models\Todo;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

/** @return array{user: User, workspace: Workspace, token: string} */
function createVersionedApiActor(?array $abilities = null): array
{
    $user = User::factory()->create();
    $workspace = Workspace::factory()->for($user, 'owner')->create();
    WorkspaceMember::factory()->for($workspace)->for($user)->create(['role' => WorkspaceRole::Owner]);
    $token = $user->createToken('v1-contract', $abilities ?? ApiTokenAbility::values())->plainTextToken;

    return compact('user', 'workspace', 'token');
}

test('canonical API routes are versioned named and parent scoped', function () {
    expect(route('api.v1.auth.login', absolute: false))->toBe('/api/v1/auth/login')
        ->and(route('api.v1.workspaces.index', absolute: false))->toBe('/api/v1/workspaces')
        ->and(route('api.v1.projects.show', [
            'workspace' => 'workspace-id',
            'project' => 'project-id',
        ], absolute: false))->toBe('/api/v1/workspaces/workspace-id/projects/project-id')
        ->and(route('api.v1.comments.update', [
            'todo' => 'task-id',
            'comment' => 'comment-id',
        ], absolute: false))->toBe('/api/v1/tasks/task-id/comments/comment-id')
        ->and(Route::getRoutes()->getByName('api.legacy.auth.login'))->not->toBeNull();
});

test('v1 success responses have a stable envelope and request correlation', function () {
    ['token' => $token] = createVersionedApiActor();
    $requestId = (string) Str::uuid();

    $response = $this->withToken($token)
        ->withHeader('X-Request-Id', $requestId)
        ->getJson(route('api.v1.workspaces.index', absolute: false));

    $response->assertOk()
        ->assertHeader('X-API-Version', '1')
        ->assertHeader('X-Request-Id', $requestId)
        ->assertJsonPath('meta.request_id', $requestId)
        ->assertJsonStructure(['data' => [['id', 'name']], 'meta' => ['request_id']]);
});

test('v1 replaces an invalid incoming request identifier', function () {
    ['token' => $token] = createVersionedApiActor();

    $response = $this->withToken($token)
        ->withHeader('X-Request-Id', 'not-a-uuid')
        ->getJson(route('api.v1.user.show', absolute: false));
    $requestId = (string) $response->headers->get('X-Request-Id');

    $response->assertOk()
        ->assertJsonPath('meta.request_id', $requestId)
        ->assertJsonStructure(['data' => ['id', 'name', 'email']]);

    expect(Str::isUuid($requestId))->toBeTrue();
});

test('v1 errors use stable codes details localization and request correlation', function () {
    $unauthenticated = $this->getJson(route('api.v1.user.show', absolute: false));
    $unauthenticatedId = (string) $unauthenticated->headers->get('X-Request-Id');

    $unauthenticated->assertUnauthorized()
        ->assertHeader('X-API-Version', '1')
        ->assertJsonPath('error.code', 'unauthenticated')
        ->assertJsonPath('meta.request_id', $unauthenticatedId);

    $validation = $this->withHeader('Accept-Language', 'lt')
        ->postJson(route('api.v1.auth.login', absolute: false), []);

    $validation->assertUnprocessable()
        ->assertJsonPath('error.code', 'validation_failed')
        ->assertJsonPath('error.message', 'Pateikti duomenys neteisingi.')
        ->assertJsonStructure(['error' => ['code', 'message', 'details' => ['email', 'password', 'device_name']], 'meta' => ['request_id']]);
});

test('v1 nested bindings hide a project paired with another workspace', function () {
    ['token' => $token, 'workspace' => $workspace] = createVersionedApiActor();
    $foreignWorkspace = Workspace::factory()->create();
    $foreignProject = Project::factory()->for($foreignWorkspace)->create();

    $this->withToken($token)
        ->getJson(route('api.v1.projects.show', [
            'workspace' => $workspace,
            'project' => $foreignProject,
        ], absolute: false))
        ->assertNotFound()
        ->assertJsonPath('error.code', 'not_found');
});

test('v1 scoped task route resolves an authorized task and normalizes its payload', function () {
    ['token' => $token, 'workspace' => $workspace] = createVersionedApiActor();
    $todo = Todo::factory()->for($workspace)->create();

    $this->withToken($token)
        ->getJson(route('api.v1.tasks.show', [$workspace, $todo], false))
        ->assertOk()
        ->assertJsonPath('data.id', $todo->id)
        ->assertJsonStructure(['data' => ['id', 'title'], 'meta' => ['request_id']]);
});

test('v1 scopes task children to every parent in the canonical URL', function () {
    ['user' => $user, 'token' => $token, 'workspace' => $workspace] = createVersionedApiActor();
    $firstTodo = Todo::factory()->for($workspace)->create();
    $secondTodo = Todo::factory()->for($workspace)->create();
    $foreignComment = Comment::factory()->for($secondTodo)->for($user)->create();
    $foreignChecklist = Checklist::factory()->for($secondTodo)->create();
    $firstChecklist = Checklist::factory()->for($firstTodo)->create();
    $foreignItem = ChecklistItem::factory()->for($foreignChecklist)->create();
    $foreignReminder = Reminder::factory()->for($secondTodo)->for($user)->create();
    $foreignAttachment = Attachment::factory()->for($secondTodo)->for($user)->create();

    $this->withToken($token)
        ->putJson(route('api.v1.comments.update', [$firstTodo, $foreignComment], false), ['body' => 'Blocked'])
        ->assertNotFound();
    $this->withToken($token)
        ->postJson(route('api.v1.checklist-items.store', [$firstTodo, $foreignChecklist], false), ['content' => 'Blocked'])
        ->assertNotFound();
    $this->withToken($token)
        ->patchJson(route('api.v1.checklist-items.toggle', [$firstTodo, $firstChecklist, $foreignItem], false))
        ->assertNotFound();
    $this->withToken($token)
        ->deleteJson(route('api.v1.reminders.destroy', [$firstTodo, $foreignReminder], false))
        ->assertNotFound();
    $this->withToken($token)
        ->deleteJson(route('api.v1.attachments.destroy', [$firstTodo, $foreignAttachment], false))
        ->assertNotFound();

    expect($foreignComment->fresh()->body)->not->toBe('Blocked')
        ->and($foreignChecklist->fresh())->not->toBeNull()
        ->and($foreignItem->fresh())->not->toBeNull()
        ->and($foreignReminder->fresh())->not->toBeNull()
        ->and($foreignAttachment->fresh())->not->toBeNull();
});

test('v1 ability failures use the forbidden contract', function () {
    ['token' => $token] = createVersionedApiActor([ApiTokenAbility::WorkspacesRead->value]);

    $this->withToken($token)
        ->postJson(route('api.v1.workspaces.store', absolute: false), ['name' => 'Blocked'])
        ->assertForbidden()
        ->assertJsonPath('error.code', 'forbidden');
});

test('v1 authentication issues only documented abilities and no-content responses keep headers', function () {
    $user = User::factory()->create(['password' => bcrypt('password123')]);

    $login = $this->postJson(route('api.v1.auth.login', absolute: false), [
        'email' => $user->email,
        'password' => 'password123',
        'device_name' => 'Contract device',
    ]);
    $plainTextToken = (string) $login->json('data.token');
    $storedToken = PersonalAccessToken::findToken($plainTextToken);

    $login->assertOk()
        ->assertJsonPath('data.abilities', ApiTokenAbility::values())
        ->assertJsonStructure(['data' => ['token', 'abilities', 'user' => ['id', 'name', 'email']]]);
    expect($storedToken?->abilities)->toBe(ApiTokenAbility::values());

    $this->withToken($plainTextToken)
        ->postJson(route('api.v1.auth.logout', absolute: false))
        ->assertNoContent()
        ->assertHeader('X-API-Version', '1')
        ->assertHeader('X-Request-Id');
});

test('v1 API login is rate limited by normalized credential and IP', function () {
    $user = User::factory()->create();
    $payload = [
        'email' => Str::upper($user->email),
        'password' => 'wrong-password',
        'device_name' => 'Rate test',
    ];

    foreach (range(1, 5) as $attempt) {
        $this->postJson(route('api.v1.auth.login', absolute: false), $payload)
            ->assertUnprocessable();
    }

    $this->postJson(route('api.v1.auth.login', absolute: false), $payload)
        ->assertTooManyRequests()
        ->assertJsonPath('error.code', 'rate_limited');
});

test('v1 API registration has its own bounded credential limiter', function () {
    $payload = [
        'name' => 'Rate Limited',
        'email' => 'rate@example.com',
        'password' => 'short',
        'password_confirmation' => 'short',
        'device_name' => 'Rate test',
    ];

    foreach (range(1, 3) as $attempt) {
        $this->postJson(route('api.v1.auth.register', absolute: false), $payload)
            ->assertUnprocessable();
    }

    $this->postJson(route('api.v1.auth.register', absolute: false), $payload)
        ->assertTooManyRequests()
        ->assertJsonPath('error.code', 'rate_limited');
});

test('v1 localization falls back to English for every request', function () {
    $this->withHeader('Accept-Language', 'lt')
        ->getJson(route('api.v1.user.show', absolute: false))
        ->assertJsonPath('error.message', 'Būtina autentifikuotis.');

    $this->withHeader('Accept-Language', 'fr')
        ->getJson(route('api.v1.user.show', absolute: false))
        ->assertJsonPath('error.message', 'Authentication is required.');
});

test('legacy API keeps its payload and advertises the successor version', function () {
    ['token' => $token] = createVersionedApiActor();

    $response = $this->withToken($token)->getJson('/api/workspaces');

    $response->assertOk()
        ->assertHeader('Deprecation', 'true')
        ->assertHeader('X-API-Version', 'legacy')
        ->assertHeader('Link', '<'.url('/api/v1/workspaces').'>; rel="successor-version"')
        ->assertJsonStructure(['data' => [['id', 'name']]])
        ->assertJsonMissingPath('meta.request_id');
});
