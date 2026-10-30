<?php

namespace App\Models;

class Service extends ContentModel
{
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function branches()
    {
        return $this->belongsToMany(Branch::class);
    }

    public function related()
    {
        return $this->belongsToMany(self::class, 'related_service', 'service_id', 'related_id');
    }

    public function gallery()
    {
        return $this->hasMany(GalleryItem::class);
    }
}
