<?php

namespace App\Actions;

use App\Models\Project;
use App\Models\Workspace;

class CreateProject
{
    /** @param array{name: string, description?: string|null, color?: string, icon?: string} $data */
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
