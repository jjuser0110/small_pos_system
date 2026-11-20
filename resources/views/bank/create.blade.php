@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        <a class="text-muted fw-light" href="{{route('bank.index')}}">{{ __('Bank') }} /</a> 
         @if (isset($bank)) {{ __('Edit') }} @else {{ __('Create') }} @endif
    </h4>
    <div class="row">
        <div class="col-12">
            <div class="card">
            <h5 class="card-header">{{ __('Bank Details') }}</h5>
            <div class="card-body">
                <form class="row g-3" enctype="multipart/form-data" @if (isset($bank)) method="post" action="{{ route('bank.update',$bank) }}" @else method="post" action="{{ route('bank.store') }}" @endif onsubmit="showLoading()">
                @csrf
                <div class="col-md-6">
                    <label class="form-label" for="bank_name">{{ __('Bank Name') }}</label>
                    <input
                    type="text"
                    class="form-control"
                    placeholder="{{ __('Maybank') }}"
                    name="bank_name"
                    value="{{$bank->bank_name??''}}" 
                    required/>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="short_name">{{ __('Short Name') }}</label>
                    <input
                    type="text"
                    class="form-control"
                    placeholder="{{ __('MBB') }}"
                    name="short_name" 
                    value="{{$bank->short_name??''}}"
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
