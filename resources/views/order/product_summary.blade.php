@extends('layouts.app')
@section('content')
    <!-- Content -->

    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="py-3 breadcrumb-wrapper mb-4"><span class="text-muted fw-light">Order Summary </span></h4>

        <!-- DataTable with Buttons -->
        <div class="card">
            <div class="card-header flex-column flex-md-row">
                <div class="head-label" style="margin-bottom:10px">
                    <h5 class="card-title mb-0">Product Order Summary</h5>
                </div>
                <div class="col-md-12 col-12 mb-4">
                    <form method="GET">
                        <div class="row g-2 align-items-end">

                            <div class="col-md-6">
                                <label>Date</label>
                                <div class="input-group input-daterange">
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
                            </div>

                        </div>
                    </form>
                </div>
                <div class="row">
                    <div class="col-sm-4 col-lg-4 mb-3">
                        <div class="card card-border-shadow-primary h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-2 pb-1">
                                    <h4 class="ms-1 mb-0">Total Products Ordered</h4>
                                </div>
                                <p class="mb-1" style="margin:10px;font-size:18px">{{ $totalProductsOrdered }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4 col-lg-4 mb-3">
                        <div class="card card-border-shadow-primary h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-2 pb-1">
                                    <h4 class="ms-1 mb-0">Total Quantity</h4>
                                </div>
                                <p class="mb-1" style="margin:10px;font-size:18px">{{ $totalQuantity }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4 col-lg-4 mb-3">
                        <div class="card card-border-shadow-primary h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-2 pb-1">
                                    <h4 class="ms-1 mb-0">Total Amount</h4>
                                </div>
                                <p class="mb-1" style="margin:10px;font-size:18px">{{ number_format($totalAmount, 2) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-datatable text-nowrap">
                <table class="dt-column-search table table-bordered" id="mytable">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Branch</th>
                            <th>Company</th>
                            <th>Times Ordered</th>
                            <th>Total Quantity</th>
                            <th>Total Amount (RM)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($productTotals as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $item->product->product_name ?? '' }}</td>
                            <td>{{ $item->category->category_name ?? '' }}</td>
                            <td>{{ $item->branch->branch_name ?? '' }}</td>
                            <td>{{ $item->company->company_name ?? '' }}</td>
                            <td>{{ $item->order_count }}</td>
                            <td>{{ $item->total_quantity }}</td>
                            <td>{{ number_format($item->total_amount ?? 0, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="5" class="text-end">Total:</th>
                            <th>{{ $productTotals->sum('order_count') }}</th>
                            <th>{{ $productTotals->sum('total_quantity') }}</th>
                            <th>{{ number_format($productTotals->sum('total_amount') ?? 0, 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- / Content -->
@endsection

@section('page-js')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@endsection

@section('scripts')
<script>
    $(document).ready(function () {
        $('.select2').select2({
            placeholder: "Select",
            allowClear: true,
            width: '100%',
        });
    });
</script>
<script>
    $(function(){
      $('#mytable').DataTable({
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>><"table-responsive"t><"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
        pageLength: 10,
        displayLength: 10,
        lengthMenu: [10, 25, 50, 75, 100],
      });
    });
</script>
@endsection