<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\GalleryItem;
use App\Models\HomepageSection;
use App\Models\Page;
use App\Models\Service;
use App\Support\ClinicSettings;
use Tests\ClinicTestCase;

class PublicWebsiteTest extends ClinicTestCase
{
    public function test_all_core_public_pages_render_with_metadata(): void
    {
        foreach (['/', '/about', '/services', '/services/general-dentistry', '/doctors', '/doctors/demo-general-dentist', '/articles', '/articles/small-habits-healthier-smiles', '/gallery', '/branches', '/branches/addis-ababa-demo', '/appointments', '/contact', '/faq', '/testimonials', '/pages/privacy', '/pages/terms', '/pages/payment-information'] as $url) {
            $this->get($url)->assertOk()->assertSee('<meta name="description"', false)->assertSee('rel="canonical"', false)->assertSee('lang="en"', false);
        }
    }

    public function test_drafts_and_future_articles_are_not_public(): void
    {
        Service::first()->update(['status' => 'draft']);
        $this->get('/services/general-dentistry')->assertNotFound();
        Article::first()->update(['published_at' => now()->addWeek()]);
        $this->get('/articles/small-habits-healthier-smiles')->assertNotFound();
    }

    public function test_language_switching_translates_navigation_and_request_forms(): void
    {
        $this->get('/?lang=am')->assertOk()->assertSee('lang="am"', false)->assertSee('አገልግሎቶች');
        $this->get('/appointments')->assertOk()->assertSee('ሙሉ ስም');
        $this->get('/services?lang=en')->assertOk()->assertSee('Care for every kind of smile.');
        $this->get('/?lang=invalid')->assertOk()->assertSee('lang="en"', false);
    }

    public function test_brand_settings_change_public_output_and_disable_modules(): void
    {
        app(ClinicSettings::class)->update(['name' => 'Configured Clinic', 'primary_color' => '#124a40', 'modules' => ['branches']]);
        $this->get('/')->assertOk()->assertSee('Configured Clinic')->assertSee('--color-primary:#124a40', false)->assertDontSee('href="/services"', false);
        $this->get('/services')->assertNotFound();
    }

    public function test_disabling_demo_mode_hides_demonstration_content_and_reviews(): void
    {
        app(ClinicSettings::class)->update(['demo_mode' => false]);
        $this->get('/doctors')->assertOk()->assertDontSee('Your general dentist');
        $this->get('/testimonials')->assertOk()->assertDontSee('Sample patient story');
        $this->get('/services/general-dentistry')->assertNotFound();
    }

    public function test_homepage_order_and_visibility_are_used(): void
    {
        HomepageSection::where('key', 'hero')->update(['enabled' => false]);
        HomepageSection::where('key', 'faq')->update(['sort_order' => 0]);
        $this->get('/')->assertOk()->assertDontSee('class="hero hero-split"', false)->assertSee('<h1 class="sr-only">', false)->assertSeeInOrder(['A few things you may be wondering.', 'A little care for every smile.']);
    }

    public function test_sitemap_and_robots_do_not_expose_admin_or_draft_pages(): void
    {
        Page::first()->update(['status' => 'draft']);
        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml')->assertSee('/services/general-dentistry')->assertDontSee('/admin');
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /admin')->assertSee('Sitemap:');
    }

    public function test_gallery_consent_is_enforced_in_public_queries(): void
    {
        GalleryItem::create(['title' => 'Private clinical result', 'slug' => 'private-result', 'kind' => 'before_after', 'status' => 'published', 'consent' => false, 'image' => '/images/hero.webp', 'after_image' => '/images/clinic.webp']);
        $this->get('/gallery')->assertOk()->assertDontSee('Private clinical result');
    }

    public function test_public_search_handles_sql_like_text_without_leaking_drafts(): void
    {
        $this->get('/services?q='.urlencode("' OR 1=1 --"))->assertOk()->assertSee('Nothing here just yet.');
    }

    public function test_secure_headers_are_returned(): void
    {
        $this->get('/')->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('X-Frame-Options', 'DENY');
        $this->get('/login')->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }
}
