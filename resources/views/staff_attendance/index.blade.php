@extends('layouts.app')

@section('content')
<style>
    #summaryTable th, #summaryTable td,
    #dailyTable th, #dailyTable td {
        padding: 10px;
    }
</style>
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        <a class="text-muted fw-light" href="{{ route('home') }}">{{ __('Home') }} /</a> {{ __('Staff Attendance') }}
    </h4>

    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-2">
                <div class="col-auto"><input type="date" name="date_from" class="form-control" value="{{ $date_from }}"></div>
                <div class="col-auto"><input type="date" name="date_to" class="form-control" value="{{ $date_to }}"></div>
                @if($branches->count())
                <div class="col-auto">
                    <select name="branch_id" class="form-select">
                        <option value="">{{ __('All Branches') }}</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->branch_code ?? $b->id }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-auto"><button class="btn btn-primary" type="submit">{{ __('Search') }}</button></div>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header flex-column flex-md-row">
            <div class="head-label">
                <h5 class="card-title mb-0">{{ __('Summary') }}</h5>
            </div>
        </div>
        <div class="card-datatable text-nowrap">
            <table class="dt-column-search table table-bordered" id="summaryTable">
                <thead>
                    <tr>
                        <th>{{ __('Staff') }}</th>
                        <th>{{ __('Days Worked') }}</th>
                        <th>{{ __('Total Hours') }}</th>
                        <th>{{ __('Forgot to Close (days)') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($summary as $s)
                    <tr>
                        <td>{{ $s->user->name ?? '' }} ({{ $s->user->username ?? '' }})</td>
                        <td>{{ $s->days }}</td>
                        <td data-order="{{ $s->minutes }}">{{ \App\Http\Controllers\StaffAttendanceController::formatMinutes($s->minutes) }}</td>
                        <td>
                            @if($s->not_closed > 0)
                                <span class="text-danger fw-bold">{{ $s->not_closed }}</span>
                            @else
                                0
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header flex-column flex-md-row">
            <div class="head-label">
                <h5 class="card-title mb-0">{{ __('Daily Records') }}</h5>
            </div>
        </div>
        <div class="card-datatable text-nowrap">
            <table class="dt-column-search table table-bordered" id="dailyTable">
                <thead>
                    <tr>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Staff') }}</th>
                        <th>{{ __('Clock In') }}</th>
                        <th>{{ __('Clock Out (Closing)') }}</th>
                        <th>{{ __('Hours') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($records as $r)
                    <tr>
                        <td>{{ $r->work_date }}</td>
                        <td>{{ $r->user->name ?? '' }} ({{ $r->user->username ?? '' }})</td>
                        <td>{{ $r->clock_in }}</td>
                        <td>
                            @if($r->clock_out) {{ $r->clock_out }}
                            @else <span style="color:red">{{ __('Not closed') }}</span> @endif
                        </td>
                        <td>
                            @if($r->clock_out)
                                {{ \App\Http\Controllers\StaffAttendanceController::formatMinutes(\Carbon\Carbon::parse($r->clock_in)->diffInMinutes(\Carbon\Carbon::parse($r->clock_out))) }}
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
    var dom = '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>><"table-responsive"t><"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>';

    $('#summaryTable').DataTable({
        dom: dom,
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50, 75, 100],
        order: [[1, 'desc']],
    });

    $('#dailyTable').DataTable({
        dom: dom,
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50, 75, 100],
        order: [[0, 'desc']],
    });
});
</script>
@endsection