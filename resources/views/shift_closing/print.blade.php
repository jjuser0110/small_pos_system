<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shift Report {{ $date_from }} – {{ $date_to }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #000; margin: 20px; }
        h2 { margin: 0 0 4px; }
        .meta { margin-bottom: 14px; line-height: 1.6; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 5px 7px; }
        th { background: #eee; text-align: left; }
        td.num, th.num { text-align: right; }
        tfoot td { font-weight: bold; background: #f5f5f5; }
        .footer { margin-top: 14px; font-size: 11px; color: #555; }
        .no-print { margin-bottom: 15px; }
        @media print { .no-print { display: none; } body { margin: 0; } thead { display: table-header-group; } tr { page-break-inside: avoid; } }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">Print</button>
        <button onclick="window.close()">Close</button>
    </div>

    <h2>Shift Report</h2>
    <div class="meta">
        <div><b>Shift date:</b> {{ $date_from }} – {{ $date_to }}</div>
        <div><b>Shift:</b> {{ $shift ?: 'All' }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th><th>Shift</th><th>Staff</th><th>Company</th>
                <th class="num">Orders</th><th class="num">Items</th><th class="num">Sales</th>
                <th class="num">Refunds</th><th class="num">Advances</th><th class="num">Net</th>
                @foreach($totals['methods']->keys() as $m)<th class="num">{{ $m }}</th>@endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($shift_closing as $row)
            <tr>
                <td>{{ $row->work_date }}</td>
                <td>{{ $row->shift }}</td>
                <td>{{ $row->user->name ?? '' }}</td>
                <td>{{ $row->company_code }}</td>
                <td class="num">{{ $row->total_order_count + 0 }}</td>
                <td class="num">{{ $row->total_item_count + 0 }}</td>
                <td class="num">{{ number_format($row->total_order_amount, 2) }}</td>
                <td class="num">{{ number_format($row->refund_amount, 2) }}</td>
                <td class="num">{{ number_format($row->advance_amount, 2) }}</td>
                <td class="num">{{ number_format($row->netAmount(), 2) }}</td>
                @foreach($totals['methods']->keys() as $m)
                    <td class="num">{{ number_format($row->items->where('payment_method', $m)->sum('amount'), 2) }}</td>
                @endforeach
            </tr>
            @empty
            <tr><td colspan="{{ 10 + $totals['methods']->count() }}" style="text-align:center">No shifts found</td></tr>
            @endforelse
        </tbody>
        @if($shift_closing->count())
        <tfoot>
            <tr>
                <td colspan="4" class="num">Total</td>
                <td class="num">{{ $totals['orders'] + 0 }}</td>
                <td class="num">{{ $totals['items'] + 0 }}</td>
                <td class="num">{{ number_format($totals['sales'], 2) }}</td>
                <td class="num">{{ number_format($totals['refunds'], 2) }}</td>
                <td class="num">{{ number_format($totals['advances'], 2) }}</td>
                <td class="num">{{ number_format($totals['net'], 2) }}</td>
                @foreach($totals['methods'] as $amount)<td class="num">{{ number_format($amount, 2) }}</td>@endforeach
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="footer">Printed by {{ $login_user->name ?? $login_user->username }} on {{ now()->format('d/m/Y h:i A') }}</div>
    <script>window.onload = function () { window.print(); };</script>
</body>
</html>
