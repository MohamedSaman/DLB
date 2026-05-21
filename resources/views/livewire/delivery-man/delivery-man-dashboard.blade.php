<div>
    <style>
        /* ============================================================
           TODAY SUMMARY MODAL STYLES (Filtered)
        ============================================================ */
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
            background: linear-gradient(135deg, #f58320, #e85d04) !important;
            color: #fff !important; box-shadow: 0 2px 8px rgba(245,131,32,0.35);
        }
        .tsm-tab-divider { width: 1px; height: 24px; background: #cbd5e1; margin: 0 2px; }
        .tsm-month-select {
            flex: 1; min-width: 130px; max-width: 200px;
            padding: 6px 10px; border: 1.5px solid #e2e8f0; border-radius: 9px;
            background: #fff; font-size: 0.8rem; font-weight: 600; color: #475569;
            cursor: pointer; outline: none; transition: border-color .2s;
        }
        .tsm-month-select:hover, .tsm-month-select:focus { border-color: #f58320; color: #1e293b; }
        .tsm-month-select-active { border-color: #f58320 !important; background: #fff7ed !important; color: #c2410c !important; }
        .tsm-footer {
            padding: 14px 24px; background: #f9fafb;
            border-top: 1px solid #e5e7eb;
            display: flex; align-items: center; justify-content: space-between;
            border-bottom-left-radius: inherit; border-bottom-right-radius: inherit;
        }
        .tsm-btn-refresh {
            background: none; border: 1.5px solid #d1d5db;
            border-radius: 10px; padding: 7px 18px;
            font-size: 0.82rem; font-weight: 600; color: #374151;
            cursor: pointer; transition: border-color .2s, color .2s;
            display: inline-flex; align-items: center; justify-content: center;
        }
        .tsm-btn-refresh:hover { border-color: #6366f1; color: #4f46e5; }
        .tsm-btn-close {
            background: linear-gradient(135deg,#1a1a2e,#0f3460);
            border: none; border-radius: 10px;
            padding: 8px 24px; color: #fff;
            font-size: 0.85rem; font-weight: 600; cursor: pointer;
        }

        /* Mobile Responsiveness Tweaks */
        @media (max-width: 767.98px) {
            .container-fluid { padding-left: 10px !important; padding-right: 10px !important; padding-top: 10px !important; }
            .card-body { padding: 12px !important; }
            .table { font-size: 0.8rem !important; }
            .table th, .table td { padding: 8px 6px !important; }
            h2.fw-bold { font-size: 1.4rem !important; }
            h3.fw-bold { font-size: 1.2rem !important; }
            .mb-4 { margin-bottom: 1rem !important; }
            .g-4, .row { --bs-gutter-y: 1rem; --bs-gutter-x: 1rem; }
            .card-header { padding: 10px 12px !important; }
            .card-header h5 { font-size: 1.1rem !important; }
            .badge { font-size: 0.7rem !important; padding: 0.35em 0.5em !important; }
            .progress { height: 8px !important; }
        }
    </style>

    <div class="container-fluid py-3">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-2">
                <i class="bi bi-truck text-primary me-2"></i> Delivery Dashboard
            </h3>
            <p class="text-muted mb-0">Welcome back, {{ auth()->user()->name }}!</p>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="row g-4 mb-4">
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <p class="text-uppercase text-muted small fw-bold mb-2">Today's Delivered Sale</p>
                    <h2 class="fw-bold mb-3">Rs. {{ number_format($todayDeliveredSalesTotal, 2) }}</h2>

                    <div class="progress mb-3" style="height: 10px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: 100%;"></div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center text-muted mb-3">
                        <span>Total Delivered Sale</span>
                        <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary">Rs. {{ number_format($deliveredSalesTotal, 2) }}</span>
                    </div>

                    <button type="button" class="btn btn-link text-decoration-none p-0 text-dark mt-auto text-start" wire:click="openDaySummaryModal">
                        <i class="bi bi-eye me-1"></i> View Day Summary
                    </button>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <p class="text-uppercase text-muted small fw-bold mb-2">Amount Received (Today)</p>
                    <h2 class="fw-bold mb-3">Rs. {{ number_format($todayReceivedAmount, 2) }}</h2>

                    <div class="progress mb-3" style="height: 10px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: 100%;"></div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center text-muted mt-auto">
                        <span>Total Received Amount</span>
                        <span class="badge rounded-pill bg-success bg-opacity-10 text-success">Rs. {{ number_format($totalReceivedAmount, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <p class="text-uppercase text-muted small fw-bold mb-2">My Deliveries</p>
                    <h2 class="fw-bold mb-3">{{ $completedDeliveries }} <span class="fs-4 fw-normal text-muted">completed</span></h2>

                    <div class="progress mb-3" style="height: 10px;">
                        <div class="progress-bar bg-info" role="progressbar" style="width: {{ $completedDeliveries > 0 || $pendingDeliveries > 0 ? min(100, ($completedDeliveries / max(1, $completedDeliveries + $pendingDeliveries)) * 100) : 0 }}%;"></div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center text-muted mt-auto">
                        <span>Pending Deliveries</span>
                        <span class="badge rounded-pill bg-info bg-opacity-10 text-info">{{ $pendingDeliveries }} pending</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions 
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <a href="{{ route('delivery.pending') }}" class="btn btn-primary w-100 py-3">
                <i class="bi bi-box-seam me-2"></i> Pending Deliveries
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="{{ route('delivery.completed') }}" class="btn btn-outline-secondary w-100 py-3">
                <i class="bi bi-check2-all me-2"></i> Completed
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="{{ route('delivery.payments') }}" class="btn btn-outline-success w-100 py-3">
                <i class="bi bi-cash-stack me-2"></i> Collect Payment
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="{{ route('delivery.expenses') }}" class="btn btn-outline-info w-100 py-3">
                <i class="bi bi-cash-coin me-2"></i> Expenses
            </a>
        </div>
    </div>--}}

    {{-- Recent Deliveries - Desktop View --}}
    <div class="card border-0 shadow-sm d-none d-md-block">
        <div class="card-header bg-white py-3">
            <h5 class="fw-bold text-dark mb-0">
                <i class="bi bi-clock-history text-primary me-2"></i> Recent Deliveries
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Invoice</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Delivery Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentDeliveries as $sale)
                        <tr>
                            <td class="ps-4">
                                <span class="fw-medium">{{ $sale->invoice_number }}</span>
                            </td>
                            <td>
                                {{ $sale->customer->name ?? 'N/A' }}
                                @if($sale->customer->phone)
                                <small class="d-block text-muted">{{ $sale->customer->phone }}</small>
                                @endif
                            </td>
                            <td class="fw-semibold">Rs. {{ number_format($sale->total_amount, 2) }}</td>
                            <td>
                                @if($sale->delivery_status === 'pending')
                                    <span class="badge bg-warning">Pending</span>
                                @elseif($sale->delivery_status === 'in_transit')
                                    <span class="badge bg-info">In Transit</span>
                                @elseif($sale->delivery_status === 'delivered')
                                    <span class="badge bg-success">Delivered</span>
                                @else
                                    <span class="badge bg-secondary">{{ ucfirst($sale->delivery_status) }}</span>
                                @endif
                            </td>
                            <td class="text-muted">{{ $sale->created_at->format('M d, Y') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                No recent deliveries.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Recent Deliveries - Mobile View --}}
    <div class="d-md-none mb-4">
        <div class="d-flex align-items-center mb-3 px-1 mt-2">
            <h5 class="fw-bold text-dark mb-0">
                <i class="bi bi-clock-history text-primary me-2"></i> Recent Deliveries
            </h5>
        </div>
        
        @forelse($recentDeliveries as $sale)
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h6 class="fw-bold mb-1">{{ $sale->invoice_number }}</h6>
                        <small class="text-muted d-block">{{ $sale->created_at->format('M d, Y') }}</small>
                    </div>
                    <div class="text-end">
                        @if($sale->delivery_status === 'pending')
                            <span class="badge bg-warning">Pending</span>
                        @elseif($sale->delivery_status === 'in_transit')
                            <span class="badge bg-info">In Transit</span>
                        @elseif($sale->delivery_status === 'delivered')
                            <span class="badge bg-success">Delivered</span>
                        @else
                            <span class="badge bg-secondary">{{ ucfirst($sale->delivery_status) }}</span>
                        @endif
                    </div>
                </div>
                
                <div class="mb-3">
                    <p class="mb-1"><strong><i class="bi bi-person me-2"></i>{{ $sale->customer->name ?? 'N/A' }}</strong></p>
                    @if($sale->customer->phone ?? false)
                    <p class="mb-1 text-muted"><i class="bi bi-telephone me-2"></i>{{ $sale->customer->phone }}</p>
                    @endif
                </div>
                
                <div>
                    <small class="text-muted d-block">Amount</small>
                    <span class="fw-bold">Rs. {{ number_format($sale->total_amount, 2) }}</span>
                </div>
            </div>
        </div>
        @empty
        <div class="text-center py-4 text-muted bg-white rounded shadow-sm">
            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
            <p class="mb-0">No recent deliveries.</p>
        </div>
        @endforelse
    </div>

    {{-- Day Summary Modal --}}
    @if($showDaySummaryModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-primary text-white">
                        <div>
                            <h5 class="modal-title fw-bold">
                                <i class="bi bi-clipboard-data me-2"></i>Day Summary
                            </h5>
                            <p class="mb-0" style="font-size: 0.85rem; opacity: 0.85;">{{ $summaryPeriodLabel }}</p>
                        </div>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeDaySummaryModal"></button>
                    </div>

                    <div class="modal-body p-4">
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

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100 bg-info bg-opacity-10">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="fw-semibold"><i class="bi bi-truck text-info me-2"></i>Delivered Sale</div>
                                        <div class="fs-4 fw-bold text-info">Rs. {{ number_format($summaryDeliveredAmount, 2) }}</div>
                                    </div>
                                    <div class="small text-muted mt-2">{{ $summaryDeliveredCount }} deliveries</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100 bg-success bg-opacity-10">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="fw-semibold"><i class="bi bi-wallet2 text-success me-2"></i>Total Received</div>
                                        <div class="fs-5 fw-bold text-success">Rs. {{ number_format($summaryTotalReceived, 2) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <h6 class="fw-bold mt-4 mb-3 text-secondary">Payment Breakdown</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="border rounded p-3 h-100 bg-success bg-opacity-10">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="fw-semibold small"><i class="bi bi-cash me-1 text-success"></i> Cash</div>
                                        <div class="fw-bold text-success">Rs. {{ number_format($summaryCashReceived, 2) }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border rounded p-3 h-100 bg-primary bg-opacity-10">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="fw-semibold small"><i class="bi bi-file-earmark-check me-1 text-primary"></i> Cheque</div>
                                        <div class="fw-bold text-primary">Rs. {{ number_format($summaryChequeReceived, 2) }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border rounded p-3 h-100 bg-info bg-opacity-10">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="fw-semibold small"><i class="bi bi-bank me-1 text-info"></i> Bank</div>
                                        <div class="fw-bold text-info">Rs. {{ number_format($summaryBankReceived, 2) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tsm-footer">
                        <button class="tsm-btn-refresh" wire:click="switchSummaryPeriod('{{ $summaryPeriod }}')"
                            wire:loading.attr="disabled" wire:target="switchSummaryPeriod,switchSummaryMonth">
                            <span wire:loading.remove wire:target="switchSummaryPeriod,switchSummaryMonth">
                                <i class="bi bi-arrow-clockwise me-2"></i>Refresh
                            </span>
                            
                        </button>
                        <button class="tsm-btn-close" wire:click="closeDaySummaryModal">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
