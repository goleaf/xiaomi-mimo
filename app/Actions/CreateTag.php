<?php

namespace App\Actions;

use App\Models\Tag;
use App\Models\Workspace;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

class CreateTag
{
    public function handle(Workspace $workspace, string $name): Tag
    {
        try {
            $tag = $workspace->tags()->create(['name' => $name]);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'name' => [__('validation.unique', ['attribute' => 'name'])],
            ]);
        }

        return $tag->setRelation('workspace', $workspace)->loadCount('todos');
    }
}
