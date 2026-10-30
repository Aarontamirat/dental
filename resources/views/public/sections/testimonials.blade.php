@if($testimonials->isNotEmpty())
<section {!! $clinic->sectionAttributes('testimonials') !!} class="section testimonial-section"><div class="container">
@if($clinic->appearance()['sections']['testimonials']['layout'] === 'quotes')
<div class="section-header"><x-section-heading :eyebrow="__('ui.stories')" :title="$section->translated('title')"/><a class="text-link" href="/testimonials">{{ __('ui.read_more') }}<x-icon :size="18"/></a></div>
<div class="testimonial-cards">@foreach($testimonials as $review)<figure><span class="quote-mark" aria-hidden="true">“</span><blockquote>{{ $review->translated('excerpt') }}</blockquote><figcaption><strong>{{ $review->translated('title') }}</strong>@if($review->is_demo)<small>{{ __('ui.demo_review') }}</small>@endif</figcaption></figure>@endforeach</div>
@else
<div class="testimonial-grid"><div><p class="eyebrow"><span></span>{{ __('ui.stories') }}</p><h2>{{ $section->translated('title') }}</h2><a class="text-link" href="/testimonials">{{ __('ui.read_more') }}<x-icon :size="18"/></a></div><div class="testimonial-quote"><span class="quote-mark" aria-hidden="true">“</span>@foreach($testimonials->take(1) as $review)<blockquote>{{ $review->translated('excerpt') }}</blockquote><div class="quote-author"><span class="avatar-initial">{{ mb_substr($review->title,0,1) }}</span><div><strong>{{ $review->translated('title') }}</strong>@if($review->is_demo)<small>{{ __('ui.demo_review') }}</small>@endif</div></div>@endforeach</div></div>
@endif
</div></section>@endif
