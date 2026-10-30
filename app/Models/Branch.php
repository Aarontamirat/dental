<?php

namespace App\Models;

class Branch extends ContentModel
{
    public function services()
    {
        return $this->belongsToMany(Service::class);
    }

    public function professionals()
    {
        return $this->belongsToMany(Professional::class);
    }
}
