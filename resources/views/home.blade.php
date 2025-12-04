@extends('layouts.app')

@section('content')

    <!-- Content -->

<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        <span class="text-muted fw-light">Home
    </h4>

    <!-- Card Border Shadow -->
    <div class="row">
        <div class="col-sm-6 col-lg-3 mb-4">
            <div class="card card-border-shadow-primary h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2 pb-1">
                <h4 class="ms-1 mb-0">User Profile</h4>
                </div>
                <p class="mb-1">Name: {{Auth::user()->name??''}}</p>
                <p class="mb-0">
                <span class="fw-medium me-1">Username: {{Auth::user()->username??''}}</span><br>
                <small class="text-muted">Role: {{Auth::user()->role->title??''}}</small><br>
                <small class="text-muted">Company: {{Auth::user()->company->company_code??''}}</small><br>
                <small class="text-muted">Branch: {{Auth::user()->branch->branch_code??''}}</small>
                </p>
            </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12 col-lg-12 mb-12" style="margin-bottom:20px">
            <button class="btn btn-primary" style="float:right;" onclick="if(confirm('Are you sure you want to close your shift?')){showLoading();window.location.href='{{ route('shift_closing') }}'}">Closing</button>
        </div>
        <div class="col-sm-4 col-lg-4 mb-4">
            <div class="card card-border-shadow-primary h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2 pb-1">
                <h4 class="ms-1 mb-0">Total Order</h4>
                </div>
                <p class="mb-1" style="margin:10px;font-size:18px">{{$shift_data->total_order_count??0}}</p>
                </p>
            </div>
            </div>
        </div>
        <div class="col-sm-4 col-lg-4 mb-4">
            <div class="card card-border-shadow-primary h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2 pb-1">
                <h4 class="ms-1 mb-0">Total Amount</h4>
                </div>
                <p class="mb-1" style="margin:10px;font-size:18px">{{number_format($shift_data->total_order_amount??0,2)}}</p>
                </p>
            </div>
            </div>
        </div>
        <div class="col-sm-4 col-lg-4 mb-4">
            <div class="card card-border-shadow-primary h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2 pb-1">
                <h4 class="ms-1 mb-0">First Sale Time</h4>
                </div>
                <p class="mb-1" style="margin:10px;font-size:18px">{{$shift_data->first_sale_time??null}}</p>
                </p>
            </div>
            </div>
        </div>
        @if($shift_data)
        @foreach($shift_data->items as $item)
        
        <div class="col-sm-4 col-lg-4 mb-4">
            <div class="card card-border-shadow-primary h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2 pb-1">
                <h4 class="ms-1 mb-0">{{strtoupper($item->payment_method??null)}}</h4>
                </div>
                <p class="mb-1" style="margin:10px;font-size:18px">{{number_format($item->amount??0,2)}}</p>
                </p>
            </div>
            </div>
        </div>

        @endforeach
        @endif
    </div>
    <!--/ Card Border Shadow -->
</div>
    <!-- / Content -->

@endsection
@section('page-js')
@endsection
@section('scripts')
@endsection
