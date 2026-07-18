<?php

namespace App\Actions;

use App\Models\Project;

class DuplicateProject
{
    public function handle(Project $project): Project
    {
        $newProject = $project->replicate();
        $newProject->name = $project->name.' (Copy)';
        $newProject->is_archived = false;
        $newProject->position = $project->workspace->projects()->max('position') + 1;
        $newProject->save();

        foreach ($project->todos as $todo) {
            $newTodo = $todo->replicate();
            $newTodo->project_id = $newProject->id;
            $newTodo->workspace_id = $newProject->workspace_id;
            $newTodo->save();
        }

        return $newProject->load('todos');
    }
}
