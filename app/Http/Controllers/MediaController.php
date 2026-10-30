<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Support\Access;
use App\Support\Audit;
use App\Support\ClinicSettings;
use App\Support\ContentRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MediaController extends Controller
{
    public function serve(Media $media)
    {
        abort_unless(Storage::disk('local')->exists($media->path), 404);

        return response()->file(Storage::disk('local')->path($media->path), ['Content-Type' => $media->mime, 'Cache-Control' => 'public, max-age=3600', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function index(Request $request)
    {
        abort_unless(Access::allows($request->user(), 'media'), 403);
        $request->validate(['q' => 'nullable|string|max:200', 'category' => 'nullable|string|max:50']);

        return Media::when($request->q, fn ($q) => $q->where('title', 'like', '%'.$request->q.'%'))->when($request->category, fn ($q) => $q->where('category', $request->category))->latest()->paginate(18);
    }

    public function save(Request $request, ?Media $media = null)
    {
        abort_unless(Access::allows($request->user(), 'media'), 403);
        $data = $request->validate(['file' => [$media ? 'nullable' : 'required', 'file', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=6000,max_height=6000'], 'title' => 'required|string|max:255', 'alt' => 'required|string|max:255', 'category' => 'required|string|max:50']);
        $oldPaths = $media ? [$media->path, $media->original_path] : [];
        $attributes = array_diff_key($data, array_flip(['file']));
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            [$width,$height] = getimagesize($file->getRealPath());
            if ($width * $height > 20000000) {
                throw ValidationException::withMessages(['file' => 'The image is too large. Limit it to 20 megapixels.']);
            }
            $image = @imagecreatefromstring(file_get_contents($file->getRealPath()));
            if (! $image) {
                throw ValidationException::withMessages(['file' => 'Upload a valid JPEG, PNG or WebP image.']);
            }
            $scale = min(1, 1600 / max($width, $height));
            $out = imagecreatetruecolor((int) round($width * $scale), (int) round($height * $scale));
            imagealphablending($out, false);
            imagesavealpha($out, true);
            imagecopyresampled($out, $image, 0, 0, 0, 0, imagesx($out), imagesy($out), $width, $height);
            $path = 'media/'.now()->format('Y/m').'/'.Str::uuid().'.webp';
            $original = $file->store('originals/'.now()->format('Y/m'), 'local');
            ob_start();
            imagewebp($out, null, 84);
            $bytes = ob_get_clean();
            Storage::disk('local')->put($path, $bytes);
            $attributes += ['path' => $path, 'original_path' => $original, 'mime' => 'image/webp', 'size' => strlen($bytes), 'width' => imagesx($out), 'height' => imagesy($out), 'uploaded_by' => auth()->id()];
        }
        try {
            $media?->update($attributes);
            if (! $media) {
                $media = Media::create($attributes);
            }
        } catch (\Throwable $e) {
            if (isset($path)) {
                Storage::disk('local')->delete([$path, $original]);
            } throw $e;
        }
        if ($request->hasFile('file')) {
            Storage::disk('local')->delete(array_filter($oldPaths));
        }
        Audit::record('saved', 'media', $media->id);

        return ['message' => 'Image saved.', 'record' => $media];
    }

    public function destroy(Request $request, Media $media)
    {
        abort_unless(Access::allows($request->user(), 'media'), 403);
        $url = '/media/'.$media->id;
        foreach (app(ClinicSettings::class)->all() as $value) {
            if (is_string($value) && $value === $url) {
                abort(409, 'This image is used in website settings. Replace it first.');
            }
        }
        foreach (['services', 'professionals', 'branches', 'articles', 'pages', 'gallery', 'testimonials', 'faqs', 'categories'] as $key) {
            $model = ContentRegistry::get($key)['model'];
            $query = $model::where('image', $url);
            if ($key === 'gallery') {
                $query->orWhere('after_image', $url);
            }
            if ($query->exists()) {
                abort(409, 'This image is used by content. Replace it first.');
            }
        }
        $paths = array_filter([$media->path, $media->original_path]);
        $media->delete();
        Storage::disk('local')->delete($paths);
        Audit::record('deleted','media',$media->id);

        return ['message' => 'Image deleted.'];
    }
}
