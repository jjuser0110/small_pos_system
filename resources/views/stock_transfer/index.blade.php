@extends('layouts.app')

@section('content')
<style>
    #mytable th, #mytable td { padding: 10px; }
</style>
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        <a class="text-muted fw-light" href="{{ route('home') }}">Home /</a> Stock Transfer
    </h4>

    @if($canSend)
    <div class="card mb-4">
        <h5 class="card-header">Send Stock to Another Company</h5>
        <div class="card-body">
            <form method="post" action="{{ route('stock_transfer.store') }}" class="row g-3" onsubmit="showLoading()">
                @csrf
                <div class="col-md-6">
                    <label class="form-label">From company</label>
                    <select id="from_company" class="form-select" required>
                        <option value="">-- Select --</option>
                        @foreach($fromCompanies as $c)
                            <option value="{{ $c->id }}">{{ $c->company_name }} ({{ $c->company_code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">From item</label>
                    <select name="from_product_id" id="from_product" class="form-select" required></select>
                    <small class="text-muted" id="from_stock"></small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">To company</label>
                    <select id="to_company" class="form-select" required>
                        <option value="">-- Select --</option>
                        @foreach($toCompanies as $c)
                            <option value="{{ $c->id }}">{{ $c->company_name }} ({{ $c->company_code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">To item <small class="text-muted">(the same goods in that company, may have a different name)</small></label>
                    <select name="to_product_id" id="to_product" class="form-select" required></select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Quantity</label>
                    <input type="number" step="0.01" min="0.01" name="quantity" class="form-control" value="{{ old('quantity') }}" required>
                </div>
                <div class="col-md-9">
                    <label class="form-label">Reason</label>
                    <input type="text" name="reason" maxlength="500" class="form-control" value="{{ old('reason') }}">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Create Transfer</button>
                    <small class="text-muted ms-2">The sending company's staff confirm it is sent, then the receiving company's staff confirm it is received. Only then does stock move.</small>
                </div>
            </form>
        </div>
    </div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Transfers</h5>
            <form method="get">
                <select name="status" class="form-select no-select2" onchange="this.form.submit()">
                    <option value="">All</option>
                    @foreach(['pending' => 'Waiting', 'approved' => 'Received', 'rejected' => 'Rejected'] as $s => $label)
                        <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
        </div>
        <div class="card-datatable text-nowrap">
            <table class="dt-column-search table table-bordered" id="mytable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Qty</th>
                        <th>Reason</th>
                        <th>Sent By</th>
                        <th>Status</th>
                        <th>Sender Confirmed</th>
                        <th>Received / Rejected</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transfers as $t)
                    <tr>
                        <td>{{ $t->id }}</td>
                        <td>{{ $t->created_at }}</td>
                        <td>{{ $t->fromCompany->company_code ?? '' }}: {{ $t->fromProduct->product_name ?? '' }}</td>
                        <td>{{ $t->toCompany->company_code ?? '' }}: {{ $t->toProduct->product_name ?? '' }}</td>
                        <td>{{ rtrim(rtrim(number_format($t->quantity, 2), '0'), '.') }}</td>
                        <td>{{ $t->reason }}</td>
                        <td>{{ $t->requester->name ?? '' }}</td>
                        <td>
                            <span style="color:{{ $t->isPending() ? 'orange' : ($t->status == 'approved' ? 'green' : 'red') }}">{{ $t->stepLabel() }}</span>
                            @if($t->reject_reason)<br><small>{{ $t->reject_reason }}</small>@endif
                        </td>
                        <td>{{ $t->senderConfirmer->name ?? '' }}<br><small>{{ $t->sender_confirmed_at }}</small></td>
                        <td>{{ $t->approver->name ?? '' }}<br><small>{{ $t->status_at }}</small></td>
                        <td style="white-space:nowrap">
                            @if($t->canBeDecidedBy(Auth::user()))
                                <div class="d-flex gap-2">
                                    @if($t->status == 'pending_sender')
                                    <form method="post" action="{{ route('stock_transfer.approve', $t) }}" class="m-0" onsubmit="return confirm('Confirm {{ rtrim(rtrim(number_format($t->quantity, 2), '0'), '.') }} x {{ addslashes($t->fromProduct->product_name ?? '') }} is correct and being sent to {{ addslashes($t->toCompany->company_code ?? '') }}?')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">Confirm Sent</button>
                                    </form>
                                    @else
                                    <form method="post" action="{{ route('stock_transfer.approve', $t) }}" class="m-0" onsubmit="return confirm('Confirm you received {{ rtrim(rtrim(number_format($t->quantity, 2), '0'), '.') }} x {{ addslashes($t->toProduct->product_name ?? '') }}? The stock will be moved now.')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">Confirm Received</button>
                                    </form>
                                    @endif
                                    <form method="post" action="{{ route('stock_transfer.reject', $t) }}" class="m-0" onsubmit="var r = prompt('Reject reason (optional):'); if (r === null) return false; this.reject_reason.value = r; return true;">
                                        @csrf
                                        <input type="hidden" name="reject_reason">
                                        <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                                    </form>
                                </div>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function(){
    $('#mytable').DataTable({
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>><"table-responsive"t><"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50, 75, 100],
        order: [[0, 'desc']],
        columnDefs: [{ orderable: false, targets: -1 }],
    });

    @if($canSend)
    var products = @json($products);

    function fill(selectId, companyId, showStock) {
        var select = document.getElementById(selectId);
        select.innerHTML = '<option value="">-- Select item --</option>';
        products.filter(function (p) { return String(p.company_id) === String(companyId); })
            .forEach(function (p) {
                var opt = document.createElement('option');
                opt.value = p.id;
                opt.dataset.stock = p.stock_quantity;
                opt.textContent = p.product_name + (p.barcode ? ' [' + p.barcode + ']' : '') + (showStock ? ' (stock ' + p.stock_quantity + ')' : '');
                select.appendChild(opt);
            });
    }

    $('#from_company').on('change', function () { fill('from_product', this.value, true); $('#from_stock').text(''); });
    $('#to_company').on('change', function () { fill('to_product', this.value, false); });
    $('#from_product').on('change', function () {
        var stock = $(this).find('option:selected').data('stock');
        $('#from_stock').text(stock !== undefined ? 'In stock: ' + stock : '');
    });
    @endif
});
</script>
@endsection
