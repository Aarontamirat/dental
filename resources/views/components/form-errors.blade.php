@if($errors->any())
<div class="form-errors" role="alert" tabindex="-1" id="form-errors">
    <strong>{{ __('ui.form_errors') }}</strong>
    <ul>@foreach($errors->messages() as $field => $messages)
        <li id="error-{{ $field }}"><a href="#{{ $field }}">{{ $messages[0] }}</a></li>
    @endforeach</ul>
</div>
@endif
