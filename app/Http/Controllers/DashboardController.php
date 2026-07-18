<?php

namespace App\Http\Controllers;

use App\Enums\TodoStatus;
use App\Models\Workspace;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Workspace $workspace): Response
    {
        $todos = $workspace->todos()->active();

        $todayTasks = $todos->whereDate('due_date', today())->with('project')->get();
        $overdueTasks = (clone $todos)->overdue()->with('project')->limit(10)->get();
        $upcomingTasks = (clone $todos)->where('due_date', '>=', today())
            ->where('due_date', '<=', now()->addDays(7))
            ->where('status', '!=', TodoStatus::Completed)
            ->with('project')->orderBy('due_date')->limit(10)->get();

        $completedToday = (clone $todos)->completedToday()->count();
        $totalTasks = (clone $todos)->count();
        $completedTotal = (clone $todos)->where('status', TodoStatus::Completed)->count();

        $weeklyData = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $weeklyData->push([
                'date' => $date->toDateString(),
                'completed' => (clone $todos)->whereDate('completed_at', $date)->count(),
                'created' => (clone $todos)->whereDate('created_at', $date)->count(),
            ]);
        }

        return Inertia::render('Dashboard', [
            'stats' => [
                'today_count' => $todayTasks->count(),
                'overdue_count' => $overdueTasks->count(),
                'completed_today' => $completedToday,
                'total_tasks' => $totalTasks,
                'completed_total' => $completedTotal,
                'completion_rate' => $totalTasks > 0 ? round(($completedTotal / $totalTasks) * 100) : 0,
            ],
            'todayTasks' => $todayTasks,
            'overdueTasks' => $overdueTasks,
            'upcomingTasks' => $upcomingTasks,
            'weeklyData' => $weeklyData,
        ]);
    }
}
