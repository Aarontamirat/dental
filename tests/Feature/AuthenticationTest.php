<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\ClinicTestCase;

class AuthenticationTest extends ClinicTestCase
{
    public function test_an_active_administrator_can_login_and_logout(): void
    {
        $user = $this->user();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user);
        $this->get('/admin')->assertOk();
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_bad_credentials_and_inactive_accounts_are_rejected(): void
    {
        $user = $this->user(active: false);
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->post('/login', ['email' => 'unknown@example.test', 'password' => 'invalid'])->assertSessionHasErrors('email');
    }

    public function test_guests_cannot_access_any_admin_data(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        foreach (['/dashboard', '/settings', '/appointments', '/users', '/media', '/content/services'] as $path) {
            $this->getJson('/admin/api'.$path)->assertUnauthorized();
        }
    }

    public function test_roles_enforce_content_and_patient_data_boundaries(): void
    {
        $this->actingAs($this->user('content_editor'));
        $this->getJson('/admin/api/content/services')->assertOk();
        $this->getJson('/admin/api/media')->assertOk();
        foreach (['/appointments', '/messages', '/users', '/settings', '/activities'] as $path) {
            $this->getJson('/admin/api'.$path)->assertForbidden();
        }
        $this->getJson('/admin/api/dashboard')->assertOk()->assertJsonMissingPath('metrics.Pending requests');
        $this->actingAs($this->user('appointment_manager'));
        $this->getJson('/admin/api/appointments')->assertOk();
        $this->getJson('/admin/api/messages')->assertOk();
        foreach (['/content/services', '/media', '/settings', '/users'] as $path) {
            $this->getJson('/admin/api'.$path)->assertForbidden();
        }
    }

    public function test_deactivation_revokes_access_on_the_next_request(): void
    {
        $user = $this->user();
        $this->actingAs($user);
        $user->update(['active' => false]);
        $this->getJson('/admin/api/bootstrap')->assertForbidden();
        $this->assertGuest();
    }

    public function test_clinic_admin_cannot_escalate_or_edit_a_super_admin(): void
    {
        $this->actingAs($this->user());
        $super = $this->user('super_admin');
        $this->postJson('/admin/api/users', ['name' => 'Escalation', 'email' => 'escalation@example.test', 'password' => 'LongEnough12!aA', 'active' => true, 'role' => 'super_admin'])->assertForbidden();
        $this->putJson('/admin/api/users/'.$super->id, ['name' => 'Changed', 'email' => $super->email, 'active' => true, 'role' => 'clinic_admin'])->assertForbidden();
    }

    public function test_user_creation_hashes_passwords_and_prevents_self_deactivation(): void
    {
        $admin = $this->user();
        $this->actingAs($admin);
        $this->postJson('/admin/api/users', ['name' => 'Editor', 'email' => 'editor@example.test', 'password' => 'LongEnough12!aA', 'active' => true, 'role' => 'content_editor'])->assertCreated();
        $editor = User::where('email', 'editor@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('LongEnough12!aA', $editor->password));
        $this->putJson('/admin/api/users/'.$admin->id, ['name' => $admin->name, 'email' => $admin->email, 'active' => false, 'role' => 'clinic_admin'])->assertUnprocessable()->assertJsonValidationErrors('role');
    }

    public function test_password_recovery_has_an_honest_unconfigured_fallback(): void
    {
        $user = $this->user();
        $this->post('/forgot-password', ['email' => $user->email])->assertRedirect()->assertSessionHas('status', __('ui.reset_unavailable'));
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_password_reset_requires_a_valid_token_and_strong_password(): void
    {
        $user = $this->user();
        $token = Password::createToken($user);
        $this->post('/reset-password', ['token' => $token, 'email' => $user->email, 'password' => 'weak', 'password_confirmation' => 'weak'])->assertSessionHasErrors('password');
        $this->post('/reset-password', ['token' => 'invalid', 'email' => $user->email, 'password' => 'LongEnough12!aA', 'password_confirmation' => 'LongEnough12!aA'])->assertSessionHasErrors('email');
        $this->post('/reset-password', ['token' => $token, 'email' => $user->email, 'password' => 'LongEnough12!aA', 'password_confirmation' => 'LongEnough12!aA'])->assertRedirect('/login');
        $this->assertTrue(Hash::check('LongEnough12!aA', $user->fresh()->password));
    }
}
