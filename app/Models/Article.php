<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

class Article extends ContentModel
{
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function author()
    {
        return $this->belongsTo(Professional::class, 'author_id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return parent::scopePublished($query)->whereNotNull('published_at')->where('published_at', '<=', now());
    }
}
