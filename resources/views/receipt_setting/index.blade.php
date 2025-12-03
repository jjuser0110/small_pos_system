@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        <a class="text-muted fw-light">Receipt Setting</a>
    </h4>
    <div class="row">
        <div class="col-12">
            <div class="card">
            <h5 class="card-header">Receipt Setting Details</h5>
            <div class="card-body">
                <form class="row g-3" enctype="multipart/form-data" method="post" action="{{ route('receipt_setting.store') }}" onsubmit="showLoading()">
                @csrf
                <div class="col-md-12">
                    <label class="form-label" for="uom_name">Header</label>
                    <textarea name='header' class="form-control" rows="10" required>{{$receipt_setting->header ??''}}</textarea>
                </div>
                <div class="col-md-12">
                    <label class="form-label" for="uom_name">Footer</label>
                    <textarea name='footer' class="form-control" rows="10" required>{{$receipt_setting->footer ??''}}</textarea>
                </div>
                <hr>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Submit</button>
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
