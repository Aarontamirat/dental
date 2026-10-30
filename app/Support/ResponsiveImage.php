<?php

namespace App\Support;

final class ResponsiveImage
{
    public static function srcset(string $source): ?string
    {
        if (! preg_match('#^/images/[a-zA-Z0-9_-]+\.webp$#', $source)) {
            return null;
        }
        $path = public_path(ltrim($source, '/'));
        if (! is_file($path)) {
            return null;
        }
        $variants = [];
        foreach ([480, 960] as $width) {
            $url = substr($source, 0, -5).'-'.$width.'.webp';
            if (is_file(public_path(ltrim($url, '/')))) {
                $variants[] = $url.' '.$width.'w';
            }
        }
        if (! $variants) {
            return null;
        }
        $variants[] = $source.' '.getimagesize($path)[0].'w';

        return implode(', ', $variants);
    }
}
