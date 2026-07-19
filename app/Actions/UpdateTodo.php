<?php

namespace App\Actions;

use App\Models\Todo;

class UpdateTodo
{
    public function handle(Todo $todo, array $data): Todo
    {
        $fillable = collect($data)->only([
            'project_id', 'assigned_to', 'title', 'description', 'status',
            'priority', 'due_date', 'start_date', 'estimated_time', 'spent_time',
        ])->toArray();

        $todo->update($fillable);

        if (array_key_exists('label_ids', $data)) {
            $todo->labels()->sync($data['label_ids'] ?? []);
        }

        if (array_key_exists('tag_ids', $data)) {
            $todo->tags()->sync($data['tag_ids'] ?? []);
        }

        return $todo->fresh(['project', 'assignee', 'labels', 'tags']);
    }
}
