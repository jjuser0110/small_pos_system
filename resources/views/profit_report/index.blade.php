@extends('layouts.app')

@section('content')
<style>
    #profitTable th, #profitTable td { padding: 6px 8px; white-space: nowrap; }
    #profitTable td.num, #profitTable th.num { text-align: right; }
    #profitTable tr.company-row td { background: #f3fbf6; font-weight: 600; }
    #profitTable tr.branch-row td { background: #dcefe3; font-weight: 700; }
    #profitTable tr.total-row td { background: #16a34a; color: #fff; font-weight: 700; }
    .neg { color: #dc2626; }
</style>
@php
    $pct = fn ($v) => $v === null ? '-' : number_format($v, 1) . '%';
    $cls = fn ($v) => $v < 0 ? 'neg' : '';
@endphp
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4"><span class="text-muted fw-light">Profit Report</span></h4>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Shift date</label>
                    <div class="input-group">
                        <input type="date" class="form-control" name="date_from" value="{{ $date_from }}">
                        <span class="input-group-text">to</span>
                        <input type="date" class="form-control" name="date_to" value="{{ $date_to }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Branch</label>
                    <select name="branch_id" class="form-select">
                        <option value="">All branches</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" @selected(request('branch_id') == $b->id)>{{ $b->branch_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Company</label>
                    <select name="company_id" class="form-select">
                        <option value="">All companies</option>
                        @foreach($companies as $c)
                            <option value="{{ $c->id }}" @selected(request('company_id') == $c->id)>{{ $c->company_code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">Shift</label>
                    <select name="shift" class="form-select no-select2">
                        <option value="">All</option>
                        @foreach(array_keys(config('fsmart.shifts')) as $s)
                            <option value="{{ $s }}" @selected(request('shift') === $s)>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 text-end">
                    <button class="btn btn-primary w-100">Show</button>
                </div>
            </form>
        </div>
    </div>

    {{-- headline numbers --}}
    <div class="row mb-2">
        @foreach([
            ['Net Sales', money($overall->net_sales), 'sales - refunds'],
            ['Capital (cost of goods)', money($overall->cost), 'what the sold stock cost'],
            ['Gross Profit', money($overall->gross), 'margin ' . $pct($overall->margin)],
            ['Damage', money($overall->damage), 'cost of approved damage'],
            ['Net Profit', money($overall->net), 'gross profit - damage'],
            ['Advances paid', money($overall->advances), 'cash to staff'],
        ] as [$label, $value, $hint])
        <div class="col-6 col-md-2 mb-3">
            <div class="card h-100"><div class="card-body p-3">
                <small class="text-muted">{{ $label }}</small>
                <div class="fs-5 fw-semibold">{{ $value }}</div>
                <small class="text-muted">{{ $hint }}</small>
            </div></div>
        </div>
        @endforeach
    </div>

    @if($overall->uncosted_sales > 0.005)
        <div class="alert alert-warning">
            <b>{{ money($overall->uncosted_sales) }}</b> of sales came from stock with no cost recorded (initial stock,
            manual adjustments or selling into negative stock). Their capital is unknown, so profit above is
            overstated by up to that amount. Add stock through <b>Stock</b> with a cost to fix this going forward.
        </div>
    @endif

    {{-- branch summary --}}
    <div class="card mb-4">
        <h5 class="card-header">By Branch</h5>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead><tr><th>Branch</th><th class="text-end">Net Sales</th><th class="text-end">Capital</th><th class="text-end">Gross Profit</th><th class="text-end">Margin</th><th class="text-end">Damage</th><th class="text-end">Net Profit</th></tr></thead>
                <tbody>
                    @forelse($byBranch as $b)
                        <tr>
                            <td>{{ $b->branch->branch_name ?? '-' }}</td>
                            <td class="text-end">{{ money($b->net_sales) }}</td>
                            <td class="text-end">{{ money($b->cost) }}</td>
                            <td class="text-end {{ $cls($b->gross) }}">{{ money($b->gross) }}</td>
                            <td class="text-end">{{ $pct($b->margin) }}</td>
                            <td class="text-end">{{ money($b->damage) }}</td>
                            <td class="text-end fw-semibold {{ $cls($b->net) }}">{{ money($b->net) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-muted">No sales in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- branch -> company -> shift --}}
    <div class="card mb-4">
        <h5 class="card-header">By Branch, Company and Shift</h5>
        <div class="table-responsive">
            <table class="table table-bordered mb-0" id="profitTable">
                <thead>
                    <tr>
                        <th>Branch / Company / Shift</th>
                        <th class="num">Shifts</th><th class="num">Orders</th>
                        <th class="num">Sales</th><th class="num">Refunds</th><th class="num">Net Sales</th>
                        <th class="num">Capital</th><th class="num">Gross Profit</th><th class="num">Margin</th>
                        <th class="num">Damage</th><th class="num">Net Profit</th><th class="num">Advances</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($byCompany->groupBy('branch_id') as $branchId => $companyRows)
                        @php $bt = $byBranch[$branchId]; @endphp
                        @foreach($companyRows as $c)
                            @foreach($c->shiftRows as $r)
                            <tr>
                                <td class="ps-4">{{ $c->company->company_code ?? '' }} · <b>{{ $r->shift }}</b></td>
                                <td class="num">{{ $r->shifts }}</td><td class="num">{{ $r->orders + 0 }}</td>
                                <td class="num">{{ money($r->sales) }}</td><td class="num">{{ money($r->refunds) }}</td>
                                <td class="num">{{ money($r->net_sales) }}</td><td class="num">{{ money($r->cost) }}</td>
                                <td class="num {{ $cls($r->gross) }}">{{ money($r->gross) }}</td>
                                <td class="num">{{ $pct($r->net_sales > 0 ? round($r->gross / $r->net_sales * 100, 1) : null) }}</td>
                                <td class="num">-</td>
                                <td class="num">-</td>
                                <td class="num">{{ money($r->advances) }}</td>
                            </tr>
                            @endforeach
                            <tr class="company-row">
                                <td>{{ $c->company->company_name ?? 'Company #' . $c->company_id }} total</td>
                                <td class="num">{{ $c->shifts }}</td><td class="num">{{ $c->orders + 0 }}</td>
                                <td class="num">{{ money($c->sales) }}</td><td class="num">{{ money($c->refunds) }}</td>
                                <td class="num">{{ money($c->net_sales) }}</td><td class="num">{{ money($c->cost) }}</td>
                                <td class="num {{ $cls($c->gross) }}">{{ money($c->gross) }}</td><td class="num">{{ $pct($c->margin) }}</td>
                                <td class="num">{{ money($c->damage) }}</td>
                                <td class="num {{ $cls($c->net) }}">{{ money($c->net) }}</td>
                                <td class="num">{{ money($c->advances) }}</td>
                            </tr>
                        @endforeach
                        <tr class="branch-row">
                            <td>{{ $bt->branch->branch_name ?? 'Branch' }} total</td>
                            <td class="num">{{ $bt->shifts }}</td><td class="num">{{ $bt->orders + 0 }}</td>
                            <td class="num">{{ money($bt->sales) }}</td><td class="num">{{ money($bt->refunds) }}</td>
                            <td class="num">{{ money($bt->net_sales) }}</td><td class="num">{{ money($bt->cost) }}</td>
                            <td class="num {{ $cls($bt->gross) }}">{{ money($bt->gross) }}</td><td class="num">{{ $pct($bt->margin) }}</td>
                            <td class="num">{{ money($bt->damage) }}</td>
                            <td class="num {{ $cls($bt->net) }}">{{ money($bt->net) }}</td>
                            <td class="num">{{ money($bt->advances) }}</td>
                        </tr>
                    @endforeach
                    @if($byCompany->isNotEmpty())
                    <tr class="total-row">
                        <td>All</td>
                        <td class="num">{{ $overall->shifts }}</td><td class="num">{{ $overall->orders + 0 }}</td>
                        <td class="num">{{ money($overall->sales) }}</td><td class="num">{{ money($overall->refunds) }}</td>
                        <td class="num">{{ money($overall->net_sales) }}</td><td class="num">{{ money($overall->cost) }}</td>
                        <td class="num">{{ money($overall->gross) }}</td><td class="num">{{ $pct($overall->margin) }}</td>
                        <td class="num">{{ money($overall->damage) }}</td><td class="num">{{ money($overall->net) }}</td>
                        <td class="num">{{ money($overall->advances) }}</td>
                    </tr>
                    @else
                    <tr><td colspan="12" class="text-muted">No sales in this period.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <h5 class="card-header">Best items by profit</h5>
                <table class="table table-sm mb-0">
                    <thead><tr><th>Item</th><th>Company</th><th class="text-end">Qty</th><th class="text-end">Sales</th><th class="text-end">Profit</th></tr></thead>
                    <tbody>
                        @forelse($topItems as $i)
                            <tr><td>{{ $i->product->product_name ?? '' }}</td><td>{{ $i->product->company->company_code ?? '' }}</td>
                                <td class="text-end">{{ $i->qty + 0 }}</td><td class="text-end">{{ money($i->sales) }}</td>
                                <td class="text-end {{ $cls($i->profit) }}">{{ money($i->profit) }}</td></tr>
                        @empty
                            <tr><td colspan="5" class="text-muted">No costed sales yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <h5 class="card-header">Lowest profit items <small class="text-muted">(check price or cost)</small></h5>
                <table class="table table-sm mb-0">
                    <thead><tr><th>Item</th><th>Company</th><th class="text-end">Qty</th><th class="text-end">Sales</th><th class="text-end">Profit</th></tr></thead>
                    <tbody>
                        @forelse($lowItems as $i)
                            <tr><td>{{ $i->product->product_name ?? '' }}</td><td>{{ $i->product->company->company_code ?? '' }}</td>
                                <td class="text-end">{{ $i->qty + 0 }}</td><td class="text-end">{{ money($i->sales) }}</td>
                                <td class="text-end {{ $cls($i->profit) }}">{{ money($i->profit) }}</td></tr>
                        @empty
                            <tr><td colspan="5" class="text-muted">No costed sales yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <h5 class="card-header">Stock on hand (at cost)</h5>
        <table class="table table-sm mb-0">
            <thead><tr><th>Branch</th><th>Company</th><th class="text-end">Value</th></tr></thead>
            <tbody>
                @forelse($stockValue as $v)
                    <tr><td>{{ $v->company->branch->branch_name ?? '' }}</td><td>{{ $v->company->company_name ?? '' }}</td><td class="text-end">{{ money($v->value) }}</td></tr>
                @empty
                    <tr><td colspan="3" class="text-muted">No costed stock.</td></tr>
                @endforelse
            </tbody>
            @if($stockValue->count())
                <tfoot><tr><th colspan="2">Total</th><th class="text-end">{{ money($stockValue->sum('value')) }}</th></tr></tfoot>
            @endif
        </table>
    </div>
</div>
@endsection
