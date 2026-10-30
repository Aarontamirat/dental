@props(['name' => 'arrow', 'size' => 24])
<svg {{ $attributes }} width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
@switch($name)
@case('tooth')<path d="M12 5c-2-2-6-3-8 0-2 3 1 7 2 12 .5 3 2 4 3 1l1-4c.5-2 3-2 4 0l1 4c1 3 2.5 2 3-1 1-5 4-9 2-12-2-3-6-2-8 0Z"/><path d="M9 4c1 1 2 2 4 2"/>@break
@case('sparkles')<path d="m12 3 2.5 6.5L21 12l-6.5 2.5L12 21l-2.5-6.5L3 12l6.5-2.5L12 3Z"/><path d="m20 2 1 2 2 1-2 1-1 2-1-2-2-1 2-1Z"/>@break
@case('shield')<path d="m12 3 8 3v6c0 5-8 9-8 9s-8-4-8-9V6l8-3Z"/><path d="m8 12 3 3 5-6"/>@break
@case('smile')<circle cx="12" cy="12" r="9"/><path d="M8 14s1 3 4 3 4-3 4-3M8 8h.01M16 8h.01"/>@break
@case('heart')<path d="M20 5c-3-3-6-1-8 1-2-2-5-4-8-1-3 3-1 6 1 8l7 7 7-7c2-2 4-5 1-8Z"/>@break
@case('scan')<path d="M8 3H3v5M16 3h5v5M21 16v5h-5M8 21H3v-5M3 12h18"/><path d="M8 7h8v10H8z"/>@break
@case('phone')<path d="m5 3 4 1 1 4-3 2a15 15 0 0 0 7 7l2-3 4 1 1 4c-3 6-18-5-18-13Z"/>@break
@case('pin')<path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>@break
@case('clock')<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>@break
@case('check')<path d="m5 12 4 4L19 6"/>@break
@case('menu')<path d="M4 6h16M4 12h16M4 18h16"/>@break
@case('close')<path d="m6 6 12 12M6 18 18 6"/>@break
@case('plus')<path d="M12 5v14M5 12h14"/>@break
@case('mail')<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 6 9 7 9-7"/>@break
@case('globe')<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c-5 5-5 13 0 18 5-5 5-13 0-18Z"/>@break
@default<path d="M4 12h16m-6-6 6 6-6 6"/>
@endswitch
</svg>
