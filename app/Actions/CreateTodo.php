<?php

namespace App\Actions;

use App\Models\Todo;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

class CreateTodo
{
    public function __construct(private TransitionTodoDefinitions $transition) {}

    /**
     * @param  array{title: string, project_id?: string|null, assigned_to?: string|null, parent_id?: string|null, description?: string|null, status?: string, priority?: string, due_date?: string|null, start_date?: string|null, estimated_time?: int|null, label_ids?: list<string>, tag_ids?: list<string>}  $data
     */
    public function handle(Workspace $workspace, array $data, ?string $userId = null): Todo
    {
        return DB::transaction(function () use ($workspace, $data, $userId): Todo {
            $maxPosition = $workspace->todos()
                ->where('project_id', $data['project_id'] ?? null)
                ->max('position') ?? 0;

            $definitionAttributes = $this->transition->attributes($workspace, $data);
            $todo = $workspace->todos()->create([
                'project_id' => $data['project_id'] ?? null,
                'assigned_to' => $data['assigned_to'] ?? $userId,
                'parent_id' => $data['parent_id'] ?? null,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                ...$definitionAttributes,
                'due_date' => $data['due_date'] ?? null,
                'start_date' => $data['start_date'] ?? null,
                'estimated_time' => $data['estimated_time'] ?? null,
                'position' => $maxPosition + 1,
            ]);

            if (! empty($data['label_ids'])) {
                $todo->labels()->sync($data['label_ids']);
            }

            if (! empty($data['tag_ids'])) {
                $todo->tags()->sync($data['tag_ids']);
            }

            return $todo->load([
                'project', 'assignee', 'labels', 'tags',
                'statusDefinition', 'priorityDefinition',
            ]);
        }, 5);
    }
}
