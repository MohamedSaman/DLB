<div class="container-fluid py-3">
    <style>
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
        }
    </style>
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-2">
                <i class="bi bi-check2-all text-success me-2"></i> Completed Deliveries
            </h3>
            <p class="text-muted mb-0">View your delivery history</p>
        </div>
        
    </div>

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm bg-primary bg-opacity-10">
                <div class="card-body text-center py-3">
                    <h4 class="fw-bold text-primary mb-0">{{ $todayCount }}</h4>
                    <small class="text-muted">Deliveries Today</small>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm bg-success bg-opacity-10">
                <div class="card-body text-center py-3">
                    <h4 class="fw-bold text-success mb-0">{{ $totalCount }}</h4>
                    <small class="text-muted">Total Deliveries</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Search by invoice or customer...">
                    </div>
                </div>
                <div class="col-md-4">
                    <select wire:model.live="dateFilter" class="form-select">
                        <option value="">All Time</option>
                        <option value="today">Today</option>
                        <option value="week">This Week</option>
                        <option value="month">This Month</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Deliveries List - Desktop View --}}
    <div class="card border-0 shadow-sm d-none d-md-block">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Invoice</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Delivered At</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sales as $sale)
                        <tr>
                            <td class="ps-4">
                                <span class="fw-medium">{{ $sale->invoice_number }}</span>
                                <small class="d-block text-muted">{{ $sale->sale_id }}</small>
                            </td>
                            <td>
                                {{ $sale->customer->name ?? 'N/A' }}
                                @if($sale->customer->phone ?? false)
                                <small class="d-block text-muted">{{ $sale->customer->phone }}</small>
                                @endif
                            </td>
                            <td class="fw-semibold">Rs. {{ number_format($sale->total_amount, 2) }}</td>
                            <td>
                                <span class="text-success">{{ $sale->delivered_at?->format('M d, Y') }}</span>
                                <small class="d-block text-muted">{{ $sale->delivered_at?->format('h:i A') }}</small>
                            </td>
                            <td class="text-end pe-4">
                                <button wire:click="viewDetails({{ $sale->id }})" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> View
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                No completed deliveries found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Deliveries List - Mobile View --}}
    <div class="d-md-none">
        @forelse($sales as $sale)
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h6 class="fw-bold mb-1">{{ $sale->invoice_number }}</h6>
                        <small class="text-muted d-block">{{ $sale->sale_id }}</small>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-success">Delivered</span>
                    </div>
                </div>
                
                <div class="mb-3">
                    <p class="mb-1"><strong><i class="bi bi-person me-2"></i>{{ $sale->customer->name ?? 'N/A' }}</strong></p>
                    @if($sale->customer->phone ?? false)
                    <p class="mb-1 text-muted"><i class="bi bi-telephone me-2"></i>{{ $sale->customer->phone }}</p>
                    @endif
                </div>
                
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <small class="text-muted d-block">Amount</small>
                        <span class="fw-bold">Rs. {{ number_format($sale->total_amount, 2) }}</span>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Delivered At</small>
                        <span class="text-success fw-medium">{{ $sale->delivered_at?->format('M d, Y') }}</span>
                        <small class="d-block text-muted">{{ $sale->delivered_at?->format('h:i A') }}</small>
                    </div>
                </div>
                
                <div class="d-grid gap-2">
                    <button wire:click="viewDetails({{ $sale->id }})" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-eye me-2"></i> View Details
                    </button>
                </div>
            </div>
        </div>
        @empty
        <div class="text-center py-5 text-muted">
            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
            <p>No completed deliveries found.</p>
        </div>
        @endforelse
    </div>

      {{-- Pagination --}}
    <div class="mt-4">
        {{ $sales->links('livewire.custom-pagination') }}
    </div>

    {{-- Details Modal --}}
    @if($showDetailsModal && $selectedSale)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-check-circle me-2"></i>Delivery Details - {{ $selectedSale->invoice_number }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeDetailsModal"></button>
                </div>
                <div class="modal-body">
                    {{-- Delivery Info --}}
                    <div class="alert alert-success mb-4">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Delivered On:</strong> {{ $selectedSale->delivered_at?->format('M d, Y h:i A') }}
                            </div>
                            <div class="col-md-6">
                                <strong>Total Amount:</strong> Rs. {{ number_format($selectedSale->total_amount, 2) }}
                            </div>
                        </div>
                    </div>

                    {{-- Customer Info --}}
                    <h6 class="fw-bold mb-2"><i class="bi bi-person me-2"></i>Customer Information</h6>
                    <div class="bg-light rounded p-3 mb-4">
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Name:</strong> {{ $selectedSale->customer->name ?? 'N/A' }}</p>
                                <p class="mb-0"><strong>Phone:</strong> {{ $selectedSale->customer->phone ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-0"><strong>Address:</strong> {{ $selectedSale->customer->address ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Items --}}
                    <h6 class="fw-bold mb-2">Order Items</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($selectedSale->items as $item)
                                <tr>
                                    <td>{{ $item->product_name }}</td>
                                    <td class="text-center">{{ $item->quantity }}</td>
                                    <td class="text-end">Rs. {{ number_format($item->total, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="table-success">
                                    <td colspan="2" class="text-end fw-bold">Total:</td>
                                    <td class="text-end fw-bold">Rs. {{ number_format($selectedSale->total_amount, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeDetailsModal">Close</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
