@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        <a class="text-muted fw-light" href="{{route('branch.index')}}">Branch /</a> 
         @if (isset($branch)) Edit @else Create @endif
    </h4>
    <div class="row">
        <div class="col-12">
            <div class="card">
            <h5 class="card-header">Branch Details</h5>
            <div class="card-body">
                <form class="row g-3" enctype="multipart/form-data" @if (isset($branch)) method="post" action="{{ route('branch.update',$branch) }}" @else method="post" action="{{ route('branch.store') }}" @endif onsubmit="showLoading()">
                @csrf
                <div class="col-md-6">
                    <label class="form-label" for="branch_name">Branch Name</label>
                    <input
                    type="text"
                    class="form-control"
                    placeholder="Kuching"
                    name="branch_name"
                    value="{{$branch->branch_name??''}}" 
                    required/>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="branch_code">Branch Code</label>
                    <input
                    type="text"
                    class="form-control"
                    placeholder="KCH"
                    name="branch_code" 
                    value="{{$branch->branch_code??''}}"
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
