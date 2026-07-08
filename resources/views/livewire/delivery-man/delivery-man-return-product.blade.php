<div>
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h3 class="fw-bold text-dark mb-2">
                <i class="bi bi-arrow-return-left text-orange me-2"></i> Product Returns
            </h3>
            <p class="text-muted mb-0">Manage product returns for pending deliveries</p>
        </div>
    </div>

    <!-- Customer Search and Invoice Selection -->
    <div class="row mb-4">
        <!-- Customer Search -->
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center border-bottom">
                    <h5 class="fw-bold mb-0 text-orange">
                        <i class="bi bi-person-search me-2"></i> Customer Search
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Search Customer or Pending Invoice #</label>
                        <input type="text" class="form-control" wire:model.live="searchCustomer" placeholder="Search by name, phone or invoice number...">
                    </div>

                    @if($searchCustomer && (count($customers) > 0 || count($customerInvoices) > 0))
                    <div class="border rounded p-3 bg-light">
                        <h6 class="fw-semibold mb-2">Search Results</h6>
                        <div class="list-group mb-2">
                            @foreach($customers as $customer)
                            <button class="list-group-item list-group-item-action p-2"
                                wire:click="selectCustomer({{ $customer->id }})"
                                type="button">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="bi bi-person-circle fs-4 text-orange"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold text-dark">{{ $customer->name }}</div>
                                        <small class="text-muted">{{ $customer->phone }} | {{ $customer->email }}</small>
                                    </div>
                                </div>
                            </button>
                            @endforeach
                        </div>
                        <div class="list-group">
                            @foreach($customerInvoices as $invoice)
                            @if(str_contains($invoice->invoice_number, $searchCustomer))
                            <button class="list-group-item list-group-item-action p-2"
                                wire:click="selectInvoiceForReturn({{ $invoice->id }})"
                                type="button">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="bi bi-receipt fs-4 text-info"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold text-dark">Invoice #{{ $invoice->invoice_number }}</div>
                                        <small class="text-muted">{{ $invoice->created_at->format('Y-m-d') }} | Rs.{{ number_format($invoice->total_amount, 2) }}</small>
                                    </div>
                                </div>
                            </button>
                            @endif
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if($selectedCustomer)
                    <div class="mt-3 p-3 bg-warning bg-opacity-10 rounded border border-warning">
                        <h6 class="fw-semibold text-orange mb-2">Selected Customer</h6>
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="bi bi-person-check fs-4 text-orange"></i>
                            </div>
                            <div>
                                <div class="fw-semibold text-dark">{{ $selectedCustomer->name }}</div>
                                <small class="text-muted">{{ $selectedCustomer->phone }} | {{ $selectedCustomer->email }}</small>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Customer Invoices (Pending Delivery Only) -->
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center border-bottom">
                    <h5 class="fw-bold mb-0 text-orange">
                        <i class="bi bi-receipt me-2"></i> Pending Invoices
                    </h5>
                    <button class="btn btn-outline-orange btn-sm" wire:click="loadCustomerInvoices">
                        <i class="bi bi-arrow-clockwise me-1"></i> Refresh
                    </button>
                </div>
                <div class="card-body">
                    @if($selectedCustomer && count($customerInvoices) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th class="ps-3 text-dark">Invoice #</th>
                                    <th class="text-dark">Date</th>
                                    <th class="text-dark">Total</th>
                                    <th class="text-end pe-3 text-dark">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($customerInvoices as $invoice)
                                <tr>
                                    <td class="ps-3">
                                        <span class="fw-medium text-dark">{{ $invoice->invoice_number }}</span>
                                    </td>
                                    <td class="text-dark">{{ $invoice->created_at->format('Y-m-d') }}</td>
                                    <td>
                                        <span class="fw-bold text-dark">Rs.{{ number_format($invoice->total_amount, 2) }}</span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-orange text-white"
                                                wire:click="selectInvoiceForReturn({{ $invoice->id }})">
                                                <i class="bi bi-check-circle me-1"></i> Select
                                            </button>
                                            <button class="btn btn-outline-info"
                                                wire:click="viewInvoice({{ $invoice->id }})">
                                                <i class="bi bi-eye me-1"></i> View
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-4">
                        <i class="bi bi-receipt-cutoff text-muted fs-1 mb-3"></i>
                        <p class="text-muted mb-0">No pending delivery invoices found for this customer</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($showReturnSection && $selectedInvoice)
    <!-- Previous Returns Section -->
    @if(!empty($previousReturns))
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-warning">
                <div class="card-header bg-warning bg-opacity-10 border-bottom border-warning">
                    <h5 class="fw-bold mb-0 text-orange">
                        <i class="bi bi-exclamation-triangle me-2"></i> Previous Returns for Invoice #{{ $selectedInvoice->invoice_number }}
                    </h5>
                </div>
                <div class="card-body overflow-auto">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0 text-dark">
                            <thead>
                                <tr>
                                    <th class="text-dark">Product</th>
                                    <th class="text-dark">Total Returned</th>
                                    <th class="text-dark">Total Amount</th>
                                    <th class="text-dark">Return Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($previousReturns as $productId => $returnData)
                                <tr>
                                    <td class="text-dark">{{ $returnData['product_name'] }}</td>
                                    <td><span class="badge bg-warning">{{ $returnData['total_returned'] }} units</span></td>
                                    <td class="fw-bold text-dark">Rs.{{ number_format($returnData['total_amount'], 2) }}</td>
                                    <td>
                                        <div class="small">
                                            @foreach($returnData['returns'] as $return)
                                            <div class="mb-1 text-dark">
                                                <span class="badge bg-secondary">{{ $return['quantity'] }} units</span>
                                                <span class="text-muted">- Rs.{{ number_format($return['amount'], 2) }}</span>
                                                <span class="text-muted">on {{ $return['date'] }}</span>
                                            </div>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Invoice Items for Return -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <div>
                        <h5 class="fw-bold mb-0 text-orange">
                            <i class="bi bi-receipt me-2"></i> Invoice #{{ $selectedInvoice->invoice_number }} Items
                        </h5>
                        <p class="text-muted small mb-0">Select return quantity for each item. Discount will be recalculated automatically after return.</p>
                    </div>
                </div>
                <div class="card-body overflow-auto">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0 text-dark">
                            <thead>
                                <tr>
                                    <th class="text-dark">Product</th>
                                    <th class="text-dark">Code</th>
                                    <th class="text-dark">Original Qty</th>
                                    <th class="text-dark">Returned</th>
                                    <th class="text-dark">Available</th>
                                    <th class="text-dark" style="width: 120px;">Usable Return Qty</th>
                                    <th class="text-dark" style="width: 120px;">Damage Return Qty</th>
                                    <th class="text-dark">Selling Price</th>
                                    <th class="text-dark">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($returnItems as $index => $item)
                                <tr>
                                    <td class="text-dark">{{ $item['name'] }}</td>
                                    <td class="text-dark">{{ $item['product_code'] ?? 'N/A' }}</td>
                                    <td class="text-dark">{{ $item['original_qty'] }}</td>
                                    <td>
                                        @if($item['already_returned'] > 0)
                                        <span class="badge bg-warning">{{ $item['already_returned'] }}</span>
                                        @else
                                        <span class="text-muted">0</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-success">{{ $item['max_qty'] }}</span>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm border-orange" 
                                            min="0" max="{{ $item['max_qty'] }}"
                                            wire:model.live="returnItems.{{ $index }}.usable_qty"
                                            @if($item['max_qty'] == 0) disabled @endif>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm border-danger text-danger" 
                                            min="0" max="{{ $item['max_qty'] }}"
                                            wire:model.live="returnItems.{{ $index }}.damage_qty"
                                            @if($item['max_qty'] == 0) disabled @endif>
                                    </td>
                                    <td class="fw-bold text-dark">Rs.{{ number_format($item['selling_price'], 2) }}</td>
                                    <td class="fw-bold text-success">
                                        Rs.{{ number_format(((int)($item['usable_qty'] ?? 0) + (int)($item['damage_qty'] ?? 0)) * $item['selling_price'], 2) }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end mt-3 bg-light p-3 rounded">
                        <span class="fw-bold fs-4 text-orange">Total Return Value: Rs.{{ number_format($totalReturnValue, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-end mt-2">
                        <button class="btn btn-danger me-2 px-4" wire:click="$set('showFullReturnConfirmModal', true)">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Full Invoice Return
                        </button>
                        <button class="btn btn-orange text-white px-4" wire:click="processReturn">
                            <i class="bi bi-check2-circle me-1"></i> Process Return
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Return Processing Modal -->
    <div wire:ignore.self class="modal fade" id="returnModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-orange text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-arrow-return-left me-2"></i> Confirm Product Return
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-dark">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Customer:</strong> {{ $selectedCustomer?->name }}</p>
                            <p><strong>Invoice:</strong> #{{ $selectedInvoice?->invoice_number }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Return Value:</strong> <span class="text-success fw-bold">Rs.{{ number_format($totalReturnValue, 2) }}</span></p>
                            <p><strong>Items:</strong> {{ count(array_filter($returnItems, fn($item) => ((int)($item['usable_qty'] ?? 0) + (int)($item['damage_qty'] ?? 0)) > 0)) }}</p>
                        </div>
                    </div>

                    <h6 class="fw-bold mb-3">Return Items Summary</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th class="text-dark">Product</th>
                                    <th class="text-dark">Usable Qty</th>
                                    <th class="text-dark">Damage Qty</th>
                                    <th class="text-dark">Selling Price</th>
                                    <th class="text-dark">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($returnItems as $item)
                                @if(((int)($item['usable_qty'] ?? 0) + (int)($item['damage_qty'] ?? 0)) > 0)
                                <tr>
                                    <td class="text-dark">{{ $item['name'] }}</td>
                                    <td class="text-dark">{{ (int)($item['usable_qty'] ?? 0) }}</td>
                                    <td class="text-danger fw-bold">{{ (int)($item['damage_qty'] ?? 0) }}</td>
                                    <td class="text-dark">Rs.{{ number_format($item['selling_price'], 2) }}</td>
                                    <td class="fw-bold text-dark">Rs.{{ number_format(((int)($item['usable_qty'] ?? 0) + (int)($item['damage_qty'] ?? 0)) * $item['selling_price'], 2) }}</td>
                                </tr>
                                @endif
                                @endforeach
                            </tbody>
                            <tfoot class="bg-light">
                                <tr>
                                    <td colspan="4" class="text-end fw-bold text-dark">Total Return Amount:</td>
                                    <td class="fw-bold text-success">Rs.{{ number_format($totalReturnValue, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-orange text-white" wire:click="confirmReturn">Confirm Return</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Invoice Details Modal -->
    <div wire:ignore.self class="modal fade" id="invoiceModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-receipt me-2"></i> Invoice Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-dark">
                    @if($invoiceModalData)
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p><strong>Invoice Number:</strong> {{ $invoiceModalData['invoice_number'] }}</p>
                            <p><strong>Customer:</strong> {{ $invoiceModalData['customer_name'] }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Date:</strong> {{ $invoiceModalData['date'] }}</p>
                            <p><strong>Total Amount:</strong> Rs.{{ number_format($invoiceModalData['total_amount'], 2) }}</p>
                            @if($invoiceModalData['overall_discount'] > 0)
                            <p><strong>Overall Discount:</strong> <span class="text-danger">Rs.{{ number_format($invoiceModalData['overall_discount'], 2) }}</span></p>
                            @endif
                        </div>
                    </div>

                    <h6 class="fw-bold mb-3">Invoice Items</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th class="text-dark">Product</th>
                                    <th class="text-dark">Code</th>
                                    <th class="text-dark">Qty</th>
                                    <th class="text-dark">Unit Price</th>
                                    <th class="text-dark">Item Disc.</th>
                                    <th class="text-dark">Overall Disc.</th>
                                    <th class="text-dark">Net Price</th>
                                    <th class="text-dark">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invoiceModalData['items'] as $item)
                                <tr>
                                    <td class="text-dark">{{ $item['product_name'] }}</td>
                                    <td class="text-dark">{{ $item['product_code'] }}</td>
                                    <td class="text-dark">{{ $item['quantity'] }}</td>
                                    <td class="text-dark">Rs.{{ number_format($item['unit_price'], 2) }}</td>
                                    <td>
                                        @if($item['item_discount'] > 0)
                                        <span class="text-danger">-Rs.{{ number_format($item['item_discount'], 2) }}</span>
                                        @else
                                        -
                                        @endif
                                    </td>
                                    <td>
                                        @if($item['overall_discount'] > 0)
                                        <span class="text-danger">-Rs.{{ number_format($item['overall_discount'], 2) }}</span>
                                        @else
                                        -
                                        @endif
                                    </td>
                                    <td class="fw-bold text-dark">Rs.{{ number_format($item['net_price'], 2) }}</td>
                                    <td class="fw-bold text-dark">Rs.{{ number_format($item['total'], 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Full Return Confirmation Modal --}}
    @if($showFullReturnConfirmModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5); z-index: 1060;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-danger text-white border-0">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm Full Return
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="$set('showFullReturnConfirmModal', false)"></button>
                </div>
                <div class="modal-body py-4 text-center">
                    <i class="bi bi-exclamation-octagon text-danger display-4 mb-3 d-block"></i>
                    <p class="mb-0 text-dark fs-5 fw-semibold text-black">Are you sure you want to fully return this invoice?</p>
                    <p class="text-muted small mt-2">All remaining items will be returned to the inventory as usable quantity. This action cannot be undone and will mark the delivery as cancelled.</p>
                </div>
                <div class="modal-footer border-0 bg-light justify-content-center">
                    <button type="button" class="btn btn-secondary px-4" wire:click="$set('showFullReturnConfirmModal', false)">Cancel</button>
                    <button type="button" class="btn btn-danger px-4" wire:click="processFullReturn" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="processFullReturn">
                            <i class="bi bi-check-circle me-1"></i> Yes, Return Full Invoice
                        </span>
                        <span wire:loading wire:target="processFullReturn">
                            <span class="spinner-border spinner-border-sm me-1"></span>Processing...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@push('styles')
<style>
    .card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
    }

    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
    }

    .card-header {
        background-color: white;
        border-bottom: 1px solid #dee2e6;
        border-radius: 12px 12px 0 0 !important;
        padding: 1.25rem 1.5rem;
    }

    .table th {
        border-top: none;
        font-weight: 600;
        color: #000;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        white-space: nowrap;
    }

    .table td {
        vertical-align: middle;
        padding: 0.75rem;
    }

    .btn-orange {
        background-color: #f58320;
        border-color: #f58320;
    }

    .btn-orange:hover, .btn-orange:focus {
        background-color: #e07010;
        border-color: #e07010;
        color: white;
    }

    .btn-outline-orange {
        color: #f58320;
        border-color: #f58320;
    }

    .btn-outline-orange:hover {
        background-color: #f58320;
        border-color: #f58320;
        color: white;
    }

    .text-orange {
        color: #f58320 !important;
    }

    .border-orange {
        border-color: #f58320 !important;
    }

    .btn-group-sm>.btn {
        padding: 0.25rem 0.5rem;
    }

    .modal-header {
        border-bottom: 1px solid #dee2e6;
    }

    .badge {
        font-size: 0.75em;
    }

    .table-hover tbody tr:hover {
        background-color: rgba(0, 0, 0, 0.025);
    }

    .border-warning {
        border-width: 2px !important;
    }
</style>
@endpush

@push('scripts')
<script>
    window.addEventListener('alert', event => {
        Swal.fire('Success', event.detail.message, 'success');
    });

    Livewire.on('show-return-modal', () => {
        var modalEl = document.getElementById('returnModal');
        var modal = new bootstrap.Modal(modalEl);
        modal.show();
    });

    Livewire.on('show-invoice-modal', () => {
        var modalEl = document.getElementById('invoiceModal');
        var modal = new bootstrap.Modal(modalEl);
        modal.show();
    });

    Livewire.on('close-return-modal', () => {
        var modalEl = document.getElementById('returnModal');
        var modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) {
            modal.hide();
        }
    });

    Livewire.on('reload-page', () => {
        window.location.reload();
    });
</script>
@endpush
