@extends('layouts.app')
@section('content')
<!-- Content -->

<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4"><span class="text-muted fw-light">Report </span></h4>

    <!-- DataTable with Buttons -->
    <div class="card">
        <div class="card-header flex-column flex-md-row">
            <div class="head-label"  style="margin-bottom:10px">
                <h5 class="card-title mb-0">Report</h5>
            </div>
            <div class="col-md-12 col-12 mb-4">
                <form method="GET">
                    <div class="row g-2 align-items-end">

                        <div class="col-md-6">
                            <label>Date</label>
                            <div class="input-group input-daterange" >
                                <input type="datetime-local" class="form-control" name="date_from" value="{{$date_from??''}}"/>
                            <span class="input-group-text">to</span>
                            <input type="datetime-local" class="form-control" name="date_to" value="{{$date_to??''}}"/>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label>Branches</label>
                            <select name="branch_id[]" class="form-select select2" multiple>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ collect(request('branch_id'))->contains($branch->id) ? 'selected' : '' }}>
                                        {{ $branch->branch_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label>Companies</label>
                            <select name="company_id[]" class="form-select select2" multiple>
                                @foreach ($companies as $company)
                                    <option value="{{ $company->id }}" {{ collect(request('company_id'))->contains($company->id) ? 'selected' : '' }}>
                                        {{ $company->company_code }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-12 text-end mt-2">
                            <button class="btn btn-primary">Filter</button>
                            <a href="{{ route('report.print', request()->query()) }}" target="_blank" class="btn btn-secondary">
                                <i class="bx bx-printer me-1"></i> Print Report
                            </a>
                        </div>

                    </div>
                </form>
            </div>

        </div>
        <div class="card-datatable text-nowrap">
            <table class="dt-column-search table table-bordered" id="mytable">
                <thead>
                    <tr>
                        <th>Branch</th>
                        <th>Company</th>
                        <th>Product</th>
                        <th>Total Quantity</th>
                        <th>Total Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($itemTotals as $index => $item)
                    <tr>
                        <td>{{ $item->branch->branch_name ?? '' }}</td>
                        <td>{{ $item->company->company_name ?? '' }}</td>
                        <td>{{ $item->product->product_name ?? '' }}@if($item->unit_name) ({{ $item->unit_name }})@endif</td>
                        <td>{{ $item->total_quantity }}</td>
                        <td>{{ money($item->total_amount) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
<!-- / Content -->


@endsection

@section('page-js')
@endsection

@section('scripts')
<script>
$(function(){
    var table = $('#mytable').DataTable({
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>><"table-responsive"t><"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
        pageLength: 10,
        displayLength: 5,
        lengthMenu: [5, 10, 25, 50, 75, 100],
    });
});
</script>
@endsection
