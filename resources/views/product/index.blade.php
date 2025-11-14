@extends('layouts.app')
@section('content')
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
                            <th>No</th>
                            <th>Product Name</th>
                            <th>Product Code</th>
                            <th>Category</th>
                            <th>Barcode</th>
                            <th>Selling Price</th>
                            <th>Uom</th>
                            <th>Stock Bal.</th>
                            <th>Company</th>
                            <th>Branch</th>
                            <th>Arrange</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($product as $index => $row)
                        <tr>
                            <td>{{$index+1??""}}</td>
                            <td>{{$row->product_name??""}}</td>
                            <td>{{$row->product_code??""}}</td>
                            <td>{{$row->category->category_name??""}}</td>
                            <td>{{$row->barcode??""}}</td>
                            <td>{{$row->selling_price??""}}</td>
                            <td>{{$row->uom_dt->uom_unit??""}}</td>
                            <td>{{$row->stock_quantity??""}}</td>
                            <td>{{$row->company->company_code??""}}</td>
                            <td>{{$row->branch->branch_code??""}}</td>
                            <td>{{$row->arrangement??""}}</td>
                            <td><?php echo isset($row)&&$row->is_active == 1?'<span style="color:green">Active</span>':'<span style="color:red">Inactive</span>'?></td>
                            <td>
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