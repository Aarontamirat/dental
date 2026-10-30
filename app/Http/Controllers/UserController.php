<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Access;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Access::allows($request->user(), 'users'), 403);

        return User::orderBy('name')->paginate(15);
    }

    public function save(Request $request, ?int $id = null)
    {
        abort_unless(Access::allows($request->user(), 'users'), 403);
        $user = $id ? User::findOrFail($id) : new User;
        if ($user->role === 'super_admin' && $request->user()->role !== 'super_admin') {
            abort(403);
        }
        $data = $request->validate(['name' => 'required|string|max:120', 'email' => ['required', 'email:rfc', 'max:254', Rule::unique('users')->ignore($id)], 'role' => ['required', Rule::in(Access::ROLES)], 'active' => 'required|boolean', 'password' => [$id ? 'nullable' : 'required', 'string', Password::min(12)->mixedCase()->numbers()->symbols()]]);
        if ($data['role'] === 'super_admin' && $request->user()->role !== 'super_admin') {
            abort(403);
        }
        if ($id === $request->user()->id && (! $data['active'] || $data['role'] !== $user->role)) {
            throw ValidationException::withMessages(['role' => 'You cannot deactivate or change your own administrative role.']);
        }
        DB::transaction(function () use ($user, $data, $id) {
            User::whereIn('role', ['super_admin', 'clinic_admin'])->where('active', true)->lockForUpdate()->get();
            if ($id && in_array($user->role, ['super_admin', 'clinic_admin'], true) && (! $data['active'] || ! in_array($data['role'], ['super_admin', 'clinic_admin'], true)) && User::where('id', '!=', $id)->whereIn('role', ['super_admin', 'clinic_admin'])->where('active', true)->count() === 0) {
                throw ValidationException::withMessages(['active' => 'Keep at least one active clinic administrator.']);
            }
            if (empty($data['password'])) {
                unset($data['password']);
            } $user->fill($data)->save();
            if ($id && (isset($data['password']) || ! $data['active'] || $user->wasChanged('role'))) {
                DB::table('sessions')->where('user_id', $id)->delete();
            }
            Audit::record($id ? 'updated' : 'created', 'users', $user->id);
        });

        return response()->json(['message' => 'User saved.', 'record' => $user], $id ? 200 : 201);
    }
}
