<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    protected $table = 'media';

    protected $guarded = ['id'];

    protected $appends = ['url'];

    public function getUrlAttribute(): string
    {
        return '/media/'.$this->id;
    }

    protected $hidden = ['path', 'original_path'];
}
