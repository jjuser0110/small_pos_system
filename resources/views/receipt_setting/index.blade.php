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
                @if($branches->count())
                <form method="get" class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Branch</label>
                        <select name="branch_id" class="form-select" onchange="this.form.submit()">
                            <option value="">Default (branches without their own)</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}" @selected($branchId == $b->id)>{{ $b->branch_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
                @endif

                @if($branchId && !$own)
                    <div class="alert alert-info">This branch uses the default receipt. Save below to give it its own.</div>
                @endif

                <form class="row g-3" method="post" action="{{ route('receipt_setting.store') }}" onsubmit="showLoading()">
                @csrf
                <input type="hidden" name="branch_id" value="{{ $branchId }}">
                <div class="col-md-12">
                    <label class="form-label" for="header">Header</label>
                    <textarea name="header" id="header" class="form-control" rows="10" required>{{ $receipt_setting->header ?? '' }}</textarea>
                </div>
                <div class="col-md-12">
                    <label class="form-label" for="footer">Footer</label>
                    <textarea name="footer" id="footer" class="form-control" rows="10" required>{{ $receipt_setting->footer ?? '' }}</textarea>
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
