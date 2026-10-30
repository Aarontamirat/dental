<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Category;
use App\Models\HomepageSection;
use App\Models\Professional;
use App\Models\Service;
use App\Support\ClinicSettings;
use App\Support\ContentRegistry;
use Tests\ClinicTestCase;

class ContentManagementTest extends ClinicTestCase
{
    public function test_service_crud_preserves_relations_and_sanitizes_rich_html(): void
    {
        $this->actingAs($this->user());
        $branch = Branch::first();
        $data = ['title' => 'New service', 'slug' => 'new-service', 'status' => 'published', 'icon' => 'tooth', 'branches' => [$branch->id], 'content' => '<h2>Safe heading</h2><script>alert(1)</script><p onclick="alert(2)">Safe text <a href="javascript:alert(3)">link</a></p>'];
        $this->postJson('/admin/api/content/services', $data)->assertCreated();
        $service = Service::where('slug', 'new-service')->firstOrFail();
        $this->assertTrue($service->branches()->whereKey($branch->id)->exists());
        $this->assertStringNotContainsString('<script', $service->content);
        $this->assertStringNotContainsString('onclick', $service->content);
        $this->assertStringNotContainsString('javascript:', $service->content);
        $this->getJson('/admin/api/content/services/'.$service->id)->assertOk()->assertJsonPath('branches.0', $branch->id);
        $this->putJson('/admin/api/content/services/'.$service->id, [...$data, 'title' => 'Updated service'])->assertOk();
        $this->get('/services/new-service')->assertOk()->assertSee('Updated service');
        $this->deleteJson('/admin/api/content/services/'.$service->id)->assertOk();
        $this->assertDatabaseMissing('branch_service', ['service_id' => $service->id]);
        $this->get('/services/new-service')->assertNotFound();
    }

    public function test_every_content_module_supports_a_valid_create_and_delete(): void
    {
        $this->actingAs($this->user());
        foreach (ContentRegistry::modules() as $key => $config) {
            $data = ['title' => 'Sample '.$key, 'slug' => 'sample-'.$key, 'status' => 'draft'];
            foreach ($config['fields'] as $field) {
                if (($field[3] ?? false) && $field[2] === 'select') {
                    $data[$field[0]] = $field[4][0];
                }
            }
            if ($key === 'branches') {
                $data += ['address' => 'Test street', 'city' => 'Addis Ababa'];
            }if ($key === 'navigation') {
                $data += ['url' => '/about', 'enabled' => true];
            }
            $response = $this->postJson('/admin/api/content/'.$key, $data)->assertCreated();
            $id = $response->json('record.id');
            $this->getJson('/admin/api/content/'.$key.'/'.$id)->assertOk();
            $this->deleteJson('/admin/api/content/'.$key.'/'.$id)->assertOk();
        }
    }

    public function test_validation_rejects_duplicate_slugs_bad_relations_and_bad_image_urls(): void
    {
        $this->actingAs($this->user());
        $this->postJson('/admin/api/content/services', ['title' => 'Duplicate', 'slug' => 'general-dentistry', 'status' => 'draft'])->assertUnprocessable()->assertJsonValidationErrors('slug');
        $this->postJson('/admin/api/content/services', ['title' => 'Bad relation', 'slug' => 'bad-relation', 'status' => 'draft', 'branches' => [999999], 'image' => 'https://example.test/evil.svg'])->assertUnprocessable()->assertJsonValidationErrors(['branches.0', 'image']);
        $category = Category::where('type', 'article')->first();
        $this->postJson('/admin/api/content/services', ['title' => 'Wrong category', 'slug' => 'wrong-category', 'status' => 'draft', 'category_id' => $category->id])->assertUnprocessable()->assertJsonValidationErrors('category_id');
    }

    public function test_testimonials_require_verified_consent_and_approval(): void
    {
        $this->actingAs($this->user());
        $this->postJson('/admin/api/content/testimonials', ['title' => 'A patient', 'slug' => 'patient-review', 'status' => 'published', 'excerpt' => 'A submitted patient statement', 'approved' => false, 'consent' => false])->assertUnprocessable()->assertJsonValidationErrors('approved');
    }

    public function test_before_and_after_publication_requires_a_consent_reference(): void
    {
        $this->actingAs($this->user());
        $this->postJson('/admin/api/content/gallery', ['title' => 'Clinical comparison', 'slug' => 'comparison', 'status' => 'published', 'kind' => 'before_after', 'image' => '/images/hero.webp', 'after_image' => '/images/clinic.webp', 'consent' => true])->assertUnprocessable()->assertJsonValidationErrors('consent');
        $this->postJson('/admin/api/content/gallery', ['title' => 'Clinical comparison', 'slug' => 'comparison', 'status' => 'published', 'kind' => 'before_after', 'image' => '/images/hero.webp', 'after_image' => '/images/clinic.webp', 'consent' => true, 'consent_reference' => 'Signed consent stored in clinic private file'])->assertCreated();
        $this->get('/gallery')->assertOk()->assertSee('Clinical comparison')->assertDontSee('Signed consent stored in clinic private file');
    }

    public function test_branch_professional_relations_and_multilingual_content_are_saved(): void
    {
        $this->actingAs($this->user());
        $person = Professional::first();
        $branch = Branch::first();
        $this->putJson('/admin/api/content/professionals/'.$person->id, ['title' => $person->title, 'slug' => $person->slug, 'status' => 'published', 'kind' => 'dentist', 'branches' => [$branch->id], 'translations' => ['am' => ['title' => 'የማሳያ ሐኪም', 'content' => '<p>መግለጫ</p><script>evil()</script>']]])->assertOk();
        $this->get('/doctors/'.$person->slug.'?lang=am')->assertOk()->assertSee('የማሳያ ሐኪም')->assertDontSee('evil()');
    }

    public function test_admin_settings_are_validated_and_change_the_public_site(): void
    {
        $this->actingAs($this->user());
        $settings = app(ClinicSettings::class)->all();
        $this->putJson('/admin/api/settings', [...$settings, 'name' => 'A New Clinic', 'primary_color' => '#123f36'])->assertOk();
        $this->get('/')->assertSee('A New Clinic');
        $this->putJson('/admin/api/settings', [...$settings, 'primary_color' => 'red; background:url(x)'])->assertUnprocessable()->assertJsonValidationErrors('primary_color');
        $this->putJson('/admin/api/settings', [...$settings, 'languages' => ['en'], 'locale' => 'am'])->assertUnprocessable()->assertJsonValidationErrors('locale');
    }

    public function test_homepage_changes_are_authorized_and_persisted(): void
    {
        $this->actingAs($this->user());
        $sections = HomepageSection::orderBy('sort_order')->get()->map(fn ($s) => ['id' => $s->id, 'title' => $s->title, 'subtitle' => $s->subtitle, 'enabled' => $s->key !== 'hero', 'sort_order' => $s->sort_order])->all();
        $this->putJson('/admin/api/sections', ['sections' => $sections])->assertOk();
        $this->get('/')->assertDontSee('class="hero hero-split"', false);
        $this->assertDatabaseHas('activity_logs',['action' => 'updated', 'resource' => 'homepage_sections']);
    }
}
