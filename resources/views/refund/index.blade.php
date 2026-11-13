@extends('layouts.app')

@section('content')
<style>#mytable th, #mytable td { padding: 10px; }</style>
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        <a class="text-muted fw-light" href="{{ route('home') }}">Home /</a> Refunds
    </h4>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Refund Requests</h5>
            <div class="d-flex gap-2">
                @if(Auth::user()->isStaff())
                    <a href="{{ route('refund.create') }}" class="btn btn-warning">New Refund</a>
                @endif
                <form method="get">
                    <select name="status" class="form-select no-select2" onchange="this.form.submit()">
                        <option value="">All</option>
                        @foreach(['pending','approved','rejected'] as $s)
                            <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>
        <div class="card-datatable text-nowrap">
            <table class="dt-column-search table table-bordered" id="mytable">
                <thead>
                    <tr>
                        <th>#</th><th>Date</th><th>Order</th><th>Company</th><th>Items</th><th>Amount</th>
                        <th>Reason</th><th>Requested By</th><th>Status</th><th>Processed By</th><th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($refunds as $r)
                    <tr>
                        <td>{{ $r->id }}</td>
                        <td>{{ $r->created_at }}</td>
                        <td>{{ $r->order->order_no ?? '' }}</td>
                        <td>{{ $r->company->company_code ?? '' }}</td>
                        <td>
                            @foreach($r->items as $line)
                                {{ $line->product->product_name ?? '' }} × {{ $line->quantity + 0 }}<br>
                            @endforeach
                        </td>
                        <td>{{ money($r->amount) }}</td>
                        <td>{{ $r->reason }}</td>
                        <td>{{ $r->requester->name ?? '' }}</td>
                        <td>
                            @if($r->status == 'pending') <span style="color:orange">Pending</span>
                            @elseif($r->status == 'approved') <span style="color:green">Approved</span>
                            @else <span style="color:red">Rejected</span>@if($r->reject_reason)<br><small>{{ $r->reject_reason }}</small>@endif
                            @endif
                        </td>
                        <td>{{ $r->approver->name ?? '' }}<br><small>{{ $r->status_at }}</small></td>
                        <td style="white-space:nowrap">
                            @if($r->status == 'pending' && $r->canBeDecidedBy(Auth::user()))
                                <div class="d-flex gap-2">
                                    <form method="post" action="{{ route('refund.approve', $r) }}" class="m-0" onsubmit="return confirm('Approve this refund of {{ money($r->amount) }}? Stock is returned and the amount is taken off the shift.')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                    </form>
                                    <form method="post" action="{{ route('refund.reject', $r) }}" class="m-0" onsubmit="var x = prompt('Reject reason (optional):'); if (x === null) return false; this.reject_reason.value = x; return true;">
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
