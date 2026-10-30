<?php

namespace App\Http\Middleware;

use App\Support\ClinicSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $settings = app(ClinicSettings::class);
        $locale = $request->query('lang', $request->session()->get('locale', $settings->get('locale', 'en')));
        if (! in_array($locale, $settings->get('languages', ['en']), true)) {
            $locale = $settings->get('locale', 'en');
        }
        app()->setLocale($locale);
        if ($request->has('lang')) {
            $request->session()->put('locale', $locale);
        }

        return $next($request);
    }
}
