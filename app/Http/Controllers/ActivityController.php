<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityController extends Controller
{
    public function index(Request $request, Workspace $workspace): Response|JsonResponse
    {
        $activities = ActivityLog::where('workspace_id', $workspace->id)
            ->with('user')
            ->latest()
            ->paginate(50);

        if ($request->expectsJson()) {
            return response()->json(['activities' => $activities]);
        }

        return Inertia::render('activity/Index', [
            'activities' => $activities,
        ]);
    }
}
