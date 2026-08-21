<div class="container-fluid py-3">
    {{-- Flash Messages --}}
    @if (session()->has('success'))
    <div class="alert alert-success alert-dismissible fade show mb-4">
        <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if (session()->has('error'))
    <div class="alert alert-danger alert-dismissible fade show mb-4">
        <i class="bi bi-exclamation-circle me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if (session()->has('message'))
    <div class="alert alert-info alert-dismissible fade show mb-4">
        <i class="bi bi-info-circle me-2"></i> {{ session('message') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row">

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
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Select Customer *</label>
                            <select class="form-select shadow-sm" wire:model.live="customerId">
                                @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ $customer->name === 'Walking Customer' ? 'selected' : '' }}>
                                    {{ $customer->name }}
                                    @if($customer->phone)
                                    - {{ $customer->phone }}
                                    @endif
                                    @if($customer->name === 'Walking Customer')
                                    (Default)
                                    @endif
                                </option>
                                @endforeach
                            </select>

                            <div class="form-text mt-2">
                                @if($selectedCustomer && $selectedCustomer->name === 'Walking Customer')
                                <span class="text-info">
                                    <i class="bi bi-info-circle me-1"></i> Using default walking customer
                                </span>
                                @else
                                Select an existing customer or add a new one.
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Add Products Card --}}
            <div class="col-md-6 mb-4">
                <div class="card h-100 shadow-sm border-1">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0 fw-semibold">
                            <i class="bi bi-search me-2 text-success"></i> Add Products
                        </h5>
                    </div>
                    <div class="card-body position-relative">
                        <div class="mb-3">
                            <input type="text" class="form-control shadow-sm"
                                wire:model.live="search"
                                placeholder="Search by product name, code, or model...">
                        </div>

                        {{-- Search Results --}}
                        @if($search && count($searchResults) > 0)
                        <div class="search-results mt-1 position-absolute w-100 shadow-lg" style="max-height: 300px; max-width: 96%; z-index: 1055;">
                            @foreach($searchResults as $product)
                            <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-white rounded-1"
                                wire:key="product-{{ $product['id'] }}">
                                <div>
                                    <h6 class="mb-1 fw-semibold">{{ $product['name'] }}</h6>
                                    <p class="text-muted small mb-0">
                                        Code: {{ $product['code'] }} | Model: {{ $product['model'] }}
                                    </p>
                                    <p class="text-success small mb-0">
                                        Rs.{{ number_format($product['price'], 2) }} | Stock: {{ $product['stock'] }}
                                    </p>
                                </div>
                                <button class="btn btn-sm btn-outline-primary"
                                    wire:click="addToCart({{ json_encode($product) }})"
                                    {{ $product['stock'] <= 0 ? 'disabled' : '' }}>
                                    <i class="bi bi-plus-lg"></i> Add
                                </button>
                            </div>
                            @endforeach
                        </div>
                        @elseif($search)
                        <div class="text-center text-muted p-3">
                            <i class="bi bi-exclamation-circle me-1"></i> No products found
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>


        {{-- Sale Items Table --}}
        <div class="col-md-12 mb-4">
            <div class="card overflow-auto">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-cart me-2"></i>Sale Items
                    </h5>
                    <span class="badge bg-primary">{{ count($cart) }} items</span>
                </div>
                <div class="card-body p-0">
                    @if(count($cart) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="30">#</th>
                                    <th>Product</th>
                                    <th width="120">Unit Price</th>
                                    <th width="150">Quantity</th>
                                    <th width="120">Discount/Unit</th>
                                    <th width="120">Total</th>
                                    <th width="100" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cart as $index => $item)
                                <tr wire:key="{{ $item['key'] ?? 'cart_' . $index }}">
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <div>
                                            <strong>{{ $item['name'] }}</strong>
                                            <div class="text-muted small">
                                                {{ $item['code'] }} | {{ $item['model'] }}
                                            </div>
                                            <div class="text-info small">
                                                Stock: {{ $item['stock'] }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="fw-bold">
                                        <input type="number" class="form-control-sm text-primary" style="min-width:90px;"
                                            wire:change="updatePrice({{ $index }}, $event.target.value)"
                                            value="{{ $item['price'] }}" min="0" step="0.01"
                                            placeholder="0.00">
                                    </td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <button class="btn btn-outline-secondary" type="button"
                                                wire:click="decrementQuantity({{ $index }})">-</button>
                                            <input type="number" class="form-control text-center"
                                                wire:change="updateQuantity({{ $index }}, $event.target.value)"
                                                value="{{ $item['quantity'] }}" min="1" max="{{ $item['stock'] }}">
                                            <button class="btn btn-outline-secondary" type="button"
                                                wire:click="incrementQuantity({{ $index }})">+</button>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm text-danger"
                                            wire:change="updateDiscount({{ $index }}, $event.target.value)"
                                            value="{{ $item['discount'] }}" min="0" step="0.01"
                                            placeholder="0.00">
                                    </td>
                                    <td class="fw-bold">
                                        Rs.{{ number_format($item['total'], 2) }}
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-danger"
                                            wire:click="removeFromCart({{ $index }})"
                                            title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="5" class="text-end fw-bold">Subtotal:</td>
                                    <td class="fw-bold">Rs.{{ number_format($subtotal, 2) }}</td>
                                    <td></td>
                                </tr>

                                {{-- Additional Discount Section --}}
                                <tr>
                                    <td colspan="3" class="text-end fw-bold align-middle">
                                        Additional Discount:
                                        @if($additionalDiscount > 0)
                                        <button type="button" class=" text-danger p-0 ms-1 border-0 bg-opacity-0"
                                            wire:click="removeAdditionalDiscount" title="Remove discount">
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                        @endif
                                    </td>
                                    <td colspan="2">
                                        <div class="input-group input-group-sm">
                                            <input type="number"
                                                class="form-control form-control-sm text-danger"
                                                wire:model.live="additionalDiscount"
                                                min="0"
                                                step="{{ $additionalDiscountType === 'percentage' ? '1' : '0.01' }}"
                                                @if($additionalDiscountType==='percentage' ) max="100" @endif
                                                placeholder="0{{ $additionalDiscountType === 'percentage' ? '' : '.00' }}">

                                            <span class="input-group-text">
                                                {{ $additionalDiscountType === 'percentage' ? '%' : 'Rs.' }}
                                            </span>

                                            <button type="button"
                                                class="btn btn-outline-secondary"
                                                wire:click="toggleDiscountType"
                                                title="Switch Discount Type">
                                                <i class="bi bi-arrow-repeat"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="fw-bold text-danger">
                                        @if($additionalDiscount > 0)
                                        - Rs.{{ number_format($additionalDiscountAmount, 2) }}
                                        @if($additionalDiscountType === 'percentage')
                                        <div class="text-muted small">({{ $additionalDiscount }}%)</div>
                                        @endif
                                        @else
                                        <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td></td>
                                </tr>

                                {{-- Grand Total
                                <tr>
                                    <td colspan="5" class="text-end fw-bold fs-5">Grand Total:</td>
                                    <td class="fw-bold fs-5 text-primary">Rs.{{ number_format($grandTotal, 2) }}</td>
                                <td></td>
                                </tr> --}}
                            </tfoot>
                        </table>
                    </div>
                    @else
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-cart display-4 d-block mb-2"></i>
                        No items added yet
                    </div>
                    @endif
                </div>
                @if(count($cart) > 0)
                <div class="card-footer">
                    <button class="btn btn-danger" wire:click="clearCart">
                        <i class="bi bi-trash me-2"></i>Clear All Items
                    </button>
                </div>
                @endif
            </div>
        </div>

        {{-- Notes --}}
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-chat-text me-2"></i>Notes
                    </h5>
                </div>
                <div class="card-body">
                    <textarea class="form-control" wire:model="notes" rows="3"
                        placeholder="Add any notes for this sale..."></textarea>
                </div>
            </div>
        </div>

        {{-- Create Sale Button --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <div class="fw-bold fs-5">Grand Total</div>
                        <div class="fw-bold fs-5 text-primary">Rs.{{ number_format($grandTotal, 2) }}</div>
                    </div>
                    <button class="btn btn-success btn-lg px-5" wire:click="createSale"
                        {{ count($cart) == 0 ? 'disabled' : '' }}>
                        <i class="bi bi-cart-check me-2"></i>Complete Sale
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Add Customer Modal --}}
    @if($showCustomerModal)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-person-plus me-2"></i>Add New Customer
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeCustomerModal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name *</label>
                            <input type="text" class="form-control" wire:model="customerName" placeholder="Enter customer name">
                            @error('customerName') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone *</label>
                            <input type="text" class="form-control" wire:model="customerPhone" placeholder="Enter phone number">
                            @error('customerPhone') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" wire:model="customerEmail" placeholder="Enter email address">
                            @error('customerEmail') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Customer Type *</label>
                            <select class="form-select" wire:model="customerType">
                                <option value="retail">Retail</option>
                                <option value="wholesale">Wholesale</option>

                            </select>
                            @error('customerType') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Business Name</label>
                            <input type="text" class="form-control" wire:model="businessName" placeholder="Enter business name">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address *</label>
                            <textarea class="form-control" wire:model="customerAddress" rows="3" placeholder="Enter address"></textarea>
                            @error('customerAddress') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeCustomerModal">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-primary" wire:click="createCustomer">
                        <i class="bi bi-check-circle me-2"></i>Create Customer
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Sale Preview Modal --}}
    @if($showSaleModal && $createdSale)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content rounded-0" id="printableInvoice">
                {{-- Screen Only Header (visible on screen, hidden on print) --}}
                <div class="screen-only-header p-4">
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

                        {{-- Right: Invoice Label --}}
                        <div class="text-end" style="flex: 0 0 150px;">
                            <h5 class="mb-0 fw-bold"></h5>
                            <h6 class="mb-0 text-muted">INVOICE</h6>
                        </div>
                    </div>
                    <hr class="my-2" style="border-top: 2px solid #000;">
                </div>

                <div class="modal-body p-4">
                    <div class="sale-preview" id="saleReceiptPrintContent">
                        {{-- ==================== CUSTOMER + INVOICE INFO ==================== --}}
                        <div class="row mb-3">
                            <div class="col-6">
                                <p class="mb-1"><strong>Customer :</strong></p>
                                <p class="mb-0">{{ $createdSale->customer->name }}</p>
                                <p class="mb-0">{{ $createdSale->customer->address }}</p>
                                <p class="mb-0"><strong>Tel:</strong> {{ $createdSale->customer->phone }}</p>
                            </div>
                            <div class="col-6 text-end">
                                <table class="table-borderless ms-auto" style="width: auto; display: inline-table;">
                                    <tr>
                                        <td class="pe-3"><strong>Invoice #</strong></td>
                                        <td>{{ $createdSale->invoice_number }}</td>
                                    </tr>
                                    <tr>
                                        <td class="pe-3"><strong>Sale ID</strong></td>
                                        <td>{{ $createdSale->sale_id }}</td>
                                    </tr>
                                    <tr>
                                        <td class="pe-3"><strong>Date</strong></td>
                                        <td>{{ $createdSale->created_at->format('M d, Y') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="pe-3"><strong>Time</strong></td>
                                        <td>{{ $createdSale->created_at->format('H:i A') }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        {{-- ==================== ITEMS TABLE ==================== --}}
                        <div class="table-responsive mb-3">
                            <table class="table table-bordered invoice-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Product</th>
                                        <th class="text-center">Code</th>
                                        <th class="text-center">Qty</th>
                                        <th class="text-end">Unit Price</th>
                                        <th class="text-end">Discount</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($createdSale->items as $index => $item)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $item->product_name }}</td>
                                        <td class="text-center">{{ $item->product_code }}</td>
                                        <td class="text-center">{{ $item->quantity }}</td>
                                        <td class="text-end">Rs.{{ number_format($item->unit_price, 2) }}</td>
                                        <td class="text-end">Rs.{{ number_format($item->discount_per_unit * $item->quantity, 2) }}</td>
                                        <td class="text-end">Rs.{{ number_format($item->total, 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    @php
                                    $itemDiscountTotal = $createdSale->items->sum(function($item) {
                                    return ($item->discount_per_unit ?? 0) * $item->quantity;
                                    });
                                    @endphp
                                    <tr class="totals-row">
                                        <td colspan="5" class="text-end"><strong>Subtotal</strong></td>
                                        <td colspan="2" class="text-end"><strong>Rs.{{ number_format($createdSale->subtotal, 2) }}</strong></td>
                                    </tr>
                                    @php
                                    $totalDiscount = ($createdSale->discount_amount + $itemDiscountTotal);
                                    @endphp
                                    @if($totalDiscount > 0)
                                    <tr class="totals-row">
                                        <td colspan="5" class="text-end"><strong>Total Discount</strong></td>
                                        <td colspan="2" class="text-end"><strong class="text-danger">- Rs.{{ number_format($totalDiscount, 2) }}</strong></td>
                                    </tr>
                                    @endif
                                    <tr class="totals-row grand-total">
                                        <td colspan="5" class="text-end"><strong>Grand Total</strong></td>
                                        <td colspan="2" class="text-end"><strong>Rs.{{ number_format($createdSale->total_amount, 2) }}</strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        {{-- ==================== FOOTER ==================== --}}
                        <div class="invoice-footer mt-4 border-top pt-4">
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
                                <p class="text-center mt-2" style="font-size: 11px;"><strong></strong></p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Footer Buttons --}}
                <div class="modal-footer justify-content-center bg-light">
                    <button type="button" class="btn btn-outline-secondary me-2" wire:click="createNewSale">
                        <i class="bi bi-x-circle me-2"></i>Close
                    </button>
                    <button type="button" class="btn btn-outline-primary me-2" onclick="openPrintWindow({{ $createdSale->id }})">
                        <i class="bi bi-printer me-2"></i>Print
                    </button>
                    <button type="button" class="btn btn-success" wire:click="downloadInvoice">
                        <i class="bi bi-download me-2"></i>Download Invoice
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@push('styles')
<style>
    .container-fluid,
    .card,
    .modal-content {
        font-size: 13px !important;
    }

    .table th,
    .table td {
        font-size: 12px !important;
        padding: 0.35rem 0.5rem !important;
    }

    .modal-header {
        padding-top: 0.5rem !important;
        padding-bottom: 0.5rem !important;
        margin-bottom: 0.25rem !important;
    }

    .modal-footer,
    .card-header,
    .card-body,
    .row,
    .col-md-6,
    .col-md-4,
    .col-md-2,
    .col-md-12 {
        padding-top: 0.5rem !important;
        padding-bottom: 0.5rem !important;
        margin-top: 0.25rem !important;
        margin-bottom: 0.25rem !important;
    }

    .form-control,
    .form-select {
        font-size: 12px !important;
        padding: 0.35rem 0.5rem !important;
    }

    .btn,
    .btn-sm,
    .btn-primary,
    .btn-secondary,
    .btn-outline-danger,
    .btn-outline-secondary {
        font-size: 12px !important;
        padding: 0.25rem 0.5rem !important;
    }

    .badge {
        font-size: 11px !important;
        padding: 0.25em 0.5em !important;
    }

    .list-group-item,
    .dropdown-item {
        font-size: 12px !important;
        padding: 0.35rem 0.5rem !important;
    }

    .summary-card,
    .card {
        border-radius: 8px !important;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06) !important;
    }

    .icon-container {
        width: 36px !important;
        height: 36px !important;
        font-size: 1.1rem !important;
    }

    .search-results {
        max-height: 300px;
        overflow-y: auto;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
    }

    .search-results .p-3 {
        transition: background-color 0.2s ease;
    }

    .search-results .p-3:hover {
        background-color: #f8f9fa;
    }

    .table th {
        font-size: 0.875rem;
        font-weight: 600;
        background-color: #f8f9fa;
    }

    .form-control-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }

    .card {
        border: 1px solid #dee2e6;
        border-radius: 0.5rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }

    .card-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
        padding: 1rem 1.25rem;
    }

    .input-group-sm>.btn {
        padding: 0.25rem 0.5rem;
    }

    /* Modal Styles */
    .modal.show {
        backdrop-filter: blur(2px);
    }

    /* Sale Preview Styles */
    .sale-preview {
        background: white;
        font-family: 'Segoe UI', Arial, sans-serif;
    }

    .sale-preview .header {
        border-bottom: 2px solid #057642ff;
        padding-bottom: 1rem;
    }

    .sale-preview table th {
        
        border: none;
    }

    .sale-preview table td {
        border: 1px solid #dee2e6;
    }

    /* Discount input styling */
    .text-danger.form-control:focus {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .table-responsive {
            font-size: 0.875rem;
        }

        .input-group-sm {
            flex-wrap: nowrap;
        }

        .card-header {
            padding: 0.75rem 1rem;
        }

        .modal-dialog {
            margin: 0.5rem;
        }
    }

    /* Stock warning */
    .text-info small {
        font-size: 0.75rem;
    }

    /* Hide print-only elements on screen */
    @media screen {
        .print-only-header {
            display: none !important;
        }
    }

    /* ============================================
       PROFESSIONAL PRINT STYLES FOR SALE RECEIPT
       ============================================ */
    @media print {

        /* Hide everything except receipt */
        body * {
            visibility: hidden !important;
        }

        #saleReceiptPrintContent,
        #saleReceiptPrintContent * {
            visibility: visible !important;
        }

        #saleReceiptPrintContent {
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            width: 210mm !important;
            padding: 10mm 15mm !important;
            background: white !important;
            color: #000 !important;
        }

        /* Hide screen elements */
        .screen-only-header,
        .modal-header,
        .modal-footer,
        .btn,
        .btn-close,
        .badge,
        .card {
            display: none !important;
            visibility: hidden !important;
        }

        /* Show print header */
        .print-only-header {
            display: block !important;
            visibility: visible !important;
        }

        /* Invoice info layout */
        .invoice-info-row {
            display: flex !important;
            margin-bottom: 15px !important;
            font-size: 11px !important;
        }

        .invoice-info-row .col-6 {
            flex: 0 0 50% !important;
            max-width: 50% !important;
        }

        .invoice-info-row p {
            margin: 2px 0 !important;
            line-height: 1.4 !important;
        }

        .invoice-info-row table {
            font-size: 11px !important;
        }

        .invoice-info-row td {
            padding: 2px 0 !important;
        }

        /* Table styles */
        .invoice-table {
            width: 100% !important;
            border-collapse: collapse !important;
            margin: 10px 0 !important;
            font-size: 10px !important;
        }

        .invoice-table th {
            background-color: #f0f0f0 !important;
            border: 1px solid #000 !important;
            padding: 6px 8px !important;
            font-weight: bold !important;
            text-transform: uppercase !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .invoice-table td {
            border: 1px solid #000 !important;
            padding: 5px 8px !important;
        }

        .invoice-table tbody tr {
            page-break-inside: avoid !important;
        }

        .invoice-table tfoot .totals-row td {
            border-top: 1px solid #000 !important;
            padding: 5px 8px !important;
        }

        .invoice-table tfoot .grand-total td {
            border-top: 2px solid #000 !important;
            font-size: 11px !important;
            padding: 7px 8px !important;
        }

        /* Footer */
        .invoice-footer {
            margin-top: 20px !important;
            border-top: 1px solid #000 !important;
            padding-top: 10px !important;
            font-size: 10px !important;
        }

        .invoice-footer p {
            margin: 2px 0 !important;
        }

        /* Text alignment */
        .text-end {
            text-align: right !important;
        }

        .text-center {
            text-align: center !important;
        }

        /* Page setup */
        @page {
            size: A4 portrait !important;
            margin: 0 !important;
        }

        body {
            margin: 0 !important;
            padding: 0 !important;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    // Print function for sale system
    function openPrintWindow(saleId) {
        const printUrl = '/admin/print/sale/' + saleId;
        const printWindow = window.open(printUrl, '_blank', 'width=800,height=600');
        if (printWindow) {
            printWindow.focus();
        }
    }

    // Auto-close alerts after 5 seconds
    document.addEventListener('livewire:initialized', () => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000);
        });
    });

    // Prevent form submission on enter key in search
    document.addEventListener('keydown', function(e) {
        if (e.target.type === 'text' && e.target.getAttribute('wire:model') === 'search') {
            if (e.key === 'Enter') {
                e.preventDefault();
            }
        }

        window.addEventListener('refreshPage', () => {
            window.location.reload();
        });
    });
</script>
@endpush