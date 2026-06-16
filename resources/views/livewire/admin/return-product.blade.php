<div>
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h3 class="fw-bold text-dark mb-2">
                <i class="bi bi-arrow-return-left text-success me-2"></i> Product Returns
            </h3>
            <p class="text-muted mb-0">Manage product returns and refunds efficiently</p>
        </div>
        <div>
            <button class="btn btn-primary">
                <i class="bi bi-download me-2"></i> Export Report
            </button>
        </div>
    </div>

    <!-- Customer Search and Invoice Selection -->
    <div class="row mb-4">
        <!-- Customer Search -->
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center border-bottom">
                    <h5 class="fw-bold mb-0">
                        <i class="bi bi-person-search text-primary me-2"></i> Customer Search
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Search Customer or Invoice #</label>
                        <input type="text" class="form-control" wire:model.live="searchCustomer" placeholder="Search by customer name or invoice number...">
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
                                        <i class="bi bi-person-circle fs-4 text-primary"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold">{{ $customer->name }}</div>
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
                                        <div class="fw-semibold">Invoice #{{ $invoice->invoice_number }}</div>
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
                    <div class="mt-3 p-3 bg-info bg-opacity-10 rounded border border-info">
                        <h6 class="fw-semibold text-info mb-2">Selected Customer</h6>
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="bi bi-person-check fs-4 text-info"></i>
                            </div>
                            <div>
                                <div class="fw-semibold">{{ $selectedCustomer->name }}</div>
                                <small class="text-muted">{{ $selectedCustomer->phone }} | {{ $selectedCustomer->email }}</small>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Customer Invoices -->
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center border-bottom">
                    <h5 class="fw-bold mb-0">
                        <i class="bi bi-receipt text-info me-2"></i> Recent Invoices
                    </h5>
                    <button class="btn btn-info btn-sm" wire:click="loadCustomerInvoices">
                        <i class="bi bi-arrow-clockwise me-1"></i> Refresh
                    </button>
                </div>
                <div class="card-body">
                    @if($selectedCustomer && count($customerInvoices) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table">
                                <tr>
                                    <th class="ps-4">Invoice #</th>
                                    <th>Date</th>
                                    <th>Total</th>
                                    <th class="text-end pe-4">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($customerInvoices as $invoice)
                                <tr>
                                    <td class="ps-4">
                                        <span class="fw-medium text-dark">{{ $invoice->invoice_number }}</span>
                                    </td>
                                    <td>{{ $invoice->created_at->format('Y-m-d') }}</td>
                                    <td>
                                        <span class="fw-bold text-dark">Rs.{{ number_format($invoice->total_amount, 2) }}</span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-success"
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
                        <p class="text-muted mb-0">No invoices found for this customer</p>
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
                    <h5 class="fw-bold mb-0 text-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i> Previous Returns for Invoice #{{ $selectedInvoice->invoice_number }}
                    </h5>
                </div>
                <div class="card-body overflow-auto">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead class="table">
                                <tr>
                                    <th>Product</th>
                                    <th>Total Returned</th>
                                    <th>Total Amount</th>
                                    <th>Return Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($previousReturns as $productId => $returnData)
                                <tr>
                                    <td>{{ $returnData['product_name'] }}</td>
                                    <td><span class="badge bg-warning">{{ $returnData['total_returned'] }} units</span></td>
                                    <td class="fw-bold">Rs.{{ number_format($returnData['total_amount'], 2) }}</td>
                                    <td>
                                        <div class="small">
                                            @foreach($returnData['returns'] as $return)
                                            <div class="mb-1">
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
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="fw-bold mb-0">
                                <i class="bi bi-receipt text-info me-2"></i> Invoice #{{ $selectedInvoice->invoice_number }} Items
                            </h5>
                            <p class="text-muted small mb-0">Select return quantity for each item. Discount will be recalculated automatically after return.</p>
                        </div>
                    </div>
                </div>
                <div class="card-body overflow-auto">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead class="table">
                                <tr>
                                    <th>Product</th>
                                    <th>Code</th>
                                    <th>Original Qty</th>
                                    <th>Returned</th>
                                    <th>Available</th>
                                    <th>Usable Rtn Qty</th>
                                    <th>Damage Rtn Qty</th>
                                    <th>Selling Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($returnItems as $index => $item)
                                <tr>
                                    <td>{{ $item['name'] }}</td>
                                    <td>{{ $item['product_code'] ?? 'N/A' }}</td>
                                    <td>{{ $item['original_qty'] }}</td>
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
                                        <input type="number" class="form-control form-control-sm" style="width: 80px;" 
                                            min="0" max="{{ $item['max_qty'] }}"
                                            wire:model.live="returnItems.{{ $index }}.usable_qty"
                                            @if($item['max_qty'] == 0) disabled @endif>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm border-danger text-danger" style="width: 80px;" 
                                            min="0" max="{{ $item['max_qty'] }}"
                                            wire:model.live="returnItems.{{ $index }}.damage_qty"
                                            @if($item['max_qty'] == 0) disabled @endif>
                                    </td>
                                    <td class="fw-bold">Rs.{{ number_format($item['selling_price'], 2) }}</td>
                                    <td class="fw-bold text-success">
                                        Rs.{{ number_format(((int)($item['usable_qty'] ?? 0) + (int)($item['damage_qty'] ?? 0)) * $item['selling_price'], 2) }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end mt-3 bg-light p-3 rounded">
                        <span class="fw-bold fs-4 text-warning">Total Return Value: Rs.{{ number_format($totalReturnValue, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-end mt-2">
                        <button class="btn btn-success px-4" wire:click="processReturn">
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
            <div class="modal-content">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-arrow-return-left me-2"></i> Confirm Product Return
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
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
                        <table class="table table-bordered">
                            <thead class="table">
                                <tr>
                                    <th>Product</th>
                                    <th>Usable Qty</th>
                                    <th>Damage Qty</th>
                                    <th>Selling Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($returnItems as $item)
                                @if(((int)($item['usable_qty'] ?? 0) + (int)($item['damage_qty'] ?? 0)) > 0)
                                <tr>
                                    <td>{{ $item['name'] }}</td>
                                    <td>{{ (int)($item['usable_qty'] ?? 0) }}</td>
                                    <td class="text-danger fw-bold">{{ (int)($item['damage_qty'] ?? 0) }}</td>
                                    <td>Rs.{{ number_format($item['selling_price'], 2) }}</td>
                                    <td class="fw-bold">Rs.{{ number_format(((int)($item['usable_qty'] ?? 0) + (int)($item['damage_qty'] ?? 0)) * $item['selling_price'], 2) }}</td>
                                </tr>
                                @endif
                                @endforeach
                            </tbody>
                            <tfoot class="table">
                                <tr>
                                    <td colspan="4" class="text-end fw-bold">Total Return Amount:</td>
                                    <td class="fw-bold text-success">Rs.{{ number_format($totalReturnValue, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-success" wire:click="confirmReturn">Confirm Return</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Invoice Details Modal -->
    <div wire:ignore.self class="modal fade" id="invoiceModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-receipt me-2"></i> Invoice Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
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
                        <table class="table table-bordered">
                            <thead class="table">
                                <tr>
                                    <th>Product</th>
                                    <th>Code</th>
                                    <th>Qty</th>
                                    <th>Unit Price</th>
                                    <th>Item Disc.</th>
                                    <th>Overall Disc.</th>
                                    <th>Net Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invoiceModalData['items'] as $item)
                                <tr>
                                    <td>{{ $item['product_name'] }}</td>
                                    <td>{{ $item['product_code'] }}</td>
                                    <td>{{ $item['quantity'] }}</td>
                                    <td>Rs.{{ number_format($item['unit_price'], 2) }}</td>
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
                                    <td class="fw-bold">Rs.{{ number_format($item['net_price'], 2) }}</td>
                                    <td class="fw-bold">Rs.{{ number_format($item['total'], 2) }}</td>
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
    .table{
        color:black !important;
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