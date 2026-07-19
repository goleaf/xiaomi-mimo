<?php

namespace Database\Factories;

use App\Models\Attachment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attachment>
 */
class AttachmentFactory extends Factory
{
    protected $model = Attachment::class;

    public function definition(): array
    {
        $filename = fake()->word().'.'.fake()->fileExtension();

        return [
            'todo_id' => TodoFactory::new(),
            'user_id' => UserFactory::new(),
            'filename' => $filename,
            'path' => 'attachments/'.$filename,
            'mime_type' => fake()->mimeType(),
            'size' => fake()->numberBetween(1024, 10240000),
        ];
    }
}
