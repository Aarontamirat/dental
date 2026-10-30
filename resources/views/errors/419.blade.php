@extends('layouts.public')
@section('title','Session expired')
@section('content')<section class="section"><div class="container empty-state"><span class="eyebrow">419</span><h1>{{ __('ui.form_expired') }}</h1><x-link href="/">{{ __('ui.back_home') }}</x-link></div></section>@endsection
