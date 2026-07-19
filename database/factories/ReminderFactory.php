<?php

namespace Database\Factories;

use App\Enums\ReminderType;
use App\Models\Reminder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reminder>
 */
class ReminderFactory extends Factory
{
    protected $model = Reminder::class;

    public function definition(): array
    {
        return [
            'todo_id' => TodoFactory::new(),
            'user_id' => UserFactory::new(),
            'reminded_at' => fake()->dateTimeBetween('now', '+1 week'),
            'is_sent' => false,
            'type' => fake()->randomElement(ReminderType::cases()),
        ];
    }
}
