@extends('layouts.app')
@section('content')
    <!-- Content -->

    @php
        // Build a lightweight dataset of the CURRENTLY LOADED orders (i.e. whatever
        // is on this page after the GET filter form was applied) for report printing.
        // Voided orders are excluded from report totals.
        $reportOrders = $order->map(function ($row) {
            return [
                'status'         => $row->status,
                'payment_method' => $row->payment_method,
                'items'          => $row->items->map(function ($item) {
                    return [
                        'name'        => $item->product->product_name ?? ($item->product_name ?? '-'),
                        'qty'         => $item->quantity,
                        'total_price' => $item->total_price,
                    ];
                }),
            ];
        });
    @endphp

    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="py-3 breadcrumb-wrapper mb-4"><span class="text-muted fw-light">Order </span></h4>

        <!-- DataTable with Buttons -->
        <div class="card">
            <div class="card-header flex-column flex-md-row">
                <div class="head-label" style="margin-bottom:10px">
                    <div class="d-flex flex-wrap justify-content-between align-items-center" style="gap:10px">
                        <h5 class="card-title mb-0">Order Listing</h5>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button"
                                    class="btn btn-primary d-flex align-items-center gap-2 shadow-sm"
                                    onclick="openReportDateModal()"
                                    title="Pick a date to print an order report for that business day (2:00 AM to 2:00 AM the next day).">
                                <i class="fa-solid fa-print"></i>
                                <span>Print Report</span>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-md-12 col-12 mb-4">
                    <form method="GET">
                        <div class="row g-2 align-items-end">

                            <div class="col-md-6">
                                <label>Date</label>
                                <div class="input-group input-daterange" >
                                    <input type="datetime-local" class="form-control" name="date_from" value="{{$date_from??''}}"/>
                                <span class="input-group-text">to</span>
                                <input type="datetime-local" class="form-control" name="date_to" value="{{$date_to??''}}"/>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <label>Branches</label>
                                <select name="branch_id[]" class="form-select select2" multiple>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ collect(request('branch_id'))->contains($branch->id) ? 'selected' : '' }}>
                                            {{ $branch->branch_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label>Companies</label>
                                <select name="company_id[]" class="form-select select2" multiple>
                                    @foreach ($companies as $company)
                                        <option value="{{ $company->id }}" {{ collect(request('company_id'))->contains($company->id) ? 'selected' : '' }}>
                                            {{ $company->company_code }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-12 text-end mt-2">
                                <button class="btn btn-primary">Filter</button>
                            </div>

                        </div>
                    </form>
                </div>
                <div class="row">
                    <div class="col-sm-3 col-lg-3 mb-3">
                        <div class="card card-border-shadow-primary h-100 cursor-pointer" onclick="showOrdersTable()">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2 pb-1">
                            <h4 class="ms-1 mb-0">Total Order</h4>
                            </div>
                            <p class="mb-1" style="margin:10px;font-size:18px">{{ $activeOrderCount }}</p>
                        </div>
                        </div>
                    </div>
                    <div class="col-sm-3 col-lg-3 mb-3">
                        <div class="card card-border-shadow-primary h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-2 pb-1">
                                    <h4 class="ms-1 mb-0">Total Amount</h4>
                                </div>
                                <p class="mb-1" style="margin:10px;font-size:18px">{{ number_format($activeOrderTotal, 2) }}</p>
                            </div>
                        </div>
                    </div>
                    <!-- <div class="col-sm-3 col-lg-3 mb-3">
                        <div class="card card-border-shadow-primary h-100 cursor-pointer" onclick="showProfitTable()">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-2 pb-1">
                                <h4 class="ms-1 mb-0">Total Profit</h4>
                                </div>
                                <p class="mb-1" style="margin:10px;font-size:18px">{{ number_format($total_profit ?? 0, 2) }}</p>
                            </div>
                        </div>
                    </div> -->
                </div>
            </div>
            <div id="ordersTableWrapper" class="card-datatable text-nowrap">
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
                            <th>Status</th>
                            @if (in_array(Auth::user()->role_id, [1, 2, 3]))
                               <th>Discount</th>
                            @endif
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order as $index => $row)
                            @php
                                $printItems = $row->items->map(function ($item) {
                                    return [
                                        'name'        => $item->product->product_name ?? ($item->product_name ?? '-'),
                                        'qty'         => $item->quantity,
                                        'total_price' => $item->total_price,
                                        'addons'      => $item->addons
                                            ? (is_string($item->addons) ? json_decode($item->addons, true) : $item->addons)
                                            : [],
                                    ];
                                });

                                $printData = [
                                    'order_no'        => $row->order_no,
                                    'created_at'      => $row->created_at,
                                    'payment_method'  => $row->payment_method,
                                    'amount_received' => $row->amount_received,
                                    'change'          => $row->change,
                                    'final_total'     => $row->final_total,
                                    'items'           => $printItems,
                                ];
                            @endphp
                            <tr style="{{ $row->status <> 'Active' ? 'background: lightgrey;' : '' }}">
                                <td>{{$index+1??""}}</td>
                                <td>{{$row->order_no??""}}</td>
                                <td>{{$row->created_at??""}}</td>
                                <td>{{$row->user->username??""}}</td>
                                <td>{{$row->total_product??""}}</td>
                                <td>{{$row->total_item??""}}</td>
                                <td data-order="{{ $row->final_total }}">{{number_format($row->final_total, 2)??""}}</td>
                                <td>{{$row->payment_method??""}}</td>
                                <td>{{$row->amount_received??""}}</td>
                                <td>{{$row->change??""}}</td>
                                <td>{{$row->status??""}}</td>

                                @if (in_array(Auth::user()->role_id, [1, 2, 3]))
                                    <td>
                                        <form method="POST" action="{{ route('order.discount', $row->id) }}" class="d-flex align-items-center gap-2">
                                            @csrf
                                            <input type="number" class="form-control" name="discount" value="{{ $row->discount ?? '' }}" min="0" step="0.01" style="min-width: 65px;" required>
                                            <button type="submit" class="btn btn-xs btn-primary px-2 py-1">
                                                <i class="bx bx-save"></i>
                                            </button>
                                        </form>
                                    </td>
                                @endif

                                <td>
                                    <button type="button"
                                            class="btn btn-sm p-0 border-0 bg-transparent text-success print-receipt-btn"
                                            title="Print Receipt"
                                            data-order='@json($printData)'
                                            onclick="printOrderReceipt(this)">
                                        <i class="fa-solid fa-print"></i>
                                    </button>

                                    <a href="{{ route('order.view',$row) }}" onclick="showLoading()"><i class="fa-solid fa-eye"></i></a>

                                    @if (auth()->user()->role_id == 1 || auth()->user()->role_id == 2 || auth()->user()->role_id == 3)
                                        @if($row->status !== 'Voided')
                                            <a href="javascript:void(0)"
                                            class="text-danger"
                                            onclick="openVoidModal('{{ route('order.void', $row->id) }}')">
                                                <i class="fa-solid fa-ban"></i>
                                            </a>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div id="amountTableWrapper" class="card-datatable text-nowrap d-none">
                <table class="dt-column-search table table-bordered" id="mytable2">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Branch</th>
                            <th>Company</th>
                            <th>Category</th>
                            <th>Number of Transactions</th>
                            <th>Total Amount (RM)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($categoryTotals as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $item->branch->branch_name ?? '' }}</td>
                            <td>{{ $item->company->company_name ?? '' }}</td>
                            <td>{{ $item->category->category_name ?? '' }}</td>
                            <td>{{ $item->order_item_count }}</td>
                            <td>{{ number_format($item->total_amount ?? 0, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="5" class="text-end">Total:</th>
                            <th>{{ number_format($categoryTotals->sum('total_amount') ?? 0, 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div id="profitTableWrapper" class="card-datatable text-nowrap d-none">
                <table class="dt-column-search table table-bordered" id="mytable3">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Branch</th>
                            <th>Company</th>
                            <th>Category</th>
                            <th>Total Profit (RM)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($categoryProfits as $index => $profit_item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $profit_item->branch->branch_name ?? '' }}</td>
                            <td>{{ $profit_item->company->company_name ?? '' }}</td>
                            <td>{{ $profit_item->category->category_name ?? '' }}</td>
                            <td>{{ number_format($profit_item->total_amount ?? 0, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4" class="text-end">Total:</th>
                            <th>{{ number_format($categoryProfits->sum('total_amount') ?? 0, 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="voidModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" id="voidForm">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-danger">Void Order</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <p class="mb-2">Please provide a reason for voiding this order:</p>

                        <textarea name="voided_reason"
                                class="form-control"
                                rows="4"
                                required
                                placeholder="E.g. Wrong item, payment mistake, duplicate order"></textarea>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-danger">
                            Confirm Void
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="reportDateModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fa-solid fa-print text-primary"></i> Print Order Report
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label for="reportDateInput" class="form-label">Select date</label>
                    <input type="date" id="reportDateInput" class="form-control">
                    <p class="text-muted mt-2 mb-0" style="font-size:13px">
                        Covers that day's business hours: <strong>2:00 AM</strong> to <strong>2:00 AM the next day</strong>.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-primary" id="reportDatePrintBtn">
                        <i class="fa-solid fa-print"></i> Print
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- / Content -->
@endsection

@section('page-js')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@endsection

@section('scripts')
<script>
    $(document).ready(function () {
        $('.select2').select2({
            placeholder: "Select",
            allowClear: true,
            width: '100%',
        });
    });
</script>
<script>
    $(function(){
      var table = $('#mytable').DataTable({
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>><"table-responsive"t><"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
        pageLength: 10,
        displayLength: 5,
        lengthMenu: [5, 10, 25, 50, 75, 100],
      });
      var table = $('#mytable2').DataTable({
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>><"table-responsive"t><"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
        pageLength: 10,
        displayLength: 5,
        lengthMenu: [5, 10, 25, 50, 75, 100],
      });
      var table = $('#mytable3').DataTable({
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>><"table-responsive"t><"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
        pageLength: 10,
        displayLength: 5,
        lengthMenu: [5, 10, 25, 50, 75, 100],
      });
    });

    function openVoidModal(action) {
        const form = document.getElementById('voidForm');
        form.action = action;

        const modal = new bootstrap.Modal(document.getElementById('voidModal'));
        modal.show();
    }
</script>
<script>
function showOrdersTable() {
    document.getElementById('ordersTableWrapper').classList.remove('d-none');
    document.getElementById('amountTableWrapper').classList.add('d-none');
    document.getElementById('profitTableWrapper').classList.add('d-none');

    // redraw DataTable if needed
    if ($.fn.DataTable.isDataTable('#mytable')) {
        $('#mytable').DataTable().columns.adjust().draw(false);
    }
}

function showAmountTable() {
    document.getElementById('ordersTableWrapper').classList.add('d-none');
    document.getElementById('amountTableWrapper').classList.remove('d-none');
    document.getElementById('profitTableWrapper').classList.add('d-none');

    if ($.fn.DataTable.isDataTable('#mytable2')) {
        $('#mytable2').DataTable().columns.adjust().draw(false);
    }
}

function showProfitTable() {
    document.getElementById('ordersTableWrapper').classList.add('d-none');
    document.getElementById('amountTableWrapper').classList.add('d-none');
    document.getElementById('profitTableWrapper').classList.remove('d-none');

    if ($.fn.DataTable.isDataTable('#mytable3')) {
        $('#mytable3').DataTable().columns.adjust().draw(false);
    }
}
</script>

<script>
// ════════════════════════════════════════════════
// RECEIPT SETTINGS (same source as POS screen)
// ════════════════════════════════════════════════
let receiptHeader = 'WILDFIRE';
let receiptFooter = 'THANK YOU';

fetch('/pos/receipt-settings', {
    headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
    }
})
.then(res => res.ok ? res.json() : null)
.then(data => {
    if (!data) return;
    if (data.header) receiptHeader = data.header;
    if (data.footer) receiptFooter = data.footer;
})
.catch(err => console.error('Failed to load receipt settings, using fallback', err));

// ════════════════════════════════════════════════
// RECEIPT TEXT FORMATTING (same as POS screen)
// ════════════════════════════════════════════════
function formatReceiptLines(text, tagWrap = true) {
    if (!text) return '';
    return text
        .replace(/\r/g, '')
        .split('\n')
        .map(line => line.trim())
        .filter(line => line.length > 0)
        .map(line => `[C]${line}`)
        .join('\n\n');
}

// ════════════════════════════════════════════════
// DISPLAY NAME HELPER — identical logic to counter.blade.php's
// displayItemName()/splitZhEn(), so a reprinted receipt here matches
// the one that printed at checkout. Folds addon names in front of the
// product name, grouping the Chinese parts together and the English
// parts together.
// e.g. addon "招牌 Signature" + product "嘟嘟鸡煲 Sizzling Chicken Claypot"
//      → "招牌 嘟嘟鸡煲 Signature Sizzling Chicken Claypot"
// ════════════════════════════════════════════════
function splitZhEn(text) {
    const idx = text.search(/[A-Za-z]/);
    if (idx === -1) return { zh: text.trim(), en: '' };
    if (idx === 0)  return { zh: '', en: text.trim() };
    return { zh: text.slice(0, idx).trim(), en: text.slice(idx).trim() };
}

function displayItemName(item) {
    if (!item.addons || !item.addons.length) return item.name;

    const addonParts  = item.addons.map(a => splitZhEn(a.name));
    const productPart = splitZhEn(item.name);

    const addonZh = addonParts.map(a => a.zh).filter(Boolean).join(' + ');
    const addonEn = addonParts.map(a => a.en).filter(Boolean).join(' + ');

    const zhFull = [addonZh, productPart.zh].filter(Boolean).join(' ');
    const enFull = [addonEn, productPart.en].filter(Boolean).join(' ');

    return [zhFull, enFull].filter(Boolean).join(' ');
}

// Groups duplicate product+addon combos into one line, same as
// counter.blade.php's groupItems(). This dataset has no productId, so
// the product name + sorted addon names stands in as the group key.
function groupReceiptItems(items) {
    const groups = {};
    items.forEach(it => {
        const addonKey = (it.addons || []).map(a => a.name).sort().join(',');
        const key = it.name + '|' + addonKey;

        if (!groups[key]) {
            groups[key] = { ...it, qty: 0, total_price: 0 };
        }
        groups[key].qty         += parseFloat(it.qty) || 0;
        groups[key].total_price += parseFloat(it.total_price) || 0;
    });
    return Object.values(groups);
}

// ════════════════════════════════════════════════
// PRINT RECEIPT — from Order Listing row
// ════════════════════════════════════════════════
function printOrderReceipt(btn) {
    if (!window.AndroidPrinter) {
        alert('Printer only works inside Android APK');
        return;
    }

    let order;
    try {
        order = JSON.parse(btn.dataset.order);
    } catch (e) {
        console.error('Invalid order data', e);
        return;
    }

    const items = groupReceiptItems(order.items || []);
    if (!items.length) {
        alert('No items found for this order');
        return;
    }

    const now = order.created_at
        ? new Date(order.created_at).toLocaleString('en-MY')
        : new Date().toLocaleString('en-MY');

    let receipt = `
${formatReceiptLines(receiptHeader)}

[C]${order.order_no ?? ''}

[C]${now}

[C]================================
`;

    items.forEach(item => {
        receipt += `\n[L]${item.qty} x ${displayItemName(item)}\n`;
        receipt += `[R]RM ${parseFloat(item.total_price ?? 0).toFixed(2)}\n`;
    });

    // Cash / QR — matches counter.blade.php's method label exactly
    const methodLabel = (order.payment_method ?? '').toLowerCase() === 'cash' ? 'Cash'
        : (order.payment_method ?? '').toLowerCase() === 'qr' ? 'QR'
        : (order.payment_method ?? '-');

    receipt += `
[C]--------------------------------
[L]Total
[R]RM ${parseFloat(order.final_total ?? 0).toFixed(2)}
[L]Payment
[R]${methodLabel}
`;

    if ((order.payment_method ?? '').toLowerCase() === 'cash') {
        receipt += `[L]Received
[R]RM ${parseFloat(order.amount_received ?? 0).toFixed(2)}
[L]Change
[R]RM ${parseFloat(order.change ?? 0).toFixed(2)}
`;
    }

    receipt += `
[C]================================

${formatReceiptLines(receiptFooter || 'Thank You!', false)}

\n\n\n
`;

    // Note: no [[OPEN_DRAWER]] prefix here, unlike counter.blade.php's
    // printReceipt() — this is a historical reprint, not a live sale,
    // so it should not trigger the cash drawer.
    AndroidPrinter.printBluetooth(receipt);

    if (typeof showToast === 'function') {
        showToast('🖨 Printing receipt...', '');
    }
}
</script>

<script>
// ════════════════════════════════════════════════
// ORDER REPORT DATA
// This is the set of orders CURRENTLY loaded on the page
// (i.e. whatever the GET filter form above produced).
// Voided orders are excluded from totals.
// ════════════════════════════════════════════════
let allOrdersData = @json($reportOrders);

// ════════════════════════════════════════════════
// REPORT DATE MODAL — lets the user pick which business day to print.
// Uses the same Bootstrap modal pattern as the Void modal.
// ════════════════════════════════════════════════
let reportDateModalInstance = null;

function openReportDateModal() {
    const input = document.getElementById('reportDateInput');

    // Default to "today" in local time (YYYY-MM-DD)
    if (!input.value) {
        const now = new Date();
        const pad = (n) => String(n).padStart(2, '0');
        input.value = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
    }

    if (!reportDateModalInstance) {
        reportDateModalInstance = new bootstrap.Modal(document.getElementById('reportDateModal'));
    }
    reportDateModalInstance.show();
}

document.getElementById('reportDatePrintBtn').addEventListener('click', function () {
    const dateStr = document.getElementById('reportDateInput').value;
    if (!dateStr) {
        alert('Please select a date.');
        return;
    }
    reportDateModalInstance.hide();
    printReportForDate(dateStr);
});

// ════════════════════════════════════════════════
// REPORT AGGREGATION
// ════════════════════════════════════════════════
function computeReportTotals(orders) {
    let totalQty = 0, totalPrice = 0;
    let qrQty = 0, qrPrice = 0;
    let cashQty = 0, cashPrice = 0;
    let productMap = {}; // name -> { qty, total }

    (orders || []).forEach(o => {
        if ((o.status || '').toLowerCase() === 'voided') return;

        const pm = (o.payment_method || '').trim().toLowerCase();

        (o.items || []).forEach(item => {
            const qty = parseFloat(item.qty) || 0;
            const price = parseFloat(item.total_price) || 0;

            totalQty += qty;
            totalPrice += price;

            if (pm === 'qr') {
                qrQty += qty;
                qrPrice += price;
            } else if (pm === 'cash') {
                cashQty += qty;
                cashPrice += price;
            }

            const name = item.name || '-';
            if (!productMap[name]) {
                productMap[name] = { qty: 0, total: 0 };
            }
            productMap[name].qty += qty;
            productMap[name].total += price;
        });
    });

    return { totalQty, totalPrice, qrQty, qrPrice, cashQty, cashPrice, productMap };
}

// ════════════════════════════════════════════════
// BUILD REPORT RECEIPT TEXT (same tag format as printOrderReceipt)
// ════════════════════════════════════════════════
function buildReportReceipt(title, orders) {
    const { totalQty, totalPrice, qrQty, qrPrice, cashQty, cashPrice, productMap } = computeReportTotals(orders);

    const now = new Date().toLocaleString('en-MY');

    let receipt = `
${formatReceiptLines(receiptHeader)}

[C]${title}

[C]${now}

[C]================================
`;

    receipt += `\n[L]Total Qty\n[R]${totalQty}\n`;
    receipt += `[L]Total Price\n[R]RM ${totalPrice.toFixed(2)}\n`;

    receipt += `
[C]--------------------------------
`;
    receipt += `\n[L]QR Qty\n[R]${qrQty}\n`;
    receipt += `[L]QR Price\n[R]RM ${qrPrice.toFixed(2)}\n`;

    receipt += `
[C]--------------------------------
`;
    receipt += `\n[L]Cash Qty\n[R]${cashQty}\n`;
    receipt += `[L]Cash Price\n[R]RM ${cashPrice.toFixed(2)}\n`;

    receipt += `
[C]================================
[C]Product Breakdown
[C]================================
`;

    Object.keys(productMap).sort().forEach(name => {
        const p = productMap[name];
        receipt += `\n[L]${name}\n`;
        receipt += `[L]  Qty: ${p.qty}\n`;
        receipt += `[R]RM ${p.total.toFixed(2)}\n`;
    });

    receipt += `
[C]================================

${formatReceiptLines(receiptFooter || 'Thank You!', false)}

\n\n\n
`;

    return receipt;
}

// ════════════════════════════════════════════════
// PRINT REPORT — shared entry point
// ════════════════════════════════════════════════
function printReportFromData(title, orders) {
    if (!window.AndroidPrinter) {
        alert('Printer only works inside Android APK');
        return;
    }
    if (!orders || !orders.length) {
        alert('No orders found for this report');
        return;
    }

    const receipt = buildReportReceipt(title, orders);
    AndroidPrinter.printBluetooth(receipt);

    if (typeof showToast === 'function') {
        showToast('🖨 Printing report...', '');
    }
}

// ════════════════════════════════════════════════
// PRINT REPORT FOR A CHOSEN DATE
//
// Covers that date's business day: 2:00 AM -> 2:00 AM the next day.
// Does NOT reload/navigate the page — it fetches the same page in the
// background with date_from/date_to set to that window, pulls the
// "allOrdersData" that page would have embedded, and prints from that.
// Uses the same AndroidPrinter.printBluetooth() call already proven to
// work in printOrderReceipt() / printReportFromData() above.
// ════════════════════════════════════════════════
async function printReportForDate(dateStr) {
    // dateStr is "YYYY-MM-DD" from the <input type="date">
    const [y, m, d] = dateStr.split('-').map(Number);

    let start = new Date(y, m - 1, d, 2, 0, 0, 0); // that date, 2:00 AM
    let end   = new Date(start);
    end.setDate(end.getDate() + 1);                // next day, 2:00 AM

    const fmt = (dt) => {
        const pad = (n) => String(n).padStart(2, '0');
        return `${dt.getFullYear()}-${pad(dt.getMonth() + 1)}-${pad(dt.getDate())}T${pad(dt.getHours())}:${pad(dt.getMinutes())}`;
    };
    const displayFmt = (dt) => dt.toLocaleDateString('en-MY', { day: '2-digit', month: 'short', year: 'numeric' });

    const url = new URL(window.location.href);
    url.searchParams.set('date_from', fmt(start));
    url.searchParams.set('date_to', fmt(end));

    try {
        const res = await fetch(url.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        });
        if (!res.ok) throw new Error('Request failed: ' + res.status);

        const html = await res.text();
        const match = html.match(/let\s+allOrdersData\s*=\s*(\[[\s\S]*?\]);/);
        if (!match) throw new Error('Could not find order data in response');

        const dayOrdersData = JSON.parse(match[1]);
        printReportFromData(`ORDER REPORT — ${displayFmt(start)}`, dayOrdersData);
    } catch (err) {
        console.error(err);
        alert("Couldn't load orders for that date. Please try again.");
    }
}

</script>
@endsection