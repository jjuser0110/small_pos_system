@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        <a class="text-muted fw-light" href="{{ route('product.index') }}">Product /</a> Stock Adjustment
    </h4>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Stock Adjustment Requests</h5>
            <form method="get">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">All</option>
                    @foreach(['pending','approved','rejected'] as $s)
                        <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </form>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Date</th><th>Product</th><th>Type</th><th>Qty</th><th>From</th><th>To</th>
                        <th>Reason</th><th>Requested By</th><th>Status</th><th>Processed By</th><th>Processed At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($adjustments as $a)
                    <tr>
                        <td>{{ $a->created_at }}</td>
                        <td>{{ $a->product->product_name ?? '' }}</td>
                        <td>{{ $a->typeLabel() }}</td>
                        <td>{{ $a->type == 'adjust_in' ? '+' : '-' }}{{ number_format($a->quantity, 2) }}</td>
                        <td>{{ $a->company->company_code ?? '' }}</td>
                        <td>{{ $a->type == 'transfer' ? ($a->targetCompany->company_code ?? '') : '' }}</td>
                        <td>{{ $a->reason }}</td>
                        <td>{{ $a->requester->name ?? '' }}</td>
                        <td>
                            @if($a->status == 'pending') <span class="badge bg-warning">Pending</span>
                            @elseif($a->status == 'approved') <span class="badge bg-success">Approved</span>
                            @else <span class="badge bg-danger">Rejected</span>
                                @if($a->reject_reason)<br><small>{{ $a->reject_reason }}</small>@endif
                            @endif
                        </td>
                        <td>{{ $a->approver->name ?? '' }}</td>
                        <td>{{ $a->status_at }}</td>
                        <td style="white-space:nowrap">
                            @if($a->status == 'pending'
                                && (in_array(Auth::user()->role_id, [1, 2]) || (Auth::user()->role_id == 3 && $a->branch_id == Auth::user()->branch_id)))
                                <div class="d-flex gap-2">
                                    <form method="post" action="{{ route('stock_adjustment.approve', $a) }}" class="m-0" onsubmit="return confirm('Approve this request? Stock will be updated now.')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                    </form>
                                    <form method="post" action="{{ route('stock_adjustment.reject', $a) }}" class="m-0" onsubmit="var r = prompt('Reject reason (optional):'); if (r === null) return false; this.reject_reason.value = r; return true;">
                                        @csrf
                                        <input type="hidden" name="reject_reason">
                                        <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                                    </form>
                                </div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="12" class="text-center">No records</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection