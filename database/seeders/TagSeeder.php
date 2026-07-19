<?php

namespace Database\Seeders;

use App\Models\Tag;
use App\Models\Workspace;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        $workspace = Workspace::where('slug', 'acme-projects')->firstOrFail();

        $tags = [
            ['name' => 'frontend'],
            ['name' => 'backend'],
            ['name' => 'design'],
            ['name' => 'urgent'],
            ['name' => 'review'],
        ];

        foreach ($tags as $data) {
            Tag::create([...$data, 'workspace_id' => $workspace->id]);
        }

        $this->command->info('Created 5 tags.');
    }
}
