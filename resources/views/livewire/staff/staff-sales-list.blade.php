<div class="container-fluid py-3">
    {{-- PAGE HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-2">
                <i class="bi bi-cash-stack text-success me-2"></i> My Sales Management
            </h3>
            <p class="text-muted mb-0">View and manage your sales</p>
        </div>
        <div>
            <a href="{{ route('staff.sales-system') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i> New Sale
            </a>
        </div>
    </div>

    {{-- STATISTICS CARDS --}}
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-start border-primary border-4 shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-primary text-uppercase mb-1">Total Sales</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">{{ $stats['total_sales'] }}</div>
                        </div>
                        <div class="col-auto"><i class="bi bi-cart-check fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-start border-success border-4 shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-success text-uppercase mb-1">Total Revenue</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">Rs.{{ number_format($stats['total_amount'], 2) }}</div>
                        </div>
                        <div class="col-auto"><i class="bi bi-currency-dollar fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-start border-warning border-4 shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-warning text-uppercase mb-1">Pending Payments</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">Rs.{{ number_format($stats['pending_payments'], 2) }}</div>
                        </div>
                        <div class="col-auto"><i class="bi bi-clock-history fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-start border-info border-4 shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-info text-uppercase mb-1">Today's Sales</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">{{ $stats['today_sales'] }}</div>
                        </div>
                        <div class="col-auto"><i class="bi bi-calendar-day fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- FILTERS --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Search</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control"
                            placeholder="Search by invoice, customer name or phone..."
                            wire:model.live="search">
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Payment Status</label>
                    <select class="form-select" wire:model.live="paymentStatusFilter">
                        <option value="all">All Status</option>
                        <option value="paid">Paid</option>
                        <option value="partial">Partial</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Date Filter</label>
                    <input type="date" class="form-control" wire:model.live="dateFilter">
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold">Per Page</label>
                    <select class="form-select" wire:model.live="perPage">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- ==================== SALES TABLE ==================== --}}
    <div class="card shadow">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="fw-bold mb-0">
                    <i class="bi bi-list-ul text-primary me-2"></i> My Sales List
                </h5>
                <span class="badge bg-primary">{{ $sales->total() }} records</span>
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
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Invoice</th>
                            <th>Customer</th>
                            <th class="text-center">Date</th>
                            <th class="text-center">Amount</th>
                            <th class="text-center">Payment Status</th>
                            <th class="text-center">Sale Type</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sales as $sale)
                        <tr wire:key="sale-{{ $sale->id }}" style="cursor:pointer">
                            <td class="ps-4" wire:click="viewSale({{ $sale->id }})">
                                <div class="fw-bold text-primary">{{ $sale->invoice_number }}</div>
                                <small class="text-muted">#{{ $sale->sale_id }}</small>
                            </td>
                            <td wire:click="viewSale({{ $sale->id }})">
                                @if($sale->customer)
                                <div class="fw-medium">{{ $sale->customer->name }}</div>
                                <small class="text-muted">{{ $sale->customer->phone }}</small>
                                @else
                                <span class="text-muted">Walk-in Customer</span>
                                @endif
                            </td>
                            <td class="text-center" wire:click="viewSale({{ $sale->id }})">{{ $sale->created_at->format('M d, Y') }}</td>
                            <td class="text-center fw-bold" wire:click="viewSale({{ $sale->id }})">Rs.{{ number_format($sale->total_amount, 2) }}</td>
                            <td class="text-center" wire:click="viewSale({{ $sale->id }})">
                                <span class="badge bg-{{ $sale->payment_status == 'paid' ? 'success' : ($sale->payment_status == 'partial' ? 'warning' : 'danger') }}">
                                    {{ ucfirst($sale->payment_status) }}
                                </span>
                            </td>
                            <td class="text-center" wire:click="viewSale({{ $sale->id }})">
                                <span class="badge bg-info">{{ strtoupper($sale->sale_type ?? 'Staff') }}</span>
                            </td>
                            <td class="text-end pe-4">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                        type="button"
                                        data-bs-toggle="dropdown"
                                        aria-expanded="false">
                                        <i class="bi bi-gear-fill"></i> Actions
                                    </button>

                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <!-- View Sale -->
                                        <li>
                                            <button class="dropdown-item" wire:click="viewSale({{ $sale->id }})">
                                                <i class="bi bi-eye text-primary me-2"></i> View Details
                                            </button>
                                        </li>
                                        <!-- Download Invoice -->
                                        <li>
                                            <button class="dropdown-item"
                                                wire:click="downloadInvoice({{ $sale->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="downloadInvoice({{ $sale->id }})">

                                                <span wire:loading wire:target="downloadInvoice({{ $sale->id }})">
                                                    <i class="spinner-border spinner-border-sm me-2"></i>
                                                    Loading...
                                                </span>
                                                <span wire:loading.remove wire:target="downloadInvoice({{ $sale->id }})">
                                                    <i class="bi bi-download text-success me-2"></i>
                                                    Download Invoice
                                                </span>
                                            </button>
                                        </li>
                                        <!-- Print Invoice -->
                                        <li>
                                            <button class="dropdown-item"
                                                wire:click="printInvoice({{ $sale->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="printInvoice({{ $sale->id }})">

                                                <span wire:loading wire:target="printInvoice({{ $sale->id }})">
                                                    <i class="spinner-border spinner-border-sm me-2"></i>
                                                    Loading...
                                                </span>
                                                <span wire:loading.remove wire:target="printInvoice({{ $sale->id }})">
                                                    <i class="bi bi-printer text-info me-2"></i>
                                                    Print
                                                </span>
                                            </button>
                                        </li>

                                        <!-- Delete Sale
                                        <li>
                                            <button class="dropdown-item"
                                                wire:click="deleteSale({{ $sale->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="deleteSale({{ $sale->id }})">

                                                <span wire:loading wire:target="deleteSale({{ $sale->id }})">
                                                    <i class="spinner-border spinner-border-sm me-2"></i>
                                                    Loading...
                                                </span>
                                                <span wire:loading.remove wire:target="deleteSale({{ $sale->id }})">
                                                    <i class="bi bi-trash text-danger me-2"></i>
                                                    Delete
                                                </span>
                                            </button>
                                        </li> -->
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="bi bi-cart-x display-4 d-block mb-3"></i>
                                <p class="mb-0">No sales found.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if ($sales->hasPages())
            <div class="card-footer">
                <div class="d-flex justify-content-center">
                    {{ $sales->links() }}
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- ==================== VIEW SALE MODAL (Admin Style) ==================== --}}
    @if($showViewModal && $selectedSale)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" id="printableInvoice">
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

                        {{-- Right: Invoice --}}
                        <div class="text-end" style="flex: 0 0 150px;">
                            <h6 class="mb-0 text-muted">INVOICE</h6>
                        </div>
                    </div>
                    <hr class="my-2" style="border-top: 2px solid #000;">
                </div>

                <div class="modal-body">
                    {{-- ==================== CUSTOMER + INVOICE INFO ==================== --}}
                    <div class="row mb-3">
                        <div class="col-6">
                            <strong>Customer :</strong><br>
                            {{ $selectedSale->customer->name ?? 'Walk-in Customer' }}<br>
                            {{ $selectedSale->customer->address ?? '' }}<br>
                            Tel: {{ $selectedSale->customer->phone ?? '' }}
                        </div>
                        <div class="col-6 text-end">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td><strong>Invoice #</strong></td>
                                    <td>{{ $selectedSale->invoice_number }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Sale ID</strong></td>
                                    <td>{{ $selectedSale->sale_id }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Date</strong></td>
                                    <td>{{ $selectedSale->created_at->format('M d, Y h:i A') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Sale Type</strong></td>
                                    <td><span class="badge bg-info">{{ strtoupper($selectedSale->sale_type ?? 'Staff') }}</span></td>
                                </tr>
                                <tr>
                                    <td><strong>Created By</strong></td>
                                    <td>{{ $selectedSale->user->name ?? 'System' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    {{-- ==================== ITEMS TABLE ==================== --}}
                    <div class="table-responsive mb-3">
                        <table class="table table-bordered table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Product</th>
                                    <th class="text-center">Code</th>
                                    <th class="text-center">Quantity</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Discount</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($selectedSale->items as $i => $item)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>{{ $item->product_name }}</td>
                                    <td class="text-center">{{ $item->product_code }}</td>
                                    <td class="text-center">{{ $item->quantity }}</td>
                                    <td class="text-end">Rs.{{ number_format($item->unit_price, 2) }}</td>
                                    <td class="text-end">Rs.{{ number_format(($item->discount_per_unit ?? 0) * $item->quantity, 2) }}</td>
                                    <td class="text-end">Rs.{{ number_format($item->total, 2) }}</td>
                                </tr>
                                @endforeach
                                @if($selectedSale->items->count() == 0)
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No items found.</td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    {{-- ==================== TOTALS (right-aligned) ==================== --}}
                    <div class="row">
                        <div class="col-7"></div>
                        <div class="col-5">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td><strong>Subtotal</strong></td>
                                    <td class="text-end">Rs.{{ number_format($selectedSale->subtotal ?? $selectedSale->total_amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Discount</strong></td>
                                    <td class="text-end">- Rs.{{ number_format($selectedSale->discount_amount ?? 0, 2) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Grand Total</strong></td>
                                    <td class="text-end fw-bold">Rs.{{ number_format($selectedSale->total_amount, 2) }}</td>
                                </tr>
                                @if($selectedSale->due_amount > 0)
                                <tr>
                                    <td><strong class="text-danger">Due Amount</strong></td>
                                    <td class="text-end fw-bold text-danger">Rs.{{ number_format($selectedSale->due_amount, 2) }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>

                    {{-- ==================== RETURNED ITEMS TABLE ==================== --}}
                    @if(isset($selectedSale->returns) && count($selectedSale->returns) > 0)
                    <h6 class="text-muted mb-3 mt-4">RETURNED ITEMS</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Product</th>
                                    <th class="text-center">Code</th>
                                    <th class="text-center">Return Qty</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $returnAmount = 0; @endphp
                                @foreach($selectedSale->returns as $rIndex => $return)
                                @php $returnAmount += $return->total_amount; @endphp
                                <tr>
                                    <td>{{ $rIndex + 1 }}</td>
                                    <td>{{ $return->product?->name ?? '-' }}</td>
                                    <td class="text-center">{{ $return->product?->code ?? '-' }}</td>
                                    <td class="text-center">{{ $return->return_quantity }}</td>
                                    <td class="text-end">Rs.{{ number_format($return->selling_price, 2) }}</td>
                                    <td class="text-end">Rs.{{ number_format($return->total_amount, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="5" class="text-end"><strong>Return Amount:</strong></td>
                                    <td class="text-end">- Rs.{{ number_format($returnAmount, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    @endif

                    @if($selectedSale->notes)
                    <h6 class="text-muted mb-2">NOTES</h6>
                    <div class="card bg-light">
                        <div class="card-body">
                            <p class="mb-0">{{ $selectedSale->notes }}</p>
                        </div>
                    </div>
                    @endif

                    {{-- Footer Note --}}
                    <div class="invoice-footer mt-4">
                        <div class="row text-center mb-3">
                            <div class="col-4">
                                <p><strong>.............................</strong></p>
                                <p class="mb-2"><strong>Checked By</strong></p>
                            </div>
                            <div class="col-4">
                                <p><strong>.............................</strong></p>
                                <p class="mb-2"><strong>Authorized Officer</strong></p>
                            </div>
                            <div class="col-4">
                                <p><strong>.............................</strong></p>
                                <p class="mb-2"><strong>Customer Stamp</strong></p>
                            </div>
                        </div>
                        <div class="border-top pt-3">
                            <p class="text-center mb-0"><strong>ADDRESS :</strong> Sample address</p>
                            <p class="text-center mb-0"><strong>TEL :</strong> (077) 1234567, <strong>EMAIL :</strong> Sample email</p>
                            <p class="text-center" style="font-size: 11px;"><strong></strong></p>
                        </div>
                    </div>
                </div>

                {{-- ==================== FOOTER BUTTONS ==================== --}}
                <div class="modal-footer bg-light justify-content-between">
                    <button type="button" class="btn btn-secondary" wire:click="closeModal">
                        <i class="bi bi-x-circle me-1"></i> Close
                    </button>
                    <div>
                        <button type="button" class="btn btn-success me-2" wire:click="downloadInvoice({{ $selectedSale->id }})">
                            <i class="bi bi-download me-1"></i> Download PDF
                        </button>
                        <button type="button" class="btn btn-outline-primary" wire:click="printInvoice({{ $selectedSale->id }})" wire:loading.attr="disabled" wire:target="printInvoice({{ $selectedSale->id }})">
                            <span wire:loading wire:target="printInvoice({{ $selectedSale->id }})">
                                <i class="spinner-border spinner-border-sm me-1"></i> Printing...
                            </span>
                            <span wire:loading.remove wire:target="printInvoice({{ $selectedSale->id }})">
                                <i class="bi bi-printer me-1"></i> Print
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- DELETE CONFIRMATION MODAL --}}
    @if($showDeleteModal && $selectedSale)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-exclamation-triangle me-2"></i>Confirm Delete
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeModal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete sale <strong>{{ $selectedSale->invoice_number }}</strong>?</p>
                    <p class="text-danger"><i class="bi bi-info-circle me-1"></i>This action cannot be undone. Stock quantities will be restored.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeModal">Cancel</button>
                    <button type="button" class="btn btn-danger" wire:click="confirmDelete">
                        <i class="bi bi-trash me-1"></i>Delete Sale
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

@push('scripts')
<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('showToast', (event) => {
            const type = event.type || 'info';
            const message = event.message || 'Action completed';
            
            // You can integrate with your toast notification system here
            alert(message);
        });
    });
</script>
@endpush
</div>


