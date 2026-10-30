@props(['eyebrow' => '', 'title', 'description' => '', 'centered' => false])
<div {{ $attributes->class(['section-heading', 'centered' => $centered]) }}>
@if($eyebrow)<p class="eyebrow"><span></span>{{ $eyebrow }}</p>@endif
<h2>{{ $title }}</h2>@if($description)<p class="section-description">{{ $description }}</p>@endif
</div>
