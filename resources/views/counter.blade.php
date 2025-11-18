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
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    height: 100vh;
    overflow: hidden;
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
.left-panel::-webkit-scrollbar-thumb { background: #667eea; border-radius: 10px; }
.left-panel::-webkit-scrollbar-thumb:hover { background: #5568d3; }

.right-panel { display: flex; flex-direction: column; max-height: 100%; overflow: hidden; }

h1 { color: #667eea; margin-bottom: 15px; font-size: 18px; }

.barcode-scanner { margin-bottom: 15px; position: relative; }
.barcode-input {
    width: 100%; padding: 10px 40px 10px 12px;
    border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px;
    transition: all 0.3s;
}
.barcode-input:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
.barcode-icon {
    position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
    color: #667eea; pointer-events: none;
}

.categories { display: grid; grid-template-columns: repeat(auto-fit, minmax(90px, 1fr)); gap: 6px; margin-bottom: 15px; }
.category-btn { padding: 8px 6px; border: 2px solid #e0e0e0; background: white; border-radius: 8px; cursor: pointer; transition: all 0.3s; font-size: 12px; text-align: center; }
.category-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
.category-btn.active { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-color: #667eea; }

.products { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 10px; }
.product-card { background: #f8f9fa; border-radius: 8px; padding: 10px; cursor: pointer; transition: all 0.3s; border: 2px solid transparent; }
.product-card:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(102, 126, 234, 0.3); border-color: #667eea; }
.product-card:active { transform: translateY(-1px); }
.product-name { font-weight: 600; color: #333; margin-bottom: 5px; font-size: 12px; }
.product-price { color: #667eea; font-weight: bold; font-size: 14px; }
.out-of-stock { color: #e74c3c; font-size: 12px; font-weight: bold; margin-top: 5px; }

.cart-header { display: flex; align-items: center; gap: 8px; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #e0e0e0; }
.cart-icon { width: 20px; height: 20px; color: #667eea; }

.cart-items { flex: 1; overflow-y: auto; margin-bottom: 15px; max-height: calc(100vh - 350px); padding-right: 5px; }
.cart-items::-webkit-scrollbar { width: 8px; }
.cart-items::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
.cart-items::-webkit-scrollbar-thumb { background: #667eea; border-radius: 10px; }
.cart-items::-webkit-scrollbar-thumb:hover { background: #5568d3; }

.cart-item { background: #f8f9fa; padding: 8px; border-radius: 8px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center; transition: all 0.2s; }
.cart-item.adding { animation: pulse 0.3s ease-out; }
@keyframes pulse { 0%,100%{transform:scale(1);} 50%{transform:scale(1.05);background:#e8f4ff;} }

.item-info { flex: 1; }
.item-name { font-weight: 600; color: #333; margin-bottom: 3px; font-size: 12px; }
.item-price { color: #667eea; font-size: 11px; }

.item-controls { display: flex; align-items: center; gap: 6px; }
.qty-btn, .remove-btn {
    width: 26px; height: 26px; border: none; border-radius: 5px; cursor: pointer;
    display: flex; align-items: center; justify-content: center; transition: all 0.2s; font-size: 14px;
}
.qty-btn { background: #667eea; color: white; }
.qty-btn:hover { background: #5568d3; }
.qty-btn:active { transform: scale(0.95); }
.remove-btn { background: #e74c3c; color: white; font-size: 16px; }
.remove-btn:hover { background: #c0392b; }

.quantity-input {
    width: 40px; text-align: center; border: 1px solid #ccc; border-radius: 5px; font-size: 12px;height:30px;
}

.cart-summary { border-top: 2px solid #e0e0e0; padding-top: 10px; }
.summary-row { display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 13px; }
.total-row { font-size: 16px; font-weight: bold; color: #667eea; margin-top: 8px; }
.checkout-btn {
    width: 100%; padding: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white; border: none; border-radius: 8px; font-size: 15px; font-weight: bold; cursor: pointer; margin-top: 10px;
    transition: all 0.3s;
}
.checkout-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4); }
.checkout-btn:active { transform: translateY(0); }

.empty-cart { text-align: center; color: #999; padding: 30px 15px; }
.empty-cart-icon { font-size: 36px; margin-bottom: 8px; }
</style>
</head>
<body>
<div class="container">
    <div class="left-panel">
        <h1>Select Products</h1>
        <div class="barcode-scanner">
            <input type="text" id="barcodeInput" class="barcode-input" placeholder="Scan barcode here..." autocomplete="off">
        </div>
        <div class="categories" id="categories">
            @foreach($category as $index=> $cat)
            <button class="category-btn @if($index==0) active @endif" data-category="{{ Str::slug($cat->category_name, '_') }}">
                {{$cat->category_name??''}}
            </button>
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
            <div class="empty-cart">
                <div class="empty-cart-icon">🛒</div>
                <p>Cart is empty</p>
            </div>
        </div>

        <div class="cart-summary">
            <div class="summary-row">
                <span>Subtotal:</span>
                <span id="subtotal">$0.00</span>
            </div>
            <div class="summary-row total-row">
                <span>Total:</span>
                <span id="total">$0.00</span>
            </div>
            <button class="checkout-btn" onclick="checkout()">Checkout</button>
        </div>
    </div>
</div>

<script>
const products = {
    @foreach($category as $cat)
    "{{ Str::slug($cat->category_name, '_') }}": [
        @foreach($products->where('category_id', $cat->id) as $p)
        {
            id: "{{ $p->id }}",
            name: "{{ $p->product_name }}",
            price: {{ number_format($p->selling_price, 2, '.', '') }},
            stock: {{ $p->stock_quantity }}
        }@if(!$loop->last), @endif
        @endforeach
    ]@if(!$loop->last), @endif
    @endforeach
};

let cart = [];
let selectedCategory = "{{ Str::slug($category->first()->category_name, '_') }}";
let isLoading = false;

// Load cart from server
function loadCartFromServer() {
    if (isLoading) return;
    isLoading = true;
    fetch('/cart/load')
    .then(res => res.json())
    .then(data => {
        cart = data.map(item => ({
            id: item.product_id,
            name: item.product_name,
            price: parseFloat(item.single_price),
            quantity: item.quantity,
            stock: item.stock,
        }));
        updateCart();
        isLoading = false;
    }).catch(err => { console.error(err); isLoading=false; });
}

// Barcode scanner
const barcodeInput = document.getElementById('barcodeInput');
let barcodeTimeout;
barcodeInput.addEventListener('input', e => {
    clearTimeout(barcodeTimeout);
    barcodeTimeout = setTimeout(() => {
        const barcode = e.target.value.trim();
        if(barcode){ processBarcode(barcode); e.target.value=''; }
    },100);
});
barcodeInput.addEventListener('keypress', e => {
    if(e.key==='Enter'){ clearTimeout(barcodeTimeout); const barcode=e.target.value.trim(); if(barcode){ processBarcode(barcode); e.target.value=''; } }
});
function processBarcode(barcode) {
    let found = null;
    for(let cat in products){
        found = products[cat].find(p=>p.id==barcode);
        if(found) break;
    }
    if(found) addToCart(found);
    else { barcodeInput.style.borderColor='#e74c3c'; setTimeout(()=>barcodeInput.style.borderColor='#e0e0e0',500); alert('Product not found!'); }
}

// Display products
function displayProducts(category){
    const container = document.getElementById('products');
    container.innerHTML='';
    products[category].forEach(p=>{
        const card=document.createElement('div');
        card.className='product-card';
        if(p.stock===0){ card.style.opacity=0.5; card.style.pointerEvents='none'; }
        else card.onclick=()=>addToCart(p);
        card.innerHTML=`<div class="product-name">${p.name}</div>
                        <div class="product-price">RM ${p.price.toFixed(2)}</div>
                        ${p.stock===0?'<div class="out-of-stock">Out of Stock</div>':''}`;
        container.appendChild(card);
    });
}

// Add to cart
function addToCart(product){
    const existing = cart.find(i=>i.id==product.id);
    const currentQty = existing? existing.quantity :0;
    if(currentQty+1>product.stock){ alert(`Cannot add more. Only ${product.stock} in stock.`); return; }

    if(existing) existing.quantity++;
    else cart.push({id:product.id,name:product.name,price:product.price,quantity:1,stock:product.stock});

    updateCart();

    setTimeout(()=>{
        const elem=document.querySelector(`[data-item-id="${product.id}"]`);
        if(elem){ elem.classList.add('adding'); setTimeout(()=>elem.classList.remove('adding'),300);}
    },0);

    fetch('/cart/add',{
        method:'POST',
        headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
        body:JSON.stringify({id:product.id,price:product.price,name:product.name})
    }).then(res=>res.json()).catch(err=>{
        console.error(err);
        if(existing){ existing.quantity--; if(existing.quantity<=0) cart=cart.filter(i=>i.id!=product.id);}
        else cart=cart.filter(i=>i.id!=product.id);
        updateCart(); alert('Failed to add item. Please try again.');
    });
}

// Update quantity buttons
function updateQuantity(id, change){
    const item=cart.find(i=>i.id==id);
    if(!item) return;
    const newQty=item.quantity+change;
    if(newQty>item.stock){ alert(`Cannot increase beyond stock (${item.stock})`); return; }
    if(newQty<=0){ removeFromCart(id); return; }
    item.quantity=newQty; updateCart();
    fetch('/cart/update',{ method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'}, body:JSON.stringify({id:id,quantity:newQty}) })
    .then(res=>res.json()).catch(err=>{ console.error(err); item.quantity-=change; updateCart(); alert('Failed to update quantity.'); });
}

// Input events for quantity (Enter or blur)
function onQuantityInputEvent(input){
    const id = input.dataset.id;
    const item = cart.find(i => i.id==id);
    if(!item) return;

    let numericValue = input.value.replace(/[^\d]/g, '');
    if(numericValue==='') numericValue='1';
    let newQty = parseInt(numericValue);
    if(newQty>item.stock){ alert(`Cannot set quantity beyond stock (${item.stock})`); newQty=item.stock; }
    item.quantity = newQty;
    updateCart();

    fetch('/cart/update', {
        method:'POST',
        headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
        body: JSON.stringify({id:id, quantity:newQty})
    }).then(res=>res.json()).catch(err=>{ console.error(err); alert('Failed to update quantity.'); });
}

// Bind events to inputs
function bindQuantityInputs(){
    document.querySelectorAll('.quantity-input').forEach(input=>{
        input.onkeypress = e => { if(e.key==='Enter'){ e.preventDefault(); onQuantityInputEvent(input); } };
        input.onblur = e => onQuantityInputEvent(input);
    });
}

// Remove
function removeFromCart(id){
    const original=[...cart]; cart=cart.filter(i=>i.id!=id); updateCart();
    fetch('/cart/remove',{ method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'}, body:JSON.stringify({id:id}) })
    .then(res=>res.json()).catch(err=>{ console.error(err); cart=original; updateCart(); alert('Failed to remove item.'); });
}

// Update cart UI
function updateCart(){
    const container=document.getElementById('cartItems');
    if(cart.length===0){
        container.innerHTML=`<div class="empty-cart"><div class="empty-cart-icon">🛒</div><p>Cart is empty</p></div>`;
    } else {
        container.innerHTML=cart.map(item=>`
            <div class="cart-item" data-item-id="${item.id}">
                <div class="item-info">
                    <div class="item-name">${item.name}</div>
                    <div class="item-price">RM ${item.price.toFixed(2)} each</div>
                </div>
                <div class="item-controls">
                    <button class="qty-btn" onclick="updateQuantity('${item.id}',-1)">−</button>
                    <input type="text" class="quantity-input" value="${item.quantity}" data-id="${item.id}">
                    <button class="qty-btn" onclick="updateQuantity('${item.id}',1)">+</button>
                    <button class="remove-btn" onclick="removeFromCart('${item.id}')">×</button>
                </div>
            </div>
        `).join('');
    }
    const subtotal=cart.reduce((acc,i)=>acc+i.price*i.quantity,0);
    document.getElementById('subtotal').innerText=`RM ${subtotal.toFixed(2)}`;
    document.getElementById('total').innerText=`RM ${subtotal.toFixed(2)}`;
    bindQuantityInputs();
}

// Checkout
function checkout(){ if(cart.length===0){ alert('Cart empty'); return; } window.location.href='/checkout'; }

// Categories click
document.querySelectorAll('.category-btn').forEach(btn=>{
    btn.onclick=()=>{
        document.querySelectorAll('.category-btn').forEach(b=>b.classList.remove('active'));
        btn.classList.add('active'); selectedCategory=btn.dataset.category; displayProducts(selectedCategory);
    }
});

// Init
displayProducts(selectedCategory);
loadCartFromServer();
</script>
</body>
</html>
