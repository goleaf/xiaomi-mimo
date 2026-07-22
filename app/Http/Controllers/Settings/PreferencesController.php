<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\UserPreference;
use DateTimeZone;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PreferencesController extends Controller
{
    public function edit(Request $request): Response
    {
        $preferences = $request->user()->preferences()->first();

        return Inertia::render('settings/Preferences', [
            'preferences' => [
                ...UserPreference::defaults(),
                ...($preferences?->only(array_keys(UserPreference::defaults())) ?? []),
            ],
            'timezones' => DateTimeZone::listIdentifiers(),
        ]);
    }
}
