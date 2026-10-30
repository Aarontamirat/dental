<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomepageSection extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean', 'translations' => 'array'];
    }

    public function translated(string $key)
    {
        return ($this->translations[app()->getLocale()][$key] ?? null) ?: $this->$key;
    }
}
