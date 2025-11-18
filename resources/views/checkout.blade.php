<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - POS System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            overflow: hidden;
        }

        .checkout-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            height: 100vh;
            gap: 20px;
            padding: 20px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 18px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .page-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e0e0e0;
        }

        .back-btn {
            width: 36px;
            height: 36px;
            border: none;
            background: #f0f0f0;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .back-btn:hover {
            background: #667eea;
            color: white;
            transform: translateX(-3px);
        }

        h1 {
            color: #667eea;
            font-size: 20px;
            flex: 1;
        }

        .section-title {
            font-size: 15px;
            color: #333;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .order-items-container {
            flex: 1;
            overflow-y: auto;
            margin-bottom: 10px;
        }

        .order-items-container::-webkit-scrollbar {
            width: 8px;
        }

        .order-items-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .order-items-container::-webkit-scrollbar-thumb {
            background: #667eea;
            border-radius: 10px;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 8px;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 2px;
            font-size: 13px;
        }

        .item-meta {
            color: #667eea;
            font-size: 11px;
        }

        .item-total {
            font-weight: bold;
            color: #333;
            font-size: 14px;
        }

        .summary-section {
            padding-top: 10px;
            border-top: 2px solid #e0e0e0;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 14px;
        }

        .summary-row.total {
            font-size: 22px;
            font-weight: bold;
            color: #667eea;
            margin-top: 6px;
            padding-top: 10px;
            border-top: 2px solid #e0e0e0;
        }

        .payment-section {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .payment-methods {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 15px;
        }

        .payment-method {
            padding: 12px 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }

        .payment-method:hover {
            border-color: #667eea;
            background: #f8f9ff;
        }

        .payment-method.active {
            border-color: #667eea;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .payment-icon {
            font-size: 32px;
            margin-bottom: 5px;
        }

        .payment-name {
            font-size: 13px;
            font-weight: 600;
        }

        .amount-section {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .amount-label {
            font-size: 14px;
            color: #555;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .amount-input {
            width: 100%;
            padding: 15px;
            border: 3px solid #e0e0e0;
            border-radius: 8px;
            font-size: 28px;
            font-weight: bold;
            text-align: center;
            color: #667eea;
            margin-bottom: 10px;
        }

        .amount-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .quick-amount-btns {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-bottom: 12px;
        }

        .quick-amount-btn {
            padding: 10px;
            background: #f0f0f0;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.2s;
        }

        .quick-amount-btn:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .quick-amount-btn:active {
            transform: scale(0.95);
        }

        .change-display {
            background: #e8f4ff;
            border: 3px solid #667eea;
            border-radius: 12px;
            padding: 18px;
            margin-bottom: 12px;
            text-align: center;
        }

        .change-label {
            font-size: 14px;
            color: #555;
            margin-bottom: 6px;
            font-weight: 600;
        }

        .change-amount {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
        }

        .btn-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .btn {
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-print {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-print:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-print:active:not(:disabled) {
            transform: translateY(0);
        }

        .btn-complete {
            background: #27ae60;
            color: white;
        }

        .btn-complete:hover:not(:disabled) {
            background: #229954;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(39, 174, 96, 0.4);
        }

        .btn-complete:active:not(:disabled) {
            transform: translateY(0);
        }

        .btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
            transform: none;
        }

        .insufficient-payment {
            background: #ffe8e8;
            border: 3px solid #e74c3c;
            color: #c0392b;
            padding: 12px;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
            margin-bottom: 12px;
            font-size: 14px;
        }

        .empty-cart {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }

        /* Receipt Print Styles */
        @media print {
            body { background: white; padding: 0; }
            .checkout-container { display: block; max-width: 80mm; }
            .card { box-shadow: none; border-radius: 0; padding: 10px; }
            .card:last-child { display: none; }
            .back-btn { display: none; }
            .page-header { border: none; margin-bottom: 10px; padding-bottom: 5px; }
            h1 { font-size: 18px; text-align: center; }
            .receipt-header { display: block !important; }
        }

        .receipt-header {
            display: none;
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px dashed #333;
        }

        .receipt-header h2 {
            font-size: 20px;
            margin-bottom: 5px;
        }

        .receipt-header p {
            font-size: 12px;
            color: #666;
            margin: 2px 0;
        }
    </style>
</head>
<body>
    <div class="checkout-container">
        <!-- Left Side - Order Summary -->
        <div class="card">
            <!-- Receipt Header (only visible when printing) -->
            <!-- <div class="receipt-header">
                <h2>YOUR SHOP NAME</h2>
                <p>Address Line 1</p>
                <p>Phone: +60 12-345 6789</p>
                <p id="receiptDate"></p>
            </div> -->

            <div class="page-header">
                <button class="back-btn" onclick="window.location.href='/counter'">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </button>
                <h1>Order Summary</h1>
            </div>

            <div class="order-items-container" id="orderItems"></div>

            <div class="summary-section">
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span id="subtotal">RM 0.00</span>
                </div>
                <div class="summary-row total">
                    <span>TOTAL:</span>
                    <span id="total">RM 0.00</span>
                </div>
            </div>
        </div>

        <!-- Right Side - Payment -->
        <div class="card">
            <div class="page-header" style="border: none; padding-bottom: 0; margin-bottom: 12px;">
                <h1>Payment</h1>
            </div>

            <div class="payment-section">
                <div class="section-title">Payment Method</div>
                <div class="payment-methods">
                    @foreach($payment_method as $row=> $payment)
                    <div class="payment-method @if($row == 0) active @endif" data-method="{{ $payment->payment_method_name??'' }}" onclick="selectPayment('{{ $payment->payment_method_name??'' }}')">
                        <!-- <div class="payment-icon">💳</div> -->
                        <div class="payment-name">{{ $payment->payment_method_name??'' }}</div>
                    </div>
                    @endforeach
                </div>

                <div class="amount-section">
                    <div class="amount-label">Amount Received</div>
                    <input type="number" id="amountReceived" class="amount-input" placeholder="0.00" step="0.01" oninput="calculateChange()">
                    
                    <div class="quick-amount-btns">
                        <button class="quick-amount-btn" onclick="setQuickAmount(10)">RM 10</button>
                        <button class="quick-amount-btn" onclick="setQuickAmount(20)">RM 20</button>
                        <button class="quick-amount-btn" onclick="setQuickAmount(50)">RM 50</button>
                        <button class="quick-amount-btn" onclick="setQuickAmount(100)">RM 100</button>
                    </div>

                    <div id="insufficientMsg" class="insufficient-payment" style="display: none;">
                        ⚠️ Insufficient Payment Amount!
                    </div>

                    <div id="changeDisplay" style="display: none;">
                        <div class="change-display">
                            <div class="change-label">Change</div>
                            <div class="change-amount" id="changeAmount">RM 0.00</div>
                        </div>
                    </div>

                    <div class="btn-group">
                        <button class="btn btn-print" id="printBtn" onclick="printReceipt()" disabled>
                            🖨️ Print Receipt
                        </button>
                        <button class="btn btn-complete" id="completeBtn" onclick="completeOrder()" disabled>
                            ✓ Complete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let cart = [];
        let selectedPaymentMethod = 'cash';
        let totalAmount = 0;

        // Load cart from server
        function loadCart() {
            fetch('/cart/load')
                .then(res => res.json())
                .then(data => {
                    if (data.length === 0) {
                        showEmptyCart();
                        return;
                    }
                    cart = data.map(item => ({
                        id: item.product_id,
                        name: item.product_name,
                        price: parseFloat(item.single_price),
                        quantity: item.quantity
                    }));
                    displayCart();
                    calculateTotals();
                })
                .catch(err => {
                    console.error('Error loading cart:', err);
                    alert('Failed to load cart items');
                });
        }

        function showEmptyCart() {
            document.getElementById('orderItems').innerHTML = `
                <div class="empty-cart">
                    <div class="empty-icon">🛒</div>
                    <p style="font-size: 18px;">Your cart is empty</p>
                </div>
            `;
        }

        function displayCart() {
            const container = document.getElementById('orderItems');
            container.innerHTML = cart.map(item => `
                <div class="order-item">
                    <div class="item-details">
                        <div class="item-name">${item.name}</div>
                        <div class="item-meta">RM ${item.price.toFixed(2)} × ${item.quantity}</div>
                    </div>
                    <div class="item-total">RM ${(item.price * item.quantity).toFixed(2)}</div>
                </div>
            `).join('');
        }

        function calculateTotals() {
            const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            totalAmount = subtotal;

            document.getElementById('subtotal').textContent = `RM ${subtotal.toFixed(2)}`;
            document.getElementById('total').textContent = `RM ${totalAmount.toFixed(2)}`;
        }

        function selectPayment(method) {
            selectedPaymentMethod = method;
            
            // Update active state
            document.querySelectorAll('.payment-method').forEach(el => {
                el.classList.remove('active');
            });
            document.querySelector(`[data-method="${method}"]`).classList.add('active');
            
            calculateChange();
        }

        function setQuickAmount(amount) {
            document.getElementById('amountReceived').value = amount;
            calculateChange();
        }

        function calculateChange() {
            const received = parseFloat(document.getElementById('amountReceived').value) || 0;
            const change = received - totalAmount;

            const changeDisplay = document.getElementById('changeDisplay');
            const insufficientMsg = document.getElementById('insufficientMsg');
            const printBtn = document.getElementById('printBtn');
            const completeBtn = document.getElementById('completeBtn');

            if (received === 0) {
                changeDisplay.style.display = 'none';
                insufficientMsg.style.display = 'none';
                printBtn.disabled = true;
                completeBtn.disabled = true;
            } else if (change < 0) {
                changeDisplay.style.display = 'none';
                insufficientMsg.style.display = 'block';
                printBtn.disabled = true;
                completeBtn.disabled = true;
            } else {
                changeDisplay.style.display = 'block';
                insufficientMsg.style.display = 'none';
                document.getElementById('changeAmount').textContent = `RM ${change.toFixed(2)}`;
                printBtn.disabled = false;
                completeBtn.disabled = false;
            }
        }

        function printReceipt() {
            alert('pending');
        }

        function completeOrder() {
            if (cart.length === 0) {
                alert('Cart is empty!');
                return;
            }

            const received = parseFloat(document.getElementById('amountReceived').value) || 0;
            if (received < totalAmount) {
                alert('Insufficient payment amount!');
                return;
            }

            const change = received - totalAmount;

            const orderData = {
                payment_method: selectedPaymentMethod,
                total: totalAmount,
                amount_received: received,
                change: change,
            };

            // Send to server
            fetch('/cart/placeorder', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(orderData)
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    alert(`Order completed successfully!\n\nTotal: RM ${totalAmount.toFixed(2)}\nReceived: RM ${received.toFixed(2)}\nChange: RM ${change.toFixed(2)}\n\nThank you!`);
                    
                    // Clear cart and redirect
                    window.location.href = '/counter';
                } else {
                    alert('Failed to place order: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(err => {
                console.error('Error placing order:', err);
                alert('Failed to place order. Please try again.');
            });
        }

        // Initialize
        loadCart();

        // Auto-focus amount input
        setTimeout(() => {
            document.getElementById('amountReceived').focus();
        }, 500);
    </script>
</body>
</html>