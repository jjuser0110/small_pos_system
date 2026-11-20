@extends('layouts.app')

@section('content')
<style>
    #mytable th, #mytable td { padding: 10px; }
</style>
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        <a class="text-muted fw-light" href="{{ route('home') }}">{{ __('Home') }} /</a> {{ __('Stock Transfer') }}
    </h4>

    @if($canSend)
    <div class="card mb-4">
        <h5 class="card-header">{{ __('Send Stock to Another Company') }}</h5>
        <div class="card-body">
            <form method="post" action="{{ route('stock_transfer.store') }}" id="transferForm" onsubmit="return checkTransfer()">
                @csrf
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="transfer_no">{{ __('Transfer No') }}</label>
                        <input type="text" name="transfer_no" id="transfer_no" maxlength="50" class="form-control"
                               value="{{ old('transfer_no', $nextNumber) }}" placeholder="{{ $nextNumber }}">
                        <small class="text-muted">{{ __('Change it to your own slip no if you have one.') }}</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="from_company">{{ __('From company') }}</label>
                        <select name="from_company_id" id="from_company" class="form-select" required>
                            <option value="">{{ __('-- Select --') }}</option>
                            @foreach($fromCompanies as $c)
                                <option value="{{ $c->id }}" @selected((string) old('from_company_id', \App\Support\CompanyPick::id()) === (string) $c->id)>{{ $c->company_name }} ({{ $c->company_code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="to_company">{{ __('To company') }}</label>
                        <select name="to_company_id" id="to_company" class="form-select" required>
                            <option value="">{{ __('-- Select --') }}</option>
                            @foreach($toCompanies as $c)
                                <option value="{{ $c->id }}" @selected((string) old('to_company_id') === (string) $c->id)>{{ $c->company_name }} ({{ $c->company_code }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <h6 class="mt-4 mb-2">{{ __('Items') }}</h6>
                <div class="row g-2 d-none d-md-flex text-muted small fw-semibold">
                    <div class="col-md-5">{{ __('From item (sending company)') }}</div>
                    <div class="col-md-4">{{ __('To item (same goods in the receiving company, may have a different name)') }}</div>
                    <div class="col-md-2">{{ __('Quantity') }}</div>
                    <div class="col-md-1"></div>
                </div>
                <div id="transferLines"></div>
                <p id="linesHint" class="text-muted small my-2">{{ __('Choose the from and to company first, then add items.') }}</p>
                <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="addLine"><i class="bx bx-plus"></i> {{ __('Add item') }}</button>

                <div class="row g-3 mt-1">
                    <div class="col-12">
                        <label class="form-label" for="reason">{{ __('Reason (for the whole transfer)') }}</label>
                        <input type="text" name="reason" id="reason" maxlength="500" class="form-control" value="{{ old('reason') }}">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">{{ __('Create Transfer') }}</button>
                        <small class="text-muted ms-2 d-block d-md-inline mt-2 mt-md-0">{{ __('The sending company\'s staff confirm it is sent, then the receiving company\'s staff confirm it is received. Only then does stock move.') }}</small>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ __('Transfers') }}</h5>
            <form method="get">
                <select name="status" class="form-select no-select2" onchange="this.form.submit()">
                    <option value="">{{ __('All') }}</option>
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
                        <th>{{ __('Transfer No') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th>From &rarr; To</th>
                        <th>{{ __('Items') }}</th>
                        <th>{{ __('Reason') }}</th>
                        <th>{{ __('Sent By') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Sender Confirmed') }}</th>
                        <th>{{ __('Received / Rejected') }}</th>
                        <th>{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transfers as $t)
                    <tr>
                        <td>{{ $t->id }}</td>
                        <td class="fw-semibold">{{ $t->transfer_no }}</td>
                        <td>{{ $t->created_at }}</td>
                        <td>{{ $t->fromCompany->company_code ?? '' }} &rarr; {{ $t->toCompany->company_code ?? '' }}</td>
                        <td style="white-space:normal; min-width:260px">
                            @foreach($t->items as $line)
                                <div>{{ $line->fromProduct->product_name ?? '' }} &rarr; {{ $line->toProduct->product_name ?? '' }} <b>&times; {{ rtrim(rtrim(number_format($line->quantity, 2), '0'), '.') }}</b></div>
                            @endforeach
                        </td>
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
                                    <form method="post" action="{{ route('stock_transfer.approve', $t) }}" class="m-0" onsubmit="return confirm('Confirm transfer {{ addslashes($t->transfer_no) }} ({{ $t->items->count() }} item line(s)) is correct and being sent to {{ addslashes($t->toCompany->company_code ?? '') }}?')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">{{ __('Confirm Sent') }}</button>
                                    </form>
                                    @else
                                    <form method="post" action="{{ route('stock_transfer.approve', $t) }}" class="m-0" onsubmit="return confirm('Confirm you received everything in transfer {{ addslashes($t->transfer_no) }} ({{ $t->items->count() }} item line(s))? The stock will be moved now.')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">{{ __('Confirm Received') }}</button>
                                    </form>
                                    @endif
                                    <form method="post" action="{{ route('stock_transfer.reject', $t) }}" class="m-0" onsubmit="var r = prompt('Reject reason (optional):'); if (r === null) return false; this.reject_reason.value = r; return true;">
                                        @csrf
                                        <input type="hidden" name="reject_reason">
                                        <button type="submit" class="btn btn-sm btn-danger">{{ __('Reject') }}</button>
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
    var oldLines = @json(array_values(old('items', [])));
    var lineNo = 0;

    function itemOptions(companyId, showStock) {
        var html = '<option value="">-- Select item --</option>';
        products.filter(function (p) { return String(p.company_id) === String(companyId); })
            .forEach(function (p) {
                var text = p.product_name + (p.barcode ? ' [' + p.barcode + ']' : '') + (showStock ? ' (stock ' + Number(p.stock_quantity) + ')' : '');
                html += '<option value="' + p.id + '" data-stock="' + p.stock_quantity + '">' + $('<div>').text(text).html() + '</option>';
            });
        return html;
    }

    function addLine(values) {
        values = values || {};
        var i = lineNo++;
        var row = $(
            '<div class="row g-2 align-items-start transfer-line border-bottom pb-2 mb-2">' +
              '<div class="col-12 col-md-5"><label class="form-label d-md-none small mb-1">From item</label>' +
                '<select name="items[' + i + '][from_product_id]" class="form-select js-from" required></select>' +
                '<small class="text-muted js-stock"></small></div>' +
              '<div class="col-12 col-md-4"><label class="form-label d-md-none small mb-1">To item</label>' +
                '<select name="items[' + i + '][to_product_id]" class="form-select js-to" required></select></div>' +
              '<div class="col-8 col-md-2"><label class="form-label d-md-none small mb-1">Quantity</label>' +
                '<input type="number" step="0.01" min="0.01" inputmode="decimal" name="items[' + i + '][quantity]" class="form-control js-qty" required></div>' +
              '<div class="col-4 col-md-1 d-flex align-items-end align-items-md-start"><button type="button" class="btn btn-outline-danger w-100 js-remove-line" title="Remove"><i class="bx bx-trash"></i></button></div>' +
            '</div>');
        row.find('.js-from').html(itemOptions($('#from_company').val(), true)).val(values.from_product_id || '');
        row.find('.js-to').html(itemOptions($('#to_company').val(), false)).val(values.to_product_id || '');
        row.find('.js-qty').val(values.quantity || '');
        $('#transferLines').append(row);
        window.initSelect2(row);
        showStock(row.find('.js-from'));
        refreshHint();
        return row;
    }

    function showStock(select) {
        var stock = $(select).find('option:selected').data('stock');
        $(select).closest('.transfer-line').find('.js-stock').text(stock !== undefined ? 'In stock: ' + Number(stock) : '');
    }

    function refreshHint() {
        var ready = $('#from_company').val() && $('#to_company').val();
        $('#addLine').prop('disabled', !ready);
        $('#linesHint').toggle(!ready || $('#transferLines .transfer-line').length === 0)
            .text(ready ? 'Press "Add item" to add the first item.' : 'Choose the from and to company first, then add items.');
    }

    // changing a company refills that side of every line
    $('#from_company').on('change', function () {
        var html = itemOptions(this.value, true);
        $('#transferLines .js-from').each(function () { $(this).html(html).val('').trigger('change'); });
        if (this.value && this.value === $('#to_company').val()) { $('#to_company').val('').trigger('change'); }
        if (!$('#transferLines .transfer-line').length && this.value && $('#to_company').val()) addLine();
        refreshHint();
    });
    $('#to_company').on('change', function () {
        var html = itemOptions(this.value, false);
        $('#transferLines .js-to').each(function () { $(this).html(html).val('').trigger('change'); });
        if (this.value && this.value === $('#from_company').val()) { alert('Choose a different company to receive the stock.'); $(this).val('').trigger('change'); return; }
        if (!$('#transferLines .transfer-line').length && this.value && $('#from_company').val()) addLine();
        refreshHint();
    });
    $('#transferLines').on('change', '.js-from', function () { showStock(this); });
    $('#transferLines').on('click', '.js-remove-line', function () {
        $(this).closest('.transfer-line').remove();
        refreshHint();
    });
    $('#addLine').on('click', function () { addLine().find('.js-from').select2('open'); });

    window.checkTransfer = function () {
        if (!$('#transferLines .transfer-line').length) { alert('Add at least one item to transfer.'); return false; }
        // the same sending item on several lines: the total must fit the stock
        var need = {}, stock = {}, names = {};
        $('#transferLines .transfer-line').each(function () {
            var opt = $(this).find('.js-from option:selected');
            if (!opt.val()) return;
            need[opt.val()] = (need[opt.val()] || 0) + (parseFloat($(this).find('.js-qty').val()) || 0);
            stock[opt.val()] = parseFloat(opt.data('stock'));
            names[opt.val()] = opt.text();
        });
        for (var id in need) {
            if (need[id] - stock[id] > 0.00001) { alert('Not enough stock: ' + names[id] + ', you are sending ' + need[id] + '.'); return false; }
        }
        showLoading();
        return true;
    };

    // after a failed save, put the lines back; otherwise start with one empty line
    if (oldLines.length) {
        oldLines.forEach(function (l) { addLine(l); });
    } else if ($('#from_company').val() && $('#to_company').val()) {
        addLine();
    }
    refreshHint();
    @endif
});
</script>
@endsection
