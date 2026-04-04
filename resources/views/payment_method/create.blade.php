@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        <a class="text-muted fw-light" href="{{route('payment_method.index')}}">Payment Method /</a> 
         @if (isset($payment_method)) Edit @else Create @endif
    </h4>
    <div class="row">
        <div class="col-12">
            <div class="card">
            <h5 class="card-header">Payment Method Details</h5>
            <div class="card-body">
                <form class="row g-3" enctype="multipart/form-data" @if (isset($payment_method)) method="post" action="{{ route('payment_method.update',$payment_method) }}" @else method="post" action="{{ route('payment_method.store') }}" @endif onsubmit="showLoading()">
                @csrf
                <div class="col-md-6">
                    <label class="form-label" for="payment_method_name">Payment Method Name</label>
                    <input
                    type="text"
                    class="form-control"
                    placeholder="Cash"
                    name="payment_method_name"
                    value="{{$payment_method->payment_method_name??''}}" 
                    required
                    @if(isset($payment_method)) disabled @endif
                    />
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="file_attachment">Image</label>
                    <input
                    type="file"
                    class="form-control"
                    name="file_attachment"
                    accept="image/*"/>
                </div>
                @if(!isset($payment_method))
                <div class="col-md-6">
                    <label class="form-label" for="amount">Amount</label>
                    <input
                    type="number"
                    step="0.01"
                    class="form-control"
                    placeholder="0.00"
                    name="amount"
                    value="{{$payment_method->amount??''}}"
                    required/>
                </div>
                @endif
                @if(isset($payment_method))
                <div class="col-md-7">
                    <label class="form-label" for="password">Is Active?</label>
                    <select name="is_active" class="form-control">
                        <option value="1" <?php echo isset($payment_method)&&$payment_method->is_active == 1?'selected':'' ?>>Active</option>
                        <option value="0" <?php echo isset($payment_method)&&$payment_method->is_active == 0?'selected':'' ?>>Inactive</option>
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
