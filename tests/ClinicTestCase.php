<?php

namespace Tests;

use App\Models\Branch;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;

abstract class ClinicTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
    }

    protected function user(string $role = 'clinic_admin', bool $active = true): User
    {
        return User::factory()->create(compact('role', 'active'));
    }

    protected function requestData(): array
    {
        $branch = Branch::first();

        return ['name' => 'Test Patient', 'phone' => '0912345678', 'email' => 'patient@example.test', 'branch_id' => $branch->id, 'service_id' => $branch->services()->first()->id, 'preferred_date' => now()->addDays(3)->next('Monday')->format('Y-m-d'), 'preferred_time' => '10:00', 'consent' => true, 'form_token' => Crypt::encryptString((string) (time() - 5))];
    }
}
