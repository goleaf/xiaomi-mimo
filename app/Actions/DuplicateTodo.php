<?php

namespace App\Actions;

use App\Models\Todo;
use Illuminate\Support\Facades\DB;

class DuplicateTodo
{
    public function __construct(private TransitionTodoDefinitions $transition) {}

    public function handle(Todo $todo): Todo
    {
        return DB::transaction(function () use ($todo): Todo {
            $todo->loadMissing(['workspace', 'statusDefinition', 'priorityDefinition']);
            $newTodo = $todo->replicate();
            $newTodo->title = $todo->title.' (Copy)';
            $newTodo->fill($this->transition->attributes($todo->workspace, [
                'priority_id' => $todo->priority_id,
            ]));
            $newTodo->is_pinned = false;
            $newTodo->is_favorite = false;
            $newTodo->is_archived = false;
            $newTodo->position = ((int) $todo->workspace->todos()
                ->where('project_id', $todo->project_id)
                ->max('position')) + 1;
            $newTodo->save();

            $newTodo->labels()->sync($todo->labels()->pluck('labels.id'));
            $newTodo->tags()->sync($todo->tags()->pluck('tags.id'));

            return $newTodo->load([
                'project', 'assignee', 'labels', 'tags',
                'statusDefinition', 'priorityDefinition',
            ]);
        }, 5);
    }
}
