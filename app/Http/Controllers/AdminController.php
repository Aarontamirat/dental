<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Article;
use App\Models\Branch;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\HomepageSection;
use App\Models\Professional;
use App\Models\Service;
use App\Models\Tag;
use App\Support\Access;
use App\Support\Audit;
use App\Support\BrandContrast;
use App\Support\ClinicSettings;
use App\Support\ContentRegistry;
use App\Support\PhoneNumber;
use App\Support\SettingsRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    public function bootstrap(Request $request)
    {
        $modules = [];
        foreach (ContentRegistry::modules() as $key => $config) {
            if (Access::allows($request->user(), $config['ability'])) {
                $modules[$key] = array_diff_key($config, array_flip(['model']));
            }
        }
        $options = [];
        if (Access::allows($request->user(), 'content') || Access::allows($request->user(), 'appointments')) {
            foreach (['services' => Service::class, 'branches' => Branch::class, 'professionals' => Professional::class, 'tags' => Tag::class] as $key => $model) {
                $options[$key] = $model::orderBy('title')->get(['id', 'title']);
            }
            foreach (['service', 'article', 'gallery'] as $type) {
                $options[$type.'_categories'] = Category::where('type', $type)->orderBy('title')->get(['id', 'title']);
            }
        }

        return ['user' => $request->user()->only(['id', 'name', 'email', 'role']), 'abilities' => array_values(array_filter(['content', 'media', 'settings', 'appointments', 'messages', 'users', 'audit'], fn ($a) => Access::allows($request->user(), $a))),
            'modules' => $modules, 'options' => $options, 'settings_fields' => Access::allows($request->user(), 'settings') ? SettingsRegistry::fields() : [], 'clinic_name' => app(ClinicSettings::class)->get('name')];
    }

    public function dashboard(Request $request)
    {
        $data = ['metrics' => [], 'appointments' => [], 'activities' => []];
        if (Access::allows($request->user(), 'appointments')) {
            $data['metrics'] = ['Total requests' => Appointment::count(), 'Pending requests' => Appointment::where('status', 'pending')->count(), 'Confirmed visits' => Appointment::where('status', 'confirmed')->count(), 'New messages' => ContactMessage::where('status', 'new')->count()];
            $data['appointments'] = Appointment::with(['branch:id,title', 'service:id,title'])->latest()->limit(6)->get();
        }
        if (Access::allows($request->user(), 'content')) {
            $data['metrics'] += ['Published services' => Service::published()->count(), 'Published articles' => Article::published()->count()];
        }
        if (Access::allows($request->user(), 'audit')) {
            $data['activities'] = DB::table('activity_logs')->leftJoin('users', 'users.id', '=', 'activity_logs.user_id')->select('activity_logs.*', 'users.name as user_name')->orderByDesc('activity_logs.id')->limit(8)->get();
        }

        return $data;
    }

    public function settings(Request $request)
    {
        abort_unless(Access::allows($request->user(), 'settings'), 403);

        return app(ClinicSettings::class)->all();
    }

    public function updateSettings(Request $request)
    {
        abort_unless(Access::allows($request->user(), 'settings'), 403);
        if ($request->filled('whatsapp')) {
            $request->merge(['whatsapp' => PhoneNumber::international($request->whatsapp)]);
        } $data = $request->validate(SettingsRegistry::rules());
        if (! in_array($data['locale'], $data['languages'], true)) {
            throw ValidationException::withMessages(['locale' => 'The default language must be enabled.']);
        }
        if ($data['appointment_start'] >= $data['appointment_end']) {
            throw ValidationException::withMessages(['appointment_end' => 'Closing time must be after opening time.']);
        }
        if (BrandContrast::ratio($data['primary_color'], '#ffffff') < 4.5) {
            throw ValidationException::withMessages(['primary_color' => 'Choose a darker primary color so white button text has accessible contrast.']);
        }
        if (BrandContrast::ratio($data['secondary_color'], '#ffffff') < 7 || BrandContrast::ratio($data['primary_color'], $data['background_color']) < 4.5 || BrandContrast::ratio('#203c39', $data['background_color']) < 7) {
            throw ValidationException::withMessages(['background_color' => 'Use a light background and dark brand colors to preserve readable contrast.']);
        }
        DB::transaction(function () use ($data) {
            app(ClinicSettings::class)->update($data);
            Audit::record('updated', 'settings');
        });

        return ['message' => 'Website settings saved.'];
    }

    public function sections(Request $request)
    {
        abort_unless(Access::allows($request->user(), 'content'), 403);
        $settings = app(ClinicSettings::class)->all();

        return HomepageSection::orderBy('sort_order')->get()->map(function ($section) use ($settings) {
            if (in_array($section->key, ['hero', 'about'], true)) {
                $section->title = $settings[$section->key.'_title'];
                $section->subtitle = $settings[$section->key.'_description'];
                $section->translations = ['am' => [
                    'title' => $settings['translations']['am'][$section->key.'_title'] ?? '',
                    'subtitle' => $settings['translations']['am'][$section->key.'_description'] ?? '',
                ]];
            }

            return $section;
        });
    }

    public function updateSections(Request $request)
    {
        abort_unless(Access::allows($request->user(), 'content'), 403);
        $data = $request->validate(['sections' => 'required|array', 'sections.*' => 'array:id,title,subtitle,enabled,sort_order,translations', 'sections.*.id' => 'required|integer|distinct|exists:homepage_sections,id', 'sections.*.title' => 'required|string|max:255', 'sections.*.subtitle' => 'nullable|string|max:1000', 'sections.*.enabled' => 'required|boolean', 'sections.*.sort_order' => 'required|integer|between:0,1000', 'sections.*.translations' => 'nullable|array:am', 'sections.*.translations.am' => 'nullable|array:title,subtitle', 'sections.*.translations.am.title' => 'nullable|string|max:255', 'sections.*.translations.am.subtitle' => 'nullable|string|max:1000']);
        DB::transaction(function () use ($data) {
            $settings = app(ClinicSettings::class);
            $revision = $settings->lockAppearanceRevision();
            $translations = $settings->all()['translations'] ?? [];
            $updates = [];
            foreach ($data['sections'] as $row) {
                $section = HomepageSection::findOrFail($row['id']);
                $section->update(array_diff_key($row, array_flip(['id'])));
                if (in_array($section->key, ['hero', 'about'], true)) {
                    $updates[$section->key.'_title'] = $row['title'];
                    $updates[$section->key.'_description'] = $row['subtitle'] ?? '';
                    if (array_key_exists('translations', $row)) {
                        $translations['am'][$section->key.'_title'] = $row['translations']['am']['title'] ?? '';
                        $translations['am'][$section->key.'_description'] = $row['translations']['am']['subtitle'] ?? '';
                    }
                }
            }
            $settings->update([...$updates, 'translations' => $translations, 'appearance_revision' => $revision + 1]);
            Audit::record('updated', 'homepage_sections');
        });

        return ['message' => 'Homepage updated.'];
    }

    public function activities(Request $request)
    {
        abort_unless(Access::allows($request->user(), 'audit'), 403);

        return DB::table('activity_logs')->leftJoin('users', 'users.id', '=', 'activity_logs.user_id')->select('activity_logs.*', 'users.name as user_name')->orderByDesc('activity_logs.id')->paginate(25);
    }
}
