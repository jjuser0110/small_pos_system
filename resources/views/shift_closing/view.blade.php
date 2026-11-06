@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        <a class="text-muted fw-light" href="{{ route('shift_closing.index') }}">Shift Report /</a>
        {{ $shift_closing->user->name ?? '' }} · {{ $shift_closing->shift }} {{ $shift_closing->work_date }}
    </h4>

    <div class="row">
        @foreach([
            ['Orders', $shift_closing->total_order_count + 0, false],
            ['Items Sold', $shift_closing->total_item_count + 0, false],
            ['Sales', $shift_closing->total_order_amount, true],
            ['Refunds', $shift_closing->refund_amount, true],
            ['Advances', $shift_closing->advance_amount, true],
            ['Net', $shift_closing->netAmount(), true],
        ] as [$label, $value, $money])
        <div class="col-6 col-md-2 mb-3">
            <div class="card h-100"><div class="card-body p-3">
                <small class="text-muted">{{ $label }}</small>
                <div class="fs-5 fw-semibold">{{ $money ? 'RM ' . number_format($value, 2) : $value }}</div>
            </div></div>
        </div>
        @endforeach
    </div>
    <p class="text-muted">
        First sale: {{ $shift_closing->first_sale_time ?? '-' }} ·
        Closed: {{ $shift_closing->closing_time ?? 'Not yet closed' }}
    </p>

    <div class="row">
        <div class="col-md-5 mb-4">
            <div class="card h-100">
                <h5 class="card-header">By payment method</h5>
                <table class="table mb-0">
                    <thead><tr><th>Method</th><th class="text-end">Amount (RM)</th></tr></thead>
                    <tbody>
                        @forelse($shift_closing->items as $m)
                            <tr><td>{{ $m->payment_method }}</td><td class="text-end">{{ number_format($m->amount, 2) }}</td></tr>
                        @empty
                            <tr><td colspan="2" class="text-muted">No sales</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot><tr><th>Total collected</th><th class="text-end">{{ number_format($shift_closing->items->sum('amount'), 2) }}</th></tr></tfoot>
                </table>
            </div>
        </div>
        <div class="col-md-7 mb-4">
            <div class="card h-100">
                <h5 class="card-header">Items sold</h5>
                <table class="table mb-0">
                    <thead><tr><th>Item</th><th class="text-end">Qty</th><th class="text-end">Amount (RM)</th></tr></thead>
                    <tbody>
                        @forelse($items->where('qty', '>', 0) as $i)
                            <tr><td>{{ $i->product->product_name ?? '' }}</td><td class="text-end">{{ $i->qty + 0 }}</td><td class="text-end">{{ number_format($i->amount, 2) }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="text-muted">No items sold</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot><tr><th>Total</th><th class="text-end">{{ $items->sum('qty') + 0 }}</th><th class="text-end">{{ number_format($items->sum('amount'), 2) }}</th></tr></tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <h5 class="card-header">By category</h5>
        <table class="table mb-0">
            <thead><tr><th>Category / Product</th><th class="text-end">Sales (RM)</th></tr></thead>
            <tbody>
                @foreach($shift_closing->details as $detail)
                <tr>
                    <td>{{ isset($detail->category) && $detail->category->special == 1 ? ($detail->product->product_name ?? '') : ($detail->category->category_name ?? '') }}</td>
                    <td class="text-end">{{ number_format($detail->amount ?? 0, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
