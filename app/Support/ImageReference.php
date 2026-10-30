<?php

namespace App\Support;

use App\Models\Media;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ImageReference implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (preg_match('#^/media/([0-9]+)$#', (string) $value, $matches) && Media::whereKey($matches[1])->exists()) {
            return;
        }
        if (preg_match('#^/images/[a-zA-Z0-9_-]+\.(webp|jpg|jpeg|png)$#', (string) $value) && is_file(public_path(ltrim($value, '/')))) {
            return;
        }
        $fail('Choose an existing image from the media library or bundled clinic images.');
    }
}
