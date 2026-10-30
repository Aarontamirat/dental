@props(['href', 'variant' => 'primary', 'arrow' => true])
<a href="{{ $href }}" {{ $attributes->class(['button', 'button-'.$variant]) }}>{{ $slot }}@if($arrow)<x-icon name="arrow" :size="18"/>@endif</a>
