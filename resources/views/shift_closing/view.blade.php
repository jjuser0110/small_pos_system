@extends('layouts.app')
@section('content')
    <!-- Content -->

    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="py-3 breadcrumb-wrapper mb-4"><span class="text-muted fw-light">Shift Closing Method</span></h4>

        <!-- DataTable with Buttons -->
        <div class="card">
            <div class="card-header flex-column flex-md-row">
                <div class="head-label">
                    <h5 class="card-title mb-0">{{$shift_closing->user->username??''}}</h5><br>
                    <p>Total Order : {{$shift_closing->total_order_count??0}}<br>Total Amount : {{number_format($shift_closing->total_order_amount??0,2)}}<br>{{$shift_closing->first_sale_time??''}} to {{$shift_closing->closing_time??'Not Yet Close'}}</p>
                </div>
                <p style="margin-top:10px"></p>
            </div>
            <div class="card-datatable text-nowrap">
                <table class="dt-column-search table table-bordered" id="mytable">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Payment Method</th>
                            <th>Amount (RM)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($shift_closing->items as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $item->payment_method ?? '' }}</td>
                            <td>{{ number_format($item->amount ?? 0, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="2" class="text-end">Total:</th>
                            <th>{{ number_format($shift_closing->items->sum('amount'), 2) }}</th>
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
        });
  </script>
    @endsection