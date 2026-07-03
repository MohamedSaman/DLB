<div>
    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <h5 class="fw-bold mb-1">Product Stock Report</h5>
            <p class="text-muted mb-0 small">
                <i class="bi bi-calendar-event me-1"></i>As of {{ now()->format('F d, Y h:i A') }}
            </p>
        </div>
        <!-- Per Page Selector -->
        <div class="d-flex align-items-center gap-2">
            <label class="form-label fw-bold text-secondary mb-0">
                <i class="bi bi-list-ol me-1"></i>Per Page:
            </label>
            <select class="form-select form-select-sm" style="width: 80px;" wire:model.live="perPage">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="500">500</option>
                <option value="1000">1000</option>
            </select>
        </div>
    </div>

    <!-- Stock Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 bg-primary bg-opacity-10">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1 small">Total Products</h6>
                            <h3 class="mb-0 fw-bold">{{ $reportStats['total_products'] ?? 0 }}</h3>
                        </div>
                        <i class="bi bi-box-seam fs-1 text-primary opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 bg-success bg-opacity-10">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1 small">Total Stock</h6>
                            <h3 class="mb-0 fw-bold text-success">{{ number_format($reportStats['total_stock'] ?? 0) }}</h3>
                        </div>
                        <i class="bi bi-boxes fs-1 text-success opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 bg-info bg-opacity-10">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1 small">Available Stock</h6>
                            <h3 class="mb-0 fw-bold text-info">{{ number_format($reportStats['available_stock'] ?? 0) }}</h3>
                        </div>
                        <i class="bi bi-check-circle fs-1 text-info opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 bg-warning bg-opacity-10">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1 small">Low Stock Items</h6>
                            <h3 class="mb-0 fw-bold text-warning">{{ $reportStats['low_stock'] ?? 0 }}</h3>
                        </div>
                        <i class="bi bi-exclamation-triangle fs-1 text-warning opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($reportData->isEmpty())
    <div class="text-center py-5">
        <i class="bi bi-inbox display-4 text-muted"></i>
        <p class="mt-3 text-muted">No stock data available</p>
    </div>
    @else
    <!-- Low Stock Alert -->
    @if($reportStats['low_stock'] > 0)
    <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
        <div>
            <strong>Low Stock Alert!</strong> {{ $reportStats['low_stock'] }} product(s) are running low on stock.
        </div>
    </div>
    @endif

    <!-- Stock Table -->
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Product</th>
                    <th>Brand</th>
                    <th>Category</th>
                    <th>Total Stock</th>
                    <th>Available</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData as $stock)
                @php
                    $statusColor = $stock->available_stock > 20 ? 'success' : ($stock->available_stock > 5 ? 'warning' : 'danger');
                @endphp
                <tr>
                    <td>
                        <div class="fw-semibold">
                            {{ $stock->product->name ?? '-' }}
                            @if($stock->variant_value)
                                <span class="badge bg-secondary ms-1">{{ $stock->variant_value }}</span>
                            @endif
                        </div>
                        <small class="text-muted">{{ $stock->product->model ?? '-' }}</small>
                    </td>
                    <td>{{ $stock->product->brand->brand_name ?? '-' }}</td>
                    <td>{{ $stock->product->category->category_name ?? '-' }}</td>
                    <td class="fw-bold">{{ number_format($stock->total_stock) }}</td>
                    <td>
                        <span class="badge bg-{{ $statusColor }} bg-opacity-10 text-{{ $statusColor }}">
                            {{ number_format($stock->available_stock) }}
                        </span>
                    </td>
                    <td>
                        @if($stock->available_stock == 0)
                            <span class="badge bg-danger">Out of Stock</span>
                        @elseif($stock->available_stock < 10)
                            <span class="badge bg-warning">Low Stock</span>
                        @else
                            <span class="badge bg-success">In Stock</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($reportData->hasPages())
    <div class="card-footer bg-light">
        <div class="d-flex justify-content-center">
            {{ $reportData->links('livewire.custom-pagination') }}
        </div>
    </div>
    @endif
    @endif
</div>
