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
                                {{ $cat->category_name ?? '' }}
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
                <div class="col-md-6">
                    <label class="form-label" for="product_code">Product Code</label>
                    <input
                    type="text"
                    class="form-control"
                    placeholder="AAA111"
                    name="product_code" 
                    value="{{$product->product_code??''}}"/>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="barcode">Barcode</label>
                    <input
                    type="text"
                    class="form-control"
                    placeholder="1231239328272"
                    name="barcode" 
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
@endsection
