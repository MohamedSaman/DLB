<div class="container-fluid py-3">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-2">
                <i class="bi bi-receipt text-primary me-2"></i> My Sales
            </h3>
            <p class="text-muted mb-0">View and track your sales orders</p>
        </div>
        <a href="{{ route('salesman.dashboard') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i> Back to Dashboard
        </a>
    </div>

    {{-- Stats Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-warning bg-opacity-10">
                <div class="card-body text-center py-3">
                    <h4 class="fw-bold text-warning mb-0">{{ $pendingCount }}</h4>
                    <small class="text-muted">Pending Approval</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-success bg-opacity-10">
                <div class="card-body text-center py-3">
                    <h4 class="fw-bold text-success mb-0">{{ $approvedCount }}</h4>
                    <small class="text-muted">Approved</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-danger bg-opacity-10">
                <div class="card-body text-center py-3">
                    <h4 class="fw-bold text-danger mb-0">{{ $rejectedCount }}</h4>
                    <small class="text-muted">Rejected</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Search by invoice, sale ID or customer...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select wire:model.live="statusFilter" class="form-select">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="confirm">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select wire:model.live="deliveryFilter" class="form-select">
                        <option value="">All Delivery Status</option>
                        <option value="pending">Pending</option>
                        <option value="in_transit">In Transit</option>
                        <option value="delivered">Delivered</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Sales List --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Invoice</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Delivery</th>
                            <th>Date</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sales as $sale)
                        <tr>
                            <td class="ps-4">
                                <span class="fw-medium">{{ $sale->invoice_number }}</span>
                                <small class="d-block text-muted">{{ $sale->sale_id }}</small>
                            </td>
                            <td>{{ $sale->customer->name ?? 'N/A' }}</td>
                            <td class="fw-semibold">Rs. {{ number_format($sale->total_amount, 2) }}</td>
                            <td>
                                @if($sale->status === 'pending')
                                    <span class="badge bg-warning"><i class="bi bi-hourglass-split me-1"></i>Pending</span>
                                @elseif($sale->status === 'confirm')
                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Approved</span>
                                @else
                                    <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Rejected</span>
                                @endif
                            </td>
                            <td>
                                @if($sale->delivery_status === 'pending')
                                    <span class="badge bg-secondary">Pending</span>
                                @elseif($sale->delivery_status === 'in_transit')
                                    <span class="badge bg-info">In Transit</span>
                                @elseif($sale->delivery_status === 'delivered')
                                    <span class="badge bg-success">Delivered</span>
                                @else
                                    <span class="badge bg-dark">{{ ucfirst($sale->delivery_status ?? 'N/A') }}</span>
                                @endif
                            </td>
                            <td class="text-muted">{{ $sale->created_at->format('M d, Y') }}</td>
                            <td class="text-end pe-4">
                                <div class="btn-group">
                                    <button wire:click="viewDetails({{ $sale->id }})" class="btn btn-sm btn-outline-primary" title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    @if(in_array($sale->status, ['pending', 'confirm']))
                                    <a href="{{ route('salesman.billing.edit', $sale->id) }}" class="btn btn-sm btn-outline-warning" title="Edit Sale">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    @endif
                                    @if($sale->status === 'confirm')
                                    <button wire:click="openReturnModal({{ $sale->id }})" class="btn btn-sm btn-outline-danger" title="Return">
                                        <i class="bi bi-arrow-return-left"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                No sales found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $sales->links() }}
    </div>

    {{-- Details Modal --}}
    @if($showDetailsModal && $selectedSale)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-receipt me-2"></i>Sale Details - {{ $selectedSale->invoice_number }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="closeDetailsModal"></button>
                </div>
                <div class="modal-body">
                    {{-- Sale Info --}}
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Customer:</strong> {{ $selectedSale->customer->name ?? 'N/A' }}</p>
                            <p class="mb-1"><strong>Date:</strong> {{ $selectedSale->created_at->format('M d, Y H:i') }}</p>
                            <p class="mb-0"><strong>Status:</strong>
                                @if($selectedSale->status === 'pending')
                                    <span class="badge bg-warning">Pending</span>
                                @elseif($selectedSale->status === 'confirm')
                                    <span class="badge bg-success">Approved</span>
                                @else
                                    <span class="badge bg-danger">Rejected</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-6">
                            @if($selectedSale->approvedBy)
                            <p class="mb-1"><strong>Approved By:</strong> {{ $selectedSale->approvedBy->name }}</p>
                            <p class="mb-1"><strong>Approved At:</strong> {{ $selectedSale->approved_at?->format('M d, Y H:i') }}</p>
                            @endif
                            @if($selectedSale->rejection_reason)
                            <p class="mb-0 text-danger"><strong>Rejection Reason:</strong> {{ $selectedSale->rejection_reason }}</p>
                            @endif
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
                                    <th class="text-end">Price</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($selectedSale->items as $item)
                                <tr>
                                    <td>{{ $item->product_name }}</td>
                                    <td class="text-center">{{ $item->quantity }}</td>
                                    <td class="text-end">Rs. {{ number_format($item->unit_price, 2) }}</td>
                                    <td class="text-end">Rs. {{ number_format($item->total, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end fw-bold">Subtotal:</td>
                                    <td class="text-end">Rs. {{ number_format($selectedSale->subtotal, 2) }}</td>
                                </tr>
                                @if($selectedSale->discount_amount > 0)
                                <tr>
                                    <td colspan="3" class="text-end text-danger">Discount:</td>
                                    <td class="text-end text-danger">
                                        @if($selectedSale->discount_type === 'percentage')
                                            - {{ number_format($selectedSale->discount_amount, 2) }}%
                                        @else
                                            - Rs. {{ number_format($selectedSale->discount_amount, 2) }}
                                        @endif
                                    </td>
                                </tr>
                                @endif
                                <tr class="table-primary">
                                    <td colspan="3" class="text-end fw-bold">Total:</td>
                                    <td class="text-end fw-bold">Rs. {{ number_format($selectedSale->total_amount, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    {{-- Returns Section --}}
                    @if($saleReturns && count($saleReturns) > 0)
                    <h6 class="fw-bold mb-2 text-danger"><i class="bi bi-arrow-return-left me-2"></i>Returns</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-sm table-bordered border-danger">
                            <thead class="table-danger">
                                <tr>
                                    <th>Product</th>
                                    <th class="text-center">Returned Qty</th>
                                    <th class="text-end">Amount</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($saleReturns as $return)
                                <tr>
                                    <td>{{ $return->product->name ?? 'N/A' }}</td>
                                    <td class="text-center">{{ $return->return_quantity }}</td>
                                    <td class="text-end">Rs. {{ number_format($return->total_amount, 2) }}</td>
                                    <td class="text-muted">{{ $return->created_at->format('M d, Y') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif

                    {{-- Delivery Info --}}
                    @if($selectedSale->delivery_status)
                    <h6 class="fw-bold mb-2">Delivery Information</h6>
                    <div class="bg-light rounded p-3">
                        <p class="mb-1"><strong>Delivery Status:</strong>
                            @if($selectedSale->delivery_status === 'delivered')
                                <span class="badge bg-success">Delivered</span>
                            @else
                                <span class="badge bg-secondary">{{ ucfirst($selectedSale->delivery_status) }}</span>
                            @endif
                        </p>
                        @if($selectedSale->deliveredBy)
                        <p class="mb-1"><strong>Delivered By:</strong> {{ $selectedSale->deliveredBy->name }}</p>
                        <p class="mb-0"><strong>Delivered At:</strong> {{ $selectedSale->delivered_at?->format('M d, Y H:i') }}</p>
                        @endif
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    @if($selectedSale->status === 'confirm')
                    <button wire:click="openReturnModal({{ $selectedSale->id }})" class="btn btn-outline-danger">
                        <i class="bi bi-arrow-return-left me-2"></i>Create Return
                    </button>
                    @endif
                    <button type="button" class="btn btn-secondary" wire:click="closeDetailsModal">Close</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Edit Sale Modal --}}
    @if($showEditModal && $editingSale)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil-square me-2"></i>Edit Sale - {{ $editingSale->invoice_number }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="closeEditModal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        You can edit quantity and discounts for pending sales. Changes will be reflected when the sale is approved.
                    </div>

                    {{-- Sale Items --}}
                    <h6 class="fw-bold mb-3">Order Items</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th class="text-center" style="width: 100px;">Qty</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end" style="width: 100px;">Discount</th>
                                    <th class="text-end">Total</th>
                                    <th style="width: 50px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($editItems as $index => $item)
                                <tr>
                                    <td>
                                        {{ $item['product_name'] }}
                                        @if(isset($item['available']))
                                            <small class="text-success d-block">Available: {{ $item['available'] }}</small>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <input type="number" 
                                               wire:change="updateEditItemQuantity({{ $index }}, $event.target.value)"
                                               value="{{ $item['quantity'] }}"
                                               min="1"
                                               max="{{ $item['available'] ?? 999 }}"
                                               class="form-control form-control-sm text-center">
                                    </td>
                                    <td class="text-end">Rs. {{ number_format($item['unit_price'], 2) }}</td>
                                    <td class="text-end">
                                        <input type="number" 
                                               wire:model.live="editItems.{{ $index }}.discount"
                                               min="0"
                                               class="form-control form-control-sm text-end">
                                    </td>
                                    <td class="text-end fw-semibold">
                                        Rs. {{ number_format(($item['unit_price'] - ($item['discount'] ?? 0)) * $item['quantity'], 2) }}
                                    </td>
                                    <td class="text-center">
                                        @if(count($editItems) > 1)
                                        <button wire:click="removeEditItem({{ $index }})" class="btn btn-sm btn-link text-danger p-0">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Additional Discount --}}
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Additional Discount</label>
                            <div class="input-group">
                                @if($editDiscountType === 'percentage')
                                <input type="number" wire:model.live="editDiscount" class="form-control" min="0" max="100" step="0.01" placeholder="Percentage">
                                <span class="input-group-text">%</span>
                                @else
                                <span class="input-group-text">Rs.</span>
                                <input type="number" wire:model.live="editDiscount" class="form-control" min="0" step="0.01" placeholder="Fixed Amount">
                                @endif
                                <button class="btn btn-outline-secondary" type="button" wire:click="$toggle('editDiscountType')" title="Toggle between Fixed and Percentage">
                                    <i class="bi bi-arrow-left-right"></i> {{ $editDiscountType === 'percentage' ? '% ' : 'Rs ' }}
                                </button>
                            </div>
                            <small class="text-muted d-block mt-1">
                                @if($editDiscountType === 'percentage')
                                    Discount: {{ number_format($editDiscount, 2) }}% = Rs. {{ number_format(($this->editSubtotal * $editDiscount) / 100, 2) }}
                                @else
                                    Discount: Rs. {{ number_format($editDiscount, 2) }}
                                @endif
                            </small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Notes</label>
                            <textarea wire:model="editNotes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>

                    {{-- Totals --}}
                    <div class="card bg-light">
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <span>Rs. {{ number_format($this->editSubtotal, 2) }}</span>
                            </div>
                            @if($editDiscount > 0)
                            <div class="d-flex justify-content-between mb-2 text-danger">
                                <span>Discount:</span>
                                <span>
                                    @if($editDiscountType === 'percentage')
                                        - {{ number_format($editDiscount, 2) }}% (Rs. {{ number_format(($this->editSubtotal * $editDiscount) / 100, 2) }})
                                    @else
                                        - Rs. {{ number_format($editDiscount, 2) }}
                                    @endif
                                </span>
                            </div>
                            @endif
                            <div class="d-flex justify-content-between fw-bold fs-5 border-top pt-2">
                                <span>Total:</span>
                                <span class="text-primary">
                                    @php
                                        $discountAmount = $editDiscountType === 'percentage' 
                                            ? ($this->editSubtotal * $editDiscount) / 100 
                                            : min($editDiscount, $this->editSubtotal);
                                        $total = $this->editSubtotal - $discountAmount;
                                    @endphp
                                    Rs. {{ number_format($total, 2) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeEditModal">Cancel</button>
                    <button wire:click="saveEditedSale" class="btn btn-warning" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="saveEditedSale">
                            <i class="bi bi-check-circle me-2"></i>Save Changes
                        </span>
                        <span wire:loading wire:target="saveEditedSale">
                            <span class="spinner-border spinner-border-sm me-2"></span>Saving...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Return Modal --}}
    @if($showReturnModal && $selectedSale)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-arrow-return-left me-2"></i>Process Return - {{ $selectedSale->invoice_number }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeReturnModal"></button>
                </div>
                <div class="modal-body">
                    {{-- Customer Info --}}
                    <div class="bg-light rounded p-3 mb-4">
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted d-block">Customer</small>
                                <span class="fw-bold">{{ $selectedSale->customer->name ?? 'N/A' }}</span>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">Sale Date</small>
                                <span class="fw-medium">{{ $selectedSale->created_at->format('M d, Y') }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Return Items --}}
                    <h6 class="fw-bold mb-3">Select Items to Return</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th class="text-center">Original</th>
                                    <th class="text-center">Returned</th>
                                    <th class="text-center">Available</th>
                                    <th class="text-center" style="width: 100px;">Return Qty</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($returnItems as $index => $item)
                                <tr>
                                    <td>{{ $item['product_name'] }}</td>
                                    <td class="text-center">{{ $item['original_qty'] }}</td>
                                    <td class="text-center">{{ $item['returned_qty'] }}</td>
                                    <td class="text-center">
                                        <span class="badge {{ $item['available_qty'] > 0 ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $item['available_qty'] }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <input type="number"
                                               wire:change="updateReturnQty({{ $index }}, $event.target.value)"
                                               value="{{ $item['return_qty'] }}"
                                               min="0"
                                               max="{{ $item['available_qty'] }}"
                                               class="form-control form-control-sm text-center"
                                               {{ $item['available_qty'] <= 0 ? 'disabled' : '' }}>
                                    </td>
                                    <td class="text-end fw-semibold text-danger">
                                        Rs. {{ number_format($item['return_qty'] * $item['unit_price'], 2) }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="table-danger">
                                    <td colspan="5" class="text-end fw-bold">Total Return Amount:</td>
                                    <td class="text-end fw-bold">Rs. {{ number_format($this->returnTotal, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    {{-- Return Notes --}}
                    <div class="mb-3">
                        <label class="form-label fw-medium">Return Notes</label>
                        <textarea wire:model="returnNotes" class="form-control" rows="2" placeholder="Reason for return, condition notes, etc."></textarea>
                    </div>

                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Note:</strong> Returned items will be added back to stock. This action cannot be undone.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeReturnModal">Cancel</button>
                    <button wire:click="processReturn" class="btn btn-danger" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="processReturn">
                            <i class="bi bi-check-circle me-2"></i>Process Return
                        </span>
                        <span wire:loading wire:target="processReturn">
                            <span class="spinner-border spinner-border-sm me-2"></span>Processing...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
