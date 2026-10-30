@php
$locale = app()->getLocale();
$appearance = $clinic->appearance();
$pageTitle = trim($__env->yieldContent('title')) ?: $clinic->get('seo_title');
$description = trim($__env->yieldContent('description')) ?: $clinic->get('seo_description');
$canonical = url()->current().(count($clinic->get('languages')) > 1 ? '?lang='.$locale : '');
$pageSlugs = \App\Models\Page::published()->pluck('slug');
$nav = \App\Models\NavigationItem::where('enabled', true)->orderBy('sort_order')->get()->filter(function ($item) use ($clinic,$pageSlugs) {
    if (!str_starts_with($item->url,'/')) { return true; }
    if (str_starts_with($item->url,'/pages/')) { return $pageSlugs->contains(basename(parse_url($item->url,PHP_URL_PATH))); }
    $module = explode('/', trim(parse_url($item->url, PHP_URL_PATH) ?? '', '/'))[0];
    return !in_array($module, ['services','doctors','articles','branches','gallery','testimonials']) || $clinic->enabled($module);
});
@endphp
<!DOCTYPE html><html lang="{{ $locale }}"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $pageTitle }} | {{ $clinic->get('name') }}</title><meta name="description" content="{{ $description }}"><link rel="canonical" href="{{ $canonical }}">
@foreach($clinic->get('languages') as $language)<link rel="alternate" hreflang="{{ $language }}" href="{{ url()->current().(count($clinic->get('languages')) > 1 ? '?lang='.$language : '') }}">@endforeach
<meta property="og:type" content="@yield('og_type', 'website')"><meta property="og:title" content="{{ $pageTitle }} | {{ $clinic->get('name') }}"><meta property="og:description" content="{{ $description }}"><meta property="og:url" content="{{ $canonical }}"><meta property="og:image" content="{{ url($clinic->get('hero_image')) }}"><meta property="og:locale" content="{{ $locale === 'am' ? 'am_ET' : 'en_ET' }}"><meta name="twitter:card" content="summary_large_image">
<meta name="theme-color" content="{{ $appearance['colors'][$appearance['mode'] === 'dark' ? 'dark' : 'light']['background'] }}"><link rel="icon" href="{{ $clinic->get('favicon') ?: '/favicon.svg' }}">
<link rel="stylesheet" href="/fonts/fonts.css">@vite(['resources/css/app.css','resources/js/public.ts'])
<style id="appearance-tokens">{!! \App\Support\Appearance::css($appearance) !!}</style>
@if(!$clinic->get('demo_mode'))
<script type="application/ld+json" nonce="{{ app('csp_nonce') }}">{!! json_encode(['@'.'context'=>'https://schema.org','@type'=>'Dentist','name'=>$clinic->get('name'),'url'=>url('/'),'telephone'=>$clinic->get('phone'),'image'=>url($clinic->get('hero_image')),'address'=>['@type'=>'PostalAddress','streetAddress'=>$clinic->get('address'),'addressCountry'=>'ET']], JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE) !!}</script>
@endif
@stack('head')
</head><body data-page="{{ request()->segment(1) ?: 'home' }}" data-kind="@yield('kind')" data-mode="{{ $appearance['mode'] }}" @foreach(['layout','buttons','cards','header','footer','motion'] as $group) @foreach($appearance[$group] as $key=>$value) @if(is_bool($value) || is_string($value)) data-{{ $group }}-{{ str_replace('_','-',$key) }}="{{ is_bool($value) ? ($value ? 'true' : 'false') : $value }}" @endif @endforeach @endforeach>
@if(request()->attributes->get('appearance_preview'))<div class="preview-notice" role="status">{{ app()->getLocale() === 'am' ? 'የግል ቅድመ እይታ' : 'Private appearance preview · unpublished · forms disabled' }}</div>@endif
<a class="skip-link" href="#main">{{ __('ui.skip') }}</a>
@if($clinic->get('demo_mode'))<div class="demo-strip"><span class="demo-dot"></span>{{ __('ui.demo') }}</div>@endif
<header class="site-header"><div class="container header-inner">
<a class="brand" href="/">@if($clinic->get('logo'))<img src="{{ $clinic->get('logo') }}" alt="{{ $clinic->get('name') }}" class="brand-logo">@else<span class="brand-symbol"><x-icon name="tooth" :size="28"/></span><span>{{ $clinic->get('name') }}<small>{{ __('ui.demo_profile') === 'Demonstration profile · stock photograph' ? 'DENTAL CARE' : 'የጥርስ እንክብካቤ' }}</small></span>@endif</a>
<nav class="desktop-nav" aria-label="{{ __('ui.explore') }}">@foreach($nav->where('location','header') as $item)<a href="{{ $item->url }}" @if(request()->is(ltrim($item->url,'/').'*'))aria-current="page"@endif>{{ $item->translated('title') }}</a>@endforeach</nav>
<div class="header-actions">@if(count($clinic->get('languages')) > 1)<div class="language-switch" aria-label="Language">@foreach($clinic->get('languages') as $language)<a href="{{ request()->fullUrlWithQuery(['lang'=>$language]) }}" lang="{{ $language }}" title="{{ $language === 'en' ? 'English' : 'አማርኛ · Amharic' }}" @if($locale === $language)aria-current="true"@endif>{{ $language === 'en' ? 'EN' : ($locale === 'en' ? 'AM' : 'አማ') }}</a>@endforeach</div>@endif
@if($clinic->get('appointment_enabled'))<x-link href="/appointments" class="header-book" :arrow="false">{{ $clinic->get('appointment_cta') }}</x-link>@endif
<button class="menu-toggle" aria-controls="mobile-menu" aria-expanded="false" aria-label="{{ __('ui.explore') }}"><x-icon name="menu"/></button></div>
</div><nav id="mobile-menu" class="mobile-nav" hidden aria-label="{{ __('ui.explore') }}">@foreach($nav->where('location','header') as $item)<a href="{{ $item->url }}" @if(request()->is(ltrim($item->url,'/').'*')) aria-current="page" @endif>{{ $item->translated('title') }}</a>@endforeach @if($clinic->get('appointment_enabled'))<a href="/appointments">{{ $clinic->get('appointment_cta') }}</a>@endif</nav><noscript><nav class="nojs-nav" aria-label="{{ __('ui.explore') }}">@foreach($nav->where('location','header') as $item)<a href="{{ $item->url }}">{{ $item->translated('title') }}</a>@endforeach</nav></noscript></header>
<main id="main">@yield('content')</main>
<footer class="site-footer"><div class="container"><div class="footer-top"><div class="footer-brand"><a class="brand" href="/">@if($clinic->get('logo'))<img src="{{ $clinic->get('logo') }}" alt="{{ $clinic->get('name') }}" class="brand-logo">@else<span class="brand-symbol"><x-icon name="tooth" :size="28"/></span><span>{{ $clinic->get('name') }}</span>@endif</a><p>{{ __('ui.footer_intro') }}</p><span class="footer-tagline">{{ $clinic->get('tagline') }}</span></div>
<div class="footer-column"><h3>{{ __('ui.explore') }}</h3><a href="/about">{{ __('ui.about') }}</a>@foreach(['services'=>'services','doctors'=>'team','gallery'=>'gallery','branches'=>'locations'] as $module=>$label)@if($clinic->enabled($module))<a href="/{{ $module }}">{{ __('ui.'.$label) }}</a>@endif @endforeach</div>
<div class="footer-column"><h3>{{ __('ui.patient_info') }}</h3><a href="/faq">{{ __('ui.faq') }}</a>@if($clinic->enabled('articles'))<a href="/articles">{{ __('ui.resources') }}</a>@endif @if($pageSlugs->contains('payment-information'))<a href="/pages/payment-information">{{ __('ui.payment') }}</a>@endif<a href="/contact">{{ __('ui.contact') }}</a></div>
<div class="footer-column footer-contact"><h3>{{ __('ui.find_us') }}</h3><p>{{ $clinic->get('address') }}</p>@if($clinic->get('phone'))<a href="tel:{{ preg_replace('/[^+0-9]/','',$clinic->get('phone')) }}">{{ $clinic->get('phone') }}</a>@endif @if($clinic->get('email'))<a href="mailto:{{ $clinic->get('email') }}">{{ $clinic->get('email') }}</a>@endif @foreach($clinic->get('social_links',[]) as $link)<a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer">{{ $link['label'] }} ↗</a>@endforeach</div></div>
<div class="footer-bottom"><p>© {{ now()->year }} {{ $clinic->get('name') }}. {{ $appearance['footer']['copyright'] ?: __('ui.copyright') }}</p><nav>@foreach($nav->where('location','footer') as $item)<a href="{{ $item->url }}">{{ $item->translated('title') }}</a>@endforeach<a href="/login">{{ __('ui.admin') }}</a></nav></div></div></footer>
@if($clinic->get('whatsapp'))<a class="whatsapp-float" href="https://wa.me/{{ preg_replace('/[^0-9]/','',$clinic->get('whatsapp')) }}" target="_blank" rel="noopener noreferrer" aria-label="{{ __('ui.whatsapp') }}"><x-icon name="phone"/></a>@endif
@stack('scripts')
</body></html>
