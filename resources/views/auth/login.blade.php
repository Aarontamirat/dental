@extends('auth.layout')
@section('content')
<span class="auth-icon"><x-icon name="tooth" :size="32"/></span><p class="eyebrow">{{ __('ui.admin') }}</p><h1>{{ __('ui.sign_in') }}</h1><p>{{ __('ui.sign_in_intro') }}</p>
@if(session('status'))<p class="status-message" role="status">{{ session('status') }}</p>@endif
<form action="/login" method="post">@csrf<x-form-errors/><div class="field"><label for="email">{{ __('ui.email') }}</label><input id="email" name="email" type="email" autocomplete="username" value="{{ old('email') }}" required autofocus></div><div class="field"><label for="password">{{ __('ui.password') }}</label><input id="password" name="password" type="password" autocomplete="current-password" required></div><button class="button button-primary submit-button" type="submit">{{ __('ui.login') }}<x-icon :size="18"/></button><a class="auth-forgot" href="/forgot-password">{{ __('ui.forgot_password') }}</a></form>
@endsection
