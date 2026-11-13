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
            <button class="btn btn-primary" style="float:right;" onclick="confirmClosing()">Closing</button>

            @if(Auth::user()->isStaff())
                <a class="btn btn-warning" style="float:right;margin-right:10px" href="{{ route('staff_borrow.index') }}">Advance</a>
            @endif
        </div>

            @foreach(\App\Http\Controllers\NotificationController::itemsFor(Auth::user()) as $note)
                <div class="col-12"><div class="alert alert-warning d-flex justify-content-between align-items-center">
                    <span><i class="bx bx-bell me-1"></i> {{ $note['text'] }}</span>
                    <a href="{{ $note['url'] }}" class="btn btn-sm btn-warning">Open</a>
                </div></div>
            @endforeach

            @if($errors->any())
                <div class="col-12"><div class="alert alert-danger">{{ $errors->first() }}</div></div>
            @endif
        @if($shift_data)
        <div class="col-12 mb-2"><h5 class="mb-0">Current shift: {{ $shift_data->shift }} {{ $shift_data->work_date }}</h5></div>
        @endif
        @foreach([
            ['Orders', $shift_data->total_order_count ?? 0, false],
            ['Items Sold', $shift_data->total_item_count ?? 0, false],
            ['Sales', $shift_data->total_order_amount ?? 0, true],
            ['Refunds', $shift_data->refund_amount ?? 0, true],
            ['Advances', $shift_data->advance_amount ?? 0, true],
            ['Net', $shift_data ? $shift_data->netAmount() : 0, true],
        ] as [$label, $value, $money])
        <div class="col-6 col-md-2 mb-4">
            <div class="card card-border-shadow-primary h-100"><div class="card-body p-3">
                <small class="text-muted">{{ $label }}</small>
                <div class="fs-5 fw-semibold">{{ $money ? money($value) : $value + 0 }}</div>
            </div></div>
        </div>
        @endforeach
        @if($shift_data)
            @foreach($shift_data->items as $item)
            <div class="col-6 col-md-2 mb-4">
                <div class="card h-100"><div class="card-body p-3">
                    <small class="text-muted">{{ strtoupper($item->payment_method ?? '') }}</small>
                    <div class="fs-5 fw-semibold">{{ money($item->amount ?? 0) }}</div>
                </div></div>
            </div>
            @endforeach
            <div class="col-12 text-muted mb-3">First sale: {{ $shift_data->first_sale_time ?? '-' }}</div>
        @endif
    </div>
    <!--/ Card Border Shadow -->
</div>


<!-- / Content -->

@endsection
@section('page-js')
@endsection
@section('scripts')
<script>
    function confirmClosing() {
        if (!confirm('Are you sure you want to close your shift?')) return;

        var now = new Date();
        var pad = function (n) { return String(n).padStart(2, '0'); };
        var time = now.getFullYear() + '-' + pad(now.getMonth() + 1) + '-' + pad(now.getDate()) + ' ' +
                   pad(now.getHours()) + ':' + pad(now.getMinutes()) + ':' + pad(now.getSeconds());

        if (confirm('Your shift will be recorded as closed : ' + time + '.\n\nAre you sure you want to end your shift?\n\nYOU WILL BE LOGGED OUT.')) {
            showLoading();
            window.location.href = '{{ route('shift_closing') }}';
        }
    }
</script>
@endsection
