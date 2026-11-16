@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        <a class="text-muted fw-light" href="{{route('product.index')}}">Item List /</a>
         @if (isset($product)) Edit @else Create @endif
    </h4>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <h5 class="card-header">Item Details</h5>
                <div class="card-body">
                    <form class="row g-3" enctype="multipart/form-data" @if (isset($product)) method="post" action="{{ route('product.update',$product) }}" @else method="post" action="{{ route('product.store') }}" @endif onsubmit="showLoading()">
                    @csrf

                    <div class="col-md-7 mb-4">
                        <label for="select2Basic" class="form-label">Category</label>
                        <select id="select2Basic" name="category_id" class="select2 form-select" data-allow-clear="true">
                            @foreach($category as $cat)
                                <option value="{{ $cat->id }}" @if(isset($product) && $product->category_id == $cat->id) selected @endif>
                                    {{ $cat->category_name ?? '' }} ({{ $cat->company->company_name ??''}})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="product_name">Item Name</label>
                        <input
                        type="text"
                        class="form-control"
                        placeholder="Snack"
                        name="product_name"
                        value="{{$product->product_name??''}}"
                        required/>
                    </div>
                    <!-- <div class="col-md-6">
                        <label class="form-label" for="product_code">Product Code</label>
                        <input
                        type="text"
                        class="form-control"
                        placeholder="AAA111"
                        name="product_code"
                        value="{{$product->product_code??''}}"/>
                    </div> -->
                    <div class="col-md-6">
                        <label class="form-label" for="barcode">Barcode</label>
                        <div class="input-group">
                            <input
                            type="text"
                            class="form-control js-barcode"
                            placeholder="Scan, type, or Generate"
                            name="barcode"
                            id="barcode"
                            value="{{$product->barcode??''}}"/>
                            <button type="button" class="btn btn-outline-primary js-gen-barcode" title="Make a new in-store barcode"><i class="bx bx-barcode"></i> Generate</button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="arrangement">Arrangement</label>
                        <input
                        type="text"
                        class="form-control"
                        placeholder="1"
                        name="arrangement"
                        value="{{$product->arrangement??''}}"/>
                    </div>
                    <div class="col-md-6 mb-4">
                        <label for="select2Basic" class="form-label">Base Unit (UOM)</label>
                        <select id="select2Basic" name="uom" class="select2 form-select" data-allow-clear="true">
                            @foreach($uom as $row)
                                <option value="{{ $row->id }}" @if(isset($product) && $product->uom == $row->id) selected @endif>
                                    {{ $row->uom_name ?? '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="initial">Initial Quantity (base units)</label>
                        <input
                        type="number"
                        class="form-control"
                        min="0"
                        name="initial"
                        value="{{$product->initial??0}}"/>
                    </div>
                    <div class="col-12">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="average_cost">Average cost per base unit (RM)</label>
                                <input type="text" class="form-control bg-light" id="average_cost" readonly tabindex="-1"
                                       data-cost="{{ $averageCost ?? '' }}"
                                       value="{{ $averageCost !== null ? number_format($averageCost, 4) : 'No stock cost yet' }}">
                                <small class="text-muted">From the stock you have added (stock on hand). Updates by itself.</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="margin">Margin (%)</label>
                                <input type="number" class="form-control" id="margin" name="margin" step="0.01" min="-100" inputmode="decimal"
                                       placeholder="e.g. 25" value="{{ old('margin', isset($product) && $product->margin !== null ? $product->margin + 0 : '') }}">
                                <small class="text-muted">Selling price = average cost + margin %.</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="selling_price">Selling Price per base unit (RM)</label>
                                <input
                                type="number"
                                class="form-control"
                                min="0"
                                step="0.01"
                                placeholder="9.90"
                                name="selling_price"
                                id="selling_price"
                                value="{{$product->selling_price??''}}"/>
                                <small class="text-muted" id="marginHint"></small>
                            </div>
                        </div>
                    </div>
                    @if(isset($product))
                    <div class="col-md-7">
                        <label class="form-label" for="password">Is Active?</label>
                        <select name="is_active" class="form-control">
                            <option value="1" <?php echo isset($product)&&$product->is_active == 1?'selected':'' ?>>Active</option>
                            <option value="0" <?php echo isset($product)&&$product->is_active == 0?'selected':'' ?>>Inactive</option>
                        </select>
                    </div>
                    @endif
                    <hr>
                    <div class="col-12">
                        <h6 class="mb-1">Sell Options</h6>
                        <p class="text-muted small mb-2">
                            Other ways to sell this item. Stock is always counted in the base unit above.
                            Example for Cola (base unit Tin): <b>Carton</b> = 24 tins for RM 50.00, <b>3 for RM10</b> = 3 tins for RM 10.00.
                        </p>
                        <div class="table-responsive">
                        <table class="table table-sm table-bordered" id="unitsTable">
                            <thead>
                                <tr><th>Name</th><th style="width:150px">Base units in it</th><th style="width:150px">Price (RM)</th><th>Barcode</th><th style="width:50px"></th></tr>
                            </thead>
                            <tbody>
                                @foreach(old('units', isset($product) ? $product->allUnits->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'base_qty' => $u->base_qty + 0, 'price' => $u->price, 'barcode' => $u->barcode, 'is_active' => $u->is_active])->all() : []) as $i => $u)
                                <tr>
                                    <td>
                                        <input type="hidden" name="units[{{ $i }}][id]" value="{{ $u['id'] ?? '' }}">
                                        <input type="text" class="form-control form-control-sm" name="units[{{ $i }}][name]" value="{{ $u['name'] ?? '' }}" placeholder="Carton" required>
                                    </td>
                                    <td><input type="number" class="form-control form-control-sm" name="units[{{ $i }}][base_qty]" value="{{ $u['base_qty'] ?? '' }}" min="0.0001" step="any" required></td>
                                    <td><input type="number" class="form-control form-control-sm" name="units[{{ $i }}][price]" value="{{ $u['price'] ?? '' }}" min="0" step="0.01" required></td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <input type="text" class="form-control form-control-sm js-barcode" name="units[{{ $i }}][barcode]" value="{{ $u['barcode'] ?? '' }}">
                                            <button type="button" class="btn btn-outline-primary js-gen-barcode" title="Make a new in-store barcode"><i class="bx bx-barcode"></i></button>
                                        </div>
                                    </td>
                                    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger js-remove-unit" title="Remove">&times;</button></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addUnitRow()">+ Add sell option</button>
                    </div>
                    <hr>

                    <div class="col-12">
                        <h6 class="mb-1">Barcode labels</h6>
                        <p class="text-muted small mb-2">
                            No barcode for a bundle, box or special? Press <b>Generate</b>, save, then download the label image here,
                            put it in your Word file and print it for the counter. Scanning it adds that item or sell option straight to the cart.
                        </p>
                        <div id="barcodeLabels" class="d-flex flex-wrap gap-3"></div>
                        <p id="barcodeLabelsEmpty" class="text-muted small mb-0">No barcodes yet.</p>
                    </div>
                    <hr>

                    <div class="col-12">
                        <button type="submit" name="submitButton" class="btn btn-primary">Submit</button>
                    </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- / Content -->
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.11.6/JsBarcode.all.min.js"></script>
<script>
let unitRowIndex = {{ count(old('units', isset($product) ? $product->allUnits : [])) }};
function addUnitRow() {
    const i = unitRowIndex++;
    const tr = document.createElement('tr');
    tr.innerHTML =
        '<td><input type="hidden" name="units[' + i + '][id]" value="">' +
        '<input type="text" class="form-control form-control-sm" name="units[' + i + '][name]" placeholder="Carton" required></td>' +
        '<td><input type="number" class="form-control form-control-sm" name="units[' + i + '][base_qty]" min="0.0001" step="any" placeholder="24" required></td>' +
        '<td><input type="number" class="form-control form-control-sm" name="units[' + i + '][price]" min="0" step="0.01" placeholder="50.00" required></td>' +
        '<td><div class="input-group input-group-sm"><input type="text" class="form-control form-control-sm js-barcode" name="units[' + i + '][barcode]">' +
        '<button type="button" class="btn btn-outline-primary js-gen-barcode" title="Make a new in-store barcode"><i class="bx bx-barcode"></i></button></div></td>' +
        '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger js-remove-unit" title="Remove">&times;</button></td>';
    document.querySelector('#unitsTable tbody').appendChild(tr);
    tr.querySelector('input[type=text]').focus();
}

// remove a sell option row (one handler for existing and new rows)
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.js-remove-unit');
    if (btn) btn.closest('tr').remove();
});

(function () {
    let buffer = '';
    let lastTime = 0;
    const MAX_GAP = 50;   // raise to ~100 if your scanner is slow
    const MIN_LEN = 4;
    const input = document.getElementById('barcode');

    document.addEventListener('keydown', function (e) {
        const t = e.target;
        // ignore typing in other fields (the barcode field is allowed)
        if (['INPUT','TEXTAREA','SELECT'].includes(t.tagName) && t.id !== 'barcode') return;

        const now = Date.now();
        if (now - lastTime > MAX_GAP) buffer = '';
        lastTime = now;

        if (e.key === 'Enter') {
            // a scanner ends with Enter: never let that submit the form from the barcode box
            if (t.id === 'barcode') e.preventDefault();
            if (buffer.length >= MIN_LEN) {
                e.preventDefault();
                // scanned outside the box: put the code in it (inside the box the keys already typed it)
                if (t.id !== 'barcode') input.value = buffer;
                if (typeof drawLabels === "function") drawLabels();
            }
            buffer = '';
            return;
        }

        if (e.key.length === 1) buffer += e.key;
    });
})();

// ---- margin: selling price = average cost x (1 + margin %); typing a price shows the margin it gives
(function () {
    const cost = parseFloat(document.getElementById('average_cost').dataset.cost);
    const margin = document.getElementById('margin');
    const price = document.getElementById('selling_price');
    const hint = document.getElementById('marginHint');
    function showHint() {
        const p = parseFloat(price.value);
        hint.textContent = cost > 0 && p > 0 ? 'Margin on cost: ' + ((p / cost - 1) * 100).toFixed(1) + '%' : '';
    }
    margin.addEventListener('input', function () {
        const m = parseFloat(margin.value);
        if (cost > 0 && !isNaN(m)) price.value = (cost * (1 + m / 100)).toFixed(2);
        showHint();
        drawLabels();
    });
    price.addEventListener('input', function () {
        // a price typed by hand: show the margin it gives
        if (cost > 0 && parseFloat(price.value) > 0) margin.value = ((parseFloat(price.value) / cost - 1) * 100).toFixed(2);
        showHint();
    });
    showHint();
})();

// ---- barcodes: generate an in-store code, and draw a downloadable label for each one
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.js-gen-barcode');
    if (!btn) return;
    const input = btn.parentElement.querySelector('.js-barcode');
    if (input.value.trim() && !confirm('Replace barcode ' + input.value.trim() + ' with a new one?')) return;
    btn.disabled = true;
    fetch('{{ route('product.newBarcode') }}', { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(j => { input.value = j.barcode; drawLabels(); })
        .catch(() => alert('Could not make a barcode, please try again.'))
        .finally(() => { btn.disabled = false; });
});
document.addEventListener('input', function (e) {
    if (e.target.matches('.js-barcode, input[name=product_name], #selling_price, #unitsTable input')) drawLabels();
});

function labelList() {
    const name = (document.querySelector('input[name=product_name]').value || 'Item').trim();
    const list = [];
    const itemCode = document.getElementById('barcode').value.trim();
    if (itemCode) list.push({ title: name, price: document.getElementById('selling_price').value, code: itemCode });
    document.querySelectorAll('#unitsTable tbody tr').forEach(function (tr) {
        const code = tr.querySelector('.js-barcode') ? tr.querySelector('.js-barcode').value.trim() : '';
        if (!code) return;
        const optName = tr.querySelector('input[name$="[name]"]').value.trim();
        list.push({ title: name + (optName ? ' - ' + optName : ''), price: tr.querySelector('input[name$="[price]"]').value, code: code });
    });
    return list;
}

function isEan13(code) {
    if (!/^\d{13}$/.test(code)) return false;
    let sum = 0;
    for (let i = 0; i < 12; i++) sum += +code[i] * (i % 2 ? 3 : 1);
    return (10 - sum % 10) % 10 === +code[12];
}

/** name on top, barcode, price under it: one PNG per label, sized to print clearly */
function labelCanvas(label) {
    const bars = document.createElement('canvas');
    JsBarcode(bars, label.code, { format: isEan13(label.code) ? 'EAN13' : 'CODE128', width: 3, height: 110, fontSize: 26, margin: 10, background: '#ffffff' });
    const pad = 20, titleH = 46, priceH = label.price ? 46 : 0;
    const c = document.createElement('canvas');
    c.width = Math.max(bars.width + pad * 2, 520);
    c.height = titleH + bars.height + priceH + pad;
    const ctx = c.getContext('2d');
    ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, c.width, c.height);
    ctx.fillStyle = '#000'; ctx.textAlign = 'center';
    ctx.font = 'bold 28px Arial';
    let title = label.title;
    while (ctx.measureText(title).width > c.width - pad * 2 && title.length > 4) title = title.slice(0, -2);
    if (title !== label.title) title = title.trim() + '…';
    ctx.fillText(title, c.width / 2, 36);
    ctx.drawImage(bars, (c.width - bars.width) / 2, titleH);
    if (label.price) { ctx.font = 'bold 32px Arial'; ctx.fillText('RM ' + Number(label.price).toFixed(2), c.width / 2, titleH + bars.height + 36); }
    return c;
}

function drawLabels() {
    const box = document.getElementById('barcodeLabels');
    if (!box || typeof JsBarcode === 'undefined') return;
    box.innerHTML = '';
    const list = labelList();
    let shown = 0;
    list.forEach(function (label) {
        let canvas;
        try { canvas = labelCanvas(label); } catch (err) { return; }   // not a printable code (yet)
        const url = canvas.toDataURL('image/png');
        const card = document.createElement('div');
        card.className = 'border rounded p-2 text-center bg-white';
        card.style.width = '240px';
        const img = document.createElement('img');
        img.src = url; img.alt = label.title; img.style.width = '100%';
        const a = document.createElement('a');
        a.href = url; a.className = 'btn btn-sm btn-primary mt-2 w-100';
        a.download = (label.title.replace(/[^a-z0-9]+/gi, '-').replace(/^-|-$/g, '') || 'barcode') + '-' + label.code + '.png';
        a.innerHTML = '<i class="bx bx-download"></i> Download image';
        card.append(img, a);
        box.appendChild(card);
        shown++;
    });
    document.getElementById('barcodeLabelsEmpty').style.display = shown ? 'none' : '';
}
window.addEventListener('load', drawLabels);
document.addEventListener('click', function (e) { if (e.target.closest('.js-remove-unit')) setTimeout(drawLabels, 0); });
</script>
@endsection
