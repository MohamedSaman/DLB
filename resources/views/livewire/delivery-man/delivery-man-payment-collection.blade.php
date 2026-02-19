<div class="container-fluid py-3">
    {{-- Header --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold text-dark mb-2">
                <i class="bi bi-cash-stack text-success me-2"></i> Payment Collection
            </h3>
            <p class="text-muted mb-0">Collect payments directly from customers</p>
        </div>
        <a href="{{ route('delivery.dashboard') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i> Back to Dashboard
        </a>
    </div>

    {{-- Pending Payments Alert --}}
    @if($pendingPayments->count() > 0)
    <div class="card border-0 shadow-sm bg-warning bg-opacity-10 mb-4">
        <div class="card-header bg-warning text-dark">
            <h6 class="mb-0"><i class="bi bi-hourglass-split me-2"></i>Your Pending Payment Approvals ({{ $pendingPayments->count() }})</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Customer</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Collected At</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pendingPayments as $payment)
                        <tr>
                            <td class="ps-3">{{ $payment->customer->name ?? 'N/A' }}</td>
                            <td class="fw-semibold">Rs. {{ number_format($payment->amount, 2) }}</td>
                            <td><span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</span></td>
                            <td class="text-muted">{{ $payment->collected_at?->format('M d, Y h:i A') }}</td>
                            <td><span class="badge bg-warning">Pending Approval</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- Search --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row">
                @if(!$selectedCustomer)
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Search by customer name or phone...">
                    </div>
                </div>
                @else
                <div class="col-md-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0 text-primary">
                                <i class="bi bi-person-check-fill me-2"></i>
                                Selected Customer: <strong>{{ $selectedCustomer->name }}</strong>
                            </h5>
                            <small class="text-muted">
                                <i class="bi bi-telephone me-1"></i>{{ $selectedCustomer->phone ?? 'N/A' }}
                                @if($selectedCustomer->address)
                                <span class="ms-3"><i class="bi bi-geo-alt me-1"></i>{{ $selectedCustomer->address }}</span>
                                @endif
                            </small>
                        </div>
                        <button class="btn btn-outline-secondary" wire:click="clearSelectedCustomer">
                            <i class="bi bi-x-circle me-1"></i> Clear Selection
                        </button>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Customer List --}}
    @if(!$selectedCustomer)
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h6 class="fw-bold mb-0"><i class="bi bi-people me-2"></i>Customers with Outstanding Dues</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Customer</th>
                            <th>Contact</th>
                            <th>Due Invoices</th>
                            <th>Total Due</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $customer)
                        @php
                        $dueInvoices = $customer->sales->whereIn('payment_status', ['pending', 'partial'])->count();
                        $salesDue = $customer->sales->sum(function($sale) {
                            $returnAmount = $sale->returns ? $sale->returns->sum('total_amount') : 0;
                            return max(0, $sale->due_amount - $returnAmount);
                        });
                        $totalDue = ($customer->opening_balance ?? 0) + $salesDue;
                        // Subtract pending (unapproved) payment allocations
                        $pendingAmount = $pendingAllocationsPerCustomer[$customer->id] ?? 0;
                        $effectiveDue = max(0, $totalDue - $pendingAmount);
                        @endphp
                        @if($effectiveDue > 0.01)
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark">{{ $customer->name }}</div>
                                <small class="text-muted">Type: Distributor</small>
                            </td>
                            <td>
                                <div class="text-dark">{{ $customer->phone }}</div>
                                @if($customer->email)
                                <small class="text-muted">{{ $customer->email }}</small>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge bg-warning">{{ $dueInvoices }}</span>
                                @if($customer->opening_balance > 0)
                                <span class="badge bg-info ms-1">+ OB</span>
                                @endif
                            </td>
                            <td>
                                <span class="fw-bold text-danger">Rs. {{ number_format($effectiveDue, 2) }}</span>
                                @if($pendingAmount > 0)
                                <small class="d-block text-warning"><i class="bi bi-hourglass-split"></i> Rs. {{ number_format($pendingAmount, 2) }} pending</small>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <button wire:click="selectCustomer({{ $customer->id }})" class="btn btn-sm btn-primary">
                                    <i class="bi bi-cash me-1"></i> Collect Payment
                                </button>
                            </td>
                        </tr>
                        @endif
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-check-circle fs-1 text-success d-block mb-2"></i>
                                No outstanding dues! All payments collected.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">
        {{ $customers->links() }}
    </div>
    @endif

    {{-- Due Invoices and Payment Allocation --}}
    @if($selectedCustomer && count($customerSales) > 0)
    <div class="row">
        {{-- Due Invoices List --}}
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header bg-warning text-white">
                    <h5 class="fw-bold mb-0">
                        <i class="bi bi-receipt me-2"></i> Due Invoices - {{ $selectedCustomer->name }}
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4" style="width: 40px;">
                                        <input type="checkbox" class="form-check-input" 
                                            wire:click="selectAllInvoices"
                                            @if(count($selectedInvoices) == count($customerSales)) checked @endif>
                                    </th>
                                    <th>Invoice</th>
                                    <th class="text-end">Total Amount</th>
                                    <th class="text-end">Due Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($customerSales as $sale)
                                <tr style="cursor: pointer;" 
                                    wire:click="toggleInvoiceSelection('{{ $sale['id'] }}')"
                                    class="@if(in_array($sale['id'], $selectedInvoices)) table-success @endif">
                                    <td class="ps-4">
                                        <input type="checkbox" class="form-check-input"
                                            @if(in_array($sale['id'], $selectedInvoices)) checked @endif>
                                    </td>
                                    <td>
                                        <span class="fw-medium">{{ $sale['invoice_number'] }}</span>
                                        @if(isset($sale['is_opening_balance']) && $sale['is_opening_balance'])
                                        <span class="badge bg-info ms-1">Opening Balance</span>
                                        @else
                                        <small class="d-block text-muted">{{ $sale['sale_date'] }}</small>
                                        @endif
                                    </td>
                                    <td class="text-end">Rs. {{ number_format($sale['total_amount'], 2) }}</td>
                                    <td class="text-end fw-bold text-danger">Rs. {{ number_format($sale['due_amount'], 2) }}</td>
                                    <td>
                                        @if($sale['payment_status'] === 'pending')
                                        <span class="badge bg-warning">Pending</span>
                                        @else
                                        <span class="badge bg-info">Partial</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            @if(count($selectedInvoices) > 0)
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="3" class="text-end fw-bold">Total Selected Due:</td>
                                    <td class="text-end fw-bold text-danger">Rs. {{ number_format($totalDueAmount, 2) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Payment Allocation --}}
        <div class="col-lg-4">
            <div class="card sticky-top" style="top: 20px;">
                <div class="card-header bg-success text-white">
                    <h5 class="fw-bold mb-0">
                        <i class="bi bi-cash-coin me-2"></i> Payment Collection
                    </h5>
                </div>
                <div class="card-body">
                    @if(count($selectedInvoices) > 0)
                        <div class="alert alert-info mb-4">
                            <div class="text-center">
                                <small class="text-muted d-block">TOTAL DUE AMOUNT</small>
                                <span class="fw-bold fs-5 text-danger">Rs. {{ number_format($totalDueAmount, 2) }}</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Payment Amount</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text">Rs.</span>
                                <input type="number" 
                                    wire:model.live="totalPaymentAmount" 
                                    class="form-control" 
                                    min="0" 
                                    step="0.01" 
                                    placeholder="0.00">
                            </div>
                            <small class="text-muted">Total Due: Rs. {{ number_format($totalDueAmount, 2) }}</small>
                        </div>

                        @if($totalPaymentAmount > 0)
                            @if($overpaidAmount > 0)
                            <div class="alert alert-info mb-3">
                                <small class="text-muted d-block">Overpaid Amount:</small>
                                <span class="fw-bold text-primary">Rs. {{ number_format($overpaidAmount, 2) }}</span>
                                <small class="text-muted d-block mt-1">
                                    <i class="bi bi-info-circle me-1"></i>
                                    This amount will be added to customer's overpaid balance.
                                </small>
                            </div>
                            @else
                            <div class="alert alert-{{ $remainingAmount > 0 ? 'warning' : 'success' }} mb-3">
                                <small class="text-muted d-block">Remaining Due After Payment:</small>
                                <span class="fw-bold">Rs. {{ number_format($remainingAmount, 2) }}</span>
                            </div>
                            @endif
                        @endif

                        <div class="d-grid mt-3">
                            <button 
                                class="btn btn-success btn-lg"
                                wire:click="openCollectModal"
                                @if($totalPaymentAmount <= 0) disabled @endif>
                                <i class="bi bi-check-circle me-2"></i>Proceed to Collect
                            </button>
                        </div>

                        <div class="mt-3">
                            <button class="btn btn-outline-primary btn-sm w-100"
                                wire:click="$set('totalPaymentAmount', {{ $totalDueAmount }})">
                                Pay Full Amount (Rs. {{ number_format($totalDueAmount, 2) }})
                            </button>
                        </div>
                    @else
                        <div class="alert alert-warning text-center">
                            <i class="bi bi-exclamation-triangle fs-3 d-block mb-2"></i>
                            Select at least one invoice to proceed
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
                <p class="mb-0">{{ $selectedCustomer->name }} has no pending dues.</p>
            </div>
        </div>
    </div>
    @endif

    {{-- Payment Collection Modal --}}
    @if($showCollectModal && $selectedCustomer)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-cash me-2"></i>Confirm Payment Collection
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeCollectModal"></button>
                </div>
                <div class="modal-body">
                    {{-- Customer Info --}}
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="bg-light rounded p-3">
                                <small class="text-muted d-block">Customer</small>
                                <span class="fw-bold">{{ $selectedCustomer->name }}</span>
                                <br>
                                <small class="text-muted">{{ $selectedCustomer->phone }}</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="bg-light rounded p-3">
                                <small class="text-muted d-block">Total Collection</small>
                                <span class="fw-bold fs-5 text-success">Rs. {{ number_format($totalPaymentAmount, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Payment Details Form --}}
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Payment Date</label>
                            <input type="date" wire:model="paymentData.payment_date" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Payment Method</label>
                            <select wire:model.live="paymentData.payment_method" class="form-select">
                                <option value="cash">Cash</option>
                                <option value="cheque">Cheque</option>
                                <option value="bank_transfer">Bank Transfer</option>
                            </select>
                        </div>

                        {{-- Cheque Details --}}
                        @if($paymentData['payment_method'] === 'cheque')
                        <div class="col-12">
                            <div class="card border-info">
                                <div class="card-header bg-info text-white">Cheque Details</div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">Cheque Number*</label>
                                            <input type="text" wire:model="cheque.cheque_number" class="form-control" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Bank Name*</label>
                                            <input type="text" wire:model="cheque.bank_name" class="form-control" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Cheque Date*</label>
                                            <input type="date" wire:model="cheque.cheque_date" class="form-control" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        {{-- Bank Transfer Details --}}
                        @if($paymentData['payment_method'] === 'bank_transfer')
                        <div class="col-12">
                            <div class="card border-primary">
                                <div class="card-header bg-primary text-white">Bank Transfer Details</div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">Bank Name*</label>
                                            <input type="text" wire:model="bankTransfer.bank_name" class="form-control" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Transfer Date*</label>
                                            <input type="date" wire:model="bankTransfer.transfer_date" class="form-control" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Reference Number*</label>
                                            <input type="text" wire:model="bankTransfer.reference_number" class="form-control" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <div class="col-md-6">
                            <label class="form-label fw-medium">Reference (Optional)</label>
                            <input type="text" wire:model="paymentData.reference_number" class="form-control" placeholder="Transaction reference">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium">Notes (Optional)</label>
                            <textarea wire:model="paymentData.notes" class="form-control" rows="1" placeholder="Payment notes"></textarea>
                        </div>
                    </div>

                    {{-- Allocation Breakdown --}}
                    <h6 class="text-muted mt-4 mb-3">PAYMENT ALLOCATION BREAKDOWN</h6>
                    
                    @if(empty($allocations))
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        No allocations found. Please close and try again.
                    </div>
                    @else
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Invoice</th>
                                    <th class="text-end">Due</th>
                                    <th class="text-end">Paying</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($allocations as $allocation)
                                <tr>
                                    <td class="fw-medium">
                                        {{ $allocation['invoice_number'] }}
                                        @if($allocation['is_opening_balance'])
                                        <span class="badge bg-info ms-1">Opening Balance</span>
                                        @endif
                                    </td>
                                    <td class="text-end">Rs. {{ number_format($allocation['due_amount'], 2) }}</td>
                                    <td class="text-end fw-bold text-success">Rs. {{ number_format($allocation['payment_amount'], 2) }}</td>
                                    <td>
                                        @if($allocation['is_fully_paid'])
                                        <span class="badge bg-success">Full Payment</span>
                                        @else
                                        <span class="badge bg-warning">Partial</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif

                    <div class="alert alert-success mt-3">
                        <i class="bi bi-check-circle me-2"></i>
                        This payment will be processed immediately and due amounts will be reduced.
                    </div>

                    @if($overpaidAmount > 0)
                    <div class="alert alert-info mt-2">
                        <i class="bi bi-wallet2 me-2"></i>
                        <strong>Overpayment: Rs. {{ number_format($overpaidAmount, 2) }}</strong> will be added to customer's overpaid balance.
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeCollectModal">Cancel</button>
                    <button type="button" class="btn btn-success" wire:click="collectPayment" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="collectPayment">
                            <i class="bi bi-check-circle me-2"></i>Collect Payment
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
    window.addEventListener('show-toast', event => {
        const data = event.detail;
        const type = data.type || 'info';
        const message = data.message || 'Notification';

        const toastId = 'toast-' + Date.now();
        const toastHtml = `
        <div id="${toastId}" class="toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;

        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
        }

        container.insertAdjacentHTML('beforeend', toastHtml);

        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, { autohide: true, delay: 5000 });
        toast.show();

        toastElement.addEventListener('hidden.bs.toast', function() {
            toastElement.remove();
        });
    });
</script>
@endpush
