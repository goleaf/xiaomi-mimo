<?php

namespace App\Http\Controllers;

use App\Actions\BulkDeleteTodos;
use App\Actions\BulkUpdateTodos;
use App\Actions\CompleteTodo;
use App\Actions\CreateTodo;
use App\Actions\DeleteTodo;
use App\Actions\DuplicateTodo;
use App\Actions\FavoriteTodo;
use App\Actions\PinTodo;
use App\Actions\ReorderTodos;
use App\Actions\UncompleteTodo;
use App\Actions\UpdateTodo;
use App\Http\Requests\BulkActionRequest;
use App\Http\Requests\ReorderTodosRequest;
use App\Http\Requests\StoreTodoRequest;
use App\Http\Requests\UpdateTodoRequest;
use App\Http\Resources\LabelResource;
use App\Http\Resources\TaskPriorityResource;
use App\Http\Resources\TaskStatusResource;
use App\Http\Resources\TodoResource;
use App\Models\Label;
use App\Models\Todo;
use App\Models\Workspace;
use App\Services\TodoFilterService;
use App\Services\TodoSortService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TodoController extends Controller
{
    public function __construct(
        private TodoFilterService $filterService,
        private TodoSortService $sortService,
    ) {}

    public function index(Request $request, Workspace $workspace): Response|JsonResponse
    {
        $this->authorize('view', $workspace);

        $query = $workspace->todos()
            ->with(['project', 'assignee', 'labels', 'tags', 'statusDefinition', 'priorityDefinition'])
            ->active();

        $query = $this->filterService->apply($query->getQuery(), $request->only([
            'search', 'project_id', 'status', 'priority', 'assigned_to',
            'label_id', 'tag_id', 'is_pinned', 'is_favorite',
            'due_date_from', 'due_date_to', 'overdue', 'completed_today',
        ]));

        $query = $this->sortService->apply($query, $request->sort, $request->direction);

        $todos = $query->paginate($request->get('per_page', 50));

        if ($request->expectsJson()) {
            return response()->json(TodoResource::collection($todos));
        }

        return Inertia::render('tasks/Index', [
            'todos' => TodoResource::collection($todos),
            'filters' => $request->only(['search', 'project_id', 'status', 'priority']),
            'projects' => $workspace->projects()->active()->get(),
            'workspace' => ['id' => $workspace->id],
            'taskDefinitions' => [
                'statuses' => TaskStatusResource::collection(
                    $workspace->taskStatuses()->ordered()->get(),
                )->resolve($request),
                'priorities' => TaskPriorityResource::collection(
                    $workspace->taskPriorities()->ordered()->get(),
                )->resolve($request),
            ],
        ]);
    }

    public function store(StoreTodoRequest $request, Workspace $workspace, CreateTodo $action): JsonResponse
    {
        $this->authorize('create', [Todo::class, $workspace]);
        $todo = $action->handle($workspace, $request->todoData(), $request->user()->id);

        return response()->json(['todo' => new TodoResource($todo)], 201);
    }

    public function show(Request $request, Todo $todo): Response
    {
        $this->authorize('view', $todo);
        $todo->load([
            'project', 'assignee', 'labels', 'tags', 'comments.user', 'checklists.items',
            'attachments.user', 'reminders', 'subtasks', 'statusDefinition', 'priorityDefinition',
        ]);

        return Inertia::render('tasks/Show', [
            'todo' => new TodoResource($todo),
            'availableLabels' => LabelResource::collection(
                Label::query()
                    ->where('workspace_id', $todo->workspace_id)
                    ->orderBy('name')
                    ->get()
            ),
            'taskDefinitions' => [
                'statuses' => TaskStatusResource::collection(
                    $todo->workspace->taskStatuses()->ordered()->get(),
                )->resolve($request),
                'priorities' => TaskPriorityResource::collection(
                    $todo->workspace->taskPriorities()->ordered()->get(),
                )->resolve($request),
            ],
            'labels' => [
                'editTask' => __('tasks.edit_task'),
                'cancel' => __('tasks.cancel'),
                'saveChanges' => __('tasks.save_changes'),
                'saving' => __('tasks.saving'),
                'title' => __('tasks.title'),
                'description' => __('tasks.description'),
                'descriptionPlaceholder' => __('tasks.description_placeholder'),
                'status' => __('tasks.status'),
                'priority' => __('tasks.priority'),
                'dueDate' => __('tasks.due_date'),
                'labels' => __('tasks.labels'),
                'labelsHelp' => __('tasks.labels_help'),
                'noLabelsAvailable' => __('tasks.no_labels_available'),
                'updated' => __('tasks.updated'),
                'statuses' => [
                    'pending' => __('tasks.statuses.pending'),
                    'inProgress' => __('tasks.statuses.in_progress'),
                    'completed' => __('tasks.statuses.completed'),
                ],
                'priorities' => [
                    'none' => __('tasks.priorities.none'),
                    'low' => __('tasks.priorities.low'),
                    'medium' => __('tasks.priorities.medium'),
                    'high' => __('tasks.priorities.high'),
                    'urgent' => __('tasks.priorities.urgent'),
                ],
            ],
        ]);
    }

    public function update(UpdateTodoRequest $request, Todo $todo, UpdateTodo $action): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $todo);
        $todo = $action->handle($todo, $request->validated());

        if (! $request->expectsJson()) {
            return back();
        }

        return response()->json(['todo' => new TodoResource($todo)]);
    }

    public function destroy(Request $request, Todo $todo, DeleteTodo $action): JsonResponse|RedirectResponse
    {
        $this->authorize('delete', $todo);
        $action->handle($todo);

        if (! $request->expectsJson()) {
            return back();
        }

        return response()->json(null, 204);
    }

    public function complete(Request $request, Todo $todo, CompleteTodo $action): JsonResponse|RedirectResponse
    {
        $this->authorize('complete', $todo);
        $todo = $action->handle($todo);

        if (! $request->expectsJson()) {
            return back();
        }

        return response()->json(['todo' => new TodoResource($todo)]);
    }

    public function uncomplete(Request $request, Todo $todo, UncompleteTodo $action): JsonResponse|RedirectResponse
    {
        $this->authorize('complete', $todo);
        $todo = $action->handle($todo);

        if (! $request->expectsJson()) {
            return back();
        }

        return response()->json(['todo' => new TodoResource($todo)]);
    }

    public function archive(Request $request, Todo $todo): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $todo);
        $todo->update(['is_archived' => true]);

        if (! $request->expectsJson()) {
            return back();
        }

        return response()->json(['todo' => new TodoResource($todo->fresh())]);
    }

    public function restore(Request $request, Todo $todo): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $todo);
        $todo->update(['is_archived' => false]);

        if (! $request->expectsJson()) {
            return back();
        }

        return response()->json(['todo' => new TodoResource($todo->fresh())]);
    }

    public function pin(Request $request, Todo $todo, PinTodo $action): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $todo);
        $todo = $action->handle($todo);

        if (! $request->expectsJson()) {
            return back();
        }

        return response()->json(['todo' => new TodoResource($todo)]);
    }

    public function favorite(Request $request, Todo $todo, FavoriteTodo $action): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $todo);
        $todo = $action->handle($todo);

        if (! $request->expectsJson()) {
            return back();
        }

        return response()->json(['todo' => new TodoResource($todo)]);
    }

    public function duplicate(Request $request, Todo $todo, DuplicateTodo $action): JsonResponse|RedirectResponse
    {
        $this->authorize('create', [Todo::class, $todo->workspace]);
        $todo = $action->handle($todo);

        if (! $request->expectsJson()) {
            return back();
        }

        return response()->json(['todo' => new TodoResource($todo)], 201);
    }

    public function reorder(
        ReorderTodosRequest $request,
        Workspace $workspace,
        ReorderTodos $action,
    ): JsonResponse|RedirectResponse {
        $this->authorize('update', $workspace);
        $action->handle($workspace, $request->items());

        if (! $request->expectsJson()) {
            return back();
        }

        return response()->json(null, 204);
    }

    public function bulk(
        BulkActionRequest $request,
        Workspace $workspace,
        BulkUpdateTodos $bulkUpdate,
        BulkDeleteTodos $bulkDelete,
    ): JsonResponse|RedirectResponse {
        $this->authorize('update', $workspace);

        match ($request->action()) {
            'complete' => $bulkUpdate->setCompletion($workspace, $request->ids(), true),
            'uncomplete' => $bulkUpdate->setCompletion($workspace, $request->ids(), false),
            'archive' => $bulkUpdate->setArchived($workspace, $request->ids(), true),
            'restore' => $bulkUpdate->setArchived($workspace, $request->ids(), false),
            'delete' => $bulkDelete->handle($workspace, $request->ids()),
            default => throw ValidationException::withMessages([
                'action' => __('validation.in', ['attribute' => 'action']),
            ]),
        };

        if (! $request->expectsJson()) {
            return back();
        }

        return response()->json(null, 204);
    }
}
