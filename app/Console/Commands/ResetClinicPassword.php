<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class ResetClinicPassword extends Command
{
    protected $signature = 'clinic:reset-password {email}';

    protected $description = 'Recover an existing administrator account through a hidden password prompt';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();
        if (! $user) {
            $this->error('Account not found.');

            return self::FAILURE;
        }
        $password = $this->secret('New password');
        $v = Validator::make(compact('password'), ['password' => ['required', Password::min(12)->mixedCase()->numbers()->symbols()]]);
        if ($v->fails()) {
            $this->error($v->errors()->first());

            return self::FAILURE;
        }
        DB::transaction(function () use ($user, $password) {
            $user->update(['password' => $password]);
            DB::table('sessions')->where('user_id', $user->id)->delete();
        });
        $this->info('Password changed and existing sessions revoked.');

        return self::SUCCESS;
    }
}
