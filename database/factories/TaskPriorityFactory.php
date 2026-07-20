<?php

namespace Database\Factories;

use App\Models\TaskPriority;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<TaskPriority> */
class TaskPriorityFactory extends Factory
{
    protected $model = TaskPriority::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $name = fake()->unique()->word().' '.fake()->word();

        return [
            'workspace_id' => WorkspaceFactory::new(),
            'key' => Str::slug($name, '_').'_'.Str::lower(Str::random(5)),
            'name' => Str::headline($name),
            'translation_key' => null,
            'color' => fake()->hexColor(),
            'position' => fake()->numberBetween(5, 40),
            'is_default' => false,
            'is_archived' => false,
        ];
    }
}
