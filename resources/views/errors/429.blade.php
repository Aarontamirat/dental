@extends('layouts.public')
@section('title','Please wait')
@section('content')<section class="section"><div class="container empty-state"><span class="eyebrow">429</span><h1>{{ __('ui.unavailable') }}</h1><p>{{ __('ui.unavailable_intro') }}</p><x-link href="/">{{ __('ui.back_home') }}</x-link></div></section>@endsection
