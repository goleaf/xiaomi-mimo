<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Seeder;

class CommentSeeder extends Seeder
{
    public function run(): void
    {
        $demo = User::where('email', 'demo@example.com')->firstOrFail();
        $alice = User::where('email', 'alice@example.com')->firstOrFail();
        $bob = User::where('email', 'bob@example.com')->firstOrFail();

        $comments = [
            ['title' => 'Finalize product positioning document', 'user' => $alice, 'body' => 'I reviewed the draft and left some inline comments. The competitor analysis section needs more data points.'],
            ['title' => 'Finalize product positioning document', 'user' => $demo, 'body' => 'Good point. I will pull the latest market research data and update the section by tomorrow.'],
            ['title' => 'Design new landing page mockups', 'user' => $demo, 'body' => 'Love the hero section! Can we try a version with the product screenshot on the right side?'],
            ['title' => 'Implement push notification service', 'user' => $bob, 'body' => 'FCM setup is done. Moving to APNs next. The certificate provisioning took longer than expected.'],
            ['title' => 'Set up Kubernetes cluster', 'user' => $demo, 'body' => 'Cluster is up and running. I have deployed the monitoring stack (Prometheus + Grafana). Please verify the dashboards.'],
            ['title' => 'Set up Kubernetes cluster', 'user' => $alice, 'body' => 'Dashboards look great! One thing — can we add alerting rules for memory usage above 80%?'],
            ['title' => 'Fix crash on Android 12', 'user' => $bob, 'body' => 'Found the root cause: it is a compatibility issue with the new notification API on Android 12. Working on a fix.'],
            ['title' => 'Create social media content calendar', 'user' => $alice, 'body' => 'Draft calendar is ready for review. I have planned posts for LinkedIn, Twitter, and Instagram for the next 30 days.'],
        ];

        foreach ($comments as $data) {
            $todo = Todo::where('title', $data['title'])->first();
            if ($todo) {
                Comment::create([
                    'todo_id' => $todo->id,
                    'user_id' => $data['user']->id,
                    'body' => $data['body'],
                ]);
            }
        }

        $this->command->info('Created 8 comments.');
    }
}
