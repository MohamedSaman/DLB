<div class=\"container-fluid py-3\" wire:poll.10s>
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-2">
                <i class="bi bi-clipboard-check text-primary me-2"></i> Staff Sales
            </h3>
            <p class="text-muted mb-0">View all staff sales with filters and summary <small class="text-success"><i class="bi bi-arrow-repeat me-1"></i>Auto-refreshing</small></p>
        </div>
        <div>
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
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3">
                        <i class="bi bi-cash-stack text-primary fs-4"></i>
                    </div>
                    <div>
                        <p class="text-muted mb-0 small">Total Sales Amount</p>
                        <h4 class="fw-bold mb-0">Rs. {{ number_format($totalSalesAmount, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-danger bg-opacity-10 p-3 me-3">
                        <i class="bi bi-exclamation-circle text-danger fs-4"></i>
                    </div>
                    <div>
                        <p class="text-muted mb-0 small">Total Due Amount</p>
                        <h4 class="fw-bold mb-0 text-danger">Rs. {{ number_format($totalDueAmount, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3">
                        <i class="bi bi-check-circle text-success fs-4"></i>
                    </div>
                    <div>
                        <p class="text-muted mb-0 small">Total Collected Amount</p>
                        <h4 class="fw-bold mb-0 text-success">Rs. {{ number_format($totalCollectedAmount, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Search invoice, ID or customer...">
                    </div>
                </div>
                <div class="col-md-2">
                    <select wire:model.live="staffFilter" class="form-select">
                        <option value="">All Staff</option>
                        @foreach($staffUsers as $staff)
                            <option value="{{ $staff->id }}">{{ $staff->name }} ({{ ucfirst($staff->staff_type) }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select wire:model.live="statusFilter" class="form-select">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="confirm">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" wire:model.live="dateFrom" class="form-control" title="From Date">
                </div>
                <div class="col-md-2">
                    <input type="date" wire:model.live="dateTo" class="form-control" title="To Date">
                </div>
                <div class="col-md-1">
                    <select wire:model.live="perPage" class="form-select form-select-sm" title="Entries per page">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="200">200</option>
                        <option value="500">500</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Sales List --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Invoice</th>
                            <th>Staff</th>
                            <th>Customer</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">Due</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sales as $sale)
                        <tr>
                            <td class="ps-4">
                                <span class="fw-medium">{{ $sale->invoice_number }}</span>
                                <small class="d-block text-muted">{{ $sale->sale_id }}</small>
                            </td>
                            <td>{{ $sale->user->name ?? 'N/A' }}</td>
                            <td>{{ $sale->customer->name ?? 'N/A' }}</td>
                            <td class="text-end fw-semibold">Rs. {{ number_format($sale->total_amount, 2) }}</td>
                            <td class="text-end fw-semibold {{ $sale->due_amount > 0 ? 'text-danger' : 'text-success' }}">Rs. {{ number_format($sale->due_amount ?? 0, 2) }}</td>
                            <td>
                                @if(($sale->payment_status ?? 'pending') === 'paid')
                                    <span class="badge bg-success">Paid</span>
                                @elseif(($sale->payment_status ?? 'pending') === 'partial')
                                    <span class="badge bg-info">Partial</span>
                                @else
                                    <span class="badge bg-secondary">Pending</span>
                                @endif
                            </td>
                            <td>
                                @if($sale->status === 'pending')
                                    <span class="badge bg-warning"><i class="bi bi-hourglass-split me-1"></i>Pending</span>
                                @elseif($sale->status === 'confirm')
                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Approved</span>
                                @else
                                    <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Rejected</span>
                                @endif
                            </td>
                            <td class="text-muted">{{ $sale->created_at->format('M d, Y') }}</td>
                            <td class="text-end pe-4" style="position: relative;">
                                <div class="dropdown" style="position: static;">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-three-dots-vertical"></i> Actions
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="z-index: 1050;">
                                        <li>
                                            <a class="dropdown-item" href="#" wire:click.prevent="viewDetails({{ $sale->id }})">
                                                <i class="bi bi-eye text-primary me-2"></i> View
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="#" wire:click.prevent="openEditModal({{ $sale->id }})">
                                                <i class="bi bi-pencil-square text-info me-2"></i> Edit
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="#" wire:click.prevent="printSale({{ $sale->id }})">
                                                <i class="bi bi-printer text-success me-2"></i> Print
                                            </a>
                                        </li>
                                        @if($sale->status === 'pending')
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item" href="#" wire:click.prevent="openApproveModal({{ $sale->id }})">
                                                <i class="bi bi-check-circle text-success me-2"></i> Approve
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="#" wire:click.prevent="openRejectModal({{ $sale->id }})">
                                                <i class="bi bi-x-circle text-warning me-2"></i> Reject
                                            </a>
                                        </li>
                                        @endif
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item text-danger" href="#" wire:click.prevent="openDeleteModal({{ $sale->id }})">
                                                <i class="bi bi-trash me-2"></i> Delete
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-check-circle fs-1 text-success d-block mb-2"></i>
                                No sales to display.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-light">
                <div class="d-flex justify-content-center">
                    {{ $sales->links('livewire.custom-pagination') }}
                </div>
            </div>
        </div>
    </div>

    
    {{-- Details Modal --}}
    @if($showDetailsModal && $this->selectedSale)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-receipt me-2"></i>Invoice Preview - {{ $this->selectedSale->invoice_number }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeDetailsModal"></button>
                </div>
                <div class="modal-body p-4" style="background: #f8f9fa;">
                    {{-- Printable Invoice --}}
                    <div id="printableInvoice">
                        <div class="receipt-container" style="background: white; padding: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-radius: 8px; max-width: 800px; margin: 0 auto;">
                            <style>
                                .receipt-container { width: 100%; max-width: 800px; margin: 0 auto; padding: 20px; }
                                .receipt-header { border-bottom: 3px solid #000; padding-bottom: 12px; margin-bottom: 12px; }
                                .receipt-row { display:flex; align-items:center; justify-content:space-between; }
                                .receipt-center { flex: 1; text-align:center; }
                                .receipt-center h2 { margin: 0 0 4px 0; font-size: 2rem; letter-spacing: 2px; }
                                .mb-0 { margin-bottom: 0; }
                                .mb-1 { margin-bottom: 4px; }
                                table.receipt-table { width:100%; border-collapse: collapse; margin-top: 12px; }
                                table.receipt-table th{border-bottom: 1px solid #000; padding: 8px; text-align: left;}
                                table.receipt-table td { border: 0px solid #000; padding: 2px; text-align: left; }
                                table.receipt-table th { background: none; font-weight: bold; }
                                .text-end { text-align: right; }
                            </style>

                            {{-- Header --}}
                            <div class="receipt-header">
                                <div class="receipt-row">
                                    <div class="receipt-center">
                                        <h2 class="mb-0">HARDMEN (PVT) LTD</h2>
                                        <p class="mb-0 text-muted" style="color:#666; font-size:12px;">TOOLS WITH POWER</p>
                                        <p style="margin:0; text-align:center;"><strong>421/2, Doolmala, thihariya, Kalagedihena.</strong></p>
                                        <p style="margin:0; text-align:center;"><strong>TEL :</strong> (077) 9752950, <strong>EMAIL :</strong> Hardmenlanka@gmail.com</p>
                                    </div>
                                </div>
                            </div>

                            {{-- Customer & Invoice Info --}}
                            <div style="display:flex; gap:20px; margin-bottom:12px; justify-content:space-between; align-items:flex-start;">
                                <div style="flex:0 0 45%; text-align:left;">
                                    @if($this->selectedSale->customer)
                                    <p style="margin:0; font-size:12px;"><strong>Name:</strong> {{ $this->selectedSale->customer->name }}</p>
                                    <p style="margin:0; font-size:12px;"><strong>Phone:</strong> {{ $this->selectedSale->customer->phone }}</p>
                                    <p style="margin:0; font-size:12px;"><strong>Address:</strong> {{ $this->selectedSale->customer->address ?? 'N/A' }}</p>
                                    <p style="margin:0; font-size:12px;"><strong>Type:</strong> {{ ucfirst($this->selectedSale->customer_type ?? 'customer') }}</p>
                                    @else
                                    <p class="text-muted">Walk-in Customer</p>
                                    @endif
                                    <p style="margin:0; font-size:12px;"><strong>Salesman:</strong> {{ $this->selectedSale->user->name ?? 'N/A' }}</p>
                                </div>
                                <div style="flex:0 0 45%; text-align:right;">
                                    <p style="margin:0; font-size:12px;"><strong>Invoice Number:</strong> {{ $this->selectedSale->invoice_number }}</p>
                                    <p style="margin:0; font-size:12px;"><strong>Date:</strong> {{ $this->selectedSale->created_at->format('d/m/Y h:i A') }}</p>
                                    <p style="margin:0; font-size:12px;"><strong>Payment Status:</strong> <span style="color:#e67e22; font-weight:bold;">{{ ucfirst($this->selectedSale->payment_status ?? 'paid') }}</span></p>
                                </div>
                            </div>

                            {{-- Items Table --}}
                            @php
                                $allItems = collect($this->selectedSale->items);
                                $totalItemsCount = $allItems->count();

                                $itemChunks = collect();
                                $itemOffsets = [];
                                if ($totalItemsCount <= 28) {
                                    $itemChunks->push($allItems);
                                    $itemOffsets[] = 0;
                                } else {
                                    $itemChunks->push($allItems->slice(0, 28));
                                    $itemOffsets[] = 0;
                                    $remaining = $allItems->slice(28);
                                    $currentOffset = 28;
                                    foreach ($remaining->chunk(33) as $subChunk) {
                                        $itemChunks->push($subChunk);
                                        $itemOffsets[] = $currentOffset;
                                        $currentOffset += $subChunk->count();
                                    }
                                }
                                $totalPages = $itemChunks->count();

                                $saleItemsSubtotal = $allItems->sum(fn($i) => (float)($i->total ?? 0));
                                $returnItemsTotal = 0;
                                if (isset($this->selectedSale->returns) && count($this->selectedSale->returns) > 0) {
                                    foreach ($this->selectedSale->returns as $returnItem) {
                                        $returnItemsTotal += (float) ($returnItem->total_amount ?? 0);
                                    }
                                }

                                $discountAmount = (float) ($this->selectedSale->discount_amount ?? 0);
                                $netTotalAfterReturns = (float) ($this->selectedSale->total_amount ?? 0);
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
                                    @foreach($this->selectedSale->items as $index => $item)
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
                                                $discountPercent = 0;
                                                if(isset($item->discount_type) && $item->discount_type === 'percentage' && $item->discount_percentage) {
                                                    $discountPercent = $item->discount_percentage;
                                                } else if($item->discount > 0 && $item->unit_price > 0) {
                                                    $discountPercent = ($item->discount / $item->unit_price) * 100;
                                                }
                                            @endphp
                                            @if($discountPercent > 0)
                                                {{ number_format($discountPercent, 2) }}%
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="text-end" data-total="{{ $item->total }}">Rs.{{ number_format($item->total, 2) }}</td>
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

                            @if(isset($this->selectedSale->returns) && count($this->selectedSale->returns) > 0)
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
                                        @foreach($this->selectedSale->returns as $index => $return)
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

                            {{-- Summary / Payments --}}
                            <div style="display:flex; gap:20px; margin-top:25px; border-top:2px solid #000; padding-top:12px;">
                                <div style="flex:1;">
                                    <h4 style="margin:0 0 8px 0; color:#666;">PAYMENT INFORMATION</h4>
                                    @if($this->selectedSale->payments && $this->selectedSale->payments->count() > 0)
                                        @foreach($this->selectedSale->payments as $payment)
                                        <div style="margin-bottom:8px; padding:8px; border-left:3px solid {{ $payment->is_completed ? '#28a745' : '#ffc107' }}; background:#f8f9fa;">
                                            <p style="margin:0;"><strong>{{ $payment->is_completed ? 'Payment' : 'Scheduled Payment' }}:</strong> Rs.{{ number_format($payment->amount, 2) }}</p>
                                            <p style="margin:0;"><strong>Method:</strong> {{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</p>
                                        </div>
                                        @endforeach
                                    @else
                                        <p class="text-muted">No payment information available</p>
                                    @endif
                                </div>
                                <div style="flex:1;">
                                    <div>
                                        <h4 style="margin:0 0 8px 0; border-bottom:1px solid #000; padding-bottom:8px;">ORDER SUMMARY</h4>
                                            <div style="display:flex; justify-content:space-between; margin-bottom:6px;"><span>Subtotal:</span><span>Rs.{{ number_format($saleItemsSubtotal, 2) }}</span></div>
                                        <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
                                            <span>Total Discount:</span>
                                            <span>
                                                @if($this->selectedSale->discount_type === 'percentage')
                                                        {{ number_format($discountAmount, 2) }}%
                                                @else
                                                        Rs.{{ number_format($discountAmount, 2) }}
                                                @endif
                                            </span>
                                        </div>
                                            @if($returnItemsTotal > 0)
                                            <div style="display:flex; justify-content:space-between; margin-bottom:6px; color:#dc3545;">
                                                <span>Returned Items Total:</span>
                                                <span>- Rs.{{ number_format($returnItemsTotal, 2) }}</span>
                                            </div>
                                            @endif
                                        <hr>
                                            <div style="display:flex; justify-content:space-between;"><strong>{{ $returnItemsTotal > 0 ? 'Net Total' : 'Grand Total' }}:</strong><strong>Rs.{{ number_format($netTotalAfterReturns, 2) }}</strong></div>
                                    </div>
                                </div>
                            </div>

                            @if($showDueDetails && $this->selectedSale->customer && $this->selectedSale->customer->name !== 'Walking Customer' && $this->selectedSale->customer->name !== 'Walk-in')
                            @php
                                $receiptCustomer = $this->selectedSale->customer;
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

                            {{-- Footer --}}
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
                    </div>
                </div>
                <div class="modal-footer bg-light justify-content-between">
                    <div class="d-flex align-items-center">
                        @if($this->selectedSale && $this->selectedSale->customer && $this->selectedSale->customer->name !== 'Walking Customer' && $this->selectedSale->customer->name !== 'Walk-in')
                        <label class="inline-flex items-center text-xs font-semibold text-slate-600 cursor-pointer mb-0">
                            <input type="checkbox" wire:model.live="showDueDetails" class="rounded border-slate-300 text-primary focus:ring-primary me-2">
                            Show Remaining Due Amount on Receipt
                        </label>
                        @endif
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-secondary" wire:click="closeDetailsModal">
                            <i class="bi bi-x-circle me-2"></i>Close
                        </button>
                        <button type="button" class="btn btn-primary" onclick="printInvoice()">
                            <i class="bi bi-printer me-2"></i>Print
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Reject Modal --}}
    @if($showRejectModal && $this->selectedSale)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-x-circle me-2"></i>Reject Sale</h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeRejectModal"></button>
                </div>
                <div class="modal-body">
                    <p>You are about to reject sale <strong>{{ $this->selectedSale->invoice_number }}</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea wire:model="rejectionReason" class="form-control" rows="3" placeholder="Please provide a reason for rejection..."></textarea>
                        @error('rejectionReason') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeRejectModal" wire:loading.attr="disabled" wire:target="rejectSale">Cancel</button>
                    <button type="button" wire:click="rejectSale" class="btn btn-danger" wire:loading.attr="disabled" wire:target="rejectSale" wire:loading.class="opacity-50">
                        <span wire:loading.remove wire:target="rejectSale">
                            <i class="bi bi-x-circle me-2"></i>Reject Sale
                        </span>
                        <span wire:loading wire:target="rejectSale">
                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            Processing...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Approve Confirmation Modal --}}
    @if($showApproveModal && $this->selectedSale)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bi bi-check-circle me-2"></i>Approve Sale</h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeApproveModal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning mb-3">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Important:</strong> Approving this sale will reduce the stock quantities.
                    </div>
                    <p>You are about to approve sale <strong>{{ $this->selectedSale->invoice_number }}</strong>.</p>
                    <div class="bg-light rounded p-3">
                        <p class="mb-1"><strong>Customer:</strong> {{ $this->selectedSale->customer->name ?? 'N/A' }}</p>
                        <p class="mb-1"><strong>Total Amount:</strong> <span class="text-primary fw-bold">Rs. {{ number_format($this->selectedSale->total_amount, 2) }}</span></p>
                        <p class="mb-0"><strong>Items:</strong> {{ $this->selectedSale->items->count() }} products</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeApproveModal" wire:loading.attr="disabled" wire:target="approveSale">Cancel</button>
                    <button type="button" wire:click="approveSale" class="btn btn-success" wire:loading.attr="disabled" wire:target="approveSale" wire:loading.class="opacity-50">
                        <span wire:loading.remove wire:target="approveSale">
                            <i class="bi bi-check-circle me-2"></i>Confirm Approval
                        </span>
                        <span wire:loading wire:target="approveSale">
                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            Processing...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Delete Confirmation Modal --}}
    @if($showDeleteModal && $this->selectedSale)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-trash me-2"></i>Delete Sale</h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeDeleteModal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Warning!</strong> Deleting this sale will:
                        <ul class="mb-0 mt-2">
                            <li>Restore all sale items back to stock</li>
                            <li>Delete all payment records</li>
                            <li>Reduce customer's due amount</li>
                        </ul>
                    </div>
                    <p>You are about to delete sale <strong>{{ $this->selectedSale->invoice_number }}</strong>.</p>
                    <div class="bg-light rounded p-3">
                        <p class="mb-1"><strong>Customer:</strong> {{ $this->selectedSale->customer->name ?? 'N/A' }}</p>
                        <p class="mb-1"><strong>Total Amount:</strong> <span class="text-danger fw-bold">Rs. {{ number_format($this->selectedSale->total_amount, 2) }}</span></p>
                        <p class="mb-1"><strong>Due Amount:</strong> <span class="text-warning fw-bold">Rs. {{ number_format($this->selectedSale->due_amount ?? 0, 2) }}</span></p>
                        <p class="mb-0"><strong>Items:</strong> {{ $this->selectedSale->items->count() }} products</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeDeleteModal" wire:loading.attr="disabled" wire:target="deleteSale">Cancel</button>
                    <button type="button" wire:click="deleteSale" class="btn btn-danger" wire:loading.attr="disabled" wire:target="deleteSale" wire:loading.class="opacity-50">
                        <span wire:loading.remove wire:target="deleteSale">
                            <i class="bi bi-trash me-2"></i>Delete Sale
                        </span>
                        <span wire:loading wire:target="deleteSale">
                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            Deleting...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Edit Sale Modal --}}
    @if($showEditModal && $this->selectedSale)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil-square me-2"></i>Edit Invoice - {{ $this->selectedSale->invoice_number }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeEditModal"></button>
                </div>
                <div class="modal-body p-4">
                    {{-- Sale Info --}}
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Customer:</strong> {{ $this->selectedSale->customer->name ?? 'Walk-in' }}</p>
                            <p class="mb-1"><strong>Staff:</strong> {{ $this->selectedSale->user->name ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6 text-end">
                            <p class="mb-1"><strong>Invoice #:</strong> {{ $this->selectedSale->invoice_number }}</p>
                            <p class="mb-1"><strong>Date:</strong> {{ $this->selectedSale->created_at->format('d/m/Y h:i A') }}</p>
                            <p class="mb-1"><strong>Status:</strong>
                                @if($this->selectedSale->status === 'pending')
                                    <span class="badge bg-warning">Pending</span>
                                @elseif($this->selectedSale->status === 'confirm')
                                    <span class="badge bg-success">Approved</span>
                                @else
                                    <span class="badge bg-danger">Rejected</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    {{-- Items Table --}}
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Product</th>
                                    <th class="text-center" style="width: 100px;">Qty</th>
                                    <th class="text-center" style="width: 120px;">Unit Price</th>
                                    <th class="text-center" style="width: 120px;">Discount/Unit</th>
                                    <th class="text-end" style="width: 120px;">Line Total</th>
                                    <th class="text-center" style="width: 80px;">Stock</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($this->selectedSale->items as $index => $item)
                                @php
                                    $itemId = $item->id;
                                    $editQty = (int) ($editQuantities[$itemId] ?? $item->quantity);
                                    $editPrice = (float) ($editPrices[$itemId] ?? $item->unit_price);
                                    $editDisc = (float) ($editDiscounts[$itemId] ?? $item->discount_per_unit ?? 0);
                                    $lineTotal = ($editPrice - $editDisc) * $editQty;
                                    $maxStock = (int) ($editAvailableStock[$itemId] ?? 0);
                                @endphp
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <strong>{{ $item->product_name }}</strong>
                                        @if($item->product_code)
                                            <br><small class="text-muted">{{ $item->product_code }}</small>
                                        @endif
                                        @if($item->variant_value)
                                            <br><small class="text-muted">Variant: {{ $item->variant_value }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <input type="number"
                                            class="form-control form-control-sm text-center {{ $editQty > $maxStock ? 'is-invalid' : '' }}"
                                            wire:model.live="editQuantities.{{ $itemId }}"
                                            wire:change="updateEditItem({{ $itemId }})"
                                            min="1"
                                            max="{{ $maxStock }}"
                                            style="width: 80px; margin: 0 auto;">
                                        @if($editQty > $maxStock)
                                            <div class="invalid-feedback text-center" style="font-size: 10px;">Max: {{ $maxStock }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <input type="number"
                                            class="form-control form-control-sm text-center"
                                            wire:model.live="editPrices.{{ $itemId }}"
                                            wire:change="updateEditItem({{ $itemId }})"
                                            min="0"
                                            step="0.01"
                                            style="width: 110px; margin: 0 auto;">
                                    </td>
                                    <td>
                                        <input type="number"
                                            class="form-control form-control-sm text-center"
                                            wire:model.live="editDiscounts.{{ $itemId }}"
                                            wire:change="updateEditItem({{ $itemId }})"
                                            min="0"
                                            step="0.01"
                                            style="width: 110px; margin: 0 auto;">
                                    </td>
                                    <td class="text-end fw-semibold">Rs. {{ number_format($lineTotal, 2) }}</td>
                                    <td class="text-center">
                                        <span class="badge {{ $maxStock > 5 ? 'bg-success' : ($maxStock > 0 ? 'bg-warning' : 'bg-danger') }}">
                                            {{ $maxStock }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Totals Summary --}}
                    <div class="card bg-light border-0 mt-3">
                        <div class="card-body py-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="row">
                                        <div class="col-sm-6 mb-2">
                                            <label class="form-label form-label-sm mb-1 fw-bold text-muted" style="font-size: 12px;">Global Discount Type</label>
                                            <select class="form-select form-select-sm" wire:model.live="editSaleDiscountType" wire:change="updateEditItem(0)">
                                                <option value="fixed">Fixed Amount (Rs.)</option>
                                                <option value="percentage">Percentage (%)</option>
                                            </select>
                                        </div>
                                        <div class="col-sm-6 mb-2">
                                            <label class="form-label form-label-sm mb-1 fw-bold text-muted" style="font-size: 12px;">Global Discount Value</label>
                                            <input type="number" class="form-control form-control-sm" wire:model.live="editSaleDiscountAmount" wire:change="updateEditItem(0)" min="0" step="0.01">
                                        </div>
                                    </div>
                                    <div class="text-muted" style="font-size: 11px;">
                                        <i class="bi bi-info-circle me-1"></i> This applies to the entire sale subtotal.
                                    </div>
                                </div>
                                <div class="col-md-6 border-start">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Subtotal:</span>
                                        <strong>Rs. {{ number_format($editSubtotal, 2) }}</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2 text-danger">
                                        <span>Total Discount:</span>
                                        <strong>- Rs. {{ number_format($editTotalDiscount, 2) }}</strong>
                                    </div>
                                    <hr class="my-2">
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-bold fs-5">Grand Total:</span>
                                        <strong class="fs-5 text-primary">Rs. {{ number_format($editGrandTotal, 2) }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Warnings --}}
                    @php
                        $hasStockIssue = false;
                        foreach ($editQuantities as $itemId => $qty) {
                            if ((int) $qty > (int) ($editAvailableStock[$itemId] ?? 0)) {
                                $hasStockIssue = true;
                                break;
                            }
                        }
                    @endphp
                    @if($hasStockIssue)
                    <div class="alert alert-danger mt-3 mb-0">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Stock Issue:</strong> One or more items exceed available stock. Please reduce the quantity before saving.
                    </div>
                    @endif
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" wire:click="closeEditModal" wire:loading.attr="disabled" wire:target="saveEditSale">
                        <i class="bi bi-x-circle me-1"></i> Cancel
                    </button>
                    <button type="button" wire:click="saveEditSale" class="btn btn-primary"
                        wire:loading.attr="disabled" wire:target="saveEditSale" wire:loading.class="opacity-50"
                        {{ $hasStockIssue ? 'disabled' : '' }}>
                        <span wire:loading.remove wire:target="saveEditSale">
                            <i class="bi bi-check-circle me-1"></i> Save Changes
                        </span>
                        <span wire:loading wire:target="saveEditSale">
                            <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                            Saving...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@push('style')
<style>
    /* Ensure dropdown menus in the main sales table are visible and not clipped */
    .card > .card-body > .table-responsive {
        overflow: visible !important;
    }
    .card > .card-body > .table-responsive .dropdown-menu {
        position: absolute !important;
    }
</style>
@endpush

@push('scripts')
<script>
    function paginatePrintTable(containerElement) {
        const table = containerElement.querySelector('table.receipt-table, table.items-table, table');
        if (!table) return;

        const tbody = table.querySelector('tbody');
        if (!tbody) return;

        const rows = Array.from(tbody.querySelectorAll('tr'));
        if (rows.length === 0) return;

        const getChunkTotal = (rowArray) => {
            let sum = 0;
            rowArray.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length > 0) {
                    const lastCell = cells[cells.length - 1];
                    let rawVal = lastCell.getAttribute('data-total');
                    if (!rawVal) {
                        rawVal = lastCell.textContent.replace(/,/g, '').replace(/[^0-9.-]+/g, '');
                    }
                    const val = parseFloat(rawVal);
                    if (!isNaN(val)) sum += val;
                }
            });
            return sum;
        };

        const formatRsVal = (num) => {
            return 'Rs.' + num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        };

        const PAGE_1_MAX_ITEMS = 30;
        const REST_PAGE_MAX_ITEMS = 35;

        if (rows.length <= PAGE_1_MAX_ITEMS) {
            const pageSubtotal = getChunkTotal(rows);
            const tfoot = table.querySelector('tfoot');
            if (tfoot) {
                const totalCell = tfoot.querySelector('td:last-child');
                if (totalCell) {
                    totalCell.innerHTML = `<strong>${formatRsVal(pageSubtotal)}</strong>`;
                }
            }
            return;
        }

        const pageChunks = [];
        pageChunks.push(rows.slice(0, PAGE_1_MAX_ITEMS));
        let currentRowIdx = PAGE_1_MAX_ITEMS;

        while (currentRowIdx < rows.length) {
            pageChunks.push(rows.slice(currentRowIdx, currentRowIdx + REST_PAGE_MAX_ITEMS));
            currentRowIdx += REST_PAGE_MAX_ITEMS;
        }

        const theadHtml = table.querySelector('thead') ? table.querySelector('thead').outerHTML : '';
        const tableClass = table.className || 'receipt-table';

        const headerNodes = [];
        const footerNodes = [];
        let passedTable = false;

        Array.from(containerElement.children).forEach(child => {
            if (child === table || child.contains(table)) {
                passedTable = true;
            } else if (!passedTable) {
                if (child.tagName !== 'STYLE' && child.tagName !== 'SCRIPT') {
                    headerNodes.push(child);
                }
            } else {
                if (child.tagName !== 'STYLE' && child.tagName !== 'SCRIPT') {
                    footerNodes.push(child);
                }
            }
        });

        const page1HeaderHtml = headerNodes.map(node => node.outerHTML).join('');
        headerNodes.forEach(node => node.remove());
        const footerBlocks = footerNodes;

        const paginatedWrapper = document.createElement('div');
        paginatedWrapper.className = 'paginated-print-wrapper';

        pageChunks.forEach((chunk, pageIdx) => {
            const chunkSubtotal = getChunkTotal(chunk);
            const pageDiv = document.createElement('div');
            pageDiv.className = 'print-page';
            if (pageIdx > 0) {
                pageDiv.style.pageBreakBefore = 'always';
                pageDiv.style.breakBefore = 'page';
                pageDiv.style.marginTop = '15px';
            }

            let pageHeaderHtml = '';
            if (pageIdx === 0) {
                pageHeaderHtml = page1HeaderHtml;
            } else {
                pageHeaderHtml = `
                    <div style="border-bottom: 2px solid #000; padding-bottom: 6px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;">
                        <strong style="font-size: 14px;">HARDMEN (PVT) LTD — INVOICE</strong>
                        <span style="font-size: 11px; font-weight: bold; color: #555;">Page ${pageIdx + 1} of ${pageChunks.length}</span>
                    </div>
                `;
            }

            const chunkTbodyHtml = '<tbody>' + chunk.map(r => r.outerHTML).join('') + '</tbody>';

            pageDiv.innerHTML = `
                ${pageHeaderHtml}
                <table class="${tableClass}" style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                    ${theadHtml}
                    ${chunkTbodyHtml}
                    <tfoot>
                        <tr style="border-top: 1px solid #000; border-bottom: 2px solid #000;">
                            <td colspan="6" class="text-end" style="padding: 6px; font-weight: bold;">
                                Sales Items Subtotal (Page ${pageIdx + 1}${pageChunks.length > 1 ? ' of ' + pageChunks.length : ''})
                            </td>
                            <td class="text-end" style="padding: 6px; font-weight: bold;">
                                ${formatRsVal(chunkSubtotal)}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            `;

            if (pageIdx === pageChunks.length - 1) {
                footerBlocks.forEach(block => pageDiv.appendChild(block.cloneNode(true)));
            }

            paginatedWrapper.appendChild(pageDiv);
        });

        containerElement.innerHTML = '';
        containerElement.appendChild(paginatedWrapper);
    }

    function printInvoice() {
        console.log('=== Print Invoice Function Called ===');
        
        const printEl = document.getElementById('printableInvoice');
        if (!printEl) { 
            console.error('ERROR: Printable invoice element not found');
            setTimeout(function() {
                console.log('Retrying print after 1 second...');
                const retryEl = document.getElementById('printableInvoice');
                if (retryEl) {
                    printInvoice();
                } else {
                    alert('Invoice not ready for printing. Please try again.');
                }
            }, 1000);
            return; 
        }

        console.log('Print element found:', printEl);

        // Get the actual receipt container
        const receiptContainer = printEl.querySelector('.receipt-container');
        if (!receiptContainer) {
            console.error('ERROR: Receipt container not found inside printableInvoice');
            alert('Invoice content not ready. Please try again.');
            return;
        }

        console.log('Receipt container found, preparing content...');

        // Clone the content to avoid modifying the original
        let content = receiptContainer.cloneNode(true);
        
        // Remove any buttons or interactive elements from print
        content.querySelectorAll('button, .no-print').forEach(el => el.remove());

        // Paginate table for multi-page print invoices & calculate accurate per-page subtotals
        paginatePrintTable(content);

        // Ensure footer is anchored to bottom
        const footerEl = content.querySelector('div[style*="border-top:2px solid #000"]') || content.querySelector('div:last-child');
        if (footerEl) {
            footerEl.classList.add('receipt-footer');
            footerEl.style.marginTop = 'auto';
        }

        // Get the HTML string
        let htmlContent = content.outerHTML;

        console.log('Content prepared, opening print window...');

        // Open a new window
        const printWindow = window.open('', '_blank', 'width=800,height=600');
        
        if (!printWindow) {
            console.error('ERROR: Print window blocked by popup blocker');
            alert('Popup blocked. Please allow pop-ups for this site.');
            return;
        }

        console.log('Print window opened successfully');

        // Complete HTML document with styles matching store billing
        const fullHtml = `
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="utf-8">
                <title>Invoice - HARDMEN (PVT) LTD</title>
                <style>
                    @page { 
                        size: letter portrait; 
                        margin: 5mm 8mm; 
                    }

                    html, body { 
                        height: auto !important; 
                        min-height: 0 !important;
                    }

                    * {
                        margin: 0;
                        padding: 0;
                        box-sizing: border-box;
                    }

                    body { 
                        font-family: sans-serif; 
                        color: #000; 
                        background: #fff; 
                        padding: 5mm;
                        font-size: 12px;
                        line-height: 1.4;
                    }

                    .receipt-container { 
                        max-width: 800px; 
                        margin: 0 auto;
                        padding: 10px;
                        background: white;
                        display: block;
                        page-break-inside: auto;
                    }

                    .receipt-footer { 
                        margin-top: 15px !important; 
                        page-break-inside: avoid;
                    }
                    
                    .receipt-header { 
                        border-bottom: 3px solid #000; 
                        padding-bottom: 8px; 
                        margin-bottom: 8px; 
                        page-break-inside: avoid;
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
                        margin-top: 8px; 
                        page-break-inside: auto;
                    }
                    
                    table.receipt-table th {
                        border-bottom: 1px solid #000; 
                        padding: 6px 8px; 
                        text-align: left;
                        font-weight: bold;
                        background: none;
                    }
                    
                    table.receipt-table td { 
                        padding: 3px 2px; 
                        text-align: left;
                        border: none;
                    }

                    table.receipt-table tr {
                        page-break-inside: avoid !important;
                    }
                    
                    .text-end { 
                        text-align: right; 
                    }
                    
                    .text-muted {
                        color: #000000;
                    }
                    
                    p {
                        margin: 2px 0;
                    }
                    
                    strong {
                        font-weight: bold;
                    }
                    
                    hr {
                        border: none;
                        border-top: 1px solid #000;
                        margin: 6px 0;
                    }
                    
                    @media print {
                        html, body {
                            padding: 0 !important;
                            margin: 0 !important;
                            height: auto !important;
                            min-height: 0 !important;
                            background: #fff !important;
                        }
                        
                        .receipt-container {
                            box-shadow: none !important;
                            padding: 0 !important;
                            margin: 0 !important;
                            min-height: 0 !important;
                            height: auto !important;
                            display: block !important;
                            page-break-inside: auto !important;
                        }

                        tr {
                            page-break-inside: avoid !important;
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

    // Make printInvoice available globally
    window.printInvoice = printInvoice;
    console.log('printInvoice function registered globally');
</script>
@endpush
