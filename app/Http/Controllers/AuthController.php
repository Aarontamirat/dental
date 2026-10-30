<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Audit;
use App\Support\NotificationDelivery;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate(['email' => ['required', 'email', 'max:254'], 'password' => ['required', 'string', 'max:255']]);
        if (! Auth::attempt([...$credentials, 'active' => true], false)) {
            throw ValidationException::withMessages(['email' => __('ui.login_failed')]);
        }
        $request->session()->regenerate();
        Audit::record('login', 'users', Auth::id());

        return redirect()->intended('/admin');
    }

    public function logout(Request $request)
    {
        Audit::record('logout', 'users', Auth::id());
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    public function forgot(Request $request)
    {
        $request->validate(['email' => ['required', 'email', 'max:254']]);
        if (! app(NotificationDelivery::class)->available()) {
            return back()->with('status', __('ui.reset_unavailable'));
        }
        try {
            Password::sendResetLink(['email' => $request->email, 'active' => true]);
        } catch (\Throwable) {
            return back()->with('status', __('ui.reset_unavailable'));
        }

        return back()->with('status', __('ui.reset_sent'));
    }

    public function reset(Request $request)
    {
        $data = $request->validate(['token' => ['required'], 'email' => ['required', 'email'], 'password' => ['required', 'confirmed', PasswordRule::min(12)->mixedCase()->numbers()->symbols()]]);
        $status = Password::reset($data, function (User $user, string $password) {
            abort_unless($user->active, 403);
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            event(new PasswordReset($user));
        });
        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }

        return redirect('/login')->with('status', __('ui.password_reset'));
    }
}
