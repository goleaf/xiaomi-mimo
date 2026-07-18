<?php

namespace Database\Factories;


use App\Enums\TodoPriority;
use App\Enums\TodoStatus;
use App\Models\Todo;
use Database\Factories\WorkspaceFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Todo>
 */
class TodoFactory extends Factory
{
    protected $model = Todo::class;

    public function definition(): array
    {
        return [
            'project_id' => null,
            'workspace_id' => WorkspaceFactory::new(),
            'assigned_to' => null,
            'parent_id' => null,
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'status' => fake()->randomElement(TodoStatus::cases()),
            'priority' => fake()->randomElement(TodoPriority::cases()),
            'due_date' => fake()->optional(0.6)->dateTimeBetween('-1 week', '+1 month'),
            'start_date' => fake()->optional(0.3)->dateTimeBetween('-1 week', 'now'),
            'estimated_time' => fake()->optional(0.4)->numberBetween(15, 480),
            'spent_time' => fake()->optional(0.3)->numberBetween(5, 300),
            'is_pinned' => fake()->boolean(10),
            'is_favorite' => fake()->boolean(15),
            'is_archived' => false,
            'is_recurring' => false,
            'recurring_rule' => null,
            'position' => fake()->numberBetween(0, 1000),
            'completed_at' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TodoStatus::Completed,
            'completed_at' => now(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TodoStatus::Pending,
            'completed_at' => null,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TodoStatus::InProgress,
            'completed_at' => null,
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => now()->subDay(),
            'status' => TodoStatus::Pending,
            'completed_at' => null,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_archived' => true,
        ]);
    }
}
