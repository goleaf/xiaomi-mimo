<?php

namespace App\Http\Controllers\Api;

use App\Actions\CompleteTodo;
use App\Actions\CreateTodo;
use App\Actions\DeleteTodo;
use App\Actions\UncompleteTodo;
use App\Actions\UpdateTodo;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTodoRequest;
use App\Http\Requests\UpdateTodoRequest;
use App\Http\Resources\TodoResource;
use App\Models\Todo;
use App\Models\Workspace;
use App\Queries\TodoDetailQuery;
use App\Services\TodoFilterService;
use App\Services\TodoSortService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TodoController extends Controller
{
    public function __construct(
        private TodoFilterService $filterService,
        private TodoSortService $sortService,
    ) {}

    public function index(Request $request, Workspace $workspace): AnonymousResourceCollection
    {
        $this->authorize('view', $workspace);

        $query = $workspace->todos()->with([
            'project', 'assignee', 'labels', 'tags', 'statusDefinition', 'priorityDefinition',
        ])->active();
        $query = $this->filterService->apply($query->getQuery(), $request->only([
            'search', 'project_id', 'status', 'priority', 'assigned_to',
        ]));
        $query = $this->sortService->apply($query, $request->sort, $request->direction);

        return TodoResource::collection($query->paginate($request->get('per_page', 50)));
    }

    public function store(StoreTodoRequest $request, Workspace $workspace, CreateTodo $action): JsonResponse
    {
        $this->authorize('create', [Todo::class, $workspace]);
        $todo = $action->handle($workspace, $request->todoData(), $request->user()->id);

        return response()->json(['todo' => new TodoResource($todo)], 201);
    }

    public function show(Todo $todo, TodoDetailQuery $todoDetailQuery): TodoResource
    {
        $this->authorize('view', $todo);

        return new TodoResource($todoDetailQuery->todo($todo));
    }

    public function showScoped(
        Workspace $workspace,
        Todo $todo,
        TodoDetailQuery $todoDetailQuery,
    ): TodoResource {
        return $this->show($todo, $todoDetailQuery);
    }

    public function update(UpdateTodoRequest $request, Todo $todo, UpdateTodo $action): JsonResponse
    {
        $this->authorize('update', $todo);
        $todo = $action->handle($todo, $request->validated());

        return response()->json(['todo' => new TodoResource($todo)]);
    }

    public function updateScoped(
        UpdateTodoRequest $request,
        Workspace $workspace,
        Todo $todo,
        UpdateTodo $action,
    ): JsonResponse {
        return $this->update($request, $todo, $action);
    }

    public function destroy(Todo $todo, DeleteTodo $action): JsonResponse
    {
        $this->authorize('delete', $todo);
        $action->handle($todo);

        return response()->json(null, 204);
    }

    public function destroyScoped(Workspace $workspace, Todo $todo, DeleteTodo $action): JsonResponse
    {
        return $this->destroy($todo, $action);
    }

    public function complete(Todo $todo, CompleteTodo $action): JsonResponse
    {
        $this->authorize('complete', $todo);
        $todo = $action->handle($todo);

        return response()->json(['todo' => new TodoResource($todo)]);
    }

    public function completeScoped(Workspace $workspace, Todo $todo, CompleteTodo $action): JsonResponse
    {
        return $this->complete($todo, $action);
    }

    public function uncomplete(Todo $todo, UncompleteTodo $action): JsonResponse
    {
        $this->authorize('complete', $todo);
        $todo = $action->handle($todo);

        return response()->json(['todo' => new TodoResource($todo)]);
    }

    public function uncompleteScoped(Workspace $workspace, Todo $todo, UncompleteTodo $action): JsonResponse
    {
        return $this->uncomplete($todo, $action);
    }
}
