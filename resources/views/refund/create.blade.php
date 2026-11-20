@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        <a class="text-muted fw-light" href="{{ route('counter') }}">{{ __('Counter') }} /</a> {{ __('Refund') }}
    </h4>

    @if(!$shift)
        <div class="alert alert-warning">{{ __('You have no open shift, so there is nothing to refund.') }}</div>
    @elseif($orders->isEmpty())
        <div class="alert alert-info">{{ __('No orders in your current shift yet.') }}</div>
    @else
        <p class="text-muted">Orders from your current shift ({{ $shift->shift }} {{ $shift->work_date }}). Enter how many of each item to refund. The superadmin must approve before stock and money are returned.</p>

        @foreach($orders as $order)
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between">
                <span><b>{{ $order->order_no }}</b> · {{ $order->created_at->format('H:i') }} · {{ $order->payment_method }}</span>
                <span>{{ money($order->final_total) }}</span>
            </div>
            <div class="card-body">
                <form method="post" action="{{ route('refund.store') }}" onsubmit="showLoading()">
                    @csrf
                    <input type="hidden" name="order_id" value="{{ $order->id }}">
                    <table class="table table-sm">
                        <thead><tr><th>{{ __('Item') }}</th><th>{{ __('Price') }}</th><th>{{ __('Sold') }}</th><th>{{ __('Can refund') }}</th><th style="width:140px">{{ __('Refund qty') }}</th></tr></thead>
                        <tbody>
                        @foreach($order->items as $item)
                            @php $can = $item->refundableQuantity(); @endphp
                            <tr>
                                <td>{{ $item->displayName() }}</td>
                                <td>{{ money($item->single_price) }}</td>
                                <td>{{ $item->quantity + 0 }}</td>
                                <td>{{ $can + 0 }}</td>
                                <td>
                                    <input type="number" name="qty[{{ $item->id }}]" class="form-control form-control-sm" min="0" max="{{ $can }}" step="1" value="0" @if($can <= 0) disabled @endif>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    <div class="row g-2 align-items-center">
                        <div class="col-md-9"><input type="text" name="reason" class="form-control" placeholder="{{ __('Reason for refund') }}" maxlength="500" required></div>
                        <div class="col-md-3"><button type="submit" class="btn btn-warning w-100">{{ __('Request Refund') }}</button></div>
                    </div>
                </form>
            </div>
        </div>
        @endforeach
    @endif

    <a href="{{ route('refund.index') }}" class="btn btn-outline-secondary">{{ __('My refund requests') }}</a>
    <a href="{{ route('counter') }}" class="btn btn-outline-primary">{{ __('Back to counter') }}</a>
</div>
@endsection
