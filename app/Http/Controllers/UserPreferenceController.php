<?php

namespace App\Http\Controllers;

use App\Actions\UpdateUserPreferences;
use App\Http\Requests\UpdateUserPreferenceRequest;
use Illuminate\Http\RedirectResponse;

class UserPreferenceController extends Controller
{
    public function update(
        UpdateUserPreferenceRequest $request,
        UpdateUserPreferences $updateUserPreferences,
    ): RedirectResponse {
        $preferences = $updateUserPreferences->execute($request->user(), $request->validated());
        $request->session()->put('locale', $preferences->language);

        return back();
    }
}
