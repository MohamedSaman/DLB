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
            <div class="card border-0 shadow-sm h-100 bg-primary bg-opacity-10">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted small mb-1">Delivered Sale Total</p>
                            <h3 class="fw-bold text-primary mb-1">Rs. {{ number_format($deliveredSalesTotal, 2) }}</h3>
                            <small class="text-muted">Today Delivered: <span class="fw-semibold">Rs. {{ number_format($todayDeliveredSalesTotal, 2) }}</span></small>
                        </div>
                        <div class="bg-primary bg-opacity-25 rounded-circle p-3">
                            <i class="bi bi-receipt text-primary fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100 bg-success bg-opacity-10">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted small mb-1">Total Received Amount</p>
                            <h3 class="fw-bold text-success mb-1">Rs. {{ number_format($totalReceivedAmount, 2) }}</h3>
                            <small class="text-muted">Today Received: <span class="fw-semibold">Rs. {{ number_format($todayReceivedAmount, 2) }}</span></small>
                        </div>
                        <div class="bg-success bg-opacity-25 rounded-circle p-3">
                            <i class="bi bi-cash-coin text-success fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100 bg-warning bg-opacity-10">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted small mb-1">My Deliveries</p>
                            <h3 class="fw-bold text-success mb-1">Completed: {{ $completedDeliveries }}</h3>
                            <small class="text-muted">Pending: <span class="fw-semibold text-warning">{{ $pendingDeliveries }}</span></small>
                        </div>
                        <div class="bg-warning bg-opacity-25 rounded-circle p-3">
                            <i class="bi bi-truck text-warning fs-4"></i>
                        </div>
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

    {{-- Recent Deliveries --}}
    <div class="card border-0 shadow-sm">
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
</div>
