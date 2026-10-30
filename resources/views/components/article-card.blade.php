@props(['article'])
<article class="article-card"><a href="{{ url('/articles/'.$article->slug) }}" class="article-image">@if($article->image)<img src="{{ $article->image }}" alt="{{ $article->image_alt ?: $article->title }}" width="640" height="420" loading="lazy">@endif</a>
<div class="article-meta"><span>{{ $article->category?->translated('title') ?: __('ui.patient_resources') }}</span><time datetime="{{ $article->published_at?->toIso8601String() }}">{{ $article->published_at?->format('d M Y') }}</time></div>
<h3><a href="{{ url('/articles/'.$article->slug) }}">{{ $article->translated('title') }}</a></h3><p>{{ $article->translated('excerpt') }}</p><a class="text-link" href="{{ url('/articles/'.$article->slug) }}">{{ __('ui.read_article') }}<x-icon :size="18"/></a>
</article>
