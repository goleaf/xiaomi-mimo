<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        $workspace = $user->currentWorkspace(
            (string) $request->session()->get('current_workspace_id'),
        );

        if (! $workspace) {
            return Inertia::render('calendar/Index', ['todos' => []]);
        }

        $todos = Todo::query()
            ->select(['id', 'project_id', 'workspace_id', 'title', 'status', 'priority', 'due_date'])
            ->where('workspace_id', $workspace->id)
            ->where('is_archived', false)
            ->whereNotNull('due_date')
            ->with('project:id,name,color')
            ->orderBy('due_date')
            ->get()
            ->map(function (Todo $todo): array {
                $project = $todo->getRelation('project');

                return [
                    'id' => $todo->id,
                    'title' => $todo->title,
                    'status' => $todo->status->value,
                    'priority' => $todo->priority->value,
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
