<?php

namespace App\Actions;

use App\Models\Project;

class ArchiveProject
{
    public function handle(Project $project): Project
    {
        $project->update(['is_archived' => true]);

        return $project->fresh();
    }
}
