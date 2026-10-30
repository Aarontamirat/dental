{!! '<'.'?xml version="1.0" encoding="UTF-8"?'.'>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
@foreach($urls as $path)
@foreach($languages as $locale)
<url><loc>{{ url($path).(count($languages) > 1 ? '?lang='.$locale : '') }}</loc>
@foreach($languages as $alternate)<xhtml:link rel="alternate" hreflang="{{ $alternate }}" href="{{ url($path).(count($languages) > 1 ? '?lang='.$alternate : '') }}"/>@endforeach
</url>
@endforeach
@endforeach
</urlset>
