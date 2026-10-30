@extends('layouts.public')
@section('content')
@unless($sections->contains('key','hero'))<h1 class="sr-only">{{ $clinic->get('name') }}</h1>@endunless
@foreach($sections as $section)
@if(!in_array($section->key,['services','doctors','articles','branches','gallery','testimonials']) || $clinic->enabled($section->key))
@includeIf('public.sections.'.$section->key, ['section'=>$section])
@endif
@endforeach
@endsection
