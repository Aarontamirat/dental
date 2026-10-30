@extends('layouts.public')
@section('kind',$kind)
@section('title',$title)
@section('content')
<section class="page-intro"><div class="container"><nav class="breadcrumbs"><a href="/">{{ __('ui.home') }}</a><span>/</span><span>{{ $title }}</span></nav><p class="eyebrow"><span></span>{{ $eyebrow }}</p><h1>{{ $title }}</h1><p>{{ $description }}</p></div></section>
<section class="section listing-section"><div class="container"><div class="filter-bar">
@if($kind !== 'doctors')<form method="get" class="search-form"><label class="sr-only" for="search">{{ __('ui.search') }}</label><input type="search" id="search" name="q" value="{{ request('q') }}" placeholder="{{ __('ui.search_placeholder') }}">@if(request('category'))<input type="hidden" name="category" value="{{ request('category') }}">@endif<input type="hidden" name="lang" value="{{ app()->getLocale() }}"><button type="submit" class="button button-primary">{{ __('ui.search') }}</button></form>@endif
@if($categories->isNotEmpty())<nav class="category-tabs" aria-label="{{ __('ui.all') }}"><a href="{{ url('/'.$kind) }}" @class(['active'=>!request('category')])>{{ __('ui.all') }}</a>@foreach($categories as $category)<a href="{{ url('/'.$kind).'?category='.$category->slug }}" @class(['active'=>request('category') === $category->slug])>{{ $category->translated('title') }}</a>@endforeach</nav>@endif
@if($kind === 'doctors')<form method="get" class="search-form"><label for="kind">{{ __('ui.team') }}</label><select name="kind" id="kind"><option value="">{{ __('ui.all') }}</option>@foreach(['dentist','specialist','hygienist','staff'] as $category)<option value="{{ $category }}" @selected(request('kind') === $category)>{{ ucfirst($category) }}</option>@endforeach</select><button class="button button-primary" type="submit">{{ __('ui.search') }}</button></form>@endif
</div>
@if($items->isEmpty())<div class="empty-state"><x-icon name="tooth" :size="48"/><h2>{{ __('ui.no_results') }}</h2><p>{{ __('ui.no_results_help') }}</p><a class="text-link" href="/{{ $kind }}">{{ __('ui.reset_filters') }}</a></div>@else
<p class="results-summary">{{ __('ui.results_count', ['count' => $items->total()]) }} @if(request('q')) · “{{ request('q') }}” @endif</p><div class="{{ $kind === 'services' ? 'service-grid' : ($kind === 'doctors' ? 'doctor-grid' : 'article-grid') }}">@foreach($items as $item)@if($kind === 'services')<x-service-card :service="$item" :index="$loop->index"/>@elseif($kind === 'doctors')<x-doctor-card :person="$item"/>@else<x-article-card :article="$item"/>@endif @endforeach</div>
<x-pagination :paginator="$items"/>@endif
</div></section>
@endsection
