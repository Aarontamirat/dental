<?php

namespace Tests\Feature;

use App\Models\HomepageSection;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\ClinicFoundationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoundationInstallationTest extends TestCase
{
    use RefreshDatabase;

    public function test_fresh_foundation_is_idempotent_and_does_not_create_demo_content_or_accounts(): void
    {
        $this->seed(ClinicFoundationSeeder::class);
        $count = HomepageSection::count();
        $this->assertGreaterThan(10, $count);
        HomepageSection::where('key', 'hero')->update(['title' => 'Owner heading']);
        $this->seed(ClinicFoundationSeeder::class);
        $this->assertSame($count, HomepageSection::count());
        $this->assertSame('Owner heading', HomepageSection::where('key', 'hero')->first()->title);
        $this->assertSame(0, Service::count());
        $this->assertSame(0, User::count());
        $this->get('/')->assertOk();
    }
}
