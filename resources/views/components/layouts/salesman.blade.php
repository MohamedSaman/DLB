<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Salesman Portal' }} - Hardmen</title>
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

        /* Sidebar styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 270px;
            height: 100vh;
            background: linear-gradient(180deg, #cec1a5 0%, #bf9038 100%);
            color: #ffffff;
            z-index: 1030;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            overflow-y: auto;
        }

        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-track {
            background-color: transparent;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background-color: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
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

        .sidebar.collapsed::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar.collapsed .sidebar-title,
        .sidebar.collapsed .nav-link span {
            display: none;
        }

        .sidebar.collapsed .nav-link i {
            margin-right: 0;
            font-size: 1.25rem;
        }

        .sidebar.collapsed .nav-link {
            text-align: center;
            padding: 10px;
            justify-content: center;
        }

        .sidebar.collapsed .nav-link.dropdown-toggle::after {
            display: none !important;
        }

        .sidebar .nav {
            padding-bottom: 50px;
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
            color: rgba(255, 255, 255, 0.8) !important;
        }

        .nav-link.active {
            background: var(--primary-50);
            color: var(--primary) !important;
            border-left: none;
        }

        .nav-link:hover:not(.active) {
            color: var(--primary) !important;
            background: rgba(255, 255, 255, 0.1);
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

        .collapse .nav {
            padding-bottom: 0;
            padding-top: 5px;
            padding-left: 12px;
        }

        .collapse .nav-item:last-child {
            margin-bottom: 5px;
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
                left: 68px;
                top: 0;
                width: 240px;
                background: #bf9038 !important; /* Matches salesman theme gradient end */
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2) !important;
                border-radius: 12px !important;
                border: 1px solid rgba(255, 255, 255, 0.1) !important;
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
                color: #ffffff !important;
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

        /* Responsive styles */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
                width: 250px;
                height: 100%;
                bottom: 0;
                top: 0;
                overflow-y: auto;
            }

            .sidebar.show {
                transform: translateX(0);
                box-shadow: 2px 0 10px rgba(0, 0, 0, 0.2);
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
    @livewireStyles
    @stack('styles')
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
                    <a class="nav-link {{ request()->routeIs('salesman.dashboard') ? 'active' : '' }}" href="{{ route('salesman.dashboard') }}">
                        <i class="bi bi-speedometer2"></i> <span>Overview</span>
                    </a>
                </li>
                
                @php
                    $staffType = auth()->user()->staff_type ?? 'salesman';
                @endphp
                
                {{-- Sales Section --}}
                @if(\App\Models\StaffTypePermission::hasPermission($staffType, 'view_sales') || \App\Models\StaffTypePermission::hasPermission($staffType, 'create_sales'))
                <li class="nav-item">
                    <a class="nav-link dropdown-toggle" href="#salesSubmenu" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="salesSubmenu">
                        <i class="bi bi-cash-stack"></i> <span>Sales</span>
                    </a>
                    <div class="collapse" id="salesSubmenu">
                        <ul class="nav flex-column ms-3">
                            @if(\App\Models\StaffTypePermission::hasPermission($staffType, 'create_sales'))
                            <li class="nav-item">
                                <a class="nav-link py-2 {{ request()->routeIs('salesman.billing') ? 'active' : '' }}" href="{{ route('salesman.billing') }}">
                                    <i class="bi bi-plus-circle"></i> <span>Create Sale</span>
                                </a>
                            </li>
                            @endif
                            @if(\App\Models\StaffTypePermission::hasPermission($staffType, 'view_sales'))
                            <li class="nav-item">
                                <a class="nav-link py-2 {{ request()->routeIs('salesman.sales') ? 'active' : '' }}" href="{{ route('salesman.sales') }}">
                                    <i class="bi bi-table"></i> <span>My Sales</span>
                                </a>
                            </li>
                            @endif
                        </ul>
                    </div>
                </li>
                @endif
                
                {{-- Products Section --}}
                @if(\App\Models\StaffTypePermission::hasPermission($staffType, 'view_products'))
                <li class="nav-item">
                    <a class="nav-link dropdown-toggle" href="#productsSubmenu" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="productsSubmenu">
                        <i class="bi bi-basket3"></i> <span>Products</span>
                    </a>
                    <div class="collapse" id="productsSubmenu">
                        <ul class="nav flex-column ms-3">
                            <li class="nav-item">
                                <a class="nav-link py-2 {{ request()->routeIs('salesman.products') ? 'active' : '' }}" href="{{ route('salesman.products') }}">
                                    <i class="bi bi-card-list"></i> <span>Product List</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                @endif
                
                {{-- Customers Section --}}
                @if(\App\Models\StaffTypePermission::hasPermission($staffType, 'view_customers') || \App\Models\StaffTypePermission::hasPermission($staffType, 'view_customer_dues'))
                <li class="nav-item">
                    <a class="nav-link dropdown-toggle" href="#customersSubmenu" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="customersSubmenu">
                        <i class="bi bi-people"></i> <span>Customers</span>
                    </a>
                    <div class="collapse" id="customersSubmenu">
                        <ul class="nav flex-column ms-3">
                            @if(\App\Models\StaffTypePermission::hasPermission($staffType, 'view_customer_dues'))
                            <li class="nav-item">
                                <a class="nav-link py-2 {{ request()->routeIs('salesman.customer-dues') ? 'active' : '' }}" href="{{ route('salesman.customer-dues') }}">
                                    <i class="bi bi-wallet"></i> <span>Customer Dues</span>
                                </a>
                            </li>
                            @endif
                        </ul>
                    </div>
                </li>
                @endif
                
                {{-- Finance Section --}}
                @if(\App\Models\StaffTypePermission::hasPermission($staffType, 'add_expenses') || \App\Models\StaffTypePermission::hasPermission($staffType, 'view_expenses'))
                <li class="nav-item">
                    <a class="nav-link dropdown-toggle" href="#financeSubmenu" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="financeSubmenu">
                        <i class="bi bi-bank"></i> <span>Finance</span>
                    </a>
                    <div class="collapse" id="financeSubmenu">
                        <ul class="nav flex-column ms-3">
                            @if(\App\Models\StaffTypePermission::hasPermission($staffType, 'add_expenses'))
                            <li class="nav-item">
                                <a class="nav-link py-2 {{ request()->routeIs('salesman.expenses') ? 'active' : '' }}" href="{{ route('salesman.expenses') }}">
                                    <i class="bi bi-receipt"></i> <span>My Expenses</span>
                                </a>
                            </li>
                            @endif
                        </ul>
                    </div>
                </li>
                @endif
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
                <h5 class="m-0 fw-bold" style="letter-spacing: -0.02em; color:#f58320;">Salesman Portal</h5>
            </div>

            <!-- Real-time Clock -->
            <div class="d-none d-lg-flex align-items-center gap-4 me-3">
                <div id="digitalClock" class="fw-800 font-monospace text-orange px-3 py-2 rounded-3 bg-white border shadow-sm" 
                    style="font-size: 1.5rem; letter-spacing: 0.1em; border-color: rgba(245, 131, 32, 0.2); min-width: 150px; text-align: center;">
                    00:00:00
                </div>
            </div>

            <!-- Staff dropdown -->
            <div class="dropdown ms-auto">
                <div class="admin-info dropdown-toggle" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="admin-avatar">{{ substr(auth()->user()->name, 0, 1) }}</div>
                    <div class="admin-name">{{ auth()->user()->name }}</div>
                </div>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminDropdown">
                    <li>
                        <a class="dropdown-item" href="{{ route('profile.show') }}">
                            <i class="bi bi-person me-2"></i>My Profile
                        </a>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item">
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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Livewire Scripts -->
    @livewireScripts
    @stack('scripts')
    
    <script>
        // Digital Clock
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { 
                hour12: false,
                hour: '2-digit', 
                minute: '2-digit', 
                second: '2-digit' 
            });
            document.getElementById('digitalClock').textContent = timeString;
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Sidebar toggle
        document.getElementById('sidebarToggler').addEventListener('click', function() {
            const sidebar = document.querySelector('.sidebar');
            const topBar = document.querySelector('.top-bar');
            const mainContent = document.querySelector('.main-content');
            const togglerIcon = document.getElementById('togglerIcon');

            if (window.innerWidth < 1024) {
                sidebar.classList.toggle('show');
            } else {
                sidebar.classList.toggle('collapsed');
                topBar.classList.toggle('collapsed');
                mainContent.classList.toggle('collapsed');
                
                if (sidebar.classList.contains('collapsed')) {
                    togglerIcon?.classList.replace('bi-text-indent-left', 'bi-text-indent-right');
                } else {
                    togglerIcon?.classList.replace('bi-text-indent-right', 'bi-text-indent-left');
                }
            }
        });

        // Auto-expand active menu sections
        document.addEventListener('DOMContentLoaded', function() {
            const activeLinks = document.querySelectorAll('.nav-link.active');
            activeLinks.forEach(function(link) {
                const parentCollapse = link.closest('.collapse');
                if (parentCollapse) {
                    parentCollapse.classList.add('show');
                    const parentToggle = document.querySelector(`[data-bs-target="#${parentCollapse.id}"]`);
                    if (parentToggle) {
                        parentToggle.setAttribute('aria-expanded', 'true');
                        parentToggle.classList.add('active');
                    }
                }
            });
        });
    </script>
</body>
</html>