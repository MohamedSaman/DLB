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
                <i class="bi bi-wallet2 text-primary me-2"></i> Customer Dues
            </h3>
            <p class="text-muted mb-0">View outstanding dues from your customers (read-only)</p>
        </div>
        <a href="{{ route('salesman.dashboard') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i> Back to Dashboard
        </a>
    </div>

    {{-- Total Dues Card --}}
    <div class="card border-0 shadow-sm bg-danger bg-opacity-10 mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="bg-danger bg-opacity-25 rounded-circle p-3">
                        <i class="bi bi-cash-stack text-danger fs-3"></i>
                    </div>
                </div>
                <div class="col">
                    <small class="text-muted">Total Outstanding Dues</small>
                    <h3 class="fw-bold text-danger mb-0">Rs. {{ number_format($totalDues, 2) }}</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- Info Alert --}}
    <div class="alert alert-info d-flex align-items-center mb-4">
        <i class="bi bi-info-circle me-2"></i>
        <span>As a salesman, you can view customer dues but cannot collect payments. Payments are handled by delivery staff.</span>
    </div>

    {{-- Search --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Search customer by name or phone...">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Customers List - Desktop --}}
    <div class="card border-0 shadow-sm d-none d-md-block">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Customer</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th class="text-end">Total Due</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $customer)
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary bg-opacity-10 rounded-circle p-2 me-2">
                                        <i class="bi bi-person text-primary"></i>
                                    </div>
                                    <span class="fw-medium">{{ $customer->name }}</span>
                                </div>
                            </td>
                            <td>{{ $customer->phone ?? 'N/A' }}</td>
                            <td class="text-muted">{{ Str::limit($customer->address ?? 'N/A', 30) }}</td>
                            <td class="text-end">
                                <span class="fw-bold text-danger">Rs. {{ number_format($customer->total_due, 2) }}</span>
                            </td>
                            <td class="text-end pe-4">
                                <button wire:click="viewDetails({{ $customer->id }})" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> Details
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-check-circle fs-1 text-success d-block mb-2"></i>
                                No outstanding dues! All your customers are settled.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    {{-- Customers List - Mobile --}}
    <div class="d-md-none mb-4">
        @forelse($customers as $customer)
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary bg-opacity-10 rounded-circle p-2 me-2">
                            <i class="bi bi-person text-primary"></i>
                        </div>
                        <h6 class="fw-bold text-dark mb-0">{{ $customer->name }}</h6>
                    </div>
                    <button wire:click="viewDetails({{ $customer->id }})" class="btn btn-sm btn-outline-primary py-1 px-2">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
                <div class="mb-3">
                    <div class="text-dark"><i class="bi bi-telephone me-2"></i>{{ $customer->phone ?? 'N/A' }}</div>
                    <small class="text-muted d-block"><i class="bi bi-geo-alt me-2"></i>{{ Str::limit($customer->address ?? 'N/A', 40) }}</small>
                </div>
                <div>
                    <small class="text-muted d-block">Total Due</small>
                    <span class="fw-bold text-danger fs-5">Rs. {{ number_format($customer->total_due, 2) }}</span>
                </div>
            </div>
        </div>
        @empty
        <div class="text-center py-4 text-muted bg-white rounded shadow-sm">
            <i class="bi bi-check-circle fs-1 text-success d-block mb-2"></i>
            <p class="mb-0">No outstanding dues! All your customers are settled.</p>
        </div>
        @endforelse
    </div>

    
    {{-- Pagination --}}
    <div class="mt-4">
        {{ $customers->links('livewire.custom-pagination') }}
    </div>

    {{-- Details Modal --}}
    @if($showDetailsModal && $selectedCustomer)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-person me-2"></i>{{ $selectedCustomer->name }} - Outstanding Sales
                    </h5>
                    <button type="button" class="btn-close" wire:click="closeDetailsModal"></button>
                </div>
                <div class="modal-body">
                    {{-- Customer Info --}}
                    <div class="bg-light rounded p-3 mb-4">
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Phone:</strong> {{ $selectedCustomer->phone ?? 'N/A' }}</p>
                                <p class="mb-0"><strong>Email:</strong> {{ $selectedCustomer->email ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-0"><strong>Address:</strong> {{ $selectedCustomer->address ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Outstanding Sales --}}
                    <h6 class="fw-bold mb-3">Outstanding Sales</h6>
                    @forelse($selectedCustomer->sales as $sale)
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="fw-bold mb-1">{{ $sale->invoice_number }}</h6>
                                    <small class="text-muted">{{ $sale->created_at->format('M d, Y') }}</small>
                                </div>
                                <span class="badge bg-danger fs-6">Due: Rs. {{ number_format($sale->due_amount, 2) }}</span>
                            </div>
                            <div class="row text-sm">
                                <div class="col-4">
                                    <small class="text-muted d-block">Total</small>
                                    <span>Rs. {{ number_format($sale->total_amount, 2) }}</span>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block">Paid</small>
                                    <span class="text-success">Rs. {{ number_format($sale->total_amount - $sale->due_amount, 2) }}</span>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block">Due</small>
                                    <span class="text-danger">Rs. {{ number_format($sale->due_amount, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <p class="text-muted text-center py-3">No outstanding sales for this customer.</p>
                    @endforelse

                    {{-- Total --}}
                    <div class="border-top pt-3 mt-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0">Total Outstanding:</h6>
                            <h5 class="fw-bold text-danger mb-0">Rs. {{ number_format($selectedCustomer->sales->sum('due_amount'), 2) }}</h5>
                        </div>
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
