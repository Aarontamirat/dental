<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ActiveAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            abort(403, 'This account is inactive.');
        }

        return $next($request);
    }
}
