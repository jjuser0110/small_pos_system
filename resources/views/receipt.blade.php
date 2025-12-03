<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt Preview</title>
    <style>
        body {
            background: #f5f5f5;
            font-family: 'Courier New', monospace;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .receipt {
            width: 80mm;
            background: white;
            padding: 10mm;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        .receipt-header {
            text-align: center;
            border-bottom: 2px dashed #000;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .shop-name {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .shop-info {
            font-size: 11px;
            line-height: 1.5;
        }

        .receipt-title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin: 15px 0;
        }

        .receipt-info {
            font-size: 11px;
            margin-bottom: 15px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
        }

        .items-header {
            border-top: 2px dashed #000;
            border-bottom: 1px solid #000;
            padding: 5px 0;
            font-size: 11px;
            font-weight: bold;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
        }

        .item {
            padding: 5px 0;
            font-size: 11px;
        }

        .item-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            margin-bottom: 3px;
        }

        .item-name {
            font-weight: bold;
        }

        .items-section {
            border-bottom: 1px solid #000;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }

        .totals {
            font-size: 12px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .total-row.grand-total {
            font-size: 16px;
            font-weight: bold;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            padding: 8px 0;
            margin: 10px 0;
        }

        .payment-info {
            font-size: 12px;
            margin-top: 10px;
        }

        .payment-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .receipt-footer {
            text-align: center;
            border-top: 2px dashed #000;
            padding-top: 15px;
            margin-top: 15px;
            font-size: 11px;
        }

        .thank-you {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .barcode {
            text-align: center;
            margin: 15px 0;
            font-family: 'Libre Barcode 39', cursive;
            font-size: 40px;
            letter-spacing: 2px;
        }

        
        @media print {
            @page {
                size: 72mm auto; /* 72mm wide, auto height */
                margin: 2mm 2mm 2mm 2mm; /* small margin top/right/bottom/left */
            }

            body {
                margin: 0;
                padding: 0;
                background: white;
            }

            .receipt {
                width: 68mm; /* slightly smaller than page to avoid cutting */
                padding: 2mm; /* reduce padding for print */
                margin: 0 auto;
                box-shadow: none;
            }

            button {
                display: none !important; /* hide the print button */
            }
        }

    </style>
</head>
<body>
     <button onclick="window.print()" 
        style="
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 18px;
            background: #000;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            z-index: 999;
        ">
        Print Receipt
    </button>
    <div class="receipt">
        <!-- Header -->
        <div class="receipt-header">
            {!! $receipt_setting->header !!}
        </div>

        <!-- Receipt Title -->
        <div class="receipt-title">SALES RECEIPT</div>

        <!-- Receipt Info -->
        <div class="receipt-info">
            <div class="info-row">
                <span>Receipt No:</span>
                <span><strong>{{$order->order_no??''}}</strong></span>
            </div>
            <div class="info-row">
                <span>Date:</span>
                <span>{{$order->created_at??''}}</span>
            </div>
            <div class="info-row">
                <span>Cashier:</span>
                <span>{{$order->user->username??''}}</span>
            </div>
        </div>

        <!-- Items Header -->
        <div class="items-header">
            <span>Item</span>
            <span style="text-align: center;">Qty</span>
            <span style="text-align: right;">Price</span>
        </div>

        <!-- Items Section -->
        <div class="items-section">
            @foreach($order->items as $item)
            <div class="item">
                <div class="item-row">
                    <span class="item-name">{{$item->product->product_name??''}}</span>
                    <span style="text-align: center;">{{$item->quantity??0}}</span>
                    <span style="text-align: right;">{{number_format($item->total_price??0,2)}}</span>
                </div>
                <div style="font-size: 10px; color: #666; margin-left: 5px;">
                    @ RM {{number_format($item->single_price??0,2)}} each
                </div>
            </div>
            @endforeach
        </div>

        <!-- Totals -->
        <div class="totals">
            
            <div class="total-row grand-total">
                <span>TOTAL:</span>
                <span>RM {{number_format($order->total_price??0,2)}}</span>
            </div>
        </div>

        <!-- Payment Info -->
        <div class="payment-info">
            <div class="payment-row">
                <span>Payment Method:</span>
                <span><strong>{{$order->payment_method??''}}</strong></span>
            </div>
            <div class="payment-row">
                <span>Amount Paid:</span>
                <span>RM {{number_format($order->amount_received??0,2)}}</span>
            </div>
            <div class="payment-row" style="font-weight: bold;">
                <span>Change:</span>
                <span>RM {{number_format($order->change??0,2)}}</span>
            </div>
        </div>

        <!-- Footer -->
        <div class="receipt-footer">
            {!! $receipt_setting->footer !!}
        </div>
    </div>
</body>
</html>