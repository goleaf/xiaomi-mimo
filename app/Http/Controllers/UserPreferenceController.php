<?php

namespace App\Http\Controllers;

use App\Models\UserPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserPreferenceController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'timezone' => 'sometimes|string',
            'language' => 'sometimes|string',
            'date_format' => 'sometimes|string',
            'time_format' => 'sometimes|string',
            'theme' => 'sometimes|string|in:system,light,dark',
            'default_view' => 'sometimes|string|in:list,board,calendar',
            'start_page' => 'sometimes|string',
            'notification_email' => 'sometimes|boolean',
            'notification_browser' => 'sometimes|boolean',
            'notification_in_app' => 'sometimes|boolean',
        ]);

        $preferences = UserPreference::firstOrCreate(
            ['user_id' => $request->user()->id],
            $validated
        );

        $preferences->update($validated);

        return response()->json(['preferences' => $preferences]);
    }
}
