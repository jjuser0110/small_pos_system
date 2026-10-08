@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        <a class="text-muted fw-light" href="{{ route('home') }}">Home /</a> Staff Borrow
    </h4>

    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Borrow Records</h5>
            <form method="get" class="d-flex gap-2">
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
                        <th>Date</th>
                        <th>Staff</th>
                        <th>Amount</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Processed By</th>
                        <th>Processed At</th>
                        @if(in_array(Auth::user()->role_id, [1, 2, 3]))<th>Action</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($borrows as $b)
                    <tr>
                        <td>{{ $b->created_at }}</td>
                        <td>{{ $b->user->name ?? '' }} ({{ $b->user->username ?? '' }})</td>
                        <td>{{ number_format($b->amount, 2) }}</td>
                        <td>{{ $b->reason }}</td>
                        <td>
                            @if($b->status == 'pending') <span class="badge bg-warning">Pending</span>
                            @elseif($b->status == 'approved') <span class="badge bg-success">Approved</span>
                            @else <span class="badge bg-danger">Rejected</span>
                                @if($b->reject_reason)<br><small>{{ $b->reject_reason }}</small>@endif
                            @endif
                        </td>
                        <td>{{ $b->approver->name ?? '' }}</td>
                        <td>{{ $b->status_at }}</td>
                        @if(in_array(Auth::user()->role_id, [1, 2, 3]))
                        <td style="white-space:nowrap">
                            @if($b->status == 'pending')
                                <div class="d-flex gap-2">
                                    <form method="post" action="{{ route('staff_borrow.approve', $b) }}" class="m-0" onsubmit="return confirm('Approve this request?')">
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
                    @empty
                    <tr><td colspan="8" class="text-center">No records</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection