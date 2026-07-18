<?php

namespace App\Actions;

use App\Models\Project;
use App\Models\Workspace;

class CreateProject
{
    public function handle(Workspace $workspace, array $data): Project
    {
        $maxPosition = $workspace->projects()->max('position') ?? 0;

        return $workspace->projects()->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'color' => $data['color'] ?? '#6366f1',
            'icon' => $data['icon'] ?? 'folder',
            'position' => $maxPosition + 1,
        ]);
    }
}
