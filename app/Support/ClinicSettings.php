<?php

namespace App\Support;

use App\Models\HomepageSection;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class ClinicSettings
{
    private ?array $values = null;

    private ?array $previewAppearance = null;

    private ?array $resolvedAppearance = null;

    private ?array $previewHomepage = null;

    public function homepageSections(): Collection
    {
        $sections = HomepageSection::orderBy('sort_order')->get();
        if ($this->previewHomepage !== null) {
            $overrides = collect($this->previewHomepage)->keyBy('key');
            foreach ($sections as $section) {
                if ($overrides->has($section->key)) {
                    $section->enabled = (bool) $overrides[$section->key]['enabled'];
                    $section->sort_order = $overrides[$section->key]['sort_order'];
                }
            }
            $sections = $sections->sortBy('sort_order');
        }

        return $sections->where('enabled', true)->values();
    }

    public function appearance(): array
    {
        return $this->previewAppearance ?? ($this->resolvedAppearance ??= Appearance::resolve($this->all()));
    }

    public function previewAppearance(array $configuration, ?array $homepage = null): void
    {
        $this->previewAppearance = $configuration;
        $this->previewHomepage = $homepage;
    }

    public function clearPreview(): void
    {
        $this->previewAppearance = null;
        $this->previewHomepage = null;
    }

    public function lockAppearanceRevision(): int
    {
        DB::table('settings')->insertOrIgnore(['key' => 'appearance_revision', 'value' => '0', 'updated_at' => now()]);
        $row = DB::table('settings')->where('key', 'appearance_revision')->lockForUpdate()->first();

        return (int) json_decode($row->value, true);
    }

    public function sectionAttributes(string $key): string
    {
        $appearance = $this->appearance();
        $attributes = 'data-section="'.e($key).'"';
        foreach ($appearance['sections'][$key] ?? [] as $name => $value) {
            $attributes .= ' data-section-'.e($name).'="'.e($value).'"';
        }

        return $attributes;
    }

    public function all(): array
    {
        if ($this->values === null) {
            $overrides = DB::table('settings')->pluck('value', 'key')->map(fn ($v) => json_decode($v, true))->all();
            $this->values = array_replace(config('clinic'), $overrides);
        }

        return $this->values;
    }

    public function get(string $key, mixed $fallback = null): mixed
    {
        $settings = $this->all();
        if (app()->getLocale() !== 'en' && filled($settings['translations'][app()->getLocale()][$key] ?? null)) {
            return $settings['translations'][app()->getLocale()][$key];
        }

        return $settings[$key] ?? $fallback;
    }

    public function enabled(string $module): bool
    {
        return in_array($module, $this->get('modules', []), true);
    }

    public function update(array $values): void
    {
        foreach ($values as $key => $value) {
            DB::table('settings')->updateOrInsert(['key' => $key], ['value' => json_encode($value, JSON_UNESCAPED_UNICODE), 'updated_at' => now()]);
        }
        $this->values = null;
        $this->resolvedAppearance = null;
    }

    public function publicValues(): array
    {
        return array_diff_key($this->all(), array_flip(['notification_email', 'email_enabled', 'retention_days']));
    }
}
