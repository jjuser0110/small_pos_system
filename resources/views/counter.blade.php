<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>POS Counter System</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #16a34a 0%, #166534 100%);
    min-height: 100vh;
    overflow-x: hidden;
    font-size: 13px;
}
.container {
    display: grid;
    grid-template-columns: 1fr 380px;
    height: calc(100vh - 30px);
    max-height: calc(100vh - 30px);
    gap: 15px;
    padding: 15px;
}
.left-panel, .right-panel {
    background: white;
    border-radius: 12px;
    padding: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}
.left-panel { overflow-y: auto; max-height: 100%; }
.left-panel::-webkit-scrollbar { width: 8px; }
.left-panel::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
.left-panel::-webkit-scrollbar-thumb { background: #16a34a; border-radius: 10px; }
.left-panel::-webkit-scrollbar-thumb:hover { background: #15803d; }

.right-panel { display: flex; flex-direction: column; max-height: 100%; overflow: hidden; }

h1 { color: #16a34a; margin-bottom: 15px; font-size: 18px; }

.barcode-scanner { margin-bottom: 15px; position: relative; }
.barcode-input {
    width: 100%; padding: 10px 40px 10px 12px;
    border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px;
    transition: all 0.3s;
}
.barcode-input:focus { outline: none; border-color: #16a34a; box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.1); }
.barcode-icon {
    position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
    color: #16a34a; pointer-events: none;
}

.categories { display: grid; grid-template-columns: repeat(auto-fit, minmax(90px, 1fr)); gap: 6px; margin-bottom: 15px; }
.category-btn { padding: 8px 6px; border: 2px solid #e0e0e0; background: white; border-radius: 8px; cursor: pointer; transition: all 0.3s; font-size: 12px; text-align: center; }
.category-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
.category-btn.active { background: linear-gradient(135deg, #16a34a 0%, #166534 100%); color: white; border-color: #16a34a; }

.products { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 10px; }
.product-card { background: #f8f9fa; border-radius: 8px; padding: 10px; cursor: pointer; transition: all 0.3s; border: 2px solid transparent; }
.product-card:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(22, 163, 74, 0.3); border-color: #16a34a; }
.product-card:active { transform: translateY(-1px); }
.product-name { font-weight: 600; color: #333; margin-bottom: 5px; font-size: 12px; }
.product-price { color: #16a34a; font-weight: bold; font-size: 14px; }
.out-of-stock { color: #e74c3c; font-size: 12px; font-weight: bold; margin-top: 5px; }

.cart-header { display: flex; align-items: center; gap: 8px; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #e0e0e0; }
.cart-icon { width: 20px; height: 20px; color: #16a34a; }

.cart-items { flex: 1; overflow-y: auto; margin-bottom: 15px; max-height: calc(100vh - 350px); padding-right: 5px; }
.cart-items::-webkit-scrollbar { width: 8px; }
.cart-items::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
.cart-items::-webkit-scrollbar-thumb { background: #16a34a; border-radius: 10px; }
.cart-items::-webkit-scrollbar-thumb:hover { background: #15803d; }

.cart-item { background: #f8f9fa; padding: 8px; border-radius: 8px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center; transition: all 0.2s; }
.cart-item.adding { animation: pulse 0.3s ease-out; }
@keyframes pulse { 0%,100%{transform:scale(1);} 50%{transform:scale(1.05);background:#e8f7ee;} }

.item-info { flex: 1; }
.item-name { font-weight: 600; color: #333; margin-bottom: 3px; font-size: 12px; }
.item-price { color: #16a34a; font-size: 11px; }

.item-controls { display: flex; align-items: center; gap: 6px; }
.qty-btn, .remove-btn {
    width: 26px; height: 26px; border: none; border-radius: 5px; cursor: pointer;
    display: flex; align-items: center; justify-content: center; transition: all 0.2s; font-size: 14px;
}
.qty-btn { background: #16a34a; color: white; }
.qty-btn:hover { background: #15803d; }
.qty-btn:active { transform: scale(0.95); }
.remove-btn { background: #e74c3c; color: white; font-size: 16px; }
.remove-btn:hover { background: #c0392b; }

.quantity-input {
    width: 40px; text-align: center; border: 1px solid #ccc; border-radius: 5px; font-size: 12px;height:30px;
}

.cart-summary { border-top: 2px solid #e0e0e0; padding-top: 10px; }
.summary-row { display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 13px; }
.total-row { font-size: 16px; font-weight: bold; color: #16a34a; margin-top: 8px; }
.checkout-btn {
    width: 100%; padding: 12px; background: linear-gradient(135deg, #16a34a 0%, #166534 100%);
    color: white; border: none; border-radius: 8px; font-size: 15px; font-weight: bold; cursor: pointer; margin-top: 10px;
    transition: all 0.3s;
}
.home-btn {
    width: 100%;
    padding: 12px;
    background: linear-gradient(135deg, #fd9800ff 0%, #fdf900ff 100%);
    color: black;
    border: none;
    border-radius: 8px;
    font-size: 15px;
    font-weight: bold;
    cursor: pointer;
    margin-top: 10px;
    transition: all 0.3s;
}
.side-btn {
    padding: 10px 6px; background: #fff; color: #15803d; border: 2px solid #16a34a;
    border-radius: 8px; font-size: 13px; font-weight: bold; cursor: pointer;
}
.side-btn:hover { background: #e8f7ee; }
.checkout-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(22, 163, 74, 0.4); }
.checkout-btn:active { transform: translateY(0); }

.empty-cart { text-align: center; color: #999; padding: 30px 15px; }
.empty-cart-icon { font-size: 36px; margin-bottom: 8px; }

/* Mobile Responsive */
@media (max-width: 768px) {
    body {
        height: auto;
        overflow-y: auto;
    }

    .container {
        grid-template-columns: 1fr;
        height: auto;
        max-height: none;
        gap: 15px;
        padding: 10px;
    }

    .left-panel {
        max-height: none;
        order: 1;
    }

    .right-panel {
        max-height: none;
        order: 2;
        min-height: 400px;
    }

    .cart-items {
        max-height: 300px;
    }

    .products {
        grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
        gap: 8px;
    }

    .categories {
        grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
        gap: 5px;
    }
}

.custom-modal {
    display: none; /* Hidden by default */
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.5);
    animation: fadeIn 0.3s;
}

.custom-modal-content {
    background-color: #fff;
    margin: 10% auto;
    padding: 20px;
    border-radius: 12px;
    width: 320px;
    max-width: 90%;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    animation: slideIn 0.3s;
}

.custom-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.custom-modal-header h5 {
    margin: 0;
    color: #16a34a;
    font-size: 16px;
}

.custom-modal-close {
    font-size: 22px;
    cursor: pointer;
    color: #999;
    transition: 0.3s;
}

.custom-modal-close:hover {
    color: #e74c3c;
}

.custom-modal-body {
    margin-bottom: 15px;
    font-size: 14px;
    color: #333;
}

.custom-modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.modal-btn {
    padding: 8px 15px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    font-size: 13px;
    font-weight: bold;
}

.cancel-btn {
    background: #ccc;
    color: #333;
}

.cancel-btn:hover { background: #bbb; }

.confirm-btn {
    background: #16a34a;
    color: white;
}

.confirm-btn:hover { background: #15803d; }

.amount-input {
    width: 100%;
    padding: 15px;
    border: 3px solid #e0e0e0;
    border-radius: 8px;
    font-size: 28px;
    font-weight: bold;
    text-align: center;
    color: #16a34a;
    margin-bottom: 10px;
}

.amount-input:focus {
    outline: none;
    border-color: #16a34a;
    box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.1);
}

@keyframes fadeIn { from {opacity: 0;} to {opacity: 1;} }
@keyframes slideIn { from {transform: translateY(-50px);} to {transform: translateY(0);} }
</style>
</head>
<body>
<div class="container">
    <div class="left-panel">
        <h1>Select Items</h1>
        <div class="barcode-scanner">
            <input type="text" id="barcode" class="barcode-input" placeholder="Scan barcode or type item name..." autocomplete="off">
        </div>
        <div class="categories" id="categories">
            <button class="category-btn active" data-category="all">All</button>
            @foreach($category as $cat)
            <button class="category-btn" data-category="{{ $cat->id }}">{{ $cat->category_name }}</button>
            @endforeach
        </div>
        <div class="products" id="products"></div>
    </div>

    <div class="right-panel">
        <div class="cart-header">
            <svg class="cart-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            <h1>Cart</h1>
        </div>
        <div class="cart-items" id="cartItems">
            <div class="empty-cart"><div class="empty-cart-icon">🛒</div><p>Cart is empty</p></div>
        </div>

        <div class="cart-summary">
            <div class="summary-row"><span>Subtotal:</span><span id="subtotal">RM 0.00</span></div>
            <div class="summary-row total-row"><span>Total:</span><span id="total">RM 0.00</span></div>

            <a style="color:red;cursor:pointer" onclick="if(confirm('Are you sure you want to empty shopping cart?')){window.location.href='{{ route('empty_cart') }}'}">Empty Cart</a>
            <button class="checkout-btn" onclick="checkout()">Checkout</button>
            <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:6px;margin-top:10px">
                <button class="side-btn" onclick="window.location.href='{{ route('refund.create') }}'">Refund</button>
                <button class="side-btn" onclick="window.location.href='{{ route('staff_borrow.index') }}'">Advance</button>
            </div>
            <button class="home-btn" onclick="window.location.href='{{ route('home') }}'">Home</button>
        </div>
    </div>
</div>

<script>
/*
 * Everything that can be sold, one tile each: an item in its base unit, and
 * each of its sell options. Categories and items arrive already sorted by
 * their arrangement number.
 */
const sellables = @json($sellables);
const csrf = '{{ csrf_token() }}';

let cart = [];
let selectedCategory = 'all';

const money = v => 'RM ' + Number(v).toFixed(2);
const fmtQty = v => Number(v).toLocaleString(undefined, { maximumFractionDigits: 2 });

function post(url, data) {
    return fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify(data),
    }).then(r => r.json().then(j => { if (!r.ok) throw new Error(j.message || 'Request failed'); return j; }));
}

// ---------- item grid ----------
const grid = document.getElementById('products');
const searchInput = document.getElementById('barcode');

function visibleSellables() {
    const term = searchInput.value.trim().toLowerCase();
    if (term) {
        // typing searches every category by name or barcode
        return sellables.filter(s => s.name.toLowerCase().includes(term) || (s.barcode || '').toLowerCase().includes(term));
    }
    return selectedCategory === 'all' ? sellables : sellables.filter(s => String(s.category_id) === String(selectedCategory));
}

function renderGrid() {
    grid.innerHTML = '';
    const list = visibleSellables();
    if (!list.length) {
        grid.innerHTML = '<div style="grid-column:1/-1;color:#999;padding:20px;text-align:center">No items found</div>';
        return;
    }
    list.forEach(s => {
        const card = document.createElement('div');
        card.className = 'product-card';
        card.onclick = () => addToCart(s);

        const name = document.createElement('div');
        name.className = 'product-name';
        name.innerText = s.name;
        card.appendChild(name);

        if (s.unit_label) {
            const unit = document.createElement('div');
            unit.style.cssText = 'font-size:11px;color:#15803d;font-weight:600;margin-bottom:3px';
            unit.innerText = s.unit_label;
            card.appendChild(unit);
        }

        const price = document.createElement('div');
        price.className = 'product-price';
        price.innerText = money(s.price);
        card.appendChild(price);

        // stock is shown but never blocks a sale; negative means sold before stock was added
        const stock = document.createElement('div');
        stock.className = s.stock <= 0 ? 'out-of-stock' : 'product-stock';
        stock.style.fontSize = '11px';
        stock.innerText = 'Stock: ' + fmtQty(s.stock);
        card.appendChild(stock);

        grid.appendChild(card);
    });
}

document.querySelectorAll('.category-btn').forEach(btn => {
    btn.onclick = () => {
        document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        selectedCategory = btn.dataset.category;
        searchInput.value = '';
        renderGrid();
    };
});

// ---------- search box and barcode scanner ----------
function findByBarcode(code) {
    code = code.trim();
    return code ? sellables.find(s => (s.barcode || '') === code) : null;
}

searchInput.addEventListener('input', renderGrid);
searchInput.addEventListener('keydown', e => {
    if (e.key !== 'Enter') return;
    e.preventDefault();
    const term = searchInput.value.trim();
    const exact = findByBarcode(term);
    const matches = visibleSellables();
    if (exact) {
        addToCart(exact);
    } else if (matches.length === 1) {
        addToCart(matches[0]);
    } else if (!matches.length) {
        flashSearch();
        return;
    } else {
        return; // several matches: let the cashier tap one
    }
    searchInput.value = '';
    renderGrid();
});

// a scanner types very fast and ends with Enter, even when the search box is not focused
let scanBuffer = '';
let lastKey = 0;
document.addEventListener('keydown', e => {
    if (['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) return;
    const now = Date.now();
    if (now - lastKey > 50) scanBuffer = '';
    lastKey = now;
    if (e.key === 'Enter') {
        const hit = findByBarcode(scanBuffer);
        if (hit) addToCart(hit); else if (scanBuffer.length >= 4) flashSearch();
        scanBuffer = '';
        return;
    }
    if (e.key.length === 1) scanBuffer += e.key;
});

function flashSearch() {
    searchInput.style.borderColor = '#e74c3c';
    setTimeout(() => searchInput.style.borderColor = '#e0e0e0', 600);
}

// ---------- cart ----------
function loadCart() {
    return fetch('/cart/load', { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(data => {
            cart = data.map(i => ({ id: i.id, name: i.product_name, price: parseFloat(i.single_price), quantity: parseFloat(i.quantity) }));
            renderCart();
        });
}

function addToCart(s) {
    post('/cart/add', { id: s.product_id, unit_id: s.unit_id })
        .then(loadCart)
        .catch(err => alert(err.message));
}

function setQuantity(lineId, qty) {
    qty = Math.max(0, Math.floor(Number(qty) || 0));
    const req = qty <= 0 ? post('/cart/remove', { line_id: lineId }) : post('/cart/update', { line_id: lineId, quantity: qty });
    req.then(loadCart).catch(err => { alert(err.message); loadCart(); });
}

function renderCart() {
    const box = document.getElementById('cartItems');
    if (!cart.length) {
        box.innerHTML = '<div class="empty-cart"><div class="empty-cart-icon">🛒</div><p>Cart is empty</p></div>';
    } else {
        box.innerHTML = '';
        cart.forEach(item => {
            const row = document.createElement('div');
            row.className = 'cart-item';
            row.innerHTML =
                '<div class="item-info"><div class="item-name"></div><div class="item-price"></div></div>' +
                '<div class="item-controls">' +
                '<button class="qty-btn">−</button>' +
                '<input type="text" class="quantity-input" inputmode="numeric">' +
                '<button class="qty-btn">+</button>' +
                '<button class="remove-btn">×</button></div>';
            row.querySelector('.item-name').innerText = item.name;
            row.querySelector('.item-price').innerText = money(item.price) + ' each';
            const buttons = row.querySelectorAll('.qty-btn');
            const input = row.querySelector('.quantity-input');
            input.value = item.quantity;
            buttons[0].onclick = () => setQuantity(item.id, item.quantity - 1);
            buttons[1].onclick = () => setQuantity(item.id, item.quantity + 1);
            row.querySelector('.remove-btn').onclick = () => setQuantity(item.id, 0);
            input.onchange = () => setQuantity(item.id, input.value);
            input.onkeydown = e => { if (e.key === 'Enter') { e.preventDefault(); input.blur(); } };
            box.appendChild(row);
        });
    }
    const subtotal = cart.reduce((sum, i) => sum + i.price * i.quantity, 0);
    document.getElementById('subtotal').innerText = money(subtotal);
    document.getElementById('total').innerText = money(subtotal);
}

function checkout() {
    if (!cart.length) { alert('Cart empty'); return; }
    window.location.href = '{{ route('checkout') }}';
}

renderGrid();
loadCart();
searchInput.focus();
</script>
@include('layouts.shift-timer')
@include('layouts.counter-notifications')
</body>
</html>
