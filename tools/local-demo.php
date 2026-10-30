<?php

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;

// Local-only convenience. No fixed password is created or committed.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! $app->environment('local')) {
    fwrite(STDERR, "This tool is only available in the local environment.\n");
    exit(1);
}
$path = __DIR__.'/../.tools/local-access.json';
if (User::count() > 0) {
    fwrite(STDOUT, "An administrator already exists. Use clinic:admin or clinic:reset-password.\n");
    exit(0);
}
$password = 'D3!'.bin2hex(random_bytes(18)).'aA';
User::create(['name' => 'Demo Administrator', 'email' => 'demo@clinic.test', 'password' => $password, 'role' => 'clinic_admin', 'active' => true]);
if (! is_dir(dirname($path))) {
    mkdir(dirname($path), 0700, true);
}
file_put_contents($path, json_encode(['email' => 'demo@clinic.test', 'password' => $password, 'login' => 'http://localhost:8000/login'], JSON_PRETTY_PRINT));
chmod($path, 0600);
fwrite(STDOUT, "Local demo administrator created. Credentials are in .tools/local-access.json (ignored by Git).\n");
