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
    <!--/ Card Border Shadow -->
</div>
    <!-- / Content -->

@endsection
@section('page-js')
@endsection
@section('scripts')
@endsection
