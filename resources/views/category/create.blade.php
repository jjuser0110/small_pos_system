@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        <a class="text-muted fw-light" href="{{route('category.index')}}">Category /</a>
         @if (isset($category)) Edit @else Create @endif
    </h4>
    <div class="row">
        <div class="col-12">
            <div class="card">
            <h5 class="card-header">Category Details</h5>
            <div class="card-body">
                <form class="row g-3" enctype="multipart/form-data" @if (isset($category)) method="post" action="{{ route('category.update',$category) }}" @else method="post" action="{{ route('category.store') }}" @endif onsubmit="showLoading()">
                @csrf
                <div class="col-md-7 mb-4">
                    <label for="select2Basic" class="form-label">Company</label>
                    <select id="select2Basic" name="company_id" class="select2 form-select" data-allow-clear="true">
                        @foreach($company as $com)
                            <option value="{{ $com->id }}" @if(isset($category) && $category->company_id == $com->id) selected @endif>
                                {{ $com->company_name ?? '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="category_name">Category Name</label>
                    <input
                    type="text"
                    class="form-control"
                    placeholder="Snack"
                    name="category_name"
                    value="{{$category->category_name??''}}"
                    required/>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="arrangement">Arrangement</label>
                    <input
                    type="text"
                    class="form-control"
                    placeholder="1"
                    name="arrangement"
                    value="{{$category->arrangement??''}}"/>
                </div>
                <div class="col-md-6">
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="has_stock" name="special" value="1" {{ old('special', $category->special ?? 0) == 1 ? 'checked' : '' }}>
                        <label class="form-check-label" for="special">
                            Special
                        </label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="has_stock" name="has_stock" value="1" {{ old('has_stock', $category->has_stock ?? 0) == 1 ? 'checked' : '' }}>
                        <label class="form-check-label" for="has_stock">
                            Has Stock
                        </label>
                    </div>
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
