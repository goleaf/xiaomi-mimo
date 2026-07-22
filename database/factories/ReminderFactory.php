<?php

namespace Database\Factories;

use App\Enums\ReminderStatus;
use App\Enums\ReminderType;
use App\Models\Reminder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reminder>
 */
class ReminderFactory extends Factory
{
    protected $model = Reminder::class;

    public function configure(): static
    {
        return $this->afterMaking(function (Reminder $reminder): void {
            if ($reminder->is_sent && $reminder->status === ReminderStatus::Pending) {
                $reminder->status = ReminderStatus::Delivered;
                $reminder->delivered_at ??= now();
            }
        });
    }

    public function definition(): array
    {
        return [
            'todo_id' => TodoFactory::new(),
            'user_id' => UserFactory::new(),
            'reminded_at' => fake()->dateTimeBetween('now', '+1 week'),
            'is_sent' => false,
            'type' => fake()->randomElement(ReminderType::cases()),
            'status' => ReminderStatus::Pending,
            'claim_token' => null,
            'attempts' => 0,
        ];
    }
}
