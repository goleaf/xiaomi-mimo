<?php

namespace App\Actions;

use App\Models\Tag;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

class UpdateTag
{
    public function handle(Tag $tag, string $name): Tag
    {
        try {
            $tag->update(['name' => $name]);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'name' => [__('validation.unique', ['attribute' => 'name'])],
            ]);
        }

        return $tag->refresh()->load(['workspace'])->loadCount('todos');
    }
}
