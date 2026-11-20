@extends('layouts.app')
@section('content')
    <!-- Content -->

    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="py-3 breadcrumb-wrapper mb-4"><span class="text-muted fw-light">{{ __('Order') }} </span></h4>

        <!-- DataTable with Buttons -->
        <div class="card">
            <div class="card-header flex-column flex-md-row">
                <div class="head-label" style="margin-bottom:10px">
                    <h5 class="card-title mb-0">{{ __('Order Listing') }}</h5>
                </div>
                <div class="col-md-12 col-12 mb-4">
                    <form method="GET">
                        <div class="row g-2 align-items-end">

                            <div class="col-md-6">
                                <label>{{ __('Date') }}</label>
                                <div class="input-group input-daterange" >
                                    <input type="datetime-local" class="form-control" name="date_from" value="{{$date_from??''}}"/>
                                <span class="input-group-text">{{ __('to') }}</span>
                                <input type="datetime-local" class="form-control" name="date_to" value="{{$date_to??''}}"/>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <label>{{ __('Branches') }}</label>
                                <select name="branch_id[]" class="form-select select2" multiple>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ collect(request('branch_id'))->contains($branch->id) ? 'selected' : '' }}>
                                            {{ $branch->branch_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label>{{ __('Companies') }}</label>
                                <select name="company_id[]" class="form-select select2" multiple>
                                    @foreach ($companies as $company)
                                        <option value="{{ $company->id }}" {{ collect(request('company_id'))->contains($company->id) ? 'selected' : '' }}>
                                            {{ $company->company_code }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-12 text-end mt-2">
                                <button class="btn btn-primary">{{ __('Filter') }}</button>
                            </div>

                        </div>
                    </form>
                </div>
                <div class="row">
                    <div class="col-sm-3 col-lg-3 mb-3">
                        <div class="card card-border-shadow-primary h-100 cursor-pointer" onclick="showOrdersTable()">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2 pb-1">
                            <h4 class="ms-1 mb-0">{{ __('Total Order') }}</h4>
                            </div>
                            <p class="mb-1" style="margin:10px;font-size:18px">{{ $activeOrderCount }}</p>
                        </div>
                        </div>
                    </div>
                    <div class="col-sm-3 col-lg-3 mb-3">
                        <div class="card card-border-shadow-primary h-100 cursor-pointer" onclick="showAmountTable()">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2 pb-1">
                            <h4 class="ms-1 mb-0">{{ __('Total Amount') }}</h4>
                            </div>
                            <p class="mb-1" style="margin:10px;font-size:18px">{{ money($activeOrderTotal) }}</p>
                        </div>
                        </div>
                    </div>
                    <div class="col-sm-3 col-lg-3 mb-3">
                        <div class="card card-border-shadow-primary h-100 cursor-pointer" onclick="showProfitTable()">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-2 pb-1">
                                <h4 class="ms-1 mb-0">{{ __('Total Profit') }}</h4>
                                </div>
                                <p class="mb-1" style="margin:10px;font-size:18px">{{ money($total_profit ?? 0) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="ordersTableWrapper" class="card-datatable text-nowrap">
                <table class="dt-column-search table table-bordered" id="mytable">
                    <thead>
                        <tr>
                            <th>{{ __('No') }}</th>
                            <th>{{ __('Order No') }}</th>
                            <th>{{ __('Order Date') }}</th>
                            <th>{{ __('Cashier') }}</th>
                            <th>{{ __('Total Product') }}</th>
                            <th>{{ __('Total Quantity') }}</th>
                            <th>{{ __('Total Price') }}</th>
                            <th>{{ __('Payment Method') }}</th>
                            <th>{{ __('Received Amount') }}</th>
                            <th>{{ __('Change') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order as $index => $row)
                        <tr style="{{ $row->status <> 'Active' ? 'background: lightgrey;' : '' }}">
                            <td>{{$index+1??""}}</td>
                            <td>{{$row->order_no??""}}</td>
                            <td>{{$row->created_at??""}}</td>
                            <td>{{$row->user->username??""}}</td>
                            <td>{{$row->total_product??""}}</td>
                            <td>{{$row->total_item??""}}</td>
                            <td data-order="{{ $row->final_total }}">{{ money($row->final_total) }}</td>
                            <td>{{$row->payment_method??""}}</td>
                            <td>{{ money($row->amount_received) }}</td>
                            <td>{{ money($row->change) }}</td>
                            <td>{{$row->status??""}}</td>

                            <td>
                                <a href="{{ route('order.view',$row) }}" onclick="showLoading()"><i class="fa-solid fa-eye"></i></a>

                                @if (auth()->user()->role_id == 1 || auth()->user()->role_id == 2 || auth()->user()->role_id == 3)
                                    @if($row->status !== 'Voided')
                                        <a href="javascript:void(0)"
                                        class="text-danger"
                                        onclick="openVoidModal('{{ route('order.void', $row->id) }}')">
                                            <i class="fa-solid fa-ban"></i>
                                        </a>
                                    @endif
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div id="amountTableWrapper" class="card-datatable text-nowrap d-none">
                <table class="dt-column-search table table-bordered" id="mytable2">
                    <thead>
                        <tr>
                            <th>{{ __('No.') }}</th>
                            <th>{{ __('Branch') }}</th>
                            <th>{{ __('Company') }}</th>
                            <th>{{ __('Category') }}</th>
                            <th>{{ __('Number of Transactions') }}</th>
                            <th>{{ __('Total Amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($categoryTotals as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $item->branch->branch_name ?? '' }}</td>
                            <td>{{ $item->company->company_name ?? '' }}</td>
                            <td>{{ $item->category->category_name ?? '' }}</td>
                            <td>{{ $item->order_item_count }}</td>
                            <td>{{ money($item->total_amount ?? 0) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="5" class="text-end">{{ __('Total') }}:</th>
                            <th>{{ money($categoryTotals->sum('total_amount') ?? 0) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div id="profitTableWrapper" class="card-datatable text-nowrap d-none">
                <table class="dt-column-search table table-bordered" id="mytable3">
                    <thead>
                        <tr>
                            <th>{{ __('No.') }}</th>
                            <th>{{ __('Branch') }}</th>
                            <th>{{ __('Company') }}</th>
                            <th>{{ __('Category') }}</th>
                            <th>{{ __('Total Profit') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($categoryProfits as $index => $profit_item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $profit_item->branch->branch_name ?? '' }}</td>
                            <td>{{ $profit_item->company->company_name ?? '' }}</td>
                            <td>{{ $profit_item->category->category_name ?? '' }}</td>
                            <td>{{ money($profit_item->total_amount ?? 0) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4" class="text-end">{{ __('Total') }}:</th>
                            <th>{{ money($categoryProfits->sum('total_amount') ?? 0) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="voidModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" id="voidForm">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-danger">{{ __('Void Order') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <p class="mb-2">{{ __('Please provide a reason for voiding this order') }}:</p>

                        <textarea name="voided_reason"
                                class="form-control"
                                rows="4"
                                {{ __('required') }}
                                placeholder="{{ __('E.g. Wrong item, payment mistake, duplicate order') }}"></textarea>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit" class="btn btn-danger">
                            {{ __('Confirm Void') }}
                        </button>
                    </div>
                </div>
            </form>
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
      var table = $('#mytable2').DataTable({
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>><"table-responsive"t><"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
        pageLength: 10,
        displayLength: 5,
        lengthMenu: [5, 10, 25, 50, 75, 100],
      });
      var table = $('#mytable3').DataTable({
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>><"table-responsive"t><"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
        pageLength: 10,
        displayLength: 5,
        lengthMenu: [5, 10, 25, 50, 75, 100],
      });
    });

    function openVoidModal(action) {
        const form = document.getElementById('voidForm');
        form.action = action;

        const modal = new bootstrap.Modal(document.getElementById('voidModal'));
        modal.show();
    }
</script>
<script>
function showOrdersTable() {
    document.getElementById('ordersTableWrapper').classList.remove('d-none');
    document.getElementById('amountTableWrapper').classList.add('d-none');
    document.getElementById('profitTableWrapper').classList.add('d-none');

    // redraw DataTable if needed
    if ($.fn.DataTable.isDataTable('#mytable')) {
        $('#mytable').DataTable().columns.adjust().draw(false);
    }
}

function showAmountTable() {
    document.getElementById('ordersTableWrapper').classList.add('d-none');
    document.getElementById('amountTableWrapper').classList.remove('d-none');
    document.getElementById('profitTableWrapper').classList.add('d-none');

    if ($.fn.DataTable.isDataTable('#mytable2')) {
        $('#mytable2').DataTable().columns.adjust().draw(false);
    }
}

function showProfitTable() {
    document.getElementById('ordersTableWrapper').classList.add('d-none');
    document.getElementById('amountTableWrapper').classList.add('d-none');
    document.getElementById('profitTableWrapper').classList.remove('d-none');

    if ($.fn.DataTable.isDataTable('#mytable3')) {
        $('#mytable3').DataTable().columns.adjust().draw(false);
    }
}
</script>
@endsection
