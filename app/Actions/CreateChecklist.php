<?php

namespace App\Actions;

use App\Models\Checklist;
use App\Models\Todo;

class CreateChecklist
{
    public function handle(Todo $todo, string $name): Checklist
    {
        $maxPosition = $todo->checklists()->max('position') ?? 0;

        return $todo->checklists()->create([
            'name' => $name,
            'position' => $maxPosition + 1,
        ]);
    }
}
