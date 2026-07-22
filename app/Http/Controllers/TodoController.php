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
use App\Models\Todo;
use App\Models\Workspace;
use App\Queries\TodoDetailQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TodoController extends Controller
{
    public function store(StoreTodoRequest $request, Workspace $workspace, CreateTodo $action): JsonResponse
    {
        $this->authorize('create', [Todo::class, $workspace]);
        $todo = $action->handle($workspace, $request->todoData(), $request->user()->id);

        return response()->json(['todo' => new TodoResource($todo)], 201);
    }

    public function show(Request $request, Todo $todo, TodoDetailQuery $todoDetailQuery): Response
    {
        $this->authorize('view', $todo);
        $todo = $todoDetailQuery->todo($todo);

        return Inertia::render('tasks/Show', [
            'todo' => new TodoResource($todo),
            'availableLabels' => LabelResource::collection(
                $todoDetailQuery->availableLabels($todo),
            ),
            'taskDefinitions' => [
                'statuses' => TaskStatusResource::collection(
                    $todoDetailQuery->statuses($todo),
                )->resolve($request),
                'priorities' => TaskPriorityResource::collection(
                    $todoDetailQuery->priorities($todo),
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

    public function update(UpdateTodoRequest $request, Todo $todo, UpdateTodo $action): RedirectResponse
    {
        $this->authorize('update', $todo);
        $action->handle($todo, $request->validated());

        return back();
    }

    public function destroy(Todo $todo, DeleteTodo $action): RedirectResponse
    {
        $this->authorize('delete', $todo);
        $action->handle($todo);

        return back();
    }

    public function complete(Todo $todo, CompleteTodo $action): RedirectResponse
    {
        $this->authorize('complete', $todo);
        $action->handle($todo);

        return back();
    }

    public function uncomplete(Todo $todo, UncompleteTodo $action): RedirectResponse
    {
        $this->authorize('complete', $todo);
        $action->handle($todo);

        return back();
    }

    public function archive(Todo $todo): RedirectResponse
    {
        $this->authorize('update', $todo);
        $todo->update(['is_archived' => true]);

        return back();
    }

    public function restore(Todo $todo): RedirectResponse
    {
        $this->authorize('update', $todo);
        $todo->update(['is_archived' => false]);

        return back();
    }

    public function pin(Todo $todo, PinTodo $action): RedirectResponse
    {
        $this->authorize('update', $todo);
        $action->handle($todo);

        return back();
    }

    public function favorite(Todo $todo, FavoriteTodo $action): RedirectResponse
    {
        $this->authorize('update', $todo);
        $action->handle($todo);

        return back();
    }

    public function duplicate(Todo $todo, DuplicateTodo $action): RedirectResponse
    {
        $this->authorize('create', [Todo::class, $todo->workspace]);
        $action->handle($todo);

        return back();
    }

    public function reorder(
        ReorderTodosRequest $request,
        Workspace $workspace,
        ReorderTodos $action,
    ): RedirectResponse {
        $this->authorize('update', $workspace);
        $action->handle($workspace, $request->items());

        return back();
    }

    public function bulk(
        BulkActionRequest $request,
        Workspace $workspace,
        BulkUpdateTodos $bulkUpdate,
        BulkDeleteTodos $bulkDelete,
    ): RedirectResponse {
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

        return back();
    }
}
