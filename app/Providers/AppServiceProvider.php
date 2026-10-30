<?php

namespace App\Providers;

use App\Support\ClinicSettings;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($path = config('app.public_path')) {
            $this->app->usePublicPath($path);
        }
        $this->app->scoped(ClinicSettings::class);
        $this->app->scoped('csp_nonce', fn () => base64_encode(random_bytes(18)));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::useCspNonce(app('csp_nonce'));
        View::composer(['layouts.public', 'auth.*', 'admin', 'public.*', 'components.*'], function ($view) {
            $view->with('clinic', app(ClinicSettings::class));
        });
        RateLimiter::for('patient', fn ($request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('login', fn ($request) => Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip()));
        RateLimiter::for('password-reset', fn ($request) => Limit::perHour(5)->by($request->ip()));
    }
}
