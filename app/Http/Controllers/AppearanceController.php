<?php

namespace App\Http\Controllers;

use App\Models\HomepageSection;
use App\Support\Access;
use App\Support\Appearance;
use App\Support\Audit;
use App\Support\ClinicSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AppearanceController extends Controller
{
    public function show(Request $request): array
    {
        abort_unless(Access::allows($request->user(), 'settings'), 403);
        $settings = app(ClinicSettings::class);

        return ['configuration' => $settings->appearance(), 'revision' => (int) $settings->get('appearance_revision', 0), 'schema' => Appearance::schema(), 'presets' => Appearance::presets(), 'defaults' => Appearance::defaults(), 'fonts' => Appearance::FONTS, 'sections' => collect(Appearance::sectionKeys())->mapWithKeys(fn ($key) => [$key => Appearance::sectionOptions($key)]), 'homepage' => HomepageSection::orderBy('sort_order')->get(['key', 'enabled', 'sort_order']), 'warnings' => Appearance::warnings($settings->appearance())];
    }

    public function preview(Request $request): array
    {
        abort_unless(Access::allows($request->user(), 'settings'), 403);
        $data = $this->validatedDraft($request);
        $token = (string) Str::uuid();
        $previews = $request->session()->get('appearance_previews', []);
        $previews[$token] = ['configuration' => $data['configuration'], 'homepage' => $data['homepage'] ?? null, 'expires' => now()->addHour()->timestamp, 'user_id' => $request->user()->id];
        $request->session()->put('appearance_previews', array_slice($previews, -4, null, true));

        return ['token' => $token, 'warnings' => Appearance::warnings($data['configuration']), ...$data];
    }

    public function publish(Request $request): array
    {
        abort_unless(Access::allows($request->user(), 'settings'), 403);
        $data = $this->validatedDraft($request, ['revision' => ['required', 'integer', 'min:0']]);
        $warnings = Appearance::warnings($data['configuration']);
        if ($warnings) {
            throw ValidationException::withMessages(['configuration.colors' => $warnings]);
        }
        $revision = DB::transaction(function () use ($data): int {
            $revision = app(ClinicSettings::class)->lockAppearanceRevision();
            abort_if($revision !== (int) $data['revision'], 409, 'Appearance or homepage sections changed in another editor. Reload published settings before saving your draft.');
            app(ClinicSettings::class)->update(['appearance' => $data['configuration'], 'appearance_revision' => $revision + 1]);
            foreach ($data['homepage'] ?? [] as $section) {
                HomepageSection::where('key', $section['key'])->update(['enabled' => $section['enabled'], 'sort_order' => $section['sort_order']]);
            }
            Audit::record('published', 'appearance');

            return $revision + 1;
        });

        return ['message' => 'Appearance published.', 'revision' => $revision, 'configuration' => $data['configuration'], 'homepage' => HomepageSection::orderBy('sort_order')->get(['key', 'enabled', 'sort_order'])];
    }

    private function validatedDraft(Request $request, array $extraRules = []): array
    {
        $data = $request->validate(Appearance::rules() + $this->homepageRules() + $extraRules);
        $data['configuration'] = Appearance::normalize($data['configuration']);
        if (isset($data['homepage'])) {
            $data['homepage'] = array_map(fn (array $section): array => [
                'key' => $section['key'], 'enabled' => (bool) $section['enabled'], 'sort_order' => (int) $section['sort_order'],
            ], $data['homepage']);
        }

        return $data;
    }

    private function homepageRules(): array
    {
        return ['homepage' => ['sometimes', 'array', 'max:15'], 'homepage.*' => ['array:key,enabled,sort_order'], 'homepage.*.key' => ['required', 'string', 'distinct', 'exists:homepage_sections,key'], 'homepage.*.enabled' => ['required', 'boolean'], 'homepage.*.sort_order' => ['required', 'integer', 'distinct', 'between:0,1000']];
    }
}
