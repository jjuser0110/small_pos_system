@extends('layouts.app')

@section('content')
<style>
    #mytable {
        font-size: 12px;
        border-collapse: collapse;
    }

    #mytable th,
    #mytable td {
        padding: 5px;
        font-size: 12px;
    }

    #mytable th {
        font-weight: 600; 
        text-align: center;
    }
</style>
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        <a class="text-muted fw-light" href="{{route('batch.index')}}">Batch /</a> 
         @if (isset($batch)) Edit @else Create @endif
    </h4>
    <div class="row">
        <div class="col-12">
            <div class="card">
            <h5 class="card-header">Batch Details</h5>
            <div class="card-body">
                <form class="row g-3" enctype="multipart/form-data" @if (isset($batch)) method="post" action="{{ route('batch.update',$batch) }}" @else method="post" action="{{ route('batch.store') }}" @endif onsubmit="showLoading()">
                @csrf
                <div class="col-md-7 mb-4">
                    <label for="select2Basic" class="form-label">Company</label>
                    <select id="select2Basic" name="company_id" class="select2 form-select" data-allow-clear="true" @if(isset($batch)) disabled @endif>
                        @foreach($company as $com)
                            <option value="{{ $com->id }}" @if(isset($category) && $category->company_id == $com->id) selected @endif>
                                {{ $com->company_name ?? '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="batch_no">Batch No</label>
                    <input
                    type="text"
                    class="form-control"
                    name="batch_no"
                    value="{{ $batch->batch_no ?? $batch_no ?? '' }}"
                    readonly/>
                </div>
                <input type="hidden" name="code" value="{{ $code ?? '' }}">
                <input type="hidden" name="year" value="{{ $year ?? '' }}">
                <input type="hidden" name="month" value="{{ $month ?? '' }}">
                <div class="col-md-6">
                    <label class="form-label" for="batch_date">Batch Date</label>
                    <input
                    type="date"
                    class="form-control"
                    name="batch_date" 
                    value="{{ $batch->batch_date ?? date('Y-m-d') }}"
                    required
                    @if(isset($batch)) disabled @endif/>
                </div>
                <hr>
                @if(isset($batch))
                <div class="col-md-6 mb-4">
                    <label  class="form-label">Status</label>
                    <div class="input-group">
                        <input type="text" class="form-control" value="{{ $batch->status ?? 'Open' }}" readonly>
                        @if($batch->status == 'Open')
                        <a class="btn btn-outline-primary" onclick="if(confirm('Are you sure you want to complete this Batch?')){showLoading();window.location.href='{{ route('batch.complete',$batch) }}'}">Complete This Batch for Calculation</a>
                        @endif
                    </div>
                </div>
                @endif
                <div class="col-12">
                    @if(isset($batch))
                        <a href="{{ route('batch.index') }}" class="btn btn-secondary">Back</a>
                    @else
                        <button type="submit" name="submitButton" class="btn btn-primary">Submit</button>
                    @endif
                </div>
                </form>
                @if(isset($batch))
                <div class="col-12" style="margin-top:30px">
                    <div class="flex-column flex-md-row" style="margin-bottom:10px">
                        <div class="head-label">
                            <h5 class="card-title mb-0">Batch Items</h5>
                        </div>
                        <div class="dt-action-buttons text-end pt-3 pt-md-0">
                            <div class="dt-buttons"> 
                                @if($batch->status == 'Open')
                                <a class="dt-button create-new btn btn-primary" type="button" style="color:white" data-bs-toggle="modal"
                                data-bs-target="#activityModal">
                                    Add New Record
                                </a> 
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="card-datatable text-nowrap">
                        <div class="card-body">
                            <table class="dt-column-search table table-bordered" id="mytable">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Product Code</th>
                                        <th>Barcode</th>
                                        <th>Product Name</th>
                                        <th>Quantity</th>
                                        <th>Total Cost Per Unit</th>
                                        <th>Total Cost</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($batch->batch_items as $index => $row)
                                    <tr>
                                        <td>{{$index+1??""}}</td>
                                        <td>{{$row->product->product_code??""}}</td>
                                        <td>{{$row->product->barcode??""}}</td>
                                        <td>{{$row->product->product_name??""}}</td>
                                        <td style="text-align:center">{{$row->quantity??""}}</td>
                                        <td style="text-align:center">{{number_format($row->cost_per_unit??0,2)}}</td>
                                        <td style="text-align:center">{{number_format($row->total_cost??0,2)}}</td>
                                        <td>
                                            @if($batch->status == 'Open')
                                            <a style="color:red;cursor:pointer" onclick="if(confirm('Are you sure you want to delete?')){showLoading();window.location.href='{{ route('batch.destroyItem',$row) }}'}"><i class="fa-solid fa-trash"></i></a>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="4" style="text-align:right">Total:</th>
                                        <th>{{ $batch->batch_items->sum('quantity') ?? 0 }}</th>
                                        <th></th>
                                        <th>{{ $batch->batch_items->sum('total_cost') ?? 0 }}</th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                @endif
            </div>
            </div>
        </div>
    </div>
</div>

@if(isset($batch))
<div class="modal fade" id="activityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="activityForm" enctype="multipart/form-data" method="post" action="{{ route('batch.addBatchItem',$batch) }}">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"  id="modalTitle">Add Product</h5>
                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12">
                        <label class="col-form-label">Product</label>
                        <select id="product_id" name="product_id" class="select2 form-select" data-allow-clear="true" required>
                            @foreach($product as $prod)
                                <option value="{{$prod->id}}">{{$prod->product_name??''}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="col-form-label">Quantity</label>
                        <input class="form-control" type="number" step="0.01" min="0" name="quantity" id="quantity" onkeyup="countCost()" required>
                    </div>
                    <div class="col-12">
                        <label class="col-form-label">Single Cost</label>
                        <input class="form-control" type="number" step="0.01" min="0" name="cost_per_unit" id="cost_per_unit" onkeyup="countCost()" required>
                    </div>
                    <div class="col-12">
                        <label class="col-form-label">Total Cost</label>
                        <input class="form-control" type="number" step="0.01" min="0" name="total_cost" id="total_cost" onkeyup="countCost()" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                    Close
                </button>
                <button type="submit" class="btn btn-primary">Save changes</button>
            </div>
            </form>
        </div>
    </div>
</div>
@endif
<!-- / Content -->
@endsection

@section('scripts')
      <script>
    $(function(){
        var table = $('#mytable').DataTable({
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>><"table-responsive"t><"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
            paging: false,       
            info: false,          
            ordering: false,      
            searching: true,
        });
    });

    function countCost(){
        var quantity = parseFloat(document.getElementById("quantity").value) || 0;
        var cost_per_unit = parseFloat(document.getElementById("cost_per_unit").value) || 0;
        var total_cost = parseFloat(document.getElementById("total_cost").value) || 0;

        if(event.target.id == "quantity" || event.target.id == "cost_per_unit"){
            total_cost = quantity * cost_per_unit;
            document.getElementById("total_cost").value = total_cost.toFixed(2);
        }else if(event.target.id == "total_cost"){
            if(quantity != 0){
                cost_per_unit = total_cost / quantity;
                document.getElementById("cost_per_unit").value = cost_per_unit.toFixed(2);
            }
        }
    }
  </script>
@endsection
