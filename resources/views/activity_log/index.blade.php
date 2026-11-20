@extends('layouts.app')

@section('content')
@php
    $actionColor = [
        'created' => 'success', 'updated' => 'info', 'deleted' => 'danger', 'restored' => 'primary',
        'rejected' => 'warning', 'login' => 'secondary', 'logout' => 'secondary', 'login_failed' => 'danger',
    ];
    $show = function ($v) {
        if ($v === null || $v === '') return '—';
        return \Illuminate\Support\Str::limit(is_bool($v) ? ($v ? 'true' : 'false') : (string) $v, 120);
    };
@endphp
<style>
    .log-changes { font-size: .8rem; margin: 0; }
    .log-changes td { padding: 1px 6px !important; border: 0 !important; vertical-align: top; }
    .log-changes .old { text-decoration: line-through; opacity: .7; }
</style>
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        <a class="text-muted fw-light" href="{{ route('home') }}">{{ __('Home') }} /</a> {{ __('Activity Log') }}
    </h4>

    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label">{{ __('From') }}</label>
                    <input type="date" name="date_from" class="form-control" value="{{ $from->format('Y-m-d') }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">{{ __('To') }}</label>
                    <input type="date" name="date_to" class="form-control" value="{{ $to->format('Y-m-d') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('User') }}</label>
                    <select name="user_id" class="form-select">
                        <option value="">{{ __('All') }}</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" @selected(request('user_id') == $u->id)>{{ $u->name ?: $u->username }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">{{ __('Action') }}</label>
                    <select name="action" class="form-select">
                        <option value="">{{ __('All') }}</option>
                        @foreach($actions as $a)
                            <option value="{{ $a }}" @selected(request('action') == $a)>{{ __(ucfirst(str_replace('_', ' ', $a))) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">{{ __('Record') }}</label>
                    <select name="type" class="form-select">
                        <option value="">{{ __('All') }}</option>
                        @foreach($types as $t)
                            <option value="{{ $t }}" @selected(request('type') == $t)>{{ $t }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('Search') }}</label>
                    <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="{{ __('Name, no, IP…') }}">
                </div>
                <div class="col-12">
                    <button class="btn btn-primary">{{ __('Filter') }}</button>
                    <a href="{{ route('activity_log.index') }}" class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                    <small class="text-muted ms-2">{{ __('Showing :count record(s)', ['count' => $logs->total()]) }}</small>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0">
                <thead>
                    <tr>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('User') }}</th>
                        <th>{{ __('Action') }}</th>
                        <th>{{ __('Record') }}</th>
                        <th>{{ __('Changes') }}</th>
                        <th>{{ __('Company') }}</th>
                        <th>{{ __('IP / Device') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td style="white-space:nowrap">{{ $log->created_at->format('d M Y') }}<br><small>{{ $log->created_at->format('H:i:s') }}</small></td>
                        <td>{{ $log->user_name ?? __('System') }}</td>
                        <td><span class="badge bg-label-{{ $actionColor[$log->action] ?? 'dark' }}">{{ __(ucfirst(str_replace('_', ' ', $log->action))) }}</span></td>
                        <td style="min-width:160px">
                            @if($log->subject_type)
                                <b>{{ $log->subject_type }}</b> <small class="text-muted">#{{ $log->subject_id }}</small><br>
                                {{ $log->subject_label }}
                            @endif
                            @if($log->description)<div class="small text-muted">{{ $log->description }}</div>@endif
                        </td>
                        <td style="min-width:260px">
                            @if($log->changes)
                                <table class="log-changes">
                                    @foreach($log->changes as $field => $pair)
                                        <tr>
                                            <td class="text-muted">{{ $field }}</td>
                                            <td>
                                                @if($log->action === 'updated' || $log->action === 'rejected')
                                                    <span class="old">{{ $show($pair[0] ?? null) }}</span> &rarr;
                                                @endif
                                                <b>{{ $show($pair[1] ?? null) }}</b>
                                            </td>
                                        </tr>
                                    @endforeach
                                </table>
                            @endif
                        </td>
                        <td>{{ $log->company->company_code ?? '' }}<br><small class="text-muted">{{ $log->branch->branch_name ?? '' }}</small></td>
                        <td style="min-width:140px"><small>{{ $log->ip }}<br><span class="text-muted" title="{{ $log->user_agent }}">{{ \Illuminate\Support\Str::limit($log->user_agent, 40) }}</span></small></td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">{{ __('No activity in this period.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
            <div class="card-footer">{{ $logs->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>
</div>
@endsection
