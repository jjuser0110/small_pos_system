@extends('layouts.app')
@section('content')
    <!-- Content -->

    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="py-3 breadcrumb-wrapper mb-4"><span class="text-muted fw-light">Order</span></h4>

        <!-- DataTable with Buttons -->
        <div class="card">
            <div class="card-header flex-column flex-md-row">
                <div class="head-label">
                    <h5 class="card-title mb-0">{{$order->order_no??''}}</h5>
                </div>
                <p style="margin-top:10px"></p>
            </div>
            <div class="card-datatable text-nowrap">
                <table class="dt-column-search table table-bordered" id="mytable">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Product</th>
                            <th>Unit Price</th>
                            <th>Quantity</th>
                            <th>Total Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $item->displayName() }}</td>
                            <td>{{ money($item->single_price ?? 0) }}</td>
                            <td>{{ number_format($item->quantity ?? 0, 0) }}</td>
                            <td>{{ money($item->total_price ?? 0) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4" class="text-end">Total:</th>
                            <th>{{ money($order->items->sum('total_price')) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="card-datatable text-nowrap">
                <table class="dt-column-search table table-bordered" id="mytable2">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Product</th>
                            <th>Batch</th>
                            <th>Cost Price</th>
                            <th>Selling Price</th>
                            <th>Quantity</th>
                            <th>Total Cost Price</th>
                            <th>Total Selling Price</th>
                            <th>Total Profit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->profit_items as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $item->product->product_name ?? '' }}</td>
                            <td>{{ $item->batch->batch_no ?? '' }}</td>
                            <td>{{ money($item->cost_price ?? 0) }}</td>
                            <td>{{ money($item->selling_price ?? 0) }}</td>
                            <td>{{ number_format($item->quantity ?? 0, 2) }}</td>
                            <td>{{ money($item->total_cost_price ?? 0) }}</td>
                            <td>{{ money($item->total_selling_price ?? 0) }}</td>
                            <td>{{ money($item->total_earning ?? 0) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="6" class="text-end">Total:</th>
                            <th>{{ money($order->profit_items->sum('total_cost_price')) }}</th>
                            <th>{{ money($order->profit_items->sum('total_selling_price')) }}</th>
                            <th>{{ money($order->profit_items->sum('total_earning')) }}</th>
                        </tr>
                    </tfoot>
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
            var table2 = $('#mytable2').DataTable({
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>><"table-responsive"t><"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
                pageLength: 10,
                displayLength: 5,
                lengthMenu: [5, 10, 25, 50, 75, 100],
            });
        });
  </script>
    @endsection
