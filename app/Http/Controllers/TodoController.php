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
use App\Http\Resources\TodoResource;
use App\Models\Todo;
use App\Models\Workspace;
use App\Services\TodoFilterService;
use App\Services\TodoSortService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        $todo = $action->handle($workspace, $request->validated(), $request->user()->id);

        return response()->json(['todo' => new TodoResource($todo)], 201);
    }

    public function show(Todo $todo): Response
    {
        $this->authorize('view', $todo);
        $todo->load(['project', 'assignee', 'labels', 'tags', 'comments.user', 'checklists.items', 'attachments.user', 'reminders', 'subtasks']);

        return Inertia::render('tasks/Show', [
            'todo' => new TodoResource($todo),
        ]);
    }

    public function update(UpdateTodoRequest $request, Todo $todo, UpdateTodo $action): JsonResponse
    {
        $this->authorize('update', $todo);
        $todo = $action->handle($todo, $request->validated());

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
        };

        return response()->json(null, 204);
    }
}
