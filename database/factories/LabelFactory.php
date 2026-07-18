<?php

namespace Database\Factories;


use App\Models\Label;
use Database\Factories\WorkspaceFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

class LabelFactory extends Factory
{
    protected $model = Label::class;

    public function definition(): array
    {
        return [
            'workspace_id' => WorkspaceFactory::new(),
            'name' => fake()->unique()->word(),
            'color' => '#'.fake()->hexColor(),
        ];
    }
}
