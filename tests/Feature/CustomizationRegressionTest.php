<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\HomepageSection;
use App\Models\Professional;
use App\Models\Service;
use App\Support\ClinicSettings;
use App\Support\ResponsiveImage;
use Tests\ClinicTestCase;

class CustomizationRegressionTest extends ClinicTestCase
{
    public function test_responsive_bundled_images_render_with_local_variants(): void
    {
        $this->get('/')->assertOk()->assertSee('/images/smile-480.webp 480w', false)->assertSee('/images/smile-960.webp 960w', false)->assertSee('sizes="(max-width: 900px) 92vw, 45vw"', false);
        $this->assertNull(ResponsiveImage::srcset('/media/1'));
        $this->assertNull(ResponsiveImage::srcset('/images/../../.env'));
    }

    public function test_language_canonicals_and_alternates_use_explicit_locale_urls(): void
    {
        $base = rtrim(config('app.url'), '/').'/services';
        $this->get('/services?lang=am')->assertOk()->assertSee('rel="canonical" href="'.$base.'?lang=am"', false);
        $this->get('/services?lang=en')->assertOk()->assertSee('rel="canonical" href="'.$base.'?lang=en"', false)->assertSee('hreflang="am" href="'.$base.'?lang=am"', false);
        $this->get('/sitemap.xml')->assertOk()->assertSee('/services?lang=en', false)->assertSee('/services?lang=am', false);
    }

    public function test_structured_data_uses_real_service_and_professional_content_without_demo_claims(): void
    {
        Service::create(['title' => 'Clinic-supplied service', 'slug' => 'clinic-supplied', 'status' => 'published', 'is_demo' => false]);
        Professional::create(['title' => 'Clinic-supplied professional', 'slug' => 'clinic-professional', 'status' => 'published', 'kind' => 'dentist', 'is_demo' => false]);
        $this->get('/services/clinic-supplied')->assertOk()->assertSee('"@type":"Service"', false);
        $this->get('/doctors/clinic-professional')->assertOk()->assertSee('"@type":"Person"', false);
        $this->get('/doctors/demo-general-dentist')->assertOk()->assertDontSee('"@type":"Person"', false)->assertDontSee('aggregateRating');
    }

    public function test_verified_statistics_and_trust_messages_are_editable_and_published_safely(): void
    {
        $this->actingAs($this->user());
        $settings = app(ClinicSettings::class)->all();
        $data = [...$settings,
            'trust_items' => [['icon' => 'heart', 'title' => 'A clinic-approved message', 'description' => 'Your questions are welcome.']],
            'statistics' => [
                ['label' => 'Verified branches', 'value' => '2', 'verified' => true],
                ['label' => 'Unverified figure', 'value' => '900', 'verified' => false],
            ],
            'whatsapp' => '0912345678',
        ];
        $this->putJson('/admin/api/settings', $data)->assertOk();
        HomepageSection::where('key', 'statistics')->update(['enabled' => true]);
        $this->get('/')->assertSee('A clinic-approved message')->assertSee('Verified branches')->assertDontSee('Unverified figure')->assertSee('wa.me/251912345678');
        $this->putJson('/admin/api/settings', [...$data, 'trust_items' => [['icon' => 'bad', 'title' => 'Invalid']]])->assertUnprocessable()->assertJsonValidationErrors('trust_items.0.icon');
    }

    public function test_homepage_primary_values_and_translations_stay_in_sync_in_an_amharic_session(): void
    {
        $this->actingAs($this->user());
        $this->get('/?lang=am')->assertOk();
        $sections = $this->getJson('/admin/api/sections')->assertOk()->json();
        $hero = collect($sections)->firstWhere('key', 'hero');
        $this->assertSame(app(ClinicSettings::class)->all()['hero_title'], $hero['title']);
        $payload = [['id' => $hero['id'], 'title' => 'Updated primary heading', 'subtitle' => 'Updated introduction', 'enabled' => true, 'sort_order' => 0, 'translations' => ['am' => ['title' => 'የክሊኒክ ርዕስ', 'subtitle' => 'የክሊኒክ መግለጫ']]]];
        $this->putJson('/admin/api/sections', ['sections' => $payload])->assertOk();
        $this->get('/?lang=am')->assertSee('የክሊኒክ ርዕስ');
        $this->get('/?lang=en')->assertSee('Updated primary heading');
        $settings = $this->getJson('/admin/api/settings')->json();
        $this->assertSame('የክሊኒክ ርዕስ', $settings['translations']['am']['hero_title']);
        $this->putJson('/admin/api/settings', [...$settings, 'hero_title' => 'Edited in settings'])->assertOk();
        $this->assertSame('Edited in settings', collect($this->getJson('/admin/api/sections')->json())->firstWhere('key', 'hero')['title']);
    }

    public function test_blank_settings_translations_fall_back_to_primary_content(): void
    {
        app(ClinicSettings::class)->update(['hero_title' => 'Primary content', 'translations' => ['am' => ['hero_title' => '']]]);
        $this->get('/?lang=am')->assertOk()->assertSee('Primary content');
    }

    public function test_hidden_demo_options_cannot_be_submitted_as_patient_preferences(): void
    {
        $data = [...$this->requestData(), 'professional_id' => Professional::first()->id];
        app(ClinicSettings::class)->update(['demo_mode' => false]);
        $this->post('/appointments', $data)->assertSessionHasErrors(['branch_id', 'service_id', 'professional_id']);
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_branch_hours_reject_duplicate_weekdays_and_invalid_time_ranges(): void
    {
        $this->actingAs($this->user());
        $branch = Branch::first();
        $data = ['title' => $branch->title, 'slug' => $branch->slug, 'status' => 'published', 'address' => $branch->address, 'city' => $branch->city];
        $day = ['day' => 'Monday', 'open' => '08:00', 'close' => '18:00', 'closed' => false];
        $this->putJson('/admin/api/content/branches/'.$branch->id, [...$data, 'hours' => [$day, $day]])->assertUnprocessable()->assertJsonValidationErrors('hours');
        $this->putJson('/admin/api/content/branches/'.$branch->id, [...$data, 'hours' => [[...$day, 'open' => '18:00', 'close' => '08:00']]])->assertUnprocessable()->assertJsonValidationErrors('hours');
        $this->putJson('/admin/api/content/branches/'.$branch->id, [...$data, 'hours' => [['day' => 'Monday', 'closed' => true]]])->assertOk();
    }
}
