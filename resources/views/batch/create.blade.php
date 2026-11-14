@extends('layouts.app')

@section('content')
<style>
    #mytable { font-size: 12px; border-collapse: collapse; }
    #mytable th, #mytable td { padding: 5px; font-size: 12px; vertical-align: middle; }
    #mytable th { font-weight: 600; text-align: center; }
    #mytable input.form-control, #mytable select.form-select { font-size: 12px; padding: 3px 6px; min-width: 80px; }
    #mytable tr.saving { background: #fffbe6; }
    #mytable tr.saved { background: #e8f7ee; transition: background .6s; }
</style>
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        <a class="text-muted fw-light" href="{{route('batch.index')}}">Stock /</a>
         @if (isset($batch)) {{ $batch->batch_no }} @else Add Stock @endif
    </h4>
    <div class="row">
        <div class="col-12">
            <div class="card">
            <h5 class="card-header">Stock Entry Details</h5>
            <div class="card-body">
                <form class="row g-3" @if (isset($batch)) method="post" action="{{ route('batch.update',$batch) }}" @else method="post" action="{{ route('batch.store') }}" @endif onsubmit="showLoading()">
                @csrf
                @if(isset($batch)) <input type="hidden" name="back" value="edit"> @endif
                <div class="col-md-6">
                    <label class="form-label">Company</label>
                    <select name="company_id" class="form-select" @if(isset($batch)) disabled @endif>
                        @foreach($company as $com)
                            <option value="{{ $com->id }}" @if((isset($batch) && $batch->company_id == $com->id) || old('company_id') == $com->id) selected @endif>{{ $com->company_name ?? '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="batch_no">Stock No</label>
                    <input type="text" class="form-control" name="batch_no" value="{{ $batch->batch_no ?? $batch_no ?? '' }}" readonly/>
                </div>
                <input type="hidden" name="code" value="{{ $code ?? '' }}">
                <input type="hidden" name="year" value="{{ $year ?? '' }}">
                <input type="hidden" name="month" value="{{ $month ?? '' }}">
                <div class="col-md-6">
                    <label class="form-label" for="batch_date">Stock Date</label>
                    <input type="date" class="form-control" name="batch_date" value="{{ $batch->batch_date ?? date('Y-m-d') }}" required @if(isset($batch)) disabled @endif/>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="supplier_id">Supplier</label>
                    @if(isset($batch))
                        <input type="text" class="form-control" value="{{ $batch->supplier->name ?? ($batch->batch_no && str_starts_with($batch->batch_no, 'TRF') ? 'Stock transfer' : '-') }}" disabled>
                    @else
                        <select name="supplier_id" id="supplier_id" class="form-select" required>
                            <option value="">-- Choose supplier --</option>
                            @foreach($suppliers as $sup)
                                <option value="{{ $sup->id }}" @if(old('supplier_id') == $sup->id) selected @endif>{{ $sup->name }}@if(Auth::user()->role_id != 3 && $sup->branch) ({{ $sup->branch->branch_name }})@endif</option>
                            @endforeach
                        </select>
                        @if($suppliers->isEmpty())
                            <small class="text-danger">No suppliers yet. Add one under Master Setting &rarr; Supplier first.</small>
                        @endif
                    @endif
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="supplier_invoice">Supplier Invoice No.</label>
                    <div class="input-group">
                        <input type="text" class="form-control" name="supplier_invoice" id="supplier_invoice" maxlength="100"
                            value="{{ old('supplier_invoice', $batch->supplier_invoice ?? '') }}" placeholder="e.g. INV-20261114-01"
                            @if(isset($batch) && $batch->status != 'Open') disabled @endif>
                        @if(isset($batch) && $batch->status == 'Open')
                            <button type="submit" class="btn btn-outline-primary">Save</button>
                        @endif
                    </div>
                </div>
                <hr>
                @if(isset($batch))
                <div class="col-md-8">
                    <label class="form-label">Status</label>
                    <div class="input-group">
                        <input type="text" class="form-control" value="{{ $batch->status ?? 'Open' }}" readonly>
                        @if($batch->status == 'Open')
                        <a class="btn btn-primary" onclick="if(confirm('Are you sure you want to complete this stock entry? The items will be added to stock.')){showLoading();window.location.href='{{ route('batch.complete',$batch) }}'}">Complete and Add to Stock</a>
                        @else
                            <button type="button" class="btn btn-outline-secondary" onclick="printReceipt()">Print</button>
                        @endif
                    </div>
                </div>
                @endif
                <div class="col-12">
                    @if(isset($batch))
                        <a href="{{ route('batch.index') }}" class="btn btn-secondary">Back</a>
                    @else
                        <button type="submit" name="submitButton" class="btn btn-primary">Next: add items</button>
                    @endif
                </div>
                </form>

                @if(isset($batch))
                <div class="col-12" style="margin-top:30px">
                    <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-2">
                        <h5 class="card-title mb-0">Items</h5>
                        @if($batch->status == 'Open')
                        <div class="dt-buttons">
                            <a class="btn btn-secondary btn-sm" href="{{ route('batch.downloadTemplate', $batch) }}"><i class="bx bx-download me-1"></i>Download Template</a>
                            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#uploadBatchItemModal"><i class="bx bx-upload me-1"></i>Import</button>
                        </div>
                        @endif
                    </div>

                    @if($batch->status == 'Open')
                    <div class="row g-2 align-items-end mb-3">
                        <div class="col-md-7">
                            <label class="form-label">Add item (type to search, or scan a barcode anywhere on this page)</label>
                            <select id="addItemPicker" class="form-select">
                                <option value="">-- Choose an item --</option>
                                @foreach($product as $prod)
                                    <option value="{{ $prod->id }}|">{{ $prod->product_name }} ({{ $prod->uom_dt->uom_name ?? 'unit' }})@if($prod->barcode) [{{ $prod->barcode }}]@endif</option>
                                    @foreach($prod->units as $unit)
                                        <option value="{{ $prod->id }}|{{ $unit->id }}">{{ $prod->product_name }} — {{ $unit->name }} ({{ $unit->base_qty + 0 }} {{ $prod->uom_dt->uom_unit ?? 'units' }})@if($unit->barcode) [{{ $unit->barcode }}]@endif</option>
                                    @endforeach
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Barcode</label>
                            <input type="text" id="barcode" class="form-control" placeholder="Scan or type + Enter" autocomplete="off">
                        </div>
                        <div class="col-md-2"><small class="text-muted" id="addStatus"></small></div>
                    </div>
                    @endif

                    <div class="card-datatable text-nowrap">
                        <table class="table table-bordered" id="mytable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Barcode</th>
                                    <th>Item</th>
                                    <th>Unit</th>
                                    <th>Qty</th>
                                    <th>Cost per unit</th>
                                    <th>Total Cost</th>
                                    <th>In base units</th>
                                    <th>Balance</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($batch->batch_items as $index => $row)
                                    @include('batch._row', ['row' => $row, 'index' => $index, 'batch' => $batch])
                                @endforeach
                                @if($batch->batch_items->isEmpty())
                                    <tr class="js-empty"><td colspan="10" class="text-center text-muted py-4">No items yet. Choose an item above or scan a barcode.</td></tr>
                                @endif
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="6" style="text-align:right">Total:</th>
                                    <th id="grandTotal">{{ money($batch->batch_items->sum('total_cost')) }}</th>
                                    <th colspan="3"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                @endif
            </div>
            </div>
        </div>
    </div>
</div>

@if(isset($batch))
<div class="modal fade" id="uploadBatchItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload Stock Items Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('batch.import', $batch) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select Excel File</label>
                        <input type="file" name="file" class="form-control" accept=".xlsx,.csv" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- completed entries: adjust a row (stock already added, so changes are logged as adjustments) --}}
@if(isset($batch) && $batch->status != 'Open')
    @foreach ($batch->batch_items as $batch_item)
        <div class="modal fade" id="editModal{{ $batch_item->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form method="post" action="{{ route('batch.updateBatchItem',$batch_item) }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Adjust {{ $batch_item->product->product_name ?? '' }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-6">
                                    <label class="col-form-label">Original Quantity</label>
                                    <p><strong>{{ $batch_item->quantity + 0 }} {{ $batch_item->product->uom_dt->uom_unit ?? '' }}</strong></p>
                                </div>
                                <div class="col-6">
                                    <label class="col-form-label">Balance</label>
                                    <p><strong>{{ $batch_item->balance + 0 }}</strong></p>
                                </div>
                                <div class="col-12">
                                    <label class="col-form-label">Quantity (base units)</label>
                                    <input class="form-control" type="number" step="0.01" min="0" name="quantity" value="{{ $batch_item->quantity + 0 }}" onkeyup="countCost(this)" required>
                                </div>
                                <div class="col-12">
                                    <label class="col-form-label">Cost per base unit (RM)</label>
                                    <input class="form-control" type="number" step="0.01" min="0" name="cost_per_unit" value="{{ round($batch_item->cost_per_unit, 2) }}" onkeyup="countCost(this)" required>
                                </div>
                                <div class="col-12">
                                    <label class="col-form-label">Total Cost (RM)</label>
                                    <input class="form-control" type="number" step="0.01" min="0" name="total_cost" value="{{ round($batch_item->total_cost, 2) }}" onkeyup="countCost(this)" required>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach

<template id="receiptContent">
    <style>
        @@page { size: 80mm auto; margin: 0; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body { width: 80mm; padding: 3mm 6mm; font-family: 'Courier New', monospace; font-size: 11px; line-height: 1.4; color: #000; }
        .c { text-align: center; }
        .b { font-weight: bold; }
        .line { border-top: 1px dashed #000; margin: 5px 0; }
        .row { display: flex; justify-content: space-between; gap: 8px; }
        .item-name { word-break: break-word; margin-top: 2px; }
        .indent { padding-left: 12px; }
    </style>

    <div class="c b">{!! nl2br(e(($receipt->header ?? '') ?: config('app.name', 'My Shop'))) !!}</div>
    <div class="c b" style="margin-top:4px">STOCK IN</div>
    <div class="line"></div>

    <div>No : {{ $batch->batch_no }}</div>
    <div>Date : {{ $batch->batch_date }}</div>
    <div>Company : {{ $batch->company->company_name ?? '-' }}</div>
    <div>Supplier : {{ $batch->supplier->name ?? '-' }}</div>
    <div>Supplier Inv : {{ $batch->supplier_invoice ?: '-' }}</div>
    <div>Printed : {{ now()->format('Y-m-d H:i') }}</div>
    <div class="line"></div>

    @foreach($batch->batch_items as $i => $row)
        <div class="item-name">{{ $i+1 }}. {{ $row->product->product_name ?? '' }}</div>
        <div class="row indent">
            <span>{{ $row->entryLabel() }} x {{ money($row->entry_cost ?? $row->cost_per_unit) }}</span>
            <span>{{ money($row->total_cost) }}</span>
        </div>
        @if($row->entry_unit)
            <div class="indent">= {{ $row->quantity + 0 }} {{ $row->product->uom_dt->uom_unit ?? 'units' }}</div>
        @endif
    @endforeach

    <div class="line"></div>
    <div class="row b"><span>Items</span><span>{{ $batch->batch_items->count() }}</span></div>
    <div class="row b"><span>Total Cost</span><span>{{ money($batch->batch_items->sum('total_cost')) }}</span></div>
    <div class="line"></div>

    <div class="c">{!! nl2br(e(($receipt->footer ?? '') ?: 'Thank you')) !!}</div>
</template>
@endif

<!-- / Content -->
@endsection

@section('scripts')
<script>
    // completed-entry adjust popup: keep quantity x cost = total
    function countCost(el) {
        const modal = el.closest('.modal');
        const qty = parseFloat(modal.querySelector('[name="quantity"]').value) || 0;
        const cost = parseFloat(modal.querySelector('[name="cost_per_unit"]').value) || 0;
        const total = parseFloat(modal.querySelector('[name="total_cost"]').value) || 0;
        if (el.name === 'quantity' || el.name === 'cost_per_unit') {
            modal.querySelector('[name="total_cost"]').value = (qty * cost).toFixed(2);
        }
        if (el.name === 'total_cost' && qty > 0) {
            modal.querySelector('[name="cost_per_unit"]').value = (total / qty).toFixed(2);
        }
    }

    @if(isset($batch) && $batch->status == 'Open')
    (function () {
        const csrf = '{{ csrf_token() }}';
        const barcodeBox = document.getElementById('barcode');
        const addUrl = '{{ route('batch.addBatchItem', $batch) }}';
        const status = document.getElementById('addStatus');
        const money = v => 'RM ' + Number(v).toFixed(2);

        function post(url, data) {
            return fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(data),
            }).then(r => r.json().then(j => { if (!r.ok || j.ok === false) throw new Error(j.message || 'Could not save'); return j; }));
        }

        const tbody = document.querySelector('#mytable tbody');

        // ---- add an item (picker or scan): the server sends back just that row
        let adding = false;
        function addItem(data) {
            if (adding) return;
            adding = true;
            status.textContent = 'Adding...';
            post(addUrl, data)
                .then(j => {
                    const holder = document.createElement('tbody');
                    holder.innerHTML = j.html.trim();
                    const fresh = holder.firstElementChild;
                    const existing = tbody.querySelector('tr[data-id="' + j.row.id + '"]');
                    if (existing) existing.replaceWith(fresh); else tbody.appendChild(fresh);
                    tbody.querySelector('.js-empty')?.remove();
                    renumber();
                    updateGrandTotal();
                    fresh.classList.add('saved');
                    fresh.scrollIntoView({ block: 'nearest' });
                    fresh.querySelector('.js-qty').select();
                    status.textContent = j.message;
                })
                .catch(err => { status.textContent = ''; alert(err.message); })
                .finally(() => {
                    adding = false;
                    barcodeBox.value = '';
                    $('#addItemPicker').val('').trigger('change.select2');
                });
        }

        function renumber() {
            tbody.querySelectorAll('tr[data-id]').forEach((tr, i) => tr.querySelector('.js-no').textContent = i + 1);
        }

        $('#addItemPicker').on('change', function () {
            if (!this.value) return;
            const [productId, unitId] = this.value.split('|');
            addItem({ product_id: productId, unit_id: unitId || null });
        });

        barcodeBox.addEventListener('keydown', e => {
            if (e.key !== 'Enter') return;
            e.preventDefault();
            if (barcodeBox.value.trim()) addItem({ barcode: barcodeBox.value.trim() });
        });

        // scanner anywhere on the page (fast keys ending with Enter)
        let buffer = '', last = 0;
        document.addEventListener('keydown', e => {
            if (['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName)) return;
            const now = Date.now();
            if (now - last > 50) buffer = '';
            last = now;
            if (e.key === 'Enter') {
                if (buffer.length >= 4) { e.preventDefault(); addItem({ barcode: buffer.trim() }); }
                buffer = '';
                return;
            }
            if (e.key.length === 1) buffer += e.key;
        });

        // ---- edit a row: Qty / Cost -> Total = Qty x Cost; Total -> Cost = Total / Qty; saved on change
        function recalc(tr, changed) {
            const qty = parseFloat(tr.querySelector('.js-qty').value) || 0;
            const costEl = tr.querySelector('.js-cost');
            const totalEl = tr.querySelector('.js-total');
            if (changed === 'total') {
                if (qty > 0) costEl.value = ((parseFloat(totalEl.value) || 0) / qty).toFixed(2);
            } else {
                totalEl.value = (qty * (parseFloat(costEl.value) || 0)).toFixed(2);
            }
        }

        function save(tr, changed) {
            const qty = parseFloat(tr.querySelector('.js-qty').value) || 0;
            if (qty <= 0) { alert('Quantity must be more than 0.'); return; }
            tr.classList.remove('saved');
            tr.classList.add('saving');
            post(tr.dataset.url, {
                unit_id: tr.querySelector('.js-unit').value || null,
                entry_qty: qty,
                entry_cost: parseFloat(tr.querySelector('.js-cost').value) || 0,
                total_cost: parseFloat(tr.querySelector('.js-total').value) || 0,
                changed: changed,
            }).then(j => {
                tr.classList.remove('saving');
                tr.classList.add('saved');
                const base = tr.querySelector('.js-base');
                base.textContent = Number(j.row.quantity).toLocaleString() + ' ' + base.dataset.unit + ' @ ' + money(j.row.cost_per_unit);
                tr.querySelector('.js-total').value = Number(j.row.total_cost).toFixed(2);
                updateGrandTotal();
            }).catch(err => { tr.classList.remove('saving'); alert(err.message); });
        }

        function updateGrandTotal() {
            let sum = 0;
            document.querySelectorAll('#mytable .js-total').forEach(i => sum += parseFloat(i.value) || 0);
            document.getElementById('grandTotal').textContent = money(sum);
        }

        // one handler for every row, including rows added later
        tbody.addEventListener('input', e => {
            const tr = e.target.closest('tr[data-id]');
            if (!tr) return;
            if (e.target.classList.contains('js-qty')) recalc(tr, 'qty');
            if (e.target.classList.contains('js-cost')) recalc(tr, 'cost');
            if (e.target.classList.contains('js-total')) recalc(tr, 'total');
        });
        tbody.addEventListener('change', e => {
            const tr = e.target.closest('tr[data-id]');
            if (!tr) return;
            const map = { 'js-qty': 'qty', 'js-cost': 'cost', 'js-total': 'total', 'js-unit': 'unit' };
            for (const cls in map) if (e.target.classList.contains(cls)) save(tr, map[cls]);
        });
        tbody.addEventListener('keydown', e => {
            if (e.key === 'Enter' && e.target.matches('tr[data-id] input')) { e.preventDefault(); e.target.blur(); }
        });
        tbody.addEventListener('click', e => {
            const link = e.target.closest('.js-remove');
            if (!link || !confirm('Remove this item?')) return;
            const tr = link.closest('tr');
            fetch(link.dataset.url, { headers: { 'Accept': 'application/json' } })
                .then(r => r.json().then(j => { if (!r.ok || j.ok === false) throw new Error(j.message || 'Could not remove'); }))
                .then(() => { tr.remove(); renumber(); updateGrandTotal(); })
                .catch(err => alert(err.message));
        });
    })();
    @endif

    function printReceipt() {
        const content = document.getElementById('receiptContent');
        if (!content) return;
        const iframe = document.createElement('iframe');
        iframe.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;';
        document.body.appendChild(iframe);
        const doc = iframe.contentWindow.document;
        doc.open();
        doc.write('<html><head><title>Stock Receipt</title></head><body>' + content.innerHTML + '</body></html>');
        doc.close();
        setTimeout(function () {
            iframe.contentWindow.focus();
            iframe.contentWindow.print();   // shows the print dialog
            setTimeout(function () { iframe.remove(); }, 2000);
        }, 300);
    }

    @if(session('print'))
    window.addEventListener('load', function () { setTimeout(printReceipt, 300); });
    @endif
</script>
@endsection
