@extends('layouts.public')
@section('title',__('ui.about'))
@section('content')
<section class="page-intro"><div class="container"><nav class="breadcrumbs"><a href="/">{{ __('ui.home') }}</a><span>/</span><span>{{ __('ui.about') }}</span></nav><p class="eyebrow"><span></span>{{ __('ui.about_eyebrow') }}</p><h1>{{ __('ui.about_title') }}</h1><p>{{ $clinic->get('about_description') }}</p></div></section>
<section class="section"><div class="container about-grid"><div class="about-visual"><img src="{{ $clinic->get('about_image') }}" alt="{{ $clinic->get('name') }} {{ __('ui.gallery') }}" width="1000" height="1100"></div><div class="prose">@if($page){!! $page->translated('content') !!}@else<h2>{{ $clinic->get('about_title') }}</h2><p>{{ $clinic->get('about_description') }}</p>@endif</div></div></section>
@if($clinic->enabled('doctors') && $professionals->isNotEmpty())<section class="section team-section"><div class="container"><x-section-heading :title="__('ui.team_title')"/><div class="doctor-grid">@foreach($professionals as $person)<x-doctor-card :person="$person"/>@endforeach</div></div></section>@endif
@endsection
