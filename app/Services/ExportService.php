<?php

namespace App\Services;

use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\Project;
use App\Models\Todo;
use App\Models\User;
use App\Models\Workspace;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use JsonException;

class ExportService
{
    private const CHUNK_SIZE = 250;

    /** @throws JsonException */
    public function stream(Workspace $workspace, string $format): void
    {
        if ($format === 'json') {
            $this->streamJson($workspace);

            return;
        }

        if ($format === 'csv') {
            $this->streamCsv($workspace);

            return;
        }

        if ($format === 'markdown') {
            $this->streamMarkdown($workspace);

            return;
        }

        throw new \InvalidArgumentException('Unsupported export format.');
    }

    /** @throws JsonException */
    private function streamJson(Workspace $workspace): void
    {
        echo '{"workspace":';
        $this->writeJson($workspace->only(['name', 'description']));
        echo ',"projects":[';

        $isFirst = true;

        foreach ($this->projectsForWorkspace($workspace)->orderBy('id')->lazy(self::CHUNK_SIZE) as $project) {
            echo $isFirst ? '' : ',';
            $this->writeJson($project->only(['name', 'description', 'color', 'icon']));
            $isFirst = false;
        }

        echo '],"todos":[';
        $isFirst = true;

        $todos = $this->todosForWorkspace($workspace)
            ->with([
                'project' => fn ($query) => $query->where('workspace_id', $workspace->id),
                'labels' => fn ($query) => $query->where('workspace_id', $workspace->id),
                'tags' => fn ($query) => $query->where('workspace_id', $workspace->id),
                'checklists.items',
            ])
            ->orderBy('id')
            ->lazy(self::CHUNK_SIZE);

        foreach ($todos as $todo) {
            $project = $this->loadedProject($todo);
            echo $isFirst ? '' : ',';
            $this->writeJson([
                'title' => $todo->title,
                'description' => $todo->description,
                'status' => $todo->status->value,
                'priority' => $todo->priority->value,
                'due_date' => $this->dueDate($todo),
                'project' => $project?->name,
                'labels' => $this->relatedNames($todo, 'labels'),
                'tags' => $this->relatedNames($todo, 'tags'),
                'checklists' => $this->checklistData($todo),
            ]);
            $isFirst = false;
        }

        echo ']}';
    }

    private function streamCsv(Workspace $workspace): void
    {
        $handle = fopen('php://output', 'w');

        if ($handle === false) {
            throw new \RuntimeException('Unable to open the export stream.');
        }

        try {
            fputcsv(
                $handle,
                ['Title', 'Status', 'Priority', 'Due Date', 'Project', 'Assigned To', 'Description'],
                escape: '',
            );

            $todos = $this->todosForWorkspace($workspace)
                ->with([
                    'project' => fn ($query) => $query->where('workspace_id', $workspace->id),
                    'assignee' => fn ($query) => $query->whereHas(
                        'workspaces',
                        fn ($workspaceQuery) => $workspaceQuery->whereKey($workspace->id),
                    ),
                ])
                ->orderBy('id')
                ->lazy(self::CHUNK_SIZE);

            foreach ($todos as $todo) {
                $project = $this->loadedProject($todo);
                $assignee = $this->loadedAssignee($todo);
                fputcsv($handle, array_map($this->escapeCsvCell(...), [
                    $todo->title,
                    $todo->status->value,
                    $todo->priority->value,
                    $this->dueDate($todo),
                    $project?->name,
                    $assignee?->name,
                    $todo->description,
                ]), escape: '');
            }
        } finally {
            fclose($handle);
        }
    }

    private function streamMarkdown(Workspace $workspace): void
    {
        echo "# {$workspace->name}\n\n";

        $todos = $this->todosForWorkspace($workspace)
            ->with([
                'project' => fn ($query) => $query->where('workspace_id', $workspace->id),
            ])
            ->orderBy('project_id')
            ->orderBy('id')
            ->lazy(self::CHUNK_SIZE);

        $previousProjectId = null;
        $hasWrittenProject = false;

        foreach ($todos as $todo) {
            $project = $this->loadedProject($todo);
            $projectId = $project?->id;

            if (! $hasWrittenProject || $projectId !== $previousProjectId) {
                if ($hasWrittenProject) {
                    echo "\n";
                }

                $projectName = $project instanceof Project ? $project->name : 'No Project';
                echo "## {$projectName}\n\n";
                $previousProjectId = $projectId;
                $hasWrittenProject = true;
            }

            $check = $todo->status->value === 'completed' ? 'x' : ' ';
            $priority = $todo->priority->value !== 'none' ? " [{$todo->priority->value}]" : '';
            $dueDate = $this->dueDate($todo);
            $due = $dueDate !== null ? " (due: {$dueDate})" : '';
            echo "- [{$check}] {$todo->title}{$priority}{$due}\n";
        }

        if ($hasWrittenProject) {
            echo "\n";
        }
    }

    /** @throws JsonException */
    private function writeJson(mixed $value): void
    {
        echo json_encode(
            $value,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
    }

    private function escapeCsvCell(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        $stringValue = (string) $value;

        if (Str::startsWith(ltrim($stringValue), ['=', '+', '-', '@'])) {
            return "'{$stringValue}";
        }

        return $stringValue;
    }

    /** @return Builder<Project> */
    private function projectsForWorkspace(Workspace $workspace): Builder
    {
        return Project::query()->where('workspace_id', $workspace->id);
    }

    /** @return Builder<Todo> */
    private function todosForWorkspace(Workspace $workspace): Builder
    {
        return Todo::query()->where('workspace_id', $workspace->id);
    }

    private function loadedProject(Todo $todo): ?Project
    {
        $project = $todo->getRelation('project');

        return $project instanceof Project ? $project : null;
    }

    private function loadedAssignee(Todo $todo): ?User
    {
        $assignee = $todo->getRelation('assignee');

        return $assignee instanceof User ? $assignee : null;
    }

    private function dueDate(Todo $todo): ?string
    {
        $dueDate = $todo->getAttribute('due_date');

        if ($dueDate instanceof CarbonInterface) {
            return $dueDate->toDateString();
        }

        return is_string($dueDate) ? $dueDate : null;
    }

    /** @return list<string> */
    private function relatedNames(Todo $todo, string $relation): array
    {
        $relatedModels = $todo->getRelation($relation);

        if (! $relatedModels instanceof Collection) {
            return [];
        }

        $names = [];

        foreach ($relatedModels as $relatedModel) {
            $name = $relatedModel->getAttribute('name');

            if (is_string($name)) {
                $names[] = $name;
            }
        }

        return $names;
    }

    /** @return list<array{name: string, items: list<array{content: string, checked: bool}>}> */
    private function checklistData(Todo $todo): array
    {
        $relatedChecklists = $todo->getRelation('checklists');

        if (! $relatedChecklists instanceof Collection) {
            return [];
        }

        $checklists = [];

        foreach ($relatedChecklists as $relatedChecklist) {
            if (! $relatedChecklist instanceof Checklist) {
                continue;
            }

            $name = $relatedChecklist->getAttribute('name');
            $relatedItems = $relatedChecklist->getRelation('items');
            $items = [];

            if ($relatedItems instanceof Collection) {
                foreach ($relatedItems as $relatedItem) {
                    if (! $relatedItem instanceof ChecklistItem) {
                        continue;
                    }

                    $content = $relatedItem->getAttribute('content');

                    if (is_string($content)) {
                        $items[] = [
                            'content' => $content,
                            'checked' => (bool) $relatedItem->getAttribute('is_checked'),
                        ];
                    }
                }
            }

            if (is_string($name)) {
                $checklists[] = ['name' => $name, 'items' => $items];
            }
        }

        return $checklists;
    }
}
