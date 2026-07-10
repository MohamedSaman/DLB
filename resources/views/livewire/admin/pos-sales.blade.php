@php
use App\Models\Sale;
@endphp

<div class="container-fluid py-3">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h3 class="fw-bold text-dark mb-2">
                <i class="bi bi-cash-stack text-success me-2"></i> POS Sales Management
            </h3>
            <p class="text-muted mb-0">View and manage POS sales</p>
        </div>
        <div class="d-flex gap-2">
            <button wire:click="exportCsv"
                    wire:loading.attr="disabled"
                    wire:target="exportCsv"
                    class="btn btn-success">
                <span wire:loading.remove wire:target="exportCsv">
                    <i class="bi bi-file-earmark-arrow-down me-2"></i>Export CSV
                </span>
                <span wire:loading wire:target="exportCsv">
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    Exporting...
                </span>
            </button>
            <a href="{{ route('admin.quotation-system') }}" class="btn btn-outline-primary">
                <i class="bi bi-file-earmark-text me-2"></i> Create Quotation
            </a>
            <a href="{{ route('admin.store-billing') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i> New POS Sale
            </a>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="row mb-5">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-start border-primary border-4 shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-primary text-uppercase mb-1">
                                Total POS Sales
                            </div>
                            <div class="h5 mb-0 fw-bold text-gray-800">{{ $stats['total_sales'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-cart-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-start border-success border-4 shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-success text-uppercase mb-1">
                                Total Revenue
                            </div>
                            <div class="h5 mb-0 fw-bold text-gray-800">Rs.{{ number_format($stats['total_amount'], 2) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-currency-dollar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-start border-warning border-4 shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-warning text-uppercase mb-1">
                                Pending Payments
                            </div>
                            <div class="h5 mb-0 fw-bold text-gray-800">Rs.{{ number_format($stats['pending_payments'], 2) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-clock-history fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-start border-info border-4 shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-info text-uppercase mb-1">
                                Today's Sales
                            </div>
                            <div class="h5 mb-0 fw-bold text-gray-800">{{ $stats['today_sales'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-calendar-day fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Search</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" class="form-control" placeholder="Search by invoice, customer name or phone..."
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
                <div class="col-md-2">
                    <label class="form-label fw-semibold">From Date</label>
                    <input type="date" class="form-control" wire:model.live="dateFrom">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">To Date</label>
                    <input type="date" class="form-control" wire:model.live="dateTo">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold invisible">Actions</label>
                    <button class="btn btn-outline-secondary w-100" wire:click="$set('dateFrom', ''); $set('dateTo', '')">
                        <i class="bi bi-arrow-clockwise me-1"></i> Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Sales Table --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="fw-bold mb-0">
                    <i class="bi bi-list-ul text-primary me-2"></i> POS Sales List
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
        <div class="card-body p-0 overflow-auto">
            <div class="table-responsive ">
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
                            <td class="text-center" wire:click="viewSale({{ $sale->id }})">
                                <div>{{ $sale->created_at->format('M d, Y') }}</div>
                            </td>

                            <td class="text-center" wire:click="viewSale({{ $sale->id }})">
                                <div class="fw-bold">Rs.{{ number_format($sale->total_amount, 2) }}</div>
                                @if($sale->due_amount > 0)
                                <small class="text-danger">Due: Rs.{{ number_format($sale->due_amount, 2) }}</small>
                                @endif
                            </td>
                            <td class="text-center" wire:click="viewSale({{ $sale->id }})">
                                <span class="badge bg-{{ $sale->payment_status == 'paid' ? 'success' : ($sale->payment_status == 'partial' ? 'warning' : 'danger') }}">
                                    {{ ucfirst($sale->payment_status) }}
                                </span>
                            </td>
                            <td class="text-center" wire:click="viewSale({{ $sale->id }})">
                                <span class="badge bg-primary">{{ strtoupper($sale->sale_type) }}</span>
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
                                                wire:click="printSaleModal({{ $sale->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="printSaleModal({{ $sale->id }})">

                                                <span wire:loading wire:target="printSaleModal({{ $sale->id }})">
                                                    <i class="spinner-border spinner-border-sm me-2"></i>
                                                    Loading...
                                                </span>
                                                <span wire:loading.remove wire:target="printSaleModal({{ $sale->id }})">
                                                    <i class="bi bi-printer text-primary me-2"></i>
                                                    Print
                                                </span>
                                            </button>
                                        </li>
                                        <!-- Edit Sale -->
                                        <li>
                                            <button class="dropdown-item"
                                                wire:click="editSaleRedirect({{ $sale->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="editSaleRedirect({{ $sale->id }})">

                                                <span wire:loading wire:target="editSaleRedirect({{ $sale->id }})">
                                                    <i class="spinner-border spinner-border-sm me-2"></i>
                                                    Loading...
                                                </span>
                                                <span wire:loading.remove wire:target="editSaleRedirect({{ $sale->id }})">
                                                    <i class="bi bi-pencil text-primary me-2"></i>
                                                    Edit Sale
                                                </span>
                                            </button>
                                        </li>
                                        <!-- Payment History -->
                                        <li>
                                            <button class="dropdown-item"
                                                wire:click="showPaymentHistory({{ $sale->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="showPaymentHistory({{ $sale->id }})">

                                                <span wire:loading wire:target="showPaymentHistory({{ $sale->id }})">
                                                    <i class="spinner-border spinner-border-sm me-2"></i>
                                                    Loading...
                                                </span>
                                                <span wire:loading.remove wire:target="showPaymentHistory({{ $sale->id }})">
                                                    <i class="bi bi-clock-history text-info me-2"></i>
                                                    Payment History
                                                </span>
                                            </button>
                                        </li>
                                        <!-- Delete Sale -->
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
                                        </li>
                                    </ul>
                                </div>
                            </td>

                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="bi bi-cart-x display-4 d-block mb-2"></i>
                                No POS sales found.
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
                    {{ $sales->links('livewire.custom-pagination') }}
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- View Sale Modal --}}
    <div wire:ignore.self class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">Invoice Preview</h5>
                    <button type="button" class="btn-close" wire:click="closeModals"></button>
                </div>
                <div class="modal-body p-4" style="background: #f8f9fa;">
                    @if($selectedSale)
                    <div id="printableInvoice" class="receipt-container" style="background: white; padding: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-radius: 8px; max-width: 800px; margin: 0 auto;">
                        <style>
                            .receipt-container { width: 100%; max-width: 800px; margin: 0 auto; padding: 20px; }
                            .receipt-header { border-bottom: 3px solid #000; padding-bottom: 12px; margin-bottom: 12px; }
                            .receipt-row { display:flex; align-items:center; justify-content:space-between; }
                            .receipt-logo { flex: 0 0 150px; }
                            .receipt-center { flex: 1; text-align:center; }
                            .receipt-center h2 { margin: 0 0 4px 0; font-size: 2rem; letter-spacing: 2px; }
                            .receipt-right { flex: 0 0 150px; text-align:right; }
                            .mb-0 { margin-bottom: 0; }
                            .mb-1 { margin-bottom: 4px; }
                            table.receipt-table { width:100%; border-collapse: collapse; margin-top: 12px; }
                            table.receipt-table th{border-bottom: 1px solid #000; padding: 8px; text-align: left;}
                            table.receipt-table td { border: 0px solid #000; padding: 2px; text-align: left; }
                            table.receipt-table th { background: none; font-weight: bold; }
                            .text-end { text-align: right; }
                        </style>

                        <!-- Header -->
                        <div class="receipt-header">
                            <div class="receipt-row">
                                <div class="receipt-center">
                                    <h2 class="mb-0">HARDMEN (PVT) LTD</h2>
                                    <p class="mb-0 text-muted" style="color:#666; font-size:12px;">TOOLS WITH POWER</p>
                                    <p style="margin:0; text-align:center;"><strong>421/2, Doolmala, thihariya, Kalagedihena.</strong></p>
                                    <p style="margin:0; text-align:center;"><strong>TEL:</strong> (077) 9752950, <strong>EMAIL:</strong> Hardmenlanka@gmail.com</p>
                                </div>
                            </div>
                        </div>

                        <!-- Invoice / Customer Details -->
                        <div style="display:flex; gap:20px; margin-bottom:12px; justify-content:space-between; align-items:flex-start;">
                            <div style="flex:0 0 45%; text-align:left;">
                                @if($selectedSale->customer)
                                <p style="margin:0; font-size:12px;"><strong>Name:</strong> {{ $selectedSale->customer->name }}</p>
                                <p style="margin:0; font-size:12px;"><strong>Phone:</strong> {{ $selectedSale->customer->phone }}</p>
                                <p style="margin:0; font-size:12px;"><strong>Address:</strong> {{ $selectedSale->customer->address ?? 'N/A' }}</p>
                                @else
                                <p class="text-muted">Walk-in Customer</p>
                                @endif
                            </div>
                            <div style="flex:0 0 45%; text-align:right;">
                                <p style="margin:0; font-size:12px;"><strong>Invoice Number:</strong> {{ $selectedSale->invoice_number }}</p>
                                <p style="margin:0; font-size:12px;"><strong>Date:</strong> {{ $selectedSale->created_at->format('d/m/Y h:i A') }}</p>
                                <p style="margin:0; font-size:12px;"><strong>Payment Status:</strong> 
                                    <span style="color:{{ $selectedSale->payment_status === 'paid' ? '#28a745' : ($selectedSale->payment_status === 'partial' ? '#ffc107' : '#dc3545') }}; font-weight:bold;">
                                        {{ ucfirst($selectedSale->payment_status) }}
                                    </span>
                                </p>
                            </div>
                        </div>

                        <!-- Items -->
                        @php
                            $saleItemsSubtotal = 0;
                            foreach ($selectedSale->items as $saleItem) {
                                $saleItemsSubtotal += (float) ($saleItem->total ?? 0);
                            }

                            $returnItemsTotal = 0;
                            if (isset($selectedSale->returns) && count($selectedSale->returns) > 0) {
                                foreach ($selectedSale->returns as $returnItem) {
                                    $returnItemsTotal += (float) ($returnItem->total_amount ?? 0);
                                }
                            }

                            $discountAmount = (float) ($selectedSale->discount_amount ?? 0);
                            $saleGrandTotal = (float) ($selectedSale->total_amount ?? 0);
                            $netTotalAfterReturns = $saleGrandTotal;
                        @endphp
                        <table class="receipt-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Code</th>
                                    <th>Item</th>
                                    <th style="text-align:center;">Price</th>
                                    <th style="text-align:center;">Qty</th>
                                    <th style="text-align:center;">Discount</th>
                                    <th style="text-align:center;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($selectedSale->items as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $item->product_code ?? '' }}</td>
                                    <td>
                                        {{ $item->product_name }}
                                    </td>
                                    <td class="text-end">Rs.{{ number_format($item->unit_price, 2) }}</td>
                                    <td class="text-end">{{ $item->quantity }}</td>
                                    <td class="text-end">
                                        @php
                                            $itemTotalDiscount = ($item->discount_per_unit ?? 0) * $item->quantity;
                                            $discountPercent = 0;
                                            if($itemTotalDiscount > 0 && $item->unit_price > 0) {
                                                $discountPercent = (($item->discount_per_unit ?? 0) / $item->unit_price) * 100;
                                            }
                                        @endphp
                                        @if($discountPercent > 0)
                                            {{ number_format($discountPercent, 2) }}%
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-end">Rs.{{ number_format($item->total, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="6" class="text-end"><strong>Sales Items Subtotal</strong></td>
                                    <td class="text-end"><strong>Rs.{{ number_format($saleItemsSubtotal, 2) }}</strong></td>
                                </tr>
                            </tfoot>
                        </table>

                        

                        @if(isset($selectedSale->returns) && count($selectedSale->returns) > 0)
                        <!-- Returned Items Section -->
                        <div style="margin-top:20px; padding-top:12px; border-top:1px solid #ddd;">
                            <h4 style="margin:0 0 8px 0; color:#dc3545; font-size:14px; font-weight:bold;">RETURNED ITEMS</h4>
                            <table class="receipt-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Product</th>
                                        <th style="text-align:center;">Qty</th>
                                        <th style="text-align:center;">Price</th>
                                        <th style="text-align:center;">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($selectedSale->returns as $index => $return)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $return->product->name ?? 'N/A' }}</td>
                                        <td class="text-end">{{ $return->return_quantity }}</td>
                                        <td class="text-end">Rs.{{ number_format($return->selling_price, 2) }}</td>
                                        <td class="text-end">Rs.{{ number_format($return->total_amount, 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4" class="text-end"><strong>Returned Items Total</strong></td>
                                        <td class="text-end"><strong>Rs.{{ number_format($returnItemsTotal, 2) }}</strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        @endif

                        <!-- Summary / Payments -->
                        <div style="display:flex; gap:20px; margin-top:25px; border-top:2px solid #000; padding-top:12px;">
                            <div style="flex:1;">
                                <h4 style="margin:0 0 8px 0; color:#666; font-size:14px; font-weight:bold;">PAYMENT INFORMATION</h4>
                                @php
                                    $paidAmount = $selectedSale->total_amount - $selectedSale->due_amount;
                                @endphp
                                <div style="margin-bottom:8px; padding:8px; border-left:3px solid {{ $selectedSale->payment_status === 'paid' ? '#28a745' : ($selectedSale->payment_status === 'partial' ? '#ffc107' : '#dc3545') }}; background:#f8f9fa;">
                                    <p style="margin:0; font-size:11px;"><strong>Paid Amount:</strong> Rs.{{ number_format($paidAmount, 2) }}</p>
                                    <p style="margin:0; font-size:11px;"><strong>Balance Due:</strong> Rs.{{ number_format($selectedSale->due_amount, 2) }}</p>
                                    <p style="margin:0; font-size:11px;"><strong>Status:</strong> {{ ucfirst($selectedSale->payment_status) }}</p>
                                </div>
                            </div>
                            <div style="flex:1;">
                                <div>
                                    <h4 style="margin:0 0 8px 0; border-bottom:1px solid #000; padding-bottom:8px; font-size:14px; font-weight:bold;">ORDER SUMMARY</h4>
                                    <div style="display:flex; justify-content:space-between; margin-bottom:6px; font-size:12px;">
                                        <span>Subtotal:</span>
                                        <span>Rs.{{ number_format($saleItemsSubtotal, 2) }}</span>
                                    </div>
                                    <div style="display:flex; justify-content:space-between; margin-bottom:6px; font-size:12px;">
                                        <span>Total Discount:</span>
                                        <span>
                                            @if($selectedSale->discount_type === 'percentage')
                                                {{ number_format($discountAmount, 2) }}%
                                            @else
                                                Rs.{{ number_format($discountAmount, 2) }}
                                            @endif
                                        </span>
                                    </div>
                                    @if($returnItemsTotal > 0)
                                    <div style="display:flex; justify-content:space-between; margin-bottom:6px; font-size:12px; color:#dc3545;">
                                        <span>Returned Items Total:</span>
                                        <span>- Rs.{{ number_format($returnItemsTotal, 2) }}</span>
                                    </div>
                                    @endif
                                    <hr style="margin: 8px  0;">
                                    <div style="display:flex; justify-content:space-between; font-size:13px;">
                                        <strong>{{ $returnItemsTotal > 0 ? 'Net Total' : 'Grand Total' }}:</strong>
                                        <strong>Rs.{{ number_format($netTotalAfterReturns, 2) }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($selectedSale->notes)
                        <!-- Notes Section -->
                        <div style="margin-top:20px; padding:10px; background:#f8f9fa; border-left:3px solid #666;">
                            <p style="margin:0; font-size:11px;"><strong>Notes:</strong> {{ $selectedSale->notes }}</p>
                        </div>
                        @endif

                        @if($showDueDetails && $selectedSale->customer && $selectedSale->customer->name !== 'Walking Customer' && $selectedSale->customer->name !== 'Walk-in')
                        @php
                            $receiptCustomer = $selectedSale->customer;
                            $receiptDueInvoiceCount = \App\Models\Sale::where('customer_id', $receiptCustomer->id)
                                ->where('due_amount', '>', 0)
                                ->count();

                            $receiptReturnedCheques = \App\Models\Cheque::where('customer_id', $receiptCustomer->id)
                                ->where('status', 'return')
                                ->get();
                            $receiptReturnedChequeCount = $receiptReturnedCheques->count();
                            $receiptReturnedChequeAmount = $receiptReturnedCheques->sum('cheque_amount');
                        @endphp
                        <div style="margin-top: 15px; padding: 12px; border: 1.5px solid #e67e22; background-color: #fffaf5; border-radius: 6px; text-align: left;">
                            <h4 style="margin: 0 0 8px 0; color: #e67e22; font-size: 11px; font-weight: bold; border-bottom: 1px solid #ffebcc; padding-bottom: 4px; text-transform: uppercase;">Outstanding Financial Summary</h4>
                            @if($receiptReturnedChequeCount > 0)
                            <div style="display: flex; justify-content: space-between; margin-bottom: 4px; font-size: 11px;">
                                <span>Invoice Outstanding Due:</span>
                                <strong>Rs. {{ number_format(max(0, $receiptCustomer->total_due - $receiptReturnedChequeAmount), 2) }} ({{ $receiptDueInvoiceCount }} Invoices)</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 4px; font-size: 11px; color: #d32f2f;">
                                <span>Returned Cheque Amount:</span>
                                <strong>Rs. {{ number_format($receiptReturnedChequeAmount, 2) }} ({{ $receiptReturnedChequeCount }} Cheques)</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 4px; font-size: 11px; border-top: 1px dashed #ffebcc; padding-top: 4px;">
                                <span style="color: #e67e22; font-weight: bold;">Total Outstanding Due:</span>
                                <strong style="color: #e67e22; font-size: 12px;">Rs. {{ number_format($receiptCustomer->total_due, 2) }}</strong>
                            </div>
                            @else
                            <div style="display: flex; justify-content: space-between; margin-bottom: 4px; font-size: 11px;">
                                <span>Remaining Due Amount:</span>
                                <strong>Rs. {{ number_format($receiptCustomer->total_due, 2) }}</strong>
                            </div>
                            @endif
                        </div>
                        @endif

                        <!-- Footer -->
                        <div style="margin-top:auto; text-align:center; padding-top:12px; display:flex; flex-direction:column;">
                            <div style="display:flex; justify-content:center; gap:20px; margin-bottom:12px;">
                                <div style="flex:0 0 50%; text-align:center;"><p><strong>....................</strong></p><p><strong>Authorized Signature</strong></p></div>
                                <div style="flex:0 0 50%; text-align:center;"><p><strong>....................</strong></p><p><strong>Customer Signature</strong></p></div>
                            </div>
                            
                            <div>
                                <p style="margin:0; font-size:12px;">Returns accepted within 30 days of purchase with the original invoice. Terms and conditions apply.</p>
                                <p style="margin:0; font-size:12px;">Thank you for your business!</p>
                                <p style="margin:0; font-size:12px;">www.hardmen.lk | info@hardmen.lk</p>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
                {{-- ==================== FOOTER BUTTONS ==================== --}}
                <div class="modal-footer bg-light justify-content-between border-top">
                    <div class="d-flex align-items-center">
                        @if($selectedSale && $selectedSale->customer && $selectedSale->customer->name !== 'Walking Customer' && $selectedSale->customer->name !== 'Walk-in')
                        <label class="inline-flex items-center text-xs font-semibold text-slate-600 cursor-pointer mb-0">
                            <input type="checkbox" wire:model.live="showDueDetails" class="rounded border-slate-300 text-primary focus:ring-primary me-2">
                            Show Remaining Due Amount on Receipt
                        </label>
                        @endif
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-secondary" wire:click="closeModals">
                            <i class="bi bi-x-circle me-1"></i> Close
                        </button>
                        @if($selectedSale)
                        <button type="button" class="btn btn-success" wire:click="downloadInvoice({{ $selectedSale->id }})">
                            <i class="bi bi-download me-1"></i> Download
                        </button>
                        <button type="button" class="btn btn-outline-primary" onclick="printInvoice()">
                            <i class="bi bi-printer me-1"></i> Print
                        </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Payment History Modal --}}
    <div wire:ignore.self class="modal fade" id="paymentHistoryModal" tabindex="-1" aria-labelledby="paymentHistoryModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-gradient-info text-white">
                    <div>
                        <h5 class="modal-title fw-bold mb-1">
                            <i class="bi bi-clock-history me-2"></i> Payment History
                        </h5>
                        @if($selectedSale)
                        <small class="opacity-75">Invoice: {{ $selectedSale->invoice_number }}</small>
                        @endif
                    </div>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeModals"></button>
                </div>
                <div class="modal-body">
                    @if($selectedSale)
                    {{-- Sale Summary --}}
                    <div class="card bg-light border-0 mb-4">
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-person-circle text-primary me-2" style="font-size: 1.5rem;"></i>
                                        <div>
                                            <small class="text-muted d-block">Customer</small>
                                            <strong>{{ $selectedSale->customer ? $selectedSale->customer->name : 'Walk-in Customer' }}</strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted d-block">Total Amount</small>
                                    <strong class="text-dark fs-5">Rs.{{ number_format($selectedSale->total_amount, 2) }}</strong>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted d-block">Payment Status</small>
                                    <span class="badge bg-{{ $selectedSale->payment_status == 'paid' ? 'success' : ($selectedSale->payment_status == 'partial' ? 'warning' : 'danger') }}">
                                        {{ ucfirst($selectedSale->payment_status) }}
                                    </span>
                                </div>
                            </div>
                            @if($selectedSale->due_amount > 0)
                            <div class="alert alert-warning mt-3 mb-0">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong>Due Amount: Rs.{{ number_format($selectedSale->due_amount, 2) }}</strong>
                            </div>
                            @endif
                        </div>
                    </div>

                    {{-- Payment List --}}
                    <div class="mb-3">
                        <h6 class="fw-bold text-dark mb-3">
                            <i class="bi bi-list-check me-2"></i> Payment Records
                            <span class="badge bg-primary ms-2">{{ count($paymentHistory) }} payments</span>
                        </h6>

                        @forelse($paymentHistory as $index => $payment)
                        <div class="card mb-3 shadow-sm border-start border-4 border-{{ $payment->status === 'approved' || $payment->status === 'paid' ? 'success' : ($payment->status === 'pending' ? 'warning' : 'danger') }}">
                            <div class="card-header bg-white">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                <strong>#{{ $index + 1 }}</strong>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-bold">Payment #{{ $payment->id }}</h6>
                                                <small class="text-muted">
                                                    <i class="bi bi-calendar3 me-1"></i>
                                                    {{ $payment->payment_date ? $payment->payment_date->format('M d, Y h:i A') : '-' }}
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <div class="fs-4 fw-bold text-success">Rs.{{ number_format($payment->amount, 2) }}</div>
                                        <span class="badge bg-secondary">
                                            {{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    {{-- Payment Method Details --}}
                                    <div class="col-md-6">
                                        <small class="text-muted d-block">Payment Method</small>
                                        <strong>
                                            <i class="bi bi-{{ $payment->payment_method === 'cash' ? 'cash' : ($payment->payment_method === 'card' ? 'credit-card' : ($payment->payment_method === 'cheque' ? 'receipt' : 'bank')) }} me-1"></i>
                                            {{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}
                                        </strong>
                                    </div>

                                    @if($payment->payment_reference)
                                    <div class="col-md-6">
                                        <small class="text-muted d-block">Reference Number</small>
                                        <strong>{{ $payment->payment_reference }}</strong>
                                    </div>
                                    @endif

                                    @if($payment->card_number)
                                    <div class="col-md-6">
                                        <small class="text-muted d-block">Card Number</small>
                                        <strong>{{ $payment->card_number }}</strong>
                                    </div>
                                    @endif

                                    @if($payment->bank_name)
                                    <div class="col-md-6">
                                        <small class="text-muted d-block">Bank Name</small>
                                        <strong>{{ $payment->bank_name }}</strong>
                                    </div>
                                    @endif

                                    @if($payment->transfer_date)
                                    <div class="col-md-6">
                                        <small class="text-muted d-block">Transfer Date</small>
                                        <strong>{{ date('M d, Y', strtotime($payment->transfer_date)) }}</strong>
                                    </div>
                                    @endif

                                    @if($payment->transfer_reference)
                                    <div class="col-md-6">
                                        <small class="text-muted d-block">Transfer Reference</small>
                                        <strong>{{ $payment->transfer_reference }}</strong>
                                    </div>
                                    @endif

                                    @if($payment->status)
                                    <div class="col-md-6">
                                        <small class="text-muted d-block">Status</small>
                                        {!! $payment->status_badge !!}
                                    </div>
                                    @endif
                                </div>

                                {{-- Cheques --}}
                                @if($payment->cheques && count($payment->cheques) > 0)
                                <div class="mt-3">
                                    <small class="text-muted d-block mb-2"><strong>Cheque Details:</strong></small>
                                    <div class="table-responsive "style="min-height: 100px;">
                                        <table class="table table-sm table-bordered mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Cheque Number</th>
                                                    <th>Bank</th>
                                                    <th>Amount</th>
                                                    <th>Date</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($payment->cheques as $cheque)
                                                <tr>
                                                    <td><strong>{{ $cheque->cheque_number }}</strong></td>
                                                    <td>{{ $cheque->bank_name }}</td>
                                                    <td class="text-success fw-bold">Rs.{{ number_format($cheque->amount, 2) }}</td>
                                                    <td>{{ $cheque->cheque_date ? date('M d, Y', strtotime($cheque->cheque_date)) : '-' }}</td>
                                                    <td>
                                                        <span class="badge bg-{{ $cheque->status === 'cleared' ? 'success' : ($cheque->status === 'pending' ? 'warning' : 'danger') }}">
                                                            {{ ucfirst($cheque->status) }}
                                                        </span>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                @endif

                                {{-- Notes --}}
                                @if($payment->notes)
                                <div class="mt-3">
                                    <small class="text-muted d-block">Notes:</small>
                                    <div class="alert alert-light mb-0">{{ $payment->notes }}</div>
                                </div>
                                @endif
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-5">
                            <i class="bi bi-inbox text-muted" style="font-size: 4rem;"></i>
                            <p class="text-muted mt-3 mb-2"><strong>No payment records found</strong></p>
                            <p class="text-muted">This sale hasn't received any payments yet.</p>
                        </div>
                        @endforelse

                        {{-- Summary --}}
                        @if(count($paymentHistory) > 0)
                        <div class="card bg-success text-white border-0 shadow-sm mt-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <small class="opacity-75">Total Payments Made</small>
                                        <h4 class="mb-0 fw-bold">Rs.{{ number_format($paymentHistory->sum('amount'), 2) }}</h4>
                                    </div>
                                    <i class="bi bi-cash-stack" style="font-size: 3rem; opacity: 0.3;"></i>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                    @endif
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" wire:click="closeModals">
                        <i class="bi bi-x-circle me-1"></i> Close
                    </button>
                </div>
            </div>
        </div>
    </div>


    {{-- Delete Confirmation Modal --}}
    <div wire:ignore.self class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-exclamation-triangle me-2"></i> Confirm Deletion
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeModals"></button>
                </div>
                <div class="modal-body">
                    @if($selectedSale)
                    <div class="alert alert-danger">
                        <h6 class="alert-heading">Warning!</h6>
                        <p class="mb-0">You are about to delete the following sale. This action cannot be undone and will restore product stock.</p>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <p><strong>Invoice:</strong> {{ $selectedSale->invoice_number }}</p>
                            <p><strong>Customer:</strong> {{ $selectedSale->customer->name ?? 'Walk-in Customer' }}</p>
                            <p><strong>Amount:</strong> Rs.{{ number_format($selectedSale->total_amount, 2) }}</p>
                            <p><strong>Date:</strong> {{ $selectedSale->created_at->format('M d, Y') }}</p>
                            <p><strong>Items:</strong> {{ $selectedSale->items->count() }} products</p>
                        </div>
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeModals">Cancel</button>
                    <button type="button" class="btn btn-danger" wire:click="confirmDelete">
                        <i class="bi bi-trash me-1"></i> Delete Sale
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Toast Container --}}
    <div class="toast-container position-fixed top-0 end-0 p-3">
        <div id="livewire-toast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <strong class="me-auto">Notification</strong>
                <small>Just now</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                <!-- Toast message will be inserted here -->
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .closebtn {
        top: 3%;
        right: 3%;
        position: absolute;
    }

    .btn-group-sm>.btn {
        padding: 0.25rem 0.5rem;
    }

    .modal-header {
        border-bottom: 1px solid #dee2e6;
        background: linear-gradient(90deg, #000000, #000000);
        color: #fff;
    }

    .badge {
        font-size: 0.75em;
    }

    /* Hover effects */
    .table-hover tbody tr:hover {
        background-color: rgba(0, 0, 0, 0.025);
    }

    .table td {
        vertical-align: middle;
    }

    /* Print styles */
    @page {
        size: letter portrait;
        margin: 6mm;
    }

    @media print {
        /* Remove browser header/footer */
        @page {
            margin: 0mm;
        }

        /* Hide everything except the invoice */
        body * {
            visibility: hidden;
        }

        #printableInvoice,
        #printableInvoice * {
            visibility: visible;
        }

        /* Position the invoice */
        #printableInvoice {
            position: fixed !important;
            left: 0 !important;
            top: 0 !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 20px !important;
            background: #fff !important;
            font-size: 11pt !important;
            color: #000 !important;
            box-sizing: border-box !important;
            overflow: visible !important;
            page-break-after: avoid !important;
            page-break-before: avoid !important;
        }

        /* Reset modal styles for print */
        .modal,
        .modal-dialog,
        .modal-content {
            all: unset !important;
            display: block !important;
            width: 100% !important;
            height: auto !important;
            position: static !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        /* Hide modal chrome */
        .modal-footer,
        .modal-header .btn,
        .btn-close,
        .closebtn,
        .screen-only-header {
            display: none !important;
        }

        /* Header styles */
        .modal-header {
            border: none !important;
            padding: 0 0 15px 0 !important;
            text-align: center !important;
            margin-bottom: 15px !important;
            background: transparent !important;
            border-bottom: 3px solid #000000 !important;
        }

        .modal-header h4 {
            margin: 5px 0 !important;
            font-size: 1.2rem !important;
            color: #000 !important;
            font-weight: bold !important;
            letter-spacing: 1px;
        }

        .modal-header p {
            margin: 2px 0 !important;
            font-size: 0.9rem !important;
            color: #000 !important;
        }

        /* Body content */
        .modal-body {
            padding: 0 !important;
            margin: 0 !important;
            max-height: none !important;
            overflow: visible !important;
        }

        /* Layout fixes */
        .row {
            display: flex !important;
            margin: 0 !important;
            page-break-inside: avoid !important;
        }

        .row>.col-6 {
            page-break-inside: avoid !important;
            flex: 0 0 50% !important;
            max-width: 50% !important;
        }

        .row>.col-6:first-child {
            text-align: left !important;
        }

        .row>.col-6:last-child {
            text-align: right !important;
        }

        .row>.col-7 {
            display: none !important;
        }

        .row>.col-5 {
            flex: 0 0 100% !important;
            max-width: 100% !important;
        }

        /* Table styles */
        .table {
            border-collapse: collapse !important;
            width: 100% !important;
            margin: 12px 0 !important;
            font-size: 10pt !important;
        }

        .table th {
            border-bottom: 2px solid #000 !important;
            padding: 8px !important;
            text-align: left !important;
            font-weight: bold !important;
            background: transparent !important;
            color: #000 !important;
        }

        .table td {
            border: none !important;
            padding: 6px 8px !important;
            color: #000 !important;
            background: transparent !important;
        }

        .table tbody tr {
            border-bottom: 1px solid #ddd !important;
        }

        .table-light th,
        .table-light td,
        tfoot.table-light tr,
        tfoot.table-light td {
            background: #f5f5f5 !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .table-sm {
            font-size: 9pt !important;
        }

        .table-borderless td {
            border: none !important;
            padding: 4px 8px !important;
        }

        .table-borderless strong {
            min-width: 100px !important;
            display: inline-block !important;
        }

        /* Typography */
        h6 {
            color: #000 !important;
            margin: 12px 0 8px 0 !important;
            font-weight: bold !important;
            font-size: 11pt !important;
        }

        h5,
        h4 {
            margin: 8px 0 !important;
            color: #000 !important;
            font-weight: bold !important;
        }

        p {
            margin: 4px 0 !important;
        }

        /* Badge and color fixes */
        .badge {
            border: 1px solid #000 !important;
            padding: 2px 6px !important;
            border-radius: 2px !important;
            color: #000 !important;
            background: transparent !important;
            font-size: 8pt !important;
        }

        .fw-bold,
        strong {
            font-weight: bold !important;
            color: #000 !important;
        }

        .text-danger {
            color: #000 !important;
        }

        .text-success {
            color: #000 !important;
        }

        .text-muted {
            font-size: 9pt !important;
            color: #666 !important;
        }

        .text-end {
            text-align: right !important;
        }

        /* Card styles */
        .card {
            border: 1px solid #ddd !important;
            page-break-inside: avoid !important;
            margin: 8px 0 !important;
        }

        .card-body {
            padding: 8px !important;
        }

        .card-header {
            background: #f5f5f5 !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            padding: 6px 8px !important;
            font-weight: bold !important;
        }

        .alert {
            border: 1px solid #999 !important;
            padding: 8px !important;
            margin: 8px 0 !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .alert-heading {
            margin: 0 0 6px 0 !important;
            font-weight: bold !important;
        }

        .bg-light {
            background-color: #f5f5f5 !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* Spacing adjustments */
        .mb-3,
        .mb-4 {
            margin-bottom: 8px !important;
        }

        .mt-4 {
            margin-top: 12px !important;
        }

        .pt-3 {
            padding-top: 8px !important;
        }

        .pb-2 {
            padding-bottom: 4px !important;
        }

        .p-4 {
            padding: 8px !important;
        }

        /* Prevent page breaks */
        .table-responsive {
            page-break-inside: avoid !important;
        }

        hr {
            border: none !important;
            border-top: 1px solid #000 !important;
            margin: 8px 0 !important;
        }
        
        /* Optimize for printing */
        html,
        body {
            margin: 0 !important;
            padding: 0 !important;
            background: #fff !important;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    // Improved Print function matching store-billing approach
    function printInvoice() {
        console.log('=== Print Invoice Function Called ===');
        
        const printEl = document.getElementById('printableInvoice');
        if (!printEl) { 
            console.error('ERROR: Printable invoice element not found');
            alert('Invoice not found. Please try again.');
            return; 
        }

        console.log('Print element found, preparing content...');

        // Clone the content to avoid modifying the original
        let content = printEl.cloneNode(true);
        
        // Remove any buttons or interactive elements from print
        content.querySelectorAll('button, .no-print, .modal-footer').forEach(el => el.remove());

        // Get the HTML string
        let htmlContent = content.innerHTML;

        console.log('Content prepared, opening print window...');

        // Open a new window
        const printWindow = window.open('', '_blank', 'width=900,height=700');
        
        if (!printWindow) {
            console.error('ERROR: Print window blocked by popup blocker');
            alert('Popup blocked. Please allow pop-ups for this site.');
            return;
        }

        console.log('Print window opened successfully');

        // Complete HTML document with print styles
        const fullHtml = `
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="utf-8">
                <title>Invoice - HARDMEN (PVT) LTD</title>
                <style>
                    @page { 
                        size: letter portrait; 
                        margin: 6mm; 
                    }

                    html, body { height: 100%; }

                    * {
                        margin: 0;
                        padding: 0;
                        box-sizing: border-box;
                    }

                    body { 
                        font-family: sans-serif; 
                        color: #000; 
                        background: #fff; 
                        padding: 10mm;
                        font-size: 12px;
                        line-height: 1.4;
                    }

                    .receipt-container { 
                        max-width: 800px; 
                        margin: 0 auto;
                        padding: 20px;
                        background: white;
                        display: flex;
                        flex-direction: column;
                        min-height: 100vh;
                        page-break-inside: avoid;
                    }

                    .receipt-footer { 
                        margin-top: auto !important; 
                        page-break-inside: avoid;
                    }
                    
                    .receipt-header { 
                        border-bottom: 3px solid #000; 
                        padding-bottom: 12px; 
                        margin-bottom: 12px; 
                    }
                    
                    .receipt-row { 
                        display: flex; 
                        align-items: center; 
                        justify-content: space-between; 
                    }
                    
                    .receipt-center { 
                        flex: 1; 
                        text-align: center; 
                    }
                    
                    .receipt-center h2 { 
                        margin: 0 0 4px 0; 
                        font-size: 2rem; 
                        letter-spacing: 2px;
                        font-weight: bold;
                    }
                    
                    table.receipt-table { 
                        width: 100%; 
                        border-collapse: collapse; 
                        margin-top: 12px; 
                    }
                    
                    table.receipt-table th {
                        border-bottom: 1px solid #000; 
                        padding: 8px; 
                        text-align: left;
                        font-weight: bold;
                        background: none;
                    }
                    
                    table.receipt-table td { 
                        padding: 2px; 
                        text-align: left;
                        border: none;
                    }
                    
                    .text-end { 
                        text-align: right; 
                    }
                    
                    .text-muted {
                        color: #000000;
                    }
                    
                    p {
                        margin: 4px 0;
                    }
                    
                    strong {
                        font-weight: bold;
                    }
                    
                    hr {
                        border: none;
                        border-top: 1px solid #000;
                        margin: 8px 0;
                    }
                    
                    @media print {
                        body {
                            padding: 0;
                        }
                        
                        .receipt-container {
                            box-shadow: none !important;
                        }
                        
                        .receipt-container {
                            page-break-inside: avoid;
                        }
                    }
                </style>
            </head>
            <body>
                ${htmlContent}
                <script>
                    console.log('Print window document loaded');
                    window.onload = function() {
                        console.log('Print window fully loaded, triggering print dialog...');
                        setTimeout(function() {
                            try {
                                window.print();
                                console.log('Print dialog triggered');
                            } catch(e) {
                                console.error('Print failed:', e);
                                alert('Print failed: ' + e.message);
                            }
                        }, 500);
                    };
                <\/script>
            </body>
            </html>
        `;

        // Write the content
        try {
            printWindow.document.open();
            printWindow.document.write(fullHtml);
            printWindow.document.close();
            console.log('=== Content written to print window successfully ===');
        } catch(e) {
            console.error('ERROR writing to print window:', e);
            alert('Failed to prepare print: ' + e.message);
        }
        
        // Focus the print window
        printWindow.focus();
    }

    document.addEventListener('livewire:initialized', () => {
        // Modal management
        Livewire.on('showModal', (modalId) => {
            console.log('Showing modal:', modalId);
            const modalElement = document.getElementById(modalId);
            if (modalElement) {
                const modal = new bootstrap.Modal(modalElement);
                modal.show();

                // Close modal when hidden
                modalElement.addEventListener('hidden.bs.modal', function() {
                    Livewire.dispatch('closeModals');
                });
            }
        });

        Livewire.on('hideModal', (modalId) => {
            console.log('Hiding modal:', modalId);
            const modalElement = document.getElementById(modalId);
            if (modalElement) {
                const modal = bootstrap.Modal.getInstance(modalElement);
                if (modal) {
                    modal.hide();
                }
            }
        });

        // Toast notifications
        Livewire.on('showToast', (event) => {
            const toastElement = document.getElementById('livewire-toast');
            if (toastElement) {
                const toastBody = toastElement.querySelector('.toast-body');
                const toastHeader = toastElement.querySelector('.toast-header');

                if (toastBody) toastBody.textContent = event.message;
                if (toastHeader) {
                    // Remove existing color classes
                    toastHeader.className = 'toast-header text-white';
                    // Add new color class
                    toastHeader.classList.add('bg-' + event.type);
                }

                const toast = new bootstrap.Toast(toastElement);
                toast.show();
            }
        });

        // Close modals when escape key is pressed
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                Livewire.dispatch('closeModals');
            }
        });
    });

    // Handle download button state
    document.addEventListener('livewire:request-start', (event) => {
        const buttons = document.querySelectorAll('[wire\\:click*="downloadInvoice"]');
        buttons.forEach(button => {
            button.disabled = true;
            const icon = button.querySelector('i');
            if (icon) {
                icon.className = 'bi bi-hourglass-split me-1';
            }
        });
    });

    document.addEventListener('livewire:request-finish', (event) => {
        const buttons = document.querySelectorAll('[wire\\:click*="downloadInvoice"]');
        buttons.forEach(button => {
            button.disabled = false;
            const icon = button.querySelector('i');
            if (icon) {
                icon.className = 'bi bi-download me-1';
            }
        });
    });
</script>
@endpush