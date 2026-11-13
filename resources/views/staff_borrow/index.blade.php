@extends('layouts.app')

@section('content')
<style>
    #mytable th,
    #mytable td {
        padding: 10px;
    }
</style>
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        <a class="text-muted fw-light" href="{{ route('home') }}">Home /</a> Advance
    </h4>

    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    @if(Auth::user()->isStaff())
    <div class="card mb-4">
        <h5 class="card-header">Request an Advance</h5>
        <div class="card-body">
            <form method="post" action="{{ route('staff_borrow.store') }}" class="row g-3" onsubmit="showLoading()">
                @csrf
                <div class="col-md-3">
                    <label class="form-label">Amount (RM)</label>
                    <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required>
                </div>
                <div class="col-md-9">
                    <label class="form-label">Reason</label>
                    <input type="text" name="reason" maxlength="500" class="form-control">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Submit</button>
                    <small class="text-muted ms-2">Waits for superadmin approval. If approved it is taken from this shift's sales.</small>
                    <a href="{{ route('counter') }}" class="btn btn-outline-primary float-end">Back to counter</a>
                </div>
            </form>
        </div>
    </div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Advance Records</h5>
            <form method="get" class="d-flex gap-2">
                <select name="status" class="form-select no-select2" onchange="this.form.submit()">
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
                    <tr>
                        <th>Date</th>
                        <th>Staff</th>
                        <th>Amount</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Processed By</th>
                        <th>Processed At</th>
                        @if(Auth::user()->role_id == 1)<th>Action</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($borrows as $b)
                    <tr>
                        <td>{{ $b->created_at }}</td>
                        <td>{{ $b->user->name ?? '' }} ({{ $b->user->username ?? '' }})</td>
                        <td>{{ money($b->amount) }}</td>
                        <td>{{ $b->reason }}</td>
                        <td>
                            @if($b->status == 'pending') <span style="color:orange">Pending</span>
                            @elseif($b->status == 'approved') <span style="color:green">Approved</span>
                            @else <span style="color:red">Rejected</span>
                                @if($b->reject_reason)<br><small>{{ $b->reject_reason }}</small>@endif
                            @endif
                        </td>
                        <td>{{ $b->approver->name ?? '' }}</td>
                        <td>{{ $b->status_at }}</td>
                        @if(Auth::user()->role_id == 1)
                        <td style="white-space:nowrap">
                            @if($b->status == 'pending')
                                <div class="d-flex gap-2">
                                    <form method="post" action="{{ route('staff_borrow.approve', $b) }}" class="m-0" onsubmit="return confirm('Approve this advance? It will be taken from the shift sales.')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                    </form>
                                    <form method="post" action="{{ route('staff_borrow.reject', $b) }}" class="m-0" onsubmit="var r = prompt('Reject reason (optional):'); if (r === null) return false; this.reject_reason.value = r; return true;">
                                        @csrf
                                        <input type="hidden" name="reject_reason">
                                        <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                                    </form>
                                </div>
                            @endif
                        </td>
                        @endif
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
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50, 75, 100],
        order: [[0, 'desc']],
        @if(Auth::user()->role_id == 1)
        columnDefs: [{ orderable: false, targets: -1 }],
        @endif
    });
});
</script>
@endsection