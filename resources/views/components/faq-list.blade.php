@props(['faqs'])
<div class="faq-list">@foreach($faqs as $faq)<details class="faq-item"><summary><span>{{ $faq->translated('title') }}</span><x-icon name="plus" :size="20"/></summary><div class="faq-answer prose">{!! $faq->translated('content') !!}</div></details>@endforeach</div>
