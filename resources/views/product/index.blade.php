@extends('layouts.app')
@section('content')
    <style>
        #mytable th,
        #mytable td {
            padding: 10px;
        }
    </style>
    <!-- Content -->

    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="py-3 breadcrumb-wrapper mb-4"><span class="text-muted fw-light">{{ __('Item List') }} </span></h4>

        <!-- DataTable with Buttons -->
        <div class="card">
            <div class="card-header flex-column flex-md-row">
                <div class="head-label">
                    <h5 class="card-title mb-0">{{ __('Item List') }}</h5>
                </div>
                <div class="dt-action-buttons text-end pt-3 pt-md-0">
                    <div class="dt-buttons">
                        <a class="dt-button create-new btn btn-secondary" type="button" href="{{ route('product.downloadTemplate') }}">
                            <span><i class="bx bx-download me-sm-1"></i>
                                <span class="d-none d-sm-inline-block">{{ __('Download Template') }}</span>
                            </span>
                        </a>

                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadProductModal">

                            <span><i class="bx bx-upload me-sm-1"></i>
                                <span class="d-none d-sm-inline-block">{{ __('Import') }}</span>
                            </span>
                        </button>

                        <a class="dt-button create-new btn btn-primary" type="button" href="{{route('product.create')}}" onclick="showLoading()">
                            <span><i class="bx bx-plus me-sm-1"></i>
                                <span class="d-none d-sm-inline-block">{{ __('Add New Record') }}</span>
                            </span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-datatable text-nowrap">
                <table class="dt-column-search table table-bordered" id="mytable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('Item Name') }}</th>
                            <th>{{ __('Category') }}</th>
                            <th>{{ __('Barcode') }}</th>
                            <th>{{ __('Price') }}</th>
                            <th>{{ __('Unit') }}</th>
                            <th>{{ __('Stock Bal.') }}</th>
                            <th>{{ __('Sell Options') }}</th>
                            <th>{{ __('Company') }}</th>
                            <th>{{ __('Branch') }}</th>
                            {{-- <th>Arrange</th> --}}
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($product as $index => $row)
                        <tr>
                            <td>{{$index+1??""}}</td>
                            <td>{{$row->product_name??""}}</td>
                            <td>{{$row->category->category_name??""}}</td>
                            <td>{{$row->barcode??""}}</td>
                            <td data-order="{{ $row->selling_price }}">{{ money($row->selling_price) }}</td>
                            <td>{{$row->uom_dt->uom_unit??""}}</td>
                            <td @if($row->stock_quantity < 0) style="color:red" @endif>{{ $row->stock_quantity + 0 }}</td>
                            <td>
                                @foreach($row->units as $unit)
                                    <span class="badge bg-label-primary mb-1">{{ $unit->name }} &times;{{ $unit->base_qty + 0 }} · RM {{ number_format($unit->price, 2) }}</span>
                                @endforeach
                            </td>
                            <td>{{$row->company->company_code??""}}</td>
                            <td>{{$row->branch->branch_code??""}}</td>
                            {{-- <td>{{$row->arrangement??""}}</td> --}}
                            <td>@if(isset($row) && $row->is_active == 1)<span style="color:green">{{ __('Active') }}</span>@else<span style="color:red">{{ __('Inactive') }}</span>@endif</td>
                            <td>
                                @if(Auth::user()->role_id == 1)
                                <a style="color:#f0ad00;cursor:pointer" title="{{ __('Adjust Stock') }}"
                                onclick="openAdjustModal('{{ $row->id }}', '{{ addslashes($row->product_name) }}', '{{ $row->stock_quantity }}')">
                                    <i class="fa-solid fa-boxes-stacked"></i>
                                </a>
                                @endif
                                <a href="{{ route('product.viewlog',$row) }}" onclick="showLoading()"><i class="fa-solid fa-eye"></i></a>
                                <a href="{{ route('product.edit',$row) }}" onclick="showLoading()"><i class="fa-solid fa-pen-to-square"></i></a>
                                <a style="color:red;cursor:pointer" onclick="if(confirm('Are you sure you want to delete?')){showLoading();window.location.href='{{ route('product.destroy',$row) }}'}"><i class="fa-solid fa-trash"></i></a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- / Content -->

    <div class="modal fade" id="uploadProductModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Upload Items Excel') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form action="{{ route('product.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="select2Basic" class="form-label">{{ __('Company') }}</label>
                            <select id="select2Basic" name="company_id" class="select2 form-select" data-allow-clear="true">
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}" @if(\App\Support\CompanyPick::id() == $company->id) selected @endif>
                                        {{ $company->company_name ?? '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Select Excel File') }}</label>
                            <input type="file" name="file" class="form-control" accept=".xlsx,.csv" required>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit" class="btn btn-primary">
                            {{ __('Upload') }}
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>

    <div class="modal fade" id="adjustStockModal" tabindex="-1">
        <div class="modal-dialog">
            <form class="modal-content" method="post" action="{{ route('stock_adjustment.store') }}" onsubmit="showLoading()">
                @csrf
                <input type="hidden" name="product_id" id="adjust_product_id">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Adjust Stock') }}: <span id="adjustProductName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">{{ __('Current stock') }}: <b id="adjustCurrentStock"></b></p>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Type') }}</label>
                        <select name="type" id="adjust_type" class="form-select" required>
                            <option value="adjust_in">{{ __('Adjust In (add stock)') }}</option>
                            <option value="adjust_out">{{ __('Adjust Out (reduce stock)') }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Quantity') }}</label>
                        <input type="number" step="0.01" min="0.01" name="quantity" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Reason') }}</label>
                        <textarea name="reason" class="form-control" rows="3" maxlength="500" required></textarea>
                    </div>
                    <small class="text-muted">{{ __('Applied immediately and saved in the stock log.') }}</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
                </div>
            </form>
        </div>
    </div>

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

        window.openAdjustModal = function(id, name, stock){
            $('#adjust_product_id').val(id);
            $('#adjustProductName').text(name);
            $('#adjustCurrentStock').text(stock);
            $('#adjust_type').val('adjust_in').trigger('change');
            $('#adjustStockModal').modal('show');
        };
    });
    </script>
    @endsection
