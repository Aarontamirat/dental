@props(['paginator'])
@if($paginator->hasPages())<nav class="pagination" aria-label="{{ __('ui.page') }}">
@if($paginator->onFirstPage())<span aria-disabled="true">{{ __('ui.previous') }}</span>@else<a href="{{ $paginator->previousPageUrl() }}">{{ __('ui.previous') }}</a>@endif
<span>{{ __('ui.page') }} {{ $paginator->currentPage() }} {{ __('ui.of') }} {{ $paginator->lastPage() }}</span>
@if($paginator->hasMorePages())<a href="{{ $paginator->nextPageUrl() }}">{{ __('ui.next') }}</a>@else<span aria-disabled="true">{{ __('ui.next') }}</span>@endif
</nav>@endif
