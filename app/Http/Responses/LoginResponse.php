<?php

namespace App\Http\Responses;

use App\Models\UserPreference;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): Response
    {
        /** @var Request $request */
        $user = $request->user();
        $user?->loadMissing('preferences');
        $route = UserPreference::startRoute($user?->preferences?->start_page);

        return $request->wantsJson()
            ? response()->json(['two_factor' => false])
            : redirect()->intended(route($route, absolute: false));
    }
}
