@extends('layouts.app')

@section('content')
<style>
    #mytable {
        font-size: 12px;
        border-collapse: collapse;
    }

    #mytable th,
    #mytable td {
        padding: 5px;
        font-size: 12px;
    }

    #mytable th {
        font-weight: 600;
        text-align: center;
    }

</style>
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        <a class="text-muted fw-light" href="{{route('batch.index')}}">Stock /</a>
         @if (isset($batch)) Edit @else Create @endif
    </h4>
    <div class="row">
        <div class="col-12">
            <div class="card">
            <h5 class="card-header">Stock Entry Details</h5>
            <div class="card-body">
                <form class="row g-3" enctype="multipart/form-data" @if (isset($batch)) method="post" action="{{ route('batch.update',$batch) }}" @else method="post" action="{{ route('batch.store') }}" @endif onsubmit="showLoading()">
                @csrf
                <div class="col-md-7 mb-4">
                    <label for="select2Basic" class="form-label">Company</label>
                    <select id="select2Basic" name="company_id" class="select2 form-select" data-allow-clear="true" @if(isset($batch)) disabled @endif>
                        @foreach($company as $com)
                            <option value="{{ $com->id }}" @if(isset($batch) && $batch->company_id == $com->id) selected @endif>
                                {{ $com->company_name ?? '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="batch_no">Stock No</label>
                    <input
                    type="text"
                    class="form-control"
                    name="batch_no"
                    value="{{ $batch->batch_no ?? $batch_no ?? '' }}"
                    readonly/>
                </div>
                <input type="hidden" name="code" value="{{ $code ?? '' }}">
                <input type="hidden" name="year" value="{{ $year ?? '' }}">
                <input type="hidden" name="month" value="{{ $month ?? '' }}">
                <div class="col-md-6">
                    <label class="form-label" for="batch_date">Stock Date</label>
                    <input
                    type="date"
                    class="form-control"
                    name="batch_date"
                    value="{{ $batch->batch_date ?? date('Y-m-d') }}"
                    required
                    @if(isset($batch)) disabled @endif/>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="supplier_id">Supplier</label>
                    @if(isset($batch))
                        <input type="text" class="form-control" value="{{ $batch->supplier->name ?? ($batch->batch_no && str_starts_with($batch->batch_no, 'TRF') ? 'Stock transfer' : '-') }}" disabled>
                    @else
                        <select name="supplier_id" id="supplier_id" class="select2 form-select" required>
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
                <hr>
                @if(isset($batch))
                <div class="col-md-6 mb-4">
                    <label  class="form-label">Status</label>
                    <div class="input-group">
                        <input type="text" class="form-control" value="{{ $batch->status ?? 'Open' }}" readonly>
                        @if($batch->status == 'Open')
                        <a class="btn btn-outline-primary" onclick="if(confirm('Are you sure you want to complete this stock entry? The items will be added to stock.')){showLoading();window.location.href='{{ route('batch.complete',$batch) }}'}">Complete and Add to Stock</a>
                        @endif
                        @if($batch->status != 'Open')
                            <button type="button" class="btn btn-outline-secondary" onclick="printReceipt()">Print</button>
                        @endif
                    </div>
                </div>
                @endif
                <div class="col-12">
                    @if(isset($batch))
                        <a href="{{ route('batch.index') }}" class="btn btn-secondary">Back</a>
                    @else
                        <button type="submit" name="submitButton" class="btn btn-primary">Submit</button>
                    @endif
                </div>
                </form>
                @if(isset($batch))
                <div class="col-12" style="margin-top:30px">
                    <div class="flex-column flex-md-row" style="margin-bottom:10px">
                        <div class="head-label">
                            <h5 class="card-title mb-0">Items</h5>
                        </div>
                        <div class="dt-action-buttons text-end pt-3 pt-md-0">
                            <div class="dt-buttons">
                                @if($batch->status == 'Open')
                                <a class="dt-button create-new btn btn-secondary" type="button" href="{{ route('batch.downloadTemplate', $batch) }}">
                                    <span><i class="bx bx-download me-sm-1"></i>
                                        <span class="d-none d-sm-inline-block">Download Template</span>
                                    </span>
                                </a>

                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadBatchItemModal">
                                    <span><i class="bx bx-upload me-sm-1"></i>
                                        <span class="d-none d-sm-inline-block">Import</span>
                                    </span>
                                </button>

                                <a class="dt-button create-new btn btn-primary" type="button" style="color:white" data-bs-toggle="modal"
                                data-bs-target="#activityModal">
                                    Add New Record
                                </a>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="card-datatable text-nowrap">
                        <div class="card-body">
                            <table class="dt-column-search table table-bordered" id="mytable">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Barcode</th>
                                        <th>Product Name</th>
                                        <th>Quantity</th>
                                        <th>Total Cost Per Unit</th>
                                        <th>Total Cost</th>
                                        <th>Balance</th>
                                        <th>Created At</th>
                                        <th>Updated At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($batch->batch_items as $index => $row)
                                    <tr>
                                        <td>{{$index+1??""}}</td>
                                        <td>{{$row->product->barcode??""}}</td>
                                        <td>{{$row->product->product_name??""}}</td>
                                        <td style="text-align:center">{{$row->quantity??""}}</td>
                                        <td style="text-align:center">{{ money($row->cost_per_unit??0) }}</td>
                                        <td style="text-align:center">{{ money($row->total_cost??0) }}</td>
                                        <td style="text-align:center">{{$row->balance??""}}</td>
                                        <td style="text-align:center">{{$row->created_at??""}}</td>
                                        <td style="text-align:center">{{$row->updated_at??""}}</td>
                                        <td>
                                            @if($batch->status == 'Open')
                                            <a style="color:red;cursor:pointer" onclick="if(confirm('Are you sure you want to delete?')){showLoading();window.location.href='{{ route('batch.destroyItem',$row) }}'}"><i class="fa-solid fa-trash"></i></a>
                                            @else
                                                <a type="button" data-bs-toggle="modal" data-bs-target="#editModal{{ $row->id }}" data-id="{{ $index + 1 }}">
                                                    <i class="fa fa-pencil"></i>
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3" style="text-align:right">Total:</th>
                                        <th>{{ $batch->batch_items->sum('quantity') ?? 0 }}</th>
                                        <th></th>
                                        <th>{{ money($batch->batch_items->sum('total_cost') ?? 0) }}</th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
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
                <h5 class="modal-title">Upload Batch Item Excel</h5>
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
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Upload
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>
@endif

@if(isset($batch))
<div class="modal fade" id="activityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="activityForm" enctype="multipart/form-data" method="post" action="{{ route('batch.addBatchItem',$batch) }}">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"  id="modalTitle">Add Product</h5>
                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12">
                        <label class="col-form-label">Product</label>
                        <select id="product_id" name="product_id" class="select2 form-select" data-allow-clear="true" required>
                            @foreach($product as $prod)
                                <option 
                                    value="{{$prod->id}}" 
                                    data-barcode="{{$prod->barcode??''}}"
                                >{{$prod->product_name??''}} ({{$prod->uom_dt->uom_unit??''}})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="col-form-label">Barcode</label>
                        <input class="form-control" type="text" name="barcode" id="barcode">
                    </div>
                    <div class="col-12">
                        <label class="col-form-label">Quantity</label>
                        <input class="form-control" type="number" step="0.01" min="0" name="quantity" id="quantity" onkeyup="countCost(this)" required>
                    </div>
                    <div class="col-12">
                        <label class="col-form-label">Single Cost</label>
                        <input class="form-control" type="number" step="0.01" min="0" name="cost_per_unit" id="cost_per_unit" onkeyup="countCost(this)" required>
                    </div>
                    <div class="col-12">
                        <label class="col-form-label">Total Cost</label>
                        <input class="form-control" type="number" step="0.01" min="0" name="total_cost" id="total_cost" onkeyup="countCost(this)" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                    Close
                </button>
                <button type="submit" class="btn btn-primary">Save changes</button>
            </div>
            </form>
        </div>
    </div>
</div>
@endif

@if(isset($batch->batch_items))
    @foreach ($batch->batch_items as $batch_item)
        <div class="modal fade" id="editModal{{ $batch_item->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form enctype="multipart/form-data" method="post" action="{{ route('batch.updateBatchItem',$batch_item) }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title"  id="modalTitle">Edit Batch Item</h5>
                            <button
                                type="button"
                                class="btn-close"
                                data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-12">
                                    <label class="col-form-label">Product</label>
                                    <p><strong>{{ $batch_item->product->product_name ?? '' }}</strong></p>
                                </div>
                                <div class="col-12">
                                    <label class="col-form-label">Barcode</label>
                                    <p><strong>{{ $batch_item->product->barcode ?? '' }}</strong></p>
                                </div>
                                <div class="col-6">
                                    <label class="col-form-label">Original Quantity</label>
                                    <p><strong>{{ $batch_item->quantity ?? '' }}</strong></p>
                                </div>
                                <div class="col-6">
                                    <label class="col-form-label">Balance</label>
                                    <p><strong>{{ $batch_item->balance ?? '' }}</strong></p>
                                </div>
                                <div class="col-12">
                                    <label class="col-form-label">Quantity</label>
                                    <input class="form-control" type="number" step="0.01" min="0" name="quantity" value="{{ $batch_item->quantity }}" onkeyup="countCost(this)" required>
                                </div>
                                <div class="col-12">
                                    <label class="col-form-label">Single Cost</label>
                                    <input class="form-control" type="number" step="0.01" min="0" name="cost_per_unit" value="{{ $batch_item->cost_per_unit }}" onkeyup="countCost(this)" required>
                                </div>
                                <div class="col-12">
                                    <label class="col-form-label">Total Cost</label>
                                    <input class="form-control" type="number" step="0.01" min="0" name="total_cost" value="{{ $batch_item->total_cost }}" onkeyup="countCost(this)" required>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                Close
                            </button>
                            <button type="submit" class="btn btn-primary">Save changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endif

@if(isset($batch) && $batch->status != 'Open')
<template id="receiptContent">
    <style>
        @@page { size: 80mm auto; margin: 0; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            width: 80mm;
            padding: 3mm 6mm;          /* top/bottom 3mm, left/right 6mm */
            font-family: 'Courier New', monospace;
            font-size: 11px;
            line-height: 1.4;
            color: #000;
        }
        .c { text-align: center; }
        .r { text-align: right; }
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
    <div>Supplier : {{ $batch->supplier->name ?? '-' }}</div>
    <div>Printed : {{ now()->format('Y-m-d H:i') }}</div>
    <div class="line"></div>

    @foreach($batch->batch_items as $i => $row)
        <div class="item-name">{{ $i+1 }}. {{ $row->product->product_name ?? '' }}</div>
        <div class="row indent">
            <span>{{ $row->quantity + 0 }} x RM {{ number_format($row->cost_per_unit,2) }}</span>
            <span>{{ money($row->total_cost) }}</span>
        </div>
    @endforeach

    <div class="line"></div>
    <div class="row b"><span>Total Qty</span><span>{{ $batch->batch_items->sum('quantity') + 0 }}</span></div>
    <div class="row b"><span>Total Cost</span><span>{{ money($batch->batch_items->sum('total_cost')) }}</span></div>
    <div class="line"></div>

    <div class="c">{!! nl2br(e(($receipt->footer ?? '') ?: 'Thank you')) !!}</div>
</template>
@endif

<!-- / Content -->
@endsection

@section('scripts')
    <script>
    $(function(){
        var table = $('#mytable').DataTable({
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>><"table-responsive"t><"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
            paging: false,
            info: false,
            ordering: false,
            searching: true,
        });

    });

    function countCost(el){
        let modal = el.closest('.modal');

        let quantity = parseFloat(modal.querySelector('[name="quantity"]').value) || 0;
        let costPerUnit = parseFloat(modal.querySelector('[name="cost_per_unit"]').value) || 0;
        let totalCost = parseFloat(modal.querySelector('[name="total_cost"]').value) || 0;

        if (el.name === "quantity" || el.name === "cost_per_unit") {
            totalCost = quantity * costPerUnit;
            modal.querySelector('[name="total_cost"]').value = totalCost.toFixed(2);
        }

        if (el.name === "total_cost" && quantity > 0) {
            costPerUnit = totalCost / quantity;
            modal.querySelector('[name="cost_per_unit"]').value = costPerUnit.toFixed(2);
        }
    }

    @if(isset($batch) && $batch->status == 'Open')
    (function () {
        let buffer = '';
        let lastTime = 0;
        const MAX_GAP = 50;
        const MIN_LEN = 4;

        document.addEventListener('keydown', function (e) {
            const t = e.target;
            if (['INPUT','TEXTAREA','SELECT'].includes(t.tagName) && t.id !== 'barcode') return;

            const now = Date.now();
            if (now - lastTime > MAX_GAP) buffer = '';
            lastTime = now;

            if (e.key === 'Enter') {
                if (buffer.length >= MIN_LEN) {
                    e.preventDefault();
                    handleScan(buffer.trim());
                }
                buffer = '';
                return;
            }
            if (e.key.length === 1) buffer += e.key;
        });

        function handleScan(code) {
            const select = document.getElementById('product_id');
            const option = [...select.options].find(o => (o.dataset.barcode || '').trim() === code);

            if (!option) {
                alert('No such product: ' + code);
                return;
            }

            document.getElementById('barcode').value = code;
            $('#product_id').val(option.value).trigger('change');

            const modalEl = document.getElementById('activityModal');
            modalEl.addEventListener('shown.bs.modal', function onShown() {
                document.getElementById('quantity').focus();
                modalEl.removeEventListener('shown.bs.modal', onShown);
            });
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }

        window.simulateScan = function (code) {
            for (const ch of code) {
                document.dispatchEvent(new KeyboardEvent('keydown', { key: ch }));
            }
            document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter' }));
        };
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
        window.addEventListener('load', function () {
            setTimeout(printReceipt, 300);
        });
        @endif
    </script>

@endsection
