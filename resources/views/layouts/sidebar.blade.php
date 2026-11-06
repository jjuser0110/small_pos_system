@php

$currentRoute = request()->route()->getName();

@endphp
<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="app-brand demo" >
        <a href="{{ route('home') }}" class="app-brand-link">
            <img src="{{ asset('assets/logo-fsmart.svg') }}?v=2" alt="Logo" width="120">
        </a>

        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
            <i class="bx menu-toggle-icon d-none d-xl-block fs-4 align-middle"></i>
            <i class="bx bx-x d-block d-xl-none bx-sm align-middle"></i>
        </a>
    </div>

    <div class="menu-divider mt-0"></div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1" style="overflow-y:auto">
        @php
            $isStaff = auth()->user()->role_id == 5;
            // badges = things waiting for this user to approve
            $borrowPending   = \App\Models\StaffBorrow::pendingFor(auth()->user())->count();
            $refundPending   = \App\Models\Refund::pendingFor(auth()->user())->count();
            $damagePending   = \App\Models\Damage::pendingFor(auth()->user())->count();
            $transferPending = \App\Models\StockTransfer::pendingFor(auth()->user())->count();
        @endphp
        <li class="menu-item {{ Str::contains($currentRoute, 'home') ? 'active' : ''}}">
            <a href="{{ route('home') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-spreadsheet"></i>
                <div>Home</div>
            </a>
        </li>
        <li class="menu-item {{ Str::contains($currentRoute, 'counter') ? 'active' : ''}}">
            <a href="{{ route('counter') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-spreadsheet"></i>
                <div>Go to Counter</div>
            </a>
        </li>
        @if(!$isStaff)
        <li class="menu-item {{ Str::contains($currentRoute, 'report.index') ? 'active' : ''}}">
            <a href="{{ route('report.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-spreadsheet"></i>
                <div>Report</div>
            </a>
        </li>
        @endif
        <li class="menu-item {{ Str::contains($currentRoute, 'shift_closing.') ? 'active' : ''}}">
            <a href="{{ route('shift_closing.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-spreadsheet"></i>
                <div>Shift Report</div>
            </a>
        </li>
        @if(!$isStaff)
        <li class="menu-item {{ Str::contains($currentRoute, 'staff_attendance.') ? 'active' : ''}}">
            <a href="{{ route('staff_attendance.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-spreadsheet"></i>
                <div>Staff Attendance</div>
            </a>
        </li>
        @endif
        <li class="menu-item {{ Str::contains($currentRoute, 'staff_borrow.') ? 'active' : ''}}">
            <a href="{{ route('staff_borrow.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-wallet"></i>
                <div>Advance</div>
                @if($borrowPending > 0)
                    <span class="badge bg-danger rounded-pill ms-auto">{{ $borrowPending }}</span>
                @endif
            </a>
        </li>
        <li class="menu-item {{ Str::contains($currentRoute, 'refund.') ? 'active' : ''}}">
            <a href="{{ route('refund.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-undo"></i>
                <div>Refunds</div>
                @if($refundPending > 0)
                    <span class="badge bg-danger rounded-pill ms-auto">{{ $refundPending }}</span>
                @endif
            </a>
        </li>
        <li class="menu-item {{ Str::contains($currentRoute, 'damage.') ? 'active' : ''}}">
            <a href="{{ route('damage.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-error"></i>
                <div>Damage</div>
                @if($damagePending > 0)
                    <span class="badge bg-danger rounded-pill ms-auto">{{ $damagePending }}</span>
                @endif
            </a>
        </li>
        <li class="menu-item {{ Str::contains($currentRoute, 'stock_transfer.') ? 'active' : ''}}">
            <a href="{{ route('stock_transfer.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-transfer"></i>
                <div>Stock Transfer</div>
                @if($transferPending > 0)
                    <span class="badge bg-danger rounded-pill ms-auto">{{ $transferPending }}</span>
                @endif
            </a>
        </li>
        @if(!$isStaff)
        <li class="menu-item {{ Str::contains($currentRoute, 'stock_adjustment.') ? 'active' : ''}}">
            <a href="{{ route('stock_adjustment.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-spreadsheet"></i>
                <div>Stock Adjustment</div>
            </a>
        </li>
        @endif

        @if(!$isStaff)
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text" data-i18n="Company Settings">Company Settings</span>
        </li>
        <li class="menu-item {{ Str::contains($currentRoute, 'order.') ? 'active' : ''}}">
            <a href="{{ route('order.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-spreadsheet"></i>
                <div>Order</div>
            </a>
        </li>
        <li class="menu-item {{ Str::contains($currentRoute, 'batch.') ? 'active' : ''}}">
            <a href="{{ route('batch.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-spreadsheet"></i>
                <div>Stock</div>
            </a>
        </li>
        <li class="menu-item {{ Str::contains($currentRoute, 'product.') ? 'active' : ''}}">
            <a href="{{ route('product.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-spreadsheet"></i>
                <div>Product</div>
            </a>
        </li>
        <li class="menu-item {{ Str::contains($currentRoute, 'category.') ? 'active' : ''}}">
            <a href="{{ route('category.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-spreadsheet"></i>
                <div>Category</div>
            </a>
        </li>
        @endif

        @if(in_array(auth()->user()->role_id, [1, 2, 3]))
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text" data-i18n="Master Setting">Master Setting</span>
        </li>
        <li class="menu-item {{ Str::contains($currentRoute, 'company.index') ? 'active' : ''}}">
            <a href="{{ route('company.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-spreadsheet"></i>
                <div>Company</div>
            </a>
        </li>
        <li class="menu-item {{ Str::contains($currentRoute, 'supplier.index') ? 'active' : ''}}">
            <a href="{{ route('supplier.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-spreadsheet"></i>
                <div>Supplier</div>
            </a>
        </li>
        <li class="menu-item {{ Str::contains($currentRoute, 'company_staff.index') ? 'active' : ''}}">
            <a href="{{ route('company_staff.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-spreadsheet"></i>
                <div>Staff</div>
            </a>
        </li>
        @endif

        @if(auth()->user()->role_id == 1 || auth()->user()->role_id == 2)
        <li class="menu-item {{ Str::contains($currentRoute, 'branch_manager.index') ? 'active' : ''}}">
            <a href="{{ route('branch_manager.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-spreadsheet"></i>
                <div>Branch Manager</div>
            </a>
        </li>
        <li class="menu-item {{ Str::contains($currentRoute, 'branch.index') ? 'active' : ''}}">
            <a href="{{ route('branch.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-spreadsheet"></i>
                <div>Branch</div>
            </a>
        </li>
        <li class="menu-item {{ Str::contains($currentRoute, 'uom.index') ? 'active' : ''}}">
            <a href="{{ route('uom.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-spreadsheet"></i>
                <div>UOM</div>
            </a>
        </li>
        <li class="menu-item {{ Str::contains($currentRoute, 'payment_method.index') ? 'active' : ''}}">
            <a href="{{ route('payment_method.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-spreadsheet"></i>
                <div>Payment Method</div>
            </a>
        </li>
        <li class="menu-item {{ Str::contains($currentRoute, 'receipt_setting.index') ? 'active' : ''}}">
            <a href="{{ route('receipt_setting.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-spreadsheet"></i>
                <div>Receipt Setting</div>
            </a>
        </li>
        @endif
    </ul>
</aside>
<!-- end: sidebar -->
