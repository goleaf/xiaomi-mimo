<?php

namespace App\Http\Middleware;

use App\Enums\UserLanguage;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $user?->loadMissing('preferences');

        $preferredLocale = $user?->preferences?->language;
        $sessionLocale = $request->hasSession() ? $request->session()->get('locale') : null;
        $acceptedLocale = $request->getPreferredLanguage(UserLanguage::values());
        $fallbackLocale = (string) config('app.fallback_locale', UserLanguage::English->value);

        $locale = collect([$preferredLocale, $sessionLocale, $acceptedLocale, $fallbackLocale])
            ->first(fn (mixed $candidate): bool => is_string($candidate)
                && in_array($candidate, UserLanguage::values(), true));

        App::setLocale(is_string($locale) ? $locale : UserLanguage::English->value);

        return $next($request);
    }
}
