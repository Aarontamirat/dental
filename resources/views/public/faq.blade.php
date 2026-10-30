@extends('layouts.public')
@section('title',__('ui.faq'))
@section('content')
<section class="page-intro"><div class="container"><p class="eyebrow"><span></span>{{ __('ui.faq_eyebrow') }}</p><h1>{{ __('ui.faq_title') }}</h1><p>{{ __('ui.faq_subtitle') }}</p></div></section><section class="section"><div class="container faq-page-grid"><x-faq-list :faqs="$faqs"/><aside class="faq-help"><span class="service-icon"><x-icon name="heart"/></span><h2>{{ __('ui.questions_help') }}</h2><p>{{ __('ui.questions_help_text') }}</p><x-link href="/contact" variant="outline">{{ __('ui.contact') }}</x-link></aside></div></section>
@endsection
