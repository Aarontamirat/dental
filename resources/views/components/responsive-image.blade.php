@props(['src', 'alt' => '', 'sizes' => '100vw'])
<img src="{{ $src }}" alt="{{ $alt }}" {{ $attributes }} @if($srcset = \App\Support\ResponsiveImage::srcset($src))srcset="{{ $srcset }}" sizes="{{ $sizes }}"@endif>
