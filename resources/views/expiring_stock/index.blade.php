@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        <a class="text-muted fw-light" href="{{ route('batch.index') }}">Stock /</a> Expiring Stock
    </h4>

    <div class="card">
        <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <h5 class="mb-0">Expired and expiring within {{ $days }} days</h5>
            <form method="get" class="d-flex align-items-center gap-2">
                <label class="form-label mb-0 text-nowrap">Within</label>
                <select name="days" class="form-select no-select2" onchange="this.form.submit()">
                    @foreach([7, 14, 30, 60, 90, 180] as $d)
                        <option value="{{ $d }}" @selected($days == $d)>{{ $d }} days</option>
                    @endforeach
                </select>
            </form>
        </div>
        <div class="card-datatable text-nowrap">
            <table class="table table-bordered" id="mytable">
                <thead>
                    <tr>
                        <th>Expiry date</th>
                        <th>Days left</th>
                        <th>Item</th>
                        <th>Company</th>
                        <th>Stock entry</th>
                        <th>Left</th>
                        <th>Cost value</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        @php $left = \Carbon\Carbon::today()->diffInDays(\Carbon\Carbon::parse($row->expiry_date), false); @endphp
                        <tr class="{{ $left < 0 ? 'table-danger' : ($left <= 7 ? 'table-warning' : '') }}">
                            <td data-order="{{ $row->expiry_date }}">{{ \Carbon\Carbon::parse($row->expiry_date)->format('d/m/Y') }}</td>
                            <td data-order="{{ $left }}">{{ $left < 0 ? 'Expired ' . abs((int) $left) . ' days ago' : ($left == 0 ? 'Today' : (int) $left . ' days') }}</td>
                            <td>{{ $row->product->product_name ?? '' }}</td>
                            <td>{{ $row->company->company_code ?? '' }}</td>
                            <td><a href="{{ route('batch.edit', $row->batch_id) }}">{{ $row->batch->batch_no ?? '' }}</a></td>
                            <td>{{ $row->balance + 0 }} {{ $row->product->uom_dt->uom_unit ?? '' }}</td>
                            <td data-order="{{ $row->balance * $row->cost_per_unit }}">{{ money($row->balance * $row->cost_per_unit) }}</td>
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
        pageLength: 25,
        order: [[0, 'asc']],
        language: { emptyTable: 'Nothing expires in this period.' },
    });
});
</script>
@endsection
