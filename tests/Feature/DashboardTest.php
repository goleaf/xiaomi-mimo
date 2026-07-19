<?php

use App\Enums\WorkspaceRole;
use App\Models\Project;
use App\Models\Todo;
use App\Models\User;
use App\Models\UserPreference;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('dashboard information is calculated independently for the selected workspace', function () {
    $this->travelTo(Carbon::parse('2026-07-20 01:00:00', 'UTC'));

    $user = User::factory()->create();
    $workspace = Workspace::factory()->for($user, 'owner')->create();
    $project = Project::factory()->for($workspace)->create();

    WorkspaceMember::create([
        'workspace_id' => $workspace->id,
        'user_id' => $user->id,
        'role' => WorkspaceRole::Owner,
    ]);
    UserPreference::create([
        'user_id' => $user->id,
        'timezone' => 'America/New_York',
        'language' => 'en',
    ]);

    $localTodayTask = Todo::factory()->for($workspace)->for($project)->pending()->create([
        'title' => 'Local today task',
        'due_date' => '2026-07-19',
        'created_at' => '2026-07-20 00:30:00',
    ]);
    $overdueTask = Todo::factory()->for($workspace)->for($project)->pending()->create([
        'title' => 'Overdue task',
        'due_date' => '2026-07-18',
        'created_at' => '2026-07-20 00:30:00',
    ]);
    $upcomingTask = Todo::factory()->for($workspace)->for($project)->pending()->create([
        'title' => 'Upcoming task',
        'due_date' => '2026-07-20',
        'created_at' => '2026-07-20 00:30:00',
    ]);
    Todo::factory()->for($workspace)->for($project)->completed()->create([
        'completed_at' => '2026-07-20 00:30:00',
        'created_at' => '2026-07-20 00:30:00',
    ]);
    Todo::factory()->for($workspace)->for($project)->completed()->create([
        'completed_at' => '2026-07-18 20:00:00',
        'created_at' => '2026-07-20 00:30:00',
    ]);

    Todo::factory()->for($workspace)->for($project)->archived()->create([
        'due_date' => '2026-07-18',
    ]);
    Todo::factory()->for(Workspace::factory())->overdue()->create();

    $this->actingAs($user)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('stats.today_count', 1)
            ->where('stats.overdue_count', 1)
            ->where('stats.completed_today', 1)
            ->where('stats.total_tasks', 5)
            ->where('stats.completed_total', 2)
            ->where('stats.completion_rate', 40)
            ->has('todayTasks', 1)
            ->where('todayTasks.0.id', $localTodayTask->id)
            ->where('todayTasks.0.due_date', '2026-07-19')
            ->where('todayTasks.0.project.id', $project->id)
            ->has('overdueTasks', 1)
            ->where('overdueTasks.0.id', $overdueTask->id)
            ->has('upcomingTasks', 2)
            ->where('upcomingTasks.0.id', $localTodayTask->id)
            ->where('upcomingTasks.1.id', $upcomingTask->id)
            ->has('weeklyData', 7)
            ->where('weeklyData.6.date', '2026-07-19')
            ->where('weeklyData.6.completed', 1)
            ->where('weeklyData.6.created', 5));
});
