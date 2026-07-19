<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    protected $model = ActivityLog::class;

    public function definition(): array
    {
        return [
            'user_id' => UserFactory::new(),
            'workspace_id' => null,
            'subject_type' => 'App\\Models\\Todo',
            'subject_id' => fake()->uuid(),
            'event' => fake()->randomElement(['created', 'updated', 'completed', 'deleted']),
            'properties' => null,
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn () => ['user_id' => $user->id]);
    }

    public function forWorkspace(Workspace $workspace): static
    {
        return $this->state(fn () => ['workspace_id' => $workspace->id]);
    }

    public function forSubject(Model $subject): static
    {
        return $this->state(fn () => [
            'subject_type' => get_class($subject),
            'subject_id' => (string) $subject->getKey(),
        ]);
    }

    public function withEvent(string $event): static
    {
        return $this->state(fn () => ['event' => $event]);
    }

    /** @param array<string, mixed> $properties */
    public function withProperties(array $properties): static
    {
        return $this->state(fn () => ['properties' => $properties]);
    }
}
