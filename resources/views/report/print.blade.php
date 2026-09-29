<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Report - {{ now()->format('Y-m-d') }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #000; margin: 20px; }
        h2 { margin: 0 0 4px; }
        .meta { margin-bottom: 14px; line-height: 1.6; }
        .meta span { display: inline-block; min-width: 90px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 6px 8px; }
        th { background: #eee; text-align: left; }
        td.num, th.num { text-align: right; }
        tfoot td { font-weight: bold; background: #f5f5f5; }
        .footer { margin-top: 14px; font-size: 11px; color: #555; }
        .no-print { margin-bottom: 15px; }
        .no-print button { padding: 6px 14px; cursor: pointer; }

        @media print {
            .no-print { display: none; }
            body { margin: 0; }
            thead { display: table-header-group; } /* repeat header on each page */
            tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">Print</button>
        <button onclick="window.close()">Close</button>
    </div>

    <h2>Sales Report</h2>
    <div class="meta">
        <div><span>Date:</span> {{ $date_from->format('d/m/Y h:i A') }} - {{ $date_to->format('d/m/Y h:i A') }}</div>
        <div><span>Branches:</span> {{ $selectedBranches }}</div>
        <div><span>Companies:</span> {{ $selectedCompanies }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:40px">No</th>
                <th>Branch</th>
                <th>Company</th>
                <th>Product</th>
                <th class="num">Total Quantity</th>
                <th class="num">Total Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($itemTotals as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item->branch->branch_name ?? '' }}</td>
                    <td>{{ $item->company->company_name ?? '' }}</td>
                    <td>{{ $item->product->product_name ?? '' }}</td>
                    <td class="num">{{ $item->total_quantity }}</td>
                    <td class="num">{{ number_format($item->total_amount, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align:center">No data found</td>
                </tr>
            @endforelse
        </tbody>
        @if($itemTotals->count())
        <tfoot>
            <tr>
                <td colspan="4" class="num">Grand Total</td>
                <td class="num">{{ $grandQuantity }}</td>
                <td class="num">{{ number_format($grandAmount, 2) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="footer">
        Printed by {{ $login_user->name ?? $login_user->username }} on {{ now()->format('d/m/Y h:i A') }}
    </div>

    <script>
        window.onload = function () { window.print(); };
    </script>
</body>
</html>