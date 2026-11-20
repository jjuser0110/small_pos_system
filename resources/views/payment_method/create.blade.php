@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        <a class="text-muted fw-light" href="{{route('payment_method.index')}}">{{ __('Payment Method') }} /</a> 
         @if (isset($payment_method)) {{ __('Edit') }} @else {{ __('Create') }} @endif
    </h4>
    <div class="row">
        <div class="col-12">
            <div class="card">
            <h5 class="card-header">{{ __('Payment Method Details') }}</h5>
            <div class="card-body">
                <form class="row g-3" enctype="multipart/form-data" @if (isset($payment_method)) method="post" action="{{ route('payment_method.update',$payment_method) }}" @else method="post" action="{{ route('payment_method.store') }}" @endif onsubmit="showLoading()">
                @csrf
                <div class="col-md-6">
                    <label class="form-label" for="payment_method_name">{{ __('Payment Method Name') }}</label>
                    <input
                    type="text"
                    class="form-control"
                    placeholder="{{ __('Cash') }}"
                    name="payment_method_name"
                    value="{{$payment_method->payment_method_name??''}}" 
                    required/>
                </div>
                <hr>
                <div class="col-12">
                    <button type="submit" name="submitButton" class="btn btn-primary">{{ __('Submit') }}</button>
                </div>
                </form>
            </div>
            </div>
        </div>
    </div>
</div>
<!-- / Content -->
@endsection

@section('scripts')
@endsection
