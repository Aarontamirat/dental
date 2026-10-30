<?php

namespace Tests\Unit;

use App\Support\BrandContrast;
use PHPUnit\Framework\TestCase;

class BrandContrastTest extends TestCase
{
    public function test_brand_contrast_uses_standard_relative_luminance(): void
    {
        $this->assertEqualsWithDelta(21, BrandContrast::ratio('#000000', '#ffffff'), .001);
        $this->assertSame(1.0, BrandContrast::ratio('#ffffff', '#ffffff'));
        $this->assertGreaterThan(4.5, BrandContrast::ratio('#176d67', '#ffffff'));
    }
}
