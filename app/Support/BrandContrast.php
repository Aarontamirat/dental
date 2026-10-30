<?php

namespace App\Support;

final class BrandContrast
{
    private static function luminance(string $hex): float
    {
        $rgb = array_map(fn ($channel) => hexdec($channel) / 255, str_split(ltrim($hex, '#'), 2));
        $values = array_map(fn ($v) => $v <= .04045 ? $v / 12.92 : (($v + .055) / 1.055) ** 2.4, $rgb);

        return .2126 * $values[0] + .7152 * $values[1] + .0722 * $values[2];
    }

    public static function ratio(string $a, string $b): float
    {
        $x = self::luminance($a);
        $y = self::luminance($b);

        return (max($x, $y) + .05) / (min($x, $y) + .05);
    }
}
