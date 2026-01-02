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
        <h4 class="py-3 breadcrumb-wrapper mb-4"><span class="text-muted fw-light">Product </span></h4>

        <!-- DataTable with Buttons -->
        <div class="card">
            <div class="card-header flex-column flex-md-row">
                <div class="head-label">
                    <h5 class="card-title mb-0">Product Listing</h5>
                </div>
                <div class="dt-action-buttons text-end pt-3 pt-md-0">
                    <div class="dt-buttons">
                        <a class="dt-button create-new btn btn-secondary" type="button" href="{{ route('product.downloadTemplate') }}">
                            <span><i class="bx bx-download me-sm-1"></i>
                                <span class="d-none d-sm-inline-block">Download Template</span>
                            </span>
                        </a>

                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadProductModal">
                            <span><i class="bx bx-upload me-sm-1"></i>
                                <span class="d-none d-sm-inline-block">Import</span>
                            </span>
                        </button>

                        <a class="dt-button create-new btn btn-primary" type="button" href="{{route('product.create')}}" onclick="showLoading()">
                            <span><i class="bx bx-plus me-sm-1"></i>
                                <span class="d-none d-sm-inline-block">Add New Record</span>
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
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Barcode</th>
                            <th>S.P.</th>
                            <th>Uom</th>
                            <th>Stock Bal.</th>
                            <th>Company</th>
                            <th>Branch</th>
                            {{-- <th>Arrange</th> --}}
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($product as $index => $row)
                        <tr>
                            <td>{{$index+1??""}}</td>
                            <td>{{$row->product_name??""}}</td>
                            <td>{{$row->category->category_name??""}}</td>
                            <td>{{$row->barcode??""}}</td>
                            <td>{{$row->selling_price??""}}</td>
                            <td>{{$row->uom_dt->uom_unit??""}}</td>
                            <td>{{$row->stock_quantity??""}}</td>
                            <td>{{$row->company->company_code??""}}</td>
                            <td>{{$row->branch->branch_code??""}}</td>
                            {{-- <td>{{$row->arrangement??""}}</td> --}}
                            <td><?php echo isset($row)&&$row->is_active == 1?'<span style="color:green">Active</span>':'<span style="color:red">Inactive</span>'?></td>
                            <td>
                                @if($row->connected_product_quantity > 0)
                                <a style="color:red;cursor:pointer" onclick="if(confirm('Are you sure you want to convert this product to smaller unit?')){window.location.href='{{ route('product.convert',$row) }}'}"><i class="fa-solid fa-exchange-alt"></i></a>
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
                    <h5 class="modal-title">Upload Product Excel</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form action="{{ route('product.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="select2Basic" class="form-label">Company</label>
                            <select id="select2Basic" name="company_id" class="select2 form-select" data-allow-clear="true">
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}" @if(isset($category) && $category->company_id == $company->id) selected @endif>
                                        {{ $company->company_name ?? '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Select Excel File</label>
                            <input type="file" name="file" class="form-control" accept=".xlsx,.csv" required>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            Upload
                        </button>
                    </div>

                </form>

            </div>
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
    });
  </script>
    @endsection
