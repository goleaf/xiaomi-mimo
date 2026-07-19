<?php

namespace App\Actions;

use App\Models\Project;

class UpdateProject
{
    /** @param array{name?: string, description?: string|null, color?: string, icon?: string} $data */
    public function handle(Project $project, array $data): Project
    {
        $project->update(collect($data)->only(['name', 'description', 'color', 'icon'])->toArray());

        return $project->fresh();
    }
}
