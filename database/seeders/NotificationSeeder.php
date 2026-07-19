<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $demo = User::where('email', 'demo@example.com')->firstOrFail();
        $alice = User::where('email', 'alice@example.com')->firstOrFail();

        $demo->notifications()->create([
            'type' => 'App\\Notifications\\GenericNotification',
            'data' => json_encode(['title' => 'Welcome to Acme Projects!', 'body' => 'Start by exploring your projects and tasks.']),
            'read_at' => now()->subDays(2),
        ]);

        $demo->notifications()->create([
            'type' => 'App\\Notifications\\GenericNotification',
            'data' => json_encode(['title' => 'New comment on "Product Launch"', 'body' => 'Alice Chen commented on the product positioning document.']),
        ]);

        $demo->notifications()->create([
            'type' => 'App\\Notifications\\GenericNotification',
            'data' => json_encode(['title' => 'Task overdue', 'body' => '"Coordinate with sales team on pricing page" is overdue.']),
        ]);

        $demo->notifications()->create([
            'type' => 'App\\Notifications\\GenericNotification',
            'data' => json_encode(['title' => 'Bob completed a task', 'body' => 'Bob Smith completed "Set up analytics tracking".']),
        ]);

        $demo->notifications()->create([
            'type' => 'App\\Notifications\\GenericNotification',
            'data' => json_encode(['title' => 'Sprint review reminder', 'body' => 'Sprint review meeting is scheduled for Friday at 2 PM.']),
            'read_at' => now()->subDay(),
        ]);

        $this->command->info('Created 5 notifications.');
    }
}
