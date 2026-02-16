<div class="container-fluid py-3"
     x-data="billingKeyboard()"
     x-init="initBilling()"
     @product-added-to-cart.window="handleProductAdded($event.detail.cartKey)"
     @keydown.window="handleGlobalKey($event)">
    {{-- Edit Mode Alert --}}
    @if($editMode && $editingSaleId)
    <div class="alert alert-warning alert-dismissible fade show mb-4">
        <i class="bi bi-pencil-square me-2"></i> 
        <strong>Editing Mode:</strong> You are editing sale <strong>#{{ $editingSale?->sale_id }}</strong>
        <button class="btn btn-sm btn-warning ms-2" wire:click="cancelEdit">Cancel Edit</button>
    </div>
    @endif

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
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Customer *</label>
                        <select class="form-select shadow-sm" wire:model.live="customerId">
                            <option value="">-- Select Customer --</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}">
                                    {{ $customer->business_name ?? $customer->name}} - {{ $customer->phone ?? 'N/A' }}
                                </option>
                            @endforeach
                        </select>

                        <div class="form-text mt-2">
                            @if($selectedCustomer)
                                <div class="p-3 bg-light rounded mt-2">
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <small class="text-muted d-block">Phone:</small>
                                            <span class="fw-medium">{{ $selectedCustomer->phone ?? 'N/A' }}</span>
                                        </div>
                                        <div class="col-sm-6">
                                            <small class="text-muted d-block">Type:</small>
                                            <span class="badge bg-info">{{ ucfirst($selectedCustomer->type ?? 'Regular') }}</span>
                                        </div>
                                        <div class="col-12 mt-2">
                                            <small class="text-muted d-block">Address:</small>
                                            <span>{{ $selectedCustomer->address ?? 'No address' }}</span>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <small class="text-muted">Please select a customer to proceed with the sale.</small>
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
                            id="productSearchInput"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Search products by name or code..."
                            autocomplete="off"
                            x-ref="searchInput"
                            @keydown.arrow-down.prevent="moveDown()"
                            @keydown.arrow-up.prevent="moveUp()"
                            @keydown.enter.prevent="selectCurrent()"
                            @keydown.escape.prevent="closeDropdown()">
                    </div>

                    {{-- Search Results --}}
                    @if($search && count($searchResults) > 0)
                    <div class="search-results mt-1 position-absolute w-100 shadow-lg"
                         x-ref="searchResultsContainer"
                         style="max-height: 300px; overflow-y: auto; max-width: 96%; z-index: 1055; left: 0.5rem; right: 0.5rem;">
                        @foreach($searchResults as $idx => $product)
                        <div class="search-result-item p-3 border-bottom d-flex justify-content-between align-items-center"
                            :class="selectedIndex === {{ $idx }} ? 'search-item-active' : 'bg-white'"
                            style="cursor: pointer;"
                            data-search-index="{{ $idx }}"
                            @mouseenter="selectedIndex = {{ $idx }}"
                            @click="isAddingToCart = true"
                            wire:click="addToCart({{ json_encode($product) }})">
                            <div class="flex-grow-1">
                                <div class="fw-medium text-dark">{{ $product['display_name'] ?? $product['name'] }}</div>
                                <small class="text-muted d-block">{{ $product['code'] }}</small>
                                <small class="text-warning d-block">
                                    <i class="bi bi-percent me-1"></i>Price: Rs. {{ number_format($product['price'], 2) }}
                                </small>
                                <small class="text-info">
                                    Available: {{ $product['available'] }}
                                    @if($product['pending'] > 0)
                                        | Pending: {{ $product['pending'] }}
                                    @endif
                                    @if(isset($product['is_variant']) && $product['is_variant'])
                                        <span class="badge bg-secondary ms-2">Variant</span>
                                    @endif
                                </small>
                            </div>
                            <button class="btn btn-sm btn-primary" type="button">
                                <i class="bi bi-plus"></i>
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
                                    <th class="ps-4">Product</th>
                                    <th style="width: 160px;">Quantity</th>
                                    <th style="width: 130px;">Price</th>
                                    <th style="width: 120px;">Discount</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-center" style="width: 80px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cart as $index => $item)
                                <tr wire:key="cart-item-{{ $item['cart_key'] }}">
                                    <td class="ps-4">
                                        <div class="fw-medium">{{ $item['name'] }}</div>
                                        @if(!empty($item['code']))
                                            <small class="text-muted">{{ $item['code'] }}</small>
                                        @endif
                                        <small class="text-info d-block">Available: {{ $item['available'] }}</small>
                                        @if(isset($item['is_variant']) && $item['is_variant'])
                                            <small class="text-primary">
                                                <i class="bi bi-tags me-1"></i>{{ $item['variant_value'] ?? 'Variant' }}
                                            </small>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <button class="btn btn-outline-secondary" type="button"
                                                wire:click="updateQuantity('{{ $item['cart_key'] }}', {{ $item['quantity'] - 1 }})">-</button>
                                            <input type="number" class="form-control text-center cart-qty-input" 
                                                value="{{ $item['quantity'] }}"
                                                wire:change="updateQuantity('{{ $item['cart_key'] }}', $event.target.value)"
                                                wire:key="qty-{{ $item['cart_key'] }}"
                                                min="1" max="{{ $item['available'] }}"
                                                data-cart-key="{{ $item['cart_key'] }}"
                                                data-field="qty"
                                                @keydown.enter.prevent="handleQtyEnter($event, '{{ $item['cart_key'] }}')">
                                            <button class="btn btn-outline-secondary" type="button"
                                                wire:click="updateQuantity('{{ $item['cart_key'] }}', {{ $item['quantity'] + 1 }})">+</button>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm text-primary fw-bold cart-price-input" 
                                            value="{{ $item['price'] }}"
                                            wire:change="updatePrice('{{ $item['cart_key'] }}', $event.target.value)"
                                            wire:key="price-{{ $item['cart_key'] }}"
                                            min="0" step="0.01"
                                            placeholder="0.00"
                                            data-cart-key="{{ $item['cart_key'] }}"
                                            data-field="price"
                                            @keydown.enter.prevent="handlePriceEnter($event)">
                                    </td>
                                    
                                    <td>
                                        <input type="number" class="form-control form-control-sm text-danger" 
                                            value="{{ $item['discount'] }}"
                                            wire:change="updateDiscount('{{ $item['cart_key'] }}', $event.target.value)"
                                            wire:key="disc-{{ $item['cart_key'] }}"
                                            min="0" max="{{ $item['price'] }}" step="0.01"
                                            placeholder="0.00">
                                    </td>
                                    <td class="text-end fw-bold">Rs. {{ number_format($item['total'], 2) }}</td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-danger" 
                                            wire:click="removeFromCart('{{ $item['cart_key'] }}')" title="Remove">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr class="totals-row">
                                    <td colspan="4" class="text-end fw-semibold">Subtotal:</td>
                                    <td class="text-end fw-bold">Rs. {{ number_format($this->subtotal, 2) }}</td>
                                    <td></td>
                                </tr>
                                <tr class="totals-row">
                                    <td colspan="4" class="text-end fw-semibold">Item Discounts:</td>
                                    <td class="text-end fw-bold text-danger">- Rs. {{ number_format($this->totalDiscount, 2) }}</td>
                                    <td></td>
                                </tr>
                                <tr class="totals-row">
                                    <td colspan="3" class="text-end fw-semibold">Additional Discount:</td>
                                    <td style="width: 200px;">
                                        <div class="input-group input-group-sm">
                                            <input type="number" class="form-control text-danger" 
                                                wire:model.live="additionalDiscount"
                                                min="0" step="0.01" placeholder="0.00">
                                            <select class="form-select" wire:model.live="additionalDiscountType" style="max-width: 100px;">
                                                <option value="fixed">Rs.</option>
                                                <option value="percentage">%</option>
                                            </select>
                                        </div>
                                    </td>
                                    <td class="text-end fw-bold text-danger">- Rs. {{ number_format($this->additionalDiscountAmount, 2) }}</td>
                                    <td></td>
                                </tr>
                                <tr class="grand-total">
                                    <td colspan="4" class="text-end fw-bold fs-5">GRAND TOTAL:</td>
                                    <td class="text-end fw-bold fs-5 text-primary">Rs. {{ number_format($this->grandTotal, 2) }}</td>
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
                        <div class="fw-bold fs-5 text-primary">Rs.{{ number_format($this->grandTotal, 2) }}</div>
                    </div>
                    @if($editMode && $editingSaleId)
                        <button class="btn btn-primary btn-lg px-5" wire:click="createSale"
                            {{ count($cart) == 0 || !$customerId ? 'disabled' : '' }}>
                            <i class="bi bi-floppy me-2"></i>Save Changes
                        </button>
                    @else
                        <button class="btn btn-success btn-lg px-5" wire:click="createSale"
                            {{ count($cart) == 0 || !$customerId ? 'disabled' : '' }}>
                            <i class="bi bi-cart-check me-2"></i>Complete Sale Order
                        </button>
                    @endif
                    <div class="mt-2">
                        <small class="text-muted">
                            @if($editMode && $editingSaleId)
                                Changes will be saved to sale #{{ $editingSale?->sale_id }}
                            @else
                                Sale will be sent for admin approval
                            @endif
                        </small>
                    </div>
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
                                <option value="distributor">Distributor</option>
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
                        <div class="col-12">
                            <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="$toggle('showCustomerMoreInfo')">
                                <i class="bi bi-chevron-{{ $showCustomerMoreInfo ? 'up' : 'down' }} me-1"></i>
                                More Information
                            </button>
                        </div>
                        @if($showCustomerMoreInfo)
                        <div class="col-md-6">
                            <label class="form-label">Opening Balance</label>
                            <input type="number" class="form-control" wire:model="customerOpeningBalance" placeholder="0.00" step="0.01" min="0">
                            @error('customerOpeningBalance') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Overpaid Amount</label>
                            <input type="number" class="form-control" wire:model="customerOverpaidAmount" placeholder="0.00" step="0.01" min="0">
                            @error('customerOverpaidAmount') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        @endif
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
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-check-circle me-2"></i>Sale Order Created
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="createNewSale"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                        <h4 class="mt-2 text-success">Sale Order #{{ $createdSale->id ?? 'N/A' }}</h4>
                        <p class="text-muted">Your sale order has been created successfully and is pending admin approval.</p>
                    </div>
                    
                    @if($createdSale)
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="fw-bold">Customer Details</h6>
                            <p class="mb-1"><strong>Name:</strong> {{ $createdSale->customer->name ?? 'N/A' }}</p>
                            <p class="mb-1"><strong>Phone:</strong> {{ $createdSale->customer->phone ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold">Sale Summary</h6>
                            <p class="mb-1"><strong>Items:</strong> {{ $createdSale->items->count() ?? 0 }}</p>
                            <p class="mb-1"><strong>Total:</strong> Rs. {{ number_format($createdSale->total_amount, 2) }}</p>
                        </div>
                    </div>
                    @endif
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-success" wire:click="createNewSale">
                        <i class="bi bi-plus-circle me-2"></i>Create New Sale
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

    .search-results {
        max-height: 300px;
        overflow-y: auto;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        background: white;
    }

    .search-results .p-3 {
        transition: background-color 0.2s ease;
    }

    .search-results .p-3:hover {
        background-color: #f8f9fa;
    }

    .search-item-active {
        background-color: #e8f0fe !important;
        border-left: 3px solid #0d6efd !important;
    }

    .search-result-item {
        transition: background-color 0.15s ease;
    }

    .search-results .bg-primary.bg-opacity-10 {
        background-color: rgba(13, 110, 253, 0.1) !important;
        border-left: 3px solid #0d6efd !important;
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
</style>
@endpush

@push('scripts')
<script>
    function billingKeyboard() {
        return {
            selectedIndex: -1,
            pendingFocusCartKey: null,
            isAddingToCart: false, // Flag to prevent search auto-focus during cart add

            initBilling() {
                const self = this;

                // Auto-focus search input on page load
                setTimeout(() => {
                    const searchEl = document.getElementById('productSearchInput');
                    if (searchEl) {
                        searchEl.focus();
                        console.log('Page loaded - search input focused');
                    }
                }, 300);

                // Listen for Livewire finish event to handle pending focus
                Livewire.hook('morph.updated', ({ el, component }) => {
                    // Reset selectedIndex when search results disappear
                    const items = document.querySelectorAll('.search-result-item');
                    if (items.length === 0) {
                        self.selectedIndex = -1;
                    }
                    
                    // If we have a pending focus cart key, try to focus it after morph
                    if (self.pendingFocusCartKey) {
                        console.log('🎯 Morph complete, attempting to focus qty input for:', self.pendingFocusCartKey);
                        
                        // Give DOM a moment to fully settle
                        setTimeout(() => {
                            const success = self.focusCartQty(self.pendingFocusCartKey);
                            if (success) {
                                console.log('✅ Successfully focused qty input');
                                self.pendingFocusCartKey = null;
                                self.isAddingToCart = false;
                            } else {
                                console.warn('❌ Failed to focus qty input, retrying...');
                                // Retry once more with longer delay
                                setTimeout(() => {
                                    const retrySuccess = self.focusCartQty(self.pendingFocusCartKey);
                                    if (retrySuccess) {
                                        console.log('✅ Retry successful - qty input focused');
                                        self.pendingFocusCartKey = null;
                                    }
                                    self.isAddingToCart = false;
                                }, 200);
                            }
                        }, 100);
                    }
                });

                // Auto-close alerts after 5 seconds
                this.autoCloseAlerts();
                Livewire.hook('morph.updated', () => {
                    self.autoCloseAlerts();
                });
            },

            autoCloseAlerts() {
                document.querySelectorAll('.alert-dismissible').forEach(alert => {
                    if (alert.dataset.autoclose) return;
                    alert.dataset.autoclose = '1';
                    setTimeout(() => {
                        try { new bootstrap.Alert(alert).close(); } catch(e) {}
                    }, 5000);
                });
            },

            // --- Search dropdown navigation ---
            getSearchItems() {
                return document.querySelectorAll('.search-result-item');
            },

            moveDown() {
                const items = this.getSearchItems();
                if (items.length === 0) return;
                this.selectedIndex = (this.selectedIndex + 1) % items.length;
                this.scrollToSelected();
            },

            moveUp() {
                const items = this.getSearchItems();
                if (items.length === 0) return;
                this.selectedIndex = this.selectedIndex <= 0 ? items.length - 1 : this.selectedIndex - 1;
                this.scrollToSelected();
            },

            scrollToSelected() {
                this.$nextTick(() => {
                    const item = document.querySelector(`.search-result-item[data-search-index="${this.selectedIndex}"]`);
                    if (item && this.$refs.searchResultsContainer) {
                        item.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                    }
                });
            },

            closeDropdown() {
                this.selectedIndex = -1;
                // Clear search to hide dropdown
                if (this.$refs.searchInput) {
                    this.$refs.searchInput.value = '';
                    this.$refs.searchInput.dispatchEvent(new Event('input'));
                }
            },

            selectCurrent() {
                const items = this.getSearchItems();
                if (this.selectedIndex < 0 || items.length === 0) return;
                const item = items[this.selectedIndex];
                if (item) {
                    console.log('📦 Selecting item at index:', this.selectedIndex);
                    this.selectedIndex = -1;
                    this.isAddingToCart = true; // Set flag before clicking
                    // Click the item to trigger wire:click (addToCart)
                    item.click();
                    // Focus will be handled by handleProductAdded via Livewire event
                }
            },

            // --- Called via @product-added-to-cart.window from Livewire ---
            handleProductAdded(cartKey) {
                console.log('🔔 Product added event received, cart key:', cartKey);
                this.pendingFocusCartKey = cartKey;
                this.isAddingToCart = true;
                // The morph.updated hook will handle the focus when DOM is ready
            },

            // --- Focus helpers ---
            focusCartQty(cartKey) {
                console.log('🔍 Looking for qty input with cart key:', cartKey);
                const qtyInput = document.querySelector(`input.cart-qty-input[data-cart-key="${cartKey}"]`);
                if (qtyInput) {
                    console.log('✅ Found qty input - focusing and selecting');
                    qtyInput.focus();
                    qtyInput.select();
                    return true;
                } else {
                    console.warn('⚠️ Qty input not found for cart key:', cartKey, '- trying first input');
                    // Fallback: focus first qty input
                    const first = document.querySelector('input.cart-qty-input');
                    if (first) { 
                        console.log('✅ Focused first qty input as fallback');
                        first.focus(); 
                        first.select();
                        return true;
                    }
                    console.error('❌ No qty inputs found at all');
                    return false;
                }
            },

            // Qty Enter → commit change, then focus price of same row
            handleQtyEnter(event, cartKey) {
                console.log('⏎ Qty Enter pressed for cart key:', cartKey);
                const el = event.target;
                el.dispatchEvent(new Event('change', { bubbles: true }));
                setTimeout(() => {
                    const priceInput = document.querySelector(`input.cart-price-input[data-cart-key="${cartKey}"]`);
                    if (priceInput) {
                        priceInput.focus();
                        priceInput.select();
                        console.log('💰 Price input focused');
                    } else {
                        console.error('❌ Price input not found for cart key:', cartKey);
                    }
                }, 150);
            },

            // Price Enter → commit change, then focus search
            handlePriceEnter(event) {
                console.log('⏎ Price Enter pressed - focusing search');
                const el = event.target;
                el.dispatchEvent(new Event('change', { bubbles: true }));
                setTimeout(() => {
                    const searchEl = document.getElementById('productSearchInput');
                    if (searchEl) {
                        searchEl.focus();
                        searchEl.value = '';
                        console.log('🔍 Search focused and cleared');
                    }
                }, 150);
            },

            // --- Global keydown: focus search on any key if nothing is focused ---
            handleGlobalKey(event) {
                // Don't interfere if we're adding to cart
                if (this.isAddingToCart) {
                    console.log('🚫 Ignoring global key - adding to cart in progress');
                    return;
                }
                
                // Don't interfere with modals, textareas, or existing inputs
                const tag = event.target.tagName.toLowerCase();
                if (tag === 'input' || tag === 'textarea' || tag === 'select') return;
                if (document.querySelector('.modal.show.d-block')) return;

                // Printable character → focus search box
                if (event.key.length === 1 && !event.ctrlKey && !event.altKey && !event.metaKey) {
                    const searchEl = document.getElementById('productSearchInput');
                    if (searchEl) {
                        searchEl.focus();
                        // Let the character pass through to the input naturally
                    }
                }
            }
        }
    }
</script>
@endpush