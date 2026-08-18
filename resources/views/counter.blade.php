<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>MejaPOS</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#0d0f14;--surface:#161a22;--card:#1e2330;--card-hover:#252b3b;
  --border:#2a3045;--accent:#f5a623;--accent2:#e8623a;
  --green:#3ecf8e;--red:#e05252;--purple:#a78bfa;
  --text:#e8ecf4;--muted:#6b7794;--tag:#2a3045;
  --radius:12px;--shadow:0 6px 24px rgba(0,0,0,0.45);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
html,body{height:100%;overflow:hidden;}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);display:flex;flex-direction:column;}
header{display:flex;align-items:center;justify-content:space-between;padding:11px 18px;background:var(--surface);border-bottom:1px solid var(--border);flex-shrink:0;gap:10px;}
.brand{font-family:'Syne',sans-serif;font-size:1.1rem;font-weight:800;letter-spacing:-0.5px;}
.brand span{color:var(--accent);}
.legend{display:flex;gap:12px;align-items:center;}
.legend-item{display:flex;align-items:center;gap:5px;font-size:0.68rem;color:var(--muted);font-weight:500;}
.dot{width:7px;height:7px;border-radius:50%;}
.dot.available{background:var(--green);}
.dot.occupied{background:var(--accent2);}
.dot.dabao{background:var(--purple);}
.time-badge{font-size:0.75rem;color:var(--muted);background:var(--tag);padding:4px 11px;border-radius:20px;font-weight:500;}
.app-body{display:flex;flex:1;overflow:hidden;}
.left-panel{width:420px;flex-shrink:0;display:flex;flex-direction:column;border-right:1px solid var(--border);overflow:hidden;}
.left-scroll{flex:1;overflow-y:auto;padding:14px 14px 0;}
.left-scroll::-webkit-scrollbar{width:4px;}
.left-scroll::-webkit-scrollbar-thumb{background:var(--border);border-radius:4px;}
.section-label{font-family:'Syne',sans-serif;font-size:0.62rem;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:var(--muted);margin-bottom:10px;padding-bottom:7px;border-bottom:1px solid var(--border);}
.floor-map{display:flex;flex-direction:column;gap:8px;margin-bottom:16px;}
.table-card-empty{flex:1;min-width:0; padding: 20px 10px 20px;}
.table-row{display:flex;gap:8px;}
.table-row .table-card{flex:1;min-width:0;}
.table-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:20px 10px 20px;cursor:pointer;transition:transform .15s,box-shadow .15s,background .15s,border-color .15s;position:relative;overflow:hidden;user-select:none;}
.table-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;border-radius:var(--radius) var(--radius) 0 0;background:var(--green);}
.table-card.occupied::before{background:var(--accent2);}
.table-card.selected{border-color:var(--accent);background:var(--card-hover);}
.table-card.selected.occupied{border-color:var(--accent2);}
.table-card:hover{transform:translateY(-2px);box-shadow:var(--shadow);background:var(--card-hover);}
.table-card.available:hover{border-color:var(--green);}
.table-card.occupied:hover{border-color:var(--accent2);}
.t-top{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:5px;}
.t-num{font-family:'Syne',sans-serif;font-size:1.05rem;font-weight:800;line-height:1;}
.s-pill{font-size:0.55rem;font-weight:700;padding:2px 6px;border-radius:20px;text-transform:uppercase;letter-spacing:0.4px;}
.s-pill.available{background:rgba(62,207,142,0.15);color:var(--green);}
.s-pill.occupied{background:rgba(232,98,58,0.15);color:var(--accent2);}
.t-total{font-size:0.62rem;color:var(--accent);font-weight:600;margin-top:2px;}
.dabao-list{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px;}
.dabao-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:10px;cursor:pointer;transition:transform .15s,background .15s,border-color .15s;position:relative;overflow:hidden;user-select:none;}
.dabao-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;border-radius:var(--radius) var(--radius) 0 0;background:var(--purple);}
.dabao-card:hover{transform:translateY(-2px);border-color:var(--purple);background:var(--card-hover);}
.dabao-card.selected{border-color:var(--purple);background:var(--card-hover);}
.d-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;}
.d-num{font-family:'Syne',sans-serif;font-size:1rem;font-weight:800;}
.d-tag{font-size:0.55rem;font-weight:700;padding:2px 7px;border-radius:20px;background:rgba(167,139,250,0.15);color:var(--purple);text-transform:uppercase;letter-spacing:0.4px;}
.d-remove{background:transparent;border:none;color:var(--muted);cursor:pointer;font-size:0.72rem;padding:2px 5px;border-radius:5px;transition:background .15s,color .15s;}
.d-remove:hover{background:var(--red);color:#fff;}
.d-meta{font-size:0.65rem;color:var(--muted);}
.d-meta strong{color:var(--text);font-weight:500;}
.d-preview{font-size:0.62rem;color:var(--muted);margin-top:2px;}
.d-total{font-size:0.7rem;font-weight:700;color:var(--accent);margin-top:3px;}
.empty-dabao{font-size:0.75rem;color:var(--muted);text-align:center;padding:12px 0;opacity:.6;}
.add-dabao-btn{display:flex;align-items:center;justify-content:center;gap:6px;padding:10px;background:transparent;border:1px dashed var(--border);border-radius:var(--radius);color:var(--muted);font-family:'DM Sans',sans-serif;font-size:0.78rem;font-weight:600;cursor:pointer;transition:all .2s;width:100%;margin-bottom:14px;}
.add-dabao-btn:hover{border-color:var(--purple);color:var(--purple);background:rgba(167,139,250,0.05);}
.right-panel{flex:1;display:flex;flex-direction:column;overflow:hidden;min-width:0;}
.empty-state{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;color:var(--muted);opacity:.5;}
.empty-state .icon{font-size:3rem;}
.empty-state p{font-size:0.85rem;}
.ctx-bar{display:flex;align-items:center;justify-content:space-between;padding:10px 16px;background:var(--surface);border-bottom:1px solid var(--border);flex-shrink:0;}
.ctx-title{font-family:'Syne',sans-serif;font-size:1rem;font-weight:700;}
.ctx-sub{font-size:0.7rem;color:var(--muted);margin-top:1px;}
.ctx-actions{display:flex;gap:8px;}
.ctx-btn{padding:6px 13px;border-radius:8px;border:1px solid var(--border);background:transparent;color:var(--muted);font-family:'DM Sans',sans-serif;font-size:0.75rem;font-weight:600;cursor:pointer;transition:all .2s;}
.ctx-btn:hover{border-color:var(--text);color:var(--text);}
.dabao-name-row{padding:8px 16px;border-bottom:1px solid var(--border);background:var(--surface);flex-shrink:0;}
.dabao-name-input{width:100%;background:var(--card);border:1px solid var(--border);border-radius:9px;padding:7px 12px;color:var(--text);font-family:'DM Sans',sans-serif;font-size:0.82rem;outline:none;transition:border-color .2s;}
.dabao-name-input:focus{border-color:var(--purple);}
.dabao-name-input::placeholder{color:var(--muted);}
.menu-tabs{display:flex;border-bottom:1px solid var(--border);padding:0 16px;background:var(--surface);flex-shrink:0;overflow-x:auto;scrollbar-width:none;}
.menu-tabs::-webkit-scrollbar{display:none;}
.menu-tab{padding:9px 14px;font-size:0.75rem;font-weight:600;color:var(--muted);cursor:pointer;white-space:nowrap;border-bottom:2px solid transparent;transition:color .2s,border-color .2s;}
.menu-tab.active{color:var(--accent);border-bottom-color:var(--accent);}
.menu-tab.active.dp{color:var(--purple);border-bottom-color:var(--purple);}
.search-bar-row{padding:9px 12px;background:var(--bg);border-bottom:1px solid var(--border);flex-shrink:0;}
.search-wrap{position:relative;display:flex;align-items:center;}
.search-icon{position:absolute;left:10px;font-size:0.82rem;opacity:.45;pointer-events:none;}
.search-input{width:100%;background:var(--card);border:1px solid var(--border);border-radius:9px;padding:7px 12px 7px 30px;color:var(--text);font-family:'DM Sans',sans-serif;font-size:0.82rem;outline:none;transition:border-color .2s;}
.search-input:focus{border-color:var(--accent);}
.search-input.dp:focus{border-color:var(--purple);}
.search-input::placeholder{color:var(--muted);}
.search-clear{position:absolute;right:9px;background:none;border:none;color:var(--muted);cursor:pointer;font-size:0.85rem;line-height:1;padding:2px 4px;border-radius:4px;display:none;}
.search-clear.visible{display:block;}
.search-clear:hover{color:var(--text);}
.no-results{padding:24px 12px;text-align:center;color:var(--muted);font-size:0.8rem;opacity:.6;width:100%;}
.order-body{display:flex;flex:1;overflow:hidden;}
.menu-area{flex:1;overflow-y:auto;padding:12px;display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:9px;align-content:start;}
.menu-area::-webkit-scrollbar{width:4px;}
.menu-area::-webkit-scrollbar-thumb{background:var(--border);border-radius:4px;}
.menu-item{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:10px;cursor:pointer;transition:all .15s;display:flex;flex-direction:column;gap:3px;}
.menu-item:hover{border-color:var(--accent);background:var(--card-hover);transform:scale(1.03);}
.menu-item.dp:hover{border-color:var(--purple);}
.menu-item.out-of-stock{opacity:.4;cursor:not-allowed;}
.menu-item.out-of-stock:hover{transform:none;border-color:var(--border);}
.menu-item.has-addon::after{content:'+ Add-ons';font-size:0.55rem;color:var(--accent);font-weight:700;letter-spacing:0.3px;opacity:.8;}
.item-emoji{font-size:1.4rem;}
.item-name{font-size:0.75rem;font-weight:600;color:var(--text);line-height:1.2;}
.item-price{font-size:0.73rem;color:var(--accent);font-weight:700;}
.item-cat-tag{font-size:0.58rem;color:var(--muted);text-transform:uppercase;letter-spacing:0.5px;margin-top:1px;}
.item-stock{font-size:0.58rem;color:var(--muted);margin-top:1px;}
.item-stock.low{color:var(--red);}
.cart-area{width:320px;flex-shrink:0;border-left:1px solid var(--border);display:flex;flex-direction:column;background:var(--surface);}
.cart-header{padding:10px 14px;border-bottom:1px solid var(--border);font-family:'Syne',sans-serif;font-size:0.72rem;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--muted);}
.cart-items{flex:1;overflow-y:auto;padding:8px 10px;}
.cart-items::-webkit-scrollbar{width:3px;}
.cart-items::-webkit-scrollbar-thumb{background:var(--border);}
.cart-empty{text-align:center;color:var(--muted);font-size:0.75rem;padding:20px 0;opacity:.5;}
.cart-row{display:flex;flex-direction:column;gap:4px;padding:7px 0;border-bottom:1px solid var(--border);}
.cart-row:last-child{border-bottom:none;}
.cart-item-name{font-size:0.73rem;color:var(--text);font-weight:500;}
.cart-ctrl{display:flex;align-items:center;justify-content:space-between;}
.qty-btn{background:var(--tag);border:1px solid var(--border);color:var(--text);width:20px;height:20px;border-radius:5px;cursor:pointer;font-size:0.8rem;display:flex;align-items:center;justify-content:center;transition:background .15s;flex-shrink:0;}
.qty-btn:hover{background:var(--border);}
.qty-num{font-weight:700;font-size:0.8rem;min-width:18px;text-align:center;}
.cart-item-price{font-size:0.72rem;color:var(--accent);font-weight:700;}
/* Add-on tags in cart */
.cart-addon-tags{display:flex;flex-wrap:wrap;gap:3px;margin-top:3px;}
.cart-addon-tag{font-size:0.6rem;background:rgba(245,166,35,0.15);color:var(--accent);border-radius:4px;padding:1px 6px;font-weight:600;}
.cart-footer{border-top:1px solid var(--border);padding:10px 14px;}
.cart-total-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;}
.cart-total-label{font-family:'Syne',sans-serif;font-size:0.8rem;font-weight:700;}
.cart-total-val{font-family:'Syne',sans-serif;font-size:1.05rem;font-weight:800;color:var(--accent);}
.checkout-btn{width:100%;padding:11px;border-radius:10px;border:none;background:var(--accent);color:#000;font-family:'DM Sans',sans-serif;font-size:0.85rem;font-weight:800;cursor:pointer;transition:all .2s;}
.checkout-btn:hover{background:#ffc04d;transform:translateY(-1px);box-shadow:0 4px 16px rgba(245,166,35,0.35);}
.checkout-btn:disabled{opacity:.35;cursor:not-allowed;transform:none;box-shadow:none;}
.checkout-btn.dp{background:var(--purple);color:#fff;}
.checkout-btn.dp:hover{background:#c4b5fd;box-shadow:0 4px 16px rgba(167,139,250,0.35);}
.clear-btn{width:100%;padding:7px;border-radius:8px;border:1px solid var(--border);background:transparent;color:var(--muted);font-family:'DM Sans',sans-serif;font-size:0.75rem;font-weight:600;cursor:pointer;margin-top:6px;transition:all .2s;}
.clear-btn:hover{border-color:var(--red);color:var(--red);}

/* ─── ADDON MODAL ─── */
.addon-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.75);z-index:350;backdrop-filter:blur(5px);align-items:center;justify-content:center;}
.addon-overlay.open{display:flex;}
.addon-modal{background:var(--surface);border:1px solid var(--border);border-radius:20px;width:100%;max-width:400px;max-height:85vh;overflow-y:auto;padding:24px;animation:popIn .22s ease;box-shadow:0 20px 60px rgba(0,0,0,0.6);}
.addon-modal::-webkit-scrollbar{width:4px;}
.addon-modal::-webkit-scrollbar-thumb{background:var(--border);border-radius:4px;}
.addon-modal-title{font-family:'Syne',sans-serif;font-size:0.62rem;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:var(--muted);margin-bottom:6px;}
.addon-product-name{font-family:'Syne',sans-serif;font-size:1.05rem;font-weight:800;margin-bottom:2px;}
.addon-product-price{font-size:0.78rem;color:var(--accent);font-weight:700;margin-bottom:16px;}
.addon-section-label{font-size:0.65rem;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:var(--muted);margin-bottom:10px;padding-bottom:6px;border-bottom:1px solid var(--border);}
.addon-list{display:flex;flex-direction:column;gap:6px;margin-bottom:18px;}
.addon-item{display:flex;align-items:center;gap:10px;padding:10px 12px;background:var(--card);border:1px solid var(--border);border-radius:10px;cursor:pointer;transition:all .15s;user-select:none;}
.addon-item:hover{border-color:var(--accent);background:var(--card-hover);}
.addon-item.selected{border-color:var(--accent);background:rgba(245,166,35,0.08);}
.addon-checkbox{width:16px;height:16px;border:2px solid var(--border);border-radius:4px;flex-shrink:0;display:flex;align-items:center;justify-content:center;transition:all .15s;font-size:0.65rem;}
.addon-item.selected .addon-checkbox{background:var(--accent);border-color:var(--accent);color:#000;}
.addon-item-name{flex:1;font-size:0.8rem;font-weight:500;color:var(--text);}
.addon-item-price{font-size:0.78rem;font-weight:700;color:var(--accent);}
.addon-subtotal{background:var(--card);border-radius:10px;padding:10px 14px;display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;}
.addon-subtotal-label{font-size:0.78rem;color:var(--muted);}
.addon-subtotal-val{font-family:'Syne',sans-serif;font-size:1rem;font-weight:800;color:var(--accent);}
.addon-actions{display:flex;gap:10px;}
.addon-cancel{flex:1;padding:11px;border-radius:10px;border:1px solid var(--border);background:transparent;color:var(--muted);font-family:'DM Sans',sans-serif;font-size:0.82rem;font-weight:700;cursor:pointer;transition:all .2s;}
.addon-cancel:hover{border-color:var(--text);color:var(--text);}
.addon-confirm{flex:2;padding:11px;border-radius:10px;border:none;background:var(--accent);color:#000;font-family:'DM Sans',sans-serif;font-size:0.82rem;font-weight:800;cursor:pointer;transition:all .2s;}
.addon-confirm:hover{background:#ffc04d;transform:translateY(-1px);box-shadow:0 4px 16px rgba(245,166,35,0.3);}
.addon-confirm.dp{background:var(--purple);color:#fff;}
.addon-confirm.dp:hover{background:#c4b5fd;box-shadow:0 4px 16px rgba(167,139,250,0.3);}
@media(max-width:700px){
  .addon-modal{max-width:100%;margin:0;border-radius:20px 20px 0 0;position:fixed;bottom:0;left:0;right:0;max-height:88vh;}
  .addon-overlay.open{align-items:flex-end;}
}
/* ─── END ADDON MODAL ─── */

.pay-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.75);z-index:300;backdrop-filter:blur(5px);align-items:center;justify-content:center;}
.pay-overlay.open{display:flex;}
.pay-modal{background:var(--surface);border:1px solid var(--border);border-radius:20px;width:100%;max-width:380px;max-height:90vh;overflow-y:auto;padding:28px;animation:popIn .25s ease;box-shadow:0 20px 60px rgba(0,0,0,0.6);}
.pay-modal::-webkit-scrollbar{width:4px;}
.pay-modal::-webkit-scrollbar-thumb{background:var(--border);border-radius:4px;}
@keyframes popIn{from{transform:scale(.92);opacity:0;}to{transform:scale(1);opacity:1;}}
.pay-title{font-family:'Syne',sans-serif;font-size:1.2rem;font-weight:800;margin-bottom:4px;}
.pay-sub{font-size:0.75rem;color:var(--muted);margin-bottom:20px;}
.pay-summary{background:var(--card);border-radius:10px;padding:12px 14px;margin-bottom:18px;}
.pay-line{display:flex;justify-content:space-between;font-size:0.78rem;color:var(--muted);padding:3px 0;}
.pay-line-addon{font-size:0.7rem;color:var(--muted);padding:1px 0 1px 12px;opacity:.8;}
.pay-total-line{display:flex;justify-content:space-between;align-items:center;padding-top:10px;margin-top:8px;border-top:1px solid var(--border);}
.pay-total-label{font-family:'Syne',sans-serif;font-size:0.85rem;font-weight:700;}
.pay-total-val{font-family:'Syne',sans-serif;font-size:1.3rem;font-weight:800;color:var(--accent);}
.pay-input-label{font-size:0.75rem;color:var(--muted);font-weight:600;margin-bottom:6px;}
.pay-input{width:100%;background:var(--card);border:1px solid var(--border);border-radius:10px;padding:12px 14px;color:var(--text);font-family:'Syne',sans-serif;font-size:1.3rem;font-weight:700;outline:none;transition:border-color .2s;margin-bottom:12px;}
.pay-input:focus{border-color:var(--accent);}
.pay-input::placeholder{color:var(--border);}
.quick-amounts{display:flex;gap:8px;margin-bottom:18px;flex-wrap:wrap;}
.quick-btn{padding:7px 13px;border-radius:8px;border:1px solid var(--border);background:var(--card);color:var(--text);font-family:'DM Sans',sans-serif;font-size:0.78rem;font-weight:600;cursor:pointer;transition:all .2s;}
.quick-btn:hover{border-color:var(--accent);color:var(--accent);}
.change-box{background:rgba(62,207,142,0.1);border:1px solid rgba(62,207,142,0.3);border-radius:10px;padding:12px 14px;display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;}
.change-label{font-size:0.78rem;color:var(--green);font-weight:600;}
.change-val{font-family:'Syne',sans-serif;font-size:1.2rem;font-weight:800;color:var(--green);}
.change-box.insufficient{background:rgba(224,82,82,0.1);border-color:rgba(224,82,82,0.3);}
.change-box.insufficient .change-label,.change-box.insufficient .change-val{color:var(--red);}
.pay-actions{display:flex;gap:10px;}
.pay-cancel{flex:1;padding:12px;border-radius:10px;border:1px solid var(--border);background:transparent;color:var(--muted);font-family:'DM Sans',sans-serif;font-size:0.85rem;font-weight:700;cursor:pointer;transition:all .2s;}
.pay-cancel:hover{border-color:var(--text);color:var(--text);}
.pay-confirm{flex:2;padding:12px;border-radius:10px;border:none;background:var(--green);color:#000;font-family:'DM Sans',sans-serif;font-size:0.85rem;font-weight:800;cursor:pointer;transition:all .2s;}
.pay-confirm:hover{background:#5fdfaa;transform:translateY(-1px);box-shadow:0 4px 16px rgba(62,207,142,0.35);}
.pay-confirm:disabled{opacity:.3;cursor:not-allowed;transform:none;box-shadow:none;}
.pay-method-label{font-size:0.75rem;color:var(--muted);font-weight:600;margin-bottom:10px;}
.pay-method-btns{display:flex;gap:10px;margin-bottom:18px;}
.pay-method-btn{flex:1;padding:14px 10px;border-radius:12px;border:2px solid var(--border);background:var(--card);color:var(--text);font-family:'DM Sans',sans-serif;font-size:0.82rem;font-weight:700;cursor:pointer;transition:all .2s;display:flex;flex-direction:column;align-items:center;gap:6px;}
.pay-method-btn .pm-icon{font-size:1.6rem;}
.pay-method-btn:hover{border-color:var(--muted);background:var(--card-hover);}
.pay-method-btn.selected-cash{border-color:var(--accent);background:rgba(245,166,35,0.1);color:var(--accent);}
.pay-method-btn.selected-qr{border-color:var(--green);background:rgba(62,207,142,0.1);color:var(--green);}
.pay-detail-section{display:none;}
.pay-detail-section.visible{display:block;}
.qr-panel{text-align:center;margin-bottom:18px;}
.qr-box{background:#fff;border-radius:14px;padding:16px;display:inline-block;margin-bottom:12px;}
.qr-hint{font-size:0.75rem;color:var(--muted);margin-bottom:4px;}
.qr-amount{font-family:'Syne',sans-serif;font-size:1.4rem;font-weight:800;color:var(--green);margin-bottom:14px;}
.qr-done-btn{width:100%;padding:12px;border-radius:10px;border:none;background:var(--green);color:#000;font-family:'DM Sans',sans-serif;font-size:0.85rem;font-weight:800;cursor:pointer;transition:all .2s;}
.qr-done-btn:hover{background:#5fdfaa;transform:translateY(-1px);box-shadow:0 4px 16px rgba(62,207,142,0.35);}
.loading-tab{color:var(--muted);font-size:0.75rem;padding:9px 14px;}
.toast{position:fixed;bottom:20px;left:50%;transform:translateX(-50%) translateY(60px);padding:9px 20px;border-radius:30px;font-weight:700;font-size:0.8rem;z-index:999;transition:transform .3s ease;white-space:nowrap;background:var(--green);color:#000;}
.toast.dp{background:var(--purple);color:#fff;}
.toast.err{background:var(--red);color:#fff;}
.toast.show{transform:translateX(-50%) translateY(0);}
@media(max-width:700px){
  html,body{overflow:auto;}
  .app-body{flex-direction:column;overflow:auto;}
  .left-panel{width:100%;border-right:none;border-bottom:1px solid var(--border);overflow:visible;}
  .left-scroll{overflow:visible;padding-bottom:0;}
  .right-panel{min-height:100dvh;display:flex;flex-direction:column;}
  .order-body{flex-direction:column;flex:1;overflow:hidden;display:flex;}
  .menu-area{height:50vh;overflow-y:auto;grid-template-columns:repeat(auto-fill,minmax(100px,1fr));padding:10px;flex-shrink:0;}
  .cart-area{width:100%;border-left:none;border-top:1px solid var(--border);display:flex;flex-direction:column;flex-shrink:0;max-height:55vh;}
  .cart-items{height:18vh;overflow-y:auto;}
  .cart-footer{flex-shrink:0;background:var(--surface);border-top:1px solid var(--border);}
  .checkout-btn{padding:14px;font-size:0.95rem;}
  #activeOrder{height:auto !important;min-height:100dvh;}
  .menu-tabs{overflow-x:auto;-webkit-overflow-scrolling:touch;}
  .legend{display:none;}
  .pay-modal{max-width:100%;margin:0;border-radius:20px 20px 0 0;position:fixed;bottom:0;left:0;right:0;max-height:90vh;}
  .pay-overlay.open{align-items:flex-end;}
  .confirm-modal{max-width:100%;margin:0 12px;}
}
.confirm-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.75);z-index:400;backdrop-filter:blur(5px);align-items:center;justify-content:center;}
.confirm-overlay.open{display:flex;}
.confirm-modal{background:var(--surface);border:1px solid var(--border);border-radius:20px;width:100%;max-width:320px;padding:28px 24px;animation:popIn .2s ease;box-shadow:0 20px 60px rgba(0,0,0,0.6);text-align:center;}
.confirm-icon{font-size:2.5rem;margin-bottom:10px;}
.confirm-title{font-family:'Syne',sans-serif;font-size:1.1rem;font-weight:800;margin-bottom:6px;}
.confirm-sub{font-size:0.8rem;color:var(--muted);margin-bottom:16px;}
.confirm-details{background:var(--card);border-radius:10px;padding:12px 14px;margin-bottom:20px;text-align:left;}
.confirm-detail-row{display:flex;justify-content:space-between;align-items:center;font-size:0.78rem;padding:4px 0;border-bottom:1px solid var(--border);}
.confirm-detail-row:last-child{border-bottom:none;padding-top:8px;margin-top:4px;}
.confirm-detail-row.total{font-family:'Syne',sans-serif;font-weight:700;font-size:0.9rem;}
.confirm-detail-row.total span:last-child{color:var(--accent);}
.confirm-detail-row .label{color:var(--muted);}
.confirm-actions{display:flex;gap:10px;}
.confirm-cancel{flex:1;padding:11px;border-radius:10px;border:1px solid var(--border);background:transparent;color:var(--muted);font-family:'DM Sans',sans-serif;font-size:0.85rem;font-weight:700;cursor:pointer;transition:all .2s;}
.confirm-cancel:hover{border-color:var(--text);color:var(--text);}
.confirm-ok{flex:2;padding:11px;border-radius:10px;border:none;background:var(--green);color:#000;font-family:'DM Sans',sans-serif;font-size:0.85rem;font-weight:800;cursor:pointer;transition:all .2s;}
.confirm-ok:hover{background:#5fdfaa;transform:translateY(-1px);box-shadow:0 4px 16px rgba(62,207,142,0.35);}
.confirm-ok-noreceipt{flex:2;padding:11px;border-radius:10px;border:none;background:#4d8fff;color:#fff;font-family:'DM Sans',sans-serif;font-size:0.85rem;font-weight:800;cursor:pointer;transition:all .2s;}
.confirm-ok-noreceipt:hover{background:#6ba0ff;transform:translateY(-1px);box-shadow:0 4px 16px rgba(77,143,255,0.35);}
.print-btn{width:100%;padding:10px;border-radius:10px;border:1px solid var(--accent);background:transparent;color:var(--accent);font-family:'DM Sans',sans-serif;font-size:0.82rem;font-weight:800;cursor:pointer;transition:all .2s;margin-top:6px;margin-bottom:4px;}
.print-btn:hover{background:rgba(245,166,35,0.12);}
.print-btn:disabled{opacity:.35;cursor:not-allowed;}

.pay-actions-stacked,
.confirm-actions-stacked {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.pay-actions-stacked button,
.confirm-actions-stacked button {
  width: 100%;
}
</style>
</head>
<body>

<header>
  <div class="brand"><a href="{{ route('home') }}">Meja<span>POS</span></a></div>
  <div class="legend">
    <div class="legend-item"><div class="dot available"></div>Available</div>
    <div class="legend-item"><div class="dot occupied"></div>Occupied</div>
    <div class="legend-item"><div class="dot dabao"></div>Dabao</div>
  </div>
  <div class="time-badge" id="clock">--:--</div>
</header>

<div class="app-body">
  <div class="left-panel">
    <div class="left-scroll">
      <div class="section-label">Main Hall</div>
      <div class="floor-map" id="floorMap">
        <div style="color:var(--muted);font-size:0.75rem;padding:8px 0;opacity:.6;">Loading tables…</div>
      </div>
      <div class="section-label">🥡 Dabao (Takeaway)</div>
      <div class="dabao-list" id="dabaoList"></div>
      <button class="add-dabao-btn" onclick="newDabao()">＋ New Dabao Order</button>
    </div>
  </div>

  <div class="right-panel" id="rightPanel">
    <div class="empty-state" id="emptyState">
      <div class="icon">👆</div>
      <p>Select a table or dabao to start ordering</p>
    </div>

    <div id="activeOrder" style="display:none;flex-direction:column;height:100%;">
      <div class="ctx-bar">
        <div>
          <div class="ctx-title" id="ctxTitle"></div>
          <div class="ctx-sub"   id="ctxSub"></div>
        </div>
        <div class="ctx-actions">
          <button class="ctx-btn" onclick="deselect()">✕ Close</button>
        </div>
      </div>

      <div class="dabao-name-row" id="dabaoNameRow" style="display:none">
        <input class="dabao-name-input" id="dabaoNameInput" type="text"
               placeholder="Customer name / phone (optional)"
               oninput="debounceSaveDabaoName()">
      </div>

      <div class="menu-tabs" id="menuTabsEl">
        <div class="loading-tab">Loading menu…</div>
      </div>

      <div class="search-bar-row">
        <div class="search-wrap">
          <span class="search-icon">🔎</span>
          <input class="search-input" id="searchInput" type="text"
                 placeholder="Search menu items…" oninput="onSearch(this.value)">
          <button class="search-clear" id="searchClear" onclick="clearSearch()">✕</button>
        </div>
      </div>

      <div class="order-body">
        <div class="menu-area" id="menuArea"></div>
        <div class="cart-area">
          <div class="cart-header">Order</div>
          <div class="cart-items" id="cartItems">
            <div class="cart-empty">No items yet</div>
          </div>
          <div class="cart-footer">
            <div class="cart-total-row">
              <span class="cart-total-label">Total</span>
              <span class="cart-total-val" id="cartTotal">RM 0.00</span>
            </div>
            <button class="print-btn" onclick="printOrder()" id="printBtn" disabled>🖨 Print Order</button>
            <button class="checkout-btn" id="checkoutBtn" onclick="openPayment()" disabled>Checkout →</button>
            <button class="clear-btn" onclick="clearOrder()">Clear order</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ════ ADDON MODAL ════ -->
<div class="addon-overlay" id="addonOverlay" onclick="closeAddonOnBg(event)">
  <div class="addon-modal">
    <div class="addon-modal-title">Customise Order</div>
    <div class="addon-product-name" id="addonProductName"></div>
    <div class="addon-product-price" id="addonProductPrice"></div>
    <div class="addon-section-label">Add-ons</div>
    <div class="addon-list" id="addonList"></div>
    <div class="addon-subtotal">
      <span class="addon-subtotal-label">Item total</span>
      <span class="addon-subtotal-val" id="addonSubtotal">RM 0.00</span>
    </div>
    <div class="addon-actions">
      <button class="addon-cancel" onclick="closeAddonModal()">Cancel</button>
      <button class="addon-confirm" id="addonConfirmBtn" onclick="confirmAddon()">Add to Order</button>
    </div>
  </div>
</div>
<!-- ════ END ADDON MODAL ════ -->

<!-- PAYMENT MODAL -->
<div class="pay-overlay" id="payOverlay" onclick="closePayOnBg(event)">
  <div class="pay-modal">
    <div class="pay-title">💳 Payment</div>
    <div class="pay-sub" id="paySub"></div>

    <div class="pay-summary" id="paySummaryLines"></div>

    <div class="pay-method-label">Select Payment Method</div>
    <div class="pay-method-btns" id="payMethodBtns"></div>

    <div class="pay-detail-section" id="cashSection">
      <div class="pay-input-label">Amount Received (RM)</div>
      <input class="pay-input" id="payInput" type="number" inputmode="decimal"
             placeholder="0.00" oninput="calcChange()">
      <div class="quick-amounts" id="quickAmounts"></div>
      <div class="change-box" id="changeBox" style="display:none">
        <span class="change-label" id="changeLabel">Change</span>
        <span class="change-val"   id="changeVal">RM 0.00</span>
      </div>
      <div class="pay-actions pay-actions-stacked">
        <button class="pay-confirm" id="payConfirmBtn" onclick="confirmPayment()" disabled>Confirm Payment</button>
        <button class="pay-cancel" onclick="closePayment()">Cancel</button>
      </div>
    </div>

    <div class="pay-detail-section" id="qrSection">
      <div class="qr-panel">
        <div class="qr-box" style="min-width:152px;min-height:152px;display:flex;align-items:center;justify-content:center;"></div>
        <div class="qr-hint">Scan to pay</div>
        <div class="qr-amount" id="qrAmount">RM 0.00</div>
      </div>
      <div class="pay-actions pay-actions-stacked">
        <button class="qr-done-btn" onclick="confirmPayment()">✓ Payment Received</button>
        <button class="pay-cancel" onclick="closePayment()">Cancel</button>
      </div>
    </div>
  </div>
</div>

<!-- CONFIRM PAYMENT MODAL -->
<div class="confirm-overlay" id="confirmOverlay">
  <div class="confirm-modal">
    <div class="confirm-icon">🧾</div>
    <div class="confirm-title">Confirm Payment?</div>
    <div class="confirm-sub" id="confirmSub"></div>
    <div class="confirm-details" id="confirmDetails"></div>
    <div class="confirm-actions confirm-actions-stacked">
      <button class="confirm-ok" id="confirmOkBtn">Yes, with Receipt</button>
      <button class="confirm-ok-noreceipt" id="confirmOkNoReceiptBtn">Yes, No Receipt</button>
      <button class="confirm-cancel" onclick="closeConfirm()">Cancel</button>
    </div>
  </div>
</div>

<div class="toast" id="toast"></div>

<script>
// ════════════════════════════════════════════════
// CONFIG
// ════════════════════════════════════════════════
const API = '/pos';

// ════════════════════════════════════════════════
// STATE
// ════════════════════════════════════════════════
let tableRows    = [];
let tables       = [];
let dabaoSlots   = [];
let categories   = [];
let allProducts  = [];
let paymentMethods    = [];
let selectedMethodObj = null;

let currentMode  = null;
let currentTable = null;
let currentDabao = null;
let currentCatId = null;

// order = { cartId: { cartId, productId, name, price, qty, total_price, addons: [{id,name,price}] } }
let order = {};

let searchQuery       = '';
let payTotal          = 0;
let selectedPayMethod = null;
let dabaoNameTimer    = null;

// ── Addon modal state ──
let addonPendingProduct = null;  // product being customised
let addonSelected       = {};    // { addonId: true }

// ════════════════════════════════════════════════
// CLOCK
// ════════════════════════════════════════════════
function updateClock() {
    document.getElementById('clock').textContent =
        new Date().toLocaleTimeString('en-MY', {hour:'2-digit', minute:'2-digit'});
}
setInterval(updateClock, 1000);
updateClock();

// ════════════════════════════════════════════════
// API HELPER
// ════════════════════════════════════════════════
function apiFetch(url, options = {}) {
    return fetch(API + url, {
        ...options,
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            'Accept': 'application/json',
            ...(options.headers ?? {}),
        },
    })
    .then(async res => {
        if (!res.ok) {
            let msg = `Error ${res.status}`;
            try {
                const body = await res.json();
                msg = body.message || body.error || msg;
            } catch (_) {}
            throw new Error(msg);
        }
        return res.json();
    })
    .catch(err => {
        // Only a genuine network failure lands here as a TypeError
        // (fetch itself couldn't reach the server at all)
        const msg = err instanceof TypeError
            ? '⚠ No connection to server'
            : `⚠ ${err.message}`;
        showToast(msg, 'err');
        throw err;
    });
}

// ════════════════════════════════════════════════
// BOOT
// ════════════════════════════════════════════════
async function boot() {
    await Promise.all([loadTables(), loadDabao(), loadMenu(), loadPaymentMethods()]);
}

async function loadPaymentMethods() {
    paymentMethods = await apiFetch('/payment-methods');
}

// ════════════════════════════════════════════════
// TABLES
// ════════════════════════════════════════════════
async function loadTables() {
    const data = await apiFetch('/tables');
    tables = data.map(t => ({
        id:     t.id,
        label:  t.table_name ?? `T${t.id}`,
        status: parseFloat(t.total) > 0 ? 'occupied' : 'available',
        total:  parseFloat(t.total),
    }));
    renderTables();
}

const FLOOR_LAYOUT = [
    [8, 9, 10],
    [5, 6, 7],
    [null, 3, 4],
    [null, 1, 2],
];

function chunkBy(arr, sizes) {
    const rows = [];
    let i = 0;
    sizes.forEach(n => { rows.push(arr.slice(i, i + n)); i += n; });
    if (i < arr.length) rows.push(arr.slice(i));
    return rows;
}

function renderTables() {
    const map = document.getElementById('floorMap');
    map.innerHTML = '';
    if (!tables.length) {
        map.innerHTML = '<div style="color:var(--muted);font-size:0.75rem;padding:8px 0;opacity:.6;">No tables found.</div>';
        return;
    }
    FLOOR_LAYOUT.forEach(row => {
        const rowEl = document.createElement('div');
        rowEl.className = 'table-row';
        row.forEach(num => {
            if (num === null) {
                const empty = document.createElement('div');
                empty.className = 'table-card-empty';
                rowEl.appendChild(empty);
                return;
            }
            const t = tables.find(tb => tb.id === num);
            if (!t) return;
            const isSelected = currentMode === 'table' && currentTable?.id === t.id;
            const card = document.createElement('div');
            card.className = `table-card ${t.status}${isSelected ? ' selected' : ''}`;
            card.innerHTML = `
                <div class="t-top">
                    <div class="t-num">${t.label}</div>
                    <div class="s-pill ${t.status}">${t.status === 'available' ? 'free' : 'occ'}</div>
                </div>
                ${t.total > 0 ? `<div class="t-total">RM ${t.total.toFixed(2)}</div>` : ''}`;
            card.onclick = () => selectTable(t.id);
            rowEl.appendChild(card);
        });
        map.appendChild(rowEl);
    });
}

// ════════════════════════════════════════════════
// DABAO
// ════════════════════════════════════════════════
async function loadDabao() {
    const data = await apiFetch('/dabao');
    dabaoSlots = data.map(d => ({
        id:    d.id,
        name:  d.table_name ?? '',
        total: parseFloat(d.total),
    }));
    renderDabao();
}

function renderDabao() {
    const list = document.getElementById('dabaoList');
    list.innerHTML = '';
    if (!dabaoSlots.length) {
        list.innerHTML = '<div class="empty-dabao">No dabao orders</div>';
        return;
    }
    dabaoSlots.forEach(slot => {
        const isSelected = currentMode === 'dabao' && currentDabao?.id === slot.id;
        const liveTotal = (isSelected && Object.keys(order).length)
            ? Object.values(order).reduce((a, i) => a + i.total_price, 0)
            : slot.total;
        const card = document.createElement('div');
        card.className = `dabao-card${isSelected ? ' selected' : ''}`;
        card.innerHTML = `
            <div class="d-top">
                <div>
                    <div class="d-num">D${slot.id}</div>
                    <span class="d-tag">Takeaway</span>
                </div>
                <button class="d-remove" onclick="removeDabao(${slot.id}, event)">✕ Done</button>
            </div>
            ${slot.name
                ? `<div class="d-meta">👤 <strong>${slot.name}</strong></div>`
                : '<div class="d-meta" style="opacity:.4;font-style:italic">No name</div>'}
            ${liveTotal > 0 ? `<div class="d-total">RM ${liveTotal.toFixed(2)}</div>` : ''}`;
        card.onclick = () => selectDabao(slot.id);
        list.appendChild(card);
    });
}

async function newDabao() {
    const data = await apiFetch('/dabao', {
        method: 'POST',
        body: JSON.stringify({ table_name: null, total: 0 }),
    });
    dabaoSlots.push({ id: data.id, name: '', total: 0 });
    renderDabao();
    selectDabao(data.id);
}

async function removeDabao(id, e) {
    e.stopPropagation();
    await apiFetch(`/dabao/${id}/pay`, { method: 'PUT' });
    if (currentDabao?.id === id) deselect();
    dabaoSlots = dabaoSlots.filter(s => s.id !== id);
    renderDabao();
    showToast(`Dabao D${id} closed`, 'dp');
}

function debounceSaveDabaoName() {
    clearTimeout(dabaoNameTimer);
    dabaoNameTimer = setTimeout(() => saveDabaoName(), 800);
}

async function saveDabaoName() {
    if (!currentDabao) return;
    const name = document.getElementById('dabaoNameInput').value.trim();
    currentDabao.name = name;
    const slot = dabaoSlots.find(s => s.id === currentDabao.id);
    if (slot) slot.name = name;
    await apiFetch(`/dabao/${currentDabao.id}`, {
        method: 'PUT',
        body: JSON.stringify({ table_name: name || null }),
    });
    renderDabao();
}

// ════════════════════════════════════════════════
// MENU
// ════════════════════════════════════════════════
async function loadMenu() {
    const data = await apiFetch('/menu');
    categories  = data;
    allProducts = data.flatMap(cat =>
        cat.products.map(p => ({...p, category_name: cat.category_name}))
    );
}

// ════════════════════════════════════════════════
// SELECT TABLE
// ════════════════════════════════════════════════
async function selectTable(tableId) {
    currentMode  = 'table';
    currentTable = tables.find(t => t.id === tableId);
    currentDabao = null;
    order        = {};
    clearSearchSilent();

    showActiveOrder();
    document.getElementById('ctxTitle').textContent = currentTable.label;
    document.getElementById('ctxSub').textContent   =
        currentTable.status === 'occupied'
            ? `Occupied · RM ${currentTable.total.toFixed(2)}`
            : 'Available';
    document.getElementById('dabaoNameRow').style.display   = 'none';
    document.getElementById('checkoutBtn').className        = 'checkout-btn';
    document.getElementById('searchInput').className        = 'search-input';

    updateTabStyle(false);
    buildMenuTabs();
    renderTables();
    renderDabao();

    await loadCart(tableId);
    pushState({ page: 'order' });
}

// ════════════════════════════════════════════════
// SELECT DABAO
// ════════════════════════════════════════════════
async function selectDabao(slotId) {
    currentMode  = 'dabao';
    currentDabao = dabaoSlots.find(s => s.id === slotId);
    currentTable = null;
    order        = {};
    clearSearchSilent();

    showActiveOrder();
    document.getElementById('ctxTitle').textContent         = `Dabao D${currentDabao.id}`;
    document.getElementById('ctxSub').textContent           = 'Takeaway Order';
    document.getElementById('dabaoNameRow').style.display   = 'block';
    document.getElementById('dabaoNameInput').value         = currentDabao.name || '';
    document.getElementById('checkoutBtn').className        = 'checkout-btn dp';
    document.getElementById('searchInput').className        = 'search-input dp';

    updateTabStyle(true);
    buildMenuTabs();
    renderTables();
    renderDabao();

    await loadCart(slotId);
    pushState({ page: 'order' });
}

function showActiveOrder() {
    document.getElementById('emptyState').style.display    = 'none';
    document.getElementById('activeOrder').style.display   = 'flex';
}

function deselect() {
    currentMode = null; currentTable = null; currentDabao = null; order = {};
    clearSearchSilent();
    document.getElementById('emptyState').style.display  = 'flex';
    document.getElementById('activeOrder').style.display = 'none';
    renderTables();
    renderDabao();
}

// ════════════════════════════════════════════════
// CART — load from DB
// ════════════════════════════════════════════════
async function loadCart(tableId) {
    const data = await apiFetch(`/cart/${tableId}`);
    order = {};
    data.forEach(item => {
        // addons may be stored as JSON array on the cart item
        const addons = item.addons
            ? (typeof item.addons === 'string' ? JSON.parse(item.addons) : item.addons)
            : [];
        order[item.id] = {
            cartId:      item.id,
            productId:   item.product_id,
            name:        item.product?.product_name ?? '—',
            price:       parseFloat(item.single_price),
            qty:         item.quantity,
            total_price: parseFloat(item.total_price),
            addons:      addons,
        };
    });
    renderCart();
}

// ════════════════════════════════════════════════
// ADDON MODAL
// ════════════════════════════════════════════════

// Called when a menu item is clicked
async function handleMenuItemClick(product) {
    if (product.has_stock && product.stock_quantity !== null && product.stock_quantity <= 0) {
        showToast('Out of stock', 'err');
        return;
    }

    // Addons are already loaded in the product object from getMenu() via with(['addons'])
    const addons = Array.isArray(product.addons) ? product.addons.filter(a => a.is_active != 0) : [];

    if (addons.length > 0) {
        openAddonModal(product, addons);
    } else {
        await addItem(product, []);
    }
}

function openAddonModal(product, addons) {
    addonPendingProduct = product;
    addonSelected       = {};

    const isDabao = currentMode === 'dabao';

    document.getElementById('addonProductName').textContent  = product.product_name;
    document.getElementById('addonProductPrice').textContent = `Base price: RM ${parseFloat(product.selling_price).toFixed(2)}`;

    // Style confirm button for dabao
    const confirmBtn = document.getElementById('addonConfirmBtn');
    confirmBtn.className = isDabao ? 'addon-confirm dp' : 'addon-confirm';

    // Build addon list
    const listEl = document.getElementById('addonList');
    listEl.innerHTML = '';
    addons.forEach(addon => {
        const item = document.createElement('div');
        item.className   = 'addon-item';
        item.dataset.id  = addon.id;
        item.innerHTML   = `
            <div class="addon-checkbox"></div>
            <span class="addon-item-name">${addon.addon_name ?? addon.name}</span>
            <span class="addon-item-price">+ RM ${parseFloat(addon.addon_price ?? addon.price).toFixed(2)}</span>`;
        item.onclick = () => toggleAddon(item, addon);
        listEl.appendChild(item);
    });

    updateAddonSubtotal(product);
    document.getElementById('addonOverlay').classList.add('open');
}

function toggleAddon(itemEl, addon) {
    const id = addon.id;
    if (addonSelected[id]) {
        delete addonSelected[id];
        itemEl.classList.remove('selected');
        itemEl.querySelector('.addon-checkbox').textContent = '';
    } else {
        addonSelected[id] = addon;
        itemEl.classList.add('selected');
        itemEl.querySelector('.addon-checkbox').textContent = '✓';
    }
    updateAddonSubtotal(addonPendingProduct);
}

function updateAddonSubtotal(product) {
    const base      = parseFloat(product.selling_price);
    const addonSum  = Object.values(addonSelected).reduce((a, ao) => a + parseFloat(ao.addon_price ?? ao.price), 0);
    document.getElementById('addonSubtotal').textContent = `RM ${(base + addonSum).toFixed(2)}`;
}

async function confirmAddon() {
    const selectedAddons = Object.values(addonSelected);
    await addItem(addonPendingProduct, selectedAddons);
    closeAddonModal();
}

function closeAddonModal() {
    document.getElementById('addonOverlay').classList.remove('open');
    addonPendingProduct = null;
    addonSelected       = {};
}

function closeAddonOnBg(e) {
    if (e.target === document.getElementById('addonOverlay')) closeAddonModal();
}

// ════════════════════════════════════════════════
// ADD ITEM TO CART
// ════════════════════════════════════════════════
async function addItem(product, addons = []) {
    const tableId   = currentMode === 'table' ? currentTable.id : currentDabao.id;
    const addonSum  = addons.reduce((a, ao) => a + parseFloat(ao.addon_price ?? ao.price), 0);
    const unitPrice = parseFloat(product.selling_price) + addonSum;

    const data = await apiFetch('/cart', {
        method: 'POST',
        body: JSON.stringify({
            table_id:    tableId,
            product_id:  product.id,
            quantity:    1,
            addons:      addons.map(ao => ({
                id:    ao.id,
                name:  ao.addon_name ?? ao.name,
                price: parseFloat(ao.addon_price ?? ao.price),
            })),
            unit_price: unitPrice,  // send computed unit price including addons
        }),
    });

    // Determine addon label for display
    const addonsMapped = addons.map(ao => ({
        id:    ao.id,
        name:  ao.addon_name ?? ao.name,
        price: parseFloat(ao.addon_price ?? ao.price),
    }));
    // ALWAYS write using the server's cart row ID as the key
    const qty = data.quantity ?? 1;
    order[data.id] = {
        cartId:      data.id,
        productId:   data.product_id,
        name:        product.product_name,
        price:       unitPrice,
        qty:         qty,
        total_price: unitPrice * qty,
        addons:      addonsMapped,
    };
    syncLocalTotal(tableId);
    renderCart();
    renderDabao();
}

// ════════════════════════════════════════════════
// CHANGE QTY IN CART
// ════════════════════════════════════════════════
async function changeQty(cartId, delta) {
    const item = order[cartId];
    if (!item) return;

    const newQty  = item.qty + delta;
    const tableId = currentMode === 'table' ? currentTable.id : currentDabao.id;

    if (newQty <= 0) {
        await apiFetch(`/cart/${cartId}`, { method: 'DELETE' });
        delete order[cartId];
    } else {
        await apiFetch(`/cart/${cartId}`, {
            method: 'PUT',
            body: JSON.stringify({ quantity: newQty }),
        });
        item.qty         = newQty;
        item.total_price = newQty * item.price;
    }

    syncLocalTotal(tableId);
    renderCart();
    renderDabao();
}

function syncLocalTotal(tableId) {
    const total = Object.values(order).reduce((a, i) => a + i.total_price, 0);

    if (currentMode === 'table') {
        const tbl = tables.find(t => t.id === tableId);
        if (tbl) { tbl.total = total; tbl.status = total > 0 ? 'occupied' : 'available'; }
    } else {
        const slot = dabaoSlots.find(s => s.id === tableId);
        if (slot) slot.total = total;
    }

    renderTables();
}

async function clearOrder() {
    const tableId = currentMode === 'table' ? currentTable?.id : currentDabao?.id;
    if (!tableId) return;

    const deletes = Object.keys(order).map(cartId =>
        apiFetch(`/cart/${cartId}`, { method: 'DELETE' })
    );
    await Promise.all(deletes);

    order = {};
    syncLocalTotal(tableId);
    renderCart();
    renderDabao();
}

// ════════════════════════════════════════════════
// RENDER CART
// ════════════════════════════════════════════════
function renderCart() {
    const cartEl   = document.getElementById('cartItems');
    const totalEl  = document.getElementById('cartTotal');
    const btn      = document.getElementById('checkoutBtn');
    const printBtn = document.getElementById('printBtn');
    const items    = Object.values(order);

    if (!items.length) {
        cartEl.innerHTML    = '<div class="cart-empty">No items yet</div>';
        totalEl.textContent = 'RM 0.00';
        btn.disabled        = true;
        printBtn.disabled   = true;
        return;
    }

    cartEl.innerHTML = '';
    let total = 0;

    items.forEach(it => {
        total += it.total_price;
        const row = document.createElement('div');
        row.className = 'cart-row';

        // Build addon tags HTML
        let addonTagsHtml = '';
        if (it.addons && it.addons.length > 0) {
            const tags = it.addons.map(ao =>
                `<span class="cart-addon-tag">+ ${ao.name} (RM ${parseFloat(ao.price).toFixed(2)})</span>`
            ).join('');
            addonTagsHtml = `<div class="cart-addon-tags">${tags}</div>`;
        }

        row.innerHTML = `
            <div class="cart-item-name">${it.name}</div>
            ${addonTagsHtml}
            <div class="cart-ctrl">
                <button class="qty-btn" onclick="changeQty(${it.cartId}, -1)">−</button>
                <span class="qty-num">${it.qty}</span>
                <button class="qty-btn" onclick="changeQty(${it.cartId}, 1)">+</button>
                <span class="cart-item-price">RM ${it.total_price.toFixed(2)}</span>
            </div>`;
        cartEl.appendChild(row);
    });

    totalEl.textContent = `RM ${total.toFixed(2)}`;
    btn.disabled        = false;
    printBtn.disabled   = false;
}

// ════════════════════════════════════════════════
// MENU TABS
// ════════════════════════════════════════════════
function buildMenuTabs() {
    const tabsEl  = document.getElementById('menuTabsEl');
    const isDabao = currentMode === 'dabao';
    tabsEl.innerHTML = '';

    if (!categories.length) return;

    currentCatId = categories[0].id;

    categories.forEach((cat, index) => {
        const tab = document.createElement('div');
        tab.className = `menu-tab${isDabao ? ' dp' : ''}${index === 0 ? ' active' : ''}`;
        tab.textContent = cat.category_name;
        tab.onclick = () => switchTab(tab, cat.id);
        tabsEl.appendChild(tab);
    });

    renderMenu();
}

function switchTab(el, catId) {
    document.querySelectorAll('.menu-tab').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
    currentCatId = catId;
    clearSearchSilent();
    renderMenu();
}

function updateTabStyle(isDabao) {
    document.querySelectorAll('.menu-tab').forEach(t => {
        isDabao ? t.classList.add('dp') : t.classList.remove('dp');
    });
}

// ════════════════════════════════════════════════
// SEARCH
// ════════════════════════════════════════════════
function onSearch(val) {
    searchQuery = val.trim().toLowerCase();
    document.getElementById('searchClear').classList.toggle('visible', searchQuery.length > 0);
    renderMenu();
}
function clearSearch() {
    document.getElementById('searchInput').value = '';
    document.getElementById('searchClear').classList.remove('visible');
    searchQuery = '';
    renderMenu();
}
function clearSearchSilent() {
    searchQuery = '';
    const si = document.getElementById('searchInput');
    const sc = document.getElementById('searchClear');
    if (si) si.value = '';
    if (sc) sc.classList.remove('visible');
}

// ════════════════════════════════════════════════
// RENDER MENU
// ════════════════════════════════════════════════
function renderMenu() {
    const area    = document.getElementById('menuArea');
    const isDabao = currentMode === 'dabao';
    area.innerHTML = '';

    let products;
    if (searchQuery) {
        products = allProducts.filter(p =>
            p.product_name.toLowerCase().includes(searchQuery) ||
            p.category_name.toLowerCase().includes(searchQuery)
        );
    } else {
        const cat = categories.find(c => c.id === currentCatId);
        products  = cat ? cat.products : [];
    }

    if (!products.length) {
        area.innerHTML = `<div class="no-results">No items found${searchQuery ? ` for "<strong>${searchQuery}</strong>"` : ''}</div>`;
        return;
    }

    products.forEach(product => {
        const stockEnforced = product.has_stock == 1;
        const outOfStock    = stockEnforced && product.stock_quantity !== null && product.stock_quantity <= 0;
        // addons already loaded via getMenu() → with(["addons"])
        const hasAddons = Array.isArray(product.addons) && product.addons.some(a => a.is_active != 0);

        const div = document.createElement('div');
        div.className = `menu-item${isDabao ? ' dp' : ''}${outOfStock ? ' out-of-stock' : ''}${hasAddons ? ' has-addon' : ''}`;

        const stockLabel = stockEnforced && product.stock_quantity !== null
            ? `<div class="item-stock${product.stock_quantity <= 5 ? ' low' : ''}">
                 Stock: ${product.stock_quantity}
               </div>`
            : '';

        const showCat = !!searchQuery;

        div.innerHTML = `
            <div class="item-name">${highlightMatch(product.product_name, searchQuery)}</div>
            <div class="item-price">RM ${parseFloat(product.selling_price).toFixed(2)}</div>
            ${showCat ? `<div class="item-cat-tag">${product.category_name}</div>` : ''}
            ${stockLabel}
            ${outOfStock ? `<div class="item-stock low">Out of stock</div>` : ''}`;

        if (!outOfStock) div.onclick = () => handleMenuItemClick(product);
        area.appendChild(div);
    });
}

function highlightMatch(text, query) {
    if (!query) return text;
    const idx = text.toLowerCase().indexOf(query);
    if (idx === -1) return text;
    return text.slice(0, idx)
        + `<mark style="background:rgba(245,166,35,0.3);color:var(--accent);border-radius:2px;">${text.slice(idx, idx + query.length)}</mark>`
        + text.slice(idx + query.length);
}

// ════════════════════════════════════════════════
// PAYMENT
// ════════════════════════════════════════════════
function openPayment() {
    const items = Object.values(order);
    if (!items.length) return;

    payTotal          = items.reduce((a, i) => a + i.total_price, 0);
    selectedMethodObj = null;

    const label = currentMode === 'table'
        ? currentTable.label
        : `Dabao D${currentDabao.id}${currentDabao.name ? ' · ' + currentDabao.name : ''}`;
    document.getElementById('paySub').textContent = label;

    // Build summary lines (include addons)
    const linesEl = document.getElementById('paySummaryLines');
    linesEl.innerHTML = '';
    items.forEach(it => {
        const line = document.createElement('div');
        line.className = 'pay-line';
        line.innerHTML = `<span>${it.name} × ${it.qty}</span><span>RM ${it.total_price.toFixed(2)}</span>`;
        linesEl.appendChild(line);
        // Show addon breakdown under each item
        if (it.addons && it.addons.length > 0) {
            it.addons.forEach(ao => {
                const addonLine = document.createElement('div');
                addonLine.className = 'pay-line-addon';
                addonLine.innerHTML = `<span>↳ + ${ao.name}</span><span> (RM ${parseFloat(ao.price).toFixed(2)})</span>`;
                linesEl.appendChild(addonLine);
            });
        }
    });
    const totalLine = document.createElement('div');
    totalLine.className = 'pay-total-line';
    totalLine.innerHTML = `<span class="pay-total-label">Total</span><span class="pay-total-val">RM ${payTotal.toFixed(2)}</span>`;
    linesEl.appendChild(totalLine);

    // Build payment method buttons
    const btnsEl = document.getElementById('payMethodBtns');
    btnsEl.innerHTML = '';
    paymentMethods.forEach(pm => {
        const btn = document.createElement('button');
        btn.className  = 'pay-method-btn';
        btn.dataset.id = pm.id;

        const icon = pm.image_full_url
            ? `<img src="${pm.image_full_url}"
                    onerror="this.style.display='none'"
                    style="width:36px;height:36px;object-fit:contain;border-radius:6px;">`
            : `<span class="pm-icon"
                    style="width:36px;height:36px;display:flex;align-items:center;justify-content:center;
                        font-size:1.4rem;background:var(--tag);border-radius:6px;">
                ${pm.payment_method_name.charAt(0)}
            </span>`;

        btn.innerHTML = `${icon}<span>${pm.payment_method_name}</span>`;
        btn.onclick   = () => selectPayMethod(pm);
        btnsEl.appendChild(btn);
    });

    document.getElementById('cashSection').classList.remove('visible');
    document.getElementById('qrSection').classList.remove('visible');
    document.getElementById('payInput').value          = '';
    document.getElementById('changeBox').style.display = 'none';
    document.getElementById('payConfirmBtn').disabled  = true;

    document.getElementById('payOverlay').classList.add('open');
    pushState({ page: 'payment' });
}

function selectPayMethod(pm) {
    selectedMethodObj = pm;

    document.querySelectorAll('.pay-method-btn').forEach(b => {
        b.classList.remove('selected-cash', 'selected-qr');
        if (parseInt(b.dataset.id) === pm.id) {
            b.classList.add(pm.payment_method_name.toLowerCase() === 'cash' ? 'selected-cash' : 'selected-qr');
        }
    });

    const isCash = pm.payment_method_name.toLowerCase() === 'cash';

    if (isCash) {
        document.getElementById('cashSection').classList.add('visible');
        document.getElementById('qrSection').classList.remove('visible');

        const quickEl = document.getElementById('quickAmounts');
        quickEl.innerHTML = '';
        const rounded = Math.ceil(payTotal / 5) * 5;
        [rounded, rounded + 5, rounded + 10, rounded + 20].forEach(amt => {
            const btn       = document.createElement('button');
            btn.className   = 'quick-btn';
            btn.textContent = `RM ${amt.toFixed(0)}`;
            btn.onclick     = () => { document.getElementById('payInput').value = amt.toFixed(2); calcChange(); };
            quickEl.appendChild(btn);
        });
    } else {
        document.getElementById('qrSection').classList.add('visible');
        document.getElementById('cashSection').classList.remove('visible');
        document.getElementById('qrAmount').textContent = `RM ${payTotal.toFixed(2)}`;

        const qrBox = document.querySelector('.qr-box');
        if (pm.image_full_url) {
            qrBox.innerHTML = `
                <img src="${pm.image_full_url}"
                     onerror="this.src=''; this.alt='No image';"
                     style="width:240px;object-fit:contain;border-radius:8px;display:block;">`;
        } else {
            qrBox.innerHTML = `
                <div style="width:240px;display:flex;align-items:center;justify-content:center;
                            font-family:'Syne',sans-serif;font-weight:700;font-size:0.85rem;
                            color:#333;text-align:center;padding:8px;">
                    ${pm.payment_method_name}
                </div>`;
        }
    }
}

function calcChange() {
    const received    = parseFloat(document.getElementById('payInput').value) || 0;
    const changeBox   = document.getElementById('changeBox');
    const changeVal   = document.getElementById('changeVal');
    const changeLabel = document.getElementById('changeLabel');
    const confirmBtn  = document.getElementById('payConfirmBtn');
    if (received <= 0) { changeBox.style.display = 'none'; confirmBtn.disabled = true; return; }
    const change = received - payTotal;
    changeBox.style.display = 'flex';
    if (change >= 0) {
        changeBox.className     = 'change-box';
        changeLabel.textContent = 'Change';
        changeVal.textContent   = `RM ${change.toFixed(2)}`;
        confirmBtn.disabled     = false;
    } else {
        changeBox.className     = 'change-box insufficient';
        changeLabel.textContent = 'Short by';
        changeVal.textContent   = `RM ${Math.abs(change).toFixed(2)}`;
        confirmBtn.disabled     = true;
    }
}

function closePayment() { document.getElementById('payOverlay').classList.remove('open'); }
function closePayOnBg(e) { if (e.target === document.getElementById('payOverlay')) closePayment(); }

function confirmPayment() {
    if (!selectedMethodObj) { showToast('Please select a payment method', 'err'); return; }

    const isCash   = selectedMethodObj.payment_method_name.toLowerCase() === 'cash';
    const received = isCash
        ? parseFloat(document.getElementById('payInput').value) || payTotal
        : payTotal;
    const change   = Math.max(0, received - payTotal);

    const payload = {
        payment_method_id: selectedMethodObj.id,
        payment_method:    selectedMethodObj.payment_method_name,
        amount_received:   received,
        change:            change,
        tax_amount:        0,
        final_total:       payTotal,
    };

    const label = currentMode === 'table'
        ? currentTable.label
        : `Dabao D${currentDabao.id}${currentDabao.name ? ' · ' + currentDabao.name : ''}`;

    document.getElementById('confirmSub').textContent = label;
    document.getElementById('confirmDetails').innerHTML = `
        <div class="confirm-detail-row">
            <span class="label">Payment method</span>
            <span>${selectedMethodObj.payment_method_name}</span>
        </div>
        ${isCash ? `
        <div class="confirm-detail-row">
            <span class="label">Amount received</span>
            <span>RM ${received.toFixed(2)}</span>
        </div>
        <div class="confirm-detail-row">
            <span class="label">Change</span>
            <span>RM ${change.toFixed(2)}</span>
        </div>` : ''}
        <div class="confirm-detail-row total">
            <span>Total</span>
            <span>RM ${payTotal.toFixed(2)}</span>
        </div>`;

    document.getElementById('confirmOkBtn').onclick = () => {
        processPayment({ ...payload, print_receipt: true });
    };

    document.getElementById('confirmOkNoReceiptBtn').onclick = () => {
        processPayment({ ...payload, print_receipt: false });
    };

    document.getElementById('confirmOverlay').classList.add('open');
}

function printOrder() {
    const items = Object.values(order);
    if (!items.length) return;

    const label = currentMode === 'table'
        ? currentTable.label
        : `Dabao D${currentDabao.id}`;

    const now = new Date().toLocaleString('en-MY');

    let receipt = `
[C]<font size='big'><b>LAO YANG KOPITIAM</b></font>

[C]<font size='big'><b>${label}</b></font>

[C]${now}

[C]================================
`;

    items.forEach(item => {
        receipt += `\n[L]<font size='big'><b>${item.qty} x ${item.name}</b></font>\n`;
        if (item.addons && item.addons.length > 0) {
            item.addons.forEach(ao => {
                receipt += `[L]  + ${ao.name} (RM ${parseFloat(ao.price).toFixed(2)})\n`;
            });
        }
    });

    receipt += `
[C]================================

[C]Please Prepare Order

\n\n\n
`;

    if (window.AndroidPrinter) {
        AndroidPrinter.printBluetooth(receipt);
        showToast('🖨 Printing order...', '');
    } else {
        alert('Printer only works inside Android APK');
    }
}

async function processPayment(payload) {
    closeConfirm();

    if (currentMode === 'table') {
        await apiFetch(`/tables/${currentTable.id}/pay`, {
            method: 'PUT',
            body: JSON.stringify(payload),
        });

        const tbl = tables.find(t => t.id === currentTable.id);
        if (tbl) { tbl.total = 0; tbl.status = 'available'; }

        const label = currentTable.label;

        if (payload.print_receipt) printReceipt(payload);

        closePayment();
        deselect();
        showToast(`✓ ${label} paid via ${payload.payment_method}!`, '');

    } else {
        const id = currentDabao.id;
        await apiFetch(`/dabao/${id}/pay`, {
            method: 'PUT',
            body: JSON.stringify(payload),
        });

        printReceipt(payload);   // paid receipt

        dabaoSlots = dabaoSlots.filter(s => s.id !== id);
        closePayment();
        deselect();
        renderDabao();
        showToast(`✓ Dabao D${id} paid via ${payload.payment_method}!`, 'dp');
    }
}

function printReceipt(payload) {
    const items = Object.values(order);
    if (!items.length) return;

    const label = currentMode === 'table'
        ? currentTable.label
        : `Dabao D${currentDabao.id}`;

    const now = new Date().toLocaleString('en-MY');

    let receipt = `
[C]<font size='big'><b>LAO YANG KOPITIAM</b></font>

[C]<font size='big'><b>${label}</b></font>

[C]${now}

[C]================================
`;

    items.forEach(item => {
        receipt += `\n[L]<font size='big'><b>${item.qty} x ${item.name}</b></font>\n`;
        receipt += `[R]RM ${item.total_price.toFixed(2)}\n`;
        if (item.addons && item.addons.length > 0) {
            item.addons.forEach(ao => {
                receipt += `[L]  + ${ao.name} (RM ${parseFloat(ao.price).toFixed(2)})\n`;
            });
        }
    });

    receipt += `
[C]--------------------------------
[L]<b>Total</b>
[R]<b>RM ${payload.final_total.toFixed(2)}</b>
[L]Payment
[R]${payload.payment_method}
`;

    if (payload.payment_method.toLowerCase() === 'cash') {
        receipt += `[L]Received
[R]RM ${payload.amount_received.toFixed(2)}
[L]Change
[R]RM ${payload.change.toFixed(2)}
`;
    }

    receipt += `
[C]================================

[C]Thank You!

\n\n\n
`;

    if (window.AndroidPrinter) {
        AndroidPrinter.printBluetooth('[[OPEN_DRAWER]]' + receipt);
        showToast('🖨 Printing receipt...', '');
    } else {
        alert('Printer only works inside Android APK');
    }
}

function closeConfirm() {
    document.getElementById('confirmOverlay').classList.remove('open');
}

// ════════════════════════════════════════════════
// TOAST
// ════════════════════════════════════════════════
function showToast(msg, cls) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = `toast${cls ? ' ' + cls : ''} show`;
    setTimeout(() => t.classList.remove('show'), 2800);
}

// ════════════════════════════════════════════════
// HISTORY / BACK
// ════════════════════════════════════════════════
function pushState(state) {
    history.pushState(state, '', window.location.href);
}

(function () {
    history.pushState(null, '', window.location.href);
    window.addEventListener('popstate', function () {
        history.pushState(null, '', window.location.href);
    });
})();

boot();
</script>
</body>
</html>