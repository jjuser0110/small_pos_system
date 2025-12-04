@extends('layouts.app')
@section('content')
    <!-- Content -->

    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="py-3 breadcrumb-wrapper mb-4"><span class="text-muted fw-light">Shift Closing </span></h4>

        <!-- DataTable with Buttons -->
        <div class="card">
            <div class="card-header flex-column flex-md-row">
                <div class="head-label">
                    <h5 class="card-title mb-0">Shift Closing Listing</h5>
                </div>
            </div>
            <div class="card-datatable text-nowrap">
                <table class="dt-column-search table table-bordered" id="mytable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>User</th>
                            <th>Total Order Count</th>
                            <th>Total Order Amount</th>
                            <th>First Sale Time</th>
                            <th>Closing Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($shift_closing as $index => $row)
                        <tr>
                            <td>{{$index+1??""}}</td>
                            <td>{{$row->user->username??0}}</td>
                            <td>{{$row->total_order_count??0}}</td>
                            <td>{{number_format($row->total_order_amount??0,2)}}</td>
                            <td>{{$row->first_sale_time??''}}</td>
                            <td>{{$row->closing_time??''}}</td>
                            <td>
                                <a href="{{ route('shift_closing.view',$row) }}" onclick="showLoading()"><i class="fa-solid fa-eye"></i></a>
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