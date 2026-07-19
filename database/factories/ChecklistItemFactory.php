<?php

namespace Database\Factories;

use App\Models\ChecklistItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChecklistItem>
 */
class ChecklistItemFactory extends Factory
{
    protected $model = ChecklistItem::class;

    public function definition(): array
    {
        return [
            'checklist_id' => ChecklistFactory::new(),
            'content' => fake()->sentence(3),
            'is_checked' => fake()->boolean(30),
            'position' => fake()->numberBetween(0, 100),
        ];
    }
}
