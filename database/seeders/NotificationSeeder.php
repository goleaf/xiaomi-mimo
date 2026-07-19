<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $demo = User::where('email', 'demo@example.com')->firstOrFail();

        $notifications = [
            ['title' => 'Welcome to Acme Projects!', 'body' => 'Start by exploring your projects and tasks.', 'read_at' => now()->subDays(2)],
            ['title' => 'New comment on "Product Launch"', 'body' => 'Alice Chen commented on the product positioning document.'],
            ['title' => 'Task overdue', 'body' => '"Coordinate with sales team on pricing page" is overdue.'],
            ['title' => 'Bob completed a task', 'body' => 'Bob Smith completed "Set up analytics tracking".'],
            ['title' => 'Sprint review reminder', 'body' => 'Sprint review meeting is scheduled for Friday at 2 PM.', 'read_at' => now()->subDay()],
        ];

        foreach ($notifications as $data) {
            DB::table('notifications')->insert([
                'id' => Str::uuid()->toString(),
                'type' => 'App\\Notifications\\GenericNotification',
                'notifiable_type' => User::class,
                'notifiable_id' => $demo->id,
                'data' => json_encode(['title' => $data['title'], 'body' => $data['body']]),
                'read_at' => $data['read_at'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('Created 5 notifications.');
    }
}
