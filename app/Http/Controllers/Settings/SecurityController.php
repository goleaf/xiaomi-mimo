<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SecurityController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $features = config('fortify.features', []);

        $props = [
            'canManagePasskeys' => in_array('passkeys', $features),
            'passkeys' => [],
            'canManageTwoFactor' => in_array('two-factor-authentication', $features),
        ];

        if (in_array('two-factor-authentication', $features)) {
            $props['twoFactorEnabled'] = (bool) $user->two_factor_confirmed_at;
        }

        return Inertia::render('settings/Security', $props);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $request->user()->update([
            'password' => $request->password,
        ]);

        return redirect()->route('security.edit');
    }
}
