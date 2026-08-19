<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title ?? 'Page Title' }}</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Barcode scanner library -->
    <script src="https://cdn.jsdelivr.net/npm/quagga@0.12.1/dist/quagga.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Inter font from Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Theme tokens: Orange & White theme - MATCHING ADMIN */
        :root {
            /* Clean Page Background */
            --page-bg: #f9fafb;
            --surface: #ffffff;

            /* Standard Black & White Palette */
            --primary: #000000;
            --primary-600: #111827;
            --primary-700: #1f2937;
            --primary-50: #f9fafb;
            --primary-100: #f3f4f6;

            /* Functional Colors */
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #111827;

            /* Refined Neutral Palette */
            --text-main: #111827;
            --text-muted: #6b7280;
            --text-light: #9ca3af;
            --border: #e5e7eb;
            --border-light: #f3f4f6;

            /* Effects */
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --radius-md: 10px;
            --radius-lg: 16px;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--page-bg);
            color: var(--text-main);
            letter-spacing: -0.01em;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .fw-800 { font-weight: 800 !important; }
        .text-orange { color: #f58320 !important; }

        /* Ensure dropdowns in table are not clipped */
        .table-responsive {
            overflow: visible !important;
        }

        .dropdown-menu {
            position: absolute !important;
            left: auto !important;
            right: 0 !important;
            top: 30% !important;
            margin-top: 0.2rem;
            min-width: 160px;
            z-index: 9999 !important;
            background: #fff !important;
            box-shadow: 0 12px 32px 0 rgba(0, 0, 0, 0.22), 0 2px 8px 0 rgba(0, 0, 0, 0.10);
            border-radius: 8px !important;
            border: 1px solid #e2e8f0 !important;
            overflow: visible !important;
            filter: none !important;
        }

        .dropdown-menu>li>.dropdown-item {
            background: #fff !important;
            z-index: 9999 !important;
        }

        .dropdown-menu>li>.dropdown-item:active,
        .dropdown-menu>li>.dropdown-item:focus {
            background: #f0f7ff !important;
            color: #222 !important;
        }

        .dropdown {
            position: relative !important;
        }

        .container-fluid,
        .card,
        .modal-content {
            font-size: 13px !important;
        }

        .table th,
        .table td {
            font-size: 12px !important;
            padding: 0.35rem 0.5rem !important;
        }

        .modal-header {
            padding-top: 0.5rem !important;
            padding-bottom: 0.5rem !important;
            margin-bottom: 0.25rem !important;
        }

        .modal-footer,
        .card-header,
        .card-body,
        .row,
        .col-md-6,
        .col-md-4,
        .col-md-2,
        .col-md-12 {
            padding-top: 0.5rem !important;
            padding-bottom: 0.5rem !important;
            margin-top: 0.25rem !important;
            margin-bottom: 0.25rem !important;
        }

        .form-control,
        .form-select {
            font-size: 12px !important;
            padding: 0.35rem 0.5rem !important;
        }

        .btn,
        .btn-sm,
        .btn-primary,
        .btn-secondary,
        .btn-outline-danger,
        .btn-outline-secondary {
            font-size: 12px !important;
            padding: 0.25rem 0.5rem !important;
        }

        .badge {
            font-size: 11px !important;
            padding: 0.25em 0.5em !important;
        }

        .list-group-item,
        .dropdown-item {
            font-size: 12px !important;
            padding: 0.35rem 0.5rem !important;
        }

        .summary-card,
        .card {
            border-radius: 8px !important;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06) !important;
        }

        .icon-container {
            width: 36px !important;
            height: 36px !important;
            font-size: 1.1rem !important;
        }

        /* Sidebar styles */
        .sidebar {
            width: 270px;
            height: 100vh;
            background: #ffffff;
            color: var(--text-main);
            padding: 0;
            position: fixed;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1040;
            overflow-y: auto;
            overflow-x: hidden;
            box-shadow: 1px 0 0 var(--border);
        }

        /* Add custom scrollbar styling for sidebar */
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }

        .sidebar::-webkit-scrollbar-thumb {
            background-color: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
        }

        /* Add padding to the bottom of sidebar to ensure last items are visible */
        .sidebar .nav {
            padding-bottom: 50px;
        }

        .sidebar.collapsed {
            width: 70px;
        }

        .sidebar.collapsed .sidebar-title,
        .sidebar.collapsed .nav-link span {
            display: none;
        }

        .sidebar.collapsed .nav-link.dropdown-toggle::after {
            display: flex !important;
            margin-left: 0;
            width: 100%;
            justify-content: center;
        }

        .sidebar.collapsed .nav-link i {
            margin-right: 0;
            font-size: 1.25rem;
        }

        .sidebar.collapsed .nav-link {
            text-align: center;
            padding: 10px;
        }

        .sidebar.collapsed .nav-link.dropdown-toggle::after {
            display: none;
        }

        .sidebar-header {
            padding: 20px 20px 0;
            margin-bottom: 5px;
        }

        .sidebar-title {
            font-weight: 600;
            font-size: 1.2rem;
            color: #1e293b;
            letter-spacing: -0.02em;
        }

        /* Navigation styles */
        .nav-item {
            margin: 2px 0;
        }

        .nav-link {
            color: var(--text-muted);
            padding: 6px 24px;
            margin: 4px 12px;
            border-radius: 8px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            font-weight: 500;
        }

        .nav-link.active {
            background: var(--primary-50);
            color: var(--primary);
            border-left: none;
        }

        .nav-link:hover:not(.active) {
            color: var(--primary);
            background: var(--border-light);
            outline: none;
        }

        .nav-link i {
            margin-right: 12px;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            transition: transform 0.2s;
        }

        .nav-link:hover i {
            transform: scale(1.1);
        }

        .nav-link.dropdown-toggle::after {
            margin-left: auto;
            border: none;
            content: "\F282";
            font-family: "bootstrap-icons";
            font-size: 0.95rem;
            transition: transform 0.3s ease;
            display: flex !important;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            flex-shrink: 0;
            color: var(--text-muted);
        }

        .nav-link.dropdown-toggle[aria-expanded="true"]::after {
            transform: rotate(180deg);
            color: var(--primary);
        }

        .nav-link.dropdown-toggle:hover::after {
            color: var(--primary);
        }

        #inventorySubmenu .nav-link,
        #hrSubmenu .nav-link,
        #salesSubmenu .nav-link,
        #stockSubmenu .nav-link,
        #purchaseSubmenu .nav-link,
        #returnSubmenu .nav-link,
        #banksSubmenu .nav-link,
        #paymentSubmenu .nav-link,
        #staffSubmenu .nav-link,
        #peopleSubmenu .nav-link,
        #expensesSubmenu .nav-link {
            padding: 8px 20px;
            font-size: 0.9rem;
            margin: 2px 8px;
        }

        /* Add these styles to further improve submenu spacing */
        .collapse .nav-item {
            margin: 3px 0;
        }

        .collapse .nav.flex-column {
            padding-bottom: 0;
            padding-top: 5px;
            padding-left: 12px;
        }

        .collapse .nav-item:last-child {
            margin-bottom: 5px;
        }

        /* Disabled menu item styles */
        .nav-link.disabled {
            color: rgba(255, 255, 255, 0.4) !important;
            cursor: not-allowed !important;
            opacity: 0.6;
            pointer-events: none;
        }

        .nav-link.disabled i {
            color: rgba(255, 255, 255, 0.4) !important;
        }

        .nav-link.disabled:hover {
            background-color: transparent !important;
            color: rgba(255, 255, 255, 0.4) !important;
        }

        /* Top bar styles */
        .top-bar {
            height: 72px;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            padding: 0 24px;
            position: fixed;
            top: 0;
            right: 0;
            left: 270px;
            z-index: 1000;
            display: flex;
            align-items: center;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            border-bottom: 1px solid var(--border);
        }

        .top-bar.collapsed {
            left: 80px;
        }

        /* Main content area */
        .main-content {
            margin-left: 270px;
            margin-top: 72px;
            padding: 24px;
            background-color: var(--page-bg);
            min-height: calc(100vh - 72px);
            width: calc(100% - 270px);
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .main-content.collapsed {
            margin-left: 70px;
            width: calc(100% - 70px);
        }

        /* Top bar items styling */
        .topbar-brand {
            font-size: 16px;
            font-weight: 700;
            color: var(--primary);
            letter-spacing: -0.02em;
            margin-right: auto;
        }

        .admin-info {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 12px;
            border-radius: 8px;
            color: var(--text-main);
            cursor: pointer;
            transition: all 0.2s;
        }

        .admin-info:hover {
            background: var(--border-light);
        }

        .admin-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--primary);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
        }

        .admin-name {
            font-weight: 500;
            font-size: 14px;
        }

        .sidebar-toggle {
            background: transparent;
            border: none;
            color: var(--text-main);
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 8px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sidebar-toggle:hover {
            background: var(--border-light);
            color: var(--primary);
        }

        /* Responsive adjustments */
        @media (max-width: 1024px) {
            .sidebar {
                width: 250px;
            }

            .top-bar {
                left: 250px;
            }

            .main-content {
                margin-left: 250px;
                width: calc(100% - 250px);
            }

            .top-bar.collapsed {
                left: 70px;
            }
        }

        @media (max-width: 767.98px) {
            .sidebar {
                position: fixed;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                transform: translateX(-100%);
                transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
                z-index: 1030;
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .top-bar {
                left: 0;
                padding: 0 16px;
            }

            .main-content {
                margin-left: 0;
                width: 100%;
                margin-top: 72px;
                padding: 16px;
            }

            .topbar-brand {
                display: none;
            }
        }
    </style>
    @stack('styles')
    @livewireStyles
</head>

<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header d-flex justify-content-center">
                <div class="sidebar-title">
                    <img src="{{ asset('images/HARDMEN.png') }}" alt="Logo" width="250">
                </div>
            </div>
            <hr style="border-color: var(--border); margin: 10px 0;">

            <ul class="nav flex-column">
                {{-- Dashboard --}}
                @if(auth()->user()->hasPermission('menu_dashboard'))
                <li>
                    <a class="nav-link {{ request()->routeIs('staff.dashboard') ? 'active' : '' }}" href="{{ route('staff.dashboard') }}">
                        <i class="bi bi-speedometer2"></i> <span>Overview</span>
                    </a>
                </li>
                @endif

                {{-- Products Menu --}}
                @if(auth()->user()->hasPermission('menu_products'))
                <li class="nav-item">
                    <a class="nav-link dropdown-toggle" href="#inventorySubmenu" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="inventorySubmenu">
                        <i class="bi bi-basket3"></i> <span>Products</span>
                    </a>
                    <div class="collapse" id="inventorySubmenu">
                        <ul class="nav flex-column ms-3">
                            @if(auth()->user()->hasPermission('menu_products_list'))
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('staff.Productes') }}">
                                    <i class="bi bi-card-list"></i> <span>List Product</span>
                                </a>
                            </li>
                            @endif
                            @if(auth()->user()->hasPermission('menu_products_brand'))
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('staff.Product-brand') }}">
                                    <i class="bi bi-tags"></i> <span>Product Brand</span>
                                </a>
                            </li>
                            @endif
                            @if(auth()->user()->hasPermission('menu_products_category'))
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('staff.Product-category') }}">
                                    <i class="bi bi-tags-fill"></i> <span>Product Category</span>
                                </a>
                            </li>
                            @endif
                            @if(auth()->user()->hasPermission('menu_products_variant'))
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('admin.manage-variants') }}">
                                    <i class="bi bi-layers"></i> <span>Product Variant</span>
                                </a>
                            </li>
                            @endif
                        </ul>
                    </div>
                </li>
                @endif

                {{-- Sales Menu --}}
                @if(auth()->user()->hasPermission('menu_sales'))
                <li class="nav-item">
                    <a class="nav-link dropdown-toggle" href="#salesSubmenu" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="salesSubmenu">
                        <i class="bi bi-cash-stack"></i> <span>Sales</span>
                    </a>
                    <div class="collapse" id="salesSubmenu">
                        <ul class="nav flex-column ms-3">
                            @if(auth()->user()->hasPermission('menu_sales_add'))
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('staff.sales-system') }}">
                                    <i class="bi bi-plus-circle"></i> <span>Add Sales</span>
                                </a>
                            </li>
                            @endif
                            @if(auth()->user()->hasPermission('menu_sales_list'))
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('staff.sales-list') }}">
                                    <i class="bi bi-table"></i> <span>List Sales</span>
                                </a>
                            </li>
                            @endif
                            @if(auth()->user()->hasPermission('menu_sales_pos'))
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('staff.pos-sales') }}">
                                    <i class="bi bi-shop"></i> <span>POS Sales</span>
                                </a>
                            </li>
                            @endif
                        </ul>
                    </div>
                </li>
                @endif

                {{-- Quotation Menu --}}
                @if(auth()->user()->hasPermission('menu_quotation'))
                <li class="nav-item">
                    <a class="nav-link dropdown-toggle" href="#stockSubmenu" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="stockSubmenu">
                        <i class="bi bi-file-earmark-text"></i> <span>Quotation</span>
                    </a>
                    <div class="collapse" id="stockSubmenu">
                        <ul class="nav flex-column ms-3">
                            @if(auth()->user()->hasPermission('menu_quotation_add'))
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('staff.quotation-system') }}">
                                    <i class="bi bi-file-plus"></i> <span>Add Quotation</span>
                                </a>
                            </li>
                            @endif
                            @if(auth()->user()->hasPermission('menu_quotation_list'))
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('staff.quotation-list') }}">
                                    <i class="bi bi-card-list"></i> <span>List Quotation</span>
                                </a>
                            </li>
                            @endif
                        </ul>
                    </div>
                </li>
                @endif

                {{-- Purchase Menu --}}
                @if(auth()->user()->hasPermission('menu_purchase'))
                <li class="nav-item">
                    <a class="nav-link dropdown-toggle" href="#purchaseSubmenu" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="purchaseSubmenu">
                        <i class="bi bi-truck"></i><span>Purchase</span>
                    </a>
                    <div class="collapse" id="purchaseSubmenu">
                        <ul class="nav flex-column ms-3">
                            @if(auth()->user()->hasPermission('menu_purchase_order'))
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('staff.purchase-order-list') }}">
                                    <i class="bi bi-journal-bookmark"></i> <span>Purchase Order</span>
                                </a>
                            </li>
                            @endif
                            @if(auth()->user()->hasPermission('menu_purchase_grn'))
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('staff.grn') }}">
                                    <i class="bi bi-boxes"></i><span>GRN</span>
                                </a>
                            </li>
                            @endif
                        </ul>
                    </div>
                </li>
                @endif

                {{-- Return Menu --}}
                @if(auth()->user()->hasPermission('menu_return'))
                <li class="nav-item">
                    <a class="nav-link dropdown-toggle" href="#returnSubmenu" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="returnSubmenu">
                        <i class="bi bi-arrow-counterclockwise"></i> <span>Return</span>
                    </a>
                    <div class="collapse" id="returnSubmenu">
                        <ul class="nav flex-column ms-3">
                            @if(auth()->user()->hasPermission('menu_return_customer_add'))
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('staff.return-add') }}">
                                    <i class="bi bi-arrow-return-left"></i> <span>Add Customer Return</span>
                                </a>
                            </li>
                            @endif
                            @if(auth()->user()->hasPermission('menu_return_customer_list'))
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('staff.return-list') }}">
                                    <i class="bi bi-list-check"></i> <span>List Customer Return</span>
                                </a>
                            </li>
                            @endif
                            @if(auth()->user()->hasPermission('menu_return_supplier_add'))
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('staff.return-supplier') }}">
                                    <i class="bi bi-arrow-return-left"></i> <span>Add Supplier Return</span>
                                </a>
                            </li>
                            @endif
                            @if(auth()->user()->hasPermission('menu_return_supplier_list'))
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('staff.list-supplier-return') }}">
                                    <i class="bi bi-list-check"></i> <span>List Supplier Return</span>
                                </a>
                            </li>
                            @endif
                        </ul>
                    </div>
                </li>
                @endif

                {{-- Customer Menu --}}
                @if(auth()->user()->hasPermission('menu_customer'))
                <li class="nav-item">
                    <a class="nav-link dropdown-toggle" href="#customerSubmenu" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="customerSubmenu">
                        <i class="bi bi-people"></i> <span>Customer</span>
                    </a>
                    <div class="collapse" id="customerSubmenu">
                        <ul class="nav flex-column ms-3">
                            @if(auth()->user()->hasPermission('menu_customer_add') || auth()->user()->hasPermission('menu_customer_list'))
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('staff.manage-customers') }}">
                                    <i class="bi bi-people-fill"></i> <span>Manage Customers</span>
                                </a>
                            </li>
                            @endif
                        </ul>
                    </div>
                </li>
                @endif

                {{-- Supplier Menu --}}
                @if(auth()->user()->hasPermission('menu_supplier'))
                <li class="nav-item">
                    <a class="nav-link dropdown-toggle" href="#supplierSubmenu" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="supplierSubmenu">
                        <i class="bi bi-truck"></i> <span>Supplier</span>
                    </a>
                    <div class="collapse" id="supplierSubmenu">
                        <ul class="nav flex-column ms-3">
                            @if(auth()->user()->hasPermission('menu_supplier_add') || auth()->user()->hasPermission('menu_supplier_list'))
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('staff.supplier-management') }}">
                                    <i class="bi bi-truck"></i> <span>Supplier Management</span>
                                </a>
                            </li>
                            @endif
                        </ul>
                    </div>
                </li>
                @endif

                {{-- Cheque/Banks Menu --}}
                @if(auth()->user()->hasPermission('menu_banks'))
                <li class="nav-item">
                    <a class="nav-link dropdown-toggle" href="#banksSubmenu" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="banksSubmenu">
                        <i class="bi bi-bank"></i> <span>Cheque / Banks</span>
                    </a>
                    <div class="collapse" id="banksSubmenu">
                        <ul class="nav flex-column ms-3">
                            @if(auth()->user()->hasPermission('menu_banks_deposit'))
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('staff.income') }}">
                                    <i class="bi bi-cash-stack"></i> <span>Deposit By Cash</span>
                                </a>
                            </li>
                            @endif
                            @if(auth()->user()->hasPermission('menu_banks_cheque_list'))
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('staff.cheque-list') }}">
                                    <i class="bi bi-card-text"></i> <span>Cheque List</span>
                                </a>
                            </li>
                            @endif
                            @if(auth()->user()->hasPermission('menu_banks_return_cheque'))
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('staff.return-cheque') }}">
                                    <i class="bi bi-arrow-left-right"></i> <span>Return Cheque</span>
                                </a>
                            </li>
                            @endif
                        </ul>
                    </div>
                </li>
                @endif

                {{-- Expenses Menu --}}
                @if(auth()->user()->hasPermission('menu_expenses'))
                <li class="nav-item">
                    <a class="nav-link dropdown-toggle" href="#expensesSubmenu" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="expensesSubmenu">
                        <i class="bi bi-wallet2"></i> <span>Expenses</span>
                    </a>
                    <div class="collapse" id="expensesSubmenu">
                        <ul class="nav flex-column ms-3">
                            @if(auth()->user()->hasPermission('menu_expenses_list'))
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('staff.expenses') }}">
                                    <i class="bi bi-wallet2"></i> <span>List Expenses</span>
                                </a>
                            </li>
                            @endif
                        </ul>
                    </div>
                </li>
                @endif

                {{-- Payment Management Menu --}}
                @if(auth()->user()->hasPermission('menu_payment'))
                <li class="nav-item">
                    <a class="nav-link dropdown-toggle" href="#paymentSubmenu" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="paymentSubmenu">
                        <i class="bi bi-receipt-cutoff"></i> <span>Payment Management</span>
                    </a>
                    <div class="collapse" id="paymentSubmenu">
                        <ul class="nav flex-column ms-3">
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('staff.due-payments') }}">
                                    <i class="bi bi-cash-coin"></i> <span>Add Payment</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('staff.payments-list') }}">
                                    <i class="bi bi-list-check"></i> <span>Payment List</span>
                                </a>
                            </li>
                            @if(auth()->user()->hasPermission('menu_payment_customer_receipt_add'))
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('staff.add-customer-receipt') }}">
                                    <i class="bi bi-person-plus"></i> <span>Add Customer Receipt</span>
                                </a>
                            </li>
                            @endif
                            @if(auth()->user()->hasPermission('menu_payment_customer_receipt_list'))
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('staff.list-customer-receipt') }}">
                                    <i class="bi bi-people-fill"></i> <span>List Customer Receipt</span>
                                </a>
                            </li>
                            @endif
                            @if(auth()->user()->hasPermission('menu_payment_supplier_add'))
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('staff.add-supplier-receipt') }}">
                                    <i class="bi bi-truck-flatbed"></i> <span>Add Supplier Payment</span>
                                </a>
                            </li>
                            @endif
                            @if(auth()->user()->hasPermission('menu_payment_supplier_list'))
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('staff.list-supplier-receipt') }}">
                                    <i class="bi bi-clipboard-data"></i> <span>List Supplier Payment</span>
                                </a>
                            </li>
                            @endif
                        </ul>
                    </div>
                </li>
                @endif

                {{-- People Menu --}}
                @if(auth()->user()->hasPermission('menu_people'))
                <li class="nav-item">
                    <a class="nav-link dropdown-toggle" href="#peopleSubmenu" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="peopleSubmenu">
                        <i class="bi bi-people-fill"></i> <span>People</span>
                    </a>
                    <div class="collapse" id="peopleSubmenu">
                        <ul class="nav flex-column ms-3">
                            @if(auth()->user()->hasPermission('menu_people_suppliers'))
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('staff.supplier-management') }}">
                                    <i class="bi bi-people"></i> <span>List Suppliers</span>
                                </a>
                            </li>
                            @endif
                            @if(auth()->user()->hasPermission('menu_people_customers'))
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('staff.manage-customers') }}">
                                    <i class="bi bi-person-lines-fill"></i> <span>List Customer</span>
                                </a>
                            </li>
                            @endif
                            @if(auth()->user()->hasPermission('menu_people_staff'))
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('staff.manage-staff') }}">
                                    <i class="bi bi-person-badge"></i> <span>List Staff</span>
                                </a>
                            </li>
                            @endif
                        </ul>
                    </div>
                </li>
                @endif

                {{-- POS 
                @if(auth()->user()->hasPermission('menu_pos'))
                <li>
                    <a class="nav-link" href="{{ route('staff.billing') }}">
                        <i class="bi bi-cash"></i> <span>POS</span>
                    </a>
                </li>
                @endif
                --}}

                {{-- Reports --}}
                @if(auth()->user()->hasPermission('menu_reports'))
                <li>
                    <a class="nav-link" href="{{ route('staff.reports') }}">
                        <i class="bi bi-file-earmark-bar-graph"></i> <span>Reports</span>
                    </a>
                </li>
                @endif

                {{-- Analytics --}}
                @if(auth()->user()->hasPermission('menu_analytics'))
                <li>
                    <a class="nav-link" href="{{ route('staff.analytics') }}">
                        <i class="bi bi-bar-chart"></i> <span>Analytics</span>
                    </a>
                </li>
                @endif

                {{-- <li>
                    <a class="nav-link" href="{{ route('staff.settings') }}">
                        <i class="bi bi-gear"></i> <span>Settings</span>
                    </a>
                </li> --}}
            </ul>
        </div>

        <!-- Top Navigation Bar -->
        <nav class="top-bar">
            <button id="sidebarToggler" class="sidebar-toggle ms-n3">
                <i class="bi bi-list fs-5"></i>
            </button>

            <div class="topbar-brand">
                Hardmen
            </div>

            <div class="dropdown ms-auto">
                <div class="admin-info dropdown-toggle" id="adminDropdown" role="button" data-bs-toggle="dropdown"
                    aria-expanded="false">
                    <div class="admin-avatar">S</div>
                    <div class="admin-name">Staff</div>
                </div>

                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminDropdown">
                    <li>
                        <a class="dropdown-item" href="{{ route('profile.show') }}">
                            <i class="bi bi-person me-2"></i>My Profile
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('staff.settings') }}">
                            <i class="bi bi-gear me-2"></i>Settings
                        </a>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}" class="mb-0">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="bi bi-box-arrow-right me-2"></i>Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </nav>
        <!-- Main Content -->
        <main class="main-content">
            {{ $slot }}
        </main>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 from CDN (only need this one line) -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Include jQuery (required by Bootstrap 4 modal) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Define all elements once
            const sidebarToggler = document.getElementById('sidebarToggler');
            const sidebar = document.querySelector('.sidebar');
            const topBar = document.querySelector('.top-bar');
            const mainContent = document.querySelector('.main-content');

            // Tab Switching Functionality
            const tabs = document.querySelectorAll('.content-tab');
            if (tabs.length > 0) {
                tabs.forEach(tab => {
                    tab.addEventListener('click', function() {
                        // Remove active class from all tabs
                        tabs.forEach(t => t.classList.remove('active'));

                        // Add active class to clicked tab
                        this.classList.add('active');

                        // Hide all tab contents
                        document.querySelectorAll('.tab-content').forEach(content => {
                            content.classList.remove('active');
                        });

                        // Show the selected tab content
                        const tabId = this.getAttribute('data-tab');
                        document.getElementById(tabId).classList.add('active');
                    });
                });
            }

            // Improved menu activation logic
            function setActiveMenu() {
                const currentPath = window.location.pathname;
                let activeSubmenuFound = false;

                // First, check all menu links in the sidebar
                document.querySelectorAll('.sidebar .nav-link').forEach(link => {
                    // Reset all links to inactive state first
                    link.classList.remove('active');

                    // Get the link's href attribute
                    const href = link.getAttribute('href');
                    if (href && href !== '#' && !href.startsWith('#')) {
                        // Extract just the path portion of the href
                        const hrefPath = href.replace(/^(https?:\/\/[^\/]+)/, '').split('?')[0];

                        // Use more precise path matching logic
                        const isActive = currentPath === hrefPath ||
                            (currentPath.startsWith(hrefPath + '/') && hrefPath !== '/') ||
                            (currentPath === hrefPath + '.php');

                        if (isActive) {
                            // This link is active
                            link.classList.add('active');

                            // If this is a submenu link, expand and highlight the parent menu
                            const submenu = link.closest('.collapse');
                            if (submenu) {
                                activeSubmenuFound = true;

                                // Add 'show' class to submenu to keep it expanded
                                submenu.classList.add('show');

                                // Find and activate the parent dropdown toggle
                                const parentToggle = document.querySelector(`[data-bs-toggle="collapse"][href="#${submenu.id}"]`);
                                if (parentToggle) {
                                    parentToggle.classList.add('active');
                                    parentToggle.setAttribute('aria-expanded', 'true');
                                }
                            }
                        }
                    }
                });

                // If no submenu item is active, check if we need to activate a main nav item
                if (!activeSubmenuFound) {
                    // Get the route base path segments (e.g., /staff/billing → ["staff", "billing"])
                    const pathSegments = currentPath.split('/').filter(Boolean);

                    // Only check main items if we have path segments
                    if (pathSegments.length > 0) {
                        document.querySelectorAll('.sidebar > .sidebar-content > .nav > .nav-item > .nav-link:not(.dropdown-toggle)').forEach(link => {
                            const href = link.getAttribute('href');
                            if (href && href !== '#') {
                                const hrefPath = href.replace(/^(https?:\/\/[^\/]+)/, '').split('?')[0];
                                const hrefSegments = hrefPath.split('/').filter(Boolean);

                                // Only match exact routes or next level child routes
                                const isActive = hrefPath === currentPath ||
                                    (hrefSegments.length > 0 &&
                                        pathSegments.length > 0 &&
                                        hrefSegments[hrefSegments.length - 1] === pathSegments[pathSegments.length - 1]);

                                if (isActive) {
                                    link.classList.add('active');
                                }
                            }
                        });
                    }
                }
            }

            // Call the improved function instead of the old ones
            setActiveMenu();

            // Initialize sidebar state based on screen size
            function initializeSidebar() {
                // Existing code...
            }

            // Toggle sidebar function - unified for mobile and desktop
            function toggleSidebar(event) {
                if (event) {
                    event.stopPropagation();
                }

                const isMobile = window.innerWidth < 768;

                if (isMobile) {
                    // Mobile behavior - toggle show class
                    sidebar.classList.toggle('show');

                    // Ensure no collapsed classes are present on mobile
                    sidebar.classList.remove('collapsed');
                    topBar.classList.remove('collapsed');
                    mainContent.classList.remove('collapsed');
                } else {
                    // Desktop behavior - toggle collapsed classes
                    sidebar.classList.toggle('collapsed');
                    topBar.classList.toggle('collapsed');
                    mainContent.classList.toggle('collapsed');

                    // Save state to localStorage
                    localStorage.setItem('sidebar-collapsed', sidebar.classList.contains('collapsed'));
                }
            }

            // Adjust sidebar height
            function adjustSidebarHeight() {
                if (sidebar) {
                    // Ensure sidebar takes full viewport height
                    sidebar.style.height = `${window.innerHeight}px`;

                    // Check if content is taller than viewport
                    const sidebarNav = sidebar.querySelector('.nav.flex-column');
                    if (sidebarNav) {
                        const needsScroll = sidebarNav.scrollHeight > window.innerHeight;
                        if (needsScroll) {
                            sidebar.classList.add('scrollable');
                        } else {
                            sidebar.classList.remove('scrollable');
                        }
                    }
                }
            }

            // Initialize sidebar
            if (sidebar) {
                initializeSidebar();

                // Attach toggle event listener (single source of truth)
                if (sidebarToggler) {
                    sidebarToggler.addEventListener('click', toggleSidebar);
                }

                // Close sidebar when clicking outside on mobile
                document.addEventListener('click', function(event) {
                    const isMobile = window.innerWidth < 768;
                    const isClickInsideSidebar = sidebar.contains(event.target);
                    const isClickOnToggler = sidebarToggler && sidebarToggler.contains(event.target);

                    if (isMobile &&
                        sidebar.classList.contains('show') &&
                        !isClickInsideSidebar &&
                        !isClickOnToggler) {
                        sidebar.classList.remove('show');
                    }
                });

                // Handle window resize - switch between mobile and desktop modes
                window.addEventListener('resize', function() {
                    const wasMobile = mainContent.style.marginLeft === '0px' || mainContent.style.marginLeft === '';
                    const isMobile = window.innerWidth < 768;

                    // Only run when crossing the mobile/desktop threshold
                    if (wasMobile !== isMobile) {
                        initializeSidebar();
                    }
                });

                // Adjust sidebar height initially and on resize
                adjustSidebarHeight();
                window.addEventListener('resize', adjustSidebarHeight);

                // Fix submenu scroll visibility
                const dropdownToggles = document.querySelectorAll('.nav-link.dropdown-toggle');
                dropdownToggles.forEach(toggle => {
                    toggle.addEventListener('click', function(event) {
                        // Wait for submenu to fully appear
                        setTimeout(() => {
                            const submenu = this.nextElementSibling;
                            if (submenu && submenu.classList.contains('show')) {
                                // Check if submenu bottom is out of view
                                const submenuRect = submenu.getBoundingClientRect();
                                const sidebarRect = sidebar.getBoundingClientRect();

                                if (submenuRect.bottom > sidebarRect.bottom) {
                                    // Scroll to make submenu visible
                                    submenu.scrollIntoView({
                                        behavior: 'smooth',
                                        block: 'end'
                                    });
                                }
                            }
                        }, 300);
                    });
                });
            }
        });
    </script>
    @stack('scripts')
</body>

</html>