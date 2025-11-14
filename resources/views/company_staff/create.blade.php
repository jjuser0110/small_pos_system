@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        <a class="text-muted fw-light" href="{{route('company_staff.index')}}">Company Staff /</a> 
         @if (isset($company_staff)) Edit @else Create @endif
    </h4>
    <div class="row">
        <div class="col-12">
            <div class="card">
            <h5 class="card-header">Company Staff Details</h5>
            <div class="card-body">
                <form class="row g-3" enctype="multipart/form-data" @if (isset($company_staff)) method="post" action="{{ route('company_staff.update',$company_staff) }}" @else method="post" action="{{ route('company_staff.store') }}" @endif onsubmit="showLoading()">
                @csrf
                <div class="col-md-6">
                    <label class="form-label" for="name">Company Staff Name</label>
                    <input
                    type="text"
                    class="form-control"
                    placeholder="Jack"
                    name="name"
                    value="{{$company_staff->name??''}}" 
                    required/>
                </div>
                <div class="col-md-6 mb-4">
                    <label for="select2Basic" class="form-label">Branch</label>
                    <select id="select2Basic" name="company_id" class="select2 form-select" data-allow-clear="true">
                        @foreach($company as $com)
                            <option value="{{ $com->id }}" @if(isset($company_staff) && $company_staff->company_id == $com->id) selected @endif>
                                {{ $com->company_name ?? '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="username">Username</label>
                    <input
                    type="text"
                    class="form-control"
                    placeholder="Jack1994"
                    name="username" 
                    value="{{$company_staff->username??''}}"
                    @if(isset($company_staff)) readonly @endif
                    required/>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="password">Password</label>
                    <input
                    type="text"
                    class="form-control"
                    name="password" 
                    @if(!isset($company_staff)) required @endif/>
                </div>
                @if(isset($company_staff))
                <div class="col-md-7">
                    <label class="form-label" for="password">Is Active?</label>
                    <select name="is_active" class="form-control">
                        <option value="1" <?php echo isset($company_staff)&&$company_staff->is_active == 1?'selected':'' ?>>Active</option>
                        <option value="0" <?php echo isset($company_staff)&&$company_staff->is_active == 0?'selected':'' ?>>Inactive</option>
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
