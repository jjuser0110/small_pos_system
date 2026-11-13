@extends('layouts.app')
@section('content')
    <!-- Content -->

    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="py-3 breadcrumb-wrapper mb-4">
            <a class="text-muted fw-light" href="{{ route('product.index') }}">Item List /</a> {{ $product->product_name }}
        </h4>

        @php
            // value of what is left in completed stock entries, at what it cost
            $remaining = \App\Models\BatchItem::where('product_id', $product->id)->where('balance', '>', 0)
                ->whereHas('batch', fn ($q) => $q->where('status', 'Completed'))->get();
            $remainingValue = $remaining->sum(fn ($b) => $b->balance * $b->cost_per_unit);
            $costedQty = $remaining->sum('balance');
        @endphp
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <h5 class="mb-2">{{ $product->product_name }}</h5>
                        <div>Company: <b>{{ $product->company->company_name ?? '' }}</b> ({{ $product->company->company_code ?? '' }})</div>
                        <div>Branch: {{ $product->branch->branch_name ?? '' }}</div>
                        <div>Category: {{ $product->category->category_name ?? '' }}</div>
                        <div>Barcode: {{ $product->barcode ?: '-' }}</div>
                        <div>Status: {!! $product->is_active ? '<span class="text-success">Active</span>' : '<span class="text-danger">Inactive</span>' !!}</div>
                    </div>
                    <div class="col-md-4">
                        <div>Base unit: <b>{{ $product->uom_dt->uom_name ?? '-' }}</b></div>
                        <div>Price: <b>{{ money($product->selling_price) }}</b> per {{ $product->uom_dt->uom_unit ?? 'unit' }}</div>
                        <div class="mt-2">Sell options:</div>
                        @forelse($product->units as $unit)
                            <div class="ms-2">· {{ $unit->name }} = {{ $unit->base_qty + 0 }} {{ $product->uom_dt->uom_unit ?? 'units' }} for {{ money($unit->price) }}@if($unit->barcode) <small class="text-muted">[{{ $unit->barcode }}]</small>@endif</div>
                        @empty
                            <div class="ms-2 text-muted">none</div>
                        @endforelse
                    </div>
                    <div class="col-md-4">
                        <div class="fs-6">Stock now</div>
                        <div class="fs-3 fw-semibold @if($product->stock_quantity < 0) text-danger @endif">{{ $product->stock_quantity + 0 }} <small class="fs-6">{{ $product->uom_dt->uom_unit ?? '' }}</small></div>
                        <div class="mt-2">Stock value at cost: <b>{{ money($remainingValue) }}</b></div>
                        @if($costedQty < $product->stock_quantity)
                            <small class="text-warning">{{ $product->stock_quantity - $costedQty + 0 }} have no cost recorded (initial stock / manual adjustment).</small>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- DataTable with Buttons -->
        <div class="card">
            <div class="card-header flex-column flex-md-row">
                <div class="head-label">
                    <h5 class="card-title mb-0">Stock Movements</h5>
                </div>
            </div>
            <div class="card-datatable text-nowrap">
                <table class="dt-column-search table table-bordered" id="mytable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Company</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Before</th>
                            <th>Stock</th>
                            <th>After</th>
                            <th>By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($product->stock_logs()->with(['company', 'user'])->orderByDesc('created_at')->orderByDesc('id')->get() as $index => $row)
                        <tr>
                            <td data-order="{{ optional($row->created_at)->format('YmdHis') }}{{ str_pad($row->id, 10, '0', STR_PAD_LEFT) }}">{{ $row->created_at }}</td>
                            <td>{{ $row->company->company_code ?? '' }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $row->type ?? '')) }}</td>
                            <td>{{$row->description??""}}</td>
                            <td>{{$row->before_stock??""}}</td>
                            <td @if(in_array($row->type, ['stock_out', 'convert_out', 'adjust_out', 'transfer_out', 'damage'])) style="color:red" @else style="color:green" @endif>@if(in_array($row->type, ['stock_out', 'convert_out', 'adjust_out', 'transfer_out', 'damage'])) - @endif{{$row->quantity??""}}</td>
                            <td>{{$row->after_stock??""}}</td>
                            <td>{{ $row->user->name ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- / Content -->


    @endsection
    @section('page-js')
    @endsection
    @section('scripts')
      <script>
    $(function(){
      var table = $('#mytable').DataTable({
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>><"table-responsive"t><"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
        pageLength: 10,
        displayLength: 5,
        lengthMenu: [5, 10, 25, 50, 75, 100],
        order: [[0, 'desc']],   // newest first
      });
    });
  </script>
    @endsection
