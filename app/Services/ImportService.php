<?php

namespace App\Services;

use App\Actions\CreateProject;
use App\Actions\CreateTodo;
use App\Models\Workspace;

class ImportService
{
    public function importFromJson(Workspace $workspace, string $json): array
    {
        $data = json_decode($json, true);
        $imported = ['projects' => 0, 'todos' => 0];

        if (! empty($data['projects'])) {
            $createProject = app(CreateProject::class);
            foreach ($data['projects'] as $projectData) {
                $createProject->handle($workspace, $projectData);
                $imported['projects']++;
            }
        }

        if (! empty($data['todos'])) {
            $createTodo = app(CreateTodo::class);
            foreach ($data['todos'] as $todoData) {
                $projectName = $todoData['project'] ?? null;
                if ($projectName) {
                    $project = $workspace->projects()->where('name', $projectName)->first();
                    $todoData['project_id'] = $project?->id;
                }
                unset($todoData['project']);
                $createTodo->handle($workspace, $todoData);
                $imported['todos']++;
            }
        }

        return $imported;
    }

    public function importFromCsv(Workspace $workspace, string $csv): int
    {
        $rows = array_map('str_getcsv', explode("\n", $csv));
        $headers = array_shift($rows);
        $count = 0;

        $createTodo = app(CreateTodo::class);

        foreach ($rows as $row) {
            if (empty($row[0])) {
                continue;
            }
            $data = array_combine($headers, $row);
            $createTodo->handle($workspace, [
                'title' => $data['Title'] ?? $data['title'] ?? '',
                'status' => $data['Status'] ?? $data['status'] ?? 'pending',
                'priority' => $data['Priority'] ?? $data['priority'] ?? 'none',
                'due_date' => $data['Due Date'] ?? $data['due_date'] ?? null,
                'description' => $data['Description'] ?? $data['description'] ?? null,
            ]);
            $count++;
        }

        return $count;
    }
}
