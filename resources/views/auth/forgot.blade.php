@extends('auth.layout')
@section('content')
<span class="auth-icon"><x-icon name="mail" :size="32"/></span><h1>{{ __('ui.reset_title') }}</h1>@if(session('status'))<p class="status-message" role="status">{{ session('status') }}</p>@endif<form action="/forgot-password" method="post">@csrf<x-form-errors/><div class="field"><label for="email">{{ __('ui.email') }}</label><input id="email" name="email" type="email" autocomplete="email" value="{{ old('email') }}" required></div><button class="button button-primary submit-button" type="submit">{{ __('ui.send_reset') }}</button><a class="auth-forgot" href="/login">{{ __('ui.login_return') }}</a></form>
@endsection
