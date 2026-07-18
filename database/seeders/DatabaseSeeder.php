<?php

namespace Database\Seeders;

use App\Enums\TodoPriority;
use App\Enums\TodoStatus;
use App\Enums\WorkspaceRole;
use App\Models\Project;
use App\Models\Todo;
use App\Models\User;
use App\Models\UserPreference;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
        ]);

        UserPreference::create(['user_id' => $user->id]);

        $workspace = Workspace::create([
            'name' => 'My Workspace',
            'slug' => 'my-workspace',
            'description' => 'Demo workspace',
            'owner_id' => $user->id,
        ]);

        WorkspaceMember::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => WorkspaceRole::Owner,
        ]);

        $projects = [
            ['name' => 'Product Launch', 'color' => '#6366f1', 'icon' => 'rocket'],
            ['name' => 'Website Redesign', 'color' => '#ec4899', 'icon' => 'palette'],
            ['name' => 'Mobile App', 'color' => '#14b8a6', 'icon' => 'smartphone'],
        ];

        foreach ($projects as $index => $data) {
            Project::create([...$data, 'workspace_id' => $workspace->id, 'position' => $index]);
        }

        $projectIds = $workspace->projects()->pluck('id')->toArray();

        $tasks = [
            ['title' => 'Design landing page mockups', 'status' => TodoStatus::InProgress, 'priority' => TodoPriority::High],
            ['title' => 'Write API documentation', 'status' => TodoStatus::Pending, 'priority' => TodoPriority::Medium],
            ['title' => 'Set up CI/CD pipeline', 'status' => TodoStatus::Completed, 'priority' => TodoPriority::High, 'completed_at' => now()],
            ['title' => 'Create user onboarding flow', 'status' => TodoStatus::Pending, 'priority' => TodoPriority::Low],
            ['title' => 'Implement authentication', 'status' => TodoStatus::Completed, 'priority' => TodoPriority::Urgent, 'completed_at' => now()],
            ['title' => 'Design system color tokens', 'status' => TodoStatus::InProgress, 'priority' => TodoPriority::Medium],
            ['title' => 'Write unit tests for auth', 'status' => TodoStatus::Pending, 'priority' => TodoPriority::High],
            ['title' => 'Optimize database queries', 'status' => TodoStatus::Pending, 'priority' => TodoPriority::Medium],
            ['title' => 'Review pull requests', 'status' => TodoStatus::Pending, 'priority' => TodoPriority::Low],
            ['title' => 'Deploy to staging', 'status' => TodoStatus::Pending, 'priority' => TodoPriority::High, 'due_date' => now()->addDays(3)],
            ['title' => 'Fix navigation bug on mobile', 'status' => TodoStatus::Pending, 'priority' => TodoPriority::Urgent, 'due_date' => now()->subDay()],
            ['title' => 'Update dependencies', 'status' => TodoStatus::Pending, 'priority' => TodoPriority::Low],
        ];

        foreach ($tasks as $index => $data) {
            Todo::create([
                ...$data,
                'workspace_id' => $workspace->id,
                'project_id' => $projectIds[array_rand($projectIds)],
                'assigned_to' => $user->id,
                'position' => $index,
                'due_date' => $data['due_date'] ?? fake()->dateTimeBetween('now', '+14 days'),
            ]);
        }
    }
}
