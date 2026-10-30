<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\ClinicFoundationSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Console\Command;

class InstallClinic extends Command
{
    protected $signature = 'clinic:install {--demo : Add clearly identified demonstration content}';

    protected $description = 'Prepare a new clinic installation through a private CLI workflow';

    public function handle(): int
    {
        if (! config('app.key')) {
            $this->error('Configure .env and run php artisan key:generate first.');

            return self::FAILURE;
        }
        if ($this->call('migrate', ['--force' => true]) !== 0) {
            return self::FAILURE;
        }
        $this->call('db:seed', ['--class' => ClinicFoundationSeeder::class, '--force' => true]);
        if ($this->option('demo')) {
            $this->call('db:seed', ['--class' => DemoSeeder::class, '--force' => true]);
        }
        if (! User::whereIn('role', ['clinic_admin', 'super_admin'])->where('active', true)->exists()) {
            $this->call('clinic:admin');
        }
        $this->info('Next: sign in at /login, configure branding and branches, add reviewed content, arrange the homepage, and disable demo mode.');

        return self::SUCCESS;
    }
}
