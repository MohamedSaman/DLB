<div class="container-fluid py-4">
    {{-- Flash Messages --}}
    @if (session()->has('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if (session()->has('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-2">
                <i class="bi bi-receipt text-success me-2"></i> Customer Payment Receipt
            </h3>
            <p class="text-muted mb-0">Record customer payments and allocate to due invoices</p>
        </div>
    </div>

    {{-- Customer Search --}}
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="fw-bold mb-0">
                <i class="bi bi-search me-2"></i> Find Customer with Due Payments
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Filter by User</label>
                    <select class="form-select" wire:model.live="userFilter">
                        <option value="admin">Admin Only</option>
                        <option value="all">All Users</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Search Customer</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-person"></i>
                        </span>
                        <input type="text" class="form-control"
                            placeholder="Search by customer name, phone or email..."
                            wire:model.live="search">
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Selected Customer</label>
                    @if($selectedCustomer)
                    <div class="border rounded p-3 bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="fw-bold mb-1">{{ $selectedCustomer->name }}</h6>
                                <p class="mb-1 text-muted">
                                    <i class="bi bi-telephone me-1"></i>{{ $selectedCustomer->phone }}
                                    @if($selectedCustomer->email)
                                    | <i class="bi bi-envelope me-1"></i>{{ $selectedCustomer->email }}
                                    @endif
                                </p>
                                <span class="badge bg-warning">
                                    {{ count($customerSales) }} Due Invoice(s)
                                </span>
                            </div>
                            <button class="btn btn-outline-secondary btn-sm" wire:click="clearSelectedCustomer">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                    </div>
                    @else
                    <div class="border rounded p-3 text-center text-muted">
                        <i class="bi bi-person-x display-6 d-block mb-2"></i>
                        No customer selected
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Customer List --}}
    @if(!$selectedCustomer)
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">
                <i class="bi bi-people me-2"></i> Customers with Due Payments
            </h5>
            <span class="badge bg-primary">{{ $customers->total() }} customers</span>
        </div>
        <div class="card-body p-0 overflow-auto">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Customer</th>
                            <th>Contact</th>
                            <th class="text-center">User</th>
                            <th class="text-center">Due Invoices</th>
                            <th class="text-end">Total Due Amount</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $customer)
                        @php
                        $dueInvoices = $customer->sales->whereIn('payment_status', ['pending', 'partial'])->count();
                        $salesDue = $customer->sales->whereIn('payment_status', ['pending', 'partial'])->sum(function($sale) {
                        $returnAmount = $sale->returns ? $sale->returns->sum('total_amount') : 0;
                        return max(0, $sale->due_amount );
                        });
                        $totalDue = ($customer->opening_balance ?? 0) + $salesDue;
                        @endphp
                        <tr wire:key="customer-{{ $customer->id }}">
                            <td class="ps-4">
                                <div class="fw-bold text-dark">{{ $customer->name }}</div>
                                <small class="text-muted">Type: {{ ucfirst($customer->type) }}</small>
                            </td>
                            <td>
                                <div class="text-dark">{{ $customer->phone }}</div>
                                @if($customer->email)
                                <small class="text-muted">{{ $customer->email }}</small>
                                @endif
                            </td>
                            <td class="text-center">
                                {{ $customer->user->name ?? '-' }}
                            </td>
                            <td class="text-center">
                                <span class="badge bg-warning">{{ $dueInvoices }}</span>
                            </td>
                            <td class="text-end">
                                <span class="fw-bold text-danger">Rs.{{ number_format($totalDue, 2) }}</span>
                                <br>
                                <small class="text-muted">(Adjusted for returns)</small>
                            </td>
                            <td class="text-end pe-4">
                                <button class="btn btn-primary btn-sm" wire:click="selectCustomer({{ $customer->id }})">
                                    <i class="bi bi-credit-card me-1"></i> Receive Payment
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="bi bi-person-check display-4 d-block mb-2"></i>
                                No customers found with due payments.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($customers->hasPages())
            <div class="card-footer">
                <div class="d-flex justify-content-center">
                    {{ $customers->links('livewire.custom-pagination') }}
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Due Invoices and Payment Allocation --}}
    @if($selectedCustomer && count($customerSales) > 0)
    <div class="row">
        {{-- Due Invoices List --}}
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header bg-warning text-white d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">
                        <i class="bi bi-receipt me-2"></i> Due Invoices - {{ $selectedCustomer->name }}
                    </h5>
                    <div>
                        @if(count($selectedInvoices) > 0)
                            <button class="btn btn-light btn-sm me-2" wire:click="clearInvoiceSelection">
                                <i class="bi bi-x-circle me-1"></i> Clear Selection
                            </button>
                        @endif
                        <button class="btn btn-light btn-sm" wire:click="selectAllInvoices">
                            <i class="bi bi-check-all me-1"></i> Select All
                        </button>
                    </div>
                </div>
                <div class="card-body p-0 overflow-auto">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" width="50">
                                        <i class="bi bi-check-square"></i>
                                    </th>
                                    <th>Invoice</th>
                                    <th class="text-center">Date</th>
                                    <th class="text-end">Total Amount</th>
                                    <th class="text-end">Paid</th>
                                    <th class="text-end">Due Amount</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($customerSales as $sale)
                                @php
                                    $isSelected = in_array($sale['id'], $selectedInvoices);
                                @endphp
                                <tr 
                                    wire:key="sale-{{ $sale['id'] }}" 
                                    class="{{ $isSelected ? 'table-success' : 'table-warning' }}"
                                    style="cursor: pointer;"
                                    wire:click="toggleInvoiceSelection({{ $sale['id'] }})">
                                    <td class="text-center">
                                        <div class="form-check d-flex justify-content-center">
                                            <input 
                                                class="form-check-input" 
                                                type="checkbox" 
                                                {{ $isSelected ? 'checked' : '' }}
                                                style="pointer-events: none;">
                                        </div>
                                    </td>
                                    <td class="ps-4">
                                        <div class="fw-bold text-primary">{{ $sale['invoice_number'] }}</div>
                                        <small class="text-muted">#{{ $sale['sale_id'] }}</small>
                                    </td>
                                    <td class="text-center">{{ $sale['sale_date'] }}</td>
                                    <td class="text-end fw-bold">
                                        Rs.{{ number_format($sale['total_amount'], 2) }}
                                    </td>
                                    <td class="text-end text-success">Rs.{{ number_format($sale['paid_amount'], 2) }}</td>
                                    <td class="text-end fw-bold {{ $isSelected ? 'text-success' : 'text-danger' }}">
                                        Rs.{{ number_format($sale['due_amount'], 2) }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $sale['payment_status'] == 'partial' ? 'warning' : 'danger' }}">
                                            {{ ucfirst($sale['payment_status']) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-outline-info btn-sm"
                                            wire:click.stop="viewSale({{ $sale['id'] }})"
                                            title="View Invoice Details">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="7" class="text-end fw-bold ps-4">Total Due:</td>
                                    <td class="text-end fw-bold text-danger">Rs.{{ number_format($totalDueAmount, 2) }}</td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    @if(count($selectedInvoices) > 0)
                        <div class="card-footer bg-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-semibold">
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    {{ count($selectedInvoices) }} invoice(s) selected
                                </span>
                                <span class="fw-bold text-success">
                                    Total Due: Rs.{{ number_format($totalDueAmount, 2) }}
                                </span>
                            </div>
                        </div>
                    @else
                        <div class="card-footer bg-light text-center text-muted">
                            <i class="bi bi-hand-index me-2"></i>
                            Click on rows to select invoices for payment
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Payment Allocation --}}
        <div class="col-lg-4">
            <div class="card sticky-top" style="top: 20px;">
                <div class="card-header bg-success text-white">
                    <h5 class="fw-bold mb-0">
                        <i class="bi bi-cash-coin me-2"></i> Payment Allocation
                    </h5>
                </div>
                <div class="card-body">
                    @if(count($selectedInvoices) > 0)
                        {{-- Show total due at the top --}}
                        <div class="alert alert-info mb-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-semibold">Total Due (Selected):</span>
                                <span class="fw-bold fs-5 text-danger">Rs.{{ number_format($totalDueAmount, 2) }}</span>
                            </div>
                            <small class="text-muted d-block mt-2">
                                <i class="bi bi-info-circle me-1"></i>
                                {{ count($selectedInvoices) }} invoice(s) selected for payment
                            </small>
                        </div>

                        {{-- Payment Amount Field --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Enter Payment Amount <span class="text-danger">*</span></label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text">Rs.</span>
                                <input
                                    type="number"
                                    min="0.01"
                                    step="0.01"
                                    class="form-control"
                                    wire:model.live="totalPaymentAmount"
                                    placeholder="0.00">
                            </div>
                            <small class="text-muted">Total Due: Rs.{{ number_format($totalDueAmount, 2) }}</small>
                            @error('totalPaymentAmount')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Remaining / Overpaid Amount Display --}}
                        @if($totalPaymentAmount > 0)
                            @if($overpaidAmount > 0)
                            <div class="alert alert-info mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-semibold">Overpaid Amount:</span>
                                    <span class="fw-bold text-primary">Rs.{{ number_format($overpaidAmount, 2) }}</span>
                                </div>
                                <small class="text-muted d-block mt-1">
                                    <i class="bi bi-info-circle me-1"></i>
                                    This amount will be added to customer's overpaid balance.
                                </small>
                            </div>
                            @elseif($remainingAmount > 0)
                            <div class="alert alert-warning mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-semibold">Remaining Due:</span>
                                    <span class="fw-bold">Rs.{{ number_format($remainingAmount, 2) }}</span>
                                </div>
                            </div>
                            @else
                            <div class="alert alert-success mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-semibold">Remaining Due:</span>
                                    <span class="fw-bold">Rs.{{ number_format($remainingAmount, 2) }}</span>
                                </div>
                            </div>
                            @endif
                        @endif

                        {{-- Process Payment Button --}}
                        <div class="d-grid mt-3">
                            <button
                                class="btn btn-success btn-lg"
                                wire:click="openPaymentModal"
                                wire:loading.attr="disabled"
                                wire:loading.class="disabled"
                                @if($totalPaymentAmount <= 0) disabled @endif>
                                <span wire:loading.remove wire:target="openPaymentModal">
                                    <i class="bi bi-cash-coin me-2"></i>
                                    Process Payment
                                    @if($totalPaymentAmount > 0)
                                    <span class="ms-1">(Rs.{{ number_format($totalPaymentAmount, 2) }})</span>
                                    @endif
                                </span>
                                <span wire:loading wire:target="openPaymentModal">
                                    <span class="spinner-border spinner-border-sm me-1"></span>
                                    Processing...
                                </span>
                            </button>
                        </div>

                        {{-- Quick Payment Options --}}
                        <div class="mt-3">
                            <small class="text-muted d-block mb-2">Quick Options:</small>
                            <div class="d-grid gap-2">
                                <button
                                    class="btn btn-outline-primary btn-sm"
                                    wire:click="$set('totalPaymentAmount', {{ $totalDueAmount }})">
                                    Pay Full Amount (Rs.{{ number_format($totalDueAmount, 2) }})
                                </button>
                            </div>
                        </div>

                        {{-- Payment Note --}}
                        <div class="alert alert-light border mt-3 mb-0">
                            <small class="text-muted">
                                <i class="bi bi-info-circle me-1"></i>
                                Payment will be automatically allocated to selected invoices.
                            </small>
                        </div>
                    @else
                        <div class="alert alert-warning text-center">
                            <i class="bi bi-exclamation-triangle-fill display-4 d-block mb-3"></i>
                            <h6 class="fw-bold mb-2">No Invoices Selected</h6>
                            <p class="mb-0 small">Please select at least one invoice from the list to make a payment.</p>
                        </div>
                        <div class="d-grid">
                            <button class="btn btn-secondary btn-lg" disabled>
                                <i class="bi bi-lock me-1"></i> Select Invoices First
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @elseif($selectedCustomer && count($customerSales) == 0)
    <div class="alert alert-success">
        <div class="d-flex align-items-center">
            <i class="bi bi-check-circle-fill fs-2 me-3"></i>
            <div>
                <h5 class="mb-1">No Pending Payments</h5>
                <p class="mb-0">{{ $selectedCustomer->name }} has no pending invoices. All invoices are fully paid (including adjustments for returns).</p>
            </div>
        </div>
    </div>
    @endif

    {{-- View Sale Modal --}}
    @if($showViewModal && $selectedSale)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-eye me-2"></i> Invoice Details - {{ $selectedSale->invoice_number }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeViewModal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">INVOICE INFORMATION</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td width="40%"><strong>Invoice Number:</strong></td>
                                    <td>{{ $selectedSale->invoice_number }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Sale ID:</strong></td>
                                    <td>{{ $selectedSale->sale_id }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Date & Time:</strong></td>
                                    <td>{{ $selectedSale->created_at->format('M d, Y h:i A') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Total Amount:</strong></td>
                                    <td class="fw-bold">Rs.{{ number_format($selectedSale->total_amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Due Amount:</strong></td>
                                    <td class="fw-bold text-danger">Rs.{{ number_format($selectedSale->due_amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Payment Status:</strong></td>
                                    <td>
                                        <span class="badge bg-{{ $selectedSale->payment_status == 'paid' ? 'success' : ($selectedSale->payment_status == 'partial' ? 'warning' : 'danger') }}">
                                            {{ ucfirst($selectedSale->payment_status) }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">CUSTOMER INFORMATION</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td width="40%"><strong>Name:</strong></td>
                                    <td>{{ $selectedSale->customer->name ?? 'Walk-in Customer' }}</td>
                                </tr>
                                @if($selectedSale->customer)
                                <tr>
                                    <td><strong>Phone:</strong></td>
                                    <td>{{ $selectedSale->customer->phone }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Type:</strong></td>
                                    <td>{{ ucfirst($selectedSale->customer_type) }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>

                    <h6 class="text-muted mb-3">ITEMS SOLD</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Product</th>
                                    <th class="text-center">Code</th>
                                    <th class="text-center">Quantity</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($selectedSale->items as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $item->product_name }}</td>
                                    <td class="text-center">{{ $item->product_code }}</td>
                                    <td class="text-center">{{ $item->quantity }}</td>
                                    <td class="text-end">Rs.{{ number_format($item->unit_price, 2) }}</td>
                                    <td class="text-end">Rs.{{ number_format($item->total, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($selectedSale->returns && count($selectedSale->returns) > 0)
                    <h6 class="text-muted mb-3">RETURNED ITEMS</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered table-info">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Product</th>
                                    <th class="text-center">Return Quantity</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Return Amount</th>
                                    <th class="text-center">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($selectedSale->returns as $index => $return)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $return->product->name ?? 'N/A' }}</td>
                                    <td class="text-center">{{ $return->return_quantity }}</td>
                                    <td class="text-end">Rs.{{ number_format($return->selling_price, 2) }}</td>
                                    <td class="text-end fw-bold text-danger">Rs.{{ number_format($return->total_amount, 2) }}</td>
                                    <td class="text-center">{{ $return->created_at->format('M d, Y') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="4" class="text-end fw-bold">Total Returns:</td>
                                    <td class="text-end fw-bold text-danger">Rs.{{ number_format($selectedSale->returns->sum('total_amount'), 2) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeViewModal">Close</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Payment Modal --}}
    @if($showPaymentModal && $selectedCustomer)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-credit-card me-2"></i> Confirm Payment
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closePaymentModal"></button>
                </div>
                <div class="modal-body">
                    {{-- Customer Info --}}
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">CUSTOMER INFORMATION</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td width="40%"><strong>Name:</strong></td>
                                    <td>{{ $selectedCustomer?->name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Phone:</strong></td>
                                    <td>{{ $selectedCustomer?->phone }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Type:</strong></td>
                                    <td>{{ ucfirst($selectedCustomer?->type ?? 'N/A') }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">PAYMENT SUMMARY</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td width="50%"><strong>Total Due:</strong></td>
                                    <td class="text-end">Rs.{{ number_format($totalDueAmount, 2) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Amount Paid:</strong></td>
                                    <td class="text-end text-success fw-bold">Rs.{{ number_format($totalPaymentAmount, 2) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Remaining Due:</strong></td>
                                    <td class="text-end text-danger">Rs.{{ number_format($remainingAmount, 2) }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    {{-- Payment Details Form --}}
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('paymentData.payment_date') is-invalid @enderror" wire:model="paymentData.payment_date">
                            @error('paymentData.payment_date') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Payment Method <span class="text-danger">*</span></label>
                            <select class="form-select @error('paymentData.payment_method') is-invalid @enderror" wire:model.live="paymentData.payment_method">
                                <option value="cash">Cash</option>
                                <option value="cheque">Cheque</option>
                                <option value="bank_transfer">Bank Transfer</option>
                            </select>
                            @error('paymentData.payment_method') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        {{-- Cheque Details --}}
                        @if($paymentData['payment_method'] === 'cheque')
                        <div class="col-12">
                            <div class="border rounded p-3 bg-light">
                                <h6 class="fw-semibold mb-3 text-success">
                                    <i class="bi bi-receipt me-2"></i>Cheque Details
                                </h6>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Cheque Number <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('cheque.cheque_number') is-invalid @enderror"
                                            wire:model="cheque.cheque_number"
                                            placeholder="Enter cheque number">
                                        @error('cheque.cheque_number') <span class="text-danger small">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Bank Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('cheque.bank_name') is-invalid @enderror"
                                            wire:model="cheque.bank_name"
                                            placeholder="Enter bank name">
                                        @error('cheque.bank_name') <span class="text-danger small">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Cheque Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control @error('cheque.cheque_date') is-invalid @enderror"
                                            wire:model="cheque.cheque_date">
                                        @error('cheque.cheque_date') <span class="text-danger small">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        {{-- Bank Transfer Details --}}
                        @if($paymentData['payment_method'] === 'bank_transfer')
                        <div class="col-12">
                            <div class="border rounded p-3 bg-light">
                                <h6 class="fw-semibold mb-3 text-primary">
                                    <i class="bi bi-bank me-2"></i>Bank Transfer Details
                                </h6>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Bank Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('bankTransfer.bank_name') is-invalid @enderror"
                                            wire:model="bankTransfer.bank_name"
                                            placeholder="Enter bank name">
                                        @error('bankTransfer.bank_name') <span class="text-danger small">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Transfer Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control @error('bankTransfer.transfer_date') is-invalid @enderror"
                                            wire:model="bankTransfer.transfer_date">
                                        @error('bankTransfer.transfer_date') <span class="text-danger small">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Reference Number <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('bankTransfer.reference_number') is-invalid @enderror"
                                            wire:model="bankTransfer.reference_number"
                                            placeholder="Transaction reference">
                                        @error('bankTransfer.reference_number') <span class="text-danger small">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        {{-- Optional Reference (Always visible) --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Payment Reference (Optional)</label>
                            <input type="text" class="form-control @error('paymentData.reference_number') is-invalid @enderror"
                                wire:model="paymentData.reference_number"
                                placeholder="Enter payment reference if any">
                            @error('paymentData.reference_number') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        {{-- Notes (Always visible) --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Notes (Optional)</label>
                            <textarea class="form-control @error('paymentData.notes') is-invalid @enderror"
                                rows="1"
                                wire:model="paymentData.notes"
                                placeholder="Additional notes"></textarea>
                            @error('paymentData.notes') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- Allocation Breakdown --}}
                    <h6 class="text-muted mt-4 mb-3">PAYMENT ALLOCATION BREAKDOWN</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Invoice</th>
                                    <th class="text-end">Due Amount</th>
                                    <th class="text-end">Payment Amount</th>
                                    <th class="text-end">Remaining</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($allocations as $allocation)
                                @if($allocation['payment_amount'] > 0)
                                <tr>
                                    <td class="fw-bold">{{ $allocation['invoice_number'] }}</td>
                                    <td class="text-end">Rs.{{ number_format($allocation['due_amount'], 2) }}</td>
                                    <td class="text-end text-success fw-bold">Rs.{{ number_format($allocation['payment_amount'], 2) }}</td>
                                    <td class="text-end text-danger">Rs.{{ number_format($allocation['due_amount'] - $allocation['payment_amount'], 2) }}</td>
                                    <td>
                                        @php
                                            $remaining = $allocation['due_amount'] - $allocation['payment_amount'];
                                        @endphp
                                        @if($remaining == 0)
                                            <span class="badge bg-success">Complete</span>
                                        @elseif($allocation['payment_amount'] > 0 && $remaining > 0)
                                            <span class="badge bg-warning text-dark">Partial Paid</span>
                                        @else
                                            <span class="badge bg-danger">Payment Pending</span>
                                        @endif
                                    </td>
                                </tr>
                                @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($overpaidAmount > 0)
                    <div class="alert alert-info mt-3">
                        <i class="bi bi-wallet2 me-2"></i>
                        <strong>Overpayment: Rs.{{ number_format($overpaidAmount, 2) }}</strong> will be added to customer's overpaid balance.
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closePaymentModal">Cancel</button>
                    <button
                        type="button"
                        class="btn btn-success"
                        wire:click="processPayment"
                        wire:loading.attr="disabled"
                        wire:loading.class="disabled">
                        <span wire:loading.remove wire:target="processPayment">
                            Confirm & Save Payment
                        </span>
                        <span wire:loading wire:target="processPayment">
                            <span class="spinner-border spinner-border-sm me-1"></span> Processing...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Receipt Modal --}}
    @if($showReceiptModal && $latestPayment)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-check-circle me-2"></i> Payment Successful!
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeReceiptModal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <i class="bi bi-check-circle-fill text-success display-1"></i>
                        <h3 class="text-success mt-3">Payment Processed Successfully!</h3>
                        <p class="text-muted">Payment receipt has been generated and saved.</p>
                    </div>

                    <div class="card border-success">
                        <div class="card-header bg-success text-white">
                            <h6 class="fw-bold mb-0">
                                <i class="bi bi-receipt me-2"></i> Payment Receipt
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>Receipt ID:</strong></td>
                                            <td>#{{ $latestPayment->id }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Payment Date:</strong></td>
                                            <td>{{ \Carbon\Carbon::parse($latestPayment->payment_date)->format('M d, Y') }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Customer:</strong></td>
                                            <td>{{ $selectedCustomer->name }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Phone:</strong></td>
                                            <td>{{ $selectedCustomer->phone }}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>Payment Method:</strong></td>
                                            <td class="text-capitalize">{{ str_replace('_', ' ', $latestPayment->payment_method) }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Amount Paid:</strong></td>
                                            <td class="fw-bold text-success">Rs.{{ number_format($latestPayment->amount, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Reference No:</strong></td>
                                            <td>{{ $latestPayment->payment_reference ?: 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Received By:</strong></td>
                                            <td>{{ Auth::user()->name }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            @if($latestPayment->notes)
                            <div class="mt-3">
                                <strong>Notes:</strong>
                                <p class="mb-0">{{ $latestPayment->notes }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeReceiptModal">
                        <i class="bi bi-x-circle me-1"></i> Close
                    </button>
                    <button type="button" class="btn btn-success" wire:click="downloadReceipt">
                        <i class="bi bi-download me-1"></i> Download Receipt
                    </button>
                    <button type="button" class="btn btn-primary" wire:click="closeReceiptModal">
                        <i class="bi bi-check-circle me-1"></i> Done
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@push('styles')
<style>
    .sticky-top {
        position: sticky;
        z-index: 10;
    }

    .table th {
        font-weight: 600;
    }

    .badge {
        font-size: 0.75em;
    }

    .modal.show {
        display: block !important;
    }

    .btn-group-sm>.btn {
        padding: 0.25rem 0.5rem;
    }

    .input-group-lg .form-control {
        font-size: 1.25rem;
        font-weight: 600;
    }

    /* Invoice selection styles */
    .table tbody tr[style*="cursor: pointer"]:hover {
        background-color: rgba(13, 110, 253, 0.1);
    }

    .table tbody tr.table-success {
        background-color: rgba(25, 135, 84, 0.15) !important;
    }

    .table tbody tr.table-success:hover {
        background-color: rgba(25, 135, 84, 0.25) !important;
    }

    .form-check-input {
        width: 1.2em;
        height: 1.2em;
        cursor: pointer;
    }
</style>
@endpush

@push('scripts')
<script>
    // Listen for toast notifications
    window.addEventListener('show-toast', event => {
        const data = event.detail[0] || event.detail;
        const type = data.type || 'info';
        const message = data.message || 'Notification';

        // Create toast element
        const toastId = 'toast-' + Date.now();
        const toastHtml = `
        <div id="${toastId}" class="toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;

        // Add toast to container
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
        }

        container.insertAdjacentHTML('beforeend', toastHtml);

        // Show toast
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, {
            autohide: true,
            delay: 5000
        });
        toast.show();

        // Remove toast element after it's hidden
        toastElement.addEventListener('hidden.bs.toast', function() {
            toastElement.remove();
        });
    });

    // Listen for payment completed event and refresh page
    window.addEventListener('payment-completed', event => {
        console.log('Payment completed, refreshing page...');
        // Small delay to allow modal to close smoothly
        setTimeout(() => {
            window.location.reload();
        }, 500);
    });

    // Log when payment modal is opened
    Livewire.on('paymentModalOpened', () => {
        console.log('Payment modal opened');
    });
</script>
@endpush