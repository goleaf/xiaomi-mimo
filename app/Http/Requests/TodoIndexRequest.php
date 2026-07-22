<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TodoIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:200'],
            'project_id' => ['nullable', 'uuid'],
            'status' => ['nullable', 'string', 'max:100'],
            'priority' => ['nullable', 'string', 'max:100'],
            'assigned_to' => ['nullable', 'uuid'],
            'label_id' => ['nullable', 'uuid'],
            'tag_id' => ['nullable', 'uuid'],
            'is_pinned' => ['nullable', 'boolean'],
            'is_favorite' => ['nullable', 'boolean'],
            'due_date_from' => ['nullable', 'date_format:Y-m-d'],
            'due_date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:due_date_from'],
            'overdue' => ['nullable', 'boolean'],
            'completed_today' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in(['due_date', 'priority', 'title', 'created_at', 'status'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', Rule::in([25, 50, 100])],
            'view' => ['nullable', Rule::in(['list', 'board'])],
        ];
    }

    /**
     * @return array{search?: string|null, project_id?: string|null, status?: string|null, priority?: string|null, assigned_to?: string|null, label_id?: string|null, tag_id?: string|null, is_pinned?: bool|null, is_favorite?: bool|null, due_date_from?: string|null, due_date_to?: string|null, overdue?: bool|null, completed_today?: bool|null}
     */
    public function filters(): array
    {
        /** @var array{search?: string|null, project_id?: string|null, status?: string|null, priority?: string|null, assigned_to?: string|null, label_id?: string|null, tag_id?: string|null, is_pinned?: bool|null, is_favorite?: bool|null, due_date_from?: string|null, due_date_to?: string|null, overdue?: bool|null, completed_today?: bool|null} $filters */
        $filters = collect($this->safe()->only([
            'search', 'project_id', 'status', 'priority', 'assigned_to',
            'label_id', 'tag_id', 'is_pinned', 'is_favorite',
            'due_date_from', 'due_date_to', 'overdue', 'completed_today',
        ]))->reject(fn (mixed $value): bool => $value === null || $value === '')->all();

        return $filters;
    }

    /** @return array<string, bool|int|string> */
    public function state(): array
    {
        return [
            ...$this->filters(),
            'sort' => $this->sort() ?? '',
            'direction' => $this->direction(),
            'per_page' => $this->perPage(),
            'view' => $this->view(),
        ];
    }

    public function sort(): ?string
    {
        $sort = $this->validated('sort');

        return is_string($sort) && $sort !== '' ? $sort : null;
    }

    public function direction(): string
    {
        return $this->validated('direction') === 'desc' ? 'desc' : 'asc';
    }

    public function perPage(): int
    {
        return (int) ($this->validated('per_page') ?? 50);
    }

    public function view(): string
    {
        $view = $this->validated('view');

        if ($view === 'board' || $view === 'list') {
            return $view;
        }

        $user = $this->user();

        return $user instanceof User && $user->preferences?->default_view === 'board'
            ? 'board'
            : 'list';
    }
}
