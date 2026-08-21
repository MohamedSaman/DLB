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
                            <option value="">-- Select Customer --</option>
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
                </div>
            </div>
        </div>

        {{-- Add Products Card --}}
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm border-1">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fw-semibold">
                        <i class="bi bi-search me-2 text-success"></i> Add Allocated Products
                    </h5>
                </div>
                <div class="card-body" style="position: relative;">
                    <div class="mb-3" style="position: relative; z-index: 10;">
                        <input type="text" class="form-control shadow-sm"
                            wire:model.live="search"
                            placeholder="Search allocated products by name, code, or model...">
                    </div>

                    {{-- Search Results Dropdown --}}
                    @if($search && count($products) > 0)
                        <div class="search-results-dropdown border rounded shadow-lg" style="position: absolute; top: 55px; left: 15px; right: 15px; max-height: 400px; overflow-y: auto; background: white; z-index: 1050; min-width: 300px;">
                            @foreach($products as $product)
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
                                    <button class="btn btn-sm btn-success ms-2"
                                        wire:click="addToCart({{ json_encode($product) }})"
                                        {{ $product['stock'] <= 0 ? 'disabled' : '' }}>
                                        <i class="bi bi-plus-circle"></i> Add
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @elseif($search && count($products) == 0)
                        <div class="alert alert-info mb-3" style="position: absolute; top: 55px; left: 15px; right: 15px; z-index: 1050;">
                            <i class="bi bi-info-circle me-2"></i> No allocated products found
                        </div>
                    @endif

                    @if(!$search)
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-search display-5 mb-3"></i>
                            <p>Start typing to search for allocated products...</p>
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
                    <table class="table table-hover mb-0 table-sm">
                        <thead class="table-light">
                            <tr>
                                <th width="25" style="font-size: 0.75rem;">#</th>
                                <th style="font-size: 0.75rem; min-width: 100px;">Product</th>
                                <th width="70" class="text-center" style="font-size: 0.75rem;">Price</th>
                                <th width="85" class="text-center" style="font-size: 0.75rem;">Qty</th>
                                <th width="70" class="text-end" style="font-size: 0.75rem;">Total</th>
                                <th width="50" class="text-center" style="font-size: 0.75rem;">Del</th>
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
                                <td colspan="4" class="text-end fw-bold">Subtotal:</td>
                                <td class="fw-bold">Rs.{{ number_format($subtotal, 2) }}</td>
                                <td></td>
                            </tr>

                            {{-- Discount Section --}}
                            <tr>
                                <td colspan="3" class="text-end fw-bold align-middle">
                                    Discount:
                                    @if($discount > 0)
                                    <button type="button" class="text-danger p-0 ms-1 border-0 bg-opacity-0"
                                        wire:click="removeDiscount" title="Remove discount">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                    @endif
                                </td>
                                <td colspan="2">
                                    <div class="input-group input-group-sm">
                                        <input type="number"
                                            class="form-control form-control-sm text-danger"
                                            wire:model.live="discount"
                                            min="0"
                                            step="0.01"
                                            placeholder="0.00">

                                        <span class="input-group-text">
                                            {{ $discountType === 'percentage' ? '%' : 'Rs.' }}
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
                                    @if($discount > 0)
                                    - Rs.{{ number_format($discountAmount, 2) }}
                                    @if($discountType === 'percentage')
                                    <div class="text-muted small">({{ $discount }}%)</div>
                                    @endif
                                    @else
                                    <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td></td>
                            </tr>

                            {{-- Grand Total --}}
                            <tr>
                                <td colspan="4" class="text-end fw-bold fs-5">Grand Total:</td>
                                <td class="fw-bold fs-5 text-primary">Rs.{{ number_format($grandTotal, 2) }}</td>
                                <td></td>
                            </tr>
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

    {{-- Payment Section --}}
    <div class="row">
        {{-- Notes --}}
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-chat-text me-2"></i>Notes & Payment Details
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Notes (Optional)</label>
                        <textarea class="form-control" wire:model="notes" rows="3"
                            placeholder="Add any notes for this sale..."></textarea>
                    </div>
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
                    @if($dueAmount > 0)
                    <div class="mb-3">
                        <div class="text-muted small">Due Amount</div>
                        <div class="fw-bold fs-6 text-danger">Rs.{{ number_format($dueAmount, 2) }}</div>
                    </div>
                    @endif
                    <button class="btn btn-success btn-lg px-5" wire:click="validateAndCreateSale"
                        {{ count($cart) == 0 ? 'disabled' : '' }}>
                        <i class="bi bi-cart-check me-2"></i>Complete Sale
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ADD CUSTOMER MODAL --}}
    @if($showCustomerModal)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-person-plus me-2"></i>Add New Customer
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeCustomerModal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Name *</label>
                        <input type="text" class="form-control" wire:model="customerName" placeholder="Enter customer name">
                        @error('customerName') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Phone *</label>
                        <input type="text" class="form-control" wire:model="customerPhone" placeholder="Enter phone number">
                        @error('customerPhone') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" class="form-control" wire:model="customerEmail" placeholder="Enter email">
                        @error('customerEmail') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Address *</label>
                        <textarea class="form-control" wire:model="customerAddress" rows="2" placeholder="Enter address"></textarea>
                        @error('customerAddress') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Customer Type *</label>
                        <select class="form-select" wire:model="customerType">
                            <option value="retail">Retail</option>
                            <option value="wholesale">Wholesale</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeCustomerModal">Cancel</button>
                    <button type="button" class="btn btn-primary" wire:click="createCustomer">
                        <i class="bi bi-check-circle me-1"></i>Create Customer
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- SALE COMPLETE MODAL --}}
    @if($showSaleModal && $createdSale)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-body p-0">
                    <div class="sale-preview p-4" id="saleReceiptPrintContent">

                        {{-- Screen Only Header --}}
                        <div class="screen-only-header mb-4">
                            <div class="text-end">
                                <button type="button" class="btn-close" wire:click="createNewSale"></button>
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

                                {{-- Right:  & Invoice --}}
                                <div class="text-end" style="flex: 0 0 150px;">
                                    <h5 class="mb-0 fw-bold"></h5>
                                    <h6 class="mb-0 text-muted">INVOICE</h6>
                                </div>
                            </div>
                            <hr class="my-2" style="border-top: 2px solid #000;">
                        </div>

                        {{-- Customer & Sale Details Side by Side --}}
                        <div class="row mb-3 invoice-info-row">
                            <div class="col-12 col-md-6 mb-3 mb-md-0">
                                <p class="mb-1"><strong style="font-size: 0.85rem;">Customer :</strong></p>
                                <p class="mb-0" style="font-size: 0.8rem;">{{ $createdSale->customer->name }}</p>
                                <p class="mb-0" style="font-size: 0.8rem;">{{ $createdSale->customer->address }}</p>
                                <p class="mb-0" style="font-size: 0.8rem;"><strong>Tel:</strong> {{ $createdSale->customer->phone }}</p>
                            </div>
                            <div class="col-12 col-md-6">
                                <table class="table-borderless ms-auto" style="width: 100%; display: table; font-size: 0.8rem;">
                                    <tr>
                                        <td style="padding: 0.25rem 0.5rem 0.25rem 0;"><strong>Invoice #:</strong></td>
                                        <td style="padding: 0.25rem 0;">{{ $createdSale->invoice_number }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 0.25rem 0.5rem 0.25rem 0;"><strong>Date:</strong></td>
                                        <td style="padding: 0.25rem 0;">{{ $createdSale->created_at->format('d/m/Y') }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 0.25rem 0.5rem 0.25rem 0;"><strong>Time:</strong></td>
                                        <td style="padding: 0.25rem 0;">{{ $createdSale->created_at->format('H:i') }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        {{-- Items Table --}}
                        <div class="table-responsive mb-3">
                            <table class="table table-bordered invoice-table" style="font-size: 0.8rem;">
                                <thead>
                                    <tr>
                                        <th width="30" class="text-center" style="padding: 0.4rem 0.25rem; font-size: 0.7rem;">#</th>
                                        <th style="padding: 0.4rem 0.25rem; font-size: 0.7rem; min-width: 80px;">DESC</th>
                                        <th width="40" class="text-center" style="padding: 0.4rem 0.25rem; font-size: 0.7rem;">QTY</th>
                                        <th width="70" class="text-end" style="padding: 0.4rem 0.25rem; font-size: 0.7rem;">PRICE</th>
                                        <th width="70" class="text-end" style="padding: 0.4rem 0.25rem; font-size: 0.7rem;">TOTAL</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($createdSale->items as $index => $item)
                                    <tr>
                                        <td class="text-center" style="padding: 0.3rem 0.15rem;">{{ $index + 1 }}</td>
                                        <td style="padding: 0.3rem 0.15rem; font-size: 0.75rem;">{{ $item->product_name }}</td>
                                        <td class="text-center" style="padding: 0.3rem 0.15rem;">{{ $item->quantity }}</td>
                                        <td class="text-end" style="padding: 0.3rem 0.15rem; font-size: 0.75rem;">Rs.{{ number_format($item->unit_price, 2) }}</td>
                                        <td class="text-end" style="padding: 0.3rem 0.15rem; font-size: 0.75rem;">Rs.{{ number_format($item->total, 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="totals-row">
                                        <td colspan="4" class="text-end"><strong>Subtotal</strong></td>
                                        <td class="text-end"><strong>Rs.{{ number_format($createdSale->subtotal, 2) }}</strong></td>
                                    </tr>
                                    @if($createdSale->discount_amount > 0)
                                    <tr class="totals-row">
                                        <td colspan="4" class="text-end"><strong>Discount</strong></td>
                                        <td class="text-end"><strong>-Rs.{{ number_format($createdSale->discount_amount, 2) }}</strong></td>
                                    </tr>
                                    @endif
                                    <tr class="totals-row grand-total" style="font-size: 0.85rem;">
                                        <td colspan="4" class="text-end"><strong>Grand Total</strong></td>
                                        <td class="text-end"><strong>Rs.{{ number_format($createdSale->total_amount, 2) }}</strong></td>
                                    </tr>
                                    <tr class="totals-row" style="font-size: 0.85rem;">
                                        <td colspan="4" class="text-end"><strong>Paid Amount</strong></td>
                                        <td class="text-end"><strong>Rs.{{ number_format($createdSale->total_amount - $createdSale->due_amount, 2) }}</strong></td>
                                    </tr>
                                    <tr class="totals-row" style="font-size: 0.85rem;">
                                        <td colspan="4" class="text-end"><strong>Due Amount</strong></td>
                                        <td class="text-end text-danger"><strong>Rs.{{ number_format($createdSale->due_amount, 2) }}</strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        {{-- Footer Note --}}
                        <div class="invoice-footer mt-4">
                            <div class="row text-center mb-3 signature-section" style="font-size: 0.75rem;">
                                <div class="col-12 col-md-4 mb-3 mb-md-0" style="padding: 0 0.25rem;">
                                    <p class="mb-1"><strong style="font-size: 0.7rem;">.............................</strong></p>
                                    <p class="mb-0"><strong style="font-size: 0.75rem;">Checked By</strong></p>
                                </div>
                                <div class="col-12 col-md-4 mb-3 mb-md-0" style="padding: 0 0.25rem;">
                                    <p class="mb-1"><strong style="font-size: 0.7rem;">.............................</strong></p>
                                    <p class="mb-0"><strong style="font-size: 0.75rem;">Authorized Officer</strong></p>
                                </div>
                                <div class="col-12 col-md-4" style="padding: 0 0.25rem;">
                                    <p class="mb-1"><strong style="font-size: 0.7rem;">.............................</strong></p>
                                    <p class="mb-0"><strong style="font-size: 0.75rem;">Customer Stamp</strong></p>
                                </div>
                            </div>
                            <div class="border-top pt-3" style="font-size: 0.7rem; line-height: 1.2;">
                                <p class="text-center mb-1" style="margin-bottom: 0.3rem;"><strong>ADDRESS :</strong> Sample address</p>
                                <p class="text-center mb-1" style="margin-bottom: 0.3rem;"><strong>TEL :</strong> (077) 1234567, <strong>EMAIL :</strong> Sample email</p>
                                <p class="text-center mt-2" style="font-size: 0.65rem; margin-bottom: 0;"><strong></strong></p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Footer Buttons --}}
                <div class="modal-footer justify-content-center flex-wrap gap-2 gap-sm-3" style="padding: 1rem 0.75rem;">
                    <button type="button" class="btn btn-outline-primary flex-grow-1" style="font-size: 0.85rem; min-width: 120px;" onclick="window.open('/admin/print/sale/{{ $createdSale->id }}', '_blank')">
                        <i class="bi bi-printer me-2"></i><span class="d-none d-sm-inline">Print</span><span class="d-sm-none">Print</span>
                    </button>
                    <a href="/admin/print/sale/{{ $createdSale->id }}/download" class="btn btn-success flex-grow-1" style="font-size: 0.85rem; min-width: 120px;">
                        <i class="bi bi-download me-2"></i><span class="d-none d-sm-inline">Download Invoice</span><span class="d-sm-none">Download</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@push('styles')
<style>
    .search-results-dropdown {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        max-height: 400px;
        overflow-y: auto;
        width: 100%;
        left: 0;
        right: 0;
    }

    .search-result-item {
        padding: 12px 16px;
        border-bottom: 1px solid #f3f4f6;
        transition: background-color 0.2s;
    }

    .search-result-item:hover {
        background-color: #f9fafb;
    }

    .search-result-item:last-child {
        border-bottom: none;
    }

    .search-results-dropdown::-webkit-scrollbar {
        width: 8px;
    }

    .search-results-dropdown::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }

    .search-results-dropdown::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 10px;
    }

    .search-results-dropdown::-webkit-scrollbar-thumb:hover {
        background: #555;
    }

    /* Invoice/Receipt Styles */
    .sale-preview {
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

        #saleReceiptPrintContent,
        #saleReceiptPrintContent * {
            visibility: visible;
        }

        #saleReceiptPrintContent {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('showToast', (event) => {
            const type = event.type || 'info';
            const message = event.message || 'Action completed';
            
            if (type === 'success') {
                Swal.fire({
                    title: 'Success!',
                    text: message,
                    icon: 'success',
                    confirmButtonText: 'OK'
                });
            } else if (type === 'error') {
                Swal.fire({
                    title: 'Error!',
                    text: message,
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            } else if (type === 'warning') {
                Swal.fire({
                    title: 'Warning!',
                    text: message,
                    icon: 'warning',
                    confirmButtonText: 'OK'
                });
            } else {
                Swal.fire({
                    title: 'Info',
                    text: message,
                    icon: 'info',
                    confirmButtonText: 'OK'
                });
            }
        });
    });
</script>
@endpush
