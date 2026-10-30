<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateClinicAdminFromFile extends Command
{
    protected $signature = 'clinic:admin-file {file : Private JSON file outside the public web root}';

    protected $description = 'Create an administrator noninteractively for hosting cron setup, then remove the credential file';

    public function handle(): int
    {
        $path = realpath($this->argument('file'));
        $public = realpath(public_path());
        if (! $path || ! is_file($path) || str_starts_with(strtolower(str_replace('\\', '/', $path)), strtolower(str_replace('\\', '/', $public)).'/')) {
            $this->error('Use an existing private file outside public/.');

            return self::FAILURE;
        }
        if (filesize($path) > 4096) {
            $this->error('Credential file is too large.');

            return self::FAILURE;
        }
        $data = json_decode(file_get_contents($path), true);
        if (! is_array($data)) {
            $this->error('Invalid credential JSON.');

            return self::FAILURE;
        }
        $v = Validator::make($data, ['name' => 'required|string|max:120', 'email' => 'required|email|unique:users,email', 'password' => ['required', Password::min(12)->mixedCase()->numbers()->symbols()]]);
        if ($v->fails()) {
            $this->error('Invalid administrator data. Use a unique email and a strong password.');

            return self::FAILURE;
        }
        User::create([...$v->validated(), 'role' => 'clinic_admin', 'active' => true]);
        if (! unlink($path)) {
            $this->warn('Account created. Remove the private credential file manually.');
        } else {
            $this->info('Account created and credential file removed.');
        }

return self::SUCCESS;
    }
}
