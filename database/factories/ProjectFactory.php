<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\WorkspaceFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'workspace_id' => WorkspaceFactory::new(),
            'name' => fake()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'color' => '#'.fake()->hexColor(),
            'icon' => fake()->randomElement(['folder', 'briefcase', 'code', 'design', 'book', 'star']),
            'is_archived' => false,
            'position' => fake()->numberBetween(0, 100),
        ];
    }
}
