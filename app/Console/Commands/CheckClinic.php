<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\Page;
use App\Models\User;
use App\Support\ClinicSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckClinic extends Command
{
    protected $signature = 'clinic:check';

    protected $description = 'Check runtime and deployment readiness without exposing secrets';

    public function handle(): int
    {
        $checks = ['PHP 8.3+' => version_compare(PHP_VERSION, '8.3.0', '>='), 'GD WebP support' => extension_loaded('gd') && function_exists('imagewebp'), 'Fileinfo' => extension_loaded('fileinfo'), 'Multibyte text' => extension_loaded('mbstring'), 'DOM' => extension_loaded('dom'), 'Writable storage' => is_writable(storage_path()), 'Writable bootstrap cache' => is_writable(base_path('bootstrap/cache')), 'Built frontend assets' => is_file(public_path('build/manifest.json')), 'Application key configured' => filled(config('app.key'))];
        try {
            DB::connection()->getPdo();
            $checks['Database connection'] = true;
            $checks['Active administrator'] = User::whereIn('role', ['clinic_admin', 'super_admin'])->where('active', true)->exists();
        } catch (\Throwable) {
            $checks['Database connection and migrations'] = false;
        }
        if (app()->environment('production')) {
            $checks['Debug disabled'] = ! config('app.debug');
            $checks['HTTPS URL'] = str_starts_with(config('app.url'), 'https://');
            $checks['Secure cookies'] = (bool) config('session.secure');
            $checks['MySQL/MariaDB driver'] = config('database.default') === 'mysql';
            $checks['Demonstration mode disabled'] = ! app(ClinicSettings::class)->get('demo_mode');
            try {
                $checks['Published privacy notice'] = Page::published()->where('slug', 'privacy')->exists();
                if (app(ClinicSettings::class)->get('appointment_enabled')) {
                    $checks['Published appointment branch'] = Branch::published()->exists();
                }
            } catch (\Throwable) {
                $checks['Clinic content initialized'] = false;
            }
        }
        foreach ($checks as $name => $passed) {
            $this->line(($passed ? 'PASS ' : 'FAIL ').$name);
        }
        $this->line('Mail delivery requires a separate test with your hosting provider.');

        return in_array(false, $checks, true) ? self::FAILURE : self::SUCCESS;
    }
}
