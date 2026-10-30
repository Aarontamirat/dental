<?php

namespace App\Models;

class GalleryItem extends ContentModel
{
    public function scopePublishable($query)
    {
        return $query->published()->where(fn ($q) => $q->whereIn('kind', ['clinic', 'equipment'])->orWhere('consent', true));
    }
}
