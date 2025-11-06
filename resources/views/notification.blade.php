@extends('layouts.app')

@section('content')
<section role="main" class="content-body">
    <header class="page-header">
        <h2>Notification</h2>
    </header>

    @include('layouts.flash-message')
    <div class="row pt-4 mt-1">
        <div class="col-xl-6">
            <section class="card">
                <header class="card-header card-header-transparent">
                    <h2 class="card-title">Package Invoice</h2>
                </header>
                <div class="card-body">
                    <table class="table table-responsive-md table-striped mb-0" id="datatable-packageinvoice">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Package</th>
                                <th>P.Price</th>
                                <th>From Date</th>
                                <th>Expiry Date</th>
                                <th>Days to Due</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($package_invoices as $customer)
                                <tr>
                                    <td><a href="{{ route('customer.view',$customer) }}">{{ $customer->code }}</a></td>
                                    <td>{{ $customer->name }}</td>
                                    <td>{{ $customer->package->package_name ?? '' }}</td>
                                    <td>{{ $customer->package->amount ?? '' }}</td>
                                    <td>{{ $customer->last_package_invoice->invoice_date ?? '' }}</td>
                                    <td>{{ $customer->last_package_invoice->invoice_date_to ?? '' }}</td>
                                    <td>{{ $customer->last_package_invoice->days_to_due ?? '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
        <div class="col-xl-6">
            <section class="card">
                <header class="card-header card-header-transparent">
                    <h2 class="card-title">User Wallet</h2>
                </header>
                <div class="card-body">
                    <table class="table table-responsive-md table-striped mb-0" id="datatable-userwallet">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Wallet</th>
                                <th>Last Invoice Total</th>
                                <th>Wallet to Total Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($user_wallets as $customer)
                                <tr>
                                    <td>{{ $customer->code }}</td>
                                    <td>{{ $customer->name }}</td>
                                    <td>{{ $customer->wallet }}</td>
                                    <td>{{ $customer->last_invoice->total ?? '' }}</td>
                                    <td>{{ $customer->wallet_to_total_rate }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</section>

@endsection
@section('page-js')
    <script src="{{ asset('porto-assets/vendor/select2/js/select2.js') }}"></script>
    <script src="{{ asset('porto-assets/vendor/datatables/media/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('porto-assets/vendor/datatables/media/js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('porto-assets/vendor/datatables/extras/TableTools/Buttons-1.4.2/js/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('porto-assets/vendor/datatables/extras/TableTools/Buttons-1.4.2/js/buttons.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('porto-assets/vendor/datatables/extras/TableTools/Buttons-1.4.2/js/buttons.html5.min.js') }}"></script>
    <script src="{{ asset('porto-assets/vendor/datatables/extras/TableTools/Buttons-1.4.2/js/buttons.print.min.js') }}"></script>
    <script src="{{ asset('porto-assets/vendor/datatables/extras/TableTools/JSZip-2.5.0/jszip.min.js') }}"></script>
    <script src="{{ asset('porto-assets/vendor/datatables/extras/TableTools/pdfmake-0.1.32/pdfmake.min.js') }}"></script>
    <script src="{{ asset('porto-assets/vendor/datatables/extras/TableTools/pdfmake-0.1.32/vfs_fonts.js') }}"></script>
@endsection
@section('scripts')
    <script src="{{ asset('porto-assets/js/examples/examples.datatables.default.js') }}"></script>
    <script src="{{ asset('porto-assets/js/examples/examples.datatables.row.with.details.js') }}"></script>
    <script src="{{ asset('porto-assets/js/examples/examples.datatables.tabletools.js') }}"></script>
    <script>
        $('#datatable-packageinvoice').DataTable({
            paging: false,
            searching: false,
            ordering: false,
            info: false,
            scrollX: true,
        });
        $('#datatable-userwallet').DataTable({
            paging: false,
            searching: false,
            ordering: false,
            info: false,
            scrollX: true,
        });
    </script>


@endsection
