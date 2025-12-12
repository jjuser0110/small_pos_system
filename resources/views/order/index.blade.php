@extends('layouts.app')
@section('content')
    <!-- Content -->

    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="py-3 breadcrumb-wrapper mb-4"><span class="text-muted fw-light">Order </span></h4>

        <!-- DataTable with Buttons -->
        <div class="card">
            <div class="card-header flex-column flex-md-row">
                <div class="head-label" style="margin-bottom:10px">
                    <h5 class="card-title mb-0">Order Listing</h5>
                </div>
                <div class="col-md-6 col-12 mb-4">
                    <form method="GET">
                        <div class="input-group input-daterange" >
                            <input type="datetime-local" class="form-control" name="date_from" value="{{$date_from??''}}"/>
                            <span class="input-group-text">to</span>
                            <input type="datetime-local" class="form-control" name="date_to" value="{{$date_to??''}}"/>
                            <button class="btn btn-primary" type="submit" >Filter</button>
                        </div>
                    </form>
                </div>
                <div class="row">
                    <div class="col-sm-3 col-lg-3 mb-3">
                        <div class="card card-border-shadow-primary h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2 pb-1">
                            <h4 class="ms-1 mb-0">Total Order</h4>
                            </div>
                            <p class="mb-1" style="margin:10px;font-size:18px">{{$order->count()??0}}</p>
                            </p>
                        </div>
                        </div>
                    </div>
                    <div class="col-sm-3 col-lg-3 mb-3">
                        <div class="card card-border-shadow-primary h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2 pb-1">
                            <h4 class="ms-1 mb-0">Total Amount</h4>
                            </div>
                            <p class="mb-1" style="margin:10px;font-size:18px">{{number_format($order->sum('final_total')??0,2)}}</p>
                            </p>
                        </div>
                        </div>
                    </div>
                    <div class="col-sm-3 col-lg-3 mb-3">
                        <div class="card card-border-shadow-primary h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-2 pb-1">
                                <h4 class="ms-1 mb-0">Total Profit</h4>
                                </div>
                                <p class="mb-1" style="margin:10px;font-size:18px">{{ number_format($total_profit ?? 0, 2) }}</p>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-datatable text-nowrap">
                <table class="dt-column-search table table-bordered" id="mytable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Order No</th>
                            <th>Order Date</th>
                            <th>Cashier</th>
                            <th>Total Product</th>
                            <th>Total Quantity</th>
                            <th>Total Price</th>
                            <th>Payment Method</th>
                            <th>Received Amount</th>
                            <th>Change</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order as $index => $row)
                        <tr>
                            <td>{{$index+1??""}}</td>
                            <td>{{$row->order_no??""}}</td>
                            <td>{{$row->created_at??""}}</td>
                            <td>{{$row->user->username??""}}</td>
                            <td>{{$row->total_product??""}}</td>
                            <td>{{$row->total_item??""}}</td>
                            <td>{{$row->final_total??""}}</td>
                            <td>{{$row->payment_method??""}}</td>
                            <td>{{$row->amount_received??""}}</td>
                            <td>{{$row->change??""}}</td>
                            <td>
                                <a href="{{ route('order.view',$row) }}" onclick="showLoading()"><i class="fa-solid fa-eye"></i></a>
                                <!-- <a href="{{ route('order.edit',$row) }}" onclick="showLoading()"><i class="fa-solid fa-pen-to-square"></i></a>
                                <a style="color:red;cursor:pointer" onclick="if(confirm('Are you sure you want to delete?')){showLoading();window.location.href='{{ route('order.destroy',$row) }}'}"><i class="fa-solid fa-trash"></i></a> -->
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
