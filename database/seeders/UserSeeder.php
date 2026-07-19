<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $demo = User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
        ]);

        UserPreference::create([
            'user_id' => $demo->id,
            'timezone' => 'America/New_York',
            'language' => 'en',
            'theme' => 'dark',
            'default_view' => 'list',
        ]);

        $alice = User::factory()->create([
            'name' => 'Alice Chen',
            'email' => 'alice@example.com',
        ]);

        UserPreference::create([
            'user_id' => $alice->id,
            'timezone' => 'Asia/Tokyo',
            'language' => 'en',
            'theme' => 'light',
        ]);

        $bob = User::factory()->create([
            'name' => 'Bob Smith',
            'email' => 'bob@example.com',
        ]);

        UserPreference::create([
            'user_id' => $bob->id,
            'timezone' => 'Europe/London',
            'language' => 'en',
            'theme' => 'system',
            'default_view' => 'board',
        ]);

        $this->command->info('Created 3 users with preferences.');
    }
}
