<?php

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! $app->environment('local') || getenv('E2E_SETUP') !== '1' || ! str_contains(str_replace('\\', '/', config('database.connections.sqlite.database')), '.tools/playwright-')) {
    exit(1);
}
User::create(['name' => 'E2E Administrator', 'email' => 'e2e@clinic.test', 'password' => getenv('E2E_PASSWORD'), 'role' => 'clinic_admin', 'active' => true]);
