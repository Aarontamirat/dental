<?php

namespace App\Http\Middleware;

use App\Support\Access;
use App\Support\ClinicSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $preview = $request->query('appearance_preview');
        if ($preview !== null) {
            abort_unless($request->isMethod('GET') && is_string($preview) && Access::allows($request->user(), 'settings') && $request->user()->active && ! $request->is('admin*', 'login', 'logout', 'forgot-password', 'reset-password*'), 403);
            $draft = $request->session()->get('appearance_previews.'.$preview);
            abort_unless(is_array($draft) && $draft['expires'] >= now()->timestamp && $draft['user_id'] === $request->user()->id, 410, 'This appearance preview expired. Refresh it from Appearance.');
            app(ClinicSettings::class)->previewAppearance($draft['configuration'], $draft['homepage'] ?? null);
            $request->attributes->set('appearance_preview', true);
        }
        try {
            $response = $next($request);
        } finally {
            app(ClinicSettings::class)->clearPreview();
        }
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', $preview !== null ? 'SAMEORIGIN' : 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $frames = $request->is('admin') ? "'self'" : "'none'";
        $ancestors = $preview !== null ? "'self'" : "'none'";
        $response->headers->set('Content-Security-Policy', "default-src 'self'; script-src 'self' 'nonce-".app('csp_nonce')."'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:; font-src 'self'; connect-src 'self'; frame-src ".$frames."; base-uri 'self'; form-action 'self'; frame-ancestors ".$ancestors);
        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }
        if ($preview !== null || $request->is('admin*', 'login', 'forgot-password', 'reset-password*')) {
            $response->headers->set('Cache-Control', 'no-store, private');
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }
}
