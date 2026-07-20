<?php

namespace App\Actions;

use App\Models\Label;
use App\Models\Workspace;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

class CreateLabel
{
    public function handle(Workspace $workspace, string $name, string $color = '#6366f1'): Label
    {
        try {
            $label = $workspace->labels()->create([
                'name' => $name,
                'color' => $color,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'name' => [__('validation.unique', ['attribute' => 'name'])],
            ]);
        }

        return $label->setRelation('workspace', $workspace)->loadCount('todos');
    }
}
