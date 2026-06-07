<?php

namespace App\Http\Controllers;

use App\Supports\Locale;

class LocaleSwitchController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(string $locale)
    {
        abort_unless(Locale::isSupported($locale), 404);

        session(['locale' => $locale]);

        app()->setLocale($locale);

        return redirect()->back();
    }
}
