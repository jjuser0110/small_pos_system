@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        <a class="text-muted fw-light" href="{{route('company.index')}}">Company /</a> 
         @if (isset($company)) Edit @else Create @endif
    </h4>
    <div class="row">
        <div class="col-12">
            <div class="card">
            <h5 class="card-header">Company Details</h5>
            <div class="card-body">
                <form class="row g-3" enctype="multipart/form-data" @if (isset($company)) method="post" action="{{ route('company.update',$company) }}" @else method="post" action="{{ route('company.store') }}" @endif onsubmit="showLoading()">
                @csrf
                <div class="col-md-6">
                    <label class="form-label" for="company_name">Company Name</label>
                    <input
                    type="text"
                    class="form-control"
                    placeholder="Kuching"
                    name="company_name"
                    value="{{$company->company_name??''}}" 
                    required/>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="company_code">Company Code</label>
                    <input
                    type="text"
                    class="form-control"
                    placeholder="KCH"
                    name="company_code" 
                    value="{{$company->company_code??''}}"
                    required/>
                </div>
                <div class="col-md-6 mb-4">
                    <label for="select2Basic" class="form-label">Branch</label>
                    <select id="select2Basic" name="branch_id" class="select2 form-select" data-allow-clear="true">
                        @foreach($branch as $bran)
                            <option value="{{ $bran->id }}" @if(isset($company) && $company->branch_id == $bran->id) selected @endif>
                                {{ $bran->branch_name ?? '' }}
                            </option>
                        @endforeach
                    </select>
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
