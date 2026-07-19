<?php

namespace Database\Factories;

use App\Models\Comment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        return [
            'todo_id' => TodoFactory::new(),
            'user_id' => UserFactory::new(),
            'body' => fake()->paragraph(),
        ];
    }
}
