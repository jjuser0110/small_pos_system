@extends('layouts.app')
@section('content')
    <!-- Content -->

    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="py-3 breadcrumb-wrapper mb-4"><span class="text-muted fw-light">Product Log </span></h4>

        <!-- DataTable with Buttons -->
        <div class="card">
            <div class="card-header flex-column flex-md-row">
                <div class="head-label">
                    <h5 class="card-title mb-0">Product Log Listing</h5>
                </div>
                <p style="margin-top:10px">Product : {{$product->product_name??0}}<br>Stock : {{$product->stock_quantity??0}}</p>
            </div>
            <div class="card-datatable text-nowrap">
                <table class="dt-column-search table table-bordered" id="mytable">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Before</th>
                            <th>Stock</th>
                            <th>After</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($product->stock_logs as $index => $row)
                        <tr>
                            <td>{{$row->type??""}}</td>
                            <td>{{$row->description??""}}</td>
                            <td>{{$row->before_stock??""}}</td>
                            <td>{{$row->quantity??""}}</td>
                            <td>{{$row->after_stock??""}}</td>
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