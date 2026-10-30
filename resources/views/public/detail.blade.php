@extends('layouts.public')
@section('kind',$kind)
@section('title',$item->translated('seo_title') ?: $item->translated('title'))
@section('description',$item->translated('seo_description') ?: ($item->translated('excerpt') ?: $clinic->get('seo_description')))
@section('og_type',$kind === 'articles' ? 'article' : 'website')
@push('head')
<script type="application/ld+json" nonce="{{ app('csp_nonce') }}">{!! json_encode(['@'.'context'=>'https://schema.org','@type'=>'BreadcrumbList','itemListElement'=>[['@type'=>'ListItem','position'=>1,'name'=>__('ui.home'),'item'=>url('/')],['@type'=>'ListItem','position'=>2,'name'=>$item->translated('title'),'item'=>url()->current()]]],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE) !!}</script>
@if($kind === 'articles')<script type="application/ld+json" nonce="{{ app('csp_nonce') }}">{!! json_encode(['@'.'context'=>'https://schema.org','@type'=>'Article','headline'=>$item->translated('title'),'description'=>$item->translated('excerpt'),'datePublished'=>$item->published_at->toIso8601String(),'dateModified'=>$item->updated_at->toIso8601String(),'image'=>$item->image?url($item->image):null,'publisher'=>['@type'=>'Organization','name'=>$clinic->get('name')]],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE) !!}</script>@endif
@if(!$item->is_demo && in_array($kind,['services','doctors','branches']))
@php
$entity = match($kind) {
    'services' => ['@type'=>'Service','name'=>$item->translated('title'),'description'=>$item->translated('excerpt'),'provider'=>['@type'=>'Dentist','name'=>$clinic->get('name')]],
    'doctors' => ['@type'=>'Person','name'=>$item->translated('title'),'jobTitle'=>$item->professional_title,'description'=>$item->translated('excerpt')],
    'branches' => ['@type'=>'Dentist','name'=>$item->translated('title'),'telephone'=>$item->phone,'address'=>['@type'=>'PostalAddress','streetAddress'=>$item->address,'addressLocality'=>$item->city,'addressRegion'=>$item->region,'addressCountry'=>'ET']],
};
$entity = ['@'.'context'=>'https://schema.org','url'=>url()->current(), ...$entity];
if($item->image) $entity['image']=url($item->image);
@endphp
<script type="application/ld+json" nonce="{{ app('csp_nonce') }}">{!! json_encode($entity,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE) !!}</script>
@endif
@endpush
@section('content')
<section class="page-intro detail-intro"><div class="container"><nav class="breadcrumbs"><a href="/">{{ __('ui.home') }}</a><span>/</span>@if($kind !== 'pages')<a href="/{{ $kind }}">{{ __('ui.'.['services'=>'services','doctors'=>'team','articles'=>'resources','branches'=>'locations'][$kind]) }}</a><span>/</span>@endif<span>{{ $item->translated('title') }}</span></nav>
<p class="eyebrow"><span></span>{{ $kind === 'services' ? __('ui.our_services') : ($kind === 'doctors' ? ($item->professional_title ?: __('ui.our_team')) : ($kind === 'articles' ? __('ui.patient_resources') : __('ui.about_eyebrow'))) }}</p><h1>{{ $item->translated('title') }}</h1>@if($item->excerpt)<p>{{ $item->translated('excerpt') }}</p>@endif
@if($kind === 'articles')<div class="article-meta"><time datetime="{{ $item->published_at->toIso8601String() }}">{{ __('ui.published') }} {{ $item->published_at->format('d M Y') }}</time>@if($item->author)<span>{{ $item->author->translated('title') }}</span>@endif</div>@endif
@if($kind === 'doctors' && $clinic->get('demo_mode'))<span class="demo-caption">{{ __('ui.demo_profile') }}</span>@endif</div></section>
<section class="section detail-section"><div class="container detail-grid"><div class="detail-main">
@if($item->image)<img class="detail-image {{ $kind === 'doctors' ? 'profile-detail-image' : '' }}" src="{{ $item->image }}" alt="{{ $item->image_alt ?: $item->title }}" width="1200" height="800">@endif
<div class="prose">{!! $item->translated('content') !!}</div>
@if($kind === 'services')
@if($item->benefits)<div class="detail-block"><h2>{{ __('ui.benefits') }}</h2><ul class="check-list">@foreach($item->benefits as $benefit)<li><x-icon name="check" :size="20"/>{{ $benefit }}</li>@endforeach</ul></div>@endif
@if($item->procedure)<div class="detail-block"><h2>{{ __('ui.what_expect') }}</h2><ol class="procedure-list">@foreach($item->procedure as $step)<li><span>{{ str_pad($loop->iteration,2,'0',STR_PAD_LEFT) }}</span><p>{{ $step }}</p></li>@endforeach</ol></div>@endif
@if($item->faq)<div class="detail-block"><h2>{{ __('ui.faq') }}</h2><div class="faq-list">@foreach($item->faq as $question)<details class="faq-item"><summary>{{ $question['question'] }}<x-icon name="plus" :size="18"/></summary><p class="faq-answer">{{ $question['answer'] }}</p></details>@endforeach</div></div>@endif
<p class="information-note"><x-icon name="shield" :size="20"/>{{ __('ui.general_information') }}</p>
@if($item->gallery->isNotEmpty())<div class="gallery-grid">@foreach($item->gallery as $photo)<figure class="gallery-item"><img src="{{ $photo->image }}" alt="{{ $photo->image_alt ?: $photo->title }}" width="900" height="700" loading="lazy"><figcaption>{{ $photo->translated('title') }}</figcaption></figure>@endforeach</div>@endif
@elseif($kind === 'doctors')
@foreach(['qualifications','specializations','languages','affiliations'] as $field)@if($item->$field)<div class="detail-block"><h2>{{ __('ui.'.$field) }}</h2><ul class="check-list">@foreach($item->$field as $value)<li><x-icon name="check" :size="18"/>{{ $value }}</li>@endforeach</ul></div>@endif @endforeach
@if($item->years_experience !== null)<p>{{ __('ui.experience') }}: {{ $item->years_experience }} {{ __('ui.years') }}</p>@endif
@foreach($item->links ?? [] as $link)<a href="{{ $link['url'] }}" class="text-link" target="_blank" rel="noopener noreferrer">{{ $link['label'] }} ↗</a>@endforeach
@elseif($kind === 'branches')@include('public.partials.branch-info',['branch'=>$item])
@elseif($kind === 'articles')<p class="information-note">{{ __('ui.general_information') }}</p><div class="category-tabs">@foreach($item->tags as $tag)<a href="/articles?tag={{ $tag->slug }}">{{ $tag->title }}</a>@endforeach</div>
@endif
</div><aside class="detail-sidebar"><div class="sidebar-visit"><span class="service-icon"><x-icon name="tooth" :size="32"/></span><h2>{{ __('ui.appointment_title') }}</h2><p>{{ __('ui.request_notice') }}</p>@if($clinic->get('appointment_enabled'))<x-link :href="'/appointments?'.http_build_query($kind === 'services' ? ['service'=>$item->id] : ($kind === 'doctors' ? ['doctor'=>$item->id] : ($kind === 'branches' ? ['branch'=>$item->id] : [])))">{{ $clinic->get('appointment_cta') }}</x-link>@endif<a href="/contact" class="text-link">{{ __('ui.contact') }}</a></div>
@if(in_array($kind,['services','doctors']) && $item->branches->isNotEmpty())<div class="sidebar-branches"><h3>{{ __('ui.available_at') }}</h3>@foreach($item->branches as $branch)<a href="/branches/{{ $branch->slug }}"><x-icon name="pin" :size="18"/>{{ $branch->title }}</a>@endforeach</div>@endif
@if($kind === 'services' && $clinic->get('show_prices') && $item->price !== null)<p class="price-note">{{ $clinic->get('currency') }} {{ number_format($item->price,2) }}</p>@endif</aside></div></section>
@if($kind === 'services' && $item->related->isNotEmpty())<section class="section"><div class="container"><x-section-heading :title="__('ui.related_services')"/><div class="service-grid">@foreach($item->related as $service)<x-service-card :service="$service"/>@endforeach</div></div></section>@endif
@if($kind === 'branches')<section class="section"><div class="container">@if($clinic->enabled('services') && $item->services->isNotEmpty())<x-section-heading :title="__('ui.services')"/><div class="service-grid">@foreach($item->services as $service)<x-service-card :service="$service"/>@endforeach</div>@endif @if($clinic->enabled('doctors') && $item->professionals->isNotEmpty())<x-section-heading class="detail-block" :title="__('ui.team_at')"/><div class="doctor-grid">@foreach($item->professionals as $person)<x-doctor-card :person="$person"/>@endforeach</div>@endif</div></section>@endif
@if($kind === 'articles' && ($related ?? collect())->isNotEmpty())<section class="section"><div class="container"><x-section-heading :title="__('ui.related_articles')"/><div class="article-grid">@foreach($related as $article)<x-article-card :article="$article"/>@endforeach</div></div></section>@endif
@endsection
