<?php

namespace App\Models;

class Professional extends ContentModel
{
    public function branches()
    {
        return $this->belongsToMany(Branch::class);
    }
}
