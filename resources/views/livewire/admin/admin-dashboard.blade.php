<div>
    @push('styles')
    <style>
        /* Refined Dashboard Styles */
        .stat-card {
            position: relative;
            overflow: hidden;
        }

        .stat-card .icon-bg {
            position: absolute;
            right: -20px;
            bottom: -20px;
            font-size: 8rem;
            opacity: 0.05;
            transform: rotate(-15deg);
            pointer-events: none;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-main);
            letter-spacing: -0.02em;
        }

        .stat-label {
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }

        .chart-card {
            background: white;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        .chart-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
        }

        .widget-container {
            background: white;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            padding: 1.5rem;
        }

        .inventory-item {
            padding: 12px;
            border-radius: 12px;
            transition: background 0.2s;
            border: 1px solid transparent;
        }

        .inventory-item:hover {
            background: var(--border-light);
            border-color: var(--border);
        }

        .status-badge {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .in-stock { background: #ecfdf5; color: #065f46; }
        .low-stock { background: #fffbeb; color: #92400e; }
        .out-of-stock { background: #fef2f2; color: #991b1b; }

        .progress {
            height: 6px;
            border-radius: 3px;
            background: var(--border-light);
        }

        /* ============================================================
           TODAY SUMMARY MODAL STYLES
        ============================================================ */
        .tsm-backdrop {
            position: fixed; inset: 0;
            background: rgba(10,15,30,0.65);
            backdrop-filter: blur(4px);
            z-index: 9999;
            display: flex; align-items: center; justify-content: center;
            padding: 16px;
        }
        .tsm-modal {
            background: #fff;
            border-radius: 20px;
            width: 100%; max-width: 680px;
            box-shadow: 0 24px 64px rgba(0,0,0,0.25);
            overflow: hidden;
            animation: tsm-slide-in 0.3s cubic-bezier(.4,0,.2,1);
        }
        @keyframes tsm-slide-in {
            from { opacity:0; transform: translateY(24px) scale(0.97); }
            to   { opacity:1; transform: translateY(0) scale(1); }
        }
        .tsm-header {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            padding: 20px 24px;
            display: flex; align-items: center; justify-content: space-between;
        }
        .tsm-header-left { display: flex; align-items: center; gap: 14px; }
        .tsm-header-icon {
            width: 46px; height: 46px; border-radius: 14px;
            background: rgba(255,255,255,0.15);
            display: flex; align-items: center; justify-content: center;
            font-size: 22px; color: #f0c040;
        }
        .tsm-title { margin: 0; color: #fff; font-size: 1.1rem; font-weight: 700; }
        .tsm-subtitle { margin: 0; color: rgba(255,255,255,0.55); font-size: 0.78rem; }
        .tsm-close {
            background: rgba(255,255,255,0.1); border: none;
            color: rgba(255,255,255,0.8); border-radius: 10px;
            width: 36px; height: 36px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: background 0.2s;
        }
        .tsm-close:hover { background: rgba(255,255,255,0.2); color: #fff; }
        .tsm-body { padding: 20px 24px; }

        /* Tab Bar */
        .tsm-tab-bar {
            display: flex; align-items: center; gap: 6px; flex-wrap: wrap;
            background: #f1f5f9; border-radius: 12px; padding: 5px;
            margin-bottom: 16px;
        }
        .tsm-tab {
            flex: none; padding: 7px 14px; border: none; border-radius: 9px;
            background: transparent; font-size: 0.8rem; font-weight: 600;
            color: #64748b; cursor: pointer; transition: all 0.18s;
            white-space: nowrap;
        }
        .tsm-tab:hover { background: rgba(255,255,255,0.7); color: #1e293b; }
        .tsm-tab-active {
            background: linear-gradient(135deg, #000000, #e85d04) !important;
            color: #fff !important; box-shadow: 0 2px 8px rgba(245,131,32,0.35);
        }
        .tsm-tab-divider { width: 1px; height: 24px; background: #cbd5e1; margin: 0 2px; }
        .tsm-month-select {
            flex: 1; min-width: 130px; max-width: 200px;
            padding: 6px 10px; border: 1.5px solid #e2e8f0; border-radius: 9px;
            background: #fff; font-size: 0.8rem; font-weight: 600; color: #475569;
            cursor: pointer; outline: none; transition: border-color .2s;
        }
        .tsm-month-select:hover, .tsm-month-select:focus { border-color: #000000; color: #1e293b; }
        .tsm-month-select-active { border-color: #000000 !important; background: #fff7ed !important; color: #c2410c !important; }
        .tsm-grand-banner {
            background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
            border-radius: 14px; padding: 16px 22px;
            display: flex; gap: 20px; justify-content: space-between; align-items: center;
            margin-bottom: 20px;
        }
        .tsm-grand-left, .tsm-grand-right { display: flex; flex-direction: column; gap: 2px; }
        .tsm-grand-right { text-align: right; }
        .tsm-grand-label { color: rgba(255,255,255,0.75); font-size: 0.72rem; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; }
        .tsm-grand-value { color: #fff; font-size: 1.5rem; font-weight: 800; }
        .tsm-grand-due   { color: #fde68a; font-size: 1.35rem; font-weight: 800; }
        .tsm-section-title { font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #6b7280; margin-bottom: 10px; }
        .text-indigo { color: #6366f1; }
        .tsm-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .tsm-card { border-radius: 14px; padding: 16px; border: 1.5px solid transparent; }
        .tsm-card-pos   { background: #f0fdf4; border-color: #bbf7d0; }
        .tsm-card-staff { background: #eff6ff; border-color: #bfdbfe; }
        .tsm-card-header-row { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; }
        .tsm-card-icon { width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 18px; }
        .tsm-icon-pos   { background: #dcfce7; color: #16a34a; }
        .tsm-icon-staff { background: #dbeafe; color: #2563eb; }
        .tsm-card-label { font-weight: 700; font-size: 0.9rem; color: #1f2937; }
        .tsm-card-count { font-size: 0.75rem; color: #6b7280; }
        .tsm-card-metrics { display: flex; flex-direction: column; gap: 8px; }
        .tsm-metric { display: flex; justify-content: space-between; align-items: center; }
        .tsm-metric-label { font-size: 0.78rem; color: #6b7280; }
        .tsm-metric-value { font-size: 0.85rem; font-weight: 700; }
        .tsm-pos-total      { color: #15803d; }
        .tsm-pos-collected  { color: #059669; }
        .tsm-staff-total    { color: #1d4ed8; }
        .tsm-staff-collected{ color: #2563eb; }
        .tsm-due-red        { color: #dc2626; }
        .tsm-grid-3 { display: grid; grid-template-columns: repeat(3,1fr); gap: 12px; }
        .tsm-grid-4 { display: grid; grid-template-columns: repeat(4,1fr); gap: 12px; }
        .tsm-pay-card { border-radius: 14px; padding: 16px 12px; text-align: center; border: 1.5px solid transparent; }
        .tsm-pay-cash   { background: #fefce8; border-color: #fde68a; }
        .tsm-pay-cheque { background: #fdf2f8; border-color: #f0abfc; }
        .tsm-pay-bank   { background: #ecfeff; border-color: #a5f3fc; }
        .tsm-pay-total  { background: #f5f3ff; border-color: #c4b5fd; }
        .tsm-pay-icon { font-size: 24px; margin-bottom: 6px; }
        .tsm-pay-cash   .tsm-pay-icon { color: #ca8a04; }
        .tsm-pay-cheque .tsm-pay-icon { color: #a21caf; }
        .tsm-pay-bank   .tsm-pay-icon { color: #0891b2; }
        .tsm-pay-total  .tsm-pay-icon { color: #7c3aed; }
        .tsm-pay-label { font-size: 0.73rem; font-weight: 600; text-transform: uppercase; letter-spacing:.05em; color: #6b7280; margin-bottom: 4px; }
        .tsm-pay-value { font-size: 0.92rem; font-weight: 800; }
        .tsm-pay-cash   .tsm-pay-value { color: #92400e; }
        .tsm-pay-cheque .tsm-pay-value { color: #7e22ce; }
        .tsm-pay-bank   .tsm-pay-value { color: #164e63; }
        .tsm-pay-total  .tsm-pay-value { color: #4c1d95; }
        .tsm-footer {
            padding: 14px 24px; background: #f9fafb;
            border-top: 1px solid #e5e7eb;
            display: flex; align-items: center; justify-content: space-between;
        }
        .tsm-btn-refresh {
            background: none; border: 1.5px solid #d1d5db;
            border-radius: 10px; padding: 7px 18px;
            font-size: 0.82rem; font-weight: 600; color: #374151;
            cursor: pointer; transition: border-color .2s, color .2s;
        }
        .tsm-btn-refresh:hover { border-color: #6366f1; color: #4f46e5; }
        .tsm-btn-close {
            background: linear-gradient(135deg,#1a1a2e,#0f3460);
            border: none; border-radius: 10px;
            padding: 8px 24px; color: #fff;
            font-size: 0.85rem; font-weight: 600; cursor: pointer;
        }
        @media (max-width: 576px) {
            .tsm-grid-2 { grid-template-columns: 1fr; }
            .tsm-grid-3 { grid-template-columns: 1fr 1fr; }
            .tsm-grid-4 { grid-template-columns: 1fr 1fr; }
            .tsm-grand-banner { flex-direction: column; gap: 10px; }
            .tsm-grand-right { text-align: left; }
        }
    </style>
    @endpush

    <!-- Overview Content -->
    <div class="container-fluid p-0">
        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <h3 class="fw-bold text-dark mb-2">
                    <i class="bi bi-speedometer2 text-success me-2"></i> Overview
                </h3>
                <p class="text-muted mb-0">Get a complete view of your product performance and stock activity.</p>
            </div>
        </div>
        <!-- Stats Cards Row - Updated to 4 cards -->
        <div class="row mb-4">
            <!-- Card 1: Total Sales and Revenue -->
            <div class="col-sm-6 col-lg-3 mb-3">
                <div class="stat-card">
                    <i class="bi bi-cash-stack icon-bg"></i>
                    <div class="stat-label mb-2">Total Sales & Revenue</div>
                    <div class="stat-value mb-3">Rs.{{ number_format($totalSales, 0) }}</div>
                    
                    <div class="progress mb-2">
                        <div class="progress-bar bg-success" style="width: {{ $revenuePercentage }}%;"></div>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">Revenue: {{ $revenuePercentage }}%</small>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">
                                Rs.{{ number_format($totalRevenue, 0) }}
                            </span>
                            <button wire:click="openTodaySummaryModal"
                                class="btn btn-sm px-2 py-1"
                                style="background: linear-gradient(135deg,#000000,#e85d04); color:#fff; border:none; border-radius:8px; font-size:11px; font-weight:600; line-height:1.2; white-space:nowrap;">
                                <i class="bi bi-calendar-check me-1"></i>Summary
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Card 2: Total Payment and Due Payment -->
            <div class="col-sm-6 col-lg-3 mb-3">
                <div class="stat-card">
                    <i class="bi bi-wallet2 icon-bg"></i>
                    <div class="stat-label mb-2">Payment & Due</div>
                    <div class="stat-value mb-3">Rs.{{ number_format($totalPaidAmount, 0) }}</div>
                    
                    <div class="progress mb-2">
                        <div class="progress-bar bg-primary" style="width: {{ $revenuePercentage }}%;"></div>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">Due: {{ $duePercentage }}%</small>
                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1">
                            Rs.{{ number_format($totalDueAmount, 0) }}
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Card 3: Total Stocks and Available Stocks -->
            <div class="col-sm-6 col-lg-3 mb-3">
                <div class="stat-card">
                    <i class="bi bi-box-seam icon-bg"></i>
                    <div class="stat-label mb-2">Stocks & Available</div>
                    <div class="stat-value mb-3">{{ number_format($totalStock) }} <span class="fs-6 text-muted fw-normal">units</span></div>
                    
                    <div class="progress mb-2">
                        <div class="progress-bar bg-info" style="width: {{ $availablePercentage }}%;"></div>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">Available: {{ $availablePercentage }}%</small>
                        <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-2 py-1">
                            {{ number_format($availableStock) }} units
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Card 4: Staff Sale and Due Amount -->
            <div class="col-sm-6 col-lg-3 mb-3">
                <div class="stat-card">
                    <i class="bi bi-people icon-bg"></i>
                    <div class="stat-label mb-2">Staff Sales & Due</div>
                    <div class="stat-value mb-3">Rs.{{ number_format($totalStaffSalesValue, 0) }}</div>
                    
                    @php
                        $staffDuePercentage = $totalStaffSalesValue > 0 ? round(($totalStaffDueAmount / $totalStaffSalesValue) * 100, 1) : 0;
                        $staffPaidPercentage = 100 - $staffDuePercentage;
                    @endphp
                    
                    <div class="progress mb-2">
                        <div class="progress-bar bg-danger" style="width: {{ $staffDuePercentage }}%;"></div>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">Due: {{ $staffDuePercentage }}%</small>
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1">
                            Rs.{{ number_format($totalStaffDueAmount, 0) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Equal Size Cards Section -->
        <div class="row">
            <!-- Sales Overview By Daily Trend Card -->
            <div class="col-lg-6 col-md-12 mb-4">
                <div class="chart-card">
                    <div class="chart-header d-flex justify-content-between align-items-center flex-wrap">
                        <div class="mb-mobile-2">
                            <h6 class="mb-1">Daily Sales Trend</h6>
                            <p class="text-muted mb-0 small">Sales performance over the last 7 days</p>
                        </div>
                       
                    </div>
                    <!-- Add scrollable wrapper for the chart -->
                    <div class="chart-scroll-container" wire:ignore>
                        <div class="chart-container" style="min-width: 300px;">
                            <canvas id="salesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Inventory Status Card -->
            <div class="col-lg-6 col-md-12 mb-4">
                <div class="widget-container">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h6 class="fw-bold text-dark mb-1">Inventory Status</h6>
                            <p class="text-muted small mb-0">Current stock levels and alerts</p>
                        </div>
                        
                    </div>

                    <!-- Scrollable container -->
                    <div class="inventory-container custom-scrollbar" style="max-height: 320px; overflow-y: auto;">
                        @forelse($ProductInventory as $Product)
                        @php
                        $stockPercentage = $Product->total_stock > 0 ?
                        round(($Product->available_stock / $Product->total_stock) * 100, 2) : 0;

                        if ($Product->available_stock == 0) {
                            $statusClass = 'out-of-stock';
                            $statusText = 'Out of Stock';
                            $progressClass = 'bg-danger';
                        } elseif ($stockPercentage <= 25) { 
                            $statusClass='low-stock'; 
                            $statusText='Low Stock';
                            $progressClass='bg-warning'; 
                        } else { 
                            $statusClass='in-stock'; 
                            $statusText='In Stock';
                            $progressClass='bg-success'; 
                        } 
                        @endphp 
                        <div class="inventory-item mb-2">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <div class="fw-bold text-dark">{{ $Product->name }}</div>
                                    <div class="text-muted small">SKU: {{ $Product->code }}</div>
                                </div>
                                <span class="status-badge {{ $statusClass }}">{{ $statusText }}</span>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <div class="progress flex-grow-1">
                                    <div class="progress-bar {{ $progressClass }}" style="width: {{ $stockPercentage }}%;"></div>
                                </div>
                                <small class="text-muted fw-500" style="min-width: 45px;">{{ $Product->available_stock }}/{{ $Product->total_stock }}</small>
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-5">
                            <i class="bi bi-box-seam text-muted fs-1 mb-3 d-block"></i>
                            <p class="text-muted">No Product inventory data available.</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- TODAY SUMMARY MODAL --}}
    {{-- ============================================================ --}}
    @if($showTodaySummaryModal)
    @php
        $s = $todaySaleSummary;
        $posCollected   = ($s['pos_total'] ?? 0) - ($s['pos_due'] ?? 0);
        $staffCollected = ($s['staff_total'] ?? 0) - ($s['staff_due'] ?? 0);
        $otherPayment   = max(0, ($s['total_collected'] ?? 0) - ($s['cash_payment'] ?? 0) - ($s['cheque_payment'] ?? 0) - ($s['bank_transfer_payment'] ?? 0));
    @endphp
    <div class="tsm-backdrop" wire:click.self="closeTodaySummaryModal">
        <div class="tsm-modal">

            {{-- Header --}}
            <div class="tsm-header">
                <div class="tsm-header-left">
                    <div class="tsm-header-icon"><i class="bi bi-calendar2-check-fill"></i></div>
                    <div>
                        <h5 class="tsm-title">Sales Summary</h5>
                        <p class="tsm-subtitle">{{ $summaryPeriodLabel }}</p>
                    </div>
                </div>
                <button class="tsm-close" wire:click="closeTodaySummaryModal">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div class="tsm-body">

                {{-- Period Tab Bar --}}
                <div class="tsm-tab-bar">
                    <button wire:click="switchSummaryPeriod('today')"
                        class="tsm-tab {{ $summaryPeriod === 'today' ? 'tsm-tab-active' : '' }}">
                        <i class="bi bi-sun me-1"></i>Today
                    </button>
                    <button wire:click="switchSummaryPeriod('current_month')"
                        class="tsm-tab {{ $summaryPeriod === 'current_month' ? 'tsm-tab-active' : '' }}">
                        <i class="bi bi-calendar-month me-1"></i>This Month
                    </button>
                    <button wire:click="switchSummaryPeriod('last_month')"
                        class="tsm-tab {{ $summaryPeriod === 'last_month' ? 'tsm-tab-active' : '' }}">
                        <i class="bi bi-calendar-minus me-1"></i>Last Month
                    </button>
                    <button wire:click="switchSummaryPeriod('all')"
                        class="tsm-tab {{ $summaryPeriod === 'all' ? 'tsm-tab-active' : '' }}">
                        <i class="bi bi-infinity me-1"></i>All
                    </button>
                    <div class="tsm-tab-divider"></div>
                    <select class="tsm-month-select {{ $summaryPeriod === 'custom' ? 'tsm-month-select-active' : '' }}"
                        wire:change="switchSummaryMonth($event.target.value)">
                        <option value="">📅 Previous Month…</option>
                        @foreach($summaryMonthOptions as $opt)
                            <option value="{{ $opt['value'] }}" {{ $summaryCustomMonth === $opt['value'] ? 'selected' : '' }}>
                                {{ $opt['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Grand Total Banner --}}
                <div class="tsm-grand-banner">
                    <div class="tsm-grand-left">
                        <span class="tsm-grand-label">Total Sales — {{ $summaryPeriodLabel }}</span>
                        <span class="tsm-grand-value">Rs.{{ number_format($s['grand_total'] ?? 0, 2) }}</span>
                    </div>
                    <div class="tsm-grand-right">
                        <span class="tsm-grand-label">Outstanding Due</span>
                        <span class="tsm-grand-due">Rs.{{ number_format($s['grand_due'] ?? 0, 2) }}</span>
                    </div>
                </div>

                {{-- Sales Breakdown --}}
                <h6 class="tsm-section-title"><i class="bi bi-bar-chart-fill me-2 text-indigo"></i>Sales Breakdown</h6>
                <div class="tsm-grid-2">

                    {{-- POS Card --}}
                    <div class="tsm-card tsm-card-pos">
                        <div class="tsm-card-header-row">
                            <div class="tsm-card-icon tsm-icon-pos"><i class="bi bi-display-fill"></i></div>
                            <div>
                                <div class="tsm-card-label">POS Sales</div>
                                <div class="tsm-card-count">{{ $s['pos_count'] ?? 0 }} invoices</div>
                            </div>
                        </div>
                        <div class="tsm-card-metrics">
                            <div class="tsm-metric">
                                <span class="tsm-metric-label">Total</span>
                                <span class="tsm-metric-value tsm-pos-total">Rs.{{ number_format($s['pos_total'] ?? 0, 2) }}</span>
                            </div>
                            <div class="tsm-metric">
                                <span class="tsm-metric-label">Collected</span>
                                <span class="tsm-metric-value tsm-pos-collected">Rs.{{ number_format($posCollected, 2) }}</span>
                            </div>
                            <div class="tsm-metric">
                                <span class="tsm-metric-label">Due</span>
                                <span class="tsm-metric-value tsm-due-red">Rs.{{ number_format($s['pos_due'] ?? 0, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Staff Card --}}
                    <div class="tsm-card tsm-card-staff">
                        <div class="tsm-card-header-row">
                            <div class="tsm-card-icon tsm-icon-staff"><i class="bi bi-people-fill"></i></div>
                            <div>
                                <div class="tsm-card-label">Staff Sales</div>
                                <div class="tsm-card-count">{{ $s['staff_count'] ?? 0 }} invoices</div>
                            </div>
                        </div>
                        <div class="tsm-card-metrics">
                            <div class="tsm-metric">
                                <span class="tsm-metric-label">Total</span>
                                <span class="tsm-metric-value tsm-staff-total">Rs.{{ number_format($s['staff_total'] ?? 0, 2) }}</span>
                            </div>
                            <div class="tsm-metric">
                                <span class="tsm-metric-label">Collected</span>
                                <span class="tsm-metric-value tsm-staff-collected">Rs.{{ number_format($staffCollected, 2) }}</span>
                            </div>
                            <div class="tsm-metric">
                                <span class="tsm-metric-label">Due</span>
                                <span class="tsm-metric-value tsm-due-red">Rs.{{ number_format($s['staff_due'] ?? 0, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Payment Breakdown --}}
                <h6 class="tsm-section-title mt-3"><i class="bi bi-credit-card-2-front-fill me-2 text-indigo"></i>Payment Collection</h6>
                <div class="tsm-grid-4">

                    <div class="tsm-pay-card tsm-pay-cash">
                        <div class="tsm-pay-icon"><i class="bi bi-cash-coin"></i></div>
                        <div class="tsm-pay-label">Cash</div>
                        <div class="tsm-pay-value">Rs.{{ number_format($s['cash_payment'] ?? 0, 2) }}</div>
                    </div>

                    <div class="tsm-pay-card tsm-pay-cheque">
                        <div class="tsm-pay-icon"><i class="bi bi-file-earmark-check-fill"></i></div>
                        <div class="tsm-pay-label">Cheque</div>
                        <div class="tsm-pay-value">Rs.{{ number_format($s['cheque_payment'] ?? 0, 2) }}</div>
                    </div>

                    <div class="tsm-pay-card tsm-pay-bank">
                        <div class="tsm-pay-icon"><i class="bi bi-bank"></i></div>
                        <div class="tsm-pay-label">Bank Transfer</div>
                        <div class="tsm-pay-value">Rs.{{ number_format($s['bank_transfer_payment'] ?? 0, 2) }}</div>
                    </div>

                    <div class="tsm-pay-card tsm-pay-total">
                        <div class="tsm-pay-icon"><i class="bi bi-wallet2"></i></div>
                        <div class="tsm-pay-label">Total Collected</div>
                        <div class="tsm-pay-value">Rs.{{ number_format($s['total_collected'] ?? 0, 2) }}</div>
                    </div>
                </div>

            </div>{{-- /body --}}

            {{-- Footer --}}
            <div class="tsm-footer">
                <button class="tsm-btn-refresh" wire:click="switchSummaryPeriod('{{ $summaryPeriod }}')"
                    wire:loading.attr="disabled" wire:target="switchSummaryPeriod,switchSummaryMonth">
                    <span wire:loading.remove wire:target="switchSummaryPeriod,switchSummaryMonth">
                        <i class="bi bi-arrow-clockwise me-2"></i>Refresh
                    </span>
                    <span wire:loading wire:target="switchSummaryPeriod,switchSummaryMonth">
                        <span class="spinner-border spinner-border-sm me-1"></span>Loading…
                    </span>
                </button>
                <button class="tsm-btn-close" wire:click="closeTodaySummaryModal">
                    Close
                </button>
            </div>

        </div>
    </div>

    @endif
    {{-- END TODAY SUMMARY MODAL --}}

    @push('scripts')
    <script>
        // Prepare data from PHP
        const dailyLabels = @json(collect($dailySales)->pluck('date'));
        const dailyTotals = @json(collect($dailySales)->pluck('total_sales'));

        // Chart instance
        let salesChartInstance = null;

        document.addEventListener('DOMContentLoaded', function() {
            // Initialize daily sales chart
            initializeDailySalesChart();
        });

        function initializeDailySalesChart() {
            const ctx = document.getElementById('salesChart');
            if (!ctx) return;
            
            salesChartInstance = new Chart(ctx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: dailyLabels,
                    datasets: [{
                        label: 'Daily Sales (Rs.)',
                        backgroundColor: 'rgba(245, 131, 32, 0.1)',
                        borderColor: '#000000',
                        borderWidth: 3,
                        pointBackgroundColor: '#000000',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        data: dailyTotals,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: {
                        padding: {
                            top: 20
                        }
                    },
                    plugins: {
                        legend: { 
                            display: true,
                            position: 'top',
                            labels: {
                                font: { size: 13, weight: '500' },
                                padding: 15,
                                usePointStyle: true
                            }
                        },
                        tooltip: { 
                            backgroundColor: '#1f2937',
                            padding: 12,
                            titleFont: { size: 14, weight: 'bold' },
                            bodyFont: { size: 13 },
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return 'Rs. ' + context.parsed.y.toLocaleString();
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { 
                                color: '#f3f4f6',
                                drawBorder: false 
                            },
                            ticks: {
                                font: { size: 12 },
                                color: '#6b7280',
                                callback: function(value) {
                                    if (value >= 1000) return 'Rs.' + (value / 1000) + 'k';
                                    return 'Rs.' + value;
                                }
                            }
                        },
                        x: {
                            grid: { display: false },
                            ticks: {
                                font: { size: 12, weight: '500' },
                                color: '#6b7280'
                            }
                        }
                    }
                }
            });
        }

        // Handle window resize for chart
        window.addEventListener('resize', function() {
            if (salesChartInstance) {
                salesChartInstance.update();
            }
        });

        // Reinitialize chart after any Livewire DOM update (e.g. modal open/close)
        document.addEventListener('livewire:update', function() {
            // If the canvas is present but the chart instance was lost, recreate it
            const ctx = document.getElementById('salesChart');
            if (ctx && !salesChartInstance) {
                initializeDailySalesChart();
            } else if (salesChartInstance) {
                // Chart instance exists — just call update to re-render correctly
                try { salesChartInstance.update(); } catch(e) {
                    salesChartInstance = null;
                    initializeDailySalesChart();
                }
            }
        });
    </script>
    @endpush
</div>