<?php

namespace Database\Seeders;

use App\Enums\TodoPriority;
use App\Enums\TodoStatus;
use App\Models\Label;
use App\Models\Tag;
use App\Models\Todo;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;

class TodoSeeder extends Seeder
{
    public function run(): void
    {
        $workspace = Workspace::where('slug', 'acme-projects')->firstOrFail();
        $demo = User::where('email', 'demo@example.com')->firstOrFail();
        $alice = User::where('email', 'alice@example.com')->firstOrFail();
        $bob = User::where('email', 'bob@example.com')->firstOrFail();
        $users = [$demo, $alice, $bob];

        $projectIds = $workspace->projects()->pluck('id', 'name');

        $allLabels = Label::where('workspace_id', $workspace->id)->get();
        $allTags = Tag::where('workspace_id', $workspace->id)->get();

        $todos = [
            // Product Launch todos
            ['project' => 'Product Launch', 'title' => 'Finalize product positioning document', 'status' => TodoStatus::InProgress, 'priority' => TodoPriority::High, 'description' => 'Draft and review the final product positioning document with the marketing team.', 'due_date' => now()->addDays(2), 'estimated_time' => 480, 'spent_time' => 180],
            ['project' => 'Product Launch', 'title' => 'Prepare press release', 'status' => TodoStatus::Pending, 'priority' => TodoPriority::Medium, 'description' => 'Write and get approval for the press release ahead of launch.', 'due_date' => now()->addDays(5)],
            ['project' => 'Product Launch', 'title' => 'Set up analytics tracking', 'status' => TodoStatus::Completed, 'priority' => TodoPriority::High, 'description' => 'Configure GA4 events and conversion tracking for launch pages.', 'completed_at' => now()->subDays(2)],
            ['project' => 'Product Launch', 'title' => 'Record product demo video', 'status' => TodoStatus::Pending, 'priority' => TodoPriority::Low, 'due_date' => now()->addDays(10)],
            ['project' => 'Product Launch', 'title' => 'Coordinate with sales team on pricing page', 'status' => TodoStatus::InProgress, 'priority' => TodoPriority::Urgent, 'description' => 'Ensure pricing page reflects final pricing tiers.', 'due_date' => now()->subDay()],

            // Website Redesign todos
            ['project' => 'Website Redesign', 'title' => 'Design new landing page mockups', 'status' => TodoStatus::InProgress, 'priority' => TodoPriority::High, 'description' => 'Create Figma mockups for the hero section and feature highlights.', 'estimated_time' => 960, 'spent_time' => 300, 'due_date' => now()->addDays(3)],
            ['project' => 'Website Redesign', 'title' => 'Implement responsive navigation', 'status' => TodoStatus::Pending, 'priority' => TodoPriority::Medium, 'due_date' => now()->addDays(7)],
            ['project' => 'Website Redesign', 'title' => 'Migrate blog posts to new CMS', 'status' => TodoStatus::Completed, 'priority' => TodoPriority::Medium, 'completed_at' => now()->subDays(5)],
            ['project' => 'Website Redesign', 'title' => 'Performance audit and optimization', 'status' => TodoStatus::Pending, 'priority' => TodoPriority::High, 'due_date' => now()->addDays(14)],

            // Mobile App todos
            ['project' => 'Mobile App', 'title' => 'Implement push notification service', 'status' => TodoStatus::InProgress, 'priority' => TodoPriority::High, 'description' => 'Set up FCM for Android and APNs for iOS.', 'estimated_time' => 1440, 'spent_time' => 600, 'due_date' => now()->addDays(4)],
            ['project' => 'Mobile App', 'title' => 'Design onboarding flow', 'status' => TodoStatus::Pending, 'priority' => TodoPriority::Medium, 'description' => 'Create 4-screen onboarding experience with animations.', 'due_date' => now()->addDays(8)],
            ['project' => 'Mobile App', 'title' => 'Fix crash on Android 12', 'status' => TodoStatus::Pending, 'priority' => TodoPriority::Urgent, 'description' => 'App crashes when opening settings on Android 12 devices.', 'due_date' => now()->subDays(2)],
            ['project' => 'Mobile App', 'title' => 'Add biometric authentication', 'status' => TodoStatus::Completed, 'priority' => TodoPriority::Low, 'completed_at' => now()->subDays(3)],

            // Marketing Campaign todos
            ['project' => 'Marketing Campaign', 'title' => 'Create social media content calendar', 'status' => TodoStatus::InProgress, 'priority' => TodoPriority::Medium, 'description' => 'Plan 30 days of social content across LinkedIn, Twitter, and Instagram.', 'due_date' => now()->addDays(6)],
            ['project' => 'Marketing Campaign', 'title' => 'Design email drip sequence', 'status' => TodoStatus::Pending, 'priority' => TodoPriority::High, 'due_date' => now()->addDays(9)],
            ['project' => 'Marketing Campaign', 'title' => 'Set up A/B testing for ad creatives', 'status' => TodoStatus::Completed, 'priority' => TodoPriority::Medium, 'completed_at' => now()->subDay()],

            // DevOps Infrastructure todos
            ['project' => 'DevOps Infrastructure', 'title' => 'Set up Kubernetes cluster', 'status' => TodoStatus::InProgress, 'priority' => TodoPriority::High, 'description' => 'Provision 3-node cluster on AWS EKS with auto-scaling.', 'estimated_time' => 2880, 'spent_time' => 1200, 'due_date' => now()->addDays(12)],
            ['project' => 'DevOps Infrastructure', 'title' => 'Configure monitoring dashboards', 'status' => TodoStatus::Pending, 'priority' => TodoPriority::Medium, 'due_date' => now()->addDays(15)],

            // Legacy System todos
            ['project' => 'Legacy System', 'title' => 'Document API endpoints for migration', 'status' => TodoStatus::Completed, 'priority' => TodoPriority::Low, 'completed_at' => now()->subDays(10)],
            ['project' => 'Legacy System', 'title' => 'Archive old database backups', 'status' => TodoStatus::Completed, 'priority' => TodoPriority::Low, 'completed_at' => now()->subDays(7)],
        ];

        $position = 0;
        $createdTodos = [];

        foreach ($todos as $data) {
            $project = $projectIds[$data['project']];
            $user = $users[array_rand($users)];
            $todo = Todo::create([
                'workspace_id' => $workspace->id,
                'project_id' => $project,
                'assigned_to' => $user->id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'status' => $data['status'],
                'priority' => $data['priority'],
                'due_date' => $data['due_date'] ?? null,
                'start_date' => $data['start_date'] ?? null,
                'estimated_time' => $data['estimated_time'] ?? null,
                'spent_time' => $data['spent_time'] ?? null,
                'completed_at' => $data['completed_at'] ?? null,
                'position' => $position++,
            ]);

            $createdTodos[] = $todo;
        }

        // Attach labels to random todos
        foreach ($createdTodos as $todo) {
            $labelCount = fake()->numberBetween(0, 2);
            if ($labelCount > 0) {
                $todo->labels()->attach($allLabels->random($labelCount));
            }

            $tagCount = fake()->numberBetween(0, 2);
            if ($tagCount > 0) {
                $todo->tags()->attach($allTags->random($tagCount));
            }
        }

        // Add subtasks to some parent todos
        $parentTodo = $createdTodos[5]; // Design new landing page mockups
        $subtasks = [
            ['title' => 'Draft hero section wireframe', 'status' => TodoStatus::Completed, 'priority' => TodoPriority::High, 'completed_at' => now()->subDay()],
            ['title' => 'Design feature card components', 'status' => TodoStatus::InProgress, 'priority' => TodoPriority::Medium],
            ['title' => 'Create testimonial section layout', 'status' => TodoStatus::Pending, 'priority' => TodoPriority::Low],
        ];

        foreach ($subtasks as $index => $sub) {
            Todo::create([
                'workspace_id' => $workspace->id,
                'project_id' => $parentTodo->project_id,
                'assigned_to' => $alice->id,
                'parent_id' => $parentTodo->id,
                'title' => $sub['title'],
                'status' => $sub['status'],
                'priority' => $sub['priority'],
                'completed_at' => $sub['completed_at'] ?? null,
                'position' => $index,
            ]);
        }

        $parentTodo2 = $createdTodos[9]; // Implement push notification service
        Todo::create([
            'workspace_id' => $workspace->id,
            'project_id' => $parentTodo2->project_id,
            'assigned_to' => $bob->id,
            'parent_id' => $parentTodo2->id,
            'title' => 'Configure FCM for Android',
            'status' => TodoStatus::InProgress,
            'priority' => TodoPriority::High,
            'position' => 0,
        ]);

        Todo::create([
            'workspace_id' => $workspace->id,
            'project_id' => $parentTodo2->project_id,
            'assigned_to' => $bob->id,
            'parent_id' => $parentTodo2->id,
            'title' => 'Configure APNs for iOS',
            'status' => TodoStatus::Pending,
            'priority' => TodoPriority::Medium,
            'position' => 1,
        ]);

        $this->command->info('Created 20 todos with labels, tags, and subtasks.');
    }
}
