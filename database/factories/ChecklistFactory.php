<?php

namespace Database\Factories;

use App\Models\Checklist;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChecklistFactory extends Factory
{
    protected $model = Checklist::class;

    public function definition(): array
    {
        return [
            'todo_id' => TodoFactory::new(),
            'name' => fake()->words(3, true),
            'position' => fake()->numberBetween(0, 100),
        ];
    }
}
