<div>
    @push('styles')
    <style>
        /* Tab navigation styles */
        .content-tabs {
            display: flex;
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 20px;
        }

        .content-tab {
            padding: 10px 20px;
            cursor: pointer;
            font-weight: 500;
            color: #495057;
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
        }

        .content-tab.active {
            color: #f58320;
            border-bottom-color: #f58320;
        }

        .content-tab:hover:not(.active) {
            color: #f58320;
            border-bottom-color: #dee2e6;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Modern card styling */
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
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            border-radius: 12px 12px 0 0 !important;
            padding: 1.25rem 1.5rem;
        }

        /* Modern header styling */
        .inventory-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .search-bar {
            width: 300px;
        }

        /* Modern table styling */
        .table th {
            border-top: none;
            font-weight: 600;
            
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table td {
            vertical-align: middle;
            padding: 1rem 0.75rem;
        }

        /* Modern action buttons */
        .action-btns {
            display: flex;
            gap: 8px;
            justify-content: center;
        }

        .action-btn {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            background: transparent;
            transition: all 0.2s ease;
        }

        .action-btn:hover {
            transform: scale(1.1);
        }

        .action-btn.view {
            color: #17a2b8;
            background-color: rgba(23, 162, 184, 0.1);
        }

        .action-btn.edit {
            color: #4361ee;
            background-color: rgba(67, 97, 238, 0.1);
        }

        .action-btn.delete {
            color: #dc3545;
            background-color: rgba(220, 53, 69, 0.1);
        }

        .action-btn:hover.view {
            background-color: rgba(23, 162, 184, 0.2);
        }

        .action-btn:hover.edit {
            background-color: rgba(67, 97, 238, 0.2);
        }

        .action-btn:hover.delete {
            background-color: rgba(220, 53, 69, 0.2);
        }

        /* Modern modal styling */
        .modal-content {
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .modal-header {
            border-radius: 12px 12px 0 0;
        }

        /* Modern form styling */
        .form-control,
        .form-select {
            border-radius: 8px;
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
        }

        .form-control:focus,
        .form-select:focus {
            box-shadow: 0 0 0 3px rgba(245, 131, 32, 0.15);
            border-color: #f58320;
        }

        /* Modern button styling */
        .btn {
            border-radius: 8px;
            font-weight: 500;
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: #f58320;
            border-color: #f58320;
        }

        .btn-primary:hover {
            background-color: #d16d0e;
            border-color: #d16d0e;
            transform: translateY(-2px);
        }

        .btn-success {
            background-color: #4cc9f0;
            border-color: #4cc9f0;
        }

        .btn-success:hover {
            background-color: #3db8e0;
            border-color: #3db8e0;
            transform: translateY(-2px);
        }

        .btn-warning {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #000;
        }

        .btn-warning:hover {
            background-color: #e0a800;
            border-color: #e0a800;
            transform: translateY(-2px);
            color: #000;
        }

        /* History Modal Styles */
        .nav-tabs-custom .nav-link {
            border: none;
            color: #6c757d;
            font-weight: 500;
            padding: 1rem 1.5rem;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }

        .nav-tabs-custom .nav-link.active {
            color: #f58320;
            border-bottom-color: #f58320;
            background-color: transparent;
        }

        .nav-tabs-custom .nav-link:hover {
            color: #f58320;
            border-bottom-color: #dee2e6;
        }

        /* History Table Styles */
        .history-table th {
            background: linear-gradient(135deg, #000000 0%, #000000 100%);
            color: white;
            font-weight: 600;
            border: none;
        }

        .history-table td {
            vertical-align: middle;
            padding: 0.75rem;
        }

        /* Badge styles for counts */
        .badge {
            font-size: 0.7em;
            padding: 0.35em 0.65em;
        }

        /* Responsive design */
        @media (max-width: 576px) {
            .content-tabs {
                flex-direction: column;
                border-bottom: none;
            }

            .content-tab {
                text-align: center;
                border: 1px solid #dee2e6;
                border-radius: 4px;
                margin-bottom: 8px;
                padding: 12px 15px;
            }

            .content-tab.active {
                background-color: #4361ee;
                color: white;
                border-color: #4361ee;
            }

            .pagination {
                flex-wrap: wrap;
                justify-content: center;
            }

            .page-item {
                margin-bottom: 0.25rem;
            }

            h2,
            .h2 {
                font-size: 1.5rem;
            }

            h5,
            .h5 {
                font-size: 1.1rem;
            }

            .fs-5 {
                font-size: 1rem !important;
            }

            .card-title {
                margin-bottom: 0.5rem;
            }

            .form-select,
            .form-control {
                font-size: 0.95rem;
                padding: 0.375rem 0.5rem;
            }

            .badge {
                font-size: 0.75rem;
            }

            .nav-tabs-custom .nav-link {
                padding: 0.75rem 1rem;
                font-size: 0.9rem;
            }

            .history-table {
                font-size: 0.85rem;
            }
        }

        @media (max-width: 767.98px) {
            .modal-body .row .col-md-4.border-end {
                border-right: none !important;
                border-bottom: 1px solid #dee2e6;
                padding-bottom: 1rem;
                margin-bottom: 1rem;
            }

            .accordion-button {
                padding: 0.75rem;
            }

            .accordion-body {
                padding: 0.75rem;
            }

            .table-borderless th {
                width: auto !important;
                min-width: 120px;
            }
        }

        /* Dropdown Styles */
        .dropdown-toggle::after {
            margin-left: 0.5em;
        }

        .dropdown-menu {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(0, 0, 0, 0.1);
            position: absolute;
            z-index: 1000;
        }

        .dropdown-item {
            padding: 0.5rem 1rem;
            transition: all 0.2s ease;
        }

        .dropdown-item:hover {
            background-color: #f8f9fa;
        }

        .dropdown-item:active {
            background-color: #e9ecef;
        }

        /* Table responsive for dropdowns */
        .table-responsive {
            overflow: visible !important;
            
        }
        .table-responsive.view{
            min-height: 100px !important;
        }


        .table td .dropdown {
            position: static;
        }

        /* Stock Adjustment Modal Styles */
        #stockAdjustmentModal .modal-header {
            background: linear-gradient(135deg, #000000 0%, #000000 100%) !important;
        }

        #stockAdjustmentModal .alert-info {
            background-color: #f8f9fa;
            border-left: 4px solid #17a2b8;
        }

        #stockAdjustmentModal .preview-box {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            border-left: 4px solid #28a745;
        }

        /* Stock level indicators */
        .stock-low {
            color: #dc3545;
            font-weight: bold;
        }

        .stock-medium {
            color: #fe9604ff;
            font-weight: bold;
        }

        .stock-high {
            color: #28a745;
            font-weight: bold;
        }

        /* Modal Enhancements */
        #viewProductModal .modal-content {
            border-radius: 1rem;
            overflow: hidden;
        }

        #viewProductModal .product-image {
            transition: transform 0.3s ease;
        }

        #viewProductModal .product-image:hover {
            transform: scale(1.05);
        }

        #viewProductModal .info-section {
            animation: fadeInUp 0.5s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        #viewProductModal .info-item,
        #viewProductModal .price-card,
        #viewProductModal .stock-card {
            transition: all 0.3s ease;
        }

        #viewProductModal .info-item:hover,
        #viewProductModal .price-card:hover,
        #viewProductModal .stock-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        }

        #viewProductModal .section-header {
            position: relative;
        }

        #viewProductModal .section-header::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, #1536c7ff, #2143ebff);
            border-radius: 2px;
        }

        #viewProductModal .badge {
            font-size: 0.85rem;
            font-weight: 600;
            padding: 0.5rem 1rem;
        }

        #viewProductModal .icon-box i {
            font-size: 1.25rem;
        }

        /* Responsive adjustments */
        @media (max-width: 991px) {
            #viewProductModal .col-lg-4 {
                border-bottom: 1px solid #dee2e6;
                border-right: none;
            }
        }
    </style>
    @endpush

    <div class="container-fluid p-3">

        <div id="products" class="tab-content active">
            <div class="d-flex justify-content-between align-items-center mb-5">
                <div>
                    <h3 class="fw-bold text-dark mb-2">
                        <i class="bi bi-box-seam text-success me-2"></i> Product Inventory Management
                    </h3>
                    <p class="text-muted mb-0">Manage your product catalog and inventory levels efficiently</p>
                </div>
            </div>

            <div class="inventory-header w-100 mb-1">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center w-100 gap-3">

                    <div class="search-bar flex-grow-1">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="bi bi-search text-muted"></i>
                            </span>
                            <input type="text" class="form-control border-start-0" id="product-search"
                                wire:model.live="search" placeholder="Search Products...">
                        </div>
                    </div>

                    <div class="d-flex gap-2 justify-content-md-end">
                        @if (!$isStaff)
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importProductsModal">
                            <i class="bi bi-file-earmark-excel"></i>
                            <span class="d-none d-sm-inline">Import Excel</span>
                        </button>
                        <button class="btn btn-primary add-product-btn" wire:click="openCreateModal">
                            <i class="bi bi-plus-lg"></i>
                            <span class="d-none d-sm-inline">Add Product</span>
                        </button>
                        @endif
                    </div>

                </div>
            </div>

            <div class="row g-4">
                <div class="col-12">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="fw-bold text-dark mb-1">
                                    <i class="bi bi-list-ul text-primary me-2"></i> Products List
                                </h5>
                                <p class="text-muted small mb-0">View and manage all products in your inventory</p>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <label class="text-sm text-muted fw-medium">Show</label>
                                <select wire:model.live="perPage" class="form-select form-select-sm" style="width: 80px;">
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                    <option value="200">200</option>
                                    <option value="500">500</option>
                                </select>
                                <span class="text-sm text-muted">entries</span>
                            </div>
                        </div>

                        <div class="card-body p-0 overflow-auto">
                            <div class="table-responsive ">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">No</th>
                                            <th>Code</th>
                                            <th>Product Name</th>
                                            
                                            <th>Brand</th>
                                            <th>Model</th>
                                            @if ($isStaff)
                                                @if ($staffType === 'shop_staff')
                                                <th>Wholesale Price</th>
                                                @else
                                                <th>Distributor Price</th>
                                                @endif
                                            @endif
                                            <th>Stock</th>
                                            <th>Status</th>
                                            
                                            @if (!$isStaff)
                                            <th class="text-end pe-5">Actions</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if ($products->count() > 0)
                                        @foreach ($products as $product)
                                        <tr wire:key="product-{{ $product->id }}-{{ $products->currentPage() }}">
                                            <td class="ps-4" wire:click="viewProductDetails({{ $product->id }})">
                                                <span class="fw-medium text-dark">{{ $loop->iteration }}</span>
                                            </td>
                                            <td wire:click="viewProductDetails({{ $product->id }})">
                                                <span class="fw-medium text-dark">{{ $product->code }}</span>
                                            </td>
                                            <td wire:click="viewProductDetails({{ $product->id }})">
                                                <span class="fw-medium text-dark">{{ $product->product_name }}</span>
                                            </td>
                                            
                                            <td wire:click="viewProductDetails({{ $product->id }})">
                                                <span class="fw-medium text-dark">{{ $product->brand }}</span>
                                            </td>
                                            <td wire:click="viewProductDetails({{ $product->id }})">
                                                <span class="fw-medium text-dark">{{ $product->model }}</span>
                                            </td>
                                            @if ($isStaff)
                                                @if ($staffType === 'shop_staff')
                                                <td wire:click="viewProductDetails({{ $product->id }})">
                                                    <span class="fw-medium text-primary">Rs.{{ number_format($product->wholesale_price ?? 0, 2) }}</span>
                                                </td>
                                                @else
                                                <td wire:click="viewProductDetails({{ $product->id }})">
                                                    <span class="fw-medium text-primary">Rs.{{ number_format($product->distributor_price ?? 0, 2) }}</span>
                                                </td>
                                                @endif
                                            @endif
                                            <td wire:click="viewProductDetails({{ $product->id }})">
                                                @php
                                                $availableStock = $product->available_stock ?? 0;
                                                $stockClass = 'stock-high';
                                                if ($availableStock <= 5) { $stockClass='stock-low' ; } elseif
                                                    ($availableStock <=15) { $stockClass='stock-medium' ; } @endphp
                                                    <span class="fw-medium {{ $stockClass }}">
                                                    {{ $availableStock }}
                                                    @if($availableStock <= 5) <i
                                                        class="bi bi-exclamation-triangle-fill ms-1"></i>
                                                        @endif
                                                        </span>
                                            </td>
                                            <td wire:click="viewProductDetails({{ $product->id }})">
                                                @if ($product->status == 'active')
                                                <span class="badge bg-success">Active</span>
                                                @else
                                                <span class="badge bg-danger">Inactive</span>
                                                @endif
                                            </td>

                                            @if (!$isStaff)
                                            <td class="text-end pe-4">
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                                        type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="bi bi-gear-fill"></i> Actions
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <button class="dropdown-item"
                                                                wire:click="editProduct({{ $product->id }})"
                                                                wire:loading.attr="disabled"
                                                                wire:target="editProduct({{ $product->id }})">

                                                                <span wire:loading
                                                                    wire:target="editProduct({{ $product->id }})">
                                                                    <i
                                                                        class="spinner-border spinner-border-sm me-2"></i>
                                                                    Loading...
                                                                </span>
                                                                <span wire:loading.remove
                                                                    wire:target="editProduct({{ $product->id }})">
                                                                    <i
                                                                        class="bi bi-pencil-square text-warning me-2"></i>
                                                                    Edit
                                                                </span>
                                                            </button>
                                                        </li>

                                                        <!-- Stock Adjustment Button -->
                                                        <li>
                                                            <button class="dropdown-item"
                                                                wire:click="openStockAdjustment({{ $product->id }})"
                                                                wire:loading.attr="disabled"
                                                                wire:target="openStockAdjustment({{ $product->id }})">

                                                                <span wire:loading
                                                                    wire:target="openStockAdjustment({{ $product->id }})">
                                                                    <i
                                                                        class="spinner-border spinner-border-sm me-2"></i>
                                                                    Loading...
                                                                </span>
                                                                <span wire:loading.remove
                                                                    wire:target="openStockAdjustment({{ $product->id }})">
                                                                    <i class="bi bi-clipboard-plus text-info me-2"></i>
                                                                    Stock Adjustment
                                                                </span>
                                                            </button>
                                                        </li>

                                                        <!-- History Button -->
                                                        <li>
                                                            <a class="dropdown-item"
                                                                href="{{ route('product-history', $product->id) }}"
                                                                target="_blank">
                                                                <i class="bi bi-clock-history text-info me-2"></i>
                                                                View History
                                                                <i class="bi bi-box-arrow-up-right ms-2 small"></i>
                                                            </a>
                                                        </li>

                                                        <li>
                                                            <button class="dropdown-item"
                                                                wire:click="confirmDeleteProduct({{ $product->id }})"
                                                                wire:loading.attr="disabled"
                                                                wire:target="confirmDeleteProduct({{ $product->id }})">

                                                                <span wire:loading
                                                                    wire:target="confirmDeleteProduct({{ $product->id }})">
                                                                    <i
                                                                        class="spinner-border spinner-border-sm me-2"></i>
                                                                    Loading...
                                                                </span>
                                                                <span wire:loading.remove
                                                                    wire:target="confirmDeleteProduct({{ $product->id }})">
                                                                    <i class="bi bi-trash text-danger me-2"></i>
                                                                    Delete
                                                                </span>
                                                            </button>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                            @endif
                                        </tr>
                                        @endforeach
                                        @else
                                        <tr>
                                            <td colspan="10" class="text-center py-5">
                                                <div class="alert alert-primary bg-opacity-10">
                                                    <i class="bi bi-info-circle me-2"></i> No products found.
                                                </div>
                                            </td>
                                        </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                            <div class="card-footer bg-light">
                                <div class="d-flex justify-content-center">
                                    {{ $products->links('livewire.custom-pagination') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- View Product Modal -->
        <div wire:ignore.self class="modal fade" id="viewProductModal" tabindex="-1"
            aria-labelledby="viewProductModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header border-0 bg-gradient-primary text-white position-relative"
                        style="background: linear-gradient(135deg, #000000 0%, #000000 100%); padding: 1.5rem;">
                        <h5 class="modal-title fw-bold d-flex align-items-center">
                            <i class="bi bi-box-seam me-2 fs-4"></i>
                            <span>Product Details</span>
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-0">
                        @if($viewProduct)
                        <div class="row g-0">
                            @if ($isStaff)
                            <!-- Staff View: Simplified layout -->
                            <div class="col-12">
                                <div class="p-4">
                                    <!-- Product Image and Status -->
                                    <div class="text-center mb-4">
                                        <img src="{{ $viewProduct->image ? asset( $viewProduct->image) : asset('images/product.jpg') }}"
                                            alt="Product Image" class="img-fluid rounded-3 shadow-sm"
                                            style="width: 100%; max-width: 250px; height: 250px; object-fit: cover; border: 3px solid #f0f0f0;">

                                        @if($viewProduct->status == 'active')
                                        <div class="mt-3">
                                            <span class="badge bg-success shadow-sm px-3 py-2">
                                                <i class="bi bi-check-circle me-1"></i>Active
                                            </span>
                                        </div>
                                        @else
                                        <div class="mt-3">
                                            <span class="badge bg-danger shadow-sm px-3 py-2">
                                                <i class="bi bi-x-circle me-1"></i>Inactive
                                            </span>
                                        </div>
                                        @endif
                                    </div>

                                    <!-- Product Name and Code -->
                                    <h4 class="fw-bold text-dark text-center mb-2">{{ $viewProduct->name }}</h4>
                                    <p class="text-muted text-center mb-4">
                                        <i class="bi bi-upc-scan me-1"></i>
                                        <span class="font-monospace">{{ $viewProduct->code }}</span>
                                    </p>

                                    <!-- Staff Allocated Stock Information -->
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-6">
                                            <div class="card border-0 shadow-sm bg-white h-100">
                                                <div class="card-body p-3 text-center">
                                                    <div class="text-success mb-2">
                                                        <i class="bi bi-box-seam fs-4"></i>
                                                    </div>
                                                    <h5 class="fw-bold mb-0">
                                                        @php
                                                        $availableStock = ($viewProduct->quantity ?? 0) - ($viewProduct->sold_quantity ?? 0);
                                                        @endphp
                                                        {{ $availableStock > 0 ? $availableStock : 0 }}
                                                    </h5>
                                                    <small class="text-muted">Available Stock</small>
                                                    <div class="mt-2">
                                                        @if($availableStock > 0)
                                                        <span class="badge bg-success bg-opacity-25 text-success">In Stock</span>
                                                        @else
                                                        <span class="badge bg-danger bg-opacity-25 text-danger">Out of Stock</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="card border-0 shadow-sm bg-white h-100">
                                                <div class="card-body p-3 text-center">
                                                    <div class="text-info mb-2">
                                                        <i class="bi bi-currency-dollar fs-4"></i>
                                                    </div>
                                                    <h5 class="fw-bold mb-0">
                                                        Rs.{{ number_format($viewProduct->unit_price ?? 0, 2) }}
                                                    </h5>
                                                    <small class="text-muted">Unit Price</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Product Details -->
                                    <div class="card border-0 shadow-sm mb-1">
                                        <div class="card-body p-3">
                                            <h6 class="fw-bold text-dark mb-1">
                                                <i class="bi bi-info-circle-fill text-primary me-2"></i>Product Information
                                            </h6>
                                            <div class="row g-2">
                                                <div class="col-md-6">
                                                    <small class="text-muted d-block mb-1">Product Code</small>
                                                    <span class="fw-semibold text-dark">{{ $viewProduct->code }}</span>
                                                </div>
                                                <div class="col-md-6">
                                                    <small class="text-muted d-block mb-1">Brand</small>
                                                    <span class="fw-semibold text-dark">{{ $viewProduct->brand ?? '-' }}</span>
                                                </div>
                                                <div class="col-md-6">
                                                    <small class="text-muted d-block mb-1">Model</small>
                                                    <span class="fw-semibold text-dark">{{ $viewProduct->model ?? '-' }}</span>
                                                </div>
                                                <div class="col-md-6">
                                                    <small class="text-muted d-block mb-1">Total Allocated</small>
                                                    <span class="fw-semibold text-dark">{{ $viewProduct->quantity ?? 0 }} units</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    @if($viewProduct->description)
                                    <div class="card border-0 shadow-sm">
                                        <div class="card-body p-3">
                                            <h6 class="fw-bold text-dark mb-2">
                                                <i class="bi bi-card-text text-info me-2"></i>Description
                                            </h6>
                                            <p class="mb-0 text-muted small">{{ $viewProduct->description }}</p>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @else
                            <!-- Admin View: Full product details with pricing -->
                            <div class="col-lg-4 bg-light border-end">
                                <div class="p-4 text-center">
                                    <div class="product-image-container mb-4 position-relative">
                                        <img src="{{ $viewProduct->image ? asset( $viewProduct->image) : asset('images/product.jpg') }}"
                                            alt="Product Image" class="img-fluid rounded-3 shadow-sm product-image"
                                            style="width: 100%; max-width: 280px; height: 280px; object-fit: cover; border: 3px solid #fff;">

                                        <div class="position-absolute top-0 end-0 m-3">
                                            @if($viewProduct->status == 'active')
                                            <span class="badge bg-success shadow-sm px-3 py-2">
                                                <i class="bi bi-check-circle me-1"></i>Active
                                            </span>
                                            @else
                                            <span class="badge bg-danger shadow-sm px-3 py-2">
                                                <i class="bi bi-x-circle me-1"></i>Inactive
                                            </span>
                                            @endif
                                        </div>
                                    </div>

                                    <h4 class="fw-bold text-dark mb-1">{{ $viewProduct->name }}</h4>
                                    <p class="text-muted mb-1">
                                        <i class="bi bi-upc-scan me-1"></i>
                                        <span class="font-monospace">{{ $viewProduct->code }}</span>
                                    </p>

                                    @php
                                        // Compute totals: for variant products sum all variant stocks (available & damaged),
                                        // otherwise use the single stock record
                                        $available = 0;
                                        $damaged = 0;
                                        if ($viewProduct) {
                                            if (method_exists($viewProduct, 'hasVariants') && $viewProduct->hasVariants()) {
                                                $available = $viewProduct->stocks->sum('available_stock');
                                                $damaged = $viewProduct->stocks->sum('damage_stock');
                                            } else {
                                                $available = $viewProduct->stock->available_stock ?? 0;
                                                $damaged = $viewProduct->stock->damage_stock ?? 0;
                                            }
                                        }
                                    @endphp

                                    <div class="row g-2 mb-3">
                                        <div class="col-md-6">
                                            <div class="card border-0 shadow-sm bg-white">
                                                <div class="card-body p-3 text-center">
                                                    <div class="text-primary mb-1">
                                                        <i class="bi bi-box-seam fs-4"></i>
                                                    </div>
                                                    <h5 class="fw-bold mb-0">{{ $available }}</h5>
                                                    <small class="text-muted">Available Stock</small>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="card border-0 shadow-sm bg-white">
                                                <div class="card-body p-3 text-center">
                                                    <div class="text-danger mb-1">
                                                        <i class="bi bi-exclamation-triangle-fill fs-4"></i>
                                                    </div>
                                                    <h5 class="fw-bold mb-0">{{ $damaged }}</h5>
                                                    <small class="text-muted">Damaged Stock</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    @if(($available ?? 0) > 0)
                                    <div class="alert alert-success border-0 shadow-sm mb-0" role="alert">
                                        <i class="bi bi-check-circle-fill me-2"></i>
                                        <strong>Available</strong> - Ready to sell
                                    </div>
                                    @else
                                    <div class="alert alert-danger border-0 shadow-sm mb-0" role="alert">
                                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                        <strong>Out of Stock</strong>
                                    </div>
                                    @endif
                                </div>
                            </div>

                            <div class="col-lg-8">
                                <div class="p-4">
                                    <div class="info-section mb-4">
                                        <div class="section-header d-flex align-items-center mb-1">
                                            <div class="icon-box bg-primary bg-opacity-10 text-primary rounded-circle me-3"
                                                style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                                <i class="bi bi-info-circle-fill"></i>
                                            </div>
                                            <h6 class="fw-bold mb-0 text-dark">Basic Information</h6>
                                        </div>
                                        <div class="info-grid">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <div class="info-item p-3 bg-light rounded-3">
                                                        <small class="text-muted d-block mb-1">Product Name</small>
                                                        <span class="fw-semibold text-dark">{{ $viewProduct->name
                                                            }}</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="info-item p-3 bg-light rounded-3">
                                                        <small class="text-muted d-block mb-1">Product Code</small>
                                                        <span class="fw-semibold text-dark font-monospace">{{
                                                            $viewProduct->code }}</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="info-item p-3 bg-light rounded-3">
                                                        <small class="text-muted d-block mb-1">Model</small>
                                                        <span class="fw-semibold text-dark">{{ $viewProduct->model ??
                                                            '-' }}</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="info-item p-3 bg-light rounded-3">
                                                        <small class="text-muted d-block mb-1">Brand</small>
                                                        <span class="fw-semibold text-dark">{{ $viewProduct->brand ??
                                                            '-' }}</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-12">
                                                    <div class="info-item p-3 bg-light rounded-3">
                                                        <small class="text-muted d-block mb-1">Category</small>
                                                        <span class="fw-semibold text-dark">{{ $viewProduct->category ??
                                                            '-' }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="info-section mb-4">
                                        <div class="section-header d-flex align-items-center mb-1">
                                            <div class="icon-box bg-success bg-opacity-10 text-success rounded-circle me-3"
                                                style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                                <i class="bi bi-currency-dollar"></i>
                                            </div>
                                            <h6 class="fw-bold mb-0 text-dark">Pricing Information</h6>
                                        </div>
                                        @if(!$viewProduct || !$viewProduct->hasVariants())
                                        <div class="row g-3">
                                            <div class="col-md-3">
                                                <div class="price-card text-center p-3 border rounded-3 h-100">
                                                    <small class="text-muted d-block mb-2">Cost (Supplier)</small>
                                                    <h4 class="fw-bold text-secondary mb-0">
                                                        Rs.{{ number_format($viewProduct->price->supplier_price ?? 0, 2) }}
                                                    </h4>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="price-card text-center p-3 border rounded-3 h-100">
                                                    <small class="text-muted d-block mb-2">Wholesale Price</small>
                                                    <h4 class="fw-bold text-secondary mb-0">
                                                        Rs.{{ number_format($viewProduct->price->wholesale_price ?? 0, 2) }}
                                                    </h4>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="price-card text-center p-3 border rounded-3 h-100">
                                                    <small class="text-muted d-block mb-2">Distributor Price</small>
                                                    <h4 class="fw-bold text-secondary mb-0">
                                                        Rs.{{ number_format($viewProduct->price->distributor_price ?? 0, 2) }}
                                                    </h4>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div
                                                    class="price-card text-center p-3 border border-success rounded-3 bg-success bg-opacity-10 h-100">
                                                    <small class="text-success d-block mb-2 fw-semibold">Retail
                                                        Price</small>
                                                    <h4 class="fw-bold text-success mb-0">
                                                        Rs.{{ number_format($viewProduct->price->retail_price ?? 0, 2) }}
                                                    </h4>
                                                </div>
                                            </div>
                                        </div>
                                        @else
                                        <div class="card mb-1">
                                            <div class="card-header bg-light">
                                                <h6 class="mb-0 fw-semibold">Variant Prices & Stock</h6>
                                            </div>
                                            <div class="card-body p-0">
                                                <div class="table-responsive view">
                                                    <table class="table table-hover mb-0">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th>Value</th>
                                                                <th>Cost</th>
                                                                <th>Wholesale</th>
                                                                <th>Distributor</th>
                                                                <th>Retail</th>
                                                                <th>Available</th>
                                                                <th>Damage</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @php
                                                                // Prefer values prepared by the component (which now preserves DB order)
                                                                $variantValues = $viewProduct->sorted_variant_values ?? [];
 
                                                                if (empty($variantValues)) {
                                                                    if ($viewProduct->variant && is_array($viewProduct->variant->variant_values)) {
                                                                        // Use the variant values as stored in DB (preserve order)
                                                                        $variantValues = $viewProduct->variant->variant_values;
                                                                    } elseif (isset($viewProduct->prices) && $viewProduct->prices->isNotEmpty()) {
                                                                        // Keep the order provided by the prices collection
                                                                        $variantValues = $viewProduct->prices->pluck('variant_value')->unique()->toArray();
                                                                    } else {
                                                                        $variantValues = [];
                                                                    }
                                                                }
                                                            @endphp
 
                                                            @foreach($variantValues as $value)
                                                            @php
                                                                $price = $viewProduct->prices->firstWhere('variant_value', $value) ?? null;
                                                                $stock = $viewProduct->stocks->firstWhere('variant_value', $value) ?? null;
                                                            @endphp
                                                            <tr>
                                                                <td>{{ $value }}</td>
                                                                <td>Rs.{{ number_format($price->supplier_price ?? 0, 2) }}</td>
                                                                <td>Rs.{{ number_format($price->wholesale_price ?? 0, 2) }}</td>
                                                                <td>Rs.{{ number_format($price->distributor_price ?? 0, 2) }}</td>
                                                                <td>Rs.{{ number_format($price->retail_price ?? 0, 2) }}</td>
                                                                <td>{{ $stock->available_stock ?? 0 }}</td>
                                                                <td class="text-danger fw-semibold">{{ $stock->damage_stock ?? 0 }}</td>
                                                            </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                        @endif
                                    </div>

                                    <div class="info-section mb-4">
                                        <div class="section-header d-flex align-items-center mb-1">
                                            <div class="icon-box bg-warning bg-opacity-10 text-warning rounded-circle me-3"
                                                style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                                <i class="bi bi-boxes"></i>
                                            </div>
                                            <h6 class="fw-bold mb-0 text-dark">Stock Information</h6>
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <div class="stock-card p-3 border rounded-3 text-center">
                                                    <i class="bi bi-box-seam text-success fs-3 mb-2"></i>
                                                    <h5 class="fw-bold mb-1">{{ $available ?? ($viewProduct->stock->available_stock ?? 0) }}</h5>
                                                    <small class="text-muted">Available Stock</small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="stock-card p-3 border rounded-3 text-center">
                                                    <i class="bi bi-exclamation-triangle text-danger fs-3 mb-2"></i>
                                                    <h5 class="fw-bold mb-1">{{ $damaged ?? ($viewProduct->stock->damage_stock ?? 0) }}</h5>
                                                    <small class="text-muted">Damaged Stock</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    @if($viewProduct->description)
                                    <div class="info-section">
                                        <div class="section-header d-flex align-items-center mb-1">
                                            <div class="icon-box bg-info bg-opacity-10 text-info rounded-circle me-3"
                                                style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                                <i class="bi bi-card-text"></i>
                                            </div>
                                            <h6 class="fw-bold mb-0 text-dark">Description</h6>
                                        </div>
                                        <div class="p-3 bg-light rounded-3">
                                            <p class="mb-0 text-muted">{{ $viewProduct->description }}</p>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif
                        @else
                        <div class="text-center py-5">
                            <i class="bi bi-exclamation-circle text-muted" style="font-size: 4rem;"></i>
                            <p class="text-muted mt-3 fs-5">Product details not found.</p>
                        </div>
                        @endif
                    </div>

                    <div class="modal-footer border-0 bg-light">
                        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-2"></i>Close
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Product Modal -->
        <div wire:ignore.self class="modal fade" id="createProductModal" tabindex="-1"
            aria-labelledby="createProductModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-plus-circle text-white me-2"></i> Create New Product
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-info-circle text-primary me-2"></i> Basic Information
                                </h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-1">
                                            <label for="code" class="form-label fw-semibold">Code:</label>
                                            <input type="text" class="form-control" id="code" wire:model="code">
                                            @error('code')
                                            <span class="text-danger small">* {{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-1">
                                            <label for="name" class="form-label fw-semibold">Name:</label>
                                            <input type="text" class="form-control" id="name" wire:model="name">
                                            @error('name')
                                            <span class="text-danger small">* {{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-1">
                                            <label for="model" class="form-label fw-semibold">Model:</label>
                                            <input type="text" class="form-control" id="model" wire:model="model">
                                            @error('model')
                                            <span class="text-danger small">* {{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-diagram-3 text-primary me-2"></i> Classification
                                </h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-1">
                                            <label for="brand" class="form-label fw-semibold">Brand:</label>
                                            <select class="form-select" id="brand" wire:model="brand">
                                                <option value="">Select Brand</option>
                                                @foreach ($brands as $brand)
                                                <option value="{{ $brand->id }}" {{ $brand->id == 'Default Brand' ?
                                                    'selected' : '' }}>
                                                    {{ $brand->brand_name }}
                                                    @if($brand->id == 'Default Brand') (Default) @endif
                                                </option>
                                                @endforeach
                                            </select>
                                            @error('brand')
                                            <span class="text-danger small">* {{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-1">
                                            <label for="category" class="form-label fw-semibold">Category:</label>
                                            <select class="form-select" id="category" wire:model="category">
                                                <option value="">Select Category</option>
                                                @foreach ($categories as $category)
                                                <option value="{{ $category->id }}" {{ $category->id == 'Default
                                                    Category' ? 'selected' : '' }}>
                                                    {{ $category->category_name }}
                                                    @if($category->id == 'Default Category') (Default) @endif
                                                </option>
                                                @endforeach
                                            </select>
                                            @error('category')
                                            <span class="text-danger small">* {{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-1">
                                            <label for="supplier" class="form-label fw-semibold">Supplier:</label>
                                            <select class="form-select" id="supplier" wire:model="supplier">
                                                <option value="">Select Supplier</option>
                                                @foreach ($suppliers as $supplier)
                                                <option value="{{ $supplier->id }}" {{ $supplier->id == 'Default
                                                    Supplier' ? 'selected' : '' }}>
                                                    {{ $supplier->name }}
                                                    @if($supplier->id == 'Default Supplier') (Default) @endif
                                                </option>
                                                @endforeach
                                            </select>
                                            @error('supplier')
                                            <span class="text-danger small">* {{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-card-text text-primary me-2"></i> Product Information
                                </h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-1">
                                            <label for="image" class="form-label fw-semibold">Image:</label>
                                            <input type="text" class="form-control" id="image" wire:model="image">
                                            @error('image')
                                            <span class="text-danger small">* {{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="mb-1">
                                            <label for="description" class="form-label fw-semibold">Description:</label>
                                            <textarea class="form-control" id="description" rows="3"
                                                wire:model="description"></textarea>
                                            @error('description')
                                            <span class="text-danger small">* {{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-cash text-primary me-2"></i> Pricing and Inventory
                                </h5>
                            </div>
                            <div class="card-body p-4">
                                <!-- Pricing Mode Selection -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">Pricing Mode:</label>
                                        <div class="btn-group w-100" role="group">
                                            <input type="radio" class="btn-check" id="pricing_mode_single" 
                                                wire:model.live="pricing_mode" value="single" checked>
                                            <label class="btn btn-outline-primary" for="pricing_mode_single">
                                                <i class="bi bi-tag me-2"></i>Single Price
                                            </label>
                                            
                                            <input type="radio" class="btn-check" id="pricing_mode_variant" 
                                                wire:model.live="pricing_mode" value="variant">
                                            <label class="btn btn-outline-primary" for="pricing_mode_variant">
                                                <i class="bi bi-tags me-2"></i>Variant-Based Pricing
                                            </label>
                                        </div>
                                        <small class="text-muted d-block mt-2">
                                            @if($pricing_mode === 'single')
                                                Single price applies to all units of this product
                                            @else
                                                Different prices for each variant (e.g., size, color)
                                            @endif
                                        </small>
                                    </div>
                                </div>

                                @if($pricing_mode === 'single')
                                <!-- Single Price Mode -->
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="mb-1">
                                            <label for="supplier_price" class="form-label fw-semibold">
                                                <i class="bi bi-cash-stack text-secondary"></i> Cost Price:
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text">Rs.</span>
                                                <input type="number" step="0.01" class="form-control"
                                                    id="supplier_price" wire:model="supplier_price">
                                            </div>
                                            @error('supplier_price')
                                            <span class="text-danger small">* {{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-1">
                                            <label for="retail_price" class="form-label fw-semibold">
                                                <i class="bi bi-shop text-success"></i> Retail Price:
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text">Rs.</span>
                                                <input type="number" step="0.01" class="form-control" id="retail_price"
                                                    wire:model="retail_price">
                                            </div>
                                            @error('retail_price')
                                            <span class="text-danger small">* {{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-1">
                                            <label for="wholesale_price" class="form-label fw-semibold">
                                                <i class="bi bi-boxes text-info"></i> Wholesale Price:
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text">Rs.</span>
                                                <input type="number" step="0.01" class="form-control" id="wholesale_price"
                                                    wire:model="wholesale_price">
                                            </div>
                                            @error('wholesale_price')
                                            <span class="text-danger small">* {{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-1">
                                            <label for="distributor_price" class="form-label fw-semibold">
                                                <i class="bi bi-truck text-warning"></i> Distributor Price:
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text">Rs.</span>
                                                <input type="number" step="0.01" class="form-control" id="distributor_price"
                                                    wire:model="distributor_price">
                                            </div>
                                            @error('distributor_price')
                                            <span class="text-danger small">* {{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-1">
                                            <label for="available_stock" class="form-label fw-semibold">Available
                                                Stock:</label>
                                            <input type="number" class="form-control" id="available_stock"
                                                wire:model="available_stock">
                                            @error('available_stock')
                                            <span class="text-danger small">* {{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-1">
                                            <label for="damage_stock" class="form-label fw-semibold">Damage
                                                Stock:</label>
                                            <input type="number" class="form-control" id="damage_stock"
                                                wire:model="damage_stock">
                                            @error('damage_stock')
                                            <span class="text-danger small">* {{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                @else
                                <!-- Variant-Based Pricing Mode -->
                                <div class="alert alert-info border-info">
                                    <i class="bi bi-info-circle me-2"></i>
                                    <strong>Note:</strong> Select an existing variant type to manage prices and stock for each value.
                                    <a href="{{ route('admin.manage-variants') }}" target="_blank" class="alert-link">
                                        <i class="bi bi-plus-circle me-1"></i>Manage Variants
                                    </a>
                                </div>

                                <!-- Select Existing Variant -->
                                <div class="row mb-4">
                                    <div class="col-md-12">
                                        <label class="form-label fw-semibold">
                                            <i class="bi bi-folder2-open text-primary me-2"></i>Select Variant Type:
                                        </label>
                                        <select class="form-select form-select-lg" 
                                            wire:model="variant_id" wire:change="selectVariant($event.target.value)">
                                            <option value="">-- Choose a variant --</option>
                                            @foreach($availableVariants as $variant)
                                            <option value="{{ $variant['id'] }}">
                                                {{ $variant['variant_name'] }} ({{ count($variant['variant_values'] ?? []) }} values)
                                            </option>
                                            @endforeach
                                        </select>
                                        @error('variant_id')
                                        <span class="text-danger small">* {{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Pricing Table for Each Value (shown when variant is selected) -->
                                @if(!empty($variant_id) && count($variant_prices) > 0)
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">
                                            <i class="bi bi-cash-coin me-2"></i>Pricing & Stock for Each Value
                                        </h6>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-hover mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th class="text-center">Value</th>
                                                        <th class="text-center">Cost Price</th>
                                                        <th class="text-center">Wholesale</th>
                                                        <th class="text-center">Distributor</th>
                                                        <th class="text-center">Retail</th>
                                                        <th class="text-center">Stock</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @php
                                                        // Preserve the order of variant price keys as provided (DB order)
                                                        $variantPriceKeys = array_values(array_keys($variant_prices ?? []));
                                                    @endphp

                                                    @foreach($variantPriceKeys as $value)
                                                    @php $prices = $variant_prices[$value] ?? []; @endphp
                                                    <tr wire:key="variant-price-{{ $value }}">
                                                        <td class="text-center align-middle">
                                                            <strong class="text-primary">{{ $variant_key_map[$value] ?? $value }}</strong>
                                                        </td>
                                                        <td>
                                                            <div class="input-group input-group-sm">
                                                                <span class="input-group-text">Rs.</span>
                                                                <input type="number" step="0.01" class="form-control" 
                                                                    wire:model.defer="variant_prices.{{ $value }}.supplier_price"
                                                                    placeholder="0.00" id="supplier_{{ $value }}">
                                                            </div>
                                                            @error("variant_prices.{$value}.supplier_price")
                                                            <span class="text-danger small">* {{ $message }}</span>
                                                            @enderror
                                                        </td>
                                                        
                                                        <td>
                                                            <div class="input-group input-group-sm">
                                                                <span class="input-group-text">Rs.</span>
                                                                <input type="number" step="0.01" class="form-control" 
                                                                    wire:model.defer="variant_prices.{{ $value }}.wholesale_price"
                                                                    placeholder="0.00" id="wholesale_{{ $value }}">
                                                            </div>
                                                            @error("variant_prices.{$value}.wholesale_price")
                                                            <span class="text-danger small">* {{ $message }}</span>
                                                            @enderror
                                                        </td>
                                                        <td>
                                                            <div class="input-group input-group-sm">
                                                                <span class="input-group-text">Rs.</span>
                                                                <input type="number" step="0.01" class="form-control" 
                                                                    wire:model.defer="variant_prices.{{ $value }}.distributor_price"
                                                                    placeholder="0.00" id="distributor_{{ $loop->index }}">
                                                            </div>
                                                            @error("variant_prices.{$value}.distributor_price")
                                                            <span class="text-danger small">* {{ $message }}</span>
                                                            @enderror
                                                        </td>
                                                        <td>
                                                            <div class="input-group input-group-sm">
                                                                <span class="input-group-text">Rs.</span>
                                                                <input type="number" step="0.01" class="form-control" 
                                                                    wire:model.defer="variant_prices.{{ $value }}.retail_price"
                                                                    placeholder="0.00" id="retail_{{ $loop->index }}">
                                                            </div>
                                                            @error("variant_prices.{$value}.retail_price")
                                                            <span class="text-danger small">* {{ $message }}</span>
                                                            @enderror
                                                        </td>
                                                        <td>
                                                            <input type="number" class="form-control form-control-sm" 
                                                                wire:model.defer="variant_prices.{{ $value }}.stock"
                                                                placeholder="0" id="stock_{{ $loop->index }}">
                                                            @error("variant_prices.{$value}.stock")
                                                            <span class="text-danger small">* {{ $message }}</span>
                                                            @enderror
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                @elseif(!empty($variant_id))
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    Loading variant data or the selected variant has no values.
                                </div>
                                        </div>
                                    </div>
                                </div>
                                @elseif(!empty($variant_id))
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    Loading variant data...
                                </div>
                                @endif
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" wire:click="createProduct">
                            <span wire:loading wire:target="createProduct">
                                <i class="spinner-border spinner-border-sm"></i> Creating...
                            </span>
                            <span wire:loading.remove wire:target="createProduct">
                                Save Product
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Import Products Modal -->
        <div wire:ignore.self class="modal fade" id="importProductsModal" tabindex="-1"
            aria-labelledby="importProductsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-file-earmark-excel me-2"></i> Import Products from Excel
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-info border-0 mb-4">
                            <h6 class="alert-heading fw-bold">
                                <i class="bi bi-info-circle me-2"></i>Excel File Requirements
                            </h6>
                            <hr>
                            <p class="mb-2">Your Excel file must contain the following columns:</p>
                            <ul class="mb-2">
                                <li><strong>CODE</strong> - Product code (required, unique)</li>
                                <li><strong>NAME</strong> - Product name (required)</li>
                                <li><strong>SUPPLIER PRICE</strong> - Cost price (optional)</li>
                                <li><strong>RETAIL PRICE</strong> - Retail selling price (optional)</li>
                                <li><strong>WHOLESALE PRICE</strong> - Wholesale selling price (optional)</li>
                                <li><strong>AVAILABLE STOCK</strong> - Product quantity in stock (optional)</li>
                            </ul>
                            <p class="mb-2"><strong>Note:</strong> Other product fields will be set to default values:
                            </p>
                            <ul class="mb-0">
                                <li>Brand: Default Brand</li>
                                <li>Category: Default Category</li>
                                <li>Supplier: Default Supplier</li>
                                <li>Status: Active</li>
                                <li>If Available Stock is provided, Total Stock will be set to that value</li>
                            </ul>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                <h6 class="card-title mb-0">
                                    <i class="bi bi-table me-2"></i>Excel Format Example
                                </h6>
                                <button type="button" class="btn btn-sm btn-outline-success"
                                    wire:click="downloadTemplate">
                                    <i class="bi bi-download me-1"></i>Download Template
                                </button>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-bordered mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>CODE</th>
                                                <th>NAME</th>
                                                <th>SUPPLIER PRICE</th>
                                                <th>RETAIL PRICE</th>
                                                <th>WHOLESALE PRICE</th>
                                                <th>AVAILABLE STOCK</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>HM0001</td>
                                                <td>Flasher Musical 12 V</td>
                                                <td>100.00</td>
                                                <td>150.00</td>
                                                <td>140.00</td>
                                                <td>50</td>
                                            </tr>
                                            <tr>
                                                <td>HM0002</td>
                                                <td>Flasher Musical 24 V</td>
                                                <td>120.00</td>
                                                <td>180.00</td>
                                                <td>170.00</td>
                                                <td>30</td>
                                            </tr>
                                            <tr>
                                                <td>HM0003</td>
                                                <td>Flasher Electrical 12 V</td>
                                                <td>90.00</td>
                                                <td>140.00</td>
                                                <td>130.00</td>
                                                <td>40</td>
                                            </tr>
                                            <tr>
                                                <td>HM0004</td>
                                                <td>Flasher Electrical 24 V</td>
                                                <td>110.00</td>
                                                <td>160.00</td>
                                                <td>150.00</td>
                                                <td>25</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="mb-1">
                            <label for="importFile" class="form-label fw-semibold">
                                <i class="bi bi-upload me-2"></i>Select Excel File
                            </label>
                            <input type="file" class="form-control" id="importFile" wire:model="importFile"
                                accept=".xlsx,.xls,.csv">

                            @error('importFile')
                            <span class="text-danger small mt-1 d-block">
                                <i class="bi bi-exclamation-circle me-1"></i>{{ $message }}
                            </span>
                            @enderror

                            <div class="form-text">
                                <i class="bi bi-info-circle me-1"></i>
                                Accepted formats: .xlsx, .xls, .csv (Max size: 10MB)
                            </div>

                            @if($importFile)
                            <div class="alert alert-success mt-3 mb-0">
                                <i class="bi bi-check-circle me-2"></i>
                                File selected: <strong>{{ $importFile->getClientOriginalName() }}</strong>
                            </div>
                            @endif
                        </div>

                        <div class="alert alert-warning border-0 mb-0">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Important:</strong> Duplicate product codes will be skipped. You can edit imported
                            products later to add missing details.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg me-2"></i>Cancel
                        </button>
                        <button type="button" class="btn btn-success" wire:click="importProducts" @if(!$importFile)
                            disabled @endif>
                            <span wire:loading wire:target="importProducts">
                                <i class="spinner-border spinner-border-sm me-2"></i>Importing...
                            </span>
                            <span wire:loading.remove wire:target="importProducts">
                                <i class="bi bi-upload me-2"></i>Import Products
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Product Modal -->
        <div wire:ignore.self wire:key="edit-modal-{{ $editId ?? 'new' }}" class="modal fade" id="editProductModal"
            tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-pencil-square text-white me-2"></i> Edit Product
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-info-circle text-primary me-2"></i> Basic Information
                                </h5>
                            </div>

                            <div class="card-body p-4">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-1">
                                            <label for="editCode" class="form-label fw-semibold">Code:</label>
                                            <input type="text" class="form-control" id="editCode" wire:model="editCode">
                                            @error('editCode')
                                            <span class="text-danger small">* {{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="mb-1">
                                            <label for="editName" class="form-label fw-semibold">Name:</label>
                                            <input type="text" class="form-control" id="editName" wire:model="editName">
                                            @error('editName')
                                            <span class="text-danger small">* {{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="mb-1">
                                            <label for="editModel" class="form-label fw-semibold">Model:</label>
                                            <input type="text" class="form-control" id="editModel"
                                                wire:model="editModel">
                                            @error('editModel')
                                            <span class="text-danger small">* {{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-diagram-3 text-primary me-2"></i> Classification
                                </h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-1">
                                            <label for="editCategory" class="form-label fw-semibold">Category:</label>
                                            <select class="form-select" id="editCategory" wire:model="editCategory">
                                                <option value="">Select Category</option>
                                                @foreach ($categories as $category)
                                                <option value="{{ $category->id }}" {{ $category->id == 1 ? 'selected' :
                                                    '' }}>{{ $category->category_name }}</option>
                                                @endforeach
                                            </select>
                                            @error('editCategory')
                                            <span class="text-danger small">* {{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-1">
                                            <label for="editBrand" class="form-label fw-semibold">Brand:</label>
                                            <select class="form-select" id="editBrand" wire:model="editBrand">
                                                <option value="">Select Brand</option>
                                                @foreach ($brands as $brand)
                                                <option value="{{ $brand->id }}" {{ $brand->id == 1 ? 'selected' : ''
                                                    }}>{{ $brand->brand_name }}</option>
                                                @endforeach
                                            </select>
                                            @error('editBrand')
                                            <span class="text-danger small">* {{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-card-text text-primary me-2"></i> Product Information
                                </h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-1">
                                            <label for="editImage" class="form-label fw-semibold">Image:</label>
                                            <input type="text" class="form-control" id="editImage"
                                                wire:model="editImage">
                                            @error('editImage')
                                            <span class="text-danger small">* {{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="mb-1">
                                            <label for="editDescription"
                                                class="form-label fw-semibold">Description:</label>
                                            <textarea class="form-control" id="editDescription" rows="3"
                                                wire:model="editDescription"></textarea>
                                            @error('editDescription')
                                            <span class="text-danger small">* {{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-cash text-primary me-2"></i> Pricing and Inventory
                                </h5>
                            </div>
                            <div class="card-body p-4">
                                @if($pricing_mode === 'single')
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-1">
                                            <label for="editSupplierPrice" class="form-label fw-semibold">Supplier
                                                Price:</label>
                                            <div class="input-group">
                                                <span class="input-group-text">Rs.</span>
                                                <input type="number" step="0.01" class="form-control"
                                                    id="editSupplierPrice" wire:model="editSupplierPrice">
                                            </div>
                                            @error('editSupplierPrice')
                                            <span class="text-danger small">* {{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-1">
                                            <label for="editRetailPrice" class="form-label fw-semibold">Retail
                                                Price:</label>
                                            <div class="input-group">
                                                <span class="input-group-text">Rs.</span>
                                                <input type="number" step="0.01" class="form-control"
                                                    id="editRetailPrice" wire:model="editRetailPrice">
                                            </div>
                                            @error('editRetailPrice')
                                            <span class="text-danger small">* {{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-1">
                                            <label for="editWholesalePrice" class="form-label fw-semibold">Wholesale
                                                Price:</label>
                                            <div class="input-group">
                                                <span class="input-group-text">Rs.</span>
                                                <input type="number" step="0.01" class="form-control"
                                                    id="editWholesalePrice" wire:model="editWholesalePrice">
                                            </div>
                                            @error('editWholesalePrice')
                                            <span class="text-danger small">* {{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-1">
                                            <label for="editDiscountPrice" class="form-label fw-semibold">Discount
                                                Price:</label>
                                            <div class="input-group">
                                                <span class="input-group-text">Rs.</span>
                                                <input type="number" step="0.01" class="form-control"
                                                    id="editDiscountPrice" wire:model="editDiscountPrice">
                                            </div>
                                            @error('editDiscountPrice')
                                            <span class="text-danger small">* {{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-1">
                                            <label for="editAvailableStock" class="form-label fw-semibold">Available Stock:</label>
                                            <input type="number" class="form-control" id="editAvailableStock"
                                                wire:model="editAvailableStock">
                                            @error('editAvailableStock')
                                            <span class="text-danger small">* {{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-1">
                                            <label for="editStatus" class="form-label fw-semibold">Status:</label>
                                            <select class="form-select" id="editStatus" wire:model="editStatus">
                                                <option value="active">Active</option>
                                                <option value="inactive">Inactive</option>
                                            </select>
                                            @error('editStatus')
                                            <span class="text-danger small">* {{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                @else

                                <div class="alert alert-info border-info">
                                    <i class="bi bi-info-circle me-2"></i>
                                    <strong>Note:</strong> Select an existing variant type to edit prices and stock for each value.
                                </div>

                                {{-- Variant selector (show when there are available variants) --}}
                                @if(!empty($availableVariants))
                                <div class="row mb-4">
                                    <div class="col-md-12">
                                        <label class="form-label fw-semibold">
                                            <i class="bi bi-folder2-open text-primary me-2"></i>Select Variant Type:
                                        </label>
                                        <select class="form-select form-select-lg"
                                            wire:model.live="variant_id" wire:change="selectVariant($event.target.value)">
                                            <option value="">-- Choose a variant --</option>
                                            @foreach($availableVariants as $variant)
                                            <option value="{{ $variant['id'] }}">
                                                {{ $variant['variant_name'] }} ({{ count($variant['variant_values'] ?? []) }} values)
                                            </option>
                                            @endforeach
                                        </select>
                                        @error('variant_id')
                                        <span class="text-danger small">* {{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                @endif

                                {{-- If the product has exactly one variant value, show inline editable inputs immediately --}}
                                @if(count($variant_prices) === 1)
                                @php
                                    $variantPriceKeys = array_keys($variant_prices ?? []);
                                    $singleKey = $variantPriceKeys[0] ?? null;
                                    $singlePrices = $singleKey ? ($variant_prices[$singleKey] ?? []) : [];
                                    $variantName = '';
                                    foreach($availableVariants as $v) {
                                        if(($v['id'] ?? null) == $variant_id) { $variantName = $v['variant_name']; break; }
                                    }
                                @endphp

                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <label class="form-label fw-semibold">
                                            <i class="bi bi-folder2-open text-primary me-2"></i>Variant Type:
                                            <strong class="ms-2">{{ $variantName ?: 'Variant' }}</strong>
                                        </label>
                                    </div>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Cost Price:</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rs.</span>
                                            <input type="number" step="0.01" class="form-control"
                                                wire:model="variant_prices.{{ $singleKey }}.supplier_price"
                                                placeholder="0.00">
                                        </div>
                                        @error("variant_prices.{$singleKey}.supplier_price")
                                        <span class="text-danger small">* {{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Retail Price:</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rs.</span>
                                            <input type="number" step="0.01" class="form-control"
                                                wire:model="variant_prices.{{ $singleKey }}.retail_price"
                                                placeholder="0.00">
                                        </div>
                                        @error("variant_prices.{$singleKey}.retail_price")
                                        <span class="text-danger small">* {{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Wholesale Price:</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rs.</span>
                                            <input type="number" step="0.01" class="form-control"
                                                wire:model="variant_prices.{{ $singleKey }}.wholesale_price"
                                                placeholder="0.00">
                                        </div>
                                        @error("variant_prices.{$singleKey}.wholesale_price")
                                        <span class="text-danger small">* {{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Distributor Price:</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rs.</span>
                                            <input type="number" step="0.01" class="form-control"
                                                wire:model="variant_prices.{{ $singleKey }}.distributor_price"
                                                placeholder="0.00">
                                        </div>
                                        @error("variant_prices.{$singleKey}.distributor_price")
                                        <span class="text-danger small">* {{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Available Stock:</label>
                                        <input type="number" class="form-control" 
                                            wire:model="variant_prices.{{ $singleKey }}.stock" placeholder="0">
                                        @error("variant_prices.{$singleKey}.stock")
                                        <span class="text-danger small">* {{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Damage Stock:</label>
                                        <input type="number" class="form-control" 
                                            wire:model="variant_prices.{{ $singleKey }}.damage_stock" placeholder="0">
                                        @error("variant_prices.{$singleKey}.damage_stock")
                                        <span class="text-danger small">* {{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                @elseif(!empty($variant_id) && count($variant_prices) > 0)
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">Edit Variant Pricing & Stock</h6>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-hover mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th class="text-center">Value</th>
                                                        <th class="text-center">Cost Price</th>
                                                        <th class="text-center">Wholesale</th>
                                                        <th class="text-center">Distributor</th>
                                                        <th class="text-center">Retail</th>
                                                        <th class="text-center">Stock</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @php
                                                        // Preserve the order of variant price keys as provided (DB order)
                                                        $variantPriceKeys = array_values(array_keys($variant_prices ?? []));
                                                    @endphp

                                                    @foreach($variantPriceKeys as $value)
                                                    @php $prices = $variant_prices[$value] ?? []; @endphp
                                                    <tr wire:key="edit-variant-price-{{ $value }}">
                                                        <td class="text-center align-middle">
                                                            <strong class="text-primary">{{ $variant_key_map[$value] ?? $value }}</strong>
                                                        </td>
                                                        <td>
                                                            <div class="input-group input-group-sm">
                                                                <span class="input-group-text">Rs.</span>
                                                                <input type="number" step="0.01" class="form-control" 
                                                                    wire:model="variant_prices.{{ $value }}.supplier_price"
                                                                    placeholder="0.00" id="edit_supplier_{{ $value }}">
                                                            </div>
                                                            @error("variant_prices.{$value}.supplier_price")
                                                            <span class="text-danger small">* {{ $message }}</span>
                                                            @enderror
                                                        </td>
                                                        <td>
                                                            <div class="input-group input-group-sm">
                                                                <span class="input-group-text">Rs.</span>
                                                                <input type="number" step="0.01" class="form-control" 
                                                                    wire:model="variant_prices.{{ $value }}.wholesale_price"
                                                                    placeholder="0.00" id="edit_wholesale_{{ $value }}">
                                                            </div>
                                                            @error("variant_prices.{$value}.wholesale_price")
                                                            <span class="text-danger small">* {{ $message }}</span>
                                                            @enderror
                                                        </td>
                                                        <td>
                                                            <div class="input-group input-group-sm">
                                                                <span class="input-group-text">Rs.</span>
                                                                <input type="number" step="0.01" class="form-control" 
                                                                    wire:model="variant_prices.{{ $value }}.distributor_price"
                                                                    placeholder="0.00" id="edit_distributor_{{ $loop->index }}">
                                                            </div>
                                                            @error("variant_prices.{$value}.distributor_price")
                                                            <span class="text-danger small">* {{ $message }}</span>
                                                            @enderror
                                                        </td>
                                                        <td>
                                                            <div class="input-group input-group-sm">
                                                                <span class="input-group-text">Rs.</span>
                                                                <input type="number" step="0.01" class="form-control" 
                                                                    wire:model="variant_prices.{{ $value }}.retail_price"
                                                                    placeholder="0.00" id="edit_retail_{{ $loop->index }}">
                                                            </div>
                                                            @error("variant_prices.{$value}.retail_price")
                                                            <span class="text-danger small">* {{ $message }}</span>
                                                            @enderror
                                                        </td>
                                                        <td>
                                                            <input type="number" class="form-control form-control-sm" 
                                                                wire:model="variant_prices.{{ $value }}.stock"
                                                                placeholder="0" id="edit_stock_{{ $loop->index }}">
                                                            @error("variant_prices.{$value}.stock")
                                                            <span class="text-danger small">* {{ $message }}</span>
                                                            @enderror
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                @elseif(!empty($variant_id))
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    Loading variant data...
                                </div>
                                @endif

                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" wire:click="updateProduct">
                            <span wire:loading wire:target="updateProduct">
                                <i class="spinner-border spinner-border-sm"></i> Updating...
                            </span>
                            <span wire:loading.remove wire:target="updateProduct">
                                Update Product
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stock Adjustment Modal -->
        <div wire:ignore.self class="modal fade" id="stockAdjustmentModal" tabindex="-1"
            aria-labelledby="stockAdjustmentModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-white">
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-clipboard-plus text-white me-2"></i> Stock Adjustment
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        @if($adjustmentProductId)
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="card-title mb-0">
                                    <i class="bi bi-box me-2"></i> Product Information
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>Product:</strong> {{ $adjustmentProductName }}
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Available Stock:</strong>
                                        <span class="badge bg-success">{{ $adjustmentAvailableStock }}</span>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Damage Stock:</strong>
                                        <span class="badge bg-danger">{{ $adjustmentDamageStock }}</span>
                                    </div>
                                </div>

                                @if($adjustmentPricingMode === 'variant' && !empty($adjustmentVariantValues))
                                <div class="row mt-3 pt-3 border-top">
                                    <div class="col-md-12">
                                        <label class="form-label fw-bold text-primary mb-1">
                                            <i class="bi bi-funnel-fill me-1"></i>Select Variant to Adjust:
                                        </label>
                                        <select class="form-select border-primary" wire:model.live="adjustmentSelectedVariant">
                                            <option value="">-- Select Variant --</option>
                                            @foreach($adjustmentVariantValues as $variant)
                                                <option value="{{ $variant['variant_value'] }}">{{ $variant['variant_value'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header bg-light">
                                <h6 class="card-title mb-0">
                                    <i class="bi bi-gear me-2"></i> Adjustment Details
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info border-0 mb-1">
                                    <i class="bi bi-info-circle me-2"></i>
                                    <strong>Note:</strong>
                                    <ul class="mb-0 mt-2">
                                        <li><strong>Add Damage:</strong> Increases damage stock and decreases available stock from batches (FIFO)</li>
                                        <li><strong>Adjust Available:</strong> Increases available stock and adds to oldest batch</li>
                                    </ul>
                                </div>

                                <div class="row">
                                    {{-- Damage Stock Input --}}
                                    <div class="col-md-6">
                                        <div class="mb-1">
                                            <label for="damageQuantity" class="form-label fw-semibold">
                                                <i class="bi bi-exclamation-triangle text-danger me-1"></i> Damage Quantity:
                                            </label>
                                            <input type="number" class="form-control" id="damageQuantity"
                                                wire:model="damageQuantity" min="1"
                                                placeholder="Enter damage quantity">
                                            @error('damageQuantity')
                                            <span class="text-danger small">* {{ $message }}</span>
                                            @enderror
                                        </div>
                                        <button type="button" class="btn btn-danger w-100" wire:click="addDamageStock">
                                            <span wire:loading.remove wire:target="addDamageStock">
                                                <i class="bi bi-exclamation-triangle me-2"></i>Add Damage Stock
                                            </span>
                                            <span wire:loading wire:target="addDamageStock">
                                                <i class="spinner-border spinner-border-sm me-2"></i>Processing...
                                            </span>
                                        </button>
                                        <small class="text-muted d-block mt-2">
                                            Deducts from available stock using FIFO and updates prices
                                        </small>
                                    </div>

                                    {{-- Available Stock Input --}}
                                    <div class="col-md-6">
                                        <div class="mb-1">
                                            <label for="availableQuantity" class="form-label fw-semibold">
                                                <i class="bi bi-box-seam text-success me-1"></i> Available Quantity:
                                            </label>
                                            <input type="number" class="form-control" id="availableQuantity"
                                                wire:model="availableQuantity" min="1"
                                                placeholder="Enter quantity to add">
                                            @error('availableQuantity')
                                            <span class="text-danger small">* {{ $message }}</span>
                                            @enderror
                                        </div>
                                        <button type="button" class="btn btn-success w-100" wire:click="adjustAvailableStock">
                                            <span wire:loading.remove wire:target="adjustAvailableStock">
                                                <i class="bi bi-plus-circle me-2"></i>Adjust Available Stock
                                            </span>
                                            <span wire:loading wire:target="adjustAvailableStock">
                                                <i class="spinner-border spinner-border-sm me-2"></i>Processing...
                                            </span>
                                        </button>
                                        <small class="text-muted d-block mt-2">
                                            Increases available stock and adds to batch
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ✅ Product History Modal -->
        <div class="modal fade" id="productHistoryModal" tabindex="-1"
            aria-labelledby="productHistoryModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content border-0 shadow-lg">

                    <!-- Header -->
                    <div class="modal-header border-0 text-white"
                        style="background: linear-gradient(135deg, #000000 0%, #000000 100%);">
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-clock-history me-2"></i>
                            Product History - {{ $historyProductName ?? 'Loading...' }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <!-- Body -->
                    <div class="modal-body p-0">
                        @if($historyProductId)

                        <!-- Filters Bar (Customer Type & Variant) -->
                        <div class="bg-primary bg-opacity-10 border-bottom px-3 py-2">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <!-- Customer Type Filter -->
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-funnel-fill text-primary"></i>
                                        <label class="fw-bold text-dark mb-0">Customer Type:</label>
                                        <select class="form-select form-select-sm border-primary" style="min-width: 140px;"
                                            wire:model.live="historyCustomerTypeFilter">
                                            <option value="">All</option>
                                            <option value="retail">Retail</option>
                                            <option value="wholesale">Wholesale</option>
                                            <option value="distributor">Distributor</option>
                                        </select>
                                    </div>

                                    <!-- Variant Filter Dropdown -->
                                    @if($historyHasVariants && count($historyVariantValues ?? []) > 0)
                                    <div class="d-flex align-items-center gap-2">
                                        <label class="fw-bold text-dark mb-0">{{ $historyVariantName ?: 'Variant' }}:</label>
                                        <select class="form-select form-select-sm border-primary" style="min-width: 160px; max-width: 250px;"
                                            wire:model.live="historyVariantFilter">
                                            <option value="">-- All Variants ({{ count($historyVariantValues) }}) --</option>
                                            @foreach($historyVariantValues as $val)
                                                <option value="{{ $val }}">{{ $val }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @endif
                                </div>

                                @if(($historyVariantFilter ?? '') !== '' || ($historyCustomerTypeFilter ?? '') !== '')
                                <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="$set('historyVariantFilter', ''); $set('historyCustomerTypeFilter', '');">
                                    <i class="bi bi-x-circle me-1"></i> Clear Filters
                                </button>
                                @endif
                            </div>
                        </div>

                        <!-- Tabs -->
                        <div class="border-bottom bg-light">
                            @php
                                $vf = $historyVariantFilter ?? '';
                                $cf = $historyCustomerTypeFilter ?? '';

                                $sColl = collect($salesHistory ?? []);
                                if ($vf !== '') { $sColl = $sColl->where('variant_value', $vf); }
                                if ($cf !== '') { $sColl = $sColl->filter(fn($i) => strtolower($i['customer_type'] ?? '') === strtolower($cf)); }
                                $salesCount = $sColl->count();

                                $pColl = collect($purchasesHistory ?? []);
                                if ($vf !== '') { $pColl = $pColl->where('variant_value', $vf); }
                                if ($cf !== '') { $pColl = collect([]); }
                                $purchasesCount = $pColl->count();

                                $rColl = collect($returnsHistory ?? []);
                                if ($vf !== '') { $rColl = $rColl->where('variant_value', $vf); }
                                if ($cf !== '') { $rColl = $rColl->filter(fn($i) => strtolower($i['customer_type'] ?? '') === strtolower($cf)); }
                                $returnsCount = $rColl->count();

                                $qColl = collect($quotationsHistory ?? []);
                                if ($vf !== '') { $qColl = $qColl->where('variant_value', $vf); }
                                if ($cf !== '') { $qColl = $qColl->filter(fn($i) => strtolower($i['customer_type'] ?? '') === strtolower($cf)); }
                                $quotationsCount = $qColl->count();
                            @endphp
                            <div class="d-flex flex-wrap justify-content-between align-items-center px-3">
                                <ul class="nav nav-tabs border-0" role="tablist">
                                    <li class="nav-item">
                                        <button class="nav-link {{ $historyTab === 'sales' ? 'active' : '' }}"
                                            wire:click="switchHistoryTab('sales')" type="button">
                                            Sales <span class="badge bg-primary ms-1">{{ $salesCount }}</span>
                                        </button>
                                    </li>
                                    <li class="nav-item">
                                        <button class="nav-link {{ $historyTab === 'purchases' ? 'active' : '' }}"
                                            wire:click="switchHistoryTab('purchases')" type="button">
                                            Purchases <span class="badge bg-success ms-1">{{ $purchasesCount }}</span>
                                        </button>
                                    </li>
                                    <li class="nav-item">
                                        <button class="nav-link {{ $historyTab === 'returns' ? 'active' : '' }}"
                                            wire:click="switchHistoryTab('returns')" type="button">
                                            Returns <span class="badge bg-warning ms-1">{{ $returnsCount }}</span>
                                        </button>
                                    </li>
                                    <li class="nav-item">
                                        <button class="nav-link {{ $historyTab === 'quotations' ? 'active' : '' }}"
                                            wire:click="switchHistoryTab('quotations')" type="button">
                                            Quotations <span class="badge bg-info ms-1">{{ $quotationsCount }}</span>
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Tab Content -->
                        <div class="tab-content p-4" wire:key="tab-{{ $historyTab }}-{{ $historyVariantFilter }}-{{ $historyCustomerTypeFilter }}" style="max-height: 500px; overflow-y: auto;">
                            @if($historyTab === 'sales')
                            @include('livewire.admin.partials.sales-history')
                            @elseif($historyTab === 'purchases')
                            @include('livewire.admin.partials.purchases-history')
                            @elseif($historyTab === 'returns')
                            @include('livewire.admin.partials.returns-history')
                            @elseif($historyTab === 'quotations')
                            @include('livewire.admin.partials.quotations-history')
                            @endif
                        </div>

                        @else
                        <div class="text-center py-5">
                            <i class="bi bi-exclamation-circle text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-3 fs-5">Product not found or not loaded.</p>
                        </div>
                        @endif
                    </div>

                    <!-- Footer -->
                    <div class="modal-footer border-0 bg-light">
                        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-2"></i>Close
                        </button>
                    </div>
                </div>
            </div>
        </div>



    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.content-tab');
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    tabs.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    document.querySelectorAll('.tab-content').forEach(content => {
                        content.classList.remove('active');
                    });
                    const tabId = this.getAttribute('data-tab');
                    const targetElement = document.getElementById(tabId);
                    if (targetElement) {
                        targetElement.classList.add('active');
                    }
                });
            });

            Livewire.on('refreshPage', () => {
                setTimeout(() => {
                    window.location.reload();
                }, 1500); // Refresh after 1.5 seconds to show success message
            });

            // Close modals when Livewire operations complete
            Livewire.on('closeModal', (modalId) => {
                const modal = bootstrap.Modal.getInstance(document.getElementById(modalId));
                if (modal) {
                    modal.hide();
                }
            });

            // Real-time validation for stock adjustment
            Livewire.on('adjustmentValidation', (data) => {
                if (data.error) {
                    Swal.fire('Error!', data.error, 'error');
                }
            });

            // Handle history tab switching - with console logging
            Livewire.on('historyTabSwitched', (data) => {
                console.log('✅ Tab switched to:', data);

                // Log the current state
                const modal = document.getElementById('productHistoryModal');
                if (modal) {
                    const activeTabs = modal.querySelectorAll('.tab-pane.show.active');
                    console.log('📊 Active tab panes:', activeTabs.length);
                    activeTabs.forEach(tab => {
                        console.log('  - Tab ID:', tab.id);
                    });

                    const allTabs = modal.querySelectorAll('.tab-pane');
                    console.log('📋 Total tab panes:', allTabs.length);
                }
            });

            // Show history modal when event is dispatched
            Livewire.on('show-product-history-modal', () => {
                console.log('📂 Opening product history modal...');
                setTimeout(() => {
                    const historyModalEl = document.getElementById('productHistoryModal');
                    if (historyModalEl) {
                        // Check if modal already has an instance
                        let historyModal = bootstrap.Modal.getInstance(historyModalEl);
                        if (!historyModal) {
                            historyModal = new bootstrap.Modal(historyModalEl, {
                                backdrop: 'static',
                                keyboard: false
                            });
                        }
                        historyModal.show();
                        console.log('✅ Modal opened successfully');
                    } else {
                        console.error('❌ Product History Modal element not found!');
                    }
                }, 100); // Small delay to ensure Livewire DOM update is complete
            });

            // Error handler for Livewire
            Livewire.on('swal:modal', (data) => {
                console.error('❌ Livewire Error:', data);
                if (data && data.length > 0) {
                    const params = data[0];
                    Swal.fire({
                        icon: params.type || 'error',
                        title: params.title || 'Error',
                        text: params.text || 'An error occurred',
                    });
                }
            });

            // Log when modal is actually shown
            const historyModal = document.getElementById('productHistoryModal');
            if (historyModal) {
                historyModal.addEventListener('shown.bs.modal', function() {
                    console.log('📊 Product History Modal is now visible');
                });

                historyModal.addEventListener('hidden.bs.modal', function() {
                    console.log('🔒 Product History Modal closed');
                });
            }

            // Global error handler
            window.addEventListener('error', function(event) {
                console.error('🚨 Global Error:', event.error);
            });

            console.log('✅ Product History scripts loaded successfully');
        });
    </script>
    @endpush
</div>