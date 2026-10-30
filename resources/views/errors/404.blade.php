@extends('layouts.public')
@section('title','404')
@section('content')<section class="section"><div class="container empty-state"><span class="eyebrow">404</span><h1>{{ __('ui.not_found') }}</h1><p>{{ __('ui.not_found_intro') }}</p><x-link href="/">{{ __('ui.back_home') }}</x-link></div></section>@endsection
