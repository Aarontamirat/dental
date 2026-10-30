<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Support\ClinicSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\ClinicTestCase;

class MediaTest extends ClinicTestCase
{
    public function test_images_are_optimized_and_originals_stay_private(): void
    {
        Storage::fake('local');
        $this->actingAs($this->user());
        $response = $this->post('/admin/api/media', ['title' => 'Clinic', 'alt' => 'Clinic reception', 'category' => 'clinic', 'file' => UploadedFile::fake()->image('clinic.jpg', 2000, 1000)], ['Accept' => 'application/json'])->assertOk();
        $media = Media::firstOrFail();
        $this->assertSame('image/webp', $media->mime);
        $this->assertSame(1600, $media->width);
        $this->assertSame(800, $media->height);
        Storage::disk('local')->assertExists([$media->path, $media->original_path]);
        $response->assertJsonMissingPath('record.path')->assertJsonMissingPath('record.original_path');
        $this->get('/media/'.$media->id)->assertOk()->assertHeader('Content-Type', 'image/webp');
    }

    public function test_uploads_reject_svg_executables_oversized_files_and_missing_alt_text(): void
    {
        Storage::fake('local');
        $this->actingAs($this->user());
        foreach ([UploadedFile::fake()->createWithContent('evil.svg', '<svg onload="alert(1)"></svg>'), UploadedFile::fake()->createWithContent('evil.php', '<?php echo 1;'), UploadedFile::fake()->image('spoof.php'), UploadedFile::fake()->create('large.jpg', 6000, 'image/jpeg')] as $file) {
            $this->post('/admin/api/media', ['title' => 'Bad image', 'alt' => 'Description', 'category' => 'general', 'file' => $file], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('file');
        }
        $this->post('/admin/api/media', ['title' => 'No alt', 'category' => 'general', 'file' => UploadedFile::fake()->image('image.png')], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('alt');
    }

    public function test_image_replacement_keeps_its_url_and_in_use_images_cannot_be_deleted(): void
    {
        Storage::fake('local');
        $this->actingAs($this->user());
        $this->post('/admin/api/media', ['title' => 'Photo', 'alt' => 'Clinic photo', 'category' => 'clinic', 'file' => UploadedFile::fake()->image('photo.png')], ['Accept' => 'application/json'])->assertOk();
        $media = Media::first();
        $old = $media->path;
        $this->post('/admin/api/media/'.$media->id, ['title' => 'Replacement', 'alt' => 'Replacement clinic photo', 'category' => 'clinic', 'file' => UploadedFile::fake()->image('replacement.png')], ['Accept' => 'application/json'])->assertOk();
        $this->assertSame('/media/'.$media->id, $media->fresh()->url);
        Storage::disk('local')->assertMissing($old);
        app(ClinicSettings::class)->update(['logo' => $media->url]);
        $this->deleteJson('/admin/api/media/'.$media->id)->assertConflict();
        app(ClinicSettings::class)->update(['logo' => null]);
        $this->deleteJson('/admin/api/media/'.$media->id)->assertOk();
        $this->assertDatabaseCount('media',0);
    }
}
