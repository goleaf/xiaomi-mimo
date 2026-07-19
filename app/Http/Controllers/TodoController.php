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
use App\Http\Requests\StoreTodoRequest;
use App\Http\Requests\UpdateTodoRequest;
use App\Http\Resources\LabelResource;
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
            ->with(['project', 'assignee', 'labels', 'tags'])
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
        ]);
    }

    public function store(StoreTodoRequest $request, Workspace $workspace, CreateTodo $action): JsonResponse
    {
        $this->authorize('create', [Todo::class, $workspace]);
        $todo = $action->handle($workspace, $request->todoData(), $request->user()->id);

        return response()->json(['todo' => new TodoResource($todo)], 201);
    }

    public function show(Todo $todo): Response
    {
        $this->authorize('view', $todo);
        $todo->load(['project', 'assignee', 'labels', 'tags', 'comments.user', 'checklists.items', 'attachments.user', 'reminders', 'subtasks']);

        return Inertia::render('tasks/Show', [
            'todo' => new TodoResource($todo),
            'availableLabels' => LabelResource::collection(
                Label::query()
                    ->where('workspace_id', $todo->workspace_id)
                    ->orderBy('name')
                    ->get()
            ),
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

    public function destroy(Todo $todo, DeleteTodo $action): JsonResponse
    {
        $this->authorize('delete', $todo);
        $action->handle($todo);

        return response()->json(null, 204);
    }

    public function complete(Todo $todo, CompleteTodo $action): JsonResponse
    {
        $this->authorize('complete', $todo);
        $todo = $action->handle($todo);

        return response()->json(['todo' => new TodoResource($todo)]);
    }

    public function uncomplete(Todo $todo, UncompleteTodo $action): JsonResponse
    {
        $this->authorize('complete', $todo);
        $todo = $action->handle($todo);

        return response()->json(['todo' => new TodoResource($todo)]);
    }

    public function archive(Todo $todo): JsonResponse
    {
        $this->authorize('update', $todo);
        $todo->update(['is_archived' => true]);

        return response()->json(['todo' => new TodoResource($todo->fresh())]);
    }

    public function restore(Todo $todo): JsonResponse
    {
        $this->authorize('update', $todo);
        $todo->update(['is_archived' => false]);

        return response()->json(['todo' => new TodoResource($todo->fresh())]);
    }

    public function pin(Todo $todo, PinTodo $action): JsonResponse
    {
        $this->authorize('update', $todo);
        $todo = $action->handle($todo);

        return response()->json(['todo' => new TodoResource($todo)]);
    }

    public function favorite(Todo $todo, FavoriteTodo $action): JsonResponse
    {
        $this->authorize('update', $todo);
        $todo = $action->handle($todo);

        return response()->json(['todo' => new TodoResource($todo)]);
    }

    public function duplicate(Todo $todo, DuplicateTodo $action): JsonResponse
    {
        $this->authorize('create', [Todo::class, $todo->workspace]);
        $todo = $action->handle($todo);

        return response()->json(['todo' => new TodoResource($todo)], 201);
    }

    public function reorder(Request $request, Workspace $workspace, ReorderTodos $action): JsonResponse
    {
        $this->authorize('update', $workspace);
        $action->handle($request->items);

        return response()->json(null, 204);
    }

    public function bulk(BulkActionRequest $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('update', $workspace);

        match ($request->action) {
            'complete' => (new BulkUpdateTodos)->handle($request->ids, ['status' => 'completed']),
            'uncomplete' => (new BulkUpdateTodos)->handle($request->ids, ['status' => 'pending']),
            'archive' => (new BulkUpdateTodos)->handle($request->ids, ['is_archived' => true]),
            'restore' => (new BulkUpdateTodos)->handle($request->ids, ['is_archived' => false]),
            'delete' => (new BulkDeleteTodos)->handle($request->ids),
            default => throw ValidationException::withMessages([
                'action' => __('validation.in', ['attribute' => 'action']),
            ]),
        };

        return response()->json(null, 204);
    }
}
