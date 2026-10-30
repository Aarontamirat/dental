@props(['person'])
<a class="doctor-card" href="{{ url('/doctors/'.$person->slug) }}">
<div class="doctor-image">@if($person->image)<img src="{{ $person->image }}" alt="{{ $person->image_alt ?: $person->title }}" width="600" height="720" loading="lazy">@else<span class="image-empty"><x-icon name="heart" :size="64"/></span>@endif<span class="doctor-arrow"><x-icon/></span></div>
<div class="doctor-caption"><div><h3>{{ $person->translated('title') }}</h3><p>{{ $person->professional_title }}</p></div></div>
@if($clinic->get('demo_mode'))<span class="demo-caption">{{ __('ui.demo_profile') }}</span>@endif
</a>
