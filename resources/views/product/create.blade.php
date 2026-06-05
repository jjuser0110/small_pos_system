@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        <a class="text-muted fw-light" href="{{route('product.index')}}">Product /</a>
         @if (isset($product)) Edit @else Create @endif
    </h4>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <h5 class="card-header">Product Details</h5>
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
                        <label class="form-label" for="product_name">Product Name</label>
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
                        <label for="select2Basic" class="form-label">Uom</label>
                        <select id="select2Basic" name="uom" class="select2 form-select" data-allow-clear="true">
                            @foreach($uom as $row)
                                <option value="{{ $row->id }}" @if(isset($product) && $product->uom == $row->id) selected @endif>
                                    {{ $row->uom_name ?? '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="initial">Initial Quantity</label>
                        <input
                        type="number"
                        class="form-control"
                        min="0"
                        name="initial"
                        value="{{$product->initial??0}}"/>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="selling_price">Selling Price (RM)</label>
                        <input
                        type="number"
                        class="form-control"
                        min="0"
                        step="0.01"
                        placeholder="9.90"
                        name="selling_price"
                        value="{{$product->selling_price??''}}"/>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Has Add-ons?</label>

                        <select name="has_addon" class="form-control" id="has_addon">
                            <option value="0">No</option>
                            <option value="1"
                                {{ isset($product) && $product->has_addon ? 'selected' : '' }}>
                                Yes
                            </option>
                        </select>
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
                        <div class="col-6">
                            <label class="col-form-label">Product Link</label>
                            <select id="connected_product_id" name="connected_product_id" class="select2 form-select" data-allow-clear="true">
                                    <option value="" >--SELECT--</option>
                                @foreach($product_link as $prod)
                                    <option value="{{$prod->id}}" @if(isset($product) && $product->connected_product_id == $prod->id) selected @endif>{{$prod->product_name??''}} ({{$prod->uom_dt->uom_unit??''}})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="col-form-label">Quantity</label>
                            <input class="form-control" type="number" step="0.01" min="0" name="connected_product_quantity" value="{{$product->connected_product_quantity??''}}">
                        </div>
                    <hr>
                    <div class="col-12" id="addonSection">
                        <hr>
                        <h5>Add-ons</h5>

                        <table class="table" id="addonTable">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Price (RM)</th>
                                    <th></th>
                                </tr>
                            </thead>

                            <tbody>

                            @if(isset($product) && $product->addons->count())

                                @foreach($product->addons as $addon)
                                <tr>
                                    <td>
                                        <input type="hidden"
                                            name="addon_id[]"
                                            value="{{ $addon->id }}">

                                        <input type="text"
                                            name="addon_name[]"
                                            class="form-control"
                                            value="{{ $addon->addon_name }}">
                                    </td>

                                    <td>
                                        <input type="number"
                                            step="0.01"
                                            name="addon_price[]"
                                            class="form-control"
                                            value="{{ $addon->addon_price }}">
                                    </td>

                                    <td>
                                        <button type="button"
                                            class="btn btn-danger remove-addon">
                                            X
                                        </button>
                                    </td>
                                </tr>
                                @endforeach

                            @else

                            <tr>
                                <td>
                                    <input type="hidden" name="addon_id[]" value="">

                                    <input type="text"
                                        name="addon_name[]"
                                        class="form-control">
                                </td>

                                <td>
                                    <input type="number"
                                        step="0.01"
                                        name="addon_price[]"
                                        class="form-control">
                                </td>

                                <td>
                                    <button type="button"
                                        class="btn btn-danger remove-addon">
                                        X
                                    </button>
                                </td>
                            </tr>

                            @endif

                            </tbody>
                        </table>

                        <button type="button"
                                id="addAddon"
                                class="btn btn-success">
                            Add Add-on
                        </button>
                    </div>
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
    let buffer = "";
    let last = Date.now();
    const targetInputId = "barcode";

    document.addEventListener("keydown", function (e) {
        const now = Date.now();
        const diff = now - last;

        // Detect scanner input (<30ms between keys)
        const isScanner = diff < 30;

        if (isScanner) {
            // STOP key from going into any input box
            if (e.target.tagName === "INPUT" || e.target.tagName === "TEXTAREA") {
                e.preventDefault();
            }

            // When scanner presses Enter → barcode finished
            if (e.key === "Enter") {
                e.preventDefault();
                document.getElementById(targetInputId).value = buffer;
                buffer = "";
                return;
            }

            // Add scanned char
            buffer += e.key;
            last = now;
            return;
        }

        // Human typing → reset scanner buffer
        buffer = "";
        last = now;
    });

    function toggleAddonSection() {
        if ($('#has_addon').val() == '1') {
            $('#addonSection').show();
        } else {
            $('#addonSection').hide();
        }
    }

    $('#has_addon').change(toggleAddonSection);

    toggleAddonSection();
    $('#addAddon').click(function () {

        $('#addonTable tbody').append(`
        <tr>
            <td>
                <input type="hidden"
                    name="addon_id[]"
                    value="">

                <input type="text"
                    name="addon_name[]"
                    class="form-control">
            </td>

            <td>
                <input type="number"
                    step="0.01"
                    name="addon_price[]"
                    class="form-control">
            </td>

            <td>
                <button type="button"
                    class="btn btn-danger remove-addon">
                    X
                </button>
            </td>
        </tr>
        `);

    });

    $(document).on('click', '.remove-addon', function () {
    $(this).closest('tr').remove();
    });
</script>
@endsection
