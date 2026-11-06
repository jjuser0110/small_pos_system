@extends('layouts.app')
@section('content')
<style>#mytable th, #mytable td { padding: 8px; }</style>
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4"><span class="text-muted fw-light">Shift Report</span></h4>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Shift date</label>
                    <div class="input-group">
                        <input type="date" class="form-control" name="date_from" value="{{ $date_from }}"/>
                        <span class="input-group-text">to</span>
                        <input type="date" class="form-control" name="date_to" value="{{ $date_to }}"/>
                    </div>
                </div>
                @if($companies->count())
                <div class="col-md-3">
                    <label class="form-label">Company</label>
                    <select name="company_id" class="form-select">
                        <option value="">All</option>
                        @foreach($companies as $c)
                            <option value="{{ $c->id }}" {{ (string) $company_id === (string) $c->id ? 'selected' : '' }}>{{ $c->company_code }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-2">
                    <label class="form-label">Shift</label>
                    <select name="shift" class="form-select">
                        <option value="">All</option>
                        @foreach(array_keys(config('fsmart.shifts')) as $s)
                            <option value="{{ $s }}" {{ $shift === $s ? 'selected' : '' }}>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 text-end">
                    <button class="btn btn-primary" type="submit">Filter</button>
                    <a href="{{ route('shift_closing.print', request()->query()) }}" target="_blank" class="btn btn-secondary"><i class="bx bx-printer me-1"></i> Print</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row mb-2">
        @foreach([
            ['Orders', $totals['orders'], false], ['Items Sold', $totals['items'], false],
            ['Sales', $totals['sales'], true], ['Refunds', $totals['refunds'], true],
            ['Advances', $totals['advances'], true], ['Net', $totals['net'], true],
        ] as [$label, $value, $money])
        <div class="col-6 col-md-2 mb-3">
            <div class="card h-100"><div class="card-body p-3">
                <small class="text-muted">{{ $label }}</small>
                <div class="fs-5 fw-semibold">{{ $money ? 'RM ' . number_format($value, 2) : $value + 0 }}</div>
            </div></div>
        </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-datatable text-nowrap">
            <table class="dt-column-search table table-bordered" id="mytable">
                <thead>
                    <tr>
                        <th>Date</th><th>Shift</th><th>Staff</th><th>Company</th>
                        <th>Orders</th><th>Items</th><th>Sales</th><th>Refunds</th><th>Advances</th><th>Net</th>
                        @foreach($totals['methods']->keys() as $m)<th>{{ $m }}</th>@endforeach
                        <th>Closed</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($shift_closing as $row)
                    <tr>
                        <td>{{ $row->work_date ?? optional($row->created_at)->toDateString() }}</td>
                        <td>{{ $row->shift }}</td>
                        <td>{{ $row->user->name ?? '' }}</td>
                        <td>{{ $row->company_code }}</td>
                        <td>{{ $row->total_order_count + 0 }}</td>
                        <td>{{ $row->total_item_count + 0 }}</td>
                        <td data-order="{{ $row->total_order_amount }}">{{ number_format($row->total_order_amount, 2) }}</td>
                        <td>{{ number_format($row->refund_amount, 2) }}</td>
                        <td>{{ number_format($row->advance_amount, 2) }}</td>
                        <td><b>{{ number_format($row->netAmount(), 2) }}</b></td>
                        @foreach($totals['methods']->keys() as $m)
                            <td>{{ number_format($row->items->where('payment_method', $m)->sum('amount'), 2) }}</td>
                        @endforeach
                        <td>{{ $row->closing_time ?? 'Open' }}</td>
                        <td><a href="{{ route('shift_closing.view', $row) }}" onclick="showLoading()"><i class="fa-solid fa-eye"></i></a></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function(){
    $('#mytable').DataTable({
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>><"table-responsive"t><"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
        pageLength: 25, lengthMenu: [10, 25, 50, 100], order: [[0, 'desc']],
        columnDefs: [{ orderable: false, targets: -1 }],
    });
});
</script>
@endsection
