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
                <i class="bi bi-box-seam text-primary me-2"></i> Pending Deliveries
            </h3>
            <p class="text-muted mb-0">View and manage deliveries awaiting completion</p>
        </div>
        
    </div>

    {{-- Search --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-12">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Search by invoice, customer name or phone...">
                    </div>
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
                            <th>Address</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sales as $sale)
                        <tr>
                            <td class="ps-4">
                                <span class="fw-medium">{{ $sale->invoice_number }}</span>
                                <small class="d-block text-muted">{{ $sale->created_at->format('M d, Y') }}</small>
                            </td>
                            <td>
                                <span class="fw-medium">{{ $sale->customer->name ?? 'N/A' }}</span>
                                @if($sale->customer->phone ?? false)
                                <small class="d-block text-muted"><i class="bi bi-telephone me-1"></i>{{ $sale->customer->phone }}</small>
                                @endif
                            </td>
                            <td>
                                <small class="text-muted">{{ Str::limit($sale->customer->address ?? 'N/A', 40) }}</small>
                            </td>
                            <td>
                                <span class="fw-semibold">Rs. {{ number_format($sale->total_amount, 2) }}</span>
                                @if($sale->due_amount > 0)
                                <small class="d-block text-danger">Due: Rs. {{ number_format($sale->due_amount, 2) }}</small>
                                @endif
                            </td>
                            <td>
                                @if($sale->delivery_status === 'pending')
                                    <span class="badge bg-warning">Pending</span>
                                @elseif($sale->delivery_status === 'in_transit')
                                    <span class="badge bg-info">In Transit</span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <div class="btn-group">
                                    <button wire:click="viewDetails({{ $sale->id }})" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    @if($sale->delivery_status === 'pending')
                                    <button wire:click="showConfirmation('transit', {{ $sale->id }})" class="btn btn-sm btn-info text-white">
                                        <i class="bi bi-truck"></i>
                                    </button>
                                    @endif
                                    @if(in_array($sale->payment_status, ['pending', 'partial']))
                                    <button wire:click="openEditDiscountModal({{ $sale->id }})" class="btn btn-sm btn-warning" title="Edit Discount">
                                        <i class="bi bi-percent"></i>
                                    </button>
                                    @endif
                                    <button wire:click="showConfirmation('return', {{ $sale->id }})" class="btn btn-sm btn-outline-danger" title="Return Full Invoice">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                    </button>
                                    <button wire:click="showConfirmation('delivered', {{ $sale->id }})" class="btn btn-sm btn-success">
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-check-circle fs-1 text-success d-block mb-2"></i>
                                No pending deliveries! All caught up.
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
                        <small class="text-muted"><i class="bi bi-calendar me-1"></i>{{ $sale->created_at->format('M d, Y') }}</small>
                    </div>
                    @if($sale->delivery_status === 'pending')
                        <span class="badge bg-warning">Pending</span>
                    @elseif($sale->delivery_status === 'in_transit')
                        <span class="badge bg-info">In Transit</span>
                    @endif
                </div>
                
                <div class="mb-3">
                    <p class="mb-1"><strong><i class="bi bi-person me-2"></i>{{ $sale->customer->name ?? 'N/A' }}</strong></p>
                    @if($sale->customer->phone)
                    <p class="mb-1 text-muted"><i class="bi bi-telephone me-2"></i>{{ $sale->customer->phone }}</p>
                    @endif
                    @if($sale->customer->address)
                    <p class="mb-0 text-muted"><i class="bi bi-geo-alt me-2"></i>{{ $sale->customer->address }}</p>
                    @endif
                </div>
                
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <small class="text-muted d-block">Total Amount</small>
                        <span class="fw-bold">Rs. {{ number_format($sale->total_amount, 2) }}</span>
                    </div>
                    @if($sale->due_amount > 0)
                    <div class="col-6">
                        <small class="text-muted d-block">Due Amount</small>
                        <span class="fw-bold text-danger">Rs. {{ number_format($sale->due_amount, 2) }}</span>
                    </div>
                    @endif
                </div>
                
                <div class="d-grid gap-2">
                    <button wire:click="viewDetails({{ $sale->id }})" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-eye me-2"></i> View Details
                    </button>
                    @if($sale->delivery_status === 'pending')
                    <button wire:click="showConfirmation('transit', {{ $sale->id }})" class="btn btn-sm btn-info text-white">
                        <i class="bi bi-truck me-2"></i> Mark In Transit
                    </button>
                    @endif
                    @if(in_array($sale->payment_status, ['pending', 'partial']))
                    <button wire:click="openEditDiscountModal({{ $sale->id }})" class="btn btn-sm btn-warning">
                        <i class="bi bi-percent me-2"></i> Edit Discount
                    </button>
                    @endif
                    <button wire:click="showConfirmation('return', {{ $sale->id }})" class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-arrow-counterclockwise me-2"></i> Return Full Invoice
                    </button>
                    <button wire:click="showConfirmation('delivered', {{ $sale->id }})" class="btn btn-sm btn-success">
                        <i class="bi bi-check-circle me-2"></i> Mark Delivered
                    </button>
                </div>
            </div>
        </div>
        @empty
        <div class="text-center py-5 text-muted">
            <i class="bi bi-check-circle fs-1 text-success d-block mb-2"></i>
            <p>No pending deliveries! All caught up.</p>
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
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-box-seam me-2"></i>Delivery Details - {{ $selectedSale->invoice_number }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="closeDetailsModal"></button>
                </div>
                <div class="modal-body">
                    {{-- Customer Info --}}
                    <div class="bg-light rounded p-3 mb-4">
                        <h6 class="fw-bold mb-2"><i class="bi bi-person me-2"></i>Customer Information</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Name:</strong> {{ $selectedSale->customer->name ?? 'N/A' }}</p>
                                <p class="mb-1"><strong>Phone:</strong> {{ $selectedSale->customer->phone ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-0"><strong>Address:</strong> {{ $selectedSale->customer->address ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Order Info --}}
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Order Date:</strong> {{ $selectedSale->created_at->format('M d, Y H:i') }}</p>
                            <p class="mb-0"><strong>Created By:</strong> {{ $selectedSale->user->name ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Total Amount:</strong> <span class="fw-bold">Rs. {{ number_format($selectedSale->total_amount, 2) }}</span></p>
                            <p class="mb-0"><strong>Due Amount:</strong> <span class="fw-bold text-danger">Rs. {{ number_format($selectedSale->due_amount, 2) }}</span></p>
                        </div>
                    </div>

                    {{-- Items --}}
                    <h6 class="fw-bold mb-2">Order Items</h6>
                    <div class="table-responsive mb-4">
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
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    @if($selectedSale->delivery_status === 'pending')
                    <button wire:click="showConfirmation('transit', {{ $selectedSale->id }})" class="btn btn-info text-white">
                        <i class="bi bi-truck me-2"></i>Mark In Transit
                    </button>
                    @endif
                    <button wire:click="showConfirmation('return', {{ $selectedSale->id }})" class="btn btn-outline-danger">
                        <i class="bi bi-arrow-counterclockwise me-2"></i>Return Invoice
                    </button>
                    <button wire:click="showConfirmation('delivered', {{ $selectedSale->id }})" class="btn btn-success">
                        <i class="bi bi-check-circle me-2"></i>Mark Delivered
                    </button>
                    <button type="button" class="btn btn-secondary" wire:click="closeDetailsModal">Close</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Confirmation Modal --}}
    @if($showConfirmModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-exclamation-circle text-warning me-2"></i>Confirm Action
                    </h5>
                    <button type="button" class="btn-close" wire:click="closeConfirmModal"></button>
                </div>
                <div class="modal-body py-4">
                    <p class="mb-0 fs-6">
                        @if($confirmAction === 'transit')
                            Are you sure you want to mark this order as <strong class="text-info">In Transit</strong>?
                        @elseif($confirmAction === 'delivered')
                            Are you sure you want to mark this order as <strong class="text-success">Delivered</strong>?
                        @elseif($confirmAction === 'return')
                            Are you sure you want to <strong class="text-danger">fully return this invoice</strong>? This will restore all stock and adjust customer due.
                        @endif
                    </p>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary" wire:click="closeConfirmModal">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </button>
                    <button type="button" class="btn {{ $confirmAction === 'transit' ? 'btn-info text-white' : ($confirmAction === 'return' ? 'btn-danger' : 'btn-success') }}" wire:click="executeConfirmedAction">
                        <i class="bi bi-check-circle me-2"></i>Confirm
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Payment Modal --}}
    @if($showPaymentModal && $paymentSale)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-check-circle me-2"></i>Delivery Completed!
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closePaymentModal"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="text-center mb-4">
                        <i class="bi bi-exclamation-triangle text-warning fs-1 mb-3 d-block"></i>
                        <h6 class="fw-bold mb-3">Outstanding Payment</h6>
                        <p class="mb-2">Invoice: <strong>{{ $paymentSale->invoice_number }}</strong></p>
                        <p class="mb-2">Customer: <strong>{{ $paymentSale->customer->name ?? 'N/A' }}</strong></p>
                        <div class="alert alert-danger d-inline-block px-4 py-3 mt-3">
                            <h4 class="mb-0">Due Amount: <strong>Rs. {{ number_format($paymentSale->due_amount, 2) }}</strong></h4>
                        </div>
                    </div>
                    <p class="text-muted text-center mb-0">
                        This sale has an outstanding balance. Would you like to collect payment now?
                    </p>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary" wire:click="closePaymentModal">
                        <i class="bi bi-x-circle me-2"></i>Skip for Now
                    </button>
                    <button type="button" class="btn btn-primary" wire:click="goToPayment">
                        <i class="bi bi-cash-coin me-2"></i>Collect Payment
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Edit Discount Modal --}}
    @if($showEditDiscountModal && $editDiscountSale)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-percent me-2"></i>Edit Sale Discount
                    </h5>
                    <button type="button" class="btn-close" wire:click="closeEditDiscountModal"></button>
                </div>
                <div class="modal-body">
                    {{-- Sale Information --}}
                    <div class="bg-light rounded p-3 mb-4">
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-2"><strong>Invoice:</strong> {{ $editDiscountSale->invoice_number }}</p>
                                <p class="mb-0"><strong>Customer:</strong> {{ $editDiscountSale->customer->name ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-2"><strong>Date:</strong> {{ $editDiscountSale->created_at->format('M d, Y') }}</p>
                                <p class="mb-0"><strong>Status:</strong> 
                                    @if($editDiscountSale->delivery_status === 'pending')
                                        <span class="badge bg-warning">Pending</span>
                                    @elseif($editDiscountSale->delivery_status === 'in_transit')
                                        <span class="badge bg-info">In Transit</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Current Amounts --}}
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="card bg-light border-0">
                                <div class="card-body text-center">
                                    <small class="text-muted d-block mb-1">Subtotal</small>
                                    <h5 class="mb-0">Rs. {{ number_format($editDiscountSale->subtotal, 2) }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light border-0">
                                <div class="card-body text-center">
                                    <small class="text-muted d-block mb-1">Current Discount</small>
                                    <h5 class="mb-0">{{ $editDiscountSale->discount_amount ?? 0 }}%</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light border-0">
                                <div class="card-body text-center">
                                    <small class="text-muted d-block mb-1">Current Total</small>
                                    <h5 class="mb-0">Rs. {{ number_format($editDiscountSale->total_amount, 2) }}</h5>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Edit Discount --}}
                    <form wire:submit.prevent="updateDiscount">
                        <div class="mb-4">
                            <label class="form-label fw-semibold">New Discount Percentage</label>
                            <div class="input-group input-group-lg">
                                <input type="number" 
                                       wire:model.live="newDiscountPercentage" 
                                       class="form-control @error('newDiscountPercentage') is-invalid @enderror" 
                                       min="0" 
                                       max="100" 
                                       step="0.01"
                                       placeholder="Enter discount %">
                                <span class="input-group-text"><i class="bi bi-percent"></i></span>
                                @error('newDiscountPercentage')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <small class="text-muted">Enter a value between 0 and 100</small>
                        </div>

                        {{-- Preview New Amounts --}}
                        @if($newDiscountPercentage !== null && $newDiscountPercentage >= 0)
                        @php
                            $subtotal = $editDiscountSale->subtotal;
                            $newDiscountAmount = ($subtotal * $newDiscountPercentage) / 100;
                            $newTotal = $subtotal - $newDiscountAmount;
                            $paidAmount = $editDiscountSale->total_amount - $editDiscountSale->due_amount;
                            $newDue = max(0, $newTotal - $paidAmount);
                        @endphp
                        <div class="alert alert-info">
                            <h6 class="fw-bold mb-3"><i class="bi bi-calculator me-2"></i>Preview New Amounts</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <small class="text-muted d-block">Discount Amount</small>
                                    <strong>Rs. {{ number_format($newDiscountAmount, 2) }}</strong>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted d-block">New Total</small>
                                    <strong class="text-primary">Rs. {{ number_format($newTotal, 2) }}</strong>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted d-block">New Due Amount</small>
                                    <strong class="text-danger">Rs. {{ number_format($newDue, 2) }}</strong>
                                </div>
                            </div>
                            <hr class="my-2">
                            <small class="text-muted">
                                <strong>Already Paid:</strong> Rs. {{ number_format($paidAmount, 2) }}
                            </small>
                        </div>
                        @endif
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeEditDiscountModal">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-warning" wire:click="updateDiscount">
                        <i class="bi bi-check-circle me-2"></i>Update Discount
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
