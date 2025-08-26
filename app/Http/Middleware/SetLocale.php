<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Resolve and set the application locale.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $supported = ['es', 'en'];

        $userLocale = optional($request->user())->locale;
        $sessionLocale = $request->session()->get('locale');
        $acceptLocale = $request->getPreferredLanguage($supported);

        $locale = $userLocale
            ?: ($sessionLocale ?: ($acceptLocale ?: config('app.locale')));

        app()->setLocale($locale);

        return $next($request);
    }
}
