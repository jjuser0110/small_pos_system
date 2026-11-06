@extends('layouts.app')

@section('content')
@php $fmt = fn ($m) => \App\Http\Controllers\StaffAttendanceController::formatMinutes($m); @endphp
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        <a class="text-muted fw-light" href="{{ route('company_staff.index') }}">Staff /</a> {{ $staff->name }}
    </h4>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card h-100"><div class="card-body">
                <h5 class="mb-3">{{ $staff->name }}</h5>
                <p class="mb-1">Username: <b>{{ $staff->username }}</b></p>
                <p class="mb-1">Company: <b>{{ $staff->company->company_name ?? '' }}</b> ({{ $staff->company->domain ?? 'no domain' }})</p>
                <p class="mb-1">Branch: {{ $staff->branch->branch_name ?? '' }}</p>
                @php $def = config('fsmart.shifts.' . $staff->shift); @endphp
                <p class="mb-1">Shift: <b>{{ $staff->shift ?? '-' }}</b> @if($def)({{ $def['start'] }} – {{ $def['end'] }})@endif</p>
                <p class="mb-0">Status: {!! $staff->is_active ? '<span class="text-success">Active</span>' : '<span class="text-danger">Inactive</span>' !!}</p>
                <a href="{{ route('company_staff.edit', $staff) }}" class="btn btn-sm btn-outline-primary mt-3">Edit</a>
            </div></div>
        </div>
        <div class="col-md-8 mb-4">
            <form method="get" class="d-flex gap-2 mb-3">
                <input type="month" name="month" class="form-control" style="max-width:200px" value="{{ $month }}">
                <button class="btn btn-primary">Show</button>
            </form>
            <div class="row">
                @foreach([
                    ['Present days', $presentDays],
                    ['Working time', $fmt($minutesWorked)],
                    ['Advances (approved)', 'RM ' . number_format($advanceTotal, 2)],
                    ['Advances (pending)', 'RM ' . number_format($advancePending, 2)],
                    ['Sales in shifts', 'RM ' . number_format($salesTotal, 2)],
                ] as [$label, $value])
                <div class="col-6 col-md-4 mb-3">
                    <div class="card h-100"><div class="card-body p-3">
                        <small class="text-muted">{{ $label }}</small>
                        <div class="fs-5 fw-semibold">{{ $value }}</div>
                    </div></div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <h5 class="card-header">Shifts in {{ \Carbon\Carbon::parse($month . '-01')->format('F Y') }}</h5>
        <div class="table-responsive">
            <table class="table table-bordered mb-0">
                <thead>
                    <tr><th>Date</th><th>Shift</th><th>Login</th><th>Logout</th><th>How it ended</th><th>Hours</th><th class="text-end">Sales (RM)</th></tr>
                </thead>
                <tbody>
                    @forelse($attendance as $a)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($a->work_date)->format('D d M') }}</td>
                        <td>{{ $a->shift }}</td>
                        <td>{{ \Carbon\Carbon::parse($a->clock_in)->format('d M H:i') }}</td>
                        <td>{{ $a->clock_out ? \Carbon\Carbon::parse($a->clock_out)->format('d M H:i') : '' }}</td>
                        <td>
                            @switch($a->clock_out_reason)
                                @case('closing') Closing button @break
                                @case('logout') Logged out @break
                                @case('auto') <span class="text-warning">Auto logout</span> @break
                                @default <span class="text-success">Working now</span>
                            @endswitch
                        </td>
                        <td>{{ $a->minutesWorked() !== null ? $fmt($a->minutesWorked()) : '' }}</td>
                        <td class="text-end">{{ number_format(($shiftsByDate[\Carbon\Carbon::parse($a->work_date)->toDateString()] ?? collect())->sum('total_order_amount'), 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-muted">No shifts this month.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <h5 class="card-header">Advances in {{ \Carbon\Carbon::parse($month . '-01')->format('F Y') }}</h5>
        <div class="table-responsive">
            <table class="table table-bordered mb-0">
                <thead><tr><th>Date</th><th class="text-end">Amount (RM)</th><th>Reason</th><th>Status</th><th>Processed By</th></tr></thead>
                <tbody>
                    @forelse($advances as $b)
                    <tr>
                        <td>{{ $b->created_at->format('d M H:i') }}</td>
                        <td class="text-end">{{ number_format($b->amount, 2) }}</td>
                        <td>{{ $b->reason }}</td>
                        <td>{{ ucfirst($b->status) }}@if($b->reject_reason)<br><small>{{ $b->reject_reason }}</small>@endif</td>
                        <td>{{ $b->approver->name ?? '' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-muted">No advances this month.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
