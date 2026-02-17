<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Page Title' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/hardmenicon.png') }}">
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
        /* Theme tokens: Orange & White theme */
        :root {
            /* Clean Page Background */
            --page-bg: #f3f4f6;
            --surface: #ffffff;

            /* Professional Brand Palette - Vibrant Orange & Neutral Accents */
            --primary: #f58320;
            --primary-600: #e07010;
            --primary-700: #c66008;
            --primary-50: #fff7ed;
            --primary-100: #ffedd5;

            /* Functional Colors */
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;

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
            overflow-y: auto !important;
            overflow-x: hidden !important;
            transition: all 0.3s ease;
        }

        /* Use :has to allow overflow only when hovering an item with a submenu */
        .sidebar.collapsed:has(.nav-item:hover) {
            overflow: visible !important;
        }

        /* Performance optimization for scrolling */
        .sidebar.collapsed::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar.collapsed .sidebar-title,
        .sidebar.collapsed .nav-link span {
            display: none;
        }

        .sidebar.collapsed .nav-link.dropdown-toggle::after {
            display: none !important;
        }

        .sidebar.collapsed .nav-link i {
            margin-right: 0;
            font-size: 1.25rem;
        }

        .sidebar.collapsed .nav-link {
            text-align: center;
            padding: 10px;
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
            /* Reduced from 5px to 2px */
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
        #peopleSubmenu .nav-link {
            padding: 8px 20px;
            /* Better padding for submenu links */
            font-size: 0.9rem;
            margin: 2px 8px;
            /* Improved margins for submenu items */
        }

        /* Add these styles to further improve submenu spacing */
        .collapse .nav-item {
            margin: 3px 0;
            /* Better spacing for submenu items */
        }

        .collapse .nav.flex-column {
            padding-bottom: 0;
            /* Remove extra bottom padding from nested menus */
            padding-top: 5px;
            /* Add small top padding to separate from parent */
            padding-left: 12px;
            /* Add left padding for visual indentation */
        }

        .collapse .nav-item:last-child {
            margin-bottom: 5px;
            /* Add small space after last submenu item */
        }

        /* Floating submenu for collapsed sidebar */
        @media (min-width: 768px) {
            .sidebar.collapsed .nav-item {
                position: relative;
            }

            /* Force hide submenus in collapsed state, even if 'show' class is present */
            .sidebar.collapsed .nav-item > .collapse {
                display: none !important;
            }

            .sidebar.collapsed .nav-item:hover > .collapse {
                display: block !important;
                position: absolute !important;
                left: 68px; /* Slightly closer for better hover transition */
                top: 0;
                width: 240px;
                background: #ffffff !important;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2) !important;
                border-radius: 12px !important;
                border: 1px solid var(--border) !important;
                z-index: 9999 !important;
                padding: 10px 0 !important;
                height: auto !important;
                visibility: visible !important;
                opacity: 1 !important;
            }

            .sidebar.collapsed .nav-item:hover > .collapse .nav {
                padding-left: 0 !important;
                padding-top: 0 !important;
                margin-left: 0 !important;
            }

            .sidebar.collapsed .nav-item:hover > .collapse .nav-link {
                padding: 10px 20px !important;
                margin: 2px 10px !important;
                text-align: left !important;
                display: flex !important;
                justify-content: flex-start !important;
                border-radius: 8px !important;
            }

            .sidebar.collapsed .nav-item:hover > .collapse .nav-link span {
                display: inline !important;
                font-size: 0.9rem !important;
            }

            .sidebar.collapsed .nav-item:hover > .collapse .nav-link i {
                margin-right: 12px !important;
                font-size: 1.1rem !important;
                width: 24px !important;
            }
            
            /* Hide the normal transition for collapse when hovered in collapsed sidebar */
            .sidebar.collapsed .nav-item:hover > .collapse.collapsing {
                transition: none !important;
                height: auto !important;
                display: block !important;
            }
        }
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

        .top-bar .title {
            color: #1e293b;
        }

        /* User info styles */
        .admin-info {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 5px;
            border-radius: 5px;
            transition: background-color 0.2s;
            color: #1e293b;
        }

        .admin-info:hover {
            background-color: #f7f8fb;
            color: #1e293b;
        }

        .admin-avatar,
        .staff-avatar,
        .avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #f58320;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            letter-spacing: -0.03em;
            border: 2px solid #f58320;
        }

        .admin-name {
            font-weight: 500;
        }

        /* Dropdown menu styles */
        .dropdown-toggle {
            cursor: pointer;
        }

        .dropdown-toggle::after {
            display: none;
        }

        .dropdown-menu {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 8px 0;
            margin-top: 10px;
            min-width: 200px;
        }

        .dropdown-item {
            padding: 8px 16px;
            display: flex;
            align-items: center;
        }

        .dropdown-item:hover {
            background-color: var(--primary-100);
        }

        .dropdown-item i {
            font-size: 1rem;
        }

        /* Main content styles */
        .main-content {
            margin-left: 270px;
            margin-top: 70px;
            padding: 32px;
            background-color: var(--page-bg);
            min-height: calc(100vh - 70px);
            width: calc(100% - 270px);
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }



        .main-content.collapsed {
            margin-left: 80px;
            width: calc(100% - 80px);
        }

        /* Card styles */
        .stat-card,
        .widget-container {
            background: var(--surface);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            padding: 1.5rem;
            height: 100%;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover,
        .widget-container:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-bottom: 5px;
        }

        .stat-label {
            color: var(--muted);
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 5px;
        }

        .stat-change {
            color: var(--accent);
            font-size: 13px;
        }

        .stat-change-alert {
            color: var(--danger-text);
            font-size: 13px;
        }

        /* Tab navigation */
        .content-tabs {
            display: flex;
            border-bottom: 1px solid var(--border);
            margin-bottom: 20px;
        }

        .content-tab {
            padding: 10px 20px;
            cursor: pointer;
            font-weight: 500;
            color: var(--muted-2);
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
        }

        .content-tab.active {
            color: #f58320;
            border-bottom-color: #f58320;
            font-weight: 600;
        }

        .content-tab:hover:not(.active) {
            color: #f58320;
            border-bottom-color: var(--border);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Chart cards */
        .chart-card {
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
        }

        .chart-header {
            background-color: var(--surface);
            padding: 1.25rem;
            border-bottom: 1px solid var(--border);
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }

        .chart-container {
            position: relative;
            height: 300px;
            padding: 1.5rem;
        }

        /* Recent sales */
        .recent-sales-card {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border-radius: 10px;
            height: 380px;
            width: 100%;
        }

        .avatar {
            width: 40px;
            height: 40px;
            margin-right: 15px;
        }

        .amount {
            font-weight: bold;
            color: var(--accent);
        }

        /* Widget components */
        .widget-header h6 {
            font-size: 1.25rem;
            margin-bottom: 5px;
            font-weight: 600;
            color: var(--text);
            letter-spacing: -0.02em;
        }

        .widget-header p {
            font-size: 0.875rem;
            color: var(--muted-2);
            margin-bottom: 0;
        }

        /* Item rows in widgets */
        .item-row {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .item-details {
            flex-grow: 1;
            margin-right: 10px;
        }

        .item-details h6 {
            font-size: 1rem;
            margin-bottom: 3px;
            color: var(--text);
        }

        .item-details p {
            font-size: 0.875rem;
            color: var(--muted);
            margin-bottom: 0;
        }

        /* Status badges */
        .status-badge {
            padding: 0.35rem 0.65rem;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 500;
            white-space: nowrap;
        }

        .in-stock {
            background-color: #d1e7dd;
            color: var(--success-text);
        }

        .low-stock {
            background-color: var(--warning-bg);
            color: var(--warning-text);
        }

        .out-of-stock {
            background-color: var(--danger-bg);
            color: var(--danger-text);
        }

        /* Progress bars */
        .progress {
            height: 0.5rem;
            margin-top: 5px;
            background-color: var(--muted-3);
            border-radius: 0.25rem;
            overflow: hidden;
        }

        .progress-bar {
            height: 0.5rem;
        }

        /* Scrollable containers */
        .inventory-container,
        .staff-sales-container,
        .chart-scroll-container {
            scrollbar-width: thin;
            scrollbar-color: #dee2e6 #f8f9fa;
            max-height: 400px;
            overflow-y: auto;
        }

        .chart-scroll-container {
            width: 100%;
            overflow-x: auto;
        }

        .inventory-container::-webkit-scrollbar,
        .staff-sales-container::-webkit-scrollbar,
        .chart-scroll-container::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        .inventory-container::-webkit-scrollbar-track,
        .staff-sales-container::-webkit-scrollbar-track,
        .chart-scroll-container::-webkit-scrollbar-track {
            background: #f8f9fa;
            border-radius: 10px;
        }

        .inventory-container::-webkit-scrollbar-thumb,
        .staff-sales-container::-webkit-scrollbar-thumb,
        .chart-scroll-container::-webkit-scrollbar-thumb {
            background-color: var(--muted-3);
            border-radius: 10px;
        }

        .modal-backdrop.show {
            z-index: 1040 !important;
        }

        .modal.show {
            z-index: 1050 !important;
        }
        .table-responsive {
            min-height: 50vh;
            overflow-y: auto;
        }

        .table th {
            border-top: none;
            font-weight: 600;
            color: var(--text-muted);
            background: var(--border-light);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 12px 16px !important;
        }

        .btn {
            border-radius: 8px;
            font-weight: 500;
            padding: 8px 16px !important;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: var(--primary);
            color: #ffffff;
            border: none;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .btn-primary:hover {
            background: var(--primary-600);
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .btn-success {
            background: #198754;
            color: #ffffff;
        }

        .btn-danger {
            background: #dc3545;
            color: #ffffff;
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
        }

        .btn-secondary {
            background: #6c757d;
            color: #ffffff;
        }

        .btn-outline-primary {
            background: transparent;
            color: #f58320;
            border: 1px solid #f58320;
        }

        .btn-outline-primary:hover {
            background: #f58320;
            color: #ffffff;
        }

        .btn-outline-secondary {
            background: transparent;
            color: #6c757d;
            border: 1px solid #6c757d;
        }

        .btn-outline-danger {
            background: transparent;
            color: #dc3545;
            border: 1px solid #dc3545;
        }

        .modal-header {
            background: linear-gradient(135deg, #f58320 0%, #d16d0e 100%);
            color: #ffffff;
        }




        /* Responsive styles */
        @media (max-width: 767.98px) {
            #sidebarToggler {
                width: 50px !important;
                height: 50px !important;
                
            }

            #sidebarToggler i {
                font-size: 1.75rem !important;
            }

            .sidebar {
                transform: translateX(-100%);
                width: 250px;
                /* Ensure sidebar takes full height but allows scrolling on mobile */
                height: 100%;
                bottom: 0;
                top: 0;
                overflow-y: auto;
            }

            .sidebar.show {
                transform: translateX(0);
                box-shadow: 2px 0 10px rgba(0, 0, 0, 0.2);
            }

            .sidebar.collapsed.show {
                width: 250px;
            }
            .none{
                display:none;

            }

            .top-bar {
                left: 0;
            }

            .main-content {
                margin-left: 0;
                width: 100%;
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
            <hr style="color:#fff;">
            <ul class="nav flex-column">

                <li>
                    <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                        <i class="bi bi-speedometer2"></i> <span>Overview</span>
                    </a>
                </li>
                {{--
                <li class="nav-item">
                    <a class="nav-link dropdown-toggle" href="#hrSubmenu" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="hrSubmenu">
                        <i class="bi bi-people"></i> <span>HR Management</span>
                    </a>
                    <div class="collapse" id="hrSubmenu">
                        <ul class="nav flex-column ms-3">
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('admin.manage-admin') }}">
                                    <i class="bi bi-shield-lock"></i> <span>Manage Admin</span>
                                </a>
                            </li>
                            <!-- Disabled: Manage Staff -->
                            <!-- Disabled: Staff Attendance -->
                            <li class="nav-item">
                                <a class="nav-link py-2 disabled" href="#">
                                    <i class="bi bi-calendar-check"></i> <span>Staff Attendance</span>
                                </a>
                            </li>
                            <!-- Disabled: Loan Management -->
                            <li class="nav-item">
                                <a class="nav-link py-2 disabled" href="#">
                                    <i class="bi bi-credit-card"></i> <span>Loan Management</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('admin.manage-customer') }}">
                                    <i class="bi bi-people"></i> <span>Manage Customer</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                --}}
                
                <li class="nav-item">
                    <a class="nav-link dropdown-toggle" href="#inventorySubmenu" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="inventorySubmenu">
                        <i class="bi bi-basket3"></i> <span>Products</span>
                    </a>
                    <div class="collapse" id="inventorySubmenu">
                        <ul class="nav flex-column ms-3">
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('admin.Productes') }}">
                                    <i class="bi bi-card-list"></i> <span>List Product</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('admin.Product-brand') }}">
                                    <i class="bi bi-tags"></i> <span>Product Brand</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('admin.Product-category') }}">
                                    <i class="bi bi-tags-fill"></i> <span>Product Category</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('admin.manage-variants') }}">
                                    <i class="bi bi-layers"></i> <span>Product Variant</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                <li class="nav-item">
                    <a class="nav-link dropdown-toggle" href="#salesSubmenu" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="salesSubmenu">
                        <i class="bi bi-cash-stack"></i> <span>Sales</span>
                    </a>
                    <div class="collapse" id="salesSubmenu">
                        <ul class="nav flex-column ms-3">
                            {{--<li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('admin.sales-system') }}">
                                    <i class="bi bi-plus-circle"></i> <span>Add Sales</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('admin.sales-list') }}">
                                    <i class="bi bi-table"></i> <span>List Sales</span>
                                </a>
                            </li>--}}
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('admin.pos-sales') }}">
                                    <i class="bi bi-shop"></i> <span>POS Sales list</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.sale-approval') ? 'active' : '' }}" href="{{ route('admin.sale-approval') }}">
                                    <i class="bi bi-check-circle"></i> <span>Staff Sale List</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('admin.quotation-list') }}">
                                    <i class="bi bi-card-list"></i> <span>Quotation</span>
                                </a>
                            </li>
                            {{-- no need him  --}}
                            {{--
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('admin.due-payments') }}">
                                    <i class="bi bi-cash-coin"></i> <span>Due Payments</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('admin.view-payments') }}">
                                    <i class="bi bi-credit-card-2-back"></i> <span>View Payments</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('admin.return-product') }}">
                                    <i class="bi bi-collection"></i> <span>Return Product</span>
                                </a>
                            </li>
                            --}}
                        </ul>
                    </div>
                </li>
                <li class="nav-item">
                    <a class="nav-link dropdown-toggle" href="#peopleSubmenu" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="peopleSubmenu">
                        <i class="bi bi-people-fill"></i> <span>People</span>
                    </a>
                    <div class="collapse" id="peopleSubmenu">
                        <ul class="nav flex-column ms-3">
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('admin.supplier-management') }}">
                                    <i class="bi bi-people"></i> <span>List Suppliers</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('admin.manage-customer') }}">
                                    <i class="bi bi-person-lines-fill"></i> <span>List Customer</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-2 " href="{{ route('admin.manage-staff') }}">
                                    <i class="bi bi-person-badge"></i> <span>List Staff</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                <li class="nav-item">
                    <a class="nav-link dropdown-toggle" href="#purchaseSubmenu" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="purchaseSubmenu">
                        <i class="bi bi-truck"></i><span>Purchase</span>
                    </a>
                    <div class="collapse" id="purchaseSubmenu">
                        <ul class="nav flex-column ms-3">
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('admin.purchase-order-list') }}">
                                    <i class="bi bi-journal-bookmark"></i> <span>Purchase Order</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('admin.grn') }}">
                                    <i class="bi bi-boxes"></i><span>GRN</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                <li class="nav-item">
                    <a class="nav-link dropdown-toggle" href="#paymentSubmenu" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="paymentSubmenu">
                        <i class="bi bi-receipt-cutoff"></i> <span>Payment Management</span>
                    </a>
                    <div class="collapse" id="paymentSubmenu">
                        <ul class="nav flex-column ms-3">
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('admin.add-customer-receipt') }}">
                                    <i class="bi bi-person-plus"></i> <span>Add Customer Receipt</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('admin.list-customer-receipt') }}">
                                    <i class="bi bi-people-fill"></i> <span>List Customer Receipt</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('admin.add-supplier-receipt') }}">
                                    <i class="bi bi-truck-flatbed"></i> <span>Add Supplier Payment</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('admin.list-supplier-receipt') }}">
                                    <i class="bi bi-clipboard-data"></i> <span>List Supplier Payment</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link dropdown-toggle" href="#returnSubmenu" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="returnSubmenu">
                        <i class="bi bi-arrow-counterclockwise"></i> <span>Return</span>
                    </a>
                    <div class="collapse" id="returnSubmenu">
                        <ul class="nav flex-column ms-3">
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('admin.return-product') }}">
                                    <i class="bi bi-arrow-return-left"></i> <span>Add Customer Return</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('admin.return-list') }}">
                                    <i class="bi bi-list-check"></i> <span>List Customer Return</span>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('admin.return-supplier') }}">
                                    <i class="bi bi-arrow-return-left"></i> <span>Add Supplier Return</span>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('admin.list-supplier-return') }}">
                                    <i class="bi bi-list-check"></i> <span>List Supplier Return</span>
                                </a>
                            </li>

                        </ul>
                    </div>
                </li>
                
                {{-- // Finance --}}
                <li class="nav-item">
                    <a class="nav-link dropdown-toggle" href="#banksSubmenu" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="banksSubmenu">
                        <i class="bi bi-bank"></i> <span>Finance</span>
                    </a>
                    <div class="collapse" id="banksSubmenu">
                        <ul class="nav flex-column ms-3">
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('admin.day-summary') }}">
                                    <i class="bi bi-cash-stack"></i> <span>Deposit By Cash</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('admin.cheque-list') }}">
                                    <i class="bi bi-card-text"></i> <span>Cheque List</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('admin.return-cheque') }}">
                                    <i class="bi bi-arrow-left-right"></i> <span>Return Cheque</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-2" href="{{ route('admin.expenses') }}">
                                    <i class="bi bi-wallet2"></i> <span>List Expenses</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                <!-- //add financing -->
                
                <li class="nav-item">
                    <a class="nav-link dropdown-toggle" href="#staffSubmenu" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="staffSubmenu">
                        <i class="bi bi-file-earmark-text"></i> <span>Staff Management</span>
                    </a>
                    <div class="collapse" id="staffSubmenu">
                        <ul class="nav flex-column ms-3">
                            {{--<li class="nav-item">
                                <a class="nav-link py-2 {{ request()->routeIs('admin.staff-sales') ? 'active' : '' }}" href="{{ route('admin.staff-sales') }}">
                                    <i class="bi bi-shop"></i> <span>Staff Sales</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.staff-product-allocation') ? 'active' : '' }}" href="{{ route('admin.staff-product-allocation') }}">
                                    <i class="bi bi-person-badge"></i> <span>Staff Allocation</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.staff-allocated-list', 'admin.staff-allocated-products.view', 'admin.staff-product-reentry') ? 'active' : '' }}" href="{{ route('admin.staff-allocated-list') }}">
                                    <i class="bi bi-list-check"></i> <span>Allocated List</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-2 {{ request()->routeIs('admin.staff-payment-approval') ? 'active' : '' }}" href="{{ route('admin.staff-payment-approval') }}">
                                    <i class="bi bi-credit-card"></i> <span>Staff Payment List</span>
                                </a>
                            </li>--}}
                            
                            <li class="nav-item">
                                <a class="nav-link py-2 {{ request()->routeIs('admin.payment-approval') ? 'active' : '' }}" href="{{ route('admin.payment-approval') }}">
                                    <i class="bi bi-wallet2"></i> <span>Staff Payment List</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-2 {{ request()->routeIs('admin.staff-expense-approval') ? 'active' : '' }}" href="{{ route('admin.staff-expense-approval') }}">
                                    <i class="bi bi-receipt"></i> <span>Staff Expenses</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-2 {{ request()->routeIs('admin.staff-salary-management') ? 'active' : '' }}" href="{{ route('admin.staff-salary-management') }}">
                                    <i class="bi bi-cash-coin"></i> <span>Staff Salary</span>
                                </a>
                            </li>
                            
                        </ul>
                    </div>
                </li>
                
                
                <li>
                    <a class="nav-link" href="{{ route('admin.income') }}">
                        <i class="bi bi-file-earmark-bar-graph"></i> <span>Day Summary</span>
                    </a>
                </li>
                <li>
                    <a class="nav-link" href="{{ route('admin.reports') }}">
                        <i class="bi bi-file-earmark-bar-graph"></i> <span>Reports</span>
                    </a>
                </li>
                <li>
                    <a class="nav-link" href="{{ route('admin.analytics') }}">
                        <i class="bi bi-bar-chart"></i> <span>Analytics</span>
                    </a>
                </li>
                <li>
                    <a class="nav-link" href="{{ route('admin.profit-loss') }}">
                        <i class="bi bi-graph-up-arrow"></i> <span>Profit & Loss</span>
                    </a>
                </li>
                <li>
                    <a class="nav-link" href="{{ route('admin.settings') }}">
                        <i class="bi bi-gear"></i> <span>Settings</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Top Navigation Bar -->
        <nav class="top-bar d-flex justify-content-between align-items-center">
            <!-- Sidebar toggle button -->
            <button id="sidebarToggler" class="btn btn-light d-flex align-items-center justify-content-center p-0" style="width: 40px; height: 40px; border-radius: 10px; border: 1px solid var(--border);">
                <i class="bi bi-text-indent-left fs-4 text-dark" id="togglerIcon"></i>
            </button>

            <!-- Centered Company Name (hidden on small screens) -->
            <div class="flex-grow-1 d-none d-md-flex justify-content-center">
                <h5 class="m-0 fw-bold" style="letter-spacing: -0.02em; color:#f58320;">HARDMEN (PVT) LTD</h5>
            </div>
            @php
            use App\Models\CashInHand as CashModel;
            $cashInHand = CashModel::where('key', 'cash in hand')->value('value') ?? 0;
            @endphp

            <!-- Real-time Clock and POS Button -->
            <div class="d-none d-lg-flex align-items-center gap-4 me-3">
                <div id="digitalClock" class="fw-800 font-monospace text-orange px-3 py-2 rounded-3 bg-white border shadow-sm" 
                    style="font-size: 1.5rem; letter-spacing: 0.1em; border-color: rgba(245, 131, 32, 0.2); min-width: 150px; text-align: center;">
                    00:00:00
                </div>
                
                <div class="btn btn-primary rounded-pill shadow-sm d-flex align-items-center gap-2 px-3 py-2"
                    onclick="handlePOSClick()"
                    role="button"
                    style="background: var(--primary); border: none;">
                    <i class="bi bi-cart-plus-fill fs-5"></i>
                    <span class="fw-bold">POS</span>
                </div>
            </div>

            <!-- Reopen POS Button (visible only if today's POS session is closed) -->
            <div id="reopenPosBtnContainer" style="display:none;">
                <button type="button" class=" rounded-pill shadow-sm border border-opacity-25 d-flex align-items-center gap-2 me-2"
                    style="font-size: 0.9rem; background-color:white; color:red; cursor: pointer;"
                    onclick="showReopenPOSModal()">
                    <i class="bi bi-unlock"></i>
                </button>
            </div>



            <!-- Admin dropdown -->
            <div class="dropdown ms-auto">
                <div class="admin-info dropdown-toggle" id="adminDropdown" role="button" data-bs-toggle="dropdown"
                    aria-expanded="false">
                    <div class="admin-avatar">A</div>
                    <div class="admin-name">Admin</div>
                </div>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminDropdown">
                    <li>
                        <a class="dropdown-item" href="{{ route('profile.show') }}">
                            <i class="bi bi-person me-2"></i>My Profile
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('admin.settings') }}">
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
        <!-- Modal for Updating Cash-in-Hand -->
        <div class="modal fade" id="editCashAdminModal" tabindex="-1" aria-labelledby="editCashAdminModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-wallet2 text-warning me-2"></i> Open POS Session - {{ date('M d, Y') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <form action="{{ route('admin.updateCashInHand') }}" method="POST" id="cashInHandForm">
                        @csrf
                        <div class="modal-body">
                            @php
                            // Get yesterday's closing cash
                            use App\Models\POSSession;
                            use Carbon\Carbon;

                            $yesterday = Carbon::yesterday();
                            $yesterdaySession = POSSession::where('user_id', Auth::id())
                            ->where('session_date', $yesterday)
                            ->where('status', 'closed')
                            ->first();

                            $lastClosingCash = $yesterdaySession ? $yesterdaySession->closing_cash : $cashInHand;
                            @endphp

                            <!-- Amount Input -->
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Opening Cash Amount *</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">Rs.</span>
                                    <input type="number"
                                        step="0.01"
                                        class="form-control form-control-lg"
                                        name="newCashInHand"
                                        id="openingCashInput"
                                        value="{{ number_format($lastClosingCash, 2, '.', '') }}"
                                        placeholder="Enter opening cash amount"
                                        required>
                                </div>
                                <div class="form-text">
                                    Enter the cash amount to start your POS session
                                </div>
                            </div>

                            <!-- Previous Day Info -->
                            @if($yesterdaySession)
                            <div class="alert alert-info mb-3">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Yesterday's Closing Cash:</strong> Rs. {{ number_format($yesterdaySession->closing_cash, 2) }}
                                <br>
                                <small class="text-muted">{{ $yesterday->format('M d, Y') }}</small>
                            </div>
                            @endif

                            <!-- Current Cash in Hand -->
                            <div class="p-3 bg-success bg-opacity-10 rounded-3 border border-success border-opacity-25">
                                <h6 class="fw-bold text-dark mb-2">
                                    <i class="bi bi-calculator text-success me-2"></i> System Cash in Hand
                                </h6>
                                <div class="d-flex justify-content-between small">
                                    <span class="text-muted">Current Balance:</span>
                                    <span class="fw-semibold text-success">Rs. {{ number_format($cashInHand, 2) }}</span>
                                </div>
                            </div>

                        </div>

                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success text-white">
                                <i class="bi bi-check2-circle me-1"></i> Open POS Session
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>


        <!-- Main Content -->
        <main class="main-content">
            {{ $slot }}
        </main>

        <!-- Reopen POS Confirmation Modal -->
        <div class="modal fade" id="reopenPOSModal" tabindex="-1" aria-labelledby="reopenPOSModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="reopenPOSModalLabel">
                            <i class="bi bi-unlock me-2"></i> Reopen POS Session
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to change today's POS session status from <strong>Closed</strong> to <strong>Open</strong>?<br>This will allow new POS sales for today.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger" onclick="reopenPOSSession()">Yes, Reopen POS</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 from CDN (only need this one line) -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Include jQuery (required by Bootstrap 4 modal) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    @livewireScripts
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Define all elements once
            const sidebarToggler = document.getElementById('sidebarToggler');
            const sidebar = document.querySelector('.sidebar');
            const topBar = document.querySelector('.top-bar');
            const mainContent = document.querySelector('.main-content');

            // Digital Clock
            function updateClock() {
                const clockEl = document.getElementById('digitalClock');
                if (clockEl) {
                    const now = new Date();
                    const hours = String(now.getHours()).padStart(2, '0');
                    const minutes = String(now.getMinutes()).padStart(2, '0');
                    const seconds = String(now.getSeconds()).padStart(2, '0');
                    clockEl.textContent = `${hours}:${minutes}:${seconds}`;
                }
            }

            // Update clock every second
            updateClock();
            if (window.adminClockInterval) clearInterval(window.adminClockInterval);
            window.adminClockInterval = setInterval(updateClock, 1000);

            // Livewire hook for clock re-initialization
            if (typeof Livewire !== 'undefined') {
                Livewire.hook('message.processed', () => {
                    updateClock();
                });
            }

            // Initialize sidebar state
            function initializeSidebar() {
                // Check if sidebar state is saved in localStorage
                const sidebarCollapsed = localStorage.getItem('sidebar-collapsed') === 'true';
                const togglerIcon = document.getElementById('togglerIcon');

                if (sidebarCollapsed && window.innerWidth >= 768) {
                    sidebar.classList.add('collapsed');
                    topBar.classList.add('collapsed');
                    mainContent.classList.add('collapsed');
                    if (togglerIcon) {
                        togglerIcon.classList.replace('bi-text-indent-left', 'bi-text-indent-right');
                    }
                }

                // On mobile, always start with sidebar hidden
                if (window.innerWidth < 768) {
                    sidebar.classList.remove('show');
                    topBar.classList.remove('collapsed');
                    mainContent.classList.remove('collapsed');
                }
            }

            // Toggle sidebar function
            function toggleSidebar(event) {
                if (event) {
                    event.stopPropagation();
                }

                const togglerIcon = document.getElementById('togglerIcon');

                if (window.innerWidth < 768) {
                    // Mobile behavior
                    sidebar.classList.toggle('show');
                } else {
                    // Desktop behavior
                    sidebar.classList.toggle('collapsed');
                    topBar.classList.toggle('collapsed');
                    mainContent.classList.toggle('collapsed');

                    // Update toggler icon
                    if (togglerIcon) {
                        if (sidebar.classList.contains('collapsed')) {
                            togglerIcon.classList.replace('bi-text-indent-left', 'bi-text-indent-right');
                        } else {
                            togglerIcon.classList.replace('bi-text-indent-right', 'bi-text-indent-left');
                        }
                    }

                    // Save state to localStorage
                    localStorage.setItem('sidebar-collapsed', sidebar.classList.contains('collapsed'));
                }
            }

            // Handle sidebar interactions
            if (sidebarToggler && sidebar) {
                // Initialize sidebar
                initializeSidebar();

                // Set up single click handler
                sidebarToggler.addEventListener('click', toggleSidebar);

                // Close sidebar when clicking outside on mobile
                document.addEventListener('click', function(event) {
                    if (window.innerWidth < 768 &&
                        sidebar.classList.contains('show') &&
                        !sidebar.contains(event.target) &&
                        !sidebarToggler.contains(event.target)) {
                        sidebar.classList.remove('show');
                    }
                });

                // Handle window resize
                window.addEventListener('resize', function() {
                    if (window.innerWidth >= 768) {
                        sidebar.classList.remove('show');

                        // Restore collapsed state on desktop
                        const sidebarCollapsed = localStorage.getItem('sidebar-collapsed') === 'true';
                        if (sidebarCollapsed) {
                            sidebar.classList.add('collapsed');
                            topBar.classList.add('collapsed');
                            mainContent.classList.add('collapsed');
                        } else {
                            sidebar.classList.remove('collapsed');
                            topBar.classList.remove('collapsed');
                            mainContent.classList.remove('collapsed');
                        }
                    } else {
                        // On mobile, remove collapsed styles
                        topBar.classList.remove('collapsed');
                        mainContent.classList.remove('collapsed');
                    }
                });
            }

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

            // Chart Initialization with proper cleanup
            const salesChartEl = document.getElementById('salesChart');
            let salesChart = null;

            function initializeChart() {
                if (salesChartEl) {
                    // Destroy existing chart if it exists
                    if (window.salesChart) {
                        window.salesChart.destroy();
                    }

                    const ctx = salesChartEl.getContext('2d');
                    window.salesChart = new Chart(ctx, {
                        // Your existing chart configuration
                        type: 'line',
                        data: {
                            // Your chart data
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                }
            }

            // Initialize chart
            initializeChart();

            // Re-initialize chart when Livewire updates
            document.addEventListener('livewire:load', function() {
                Livewire.hook('message.processed', () => {
                    initializeChart();
                });
            });

            // General submenu activation logic
            function activateParentMenuIfSubmenuActive(parentToggleSelector, submenuSelector) {
                const parentToggle = document.querySelector(parentToggleSelector);
                const submenu = document.querySelector(submenuSelector);
                const submenuLinks = submenu ? submenu.querySelectorAll('.nav-link') : [];

                let active = false;
                const currentPath = window.location.pathname;

                submenuLinks.forEach(link => {
                    const href = link.getAttribute('href');
                    if (href && href !== '#') {
                        const hrefPath = href.replace(/^(https?:\/\/[^\/]+)/, '').split('?')[0];
                        const linkIsActive = currentPath === hrefPath ||
                            currentPath.endsWith(hrefPath) ||
                            currentPath.includes(hrefPath);

                        if (linkIsActive) {
                            link.classList.add('active');
                            active = true;
                        }
                    }
                });

                if (active && parentToggle && submenu) {
                    // Remove active from all main nav links
                    document.querySelectorAll('.sidebar > .nav > .nav-item > .nav-link:not(.dropdown-toggle)').forEach(link => {
                        link.classList.remove('active');
                    });
                    parentToggle.classList.add('active');
                    parentToggle.setAttribute('aria-expanded', 'true');
                    submenu.classList.add('show');
                }
            }

            // Initialize both HR and Inventory submenus
            activateParentMenuIfSubmenuActive('a[href="#hrSubmenu"]', '#hrSubmenu');
            activateParentMenuIfSubmenuActive('a[href="#inventorySubmenu"]', '#inventorySubmenu');
            activateParentMenuIfSubmenuActive('a[href="#salesSubmenu"]', '#salesSubmenu');
            activateParentMenuIfSubmenuActive('a[href="#stockSubmenu"]', '#stockSubmenu');
            activateParentMenuIfSubmenuActive('a[href="#purchaseSubmenu"]', '#purchaseSubmenu');

            // Replace the existing submenu activation logic with this comprehensive function
            function setActiveMenuItem() {
                // Get current path
                const currentPath = window.location.pathname;

                // First clear all active states
                document.querySelectorAll('.sidebar .nav-link').forEach(link => {
                    link.classList.remove('active');
                });

                // Reset all expanded states for dropdowns
                document.querySelectorAll('.collapse').forEach(submenu => {
                    submenu.classList.remove('show');
                });
                document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
                    toggle.setAttribute('aria-expanded', 'false');
                });

                // Check for exact match first (highest priority)
                let activeFound = false;

                // First try to find exact matches
                document.querySelectorAll('.sidebar .nav-link').forEach(link => {
                    const href = link.getAttribute('href');
                    if (href && href !== '#' && !href.startsWith('#')) {
                        const hrefPath = href.replace(/^(https?:\/\/[^\/]+)/, '').split('?')[0];

                        // Exact match gets priority
                        if (currentPath === hrefPath) {
                            link.classList.add('active');
                            activeFound = true;

                            // If this is a submenu link, expand its parent
                            const submenu = link.closest('.collapse');
                            if (submenu) {
                                submenu.classList.add('show');
                                const parentToggle = document.querySelector(`[href="#${submenu.id}"]`);
                                if (parentToggle) {
                                    parentToggle.classList.add('active');
                                    parentToggle.setAttribute('aria-expanded', 'true');
                                }
                            }
                        }
                    }
                });

                // If no exact match was found, try partial matches
                if (!activeFound) {
                    document.querySelectorAll('.sidebar .nav-link').forEach(link => {
                        const href = link.getAttribute('href');
                        if (href && href !== '#' && !href.startsWith('#')) {
                            const hrefPath = href.replace(/^(https?:\/\/[^\/]+)/, '').split('?')[0];

                            // Skip root path to avoid false positives
                            if (hrefPath !== '/' && currentPath.includes(hrefPath)) {
                                link.classList.add('active');

                                // If this is a submenu link, expand its parent
                                const submenu = link.closest('.collapse');
                                if (submenu) {
                                    submenu.classList.add('show');
                                    const parentToggle = document.querySelector(`[href="#${submenu.id}"]`);
                                    if (parentToggle) {
                                        parentToggle.classList.add('active');
                                        parentToggle.setAttribute('aria-expanded', 'true');
                                    }
                                }
                            }
                        }
                    });
                }

                // Special handling for Staff Allocation related routes
                // If current path is staff-allocated-products or staff-product-reentry, activate the allocated-list link
                if (currentPath.includes('/staff-allocated-products/') || currentPath.includes('/staff-product-reentry/')) {
                    const allocatedListLink = document.querySelector('a[href*="/staff-allocated-list"]');
                    if (allocatedListLink) {
                        allocatedListLink.classList.add('active');
                        
                        // Also activate and expand the parent Staff Management menu
                        const staffSubmenu = document.querySelector('#staffSubmenu');
                        const staffParentToggle = document.querySelector('a[href="#staffSubmenu"]');
                        
                        if (staffSubmenu && staffParentToggle) {
                            staffSubmenu.classList.add('show');
                            staffParentToggle.classList.add('active');
                            staffParentToggle.setAttribute('aria-expanded', 'true');
                        }
                    }
                }

                // Special handling for Quotation related routes
                // If current path is quotation-system or quotation-list, activate the quotation link and expand Sales menu
                if (currentPath.includes('/quotation-system') || currentPath.includes('/quotation-list')) {
                    const quotationLink = document.querySelector('a[href*="/quotation-list"]');
                    if (quotationLink) {
                        quotationLink.classList.add('active');
                        
                        // Also activate and expand the parent Sales menu
                        const salesSubmenu = document.querySelector('#salesSubmenu');
                        const salesParentToggle = document.querySelector('a[href="#salesSubmenu"]');
                        
                        if (salesSubmenu && salesParentToggle) {
                            salesSubmenu.classList.add('show');
                            salesParentToggle.classList.add('active');
                            salesParentToggle.setAttribute('aria-expanded', 'true');
                        }
                    }
                }
            }

            // Add this at the end of your document.addEventListener('DOMContentLoaded') function
            setActiveMenuItem();

            // Function to handle sidebar height and scrolling
            function adjustSidebarHeight() {
                const sidebar = document.querySelector('.sidebar');
                const windowHeight = window.innerHeight;

                if (sidebar) {
                    // Ensure the sidebar takes the full viewport height
                    sidebar.style.height = windowHeight + 'px';

                    // Check if content is taller than viewport
                    const sidebarContent = sidebar.querySelector('.nav.flex-column');
                    if (sidebarContent && sidebarContent.scrollHeight > windowHeight) {
                        // Add a class to indicate scrollable content
                        sidebar.classList.add('scrollable');
                    } else {
                        sidebar.classList.remove('scrollable');
                    }
                }
            }

            // Run on load and resize
            adjustSidebarHeight();
            window.addEventListener('resize', adjustSidebarHeight);
        });

        //cash in hand popup model 
        window.addEventListener('close-modal', event => {
            const modalId = event.detail.modalId;
            const modal = bootstrap.Modal.getInstance(document.getElementById(modalId));
            if (modal) modal.hide();
        });


        // Check POS session status and update UI (show/hide Reopen POS button)
        function checkPOSSessionStatus() {
            fetch("{{ route('admin.check-pos-session') }}", {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                .then(response => response.json())
                .then(data => {
                    // Show Reopen POS button if session is closed
                    const reopenBtnContainer = document.getElementById('reopenPosBtnContainer');
                    if (data.closed === true) {
                        if (reopenBtnContainer) reopenBtnContainer.style.display = '';
                    } else {
                        if (reopenBtnContainer) reopenBtnContainer.style.display = 'none';
                    }
                })
                .catch(() => {
                    // On error, hide button
                    const reopenBtnContainer = document.getElementById('reopenPosBtnContainer');
                    if (reopenBtnContainer) reopenBtnContainer.style.display = 'none';
                });
        }

        // Call on page load
        document.addEventListener('DOMContentLoaded', checkPOSSessionStatus);

        // Handle POS button click - check if closed before redirecting
        function handlePOSClick() {
            fetch("{{ route('admin.check-pos-session') }}", {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.closed === true) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Register Already Closed',
                            text: 'The POS register has already been closed for today. You cannot access the POS system again until tomorrow.',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#2a83df'
                        });
                        return;
                    }
                    window.open("{{ route('admin.store-billing') }}", '_blank');
                })
                .catch(() => {
                    window.open("{{ route('admin.store-billing') }}", '_blank');
                });
        }

        // Show Reopen POS modal
        function showReopenPOSModal() {
            const modal = new bootstrap.Modal(document.getElementById('reopenPOSModal'));
            modal.show();
        }

        // Handle Reopen POS confirmation
        function reopenPOSSession() {
            // Hide modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('reopenPOSModal'));
            if (modal) modal.hide();

            // Redirect to store billing page
            // The mount() method will detect closed session and show opening cash modal
            window.location.href = "{{ route('admin.store-billing') }}";
        }

        // Update the form submission to redirect to POS after updating cash
        document.getElementById('cashInHandForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch(this.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Close modal
                        const modal = bootstrap.Modal.getInstance(document.getElementById('editCashAdminModal'));
                        modal.hide();

                        // Show success message and redirect to POS
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: 'Cash-in-Hand updated successfully!',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            // Redirect to store billing page in same window
                            window.location.href = "{{ route('admin.store-billing') }}";
                        });
                    } else {
                        Swal.fire('Error!', data.message || 'Failed to update cash-in-hand', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error!', 'An error occurred while updating cash-in-hand', 'error');
                });
        });
    </script>
    @stack('scripts')
</body>

</html>