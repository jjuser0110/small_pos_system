@extends('layouts.app')

@section('content')
<style>
    #mytable th,
    #mytable td {
        padding: 10px;
    }
</style>
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        <a class="text-muted fw-light" href="{{ route('product.index') }}">Product /</a> Stock Adjustment
    </h4>

    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Stock Adjustments</h5>
        </div>
        <div class="card-datatable text-nowrap">
            <table class="dt-column-search table table-bordered" id="mytable">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Product</th>
                        <th>Type</th>
                        <th>Qty</th>
                        <th>Company</th>
                        <th>Reason</th>
                        <th>Adjusted By</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($adjustments as $a)
                    <tr>
                        <td>{{ $a->created_at }}</td>
                        <td>{{ $a->product->product_name ?? '' }}</td>
                        <td>{{ $a->typeLabel() }}</td>
                        <td>{{ $a->type == 'adjust_in' ? '+' : '-' }}{{ number_format($a->quantity, 2) }}</td>
                        <td>{{ $a->company->company_code ?? '' }}</td>
                        <td>{{ $a->reason }}</td>
                        <td>{{ $a->requester->name ?? '' }}</td>
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
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50, 75, 100],
        order: [[0, 'desc']],
    });
});
</script>
@endsection