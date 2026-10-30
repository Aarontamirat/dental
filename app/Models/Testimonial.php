<?php

namespace App\Models;

use App\Support\ClinicSettings;

class Testimonial extends ContentModel
{
    public function scopePublishable($query)
    {
        return $query->published()->where('approved', true)->where('consent', true)->when(! app(ClinicSettings::class)->get('demo_mode'), fn ($q) => $q->where('is_demo', false));
    }
}
