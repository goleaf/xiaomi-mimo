<?php

namespace App\Actions;

use App\Models\Project;

class UpdateProject
{
    public function handle(Project $project, array $data): Project
    {
        $project->update(collect($data)->only(['name', 'description', 'color', 'icon'])->toArray());

        return $project->fresh();
    }
}
