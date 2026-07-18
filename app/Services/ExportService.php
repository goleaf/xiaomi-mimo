<?php

namespace App\Services;

use App\Models\Workspace;

class ExportService
{
    public function exportToJson(Workspace $workspace): string
    {
        $data = [
            'workspace' => $workspace->only(['name', 'description']),
            'projects' => $workspace->projects->map(fn ($p) => $p->only(['name', 'description', 'color', 'icon'])),
            'todos' => $workspace->todos()->with(['labels', 'tags', 'checklists.items'])->get()
                ->map(fn ($t) => [
                    'title' => $t->title,
                    'description' => $t->description,
                    'status' => $t->status->value,
                    'priority' => $t->priority->value,
                    'due_date' => $t->due_date?->toDateString(),
                    'project' => $t->project?->name,
                    'labels' => $t->labels->pluck('name'),
                    'tags' => $t->tags->pluck('name'),
                    'checklists' => $t->checklists->map(fn ($c) => [
                        'name' => $c->name,
                        'items' => $c->items->map(fn ($i) => ['content' => $i->content, 'checked' => $i->is_checked]),
                    ]),
                ]),
        ];

        return json_encode($data, JSON_PRETTY_PRINT);
    }

    public function exportToCsv(Workspace $workspace): string
    {
        $todos = $workspace->todos()->with(['project', 'assignee'])->get();

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['Title', 'Status', 'Priority', 'Due Date', 'Project', 'Assigned To', 'Description']);

        foreach ($todos as $todo) {
            fputcsv($handle, [
                $todo->title,
                $todo->status->value,
                $todo->priority->value,
                $todo->due_date?->toDateString(),
                $todo->project?->name,
                $todo->assignee?->name,
                $todo->description,
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    public function exportToMarkdown(Workspace $workspace): string
    {
        $todos = $workspace->todos()->with('project')->get();
        $md = "# {$workspace->name}\n\n";

        foreach ($todos->groupBy('project_id') as $projectId => $projectTodos) {
            $projectName = $projectTodos->first()->project?->name ?? 'No Project';
            $md .= "## {$projectName}\n\n";

            foreach ($projectTodos as $todo) {
                $check = $todo->status->value === 'completed' ? 'x' : ' ';
                $priority = $todo->priority->value !== 'none' ? " [{$todo->priority->value}]" : '';
                $due = $todo->due_date ? " (due: {$todo->due_date->toDateString()})" : '';
                $md .= "- [{$check}] {$todo->title}{$priority}{$due}\n";
            }
            $md .= "\n";
        }

        return $md;
    }
}
