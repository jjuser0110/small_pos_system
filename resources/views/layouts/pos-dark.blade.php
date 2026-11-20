{{-- Dark mode for the counter and checkout (they have their own styles, not the admin theme). --}}
@if(\App\Support\Preferences::dark())
<style>
html.dark-style body { background: linear-gradient(135deg, #14532d 0%, #052e16 100%); color: #d6dbe4; }
html.dark-style .left-panel, html.dark-style .right-panel, html.dark-style .card,
html.dark-style .custom-modal-content { background: #232333; color: #d6dbe4; }
html.dark-style .left-panel::-webkit-scrollbar-track, html.dark-style .cart-items::-webkit-scrollbar-track,
html.dark-style .order-items-container::-webkit-scrollbar-track { background: #2d2d40; }
html.dark-style h1, html.dark-style .custom-modal-header h5 { color: #4ade80; }
html.dark-style .barcode-input, html.dark-style .quantity-input, html.dark-style .amount-input,
html.dark-style input, html.dark-style select, html.dark-style textarea {
    background: #1b1b28; color: #e5e7eb; border-color: #3f3f55;
}
html.dark-style .category-btn { background: #2d2d40; color: #d6dbe4; border-color: #3f3f55; }
html.dark-style .category-btn.active { background: linear-gradient(135deg, #16a34a 0%, #166534 100%); color: #fff; border-color: #16a34a; }
html.dark-style .product-card, html.dark-style .cart-item, html.dark-style .order-item { background: #2d2d40; }
html.dark-style .product-name, html.dark-style .item-name, html.dark-style .custom-modal-body,
html.dark-style .section-title, html.dark-style .amount-label, html.dark-style .change-label,
html.dark-style .payment-name, html.dark-style .item-meta { color: #d6dbe4; }
html.dark-style .product-price, html.dark-style .item-price, html.dark-style .total-row,
html.dark-style .item-total, html.dark-style .change-amount { color: #4ade80; }
html.dark-style .cart-header, html.dark-style .cart-summary, html.dark-style .page-header,
html.dark-style .summary-section, html.dark-style .payment-section { border-color: #3f3f55; }
html.dark-style .side-btn { background: #1b1b28; color: #4ade80; border-color: #16a34a; }
html.dark-style .side-btn:hover, html.dark-style .quick-amount-btn:hover { background: #14532d; }
html.dark-style .back-btn, html.dark-style .quick-amount-btn { background: #2d2d40; color: #d6dbe4; border-color: #3f3f55; }
html.dark-style .payment-method { background: #2d2d40; border-color: #3f3f55; color: #d6dbe4; }
html.dark-style .payment-method:hover { background: #1f3d2b; }
html.dark-style .payment-method.active { background: #14532d; border-color: #16a34a; }
html.dark-style .change-display { background: #1f3d2b; }
html.dark-style .insufficient-payment { background: #4a1f1f; }
html.dark-style .cancel-btn { background: #3f3f55; color: #e5e7eb; }
html.dark-style .empty-cart { color: #8b8fa3; }
@media print { html.dark-style body { background: #fff; color: #000; } }
</style>
@endif
