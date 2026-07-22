<?php

use App\Actions\CompleteTodo;
use App\Actions\TransitionTodoDefinitions;
use App\Enums\ActivityEvent;
use App\Enums\TodoStatus;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\Label;
use App\Models\Tag;
use App\Models\Todo;
use App\Models\User;
use App\Models\UserPreference;
use App\Models\Workspace;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function (): void {
    CarbonImmutable::setTestNow();
});

function recurringTodo(array $attributes = []): Todo
{
    $user = User::factory()->create();
    UserPreference::factory()->for($user)->create(['timezone' => 'America/New_York']);
    $workspace = Workspace::factory()->for($user, 'owner')->create();

    return Todo::factory()->for($workspace)->completed()->create([
        'assigned_to' => $user->id,
        'title' => 'Monthly close',
        'due_date' => '2026-01-31',
        'is_recurring' => true,
        'recurring_rule' => 'FREQ=MONTHLY',
        'completed_at' => '2026-02-02 12:00:00',
        ...$attributes,
    ]);
}

test('recurring processing creates one idempotent occurrence and copies only reusable relationships', function () {
    CarbonImmutable::setTestNow('2026-02-03 12:00:00');
    $source = recurringTodo();
    $label = Label::factory()->for($source->workspace)->create();
    $tag = Tag::factory()->for($source->workspace)->create();
    $source->labels()->attach($label);
    $source->tags()->attach($tag);
    $checklist = Checklist::factory()->for($source)->create(['name' => 'Close steps', 'position' => 4]);
    ChecklistItem::factory()->for($checklist)->create([
        'content' => 'Reconcile ledger',
        'is_checked' => true,
        'position' => 7,
    ]);

    $this->artisan('tasks:recurring')->assertSuccessful();
    $this->artisan('tasks:recurring')->assertSuccessful();

    $occurrence = Todo::query()->whereKeyNot($source->id)->sole();

    expect($source->refresh()->status)->toBe(TodoStatus::Completed)
        ->and($source->recurrence_generated_at)->not->toBeNull()
        ->and($occurrence->status)->toBe(TodoStatus::Pending)
        ->and($occurrence->completed_at)->toBeNull()
        ->and($occurrence->recurrence_series_id)->toBe($source->recurrence_series_id)
        ->and($occurrence->recurrence_sequence)->toBe(1)
        ->and($occurrence->recurrence_anchor_date?->toDateString())->toBe('2026-01-31')
        ->and($occurrence->recurrence_occurrence_date?->toDateString())->toBe('2026-02-28')
        ->and($occurrence->due_date?->toDateString())->toBe('2026-02-28')
        ->and($occurrence->labels()->pluck('labels.id')->all())->toBe([$label->id])
        ->and($occurrence->tags()->pluck('tags.id')->all())->toBe([$tag->id])
        ->and($occurrence->checklists()->sole()->name)->toBe('Close steps')
        ->and($occurrence->checklists()->sole()->items()->sole()->is_checked)->toBeFalse()
        ->and($occurrence->comments()->count())->toBe(0)
        ->and($occurrence->reminders()->count())->toBe(0)
        ->and($occurrence->attachments()->count())->toBe(0)
        ->and($occurrence->activityLogs()->where('event', ActivityEvent::RecurrenceGenerated->value)->count())->toBe(1)
        ->and(Todo::query()->count())->toBe(2);
});

test('completing a recurring task advances its series immediately', function () {
    CarbonImmutable::setTestNow('2026-02-03 12:00:00');
    $source = recurringTodo([
        'status' => TodoStatus::Pending,
        'completed_at' => null,
    ]);

    app(CompleteTodo::class)->handle($source);

    expect($source->refresh()->status)->toBe(TodoStatus::Completed)
        ->and($source->recurrence_generated_at)->not->toBeNull()
        ->and(Todo::query()->where('recurrence_sequence', 1)->sole()->status)
        ->toBe(TodoStatus::Pending);
});

test('monthly recurrence keeps the anchor day instead of permanently drifting', function () {
    CarbonImmutable::setTestNow('2026-04-01 12:00:00');
    $source = recurringTodo();

    $this->artisan('tasks:recurring')->assertSuccessful();
    $february = Todo::query()->whereKeyNot($source->id)->sole();
    $february->update([
        ...app(TransitionTodoDefinitions::class)->attributes(
            $february->workspace,
            ['status' => TodoStatus::Completed],
            $february,
        ),
    ]);

    $this->artisan('tasks:recurring')->assertSuccessful();

    $march = Todo::query()->where('recurrence_sequence', 2)->sole();

    expect($february->refresh()->due_date?->toDateString())->toBe('2026-02-28')
        ->and($march->due_date?->toDateString())->toBe('2026-03-31');
});

test('a recurrence without a due date uses the assignees local completion date across DST', function () {
    CarbonImmutable::setTestNow('2026-03-09 12:00:00');
    $source = recurringTodo([
        'due_date' => null,
        'recurring_rule' => 'FREQ=DAILY',
        'completed_at' => '2026-03-08 04:30:00',
    ]);

    $this->artisan('tasks:recurring')->assertSuccessful();

    $occurrence = Todo::query()->whereKeyNot($source->id)->sole();

    expect($source->refresh()->recurrence_anchor_date?->toDateString())->toBe('2026-03-07')
        ->and($occurrence->due_date?->toDateString())->toBe('2026-03-08');
});

test('recurrence catch up is bounded per command run', function () {
    CarbonImmutable::setTestNow('2026-02-03 12:00:00');

    recurringTodo(['title' => 'First', 'completed_at' => '2026-02-01 09:00:00']);
    recurringTodo(['title' => 'Second', 'completed_at' => '2026-02-01 10:00:00']);
    recurringTodo(['title' => 'Third', 'completed_at' => '2026-02-01 11:00:00']);

    $this->artisan('tasks:recurring', ['--limit' => 2])->assertSuccessful();

    expect(Todo::query()->whereNotNull('recurrence_generated_at')->count())->toBe(2)
        ->and(Todo::query()->where('recurrence_sequence', 1)->count())->toBe(2);
});
