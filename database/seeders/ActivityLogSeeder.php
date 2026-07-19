<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Seeder;

class ActivityLogSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'demo@example.com')->first();

        if (! $user) {
            return;
        }

        $workspace = $user->currentWorkspace();

        if (! $workspace) {
            return;
        }

        $todos = Todo::where('workspace_id', $workspace->id)->get();

        $events = [
            ['event' => 'created', 'properties' => null],
            ['event' => 'updated', 'properties' => ['old' => ['status' => 'pending'], 'new' => ['status' => 'in_progress']]],
            ['event' => 'completed', 'properties' => null],
            ['event' => 'created', 'properties' => null],
            ['event' => 'updated', 'properties' => ['old' => ['priority' => 'medium'], 'new' => ['priority' => 'high']]],
            ['event' => 'created', 'properties' => null],
            ['event' => 'updated', 'properties' => ['old' => ['title' => 'Old title'], 'new' => ['title' => 'New title']]],
            ['event' => 'completed', 'properties' => null],
            ['event' => 'created', 'properties' => null],
            ['event' => 'deleted', 'properties' => null],
            ['event' => 'archived', 'properties' => null],
            ['event' => 'created', 'properties' => null],
        ];

        foreach ($events as $index => $eventData) {
            $todo = $todos->get($index % $todos->count());

            ActivityLog::create([
                'user_id' => $user->id,
                'workspace_id' => $workspace->id,
                'subject_type' => get_class($todo),
                'subject_id' => $todo->id,
                'event' => $eventData['event'],
                'properties' => $eventData['properties'],
                'created_at' => now()->subMinutes(count($events) - $index),
            ]);
        }
    }
}
