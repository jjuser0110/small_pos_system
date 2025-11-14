@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        <a class="text-muted fw-light" href="{{route('uom.index')}}">UOM /</a> 
         @if (isset($uom)) Edit @else Create @endif
    </h4>
    <div class="row">
        <div class="col-12">
            <div class="card">
            <h5 class="card-header">UOM Details</h5>
            <div class="card-body">
                <form class="row g-3" enctype="multipart/form-data" @if (isset($uom)) method="post" action="{{ route('uom.update',$uom) }}" @else method="post" action="{{ route('uom.store') }}" @endif onsubmit="showLoading()">
                @csrf
                <div class="col-md-6">
                    <label class="form-label" for="uom_name">UOM Name</label>
                    <input
                    type="text"
                    class="form-control"
                    placeholder="Metre"
                    name="uom_name"
                    value="{{$uom->uom_name??''}}" 
                    required/>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="uom_unit">UOM Unit</label>
                    <input
                    type="text"
                    class="form-control"
                    placeholder="M"
                    name="uom_unit" 
                    value="{{$uom->uom_unit??''}}"
                    required/>
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
@endsection
