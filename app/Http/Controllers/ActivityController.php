<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityController extends Controller
{
    public function index(Request $request, Workspace $workspace): Response
    {
        $activities = ActivityLog::where('workspace_id', $workspace->id)
            ->with('user')
            ->latest()
            ->paginate(50);

        return Inertia::render('activity/Index', [
            'activities' => $activities,
        ]);
    }
}
