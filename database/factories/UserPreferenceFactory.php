<?php

namespace Database\Factories;

use App\Models\UserFactory;
use App\Models\UserPreference;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserPreferenceFactory extends Factory
{
    protected $model = UserPreference::class;

    public function definition(): array
    {
        return [
            'user_id' => UserFactory::new(),
            'timezone' => fake()->timezoneAbbr(),
            'language' => 'en',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
            'theme' => 'system',
            'default_view' => 'list',
            'start_page' => 'dashboard',
            'notification_email' => true,
            'notification_browser' => true,
            'notification_in_app' => true,
        ];
    }
}
