@extends('layouts.app')

@section('content')
<style>#mytable th, #mytable td { padding: 10px; }</style>
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        <a class="text-muted fw-light" href="{{ route('home') }}">Home /</a> Damage
    </h4>

    @if($canReport)
    <div class="card mb-4">
        <h5 class="card-header">Report Damage</h5>
        <div class="card-body">
            <form method="post" action="{{ route('damage.store') }}" class="row g-3" onsubmit="showLoading()">
                @csrf
                <div class="col-md-5">
                    <label class="form-label">Item</label>
                    <select name="product_id" class="form-select" required>
                        <option value="">-- Select item --</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}">{{ $p->product_name }}@if(Auth::user()->role_id == 3) ({{ $p->company->company_code ?? '' }})@endif · stock {{ $p->stock_quantity + 0 }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Quantity</label>
                    <input type="number" step="0.01" min="0.01" name="quantity" class="form-control" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Reason</label>
                    <input type="text" name="reason" maxlength="500" class="form-control" placeholder="e.g. bottle broken" required>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Submit</button>
                    <small class="text-muted ms-2">Stock is only removed after the superadmin approves.</small>
                    @if(Auth::user()->isStaff())
                        <a href="{{ route('counter') }}" class="btn btn-outline-primary float-end">Back to counter</a>
                    @endif
                </div>
            </form>
        </div>
    </div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Damage Reports</h5>
            <form method="get">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">All</option>
                    @foreach(['pending','approved','rejected'] as $s)
                        <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </form>
        </div>
        <div class="card-datatable text-nowrap">
            <table class="dt-column-search table table-bordered" id="mytable">
                <thead>
                    <tr><th>#</th><th>Date</th><th>Company</th><th>Item</th><th>Qty</th><th>Reason</th><th>Reported By</th><th>Status</th><th>Processed By</th><th>Action</th></tr>
                </thead>
                <tbody>
                    @foreach($damages as $d)
                    <tr>
                        <td>{{ $d->id }}</td>
                        <td>{{ $d->created_at }}</td>
                        <td>{{ $d->company->company_code ?? '' }}</td>
                        <td>{{ $d->product->product_name ?? '' }}</td>
                        <td>{{ $d->quantity + 0 }}</td>
                        <td>{{ $d->reason }}</td>
                        <td>{{ $d->requester->name ?? '' }}</td>
                        <td>
                            @if($d->status == 'pending') <span style="color:orange">Pending</span>
                            @elseif($d->status == 'approved') <span style="color:green">Approved</span>
                            @else <span style="color:red">Rejected</span>@if($d->reject_reason)<br><small>{{ $d->reject_reason }}</small>@endif
                            @endif
                        </td>
                        <td>{{ $d->approver->name ?? '' }}<br><small>{{ $d->status_at }}</small></td>
                        <td style="white-space:nowrap">
                            @if($d->status == 'pending' && $canApprove)
                                <div class="d-flex gap-2">
                                    <form method="post" action="{{ route('damage.approve', $d) }}" class="m-0" onsubmit="return confirm('Approve? {{ $d->quantity + 0 }} will be removed from stock.')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                    </form>
                                    <form method="post" action="{{ route('damage.reject', $d) }}" class="m-0" onsubmit="var x = prompt('Reject reason (optional):'); if (x === null) return false; this.reject_reason.value = x; return true;">
                                        @csrf
                                        <input type="hidden" name="reject_reason">
                                        <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                                    </form>
                                </div>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function(){
    $('#mytable').DataTable({
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>><"table-responsive"t><"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
        pageLength: 10, lengthMenu: [5, 10, 25, 50, 75, 100], order: [[0, 'desc']],
        columnDefs: [{ orderable: false, targets: -1 }],
    });
});
</script>
@endsection
