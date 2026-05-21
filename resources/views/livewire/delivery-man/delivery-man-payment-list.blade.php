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
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold text-dark mb-2">
                <i class="bi bi-list-check text-success me-2"></i> My Payment Collections
            </h3>
            <p class="text-muted mb-0">View all your collected payments</p>
        </div>
        
    </div>

    {{-- Summary Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3">
                        <i class="bi bi-cash-stack text-success fs-4"></i>
                    </div>
                    <div>
                        <p class="text-muted mb-0 small">Total Collected</p>
                        <h4 class="fw-bold mb-0">Rs. {{ number_format($totalCollected, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3">
                        <i class="bi bi-receipt text-primary fs-4"></i>
                    </div>
                    <div>
                        <p class="text-muted mb-0 small">Total Payments</p>
                        <h4 class="fw-bold mb-0">{{ $totalPayments }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Search customer or invoice...">
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <input type="date" wire:model.live="dateFrom" class="form-control" placeholder="From Date">
                </div>
                <div class="col-6 col-md-3">
                    <input type="date" wire:model.live="dateTo" class="form-control" placeholder="To Date">
                </div>
                <div class="col-12 col-md-2">
                    <select wire:model.live="perPage" class="form-select">
                        <option value="10">10 per page</option>
                        <option value="25">25 per page</option>
                        <option value="50">50 per page</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Payments List - Desktop View --}}
    <div class="card border-0 shadow-sm d-none d-md-block">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Date</th>
                            <th>Customer</th>
                            <th>Invoice</th>
                            <th class="text-end">Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                        <tr>
                            <td class="ps-4">
                                <span class="fw-medium">{{ $payment->collected_at?->format('M d, Y') ?? 'N/A' }}</span>
                                <small class="d-block text-muted">{{ $payment->collected_at?->format('h:i A') ?? '' }}</small>
                            </td>
                            <td>
                                <span class="fw-medium">{{ $payment->customer->name ?? 'N/A' }}</span>
                            </td>
                            <td>
                                @if($payment->sale)
                                    <span class="fw-medium">{{ $payment->sale->invoice_number }}</span>
                                @else
                                    <span class="badge bg-secondary">Multiple/Opening</span>
                                @endif
                            </td>
                            <td class="text-end fw-semibold">Rs. {{ number_format($payment->amount, 2) }}</td>
                            <td>
                                <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</span>
                            </td>
                            <td>
                                @if($payment->status === 'approved')
                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Approved</span>
                                @elseif($payment->status === 'pending')
                                    <span class="badge bg-warning"><i class="bi bi-hourglass-split me-1"></i>Pending</span>
                                @else
                                    <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Rejected</span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <button wire:click="viewDetails({{ $payment->id }})" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> View
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                No payments found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Payments List - Mobile View --}}
    <div class="d-md-none">
        @forelse($payments as $payment)
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h6 class="fw-bold mb-1">{{ $payment->customer->name ?? 'N/A' }}</h6>
                        <small class="text-muted">
                            <i class="bi bi-calendar me-1"></i>{{ $payment->collected_at?->format('M d, Y h:i A') ?? 'N/A' }}
                        </small>
                    </div>
                    @if($payment->status === 'approved')
                        <span class="badge bg-success">Approved</span>
                    @elseif($payment->status === 'pending')
                        <span class="badge bg-warning">Pending</span>
                    @else
                        <span class="badge bg-danger">Rejected</span>
                    @endif
                </div>
                
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <small class="text-muted d-block">Invoice</small>
                        @if($payment->sale)
                            <span class="fw-semibold">{{ $payment->sale->invoice_number }}</span>
                        @else
                            <span class="badge bg-secondary">Multiple</span>
                        @endif
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Amount</small>
                        <span class="fw-bold text-success">Rs. {{ number_format($payment->amount, 2) }}</span>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Method</small>
                        <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</span>
                    </div>
                </div>
                
                <button wire:click="viewDetails({{ $payment->id }})" class="btn btn-sm btn-outline-primary w-100">
                    <i class="bi bi-eye me-2"></i> View Details
                </button>
            </div>
        </div>
        @empty
        <div class="text-center py-5 text-muted">
            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
            <p>No payments found.</p>
        </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $payments->links('livewire.custom-pagination') }}
    </div>

    {{-- Payment Details Modal --}}
    @if($showDetailsModal && $selectedPayment)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-receipt me-2"></i>Payment Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeDetailsModal"></button>
                </div>
                <div class="modal-body">
                    {{-- Customer Info --}}
                    <div class="bg-light rounded p-3 mb-4">
                        <h6 class="fw-bold mb-3"><i class="bi bi-person me-2"></i>Customer Information</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-2"><strong>Name:</strong> {{ $selectedPayment->customer->name ?? 'N/A' }}</p>
                                <p class="mb-0"><strong>Phone:</strong> {{ $selectedPayment->customer->phone ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-0"><strong>Type:</strong> {{ ucfirst($selectedPayment->customer->type ?? 'N/A') }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Payment Info --}}
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Payment Date:</strong> {{ $selectedPayment->collected_at?->format('M d, Y H:i') ?? 'N/A' }}</p>
                            <p class="mb-2"><strong>Amount:</strong> <span class="fw-bold text-success">Rs. {{ number_format($selectedPayment->amount, 2) }}</span></p>
                            <p class="mb-2"><strong>Method:</strong> <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $selectedPayment->payment_method)) }}</span></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Status:</strong> 
                                @if($selectedPayment->status === 'approved')
                                    <span class="badge bg-success">Approved</span>
                                @elseif($selectedPayment->status === 'pending')
                                    <span class="badge bg-warning">Pending</span>
                                @else
                                    <span class="badge bg-danger">Rejected</span>
                                @endif
                            </p>
                            @if($selectedPayment->payment_reference)
                            <p class="mb-2"><strong>Reference:</strong> {{ $selectedPayment->payment_reference }}</p>
                            @endif
                            @if($selectedPayment->notes)
                            <p class="mb-0"><strong>Notes:</strong> {{ $selectedPayment->notes }}</p>
                            @endif
                        </div>
                    </div>

                    {{-- Cheque Details --}}
                    @if($selectedPayment->payment_method === 'cheque' && $selectedPayment->cheque)
                    <div class="bg-light rounded p-3 mb-4">
                        <h6 class="fw-bold mb-3"><i class="bi bi-file-text me-2"></i>Cheque Details</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-2"><strong>Cheque No:</strong> {{ $selectedPayment->cheque->cheque_number }}</p>
                                <p class="mb-0"><strong>Bank:</strong> {{ $selectedPayment->cheque->bank_name }}</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-2"><strong>Date:</strong> {{ \Carbon\Carbon::parse($selectedPayment->cheque->cheque_date)->format('M d, Y') }}</p>
                                <p class="mb-0"><strong>Amount:</strong> Rs. {{ number_format($selectedPayment->cheque->cheque_amount, 2) }}</p>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Allocation Details --}}
                    @if($selectedPayment->allocations && $selectedPayment->allocations->count() > 0)
                    <h6 class="fw-bold mb-3">Payment Allocation</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Invoice</th>
                                    <th class="text-end">Allocated Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($selectedPayment->allocations as $allocation)
                                <tr>
                                    <td>
                                        @if($allocation->sale)
                                            {{ $allocation->sale->invoice_number }}
                                        @else
                                            <span class="badge bg-secondary">Opening Balance</span>
                                        @endif
                                    </td>
                                    <td class="text-end">Rs. {{ number_format($allocation->allocated_amount, 2) }}</td>
                                </tr>
                                @endforeach
                                <tr class="table-light">
                                    <td class="text-end"><strong>Total:</strong></td>
                                    <td class="text-end"><strong>Rs. {{ number_format($selectedPayment->amount, 2) }}</strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeDetailsModal">Close</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
