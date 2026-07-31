<div class="container-fluid py-4">
    <!-- Back Button -->
    <div class="mb-3">
        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Products
        </a>
    </div>

    <!-- Product History Card -->
    <div class="card border-0 shadow-lg">
        <!-- Header -->
        <div class="card-header border-0 text-white py-3" style="background: linear-gradient(135deg, #000000 0%, #333333 100%);">
            <div class="d-flex flex-wrap justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">
                    <i class="bi bi-clock-history me-2"></i>
                    Product History - {{ $productName }}
                </h5>
                <div class="d-flex flex-wrap align-items-center gap-3 mt-2 mt-md-0">
                    <!-- Customer Type Filter -->
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-white-50">Customer Type:</span>
                        <select class="form-select form-select-sm bg-dark text-white border-secondary" 
                            style="min-width: 150px;"
                            wire:model.live="customerTypeFilter">
                            <option value="">All</option>
                            <option value="retail">Retail</option>
                            <option value="wholesale">Wholesale</option>
                            <option value="distributor">Distributor</option>
                        </select>
                    </div>

                    <!-- Variant Filter -->
                    @if($hasVariants && count($variantValues) > 0)
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-white-50">{{ $variantName ?: 'Variant' }}:</span>
                        <select class="form-select form-select-sm bg-dark text-white border-secondary" 
                            style="min-width: 150px;"
                            wire:model.live="variantFilter">
                            <option value="">All Variants</option>
                            @foreach($variantValues as $val)
                                <option value="{{ $val }}">{{ $val }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    @if($variantFilter !== '' || $customerTypeFilter !== '')
                    <button type="button" class="btn btn-sm btn-outline-light" wire:click="clearFilter" title="Clear Filters">
                        <i class="bi bi-x-circle"></i> Clear Filters
                    </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Filter Info Alert -->
        @if($variantFilter !== '' || $customerTypeFilter !== '')
        <div class="alert alert-info m-3 mb-0 py-2 d-flex align-items-center justify-content-between">
            <div>
                <i class="bi bi-funnel-fill me-2"></i>
                Showing history filtered by:
                @if($customerTypeFilter !== '')
                    <span class="badge bg-primary text-capitalize ms-1">Customer Type: {{ $customerTypeFilter }}</span>
                @endif
                @if($variantFilter !== '')
                    <span class="badge bg-dark text-capitalize ms-1">{{ $variantName }}: {{ $variantFilter }}</span>
                @endif
            </div>
            <button type="button" class="btn btn-sm btn-info text-white" wire:click="clearFilter">
                <i class="bi bi-x me-1"></i>Clear Filters
            </button>
        </div>
        @endif

        <!-- Body -->
        <div class="card-body p-0">
            <!-- Tabs -->
            <div class="border-bottom bg-light">
                <ul class="nav nav-tabs border-0 px-3" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link {{ $activeTab === 'sales' ? 'active' : '' }}"
                            wire:click="setActiveTab('sales')" type="button">
                            <i class="bi bi-cart me-1"></i>
                            Sales <span class="badge bg-primary ms-1">{{ $filteredSales->count() }}</span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link {{ $activeTab === 'purchases' ? 'active' : '' }}"
                            wire:click="setActiveTab('purchases')" type="button">
                            <i class="bi bi-truck me-1"></i>
                            Purchases <span class="badge bg-success ms-1">{{ $filteredPurchases->count() }}</span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link {{ $activeTab === 'returns' ? 'active' : '' }}"
                            wire:click="setActiveTab('returns')" type="button">
                            <i class="bi bi-arrow-return-left me-1"></i>
                            Returns <span class="badge bg-warning text-dark ms-1">{{ $filteredReturns->count() }}</span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link {{ $activeTab === 'quotations' ? 'active' : '' }}"
                            wire:click="setActiveTab('quotations')" type="button">
                            <i class="bi bi-file-text me-1"></i>
                            Quotations <span class="badge bg-info ms-1">{{ $filteredQuotations->count() }}</span>
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Tab Content -->
            <div class="p-4" style="min-height: 400px;">
                <!-- Sales Tab -->
                @if($activeTab === 'sales')
                <div>
                    @if($filteredSales->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered align-middle">
                            <thead class="table-primary">
                                <tr>
                                    <th class="text-center" style="width: 5%;">#</th>
                                    @if($hasVariants)
                                    <th style="width: 10%;">Variant</th>
                                    @endif
                                    <th style="width: 12%;">Invoice No.</th>
                                    <th style="width: 10%;">Date</th>
                                    <th style="width: 15%;">Customer</th>
                                    <th style="width: 10%;">Phone</th>
                                    <th class="text-center" style="width: 8%;">Qty</th>
                                    <th class="text-end" style="width: 10%;">Unit Price</th>
                                    <th class="text-end" style="width: 10%;">Total</th>
                                    <th class="text-center" style="width: 10%;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($filteredSales as $index => $sale)
                                <tr>
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    @if($hasVariants)
                                    <td>
                                        @if($sale['variant_value'])
                                        <span class="badge bg-secondary">{{ $sale['variant_value'] }}</span>
                                        @else
                                        <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    @endif
                                    <td>
                                        <strong class="text-primary">{{ $sale['invoice_number'] ?? 'N/A' }}</strong>
                                        @if(($sale['sale_type'] ?? 'regular') === 'wholesale')
                                            <span class="badge bg-info ms-1">Wholesale</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small>{{ \Carbon\Carbon::parse($sale['sale_date'])->format('d M Y, h:i A') }}</small>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $sale['customer_name'] ?? 'Walk-in' }}</div>
                                        @php $cType = strtolower($sale['customer_type'] ?? 'retail'); @endphp
                                        @if($cType === 'wholesale')
                                            <span class="badge bg-info text-white" style="font-size: 0.7rem;">Wholesale</span>
                                        @elseif($cType === 'distributor')
                                            <span class="badge bg-warning text-dark" style="font-size: 0.7rem;">Distributor</span>
                                        @else
                                            <span class="badge bg-secondary" style="font-size: 0.7rem;">Retail</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $sale['customer_phone'] ?? 'N/A' }}</small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-dark">{{ $sale['quantity'] ?? 0 }}</span>
                                    </td>
                                    <td class="text-end">Rs. {{ number_format($sale['unit_price'] ?? 0, 2) }}</td>
                                    <td class="text-end">
                                        <strong class="text-success">Rs. {{ number_format($sale['total'] ?? 0, 2) }}</strong>
                                    </td>
                                    <td class="text-center">
                                        @php $saleStatus = strtolower($sale['sale_status'] ?? 'completed'); @endphp
                                        @if($saleStatus === 'completed')
                                            <span class="badge bg-success">Completed</span>
                                        @elseif($saleStatus === 'pending')
                                            <span class="badge bg-warning">Pending</span>
                                        @else
                                            <span class="badge bg-secondary">{{ ucfirst($saleStatus) }}</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="{{ $hasVariants ? 6 : 5 }}" class="text-end fw-bold">Total:</td>
                                    <td class="text-center fw-bold">{{ $filteredSales->sum('quantity') }}</td>
                                    <td></td>
                                    <td class="text-end fw-bold text-success">
                                        Rs. {{ number_format($filteredSales->sum('total'), 2) }}
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-5">
                        <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-3 fs-5">No sales history found{{ $variantFilter ? ' for variant "' . $variantFilter . '"' : '' }}.</p>
                    </div>
                    @endif
                </div>
                @endif

                <!-- Purchases Tab -->
                @if($activeTab === 'purchases')
                <div>
                    @if($filteredPurchases->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered align-middle">
                            <thead class="table-success">
                                <tr>
                                    <th class="text-center" style="width: 5%;">#</th>
                                    @if($hasVariants)
                                    <th style="width: 10%;">Variant</th>
                                    @endif
                                    <th style="width: 15%;">Order Code</th>
                                    <th style="width: 12%;">Order Date</th>
                                    <th style="width: 12%;">Received Date</th>
                                    <th style="width: 15%;">Supplier</th>
                                    <th class="text-center" style="width: 8%;">Qty</th>
                                    <th class="text-center" style="width: 8%;">Received</th>
                                    <th class="text-end" style="width: 10%;">Total</th>
                                    <th class="text-center" style="width: 10%;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($filteredPurchases as $index => $purchase)
                                <tr>
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    @if($hasVariants)
                                    <td>
                                        @if($purchase['variant_value'])
                                        <span class="badge bg-secondary">{{ $purchase['variant_value'] }}</span>
                                        @else
                                        <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    @endif
                                    <td><strong class="text-success">{{ $purchase['order_code'] ?? 'N/A' }}</strong></td>
                                    <td><small>{{ \Carbon\Carbon::parse($purchase['order_date'])->format('d M Y') }}</small></td>
                                    <td>
                                        @if($purchase['received_date'] === 'Pending')
                                            <span class="badge bg-warning">Pending</span>
                                        @else
                                            <small>{{ \Carbon\Carbon::parse($purchase['received_date'])->format('d M Y') }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $purchase['supplier_name'] ?? 'N/A' }}</div>
                                        <small class="text-muted">{{ $purchase['supplier_phone'] ?? 'N/A' }}</small>
                                    </td>
                                    <td class="text-center"><span class="badge bg-dark">{{ $purchase['quantity'] ?? 0 }}</span></td>
                                    <td class="text-center"><span class="badge bg-dark">{{ $purchase['received_quantity'] ?? 0 }}</span></td>
                                    <td class="text-end"><strong class="text-success">Rs. {{ number_format($purchase['total'] ?? 0, 2) }}</strong></td>
                                    <td class="text-center">
                                        @php $status = strtolower($purchase['order_status'] ?? 'pending'); @endphp
                                        @if($status === 'completed' || $status === 'received')
                                            <span class="badge bg-success">Received</span>
                                        @elseif($status === 'pending')
                                            <span class="badge bg-warning">Pending</span>
                                        @else
                                            <span class="badge bg-secondary">{{ ucfirst($status) }}</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="{{ $hasVariants ? 7 : 6 }}" class="text-end fw-bold">Total:</td>
                                    <td class="text-center fw-bold">{{ $filteredPurchases->sum('received_quantity') }}</td>
                                    <td class="text-end fw-bold text-success">Rs. {{ number_format($filteredPurchases->sum('total'), 2) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-5">
                        <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-3 fs-5">No purchase history found{{ $variantFilter ? ' for variant "' . $variantFilter . '"' : '' }}.</p>
                    </div>
                    @endif
                </div>
                @endif

                <!-- Returns Tab -->
                @if($activeTab === 'returns')
                <div>
                    @if($filteredReturns->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered align-middle">
                            <thead class="table-warning">
                                <tr>
                                    <th class="text-center" style="width: 5%;">#</th>
                                    @if($hasVariants)
                                    <th style="width: 10%;">Variant</th>
                                    @endif
                                    <th style="width: 15%;">Invoice No.</th>
                                    <th style="width: 15%;">Return Date</th>
                                    <th style="width: 20%;">Customer</th>
                                    <th class="text-center" style="width: 10%;">Qty</th>
                                    <th class="text-end" style="width: 12%;">Amount</th>
                                    <th style="width: 20%;">Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($filteredReturns as $index => $return)
                                <tr>
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    @if($hasVariants)
                                    <td>
                                        @if($return['variant_value'])
                                        <span class="badge bg-secondary">{{ $return['variant_value'] }}</span>
                                        @else
                                        <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    @endif
                                    <td><strong class="text-warning">{{ $return['invoice_number'] ?? 'N/A' }}</strong></td>
                                    <td><small>{{ \Carbon\Carbon::parse($return['return_date'])->format('d M Y, h:i A') }}</small></td>
                                    <td>
                                        <div class="fw-semibold">{{ $return['customer_name'] ?? 'Walk-in' }}</div>
                                        <small class="text-muted">{{ $return['customer_phone'] ?? 'N/A' }}</small>
                                        <div>
                                            @php $cType = strtolower($return['customer_type'] ?? 'retail'); @endphp
                                            @if($cType === 'wholesale')
                                                <span class="badge bg-info text-white" style="font-size: 0.7rem;">Wholesale</span>
                                            @elseif($cType === 'distributor')
                                                <span class="badge bg-warning text-dark" style="font-size: 0.7rem;">Distributor</span>
                                            @else
                                                <span class="badge bg-secondary" style="font-size: 0.7rem;">Retail</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-center"><span class="badge bg-danger">{{ $return['return_quantity'] ?? 0 }}</span></td>
                                    <td class="text-end"><strong class="text-danger">Rs. {{ number_format($return['total_amount'] ?? 0, 2) }}</strong></td>
                                    <td><small class="text-muted">{{ $return['notes'] ?? 'No notes' }}</small></td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="{{ $hasVariants ? 5 : 4 }}" class="text-end fw-bold">Total:</td>
                                    <td class="text-center fw-bold">{{ $filteredReturns->sum('return_quantity') }}</td>
                                    <td class="text-end fw-bold text-danger">Rs. {{ number_format($filteredReturns->sum('total_amount'), 2) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-5">
                        <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-3 fs-5">No return history found{{ $variantFilter ? ' for variant "' . $variantFilter . '"' : '' }}.</p>
                    </div>
                    @endif
                </div>
                @endif

                <!-- Quotations Tab -->
                @if($activeTab === 'quotations')
                <div>
                    @if($filteredQuotations->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered align-middle">
                            <thead class="table-info">
                                <tr>
                                    <th class="text-center" style="width: 5%;">#</th>
                                    @if($hasVariants)
                                    <th style="width: 10%;">Variant</th>
                                    @endif
                                    <th style="width: 12%;">Quotation No.</th>
                                    <th style="width: 12%;">Date</th>
                                    <th style="width: 10%;">Valid Until</th>
                                    <th style="width: 15%;">Customer</th>
                                    <th class="text-center" style="width: 8%;">Qty</th>
                                    <th class="text-end" style="width: 10%;">Total</th>
                                    <th class="text-center" style="width: 10%;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($filteredQuotations as $index => $quotation)
                                <tr>
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    @if($hasVariants)
                                    <td>
                                        @if($quotation['variant_value'])
                                        <span class="badge bg-secondary">{{ $quotation['variant_value'] }}</span>
                                        @else
                                        <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    @endif
                                    <td><strong class="text-info">{{ $quotation['quotation_number'] ?? 'N/A' }}</strong></td>
                                    <td><small>{{ \Carbon\Carbon::parse($quotation['quotation_date'])->format('d M Y') }}</small></td>
                                    <td>
                                        <small>{{ \Carbon\Carbon::parse($quotation['valid_until'])->format('d M Y') }}</small>
                                        @if(\Carbon\Carbon::parse($quotation['valid_until'])->isPast())
                                            <div><span class="badge bg-danger badge-sm">Expired</span></div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $quotation['customer_name'] ?? 'N/A' }}</div>
                                        <small class="text-muted">{{ $quotation['customer_phone'] ?? 'N/A' }}</small>
                                        <div>
                                            @php $cType = strtolower($quotation['customer_type'] ?? 'retail'); @endphp
                                            @if($cType === 'wholesale')
                                                <span class="badge bg-info text-white" style="font-size: 0.7rem;">Wholesale</span>
                                            @elseif($cType === 'distributor')
                                                <span class="badge bg-warning text-dark" style="font-size: 0.7rem;">Distributor</span>
                                            @else
                                                <span class="badge bg-secondary" style="font-size: 0.7rem;">Retail</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-center"><span class="badge bg-dark">{{ $quotation['quantity'] ?? 0 }}</span></td>
                                    <td class="text-end"><strong class="text-success">Rs. {{ number_format($quotation['total'] ?? 0, 2) }}</strong></td>
                                    <td class="text-center">
                                        @php $status = strtolower($quotation['status'] ?? 'pending'); @endphp
                                        @if($status === 'accepted' || $status === 'approved')
                                            <span class="badge bg-success">Accepted</span>
                                        @elseif($status === 'pending')
                                            <span class="badge bg-warning">Pending</span>
                                        @elseif($status === 'rejected' || $status === 'declined')
                                            <span class="badge bg-danger">Rejected</span>
                                        @else
                                            <span class="badge bg-info">{{ ucfirst($status) }}</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="{{ $hasVariants ? 6 : 5 }}" class="text-end fw-bold">Total:</td>
                                    <td class="text-center fw-bold">{{ $filteredQuotations->sum('quantity') }}</td>
                                    <td class="text-end fw-bold text-success">Rs. {{ number_format($filteredQuotations->sum('total'), 2) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-5">
                        <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-3 fs-5">No quotation history found{{ $variantFilter ? ' for variant "' . $variantFilter . '"' : '' }}.</p>
                    </div>
                    @endif
                </div>
                @endif
            </div>
        </div>

        <!-- Footer -->
        <div class="card-footer border-0 bg-light d-flex justify-content-between">
            <a href="{{ route('admin.Productes') }}" class="btn btn-secondary px-4">
                <i class="bi bi-arrow-left me-2"></i>Back to Products
            </a>
            <div class="text-muted">
                Last updated: {{ now()->format('d M Y, h:i A') }}
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .nav-tabs .nav-link {
        border: none;
        color: #6c757d;
        font-weight: 500;
        padding: 1rem 1.5rem;
        border-bottom: 3px solid transparent;
        transition: all 0.3s ease;
    }
    .nav-tabs .nav-link.active {
        color: #f58320;
        border-bottom-color: #f58320;
        background-color: transparent;
    }
    .nav-tabs .nav-link:hover:not(.active) {
        color: #f58320;
        border-bottom-color: #dee2e6;
    }
</style>
@endpush
