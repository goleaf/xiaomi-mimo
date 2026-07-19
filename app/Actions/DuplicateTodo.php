<?php

namespace App\Actions;

use App\Enums\TodoStatus;
use App\Models\Todo;

class DuplicateTodo
{
    public function handle(Todo $todo): Todo
    {
        $newTodo = $todo->replicate();
        $newTodo->title = $todo->title.' (Copy)';
        $newTodo->status = TodoStatus::Pending;
        $newTodo->completed_at = null;
        $newTodo->is_pinned = false;
        $newTodo->is_favorite = false;
        $newTodo->is_archived = false;
        $newTodo->position = $todo->workspace->todos()
            ->where('project_id', $todo->project_id)
            ->max('position') + 1;
        $newTodo->save();

        $newTodo->labels()->sync($todo->labels()->pluck('labels.id'));
        $newTodo->tags()->sync($todo->tags()->pluck('tags.id'));

        return $newTodo->load(['project', 'assignee', 'labels', 'tags']);
    }
}
