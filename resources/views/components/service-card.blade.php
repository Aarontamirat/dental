@props(['service', 'index' => null])
<a class="service-card" href="{{ url('/services/'.$service->slug) }}">
<div class="service-card-top"><span class="service-icon"><x-icon :name="$service->icon" :size="30"/></span>@if($index !== null)<span class="card-index">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span>@endif</div>
<h3>{{ $service->translated('title') }}</h3><p>{{ $service->translated('excerpt') }}</p>
<span class="card-link">{{ __('ui.learn_more') }}<span class="circle-arrow"><x-icon :size="18"/></span></span>
</a>
