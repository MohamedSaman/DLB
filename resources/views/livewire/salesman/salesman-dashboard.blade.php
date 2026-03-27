<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-2">
                <i class="bi bi-speedometer2 text-primary me-2"></i> Salesman Dashboard
            </h3>
            <p class="text-muted mb-0">Welcome back, {{ auth()->user()->name }}!</p>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <p class="text-uppercase text-muted small fw-bold mb-2">Today's Sale</p>
                    <h2 class="fw-bold mb-3">Rs.{{ number_format($todaySalesAmount, 2) }}</h2>

                    <div class="progress mb-3" style="height: 10px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: 100%;"></div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center text-muted mb-3">
                        <span>Total Sales</span>
                        <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary">Rs.{{ number_format($lifetimeSalesAmount, 2) }}</span>
                    </div>

                    <button type="button" class="btn btn-link text-decoration-none p-0 text-dark mt-auto" wire:click="openDaySummaryModal">
                        <i class="bi bi-eye me-1"></i> View Today Summary
                    </button>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-uppercase text-muted small fw-bold mb-2">Cash Amount (Today)</p>
                    <h2 class="fw-bold mb-3">Rs.{{ number_format($todayCashAmount, 2) }}</h2>

                    <div class="progress mb-3" style="height: 10px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: 100%;"></div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center text-muted">
                        <span>Total Cash Collected</span>
                        <span class="badge rounded-pill bg-success bg-opacity-10 text-success">Rs.{{ number_format($lifetimeCashAmount, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-uppercase text-muted small fw-bold mb-2">System Stock</p>
                    <h2 class="fw-bold mb-3">{{ number_format($systemStockUnits) }} <span class="fs-4 fw-normal text-muted">units</span></h2>

                    <div class="progress mb-3" style="height: 10px;">
                        <div class="progress-bar bg-info" role="progressbar" style="width: {{ $systemStockCapacity > 0 ? min(100, ($systemStockUnits / $systemStockCapacity) * 100) : 0 }}%;"></div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center text-muted">
                        <span>Total Capacity</span>
                        <span class="badge rounded-pill bg-info bg-opacity-10 text-info">{{ number_format($systemStockCapacity) }} units</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <p class="text-uppercase text-muted small fw-bold mb-2">Recent Invoices</p>
                    <h2 class="fw-bold mb-3">{{ $recentInvoicesCount }}</h2>

                    <div class="text-muted mb-3">
                        <i class="bi bi-clock me-1"></i> Showing last 5 sales
                    </div>

                    <a href="{{ route('salesman.products') }}" class="btn btn-link text-decoration-none p-0 text-dark mt-auto">View All Products</a>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold text-dark mb-0">
                <i class="bi bi-clock-history text-primary me-2"></i> Recent Orders
            </h5>
            <a href="{{ route('salesman.sales') }}" class="btn btn-sm btn-outline-primary">View All</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Invoice</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentSales as $sale)
                            <tr>
                                <td class="ps-4">
                                    <span class="fw-medium">{{ $sale->invoice_number }}</span>
                                </td>
                                <td>{{ $sale->customer->name ?? 'N/A' }}</td>
                                <td class="fw-semibold">Rs. {{ number_format($sale->total_amount, 2) }}</td>
                                <td>
                                    @if($sale->status === 'pending')
                                        <span class="badge bg-warning">Pending</span>
                                    @elseif($sale->status === 'confirm')
                                        <span class="badge bg-success">Approved</span>
                                    @else
                                        <span class="badge bg-danger">Rejected</span>
                                    @endif
                                </td>
                                <td class="text-muted">{{ $sale->created_at->format('M d, Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    No sales yet. Start creating orders!
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($showDaySummaryModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-clipboard-data me-2"></i>Today's Summary
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeDaySummaryModal"></button>
                    </div>

                    <div class="modal-body p-4">
                        <div class="border rounded p-3 mb-3 bg-info bg-opacity-10">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="fw-semibold"><i class="bi bi-cart-check text-info me-2"></i>Total Sale</div>
                                <div class="fs-3 fw-bold text-info">Rs. {{ number_format($todaySalesAmount, 2) }}</div>
                            </div>
                            <div class="small text-muted mt-2">{{ $todaySalesCount }} orders today</div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100 bg-success bg-opacity-10">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="fw-semibold"><i class="bi bi-cash text-success me-2"></i>Cash Sale</div>
                                        <div class="fs-4 fw-bold text-success">Rs. {{ number_format($todayCashAmount, 2) }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100 bg-warning bg-opacity-10">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="fw-semibold"><i class="bi bi-wallet2 text-warning me-2"></i>Due Sale</div>
                                        <div class="fs-4 fw-bold text-warning">Rs. {{ number_format($todayDueAmount, 2) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="border rounded p-3 mb-3 bg-danger bg-opacity-10">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="fw-semibold"><i class="bi bi-bag-dash text-danger me-2"></i>Salesman Expenses</div>
                                <div class="fs-3 fw-bold text-danger">- Rs. {{ number_format($todayExpenseAmount, 2) }}</div>
                            </div>
                        </div>

                        
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeDaySummaryModal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
