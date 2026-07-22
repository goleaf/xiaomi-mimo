<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\TaskPriority;
use App\Models\TaskStatus;
use App\Models\Todo;
use App\Models\User;
use App\Queries\CalendarQuery;
use App\Queries\CurrentWorkspaceQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    public function index(
        Request $request,
        CurrentWorkspaceQuery $currentWorkspaceQuery,
        CalendarQuery $calendarQuery,
    ): Response {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        $workspace = $currentWorkspaceQuery->forUser(
            $user,
            $request->session()->get('current_workspace_id'),
        );

        if (! $workspace) {
            return Inertia::render('calendar/Index', ['todos' => []]);
        }

        $todos = $calendarQuery->datedTodos($workspace)
            ->map(function (Todo $todo): array {
                $project = $todo->getRelation('project');
                $statusDefinition = $todo->getRelation('statusDefinition');
                $priorityDefinition = $todo->getRelation('priorityDefinition');

                return [
                    'id' => $todo->id,
                    'title' => $todo->title,
                    'status' => $todo->statusKey(),
                    'priority' => $todo->priorityKey(),
                    'is_completed' => $todo->getRelation('statusDefinition') instanceof TaskStatus
                        ? $todo->statusDefinition->is_completed
                        : $todo->completed_at !== null,
                    'status_definition' => $statusDefinition instanceof TaskStatus ? [
                        'id' => $statusDefinition->id,
                        'key' => $statusDefinition->key,
                        'name' => is_string($statusDefinition->translation_key)
                            ? __($statusDefinition->translation_key)
                            : $statusDefinition->name,
                        'color' => $statusDefinition->color,
                    ] : null,
                    'priority_definition' => $priorityDefinition instanceof TaskPriority ? [
                        'id' => $priorityDefinition->id,
                        'key' => $priorityDefinition->key,
                        'name' => is_string($priorityDefinition->translation_key)
                            ? __($priorityDefinition->translation_key)
                            : $priorityDefinition->name,
                        'color' => $priorityDefinition->color,
                    ] : null,
                    'due_date' => Carbon::parse($todo->getRawOriginal('due_date'))->toDateString(),
                    'project' => $project instanceof Project ? [
                        'id' => $project->id,
                        'name' => $project->name,
                        'color' => $project->color,
                    ] : null,
                ];
            })
            ->all();

        return Inertia::render('calendar/Index', ['todos' => $todos]);
    }
}
