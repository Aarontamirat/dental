<?php

namespace App\Support;

final class PhoneNumber
{
    public static function international(?string $phone): string
    {
        $clean = preg_replace('/[\s()-]/', '', $phone ?? '');
        if (preg_match('/^0([79][0-9]{8})$/', $clean, $m)) {
            return '+251'.$m[1];
        }

return $clean;
    }
}
