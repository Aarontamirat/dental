<?php

namespace App\Support;

use App\Models\User;

final class Access
{
    public const ROLES = ['super_admin', 'clinic_admin', 'content_editor', 'appointment_manager'];

    public static function allows(?User $user, string $ability): bool
    {
        if (! $user || ! $user->active) {
            return false;
        }

        return match ($ability) {
            'dashboard' => true,
            'content', 'media' => in_array($user->role, ['super_admin', 'clinic_admin', 'content_editor'], true),
            'appointments', 'messages' => in_array($user->role, ['super_admin', 'clinic_admin', 'appointment_manager'], true),
            'settings', 'users', 'audit' => in_array($user->role, ['super_admin', 'clinic_admin'], true),
            default => false,
        };
    }
}
