<?php

namespace App\Actions;

use App\Models\Workspace;

class UpdateWorkspace
{
    public function handle(Workspace $workspace, array $data): Workspace
    {
        $workspace->update([
            'name' => $data['name'] ?? $workspace->name,
            'description' => $data['description'] ?? $workspace->description,
        ]);

        return $workspace->fresh();
    }
}
