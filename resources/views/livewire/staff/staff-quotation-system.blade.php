<div class="container-fluid py-3">
    {{-- Header --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="fw-bold text-dark mb-2">
                        <i class="bi bi-file-earmark-text text-success me-2"></i> Create Quotation
                    </h3>
                    <p class="text-muted">Quickly create professional quotations for customers</p>
                </div>
            </div>
        </div>
    </div>

    

    <div class="row">
        {{-- Customer Information --}}
        <div class="col-md-6 mb-4">
            <div class="card h-100 shadow-sm border-1">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fw-semibold">
                        <i class="bi bi-person me-2 text-primary"></i> Customer Information
                    </h5>
                    <button class="btn btn-sm btn-primary" wire:click="openCustomerModal">
                        <i class="bi bi-plus-circle me-1"></i> Add New Customer
                    </button>
                </div>

                <div class="card-body">
                    <div class="row g-3">
                        {{-- Select Customer (No default) --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Select Customer *</label>
                            <select class="form-select shadow-sm" wire:model.live="customerId">
                                <option value="">-- Select a Customer --</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}">
                                        {{ $customer->name }}
                                        @if($customer->phone)
                                            - {{ $customer->phone }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text mt-2">
                                Select an existing customer or add a new one.
                            </div>
                        </div>

                        {{-- Valid Until --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Valid Until *</label>
                            <input type="date" class="form-control shadow-sm" wire:model="validUntil">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Add Products --}}
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm border-1">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fw-semibold">
                        <i class="bi bi-search me-2 text-success"></i> Add Products
                    </h5>
                </div>

                <div class="card-body" style="position: relative;">
                    {{-- Search Field --}}
                    <div class="mb-3" style="position: relative; z-index: 10;">
                        <input type="text" class="form-control shadow-sm"
                            wire:model.live="search"
                            placeholder="Search by product name, code, or model...">
                    </div>

                    {{-- Search Results Dropdown --}}
                    @if($search && count($searchResults) > 0)
                        <div class="search-results-dropdown border rounded shadow-lg" style="position: absolute; top: 55px; left: 15px; right: 15px; max-height: 400px; overflow-y: auto; background: white; z-index: 1050; min-width: 300px;">
                            @foreach($searchResults as $product)
                                <div class="p-3 border-bottom d-flex justify-content-between align-items-center search-result-item"
                                    wire:key="product-{{ $product['id'] }}"
                                    style="cursor: pointer; transition: background-color 0.2s;">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 fw-semibold">{{ $product['name'] }}</h6>
                                        <p class="text-muted small mb-0">
                                            Code: {{ $product['code'] }} | Model: {{ $product['model'] }}
                                        </p>
                                        <p class="text-success small mb-0">
                                            Rs.{{ number_format($product['price'], 2) }} | Stock: {{ $product['stock'] }}
                                        </p>
                                    </div>
                                    <button class="btn btn-sm btn-success ms-2" wire:click="addToCart({{ json_encode($product) }})">
                                        <i class="bi bi-plus-circle"></i> Add
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @elseif($search && count($searchResults) == 0)
                        <div class="alert alert-info mb-3" style="position: absolute; top: 55px; left: 15px; right: 15px; z-index: 1050;">
                            <i class="bi bi-info-circle me-2"></i> No products found
                        </div>
                    @endif

                    @if(!$search)
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-search display-5 mb-3"></i>
                            <p>Start typing to search for products...</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Quotation Items --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-1">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fw-semibold">
                        <i class="bi bi-cart me-2 text-info"></i> Quotation Items
                        <span class="badge bg-info ms-2">{{ count($cart) }} Items</span>
                    </h5>
                </div>

                <div class="card-body">
                    @if(count($cart) > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="50">#</th>
                                        <th>Product</th>
                                        <th width="120">Unit Price</th>
                                        <th width="100">Quantity</th>
                                        <th width="120">Discount/Unit</th>
                                        <th width="120">Total</th>
                                        <th width="60" class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($cart as $index => $item)
                                        <tr wire:key="cart-{{ $index }}">
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                <strong>{{ $item['name'] }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $item['code'] }}</small>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm" step="0.01" 
                                                    value="{{ number_format($item['price'], 2, '.', '') }}"
                                                    wire:change="updateUnitPrice({{ $index }}, $event.target.value)">
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm text-center" 
                                                    value="{{ $item['quantity'] }}" min="1"
                                                    wire:change="updateQuantity({{ $index }}, $event.target.value)">
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm" step="0.01"
                                                    value="{{ number_format($item['discount'], 2, '.', '') }}"
                                                    wire:change="updateDiscount({{ $index }}, $event.target.value)">
                                            </td>
                                            <td>
                                                <strong class="text-success">Rs.{{ number_format($item['total'], 2) }}</strong>
                                            </td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-danger" wire:click="removeFromCart({{ $index }})">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="row mt-4 ms-auto" style="width: 350px;">
                            <div class="d-flex justify-content-between border-top pt-3 mb-2">
                                <strong>Subtotal:</strong>
                                <strong>Rs.{{ number_format($subtotal, 2) }}</strong>
                            </div>
                            <div class="d-flex justify-content-between border-bottom pb-3">
                                <strong>Item Discounts:</strong>
                                <strong class="text-danger">-Rs.{{ number_format($totalDiscount, 2) }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mt-3 mb-2">
                                <strong>After Item Discounts:</strong>
                                <strong>Rs.{{ number_format($subtotalAfterItemDiscounts, 2) }}</strong>
                            </div>

                            {{-- Additional Discount --}}
                            <div class="row g-2 my-3">
                                <div class="col-6">
                                    <select class="form-select form-select-sm" wire:model.live="additionalDiscountType">
                                        <option value="fixed">Fixed</option>
                                        <option value="percentage">Percentage</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <input type="number" class="form-control form-control-sm" step="0.01"
                                        wire:model.live="additionalDiscount"
                                        placeholder="Additional Discount">
                                </div>
                            </div>

                            <div class="d-flex justify-content-between border-top pt-3 mb-2">
                                <strong>Additional Discount:</strong>
                                <strong class="text-danger">-Rs.{{ number_format($additionalDiscountAmount, 2) }}</strong>
                            </div>
                            <div class="d-flex justify-content-between border-bottom pb-3">
                                <h5 class="mb-0">Grand Total:</h5>
                                <h5 class="mb-0 text-success">Rs.{{ number_format($grandTotal, 2) }}</h5>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button class="btn btn-danger me-2" wire:click="clearCart">
                                <i class="bi bi-trash me-1"></i> Clear All Items
                            </button>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-cart-x display-3 text-muted mb-3"></i>
                            <p class="text-muted">No items added yet. Search and add products above.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Terms & Conditions --}}
    <div class="row mb-4">
        <div class="col-6">
            <div class="card shadow-sm border-1">
                <div class="card-header">
                    <h5 class="card-title mb-0 fw-semibold">
                        <i class="bi bi-file-text me-2 text-warning"></i> Terms & Conditions
                    </h5>
                </div>

                <div class="card-body">
                    <textarea class="form-control" rows="5" wire:model="termsConditions" placeholder="Add terms and conditions..."></textarea>
                </div>
            </div>
        </div>

        {{-- Notes --}}
        <div class="col-6">
            <div class="card shadow-sm border-1">
                <div class="card-header">
                    <h5 class="card-title mb-0 fw-semibold">
                        <i class="bi bi-sticky me-2 text-secondary"></i> Notes
                    </h5>
                </div>

                <div class="card-body">
                    <textarea class="form-control" rows="3" wire:model="notes" placeholder="Add any additional notes..."></textarea>
                </div>
            </div>
        </div>
    </div>

    {{-- Action Buttons --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-end gap-2">
                <button class="btn btn-secondary" wire:click="createNewQuotation">
                    <i class="bi bi-arrow-clockwise me-1"></i> Reset
                </button>
                <button class="btn btn-success btn-lg" wire:click="createQuotation">
                    <i class="bi bi-check-circle me-1"></i> Create Quotation
                </button>
            </div>
        </div>
    </div>

    {{-- Quotation Modal --}}
    @if($showQuotationModal && $createdQuotation)
        <div class="modal show d-block" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-body p-0">
                        <div class="quotation-preview p-4" id="quotationPrintContent">

                            {{-- Screen Only Header --}}
                            <div class="screen-only-header mb-4">
                                <div class="text-end">
                                    <button type="button" class="btn-close" wire:click="closeModal"></button>
                                </div>
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    {{-- Left: Logo --}}
                                    <div style="flex: 0 0 150px;">
                                        <img src="{{ asset('images/HARDMEN.png') }}" alt="Logo" class="img-fluid" style="max-height:80px;">
                                    </div>

                                    {{-- Center: Company Name --}}
                                    <div class="text-center" style="flex: 1;">
                                        <h2 class="mb-0 fw-bold" style="font-size: 2.5rem; letter-spacing: 2px;">DLB ENTERPRISES</h2>
                                        <p class="mb-0 text-muted small">TOOLS WITH POWER</p>
                                    </div>

                                    {{-- Right: Quotation --}}
                                    <div class="text-end" style="flex: 0 0 150px;">
                                        <h5 class="mb-0 fw-bold"></h5>
                                        <h6 class="mb-0 text-muted">QUOTATION</h6>
                                    </div>
                                </div>
                                <hr class="my-2" style="border-top: 2px solid #000;">
                            </div>

                            {{-- Customer & Quotation Details Side by Side --}}
                            <div class="row mb-3 invoice-info-row">
                                <div class="col-6">
                                    <p class="mb-1"><strong>Customer :</strong></p>
                                    <p class="mb-0">{{ $createdQuotation->customer_name }}</p>
                                    <p class="mb-0">{{ $createdQuotation->customer_address }}</p>
                                    <p class="mb-0"><strong>Tel:</strong> {{ $createdQuotation->customer_phone }}</p>
                                </div>
                                <div class="col-6 text-end">
                                    <table class="table-borderless ms-auto" style="width: auto; display: inline-table;">
                                        <tr>
                                            <td class="pe-3"><strong>Quotation #</strong></td>
                                            <td>{{ $createdQuotation->quotation_number }}</td>
                                        </tr>
                                        <tr>
                                            <td class="pe-3"><strong>Date</strong></td>
                                            <td>{{ $createdQuotation->quotation_date->format('d/m/Y') }}</td>
                                        </tr>
                                        <tr>
                                            <td class="pe-3"><strong>Valid Until</strong></td>
                                            <td>{{ $createdQuotation->valid_until->format('d/m/Y') }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            {{-- Items Table --}}
                            <div class="table-responsive mb-3">
                                <table class="table table-bordered invoice-table">
                                    <thead>
                                        <tr>
                                            <th width="40" class="text-center">#</th>
                                            <th>DESCRIPTION</th>
                                            <th width="80" class="text-center">QTY</th>
                                            <th width="120" class="text-end">UNIT PRICE</th>
                                            <th width="100" class="text-end">DISCOUNT</th>
                                            <th width="120" class="text-end">TOTAL</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($createdQuotation->items as $index => $item)
                                        <tr>
                                            <td class="text-center">{{ $index + 1 }}</td>
                                            <td>{{ $item['product_name'] }}<br><small class="text-muted">{{ $item['product_code'] }}</small></td>
                                            <td class="text-center">{{ $item['quantity'] }}</td>
                                            <td class="text-end">Rs.{{ number_format($item['unit_price'], 2) }}</td>
                                            <td class="text-end">Rs.{{ number_format($item['discount_per_unit'], 2) }}</td>
                                            <td class="text-end">Rs.{{ number_format($item['total'], 2) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="totals-row">
                                            <td colspan="5" class="text-end"><strong>Subtotal</strong></td>
                                            <td class="text-end"><strong>Rs.{{ number_format($createdQuotation->subtotal, 2) }}</strong></td>
                                        </tr>
                                        @if($createdQuotation->discount_amount > 0)
                                        <tr class="totals-row">
                                            <td colspan="5" class="text-end"><strong>Discount</strong></td>
                                            <td class="text-end"><strong>-Rs.{{ number_format($createdQuotation->discount_amount, 2) }}</strong></td>
                                        </tr>
                                        @endif
                                        <tr class="totals-row grand-total">
                                            <td colspan="5" class="text-end"><strong>Grand Total</strong></td>
                                            <td class="text-end"><strong>Rs.{{ number_format($createdQuotation->total_amount, 2) }}</strong></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            {{-- Terms & Conditions --}}
                            @if($createdQuotation->terms_conditions)
                            <div class="mb-3">
                                <h6 class="fw-bold">Terms & Conditions:</h6>
                                <p style="white-space: pre-line; font-size: 0.9rem;">{{ $createdQuotation->terms_conditions }}</p>
                            </div>
                            @endif

                            {{-- Notes --}}
                            @if($createdQuotation->notes)
                            <div class="mb-3">
                                <h6 class="fw-bold">Notes:</h6>
                                <p style="white-space: pre-line; font-size: 0.9rem;">{{ $createdQuotation->notes }}</p>
                            </div>
                            @endif

                            {{-- Footer Note --}}
                            <div class="invoice-footer mt-4">
                                <div class="row text-center mb-3">
                                    <div class="col-4">
                                        <p class=""><strong>.............................</strong></p>
                                        <p class="mb-2"><strong>Checked By</strong></p>
                                    </div>
                                    <div class="col-4">
                                        <p class=""><strong>.............................</strong></p>
                                        <p class="mb-2"><strong>Authorized Officer</strong></p>
                                    </div>
                                    <div class="col-4">
                                        <p class=""><strong>.............................</strong></p>
                                        <p class="mb-2"><strong>Customer Stamp</strong></p>
                                    </div>
                                </div>
                                <div class="border-top pt-3">
                                    <p class="text-center"><strong>ADDRESS :</strong> Sample address</p>
                                    <p class="text-center"><strong>TEL :</strong> (077) 1234567, <strong>EMAIL :</strong> Sample email</p>
                                    <p class="text-center mt-2" style="font-size: 11px;"><strong>This quotation is valid until {{ $createdQuotation->valid_until->format('d/m/Y') }}.</strong></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Footer Buttons --}}
                    <div class="modal-footer justify-content-center">
                        <button type="button" class="btn btn-outline-primary me-2" onclick="window.open('/staff/print/quotation/{{ $createdQuotation->id }}', '_blank')">
                            <i class="bi bi-printer me-2"></i>Print
                        </button>
                        <button type="button" class="btn btn-success" wire:click="downloadQuotation">
                            <i class="bi bi-download me-2"></i>Download PDF
                        </button>
                        <button type="button" class="btn btn-secondary" wire:click="closeModal">
                            <i class="bi bi-x-circle me-2"></i>Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Customer Modal --}}
    @if($showCustomerModal)
        <div class="modal show d-block" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="bi bi-person-plus me-2"></i> Add New Customer
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeCustomerModal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold">Customer Name *</label>
                                <input type="text" class="form-control" wire:model="customerName" placeholder="Enter customer name">
                                @error('customerName') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Phone</label>
                                <input type="text" class="form-control" wire:model="customerPhone" placeholder="Phone number">
                                @error('customerPhone') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Email</label>
                                <input type="email" class="form-control" wire:model="customerEmail" placeholder="Email address">
                                @error('customerEmail') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Address *</label>
                                <input type="text" class="form-control" wire:model="customerAddress" placeholder="Customer address">
                                @error('customerAddress') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Customer Type *</label>
                                <select class="form-select" wire:model="customerType">
                                    <option value="retail">Retail</option>
                                    <option value="wholesale">Wholesale</option>
                                    <option value="business">Business</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Business Name</label>
                                <input type="text" class="form-control" wire:model="businessName" placeholder="Business name (optional)">
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-secondary" wire:click="closeCustomerModal">Cancel</button>
                        <button class="btn btn-primary" wire:click="createCustomer">
                            <i class="bi bi-check-circle me-1"></i> Create Customer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Check for flash messages and show Swal alerts
        @if (session()->has('success'))
            Swal.fire('Success!', '{{ session('success') }}', 'success');
        @endif

        @if (session()->has('error'))
            Swal.fire('Error!', '{{ session('error') }}', 'error');
        @endif
    });

    // Listen for Livewire events to show Swal alerts
    Livewire.on('showSuccess', (message) => {
        Swal.fire('Success!', message, 'success');
    });

    Livewire.on('showError', (message) => {
        Swal.fire('Error!', message, 'error');
    });
</script>
@endpush

@push('styles')
<style>
    .search-results-dropdown {
        border: 1px solid #dee2e6 !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
    }

    .search-result-item {
        transition: background-color 0.2s ease;
    }

    .search-result-item:hover {
        background-color: #f8f9fa;
    }

    .search-result-item:last-child {
        border-bottom: none;
    }

    /* Quotation/Invoice Styles */
    .quotation-preview {
        background: white;
        font-family: Arial, sans-serif;
    }

    .invoice-table {
        width: 100%;
        border-collapse: collapse;
    }

    .invoice-table th,
    .invoice-table td {
        padding: 8px;
        border: 1px solid #ddd;
    }

    .invoice-table th {
        background-color: #f8f9fa;
        font-weight: bold;
    }

    .totals-row td {
        border-top: 1px solid #000;
    }

    .grand-total td {
        font-size: 1.1em;
        background-color: #f8f9fa;
        border-top: 2px solid #000 !important;
    }

    .invoice-info-row {
        font-size: 0.9rem;
    }

    @media print {
        .screen-only-header .btn-close {
            display: none !important;
        }

        .modal-footer {
            display: none !important;
        }

        body * {
            visibility: hidden;
        }

        #quotationPrintContent,
        #quotationPrintContent * {
            visibility: visible;
        }

        #quotationPrintContent {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
    }
</style>
@endpush

