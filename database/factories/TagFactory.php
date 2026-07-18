<?php

namespace Database\Factories;


use App\Models\Tag;
use Database\Factories\WorkspaceFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

class TagFactory extends Factory
{
    protected $model = Tag::class;

    public function definition(): array
    {
        return [
            'workspace_id' => WorkspaceFactory::new(),
            'name' => fake()->unique()->word(),
        ];
    }
}
