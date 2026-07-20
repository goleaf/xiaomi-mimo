<?php

namespace App\Services;

use App\Actions\CreateProject;
use App\Actions\CreateTodo;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use JsonException;

class ImportService
{
    public const MAX_RECORDS = 1_000;

    public function __construct(
        private readonly CreateProject $createProject,
        private readonly CreateTodo $createTodo,
    ) {}

    /** @return array{projects: int, todos: int} */
    public function importFromJson(Workspace $workspace, string $json): array
    {
        return DB::transaction(function () use ($workspace, $json): array {
            $this->ensureUtf8($json);

            try {
                $data = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                $this->invalidFile(__('data_transfer.import.invalid_json', [
                    'message' => $exception->getMessage(),
                ]));
            }

            if (! is_array($data) || array_is_list($data)) {
                $this->invalidFile(__('data_transfer.import.json_object'));
            }

            $unknownKeys = array_diff(array_keys($data), ['workspace', 'projects', 'todos']);

            if ($unknownKeys !== []) {
                $this->invalidFile(__('data_transfer.import.unsupported_top_level'));
            }

            $this->validateJsonWorkspace($data['workspace'] ?? null);
            $projects = $this->recordList($data['projects'] ?? [], 'projects');
            $todos = $this->recordList($data['todos'] ?? [], 'todos');

            if (count($projects) + count($todos) > self::MAX_RECORDS) {
                $this->invalidFile(__('data_transfer.import.record_limit'));
            }

            $imported = ['projects' => 0, 'todos' => 0];
            /** @var array<string, string> $projectIdsByName */
            $projectIdsByName = $workspace->projects()->pluck('id', 'name')->all();

            foreach ($projects as $index => $projectData) {
                $recordLabel = __('data_transfer.import.project_record', ['number' => $index + 1]);
                $validatedProject = $this->validateRecord(
                    $projectData,
                    [
                        'name' => ['required', 'string', 'max:255'],
                        'description' => ['nullable', 'string', 'max:1000'],
                        'color' => ['sometimes', 'string', 'max:7'],
                        'icon' => ['sometimes', 'string', 'max:50'],
                    ],
                    $recordLabel,
                    ['name', 'description', 'color', 'icon'],
                );

                $project = $this->createProject->handle(
                    $workspace,
                    $this->projectData($validatedProject, $recordLabel),
                );
                $projectIdsByName[$project->name] = $project->id;
                $imported['projects']++;
            }

            foreach ($todos as $index => $todoData) {
                $validatedTodo = $this->validateRecord(
                    $todoData,
                    [
                        'title' => ['required', 'string', 'max:255'],
                        'description' => ['nullable', 'string'],
                        'status' => [
                            'sometimes',
                            Rule::exists('task_statuses', 'key')
                                ->where('workspace_id', $workspace->id)
                                ->where('is_archived', 0),
                        ],
                        'priority' => [
                            'sometimes',
                            Rule::exists('task_priorities', 'key')
                                ->where('workspace_id', $workspace->id)
                                ->where('is_archived', 0),
                        ],
                        'due_date' => ['nullable', 'date_format:Y-m-d'],
                        'project' => ['nullable', 'string', 'max:255'],
                        'labels' => ['sometimes', 'array', 'max:100'],
                        'labels.*' => ['string', 'max:255'],
                        'tags' => ['sometimes', 'array', 'max:100'],
                        'tags.*' => ['string', 'max:255'],
                        'checklists' => ['sometimes', 'array', 'max:100'],
                        'checklists.*' => ['array:name,items'],
                        'checklists.*.name' => ['required', 'string', 'max:255'],
                        'checklists.*.items' => ['sometimes', 'array', 'max:500'],
                        'checklists.*.items.*' => ['array:content,checked'],
                        'checklists.*.items.*.content' => ['required', 'string', 'max:1000'],
                        'checklists.*.items.*.checked' => ['required', 'boolean'],
                    ],
                    __('data_transfer.import.task_record', ['number' => $index + 1]),
                    [
                        'title',
                        'description',
                        'status',
                        'priority',
                        'due_date',
                        'project',
                        'labels',
                        'tags',
                        'checklists',
                    ],
                );

                $projectName = $validatedTodo['project'] ?? null;
                $projectId = $this->resolveProjectId(
                    $projectName,
                    $projectIdsByName,
                    __('data_transfer.import.task_record', ['number' => $index + 1]),
                );

                $this->createTodo->handle($workspace, [
                    'title' => $validatedTodo['title'],
                    'description' => $validatedTodo['description'] ?? null,
                    'status' => $validatedTodo['status'] ?? $workspace->taskStatuses()
                        ->where('is_default', true)->value('key'),
                    'priority' => $validatedTodo['priority'] ?? $workspace->taskPriorities()
                        ->where('is_default', true)->value('key'),
                    'due_date' => $validatedTodo['due_date'] ?? null,
                    'project_id' => $projectId,
                ]);
                $imported['todos']++;
            }

            return $imported;
        });
    }

    public function importFromCsv(Workspace $workspace, string $csv): int
    {
        return DB::transaction(function () use ($workspace, $csv): int {
            $this->ensureUtf8($csv);

            $handle = fopen('php://temp', 'r+');

            if ($handle === false || fwrite($handle, $csv) === false) {
                $this->invalidFile(__('data_transfer.import.csv_unreadable'));
            }

            rewind($handle);

            try {
                $headers = fgetcsv($handle, escape: '');

                if (! is_array($headers)) {
                    $this->invalidFile(__('data_transfer.import.csv_header_required'));
                }

                $headers[0] = ltrim((string) $headers[0], "\xEF\xBB\xBF");
                $normalizedHeaders = array_map($this->normalizeCsvHeader(...), $headers);
                $allowedHeaders = [
                    'title',
                    'status',
                    'priority',
                    'due_date',
                    'project',
                    'assigned_to',
                    'description',
                ];

                if (count($normalizedHeaders) !== count(array_unique($normalizedHeaders))) {
                    $this->invalidFile(__('data_transfer.import.csv_duplicate_headers'));
                }

                if (! in_array('title', $normalizedHeaders, true)) {
                    $this->invalidFile(__('data_transfer.import.csv_title_required'));
                }

                if (array_diff($normalizedHeaders, $allowedHeaders) !== []) {
                    $this->invalidFile(__('data_transfer.import.csv_unsupported_columns'));
                }

                /** @var array<string, string> $projectIdsByName */
                $projectIdsByName = $workspace->projects()->pluck('id', 'name')->all();
                $imported = 0;
                $rowNumber = 1;

                while (($row = fgetcsv($handle, escape: '')) !== false) {
                    $rowNumber++;

                    if ($this->isBlankCsvRow($row)) {
                        continue;
                    }

                    if (++$imported > self::MAX_RECORDS) {
                        $this->invalidFile(__('data_transfer.import.csv_record_limit'));
                    }

                    if (count($row) !== count($normalizedHeaders)) {
                        $this->invalidFile(__('data_transfer.import.csv_column_count', [
                            'number' => $rowNumber,
                        ]));
                    }

                    /** @var array<string, string|null> $rowData */
                    $rowData = array_combine($normalizedHeaders, $row);
                    $validatedTodo = $this->validateRecord(
                        [
                            'title' => $rowData['title'] ?? null,
                            'status' => ($rowData['status'] ?? '') ?: $workspace->taskStatuses()
                                ->where('is_default', true)->value('key'),
                            'priority' => ($rowData['priority'] ?? '') ?: $workspace->taskPriorities()
                                ->where('is_default', true)->value('key'),
                            'due_date' => ($rowData['due_date'] ?? '') ?: null,
                            'project' => ($rowData['project'] ?? '') ?: null,
                            'description' => ($rowData['description'] ?? '') ?: null,
                        ],
                        [
                            'title' => ['required', 'string', 'max:255'],
                            'status' => [
                                'required',
                                Rule::exists('task_statuses', 'key')
                                    ->where('workspace_id', $workspace->id)
                                    ->where('is_archived', 0),
                            ],
                            'priority' => [
                                'required',
                                Rule::exists('task_priorities', 'key')
                                    ->where('workspace_id', $workspace->id)
                                    ->where('is_archived', 0),
                            ],
                            'due_date' => ['nullable', 'date_format:Y-m-d'],
                            'project' => ['nullable', 'string', 'max:255'],
                            'description' => ['nullable', 'string'],
                        ],
                        __('data_transfer.import.csv_row', ['number' => $rowNumber]),
                        ['title', 'status', 'priority', 'due_date', 'project', 'description'],
                    );

                    $projectId = $this->resolveProjectId(
                        $validatedTodo['project'] ?? null,
                        $projectIdsByName,
                        __('data_transfer.import.csv_row', ['number' => $rowNumber]),
                    );

                    $this->createTodo->handle($workspace, [
                        'title' => $validatedTodo['title'],
                        'description' => $validatedTodo['description'] ?? null,
                        'status' => $validatedTodo['status'],
                        'priority' => $validatedTodo['priority'],
                        'due_date' => $validatedTodo['due_date'] ?? null,
                        'project_id' => $projectId,
                    ]);
                }

                return $imported;
            } finally {
                fclose($handle);
            }
        });
    }

    private function ensureUtf8(string $content): void
    {
        if (preg_match('//u', $content) !== 1) {
            $this->invalidFile(__('data_transfer.import.utf8'));
        }
    }

    private function validateJsonWorkspace(mixed $workspace): void
    {
        if ($workspace === null) {
            return;
        }

        if (! is_array($workspace) || array_is_list($workspace)) {
            $this->invalidFile(__('data_transfer.import.workspace_object'));
        }

        $this->validateRecord(
            $workspace,
            [
                'name' => ['sometimes', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
            ],
            __('data_transfer.import.workspace_metadata'),
            ['name', 'description'],
        );
    }

    /** @return list<array<string, mixed>> */
    private function recordList(mixed $records, string $section): array
    {
        if (! is_array($records) || ! array_is_list($records)) {
            $this->invalidFile(__('data_transfer.import.section_list', ['section' => $section]));
        }

        foreach ($records as $index => $record) {
            if (! is_array($record) || array_is_list($record)) {
                $recordLabel = $section === 'projects'
                    ? __('data_transfer.import.project_record', ['number' => $index + 1])
                    : __('data_transfer.import.task_record', ['number' => $index + 1]);
                $this->invalidFile(__('data_transfer.import.record_object', ['record' => $recordLabel]));
            }
        }

        /** @var list<array<string, mixed>> $records */
        return $records;
    }

    /**
     * @param  array<string, mixed>  $record
     * @param  array<string, mixed>  $rules
     * @param  list<string>  $allowedKeys
     * @return array<string, mixed>
     */
    private function validateRecord(
        array $record,
        array $rules,
        string $recordLabel,
        array $allowedKeys,
    ): array {
        if (array_diff(array_keys($record), $allowedKeys) !== []) {
            $this->invalidFile(__('data_transfer.import.unsupported_fields', [
                'record' => $recordLabel,
            ]));
        }

        try {
            return Validator::make($record, $rules)->stopOnFirstFailure()->validate();
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first();
            $detail = is_string($message)
                ? $message
                : __('data_transfer.import.invalid_record_fallback');
            $this->invalidFile(__('data_transfer.import.invalid_record', [
                'record' => $recordLabel,
                'detail' => $detail,
            ]));
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{name: string, description?: string|null, color?: string, icon?: string}
     */
    private function projectData(array $data, string $recordLabel): array
    {
        $name = $data['name'] ?? null;

        if (! is_string($name)) {
            $this->invalidFile(__('data_transfer.import.invalid_record', [
                'record' => $recordLabel,
                'detail' => __('data_transfer.import.invalid_record_fallback'),
            ]));
        }

        $projectData = ['name' => $name];

        foreach (['description', 'color', 'icon'] as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            $value = $data[$key];

            if ($key === 'description' && is_null($value)) {
                $projectData[$key] = null;

                continue;
            }

            if (is_string($value)) {
                $projectData[$key] = $value;
            }
        }

        return $projectData;
    }

    /** @param array<string, string> $projectIdsByName */
    private function resolveProjectId(
        mixed $projectName,
        array $projectIdsByName,
        string $recordLabel,
    ): ?string {
        if ($projectName === null || $projectName === '') {
            return null;
        }

        if (! is_string($projectName) || ! array_key_exists($projectName, $projectIdsByName)) {
            $this->invalidFile(__('data_transfer.import.project_outside_workspace', [
                'record' => $recordLabel,
            ]));
        }

        return $projectIdsByName[$projectName];
    }

    private function normalizeCsvHeader(?string $header): string
    {
        return str_replace([' ', '-'], '_', strtolower(trim((string) $header)));
    }

    /** @param list<string|null> $row */
    private function isBlankCsvRow(array $row): bool
    {
        return collect($row)->every(fn (?string $value): bool => trim((string) $value) === '');
    }

    private function invalidFile(string $message): never
    {
        throw ValidationException::withMessages(['file' => $message]);
    }
}
