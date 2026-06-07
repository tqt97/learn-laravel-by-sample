<?php

namespace App\Http\Middleware;

use App\Supports\Locale;
use Closure;
use Illuminate\Http\Request;
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
        $locale = session('locale', Locale::default());

        if (! Locale::isSupported($locale)) {
            $locale = Locale::default();
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
