<?php

namespace Tests\Feature;

use App\Models\HomepageSection;
use App\Support\Appearance;
use App\Support\ClinicSettings;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\ClinicTestCase;

class AppearanceTest extends ClinicTestCase
{
    public function test_guests_and_content_editors_cannot_access_appearance_endpoints(): void
    {
        $this->getJson('/admin/api/appearance')->assertUnauthorized();
        $this->actingAs($this->user('content_editor'));
        $this->getJson('/admin/api/appearance')->assertForbidden();
        $this->postJson('/admin/api/appearance/preview', $this->payload())->assertForbidden();
        $this->putJson('/admin/api/appearance', $this->payload())->assertForbidden();
        $this->assertDatabaseMissing('settings', ['key' => 'appearance']);
    }

    public static function presets(): array
    {
        return array_map(fn (string $preset): array => [$preset], ['clinical', 'premium', 'minimal', 'family', 'technology', 'elegant']);
    }

    #[DataProvider('presets')]
    public function test_coordinated_presets_publish_with_readable_palettes_and_persist(string $preset): void
    {
        $this->actingAs($this->user());
        $configuration = Appearance::presets()[$preset]['configuration'];

        $this->putJson('/admin/api/appearance', ['configuration' => $configuration, 'revision' => 0])
            ->assertOk()->assertJsonPath('revision', 1);

        $this->assertDatabaseHas('settings', ['key' => 'appearance_revision', 'value' => '1']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'published', 'resource' => 'appearance']);
        app()->forgetInstance(ClinicSettings::class);
        $this->getJson('/admin/api/appearance')->assertJsonPath('configuration.preset', $preset)->assertJsonPath('warnings', []);
        $this->get('/services')->assertSee('--color-primary:'.$configuration['colors']['light']['primary'], false);
    }

    public function test_preview_is_private_expires_and_does_not_publish_configuration_or_homepage_order(): void
    {
        $this->freezeTime();
        $this->actingAs($this->user());
        $configuration = Appearance::defaults();
        $configuration['typography']['body_size'] = 21;
        $payload = ['configuration' => $configuration, 'homepage' => [['key' => 'hero', 'enabled' => false, 'sort_order' => 0]]];

        $token = $this->postJson('/admin/api/appearance/preview', $payload)->assertOk()->json('token');

        $this->get('/?appearance_preview='.$token)->assertOk()
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('--typography-body-size:21px', false)->assertDontSee('class="hero hero-', false);
        $this->assertTrue(HomepageSection::where('key', 'hero')->first()->enabled);
        $this->assertDatabaseMissing('settings', ['key' => 'appearance']);
        $this->get('/')->assertHeader('X-Frame-Options', 'DENY')->assertSee('class="hero hero-', false)->assertDontSee('--typography-body-size:21px', false);
        $this->travel(61)->minutes();
        $this->get('/?appearance_preview='.$token)->assertGone();
    }

    public function test_preview_tokens_cannot_be_used_by_a_different_administrator_or_guest(): void
    {
        $this->actingAs($this->user());
        $token = $this->postJson('/admin/api/appearance/preview', $this->payload())->json('token');

        $this->actingAs($this->user())->get('/?appearance_preview='.$token)->assertGone();
        auth()->forgetGuards();
        $this->get('/?appearance_preview='.$token)->assertForbidden();
    }

    public function test_preview_forms_cannot_submit_patient_records(): void
    {
        $this->actingAs($this->user());
        $token = $this->postJson('/admin/api/appearance/preview', $this->payload())->json('token');

        $this->post('/appointments?appearance_preview='.$token, $this->requestData())->assertForbidden();
        $this->assertDatabaseCount('appointments', 0);
    }

    public static function invalidTokens(): array
    {
        return [
            'color injection' => ['colors.light.primary', '#fff;}</style><script>alert(1)</script>'],
            'unreadable inputs' => ['typography.input_size', 12],
            'unknown font' => ['typography.body_font', 'untrusted-font'],
            'unsupported section layout' => ['sections.hero.layout', 'broken'],
            'unknown key' => ['layout.custom_css', 'body{display:none}'],
            'invalid mode' => ['mode', 'inverted'],
        ];
    }

    #[DataProvider('invalidTokens')]
    public function test_invalid_tokens_return_422_without_publishing(string $key, mixed $value): void
    {
        $this->actingAs($this->user());
        $payload = $this->payload();
        data_set($payload, 'configuration.'.$key, $value);

        $this->putJson('/admin/api/appearance', $payload)->assertUnprocessable();

        $this->assertDatabaseMissing('settings', ['key' => 'appearance']);
    }

    public function test_low_contrast_can_be_previewed_but_cannot_be_published(): void
    {
        $this->actingAs($this->user());
        $payload = $this->payload();
        $payload['configuration']['colors']['light']['text'] = '#ffffff';

        $preview = $this->postJson('/admin/api/appearance/preview', $payload)->assertOk();
        $this->assertNotEmpty($preview->json('warnings'));
        $this->putJson('/admin/api/appearance', $payload)->assertUnprocessable()->assertJsonValidationErrors('configuration.colors');
        $this->assertDatabaseMissing('settings', ['key' => 'appearance']);
    }

    public function test_stale_revisions_return_409_and_preserve_the_published_configuration(): void
    {
        $this->actingAs($this->user());
        $this->putJson('/admin/api/appearance', $this->payload())->assertOk();
        $stale = $this->payload();
        $stale['configuration']['typography']['body_size'] = 22;

        $this->putJson('/admin/api/appearance', $stale)->assertConflict();

        $this->getJson('/admin/api/appearance')->assertJsonPath('revision', 1)->assertJsonPath('configuration.typography.body_size', 18);
    }

    public function test_homepage_editor_changes_invalidate_an_open_appearance_draft(): void
    {
        $this->actingAs($this->user());
        $hero = HomepageSection::where('key', 'hero')->first();
        $this->putJson('/admin/api/sections', ['sections' => [['id' => $hero->id, 'title' => $hero->title, 'enabled' => false, 'sort_order' => 0]]])->assertOk();

        $this->putJson('/admin/api/appearance', $this->payload())->assertConflict();

        $this->assertFalse($hero->fresh()->enabled);
    }

    public function test_publication_saves_homepage_order_and_reset_preserves_clinic_content(): void
    {
        $this->actingAs($this->user());
        $name = app(ClinicSettings::class)->get('name');
        $payload = $this->payload();
        $payload['homepage'] = [['key' => 'hero', 'enabled' => false, 'sort_order' => 5], ['key' => 'services', 'enabled' => true, 'sort_order' => 0]];
        $this->putJson('/admin/api/appearance', $payload)->assertOk();

        $this->putJson('/admin/api/appearance', [...$this->payload(), 'revision' => 1])->assertOk();

        $this->assertDatabaseHas('homepage_sections', ['key' => 'hero', 'enabled' => false, 'sort_order' => 5]);
        $this->assertSame($name, app(ClinicSettings::class)->get('name'));
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_legacy_values_and_missing_tokens_resolve_without_a_database_migration(): void
    {
        app(ClinicSettings::class)->update(['primary_color' => '#124a40', 'font' => 'modern']);

        $this->get('/')->assertSee('--color-primary:#124a40', false)->assertSee("--serif:'DM Sans'", false);

        app(ClinicSettings::class)->update(['appearance' => ['typography' => ['body_size' => 20]]]);
        $this->get('/')->assertSee('--typography-body-size:20px', false)->assertSee('--typography-input-size:17px', false);
    }

    public function test_dark_and_system_modes_render_the_correct_palettes_and_escape_copyright(): void
    {
        $this->actingAs($this->user());
        $payload = $this->payload();
        $payload['configuration']['mode'] = 'dark';
        $payload['configuration']['footer']['copyright'] = '<script>alert(1)</script>';
        $this->putJson('/admin/api/appearance', $payload)->assertOk();

        $this->get('/')->assertSee('--color-background:#101d1b', false)->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $payload['configuration']['mode'] = 'system';
        $payload['revision'] = 1;
        $this->putJson('/admin/api/appearance', $payload)->assertOk();
        $this->get('/')->assertSee('@media(prefers-color-scheme:dark)', false)->assertSee('color-scheme:light', false);
    }

    public function test_malformed_stored_section_values_use_safe_defaults(): void
    {
        app(ClinicSettings::class)->update(['appearance' => ['sections' => 'invalid']]);

        $this->get('/')->assertOk()->assertSee('class="hero hero-split"', false);

        app(ClinicSettings::class)->update(['appearance' => ['sections' => ['hero' => false]]]);
        $this->get('/')->assertOk()->assertSee('class="hero hero-split"', false);
    }

    public function test_validation_feedback_links_errors_to_patient_controls(): void
    {
        $response = $this->from('/appointments')->followingRedirects()->post('/appointments', [...$this->requestData(), 'phone' => 'invalid']);

        $response->assertOk()->assertSee('id="error-phone"', false)->assertSee('href="#phone"', false)->assertSee('aria-describedby="phone-help error-phone"', false);
    }

    public function test_numeric_backup_values_are_normalized_before_preview_and_publication(): void
    {
        $this->actingAs($this->user());
        $payload = $this->payload();
        $payload['configuration']['header']['sticky'] = '0';
        $payload['configuration']['motion']['enabled'] = 0;
        $payload['configuration']['buttons']['shadow'] = '1';
        $payload['configuration']['typography']['body_size'] = '20';
        $payload['configuration']['typography']['heading_weight'] = 600;
        $payload['homepage'] = [['key' => 'hero', 'enabled' => '0', 'sort_order' => '2']];

        $preview = $this->postJson('/admin/api/appearance/preview', $payload)->assertOk()
            ->assertJsonPath('configuration.header.sticky', false)
            ->assertJsonPath('configuration.motion.enabled', false)
            ->assertJsonPath('configuration.buttons.shadow', true)
            ->assertJsonPath('configuration.typography.body_size', 20)
            ->assertJsonPath('configuration.typography.heading_weight', '600')
            ->assertJsonPath('homepage.0.enabled', false)
            ->assertJsonPath('homepage.0.sort_order', 2);

        $this->get('/?appearance_preview='.$preview->json('token'))->assertOk()
            ->assertSee('data-header-sticky="false"', false)->assertSee('data-motion-enabled="false"', false);
        $this->assertDatabaseMissing('settings', ['key' => 'appearance']);

        $this->putJson('/admin/api/appearance', $payload)->assertOk();
        app()->forgetInstance(ClinicSettings::class);
        $this->getJson('/admin/api/appearance')->assertOk()
            ->assertJsonPath('configuration.header.sticky', false)->assertJsonPath('configuration.typography.body_size', 20);
        $this->assertDatabaseHas('homepage_sections', ['key' => 'hero', 'enabled' => false, 'sort_order' => 2]);
    }

    public function test_secondary_color_has_a_readable_automatic_foreground_in_each_palette(): void
    {
        $this->actingAs($this->user());
        $payload = $this->payload();
        $payload['configuration']['mode'] = 'system';
        $payload['configuration']['colors']['light']['secondary'] = '#ffffff';
        $payload['configuration']['colors']['dark']['secondary'] = '#000000';

        $this->putJson('/admin/api/appearance', $payload)->assertOk();

        $this->get('/')->assertOk()->assertSee('--color-on-secondary:#000000;', false)->assertSee('--color-on-secondary:#ffffff;', false);
    }

    private function payload(): array
    {
        return ['configuration' => Appearance::defaults(), 'revision' => 0];
    }
}
