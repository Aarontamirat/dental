<?php

namespace App\Models;

use App\Support\ClinicSettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

abstract class ContentModel extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['translations' => 'array', 'featured' => 'boolean', 'consent' => 'boolean', 'approved' => 'boolean', 'is_demo' => 'boolean', 'benefits' => 'array', 'procedure' => 'array', 'faq' => 'array', 'qualifications' => 'array', 'specializations' => 'array', 'languages' => 'array', 'affiliations' => 'array', 'links' => 'array', 'hours' => 'array', 'published_at' => 'datetime', 'price' => 'decimal:2'];
    }

    public function translated(string $field): mixed
    {
        $locale = app()->getLocale();

        return ($locale !== 'en' ? ($this->translations[$locale][$field] ?? null) : null) ?: $this->getAttribute($field);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('status'), 'published')->when(! app(ClinicSettings::class)->get('demo_mode'), fn ($q) => $q->where($this->qualifyColumn('is_demo'), false));
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
