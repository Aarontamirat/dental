@extends('layouts.public')
@section('title',__('ui.stories'))
@section('content')
<section class="page-intro"><div class="container"><p class="eyebrow"><span></span>{{ __('ui.stories') }}</p><h1>{{ __('ui.testimonials_title') }}</h1></div></section><section class="section"><div class="container"><div class="testimonial-cards">@forelse($testimonials as $review)<figure><span class="quote-mark">“</span><blockquote>{{ $review->translated('excerpt') }}</blockquote><figcaption><strong>{{ $review->translated('title') }}</strong>@if($review->rating)<span aria-label="{{ $review->rating }} / 5">{{ str_repeat('★',$review->rating) }}</span>@endif @if($review->is_demo)<small>{{ __('ui.demo_review') }}</small>@endif</figcaption></figure>@empty<div class="empty-state"><h2>{{ __('ui.no_results') }}</h2></div>@endforelse</div><x-pagination :paginator="$testimonials"/></div></section>
@endsection
