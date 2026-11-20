@if ($message = Session::get('success'))
<div class="alert alert-success dark alert-dismissible fade show" role="alert">
<strong>{{ __($message) }}</strong>
    <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="{{ __('Close') }}"></button>
</div>
@endif
  
@if ($message = Session::get('error'))
<div class="alert alert-danger dark alert-dismissible fade show" role="alert">
<strong>{{ __($message) }}</strong>
    <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="{{ __('Close') }}"></button>
</div>
@endif
   
@if ($message = Session::get('warning'))
<div class="alert alert-warning dark alert-dismissible fade show" role="alert">
<strong>{{ __($message) }}</strong>
    <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="{{ __('Close') }}"></button>
</div>
@endif
   
@if ($message = Session::get('info'))
<div class="alert alert-info dark alert-dismissible fade show" role="alert">
<strong>{{ __($message) }}</strong>
    <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="{{ __('Close') }}"></button>
</div>
@endif
  
@if ($errors->any())
<div class="alert alert-danger dark alert-dismissible fade show" role="alert">
    <strong>
    @foreach ($errors->all() as $error)
         {{ __($error) }}<br>
     @endforeach
    </strong>
    <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="{{ __('Close') }}"></button>
</div>
@endif