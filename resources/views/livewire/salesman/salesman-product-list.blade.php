<div class="container-fluid py-3">
    <style>
        /* Mobile Responsiveness Tweaks */
        @media (max-width: 767.98px) {
            .container-fluid { padding-left: 10px !important; padding-right: 10px !important; padding-top: 10px !important; }
            .card-body { padding: 12px !important; }
            h3.fw-bold { font-size: 1.2rem !important; }
            .mb-4 { margin-bottom: 1rem !important; }
            .g-3 { --bs-gutter-y: 0.75rem; --bs-gutter-x: 0.75rem; }
        }
    </style>
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-2">
                <i class="bi bi-box-seam text-primary me-2"></i> Product Catalog
            </h3>
            <p class="text-muted mb-0">View available products and stock levels (Distributor Prices)</p>
        </div>
        <a href="{{ route('salesman.dashboard') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i> Back to Dashboard
        </a>
    </div>

    {{-- Filters --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Search by name, code, or model...">
                    </div>
                </div>
                <div class="col-md-4">
                    <select wire:model.live="categoryFilter" class="form-select">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->category_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Products Grid --}}
    <div class="row g-3">
        @forelse($products as $product)
        @php
            $imageUrl = $product['image'] ? asset('images/product.jpg') : null;
        @endphp
        
        {{-- Product Card - 5 per row --}}
        <div class="col-6 col-md-4 col-lg-3 col-xl-2-4">
            <div class="card border-0 shadow-sm h-100 product-card" style="transition: all 0.3s ease;">
                {{-- Stock Badge --}}
                <div class="position-absolute top-0 start-0 m-2" style="z-index: 10;">
                    @if($product['stock'] <= 0)
                        <span class="badge bg-danger text-white" style="font-size: 9px;">Out of Stock</span>
                    @elseif($product['stock'] <= 5)
                        <span class="badge bg-warning text-dark" style="font-size: 9px;">Low Stock</span>
                    @else
                        <span class="badge bg-success text-white" style="font-size: 9px;">In Stock</span>
                    @endif
                    @if($product['variant_value'])
                        <span class="badge bg-primary text-white ms-1" style="font-size: 9px;">Variant</span>
                    @endif
                </div>

                {{-- Product Image --}}
                <div style="height: 180px; overflow: hidden; background-color: #f8f9fa;" class="position-relative">
                    @if($imageUrl)
                        <img src="{{ $imageUrl }}" class="w-100 h-100" alt="{{ $product['name'] }}" style="object-fit: cover; object-position: center;">
                    @else
                        <div class="w-100 h-100 d-flex align-items-center justify-content-center">
                            <i class="bi bi-box text-muted" style="font-size: 3rem;"></i>
                        </div>
                    @endif
                </div>
                
                {{-- Product Info --}}
                <div class="card-body pb-2 bg-white">
                    <p class="mb-1" style="font-size: 9px; color: #94a3b8; font-family: monospace;">{{ $product['code'] }}</p>
                    <h6 class="card-title fw-bold mb-2" style="font-size: 11px; line-height: 1.4; color: #1e293b;">{{ $product['name'] }}</h6>
                    
                    <div class="d-flex justify-content-between align-items-end">
                        <div>
                            <span class="d-block fw-black" style="color: #e67e22; font-size: 18px; line-height: 1;">Rs. {{ number_format($product['distributor_price'], 0) }}</span>
                            <span style="font-size: 12px; color: #64748b; font-weight: bold;" class="mt-1 d-block">
                                <span class="{{ $product['stock'] <= 5 ? 'text-warning' : 'text-success' }}">Avail: {{ $product['stock'] }}</span>
                                @if($product['pending'] > 0)
                                    | <span class="text-primary">Pend: {{ $product['pending'] }}</span>
                                @endif
                            </span>
                        </div>
                        <div class="bg-light p-2 rounded" style="font-size: 11px;">
                            <span class="badge bg-info">{{ $product['category'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="bi bi-inbox fs-1 text-muted"></i>
                    </div>
                    <p class="text-muted mb-0 text-uppercase fw-bold" style="font-size: 12px; letter-spacing: 2px;">No products found</p>
                </div>
            </div>
        </div>
        @endforelse

        {{-- Pagination --}}
        <div class="mt-4">
            {{ $products->links('livewire.custom-pagination') }}
        </div>
    </div>

    <style>
        /* 5 columns per row for large screens */
        @media (min-width: 1200px) {
            .col-xl-2-4 {
                flex: 0 0 auto;
                width: 20%;
            }
        }

        .product-card {
            transition: all 0.3s ease;
        }

        .product-card:hover {
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15) !important;
            transform: translateY(-4px);
        }
    </style>
</div>