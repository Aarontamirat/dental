<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateClinicAdmin extends Command
{
    protected $signature = 'clinic:admin {email?} {--name=}';

    protected $description = 'Create a clinic administrator using a hidden password prompt';

    public function handle(): int
    {
        $email = $this->argument('email') ?: $this->ask('Administrator email');
        $name = $this->option('name') ?: $this->ask('Administrator name');
        $password = $this->secret('Password (12+ characters, upper/lower case, number, symbol)');
        $validator = Validator::make(compact('email', 'name', 'password'), ['email' => 'required|email|unique:users,email', 'name' => 'required|string|max:120', 'password' => ['required', Password::min(12)->mixedCase()->numbers()->symbols()]]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

return self::FAILURE;
        }
        User::create(['email' => $email, 'name' => $name, 'password' => $password, 'role' => 'clinic_admin', 'active' => true]);
        $this->info('Administrator created. Sign in at /login.');

        return self::SUCCESS;
    }
}
