<?php

namespace App\Http\Controllers;

use App\Http\Resources\TodoResource;
use App\Models\User;
use App\Services\DashboardQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request, DashboardQuery $dashboardQuery): Response
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        $user->loadMissing('preferences');
        $workspace = $user->currentWorkspace(
            (string) $request->session()->get('current_workspace_id'),
        );
        $timezone = $user->preferences?->getAttribute('timezone');

        $data = $workspace
            ? $dashboardQuery->forWorkspace(
                $workspace,
                is_string($timezone) ? $timezone : (string) config('app.timezone'),
            )
            : $dashboardQuery->empty();

        foreach (['todayTasks', 'overdueTasks', 'upcomingTasks'] as $key) {
            $resourceData = TodoResource::collection($data[$key])
                ->toResponse($request)
                ->getData(true);
            $data[$key] = $resourceData['data'] ?? [];
        }

        return Inertia::render('Dashboard', $data);
    }
}
