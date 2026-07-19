<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $workspace = Workspace::where('slug', 'acme-projects')->firstOrFail();

        $projects = [
            ['name' => 'Product Launch', 'color' => '#6366f1', 'icon' => 'rocket', 'description' => 'Coordinating the Q3 product launch across engineering, marketing, and sales teams.', 'position' => 0],
            ['name' => 'Website Redesign', 'color' => '#ec4899', 'icon' => 'palette', 'description' => 'Complete overhaul of the marketing website with new branding and UX improvements.', 'position' => 1],
            ['name' => 'Mobile App', 'color' => '#14b8a6', 'icon' => 'smartphone', 'description' => 'Native iOS and Android apps for customer-facing features and notifications.', 'position' => 2],
            ['name' => 'Marketing Campaign', 'color' => '#f59e0b', 'icon' => 'megaphone', 'description' => 'Q3 multi-channel marketing campaign including social, email, and paid ads.', 'position' => 3],
            ['name' => 'DevOps Infrastructure', 'color' => '#ef4444', 'icon' => 'server', 'description' => 'Migration to Kubernetes, CI/CD pipeline improvements, and monitoring setup.', 'position' => 4],
            ['name' => 'Legacy System', 'color' => '#6b7280', 'icon' => 'archive', 'description' => 'Deprecated legacy system being phased out by end of year.', 'is_archived' => true, 'position' => 5],
        ];

        foreach ($projects as $data) {
            Project::create([...$data, 'workspace_id' => $workspace->id]);
        }

        $this->command->info('Created 6 projects (1 archived).');
    }
}
