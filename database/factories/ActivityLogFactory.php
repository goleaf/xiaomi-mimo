<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\UserFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

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
}
