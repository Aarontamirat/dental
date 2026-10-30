@extends('layouts.public')
@section('title',__('ui.locations'))
@section('content')
<section class="page-intro"><div class="container"><p class="eyebrow"><span></span>{{ __('ui.locations') }}</p><h1>{{ __('ui.branches_title') }}</h1><p>{{ __('ui.find_us') }}</p></div></section>
<section class="section"><div class="container">@forelse($branches as $branch)<article class="branch-page-card"><div class="branch-page-image">@if($branch->image)<img src="{{ $branch->image }}" alt="{{ $branch->image_alt ?: $branch->title }}" width="1000" height="900" loading="lazy">@endif</div><div class="branch-page-copy"><h2>{{ $branch->translated('title') }}</h2><p>{{ $branch->translated('excerpt') }}</p>@include('public.partials.branch-info')<x-link :href="'/branches/'.$branch->slug" variant="outline">{{ __('ui.view_branch') }}</x-link></div></article>@empty<div class="empty-state"><h2>{{ __('ui.no_results') }}</h2><x-link href="/contact">{{ __('ui.contact') }}</x-link></div>@endforelse</div></section>
@endsection
