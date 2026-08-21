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
                <div>
                    <a href="{{ route('admin.quotation-list') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i> Back to List
                    </a>
                </div>
            </div>
        </div>
    </div>

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
                        {{-- Select Customer --}}
                        <div class="col-md-4">
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

                        {{-- Price Type Selection --}}
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Price Type *</label>
                            <select class="form-select shadow-sm" wire:model.live="priceType">
                                <option value="retail">Retail Price</option>
                                <option value="wholesale">Wholesale Price</option>
                                <option value="distribute">Distribute Price</option>
                            </select>
                            <div class="form-text mt-2">
                                <i class="bi bi-tags me-1"></i> Select pricing for quotation
                            </div>
                        </div>

                        {{-- Valid Until --}}
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Valid Until *</label>
                            <input type="date" class="form-control shadow-sm" wire:model="validUntil">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Add Products --}}
        <div class="col-md-6 mb-4">
            <div class="card h-100 shadow-sm border-1">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fw-semibold">
                        <i class="bi bi-search me-2 text-success"></i> Add Products
                    </h5>
                </div>

                <div class="card-body"
                     x-data="{ 
                         highlightIndex: -1,
                         resultCount: 0
                     }"
                     x-init="
                         $watch('resultCount', value => {
                             if (value === 0) highlightIndex = -1;
                         });
                         
                         Livewire.on('product-added-to-cart', () => {
                             $nextTick(() => {
                                 const qtyInput = document.querySelector('tbody tr:first-child .qty-input-0');
                                 if (qtyInput) {
                                     qtyInput.focus();
                                     qtyInput.select();
                                 }
                             });
                         });
                         
                         // Focus search on page load
                         $nextTick(() => {
                             if ($refs.searchInput) {
                                 $refs.searchInput.focus();
                             }
                         });
                     "
                     @keydown.escape.window="highlightIndex = -1; if ($refs.searchInput) $refs.searchInput.focus()">
                    
                    {{-- Search Field --}}
                    <div class="mb-3">
                        <input type="text" 
                            class="form-control shadow-sm quotation-search-input"
                            wire:model.live="search"
                            id="quotationSearchInput"
                            x-ref="searchInput"
                            placeholder="Search by product name, code, model, or variant..."
                            @keydown.down.prevent="
                                resultCount = document.querySelectorAll('.search-result-item').length;
                                if (resultCount > 0) {
                                    highlightIndex = highlightIndex < resultCount - 1 ? highlightIndex + 1 : highlightIndex;
                                    $nextTick(() => {
                                        const active = document.querySelector('.search-result-item.active');
                                        if (active) active.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                                    });
                                }
                            "
                            @keydown.up.prevent="
                                resultCount = document.querySelectorAll('.search-result-item').length;
                                if (highlightIndex > 0) {
                                    highlightIndex--;
                                    $nextTick(() => {
                                        const active = document.querySelector('.search-result-item.active');
                                        if (active) active.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                                    });
                                } else {
                                    highlightIndex = -1;
                                }
                            "
                            @keydown.enter.prevent="
                                if (highlightIndex >= 0) {
                                    const highlighted = document.querySelector('.search-result-item.active button');
                                    if (highlighted) {
                                        highlighted.click();
                                        highlightIndex = -1;
                                    }
                                }
                            "
                            @focus="
                                resultCount = document.querySelectorAll('.search-result-item').length;
                            ">
                    </div>

                    {{-- Search Results --}}
                    @if($search && count($searchResults) > 0)
                        <div class="search-results border rounded bg-white shadow-sm" 
                             style="max-height: 300px; overflow-y: auto;"
                             x-init="resultCount = {{ count($searchResults) }}">
                            @foreach($searchResults as $idx => $product)
                                <div class="p-3 border-bottom d-flex justify-content-between align-items-center search-result-item"
                                    wire:key="product-{{ $product['id'] }}"
                                    :class="highlightIndex === {{ $idx }} ? 'active' : ''"
                                    @mouseenter="highlightIndex = {{ $idx }}"
                                    @click="highlightIndex = {{ $idx }}">
                                    <div>
                                        <h6 class="mb-1 fw-semibold">{{ $product['name'] }}</h6>
                                        <p class="text-muted small mb-0">
                                            Code: {{ $product['code'] }} | Model: {{ $product['model'] ?? 'N/A' }}
                                        </p>
                                        <p class="text-success small mb-0">
                                            Rs.{{ number_format($product['price'], 2) }} | Stock: {{ $product['stock'] }}
                                        </p>
                                    </div>
                                    <button class="btn btn-sm btn-outline-primary"
                                        wire:click="addToCart({{ json_encode($product) }})">
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


        {{-- Quotation Items Table --}}
        <div class="col-md-12 mb-4 overflow-auto">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-cart me-2"></i>Quotation Items
                    </h5>
                    <span class="badge bg-primary">{{ count($cart) }} items</span>
                </div>
                <div class="card-body p-0">
                    @if(count($cart) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table">
                                <tr>
                                    <th width="30">#</th>
                                    <th>Product</th>
                                    <th width="150">Quantity</th>
                                    <th width="120">Unit Price</th>
                                    <th width="120">Discount/Unit</th>
                                    <th width="120">Total</th>
                                    <th width="100" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cart as $index => $item)
                                <tr wire:key="cart-{{ $item['id'] }}">
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <div>
                                            <strong>{{ $item['name'] }}</strong>
                                            <div class="text-muted small">
                                                {{ $item['code'] }} | {{ $item['model'] }}
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <button class="btn btn-outline-secondary" type="button"
                                                wire:click="decrementQuantity({{ $index }})">-</button>
                                            <input type="number" 
                                                class="form-control text-center qty-input-{{ $index }}"
                                                wire:change="updateQuantity({{ $index }}, $event.target.value)"
                                                value="{{ $item['quantity'] }}" 
                                                min="1"
                                                @keydown.enter.prevent="
                                                    const priceInput = document.querySelector('.price-input-{{ $index }}');
                                                    if (priceInput) priceInput.focus();
                                                ">
                                            <button class="btn btn-outline-secondary" type="button"
                                                wire:click="incrementQuantity({{ $index }})">+</button>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="number" 
                                            class="form-control form-control-sm text-primary price-input-{{ $index }}" 
                                            min="0" 
                                            step="0.01"
                                            wire:change="updateUnitPrice({{ $index }}, $event.target.value)"
                                            value="{{ $item['price'] }}"
                                            @keydown.enter.prevent="
                                                const searchInput = document.getElementById('quotationSearchInput');
                                                if (searchInput) {
                                                    searchInput.focus();
                                                    searchInput.select();
                                                }
                                            ">
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
                            <tfoot class="table">
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
                                        <button type="button" class=" btn-link text-danger me-2 p-0 border-0"
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
                                                @if($additionalDiscountType==='percentage') max="100" @endif
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

                                {{-- Grand Total --}}
                                <tr>
                                    <td colspan="5" class="text-end fw-bold fs-5">Grand Total:</td>
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

        {{-- Notes and Terms & Conditions --}}  
        <div class="col-md-12 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-list-check me-2"></i>Terms & Conditions
                    </h5>
                </div>
                <div class="card-body">
                    <textarea class="form-control" wire:model="termsConditions" rows="6"
                        placeholder="Enter terms and conditions for the quotation..."></textarea>
                </div>
            </div>
        </div>
    
        

        {{-- Create Quotation Button --}}
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center">
                    <button class="btn btn-primary btn-lg px-5" wire:click="createQuotation"
                        {{ count($cart) == 0 ? 'disabled' : '' }}>
                        <i class="bi bi-file-earmark-plus me-2"></i>Create Quotation
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

   {{-- Quotation Preview Modal --}}
    @if($showQuotationModal && $createdQuotation)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header border-0 py-4 px-4" 
                     style="background: white; display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #ddd;">
                    <div style="flex: 0 0 auto;">
                        <img src="{{ asset('images/HARDMEN.png') }}" alt="Logo" class="img-fluid" style="max-height: 55px;">
                    </div>
                    <div style="flex: 1; text-align: center;">
                        <h3 class="mb-0 fw-bold" style="color: #333; font-size: 1.4rem; letter-spacing: 0.5px;">DLB ENTERPRISES</h3>
                        <p class="text-muted small mb-0" style="font-size: 0.85rem; margin-top: 2px;">TOOLS WITH POWER</p>
                    </div>
                    <div style="flex: 0 0 auto; text-align: right;">
                        <h3 class="mb-0 fw-normal" style="color: #666; font-size: 1.2rem; letter-spacing: 1px;">QUOTATION</h3>
                    </div>
                </div>
                <div class="modal-body p-0">
                    {{-- Quotation Preview --}}
                    <div class="quotation-preview p-0">
                        
                        

                        {{-- Customer and Quotation Details --}}
                        <div class="p-4">
                            <div class="row mb-4">
                                {{-- Customer Information --}}
                                <div class="col-md-6">
                                    <h6 class="fw-bold text-dark mb-2">BILL TO:</h6>
                                    <p class="mb-1"><strong>{{ $createdQuotation->customer_name }}</strong></p>
                                    <p class="mb-1 text-muted">{{ $createdQuotation->customer_address ?? 'N/A' }}</p>
                                    <p class="mb-1 text-muted">Tel: {{ $createdQuotation->customer_phone }}</p>
                                    @if($createdQuotation->customer_email)
                                    <p class="mb-0 text-muted">Email: {{ $createdQuotation->customer_email }}</p>
                                    @endif
                                </div>

                                {{-- Quotation Information --}}
                                <div class="col-md-6 text-end">
                                    <table class="table table-sm table-borderless ms-auto" style="width: auto;">
                                        <tr>
                                            <td class="text-muted"><strong>Quotation No:</strong></td>
                                            <td class="fw-bold">{{ $createdQuotation->quotation_number }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted"><strong>Date:</strong></td>
                                            <td>{{ $createdQuotation->quotation_date->format('d/m/Y') }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted"><strong>Valid Until:</strong></td>
                                            <td>{{ \Carbon\Carbon::parse($createdQuotation->valid_until)->format('d/m/Y') }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            {{-- Items Table --}}
                            <div class="table-responsive mb-4" style="min-height: 100px">
                                <table class="table table-bordered table-hover mb-0">
                                    <thead class="table">
                                        <tr>
                                            <th style="width: 5%;" class="text-center">#</th>
                                            <th style="width: 20%;">Item Code</th>
                                            <th>Product Name</th>
                                            <th style="width: 12%;" class="text-center">Qty</th>
                                            <th style="width: 15%;" class="text-end">Unit Price</th>
                                            <th style="width: 15%;" class="text-end">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($createdQuotation->items as $index => $item)
                                        <tr>
                                            <td class="text-center">{{ $index + 1 }}</td>
                                            <td>{{ $item['product_code'] ?? 'N/A' }}</td>
                                            <td>
                                                <strong>{{ $item['product_name'] ?? $item['name'] ?? 'N/A' }}</strong>
                                            </td>
                                            <td class="text-center">{{ $item['quantity'] }}</td>
                                            <td class="text-end">Rs.{{ number_format($item['unit_price'], 2) }}</td>
                                            <td class="text-end">Rs.{{ number_format($item['total'], 2) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            {{-- Totals Section --}}
                            <div class="row mb-4">
                                <div class="col-md-7"></div>
                                <div class="col-md-5">
                                    <table class="table table-sm table-borderless">
                                        <tr>
                                            <td class="text-end"><strong>Subtotal:</strong></td>
                                            <td class="text-end fw-bold">Rs.{{ number_format($createdQuotation->subtotal, 2) }}</td>
                                        </tr>
                                        @php
                                            $totalDiscount = $createdQuotation->discount_amount + $createdQuotation->additional_discount;
                                        @endphp
                                        @if($totalDiscount > 0)
                                        <tr class="text-danger">
                                            <td class="text-end"><strong>Discount:</strong></td>
                                            <td class="text-end fw-bold">- Rs.{{ number_format($totalDiscount, 2) }}</td>
                                        </tr>
                                        @endif
                                        <tr class="border-top border-2" style="border-color: #f58320 !important;">
                                            <td class="text-end"><strong>Grand Total:</strong></td>
                                            <td class="text-end fw-bold" style="color: #f58320; font-size: 1.1rem;">Rs.{{ number_format($createdQuotation->total_amount, 2) }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            {{-- Terms & Conditions --}}
                            @if($createdQuotation->terms_conditions)
                            <div class="mt-3 p-3 bg-light rounded border">
                                <h6 class="fw-bold mb-2">Terms & Conditions:</h6>
                                <p class="small mb-0 text-muted">{!! nl2br(e($createdQuotation->terms_conditions)) !!}</p>
                            </div>
                            @endif

                            {{-- Footer Note --}}
                            <div class="text-center small text-muted mt-4 pt-3 border-top">
                                <p class="text-center mb-0"><strong>ADDRESS :</strong> Sample address</p>
                                <p class="text-center mb-0"><strong>TEL :</strong> (077) 1234567, <strong>EMAIL :</strong> Sample email</p>
                                <p class="mb-0 mt-2"><i class="bi bi-info-circle me-1"></i> Thank you for your business!</p>
                            </div>

                        </div>

                    </div>
                </div>

                {{-- Footer Buttons --}}
                <div class="modal-footer bg-light border-top">
                    <button type="button" class="btn btn-outline-secondary me-2" wire:click="createNewQuotation">
                        <i class="bi bi-plus-circle me-2"></i>Create New Quotation
                    </button>
                    @if($createdQuotation)
                    <a href="{{ route('admin.quotation.print', $createdQuotation->id) }}" target="_blank" class="btn btn-success me-2">
                        <i class="bi bi-printer me-2"></i>Print Quotation
                    </a>
                    @endif
                    <a href="{{ route('admin.quotation-list') }}" class="btn btn-primary">
                        <i class="bi bi-list-check me-2"></i>Go to Quotation List
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>

@push('styles')
<style>
    .search-results {
        max-height: 300px;
        overflow-y: auto;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
    }

    .search-results .p-3 {
        transition: background-color 0.2s ease;
        cursor: pointer;
    }

    .search-results .p-3:hover {
        background-color: #f8f9fa;
    }

    .search-result-item.active {
        background-color: #e7f3ff !important;
        border-left: 3px solid #0d6efd;
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

    /* Quotation Preview Styles */
    .quotation-preview {
        background: white;
        font-family: 'Segoe UI', Arial, sans-serif;
    }

    .quotation-preview .header {
        border-bottom: 2px solid #057642ff;
        padding-bottom: 1rem;
    }

    .quotation-preview table th {
        background-color: #038d4fff;
        color: white;
        border: none;
    }

    .quotation-preview table td {
        border: 1px solid #dee2e6;
    }

    /* Discount input styling */
    .text-danger.form-control:focus {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }

    /* Quick discount buttons */
    .btn-outline-secondary:hover {
        background-color: #6c757d;
        color: white;
    }

    /* Animation for cart actions */
    .btn {
        transition: all 0.2s ease-in-out;
    }

    .btn:hover {
        transform: translateY(-1px);
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

    /* Loading states */
    .btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    /* Success message styling */
    .alert-success {
        border-left: 4px solid #198754;
    }

    .alert-danger {
        border-left: 4px solid #dc3545;
    }

    .alert-info {
        border-left: 4px solid #0dcaf0;
    }
</style>
@endpush

@push('scripts')
<script>
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
    });

    // Additional discount input handling
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('additionalDiscountUpdated', (value) => {
            // Additional discount validation can be handled here if needed
        });
    });
</script>
@endpush
