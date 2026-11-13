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
                        <input
                        type="text"
                        class="form-control"
                        placeholder="1231239328272"
                        name="barcode"
                        id="barcode"
                        value="{{$product->barcode??''}}"/>
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
                    <div class="col-md-6">
                        <label class="form-label" for="selling_price">Selling Price per base unit (RM)</label>
                        <input
                        type="number"
                        class="form-control"
                        min="0"
                        step="0.01"
                        placeholder="9.90"
                        name="selling_price"
                        value="{{$product->selling_price??''}}"/>
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
                                    <td><input type="text" class="form-control form-control-sm" name="units[{{ $i }}][barcode]" value="{{ $u['barcode'] ?? '' }}"></td>
                                    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">&times;</button></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addUnitRow()">+ Add sell option</button>
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
        '<td><input type="text" class="form-control form-control-sm" name="units[' + i + '][barcode]"></td>' +
        '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">&times;</button></td>';
    document.querySelector('#unitsTable tbody').appendChild(tr);
    tr.querySelector('input[type=text]').focus();
}

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
            if (buffer.length >= MIN_LEN) {
                e.preventDefault();
                input.value = buffer;
            }
            buffer = '';
            return;
        }

        if (e.key.length === 1) {
            buffer += e.key;
            // while in the barcode box, stop the key typing so the final value is set cleanly
            if (t.id === 'barcode') e.preventDefault();
        }
    });
})();
</script>
@endsection
