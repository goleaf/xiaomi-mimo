<?php

namespace Database\Seeders;

use App\Models\Label;
use App\Models\Workspace;
use Illuminate\Database\Seeder;

class LabelSeeder extends Seeder
{
    public function run(): void
    {
        $workspace = Workspace::where('slug', 'acme-projects')->firstOrFail();

        $labels = [
            ['name' => 'Bug', 'color' => '#ef4444'],
            ['name' => 'Feature', 'color' => '#3b82f6'],
            ['name' => 'Enhancement', 'color' => '#8b5cf6'],
            ['name' => 'Documentation', 'color' => '#06b6d4'],
            ['name' => 'Testing', 'color' => '#22c55e'],
            ['name' => 'Security', 'color' => '#f97316'],
        ];

        foreach ($labels as $data) {
            Label::create([...$data, 'workspace_id' => $workspace->id]);
        }

        $this->command->info('Created 6 labels.');
    }
}
