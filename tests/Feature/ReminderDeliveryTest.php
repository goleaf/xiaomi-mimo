<?php

use App\Actions\ClaimDueReminders;
use App\Actions\DeliverClaimedReminder;
use App\Actions\FailReminderDelivery;
use App\Enums\ReminderStatus;
use App\Enums\ReminderType;
use App\Jobs\DeliverReminder;
use App\Models\Reminder;
use App\Models\Todo;
use App\Models\User;
use App\Models\UserPreference;
use App\Models\Workspace;
use App\Notifications\ReminderNotification;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

afterEach(function (): void {
    CarbonImmutable::setTestNow();
});

function reminderForDelivery(ReminderType $type = ReminderType::InApp, array $attributes = []): Reminder
{
    $user = User::factory()->create();
    UserPreference::factory()->for($user)->create();
    $workspace = Workspace::factory()->for($user, 'owner')->create();
    $todo = Todo::factory()->for($workspace)->create(['assigned_to' => $user->id]);

    return Reminder::factory()->for($todo)->for($user)->create([
        'type' => $type,
        'reminded_at' => now()->subMinute(),
        ...$attributes,
    ]);
}

test('the reminder command atomically claims due reminders and dispatches one unique job', function () {
    CarbonImmutable::setTestNow('2026-07-22 12:00:00');
    Queue::fake();
    $due = reminderForDelivery();
    $future = reminderForDelivery(attributes: ['reminded_at' => now()->addMinute()]);

    $this->artisan('reminders:send')->assertSuccessful();
    $this->artisan('reminders:send')->assertSuccessful();

    expect($due->refresh()->status)->toBe(ReminderStatus::Processing)
        ->and($due->claim_token)->not->toBeNull()
        ->and($due->attempts)->toBe(1)
        ->and($future->refresh()->status)->toBe(ReminderStatus::Pending);

    Queue::assertPushed(DeliverReminder::class, 1);
});

test('database reminder delivery is idempotent', function () {
    CarbonImmutable::setTestNow('2026-07-22 12:00:00');
    $reminder = reminderForDelivery();
    $claim = app(ClaimDueReminders::class)->handle(10)->sole();
    $job = new DeliverReminder($reminder->id, $claim->claim_token);

    $job->handle(app(DeliverClaimedReminder::class), app(FailReminderDelivery::class));
    $job->handle(app(DeliverClaimedReminder::class), app(FailReminderDelivery::class));

    expect($reminder->refresh()->status)->toBe(ReminderStatus::Delivered)
        ->and($reminder->is_sent)->toBeTrue()
        ->and($reminder->delivered_at)->not->toBeNull()
        ->and($reminder->user->notifications()->where('data->reminder_id', $reminder->id)->count())->toBe(1);
});

test('email reminder delivery honors preferences and records delivery', function () {
    CarbonImmutable::setTestNow('2026-07-22 12:00:00');
    Notification::fake();
    $reminder = reminderForDelivery(ReminderType::Email);
    $claim = app(ClaimDueReminders::class)->handle(10)->sole();

    (new DeliverReminder($reminder->id, $claim->claim_token))
        ->handle(app(DeliverClaimedReminder::class), app(FailReminderDelivery::class));

    Notification::assertSentTo($reminder->user, ReminderNotification::class);
    expect($reminder->refresh()->status)->toBe(ReminderStatus::Delivered);
});

test('a disabled reminder channel is cancelled without delivery', function () {
    CarbonImmutable::setTestNow('2026-07-22 12:00:00');
    $reminder = reminderForDelivery(ReminderType::Browser);
    $reminder->user->preferences()->update(['notification_browser' => false]);
    $claim = app(ClaimDueReminders::class)->handle(10)->sole();

    (new DeliverReminder($reminder->id, $claim->claim_token))
        ->handle(app(DeliverClaimedReminder::class), app(FailReminderDelivery::class));

    expect($reminder->refresh()->status)->toBe(ReminderStatus::Cancelled)
        ->and($reminder->cancelled_at)->not->toBeNull()
        ->and($reminder->user->notifications()->count())->toBe(0);
});

test('failed deliveries retain bounded retry metadata', function () {
    CarbonImmutable::setTestNow('2026-07-22 12:00:00');
    $reminder = reminderForDelivery();
    $claim = app(ClaimDueReminders::class)->handle(10)->sole();
    $delivery = Mockery::mock(DeliverClaimedReminder::class);
    $delivery->shouldReceive('handle')->once()->andThrow(new RuntimeException('Provider unavailable'));

    expect(fn () => (new DeliverReminder($reminder->id, $claim->claim_token))
        ->handle($delivery, app(FailReminderDelivery::class)))
        ->toThrow(RuntimeException::class, 'Provider unavailable');

    expect($reminder->refresh()->status)->toBe(ReminderStatus::Failed)
        ->and($reminder->last_error)->toBe('Provider unavailable')
        ->and($reminder->next_attempt_at?->toDateTimeString())->toBe('2026-07-22 12:01:00');
});

test('stale claims are reclaimed but terminal failures are not', function () {
    CarbonImmutable::setTestNow('2026-07-22 12:00:00');
    $stale = reminderForDelivery(attributes: [
        'status' => ReminderStatus::Processing,
        'claim_token' => (string) Str::uuid(),
        'claimed_at' => now()->subMinutes(11),
        'attempts' => 1,
    ]);
    $terminal = reminderForDelivery(attributes: [
        'status' => ReminderStatus::Failed,
        'attempts' => Reminder::MAX_ATTEMPTS,
        'failed_at' => now()->subMinute(),
        'next_attempt_at' => null,
    ]);

    $claimed = app(ClaimDueReminders::class)->handle(10);

    expect($claimed->pluck('id')->all())->toContain($stale->id)
        ->not->toContain($terminal->id)
        ->and($stale->refresh()->attempts)->toBe(2);
});
