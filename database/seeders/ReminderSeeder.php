<?php

namespace Database\Seeders;

use App\Enums\ReminderStatus;
use App\Enums\ReminderType;
use App\Models\Reminder;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReminderSeeder extends Seeder
{
    public function run(): void
    {
        $demo = User::where('email', 'demo@example.com')->firstOrFail();
        $alice = User::where('email', 'alice@example.com')->firstOrFail();
        $bob = User::where('email', 'bob@example.com')->firstOrFail();

        $reminders = [
            ['title' => 'Finalize product positioning document', 'user' => $alice, 'reminded_at' => now()->addDays(1), 'is_sent' => false, 'type' => ReminderType::InApp],
            ['title' => 'Implement push notification service', 'user' => $bob, 'reminded_at' => now()->addDays(3), 'is_sent' => false, 'type' => ReminderType::Email],
            ['title' => 'Set up Kubernetes cluster', 'user' => $demo, 'reminded_at' => now()->subDay(), 'is_sent' => true, 'type' => ReminderType::Browser],
            ['title' => 'Fix crash on Android 12', 'user' => $bob, 'reminded_at' => now()->addHours(6), 'is_sent' => false, 'type' => ReminderType::InApp],
        ];

        foreach ($reminders as $data) {
            $todo = Todo::where('title', $data['title'])->first();
            if ($todo) {
                Reminder::create([
                    'todo_id' => $todo->id,
                    'user_id' => $data['user']->id,
                    'reminded_at' => $data['reminded_at'],
                    'is_sent' => $data['is_sent'],
                    'type' => $data['type'],
                    'status' => $data['is_sent']
                        ? ReminderStatus::Delivered
                        : ReminderStatus::Pending,
                    'delivered_at' => $data['is_sent'] ? now() : null,
                ]);
            }
        }

        $this->command->info('Created 4 reminders.');
    }
}
