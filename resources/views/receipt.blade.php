<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt Preview</title>
    <style>
        body {
            margin: 0;
            padding: 20px;
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
            body {
                background: white;
                padding: 0;
            }
            .receipt {
                box-shadow: none;
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <div class="receipt">
        <!-- Header -->
        <div class="receipt-header">
            <div class="shop-name">Boy Shop</div>
            <div class="shop-info">
                No. 123, Jalan Example,<br>
                Taman ABC, 93350 Kuching,<br>
                Sarawak, Malaysia<br>
                Tel: +60 12-345 6789<br>
                Email: shop@example.com
            </div>
        </div>

        <!-- Receipt Title -->
        <div class="receipt-title">SALES RECEIPT</div>

        <!-- Receipt Info -->
        <div class="receipt-info">
            <div class="info-row">
                <span>Receipt No:</span>
                <span><strong>RCP-20250117-001</strong></span>
            </div>
            <div class="info-row">
                <span>Date:</span>
                <span>17/01/2025 14:32:15</span>
            </div>
            <div class="info-row">
                <span>Cashier:</span>
                <span>Admin</span>
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
            <div class="item">
                <div class="item-row">
                    <span class="item-name">Laptop</span>
                    <span style="text-align: center;">1</span>
                    <span style="text-align: right;">999.99</span>
                </div>
                <div style="font-size: 10px; color: #666; margin-left: 5px;">
                    @ RM 999.99 each
                </div>
            </div>

            <div class="item">
                <div class="item-row">
                    <span class="item-name">Wireless Mouse</span>
                    <span style="text-align: center;">2</span>
                    <span style="text-align: right;">59.98</span>
                </div>
                <div style="font-size: 10px; color: #666; margin-left: 5px;">
                    @ RM 29.99 each
                </div>
            </div>

            <div class="item">
                <div class="item-row">
                    <span class="item-name">USB Cable Type-C</span>
                    <span style="text-align: center;">3</span>
                    <span style="text-align: right;">44.97</span>
                </div>
                <div style="font-size: 10px; color: #666; margin-left: 5px;">
                    @ RM 14.99 each
                </div>
            </div>

            <div class="item">
                <div class="item-row">
                    <span class="item-name">Keyboard Mechanical</span>
                    <span style="text-align: center;">1</span>
                    <span style="text-align: right;">299.00</span>
                </div>
                <div style="font-size: 10px; color: #666; margin-left: 5px;">
                    @ RM 299.00 each
                </div>
            </div>
        </div>

        <!-- Totals -->
        <div class="totals">
            <div class="total-row">
                <span>Subtotal:</span>
                <span>RM 1,403.94</span>
            </div>
            <div class="total-row">
                <span>Tax (6%):</span>
                <span>RM 84.24</span>
            </div>
            <div class="total-row">
                <span>Discount:</span>
                <span>RM 0.00</span>
            </div>
            
            <div class="total-row grand-total">
                <span>TOTAL:</span>
                <span>RM 1,488.18</span>
            </div>
        </div>

        <!-- Payment Info -->
        <div class="payment-info">
            <div class="payment-row">
                <span>Payment Method:</span>
                <span><strong>CASH</strong></span>
            </div>
            <div class="payment-row">
                <span>Amount Paid:</span>
                <span>RM 1,500.00</span>
            </div>
            <div class="payment-row" style="font-weight: bold;">
                <span>Change:</span>
                <span>RM 11.82</span>
            </div>
        </div>

        <!-- Footer -->
        <div class="receipt-footer">
            <div class="thank-you">THANK YOU!</div>
            <div>Please keep this receipt for<br>exchange or refund purposes.</div>
            <div style="margin-top: 10px;">
                Valid for 7 days from purchase date
            </div>
            <div style="margin-top: 15px; font-size: 10px;">
                ** GOODS SOLD ARE NOT REFUNDABLE **<br>
                EXCHANGE ONLY WITH ORIGINAL RECEIPT
            </div>
        </div>
    </div>
</body>
</html>