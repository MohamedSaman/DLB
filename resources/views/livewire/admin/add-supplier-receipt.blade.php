{{-- Toast Alert --}}
<div>
    <div
        x-data="{ show: false, type: '', message: '' }"
        x-init="
            window.addEventListener('show-toast', e => {
                console.log('Toast event received:', e.detail);
                const data = Array.isArray(e.detail) ? e.detail[0] : e.detail;
                type = data.type || 'info';
                message = data.message || 'Notification';
                show = true;
                setTimeout(() => show = false, 4000);
            });
        "
        style="position: fixed; top: 24px; right: 24px; z-index: 9999; min-width: 350px;">
        <template x-if="show">
            <div :class="type === 'success' ? 'alert alert-success shadow-lg' : 'alert alert-danger shadow-lg'" class="fade show border-0" role="alert">
                <div class="d-flex align-items-center">
                    <i :class="type === 'success' ? 'bi bi-check-circle-fill me-2' : 'bi bi-exclamation-triangle-fill me-2'" style="font-size: 1.5rem;"></i>
                    <div class="flex-grow-1" x-text="message"></div>
                    <button type="button" class="btn-close ms-2" @click="show = false" aria-label="Close"></button>
                </div>
            </div>
        </template>
    </div>

    {{-- Payment Modal --}}
    @if($showPaymentModal && $selectedSupplier)
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
                    {{-- Supplier Info --}}
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">SUPPLIER INFORMATION</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td width="40%"><strong>Name:</strong></td>
                                    <td>{{ $selectedSupplier?->name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Mobile:</strong></td>
                                    <td>{{ $selectedSupplier?->mobile }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td>{{ $selectedSupplier?->email }}</td>
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
                                @if($overpaymentToApply > 0)
                                <tr>
                                    <td><strong>Overpayment Credit:</strong></td>
                                    <td class="text-end text-info fw-bold">- Rs.{{ number_format($overpaymentToApply, 2) }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <td><strong>Cash Payment:</strong></td>
                                    <td class="text-end text-success fw-bold">Rs.{{ number_format($totalPaymentAmount, 2) }}</td>
                                </tr>
                                <tr class="border-top">
                                    <td><strong>Total Payment:</strong></td>
                                    <td class="text-end text-primary fw-bold">Rs.{{ number_format($totalPaymentAmount + $overpaymentToApply, 2) }}</td>
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
                                <option value="others">Others</option>
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
                                        <label class="form-label fw-semibold">Transaction Date <span class="text-danger">*</span></label>
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
                    <div class="table-responsive overflow-auto">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Order Code</th>
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
                                    <td class="fw-bold">{{ $allocation['order_code'] }}</td>
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
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closePaymentModal">Cancel</button>
                    <button
                        type="button"
                        class="btn btn-success"
                        wire:click="processPayment"
                        wire:loading.attr="disabled"
                        wire:loading.class="disabled"
                        onclick="console.log('Process Payment Clicked')">
                        <span wire:loading.remove wire:target="processPayment">
                            Confirm & Save Payment
                        </span>
                        <span wire:loading wire:target="processPayment">
                            <span class="spinner-border spinner-border-sm"></span> Processing...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Order Details Modal --}}
    @if($showOrderDetailsModal && $selectedOrderForView)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-eye me-2"></i> Order Details - {{ $selectedOrderForView->order_code }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeOrderDetailsModal"></button>
                </div>
                <div class="modal-body">
                    {{-- Order Header --}}
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">ORDER INFORMATION</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td width="40%"><strong>Order Code:</strong></td>
                                    <td class="fw-bold">{{ $selectedOrderForView->order_code }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Order Date:</strong></td>
                                    <td>{{ $selectedOrderForView->order_date ? date('M d, Y', strtotime($selectedOrderForView->order_date)) : '-' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td>
                                        @if($selectedOrderForView->due_amount == 0)
                                            <span class="badge bg-success">Paid</span>
                                        @elseif($selectedOrderForView->due_amount > 0 && $selectedOrderForView->due_amount < $selectedOrderForView->total_amount)
                                            <span class="badge bg-warning text-dark">Partial Paid</span>
                                        @else
                                            <span class="badge bg-danger">Payment Pending</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">FINANCIAL SUMMARY</h6>
                            @php
                                $returnAmount = $selectedOrderForView->returns ? $selectedOrderForView->returns->sum('total_amount') : 0;
                            @endphp
                            <table class="table table-borderless">
                                <tr>
                                    <td width="50%"><strong>Total Amount:</strong></td>
                                    <td class="text-end fw-bold">Rs.{{ number_format($selectedOrderForView->total_amount, 2) }}</td>
                                </tr>
                                @if($returnAmount > 0)
                                <tr>
                                    <td><strong>Return Amount:</strong></td>
                                    <td class="text-end text-info fw-bold">- Rs.{{ number_format($returnAmount, 2) }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <td><strong>Paid Amount:</strong></td>
                                    <td class="text-end text-success">Rs.{{ number_format($selectedOrderForView->total_amount - $selectedOrderForView->due_amount - $returnAmount, 2) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Due Amount:</strong></td>
                                    <td class="text-end text-danger fw-bold">Rs.{{ number_format($selectedOrderForView->due_amount, 2) }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    {{-- Order Items --}}
                    <h6 class="text-muted mb-3">ORDER ITEMS</h6>
                    <div class="table-responsive overflow-auto" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-bordered mb-0">
                            <thead class="table-light position-sticky top-0" style="z-index: 1020;">
                                <tr>
                                    <th>Product</th>
                                    <th class="text-center">Received Quantity</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($selectedOrderForView->items as $item)
                                <tr>
                                    <td class="fw-bold" title="{{ $this->formatProductName($item->product ?? null, $item->unit_price ?? null) }}">{{ $this->formatProductName($item->product ?? null, $item->unit_price ?? null) }}</td>
                                    <td class="text-center">{{ $item->received_quantity }}</td>
                                    <td class="text-end">Rs.{{ number_format($item->unit_price, 2) }}</td>
                                    <td class="text-end fw-bold">Rs.{{ number_format($item->received_quantity * $item->unit_price, 2) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">No items found for this order.</td>
                                </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="3" class="text-end fw-bold">Total:</td>
                                    <td class="text-end fw-bold text-primary">Rs.{{ number_format($selectedOrderForView->total_amount, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    {{-- Returns Section --}}
                    @if($selectedOrderForView->returns && $selectedOrderForView->returns->count() > 0)
                    <h6 class="text-muted mt-4 mb-3">
                        <i class="bi bi-arrow-return-left me-1"></i> PRODUCT RETURNS
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th class="text-center">Qty Returned</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Return Amount</th>
                                    <th>Reason</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($selectedOrderForView->returns as $return)
                                <tr>
                                    <td class="fw-bold">{{ $return->product->name ?? 'N/A' }}</td>
                                    <td class="text-center">{{ $return->return_quantity }}</td>
                                    <td class="text-end">Rs.{{ number_format($return->unit_price, 2) }}</td>
                                    <td class="text-end text-info fw-bold">Rs.{{ number_format($return->total_amount, 2) }}</td>
                                    <td><span class="badge bg-warning text-dark">{{ ucfirst($return->return_reason) }}</span></td>
                                    <td>{{ $return->created_at ? $return->created_at->format('M d, Y') : '-' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="3" class="text-end fw-bold">Total Return:</td>
                                    <td class="text-end fw-bold text-info">Rs.{{ number_format($selectedOrderForView->returns->sum('total_amount'), 2) }}</td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    @endif

                    {{-- Payment History --}}
                    @if($selectedOrderForView->payments && $selectedOrderForView->payments->count() > 0)
                    <h6 class="text-muted mt-4 mb-3">PAYMENT HISTORY</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Payment Date</th>
                                    <th>Method</th>
                                    <th class="text-end">Amount</th>
                                    <th>Reference</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($selectedOrderForView->payments as $payment)
                                <tr>
                                    <td>{{ $payment->payment_date ? date('M d, Y', strtotime($payment->payment_date)) : '-' }}</td>
                                    <td><span class="badge bg-info">{{ ucfirst($payment->payment_method) }}</span></td>
                                    <td class="text-end text-success fw-bold">Rs.{{ number_format($payment->amount, 2) }}</td>
                                    <td>{{ $payment->payment_reference ?? '-' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeOrderDetailsModal">Close</button>
                    @if($selectedOrderForView->due_amount > 0)
                    <button type="button" class="btn btn-primary" wire:click="closeOrderDetailsModal">
                        <i class="bi bi-credit-card me-1"></i> Make Payment
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

   {{-- Payment Receipt Modal --}}
@if($showReceiptModal && $lastPayment)
<div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-body p-0">
                {{-- Success Message --}}
                <div class="bg-success text-white p-4 text-center">
                    <i class="bi bi-check-circle display-4 d-block mb-3"></i>
                    <h4 class="mb-2">Payment Successful!</h4>
                    <p class="mb-0">Payment Processed Successfully!</p>
                    <p class="mb-0">Payment receipt has been generated and saved.</p>
                </div>

                {{-- Receipt Content --}}
                <div class="p-4" id="receipt-content">
                    <div class="text-center mb-4">
                        <h4 class="fw-bold">Payment Receipt</h4>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Receipt ID:</strong> #{{ $lastPayment->id }}</p>
                            <p class="mb-1"><strong>Payment Date:</strong> {{ date('M d, Y', strtotime($lastPayment->payment_date)) }}</p>
                            <p class="mb-1"><strong>Customer:</strong> {{ $lastPayment->supplier->name }}</p>
                            <p class="mb-0"><strong>Phone:</strong> {{ $lastPayment->supplier->mobile }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Payment Method:</strong> {{ ucfirst(str_replace('_', ' ', $lastPayment->payment_method)) }}</p>
                            @if($lastPayment->overpayment_used > 0)
                            <p class="mb-1"><strong>Overpayment Credit Used:</strong> <span class="text-info">Rs.{{ number_format($lastPayment->overpayment_used, 2) }}</span></p>
                            @endif
                            <p class="mb-1"><strong>Cash Paid:</strong> Rs.{{ number_format($lastPayment->amount, 2) }}</p>
                            <p class="mb-1"><strong>Total Payment:</strong> <span class="text-success fw-bold">Rs.{{ number_format($lastPayment->amount + ($lastPayment->overpayment_used ?? 0), 2) }}</span></p>
                            <p class="mb-1"><strong>Reference No:</strong> {{ $lastPayment->payment_reference ?? 'N/A' }}</p>
                            <p class="mb-0"><strong>Received By:</strong> Admin</p>
                        </div>
                    </div>

                    {{-- Allocated Orders --}}
                    <h6 class="fw-bold text-muted mb-3">PAYMENT ALLOCATION</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Order Code</th>
                                    <th class="text-end">Allocated Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($lastPayment->allocations as $allocation)
                                <tr>
                                    <td class="fw-bold">{{ $allocation->order->order_code }}</td>
                                    <td class="text-end text-success">Rs.{{ number_format($allocation->allocated_amount, 2) }}</td>
                                    <td>
                                        @if($allocation->order->due_amount == 0)
                                            <span class="badge bg-success">Fully Paid</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Partial Paid</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                @if($lastPayment->overpayment_used > 0)
                                <tr>
                                    <td class="fw-bold">Overpayment Credit</td>
                                    <td class="text-end fw-bold text-info">Rs.{{ number_format($lastPayment->overpayment_used, 2) }}</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Cash Payment</td>
                                    <td class="text-end fw-bold">Rs.{{ number_format($lastPayment->amount, 2) }}</td>
                                    <td></td>
                                </tr>
                                @endif
                                <tr>
                                    <td class="fw-bold">TOTAL</td>
                                    <td class="text-end fw-bold text-success">Rs.{{ number_format($lastPayment->amount + ($lastPayment->overpayment_used ?? 0), 2) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    @if($lastPayment->notes)
                    <div class="mt-3">
                        <h6 class="fw-bold text-muted">NOTES</h6>
                        <p class="mb-0">{{ $lastPayment->notes }}</p>
                    </div>
                    @endif

                    <div class="text-center mt-4 pt-3 border-top">
                        <p class="text-muted mb-0">Thank you for your payment!</p>
                        <small class="text-muted">Generated on {{ now()->format('M d, Y h:i A') }}</small>
                    </div>
                </div>
                
                {{-- Modal Footer --}}
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeReceiptModal">
                        Close
                    </button>
                    <button type="button" class="btn btn-success" wire:click="downloadReceipt">
                        <i class="bi bi-download me-1"></i> Download Receipt
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

    <div class="container-fluid py-4">
        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-2">
                    <i class="bi bi-receipt text-success me-2"></i> Supplier Payment Receipt
                </h3>
                <p class="text-muted mb-0">Record supplier payments and allocate to due purchase orders</p>
            </div>
        </div>

        {{-- Supplier Search --}}
<div class="card mb-4">
    <div class="card-header bg-light">
        <h5 class="fw-bold mb-0">
            <i class="bi bi-search me-2"></i> Find Supplier with Due Payments
        </h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            {{-- Search Input --}}
            <div class="col-md-6">
                <label class="form-label fw-semibold">Search Supplier</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-person"></i>
                    </span>
                    <input type="text" class="form-control"
                        placeholder="Search by supplier name, phone or email..."
                        wire:model.live="search">
                </div>
            </div>

            {{-- Selected Supplier --}}
            <div class="col-md-6">
                <label class="form-label fw-semibold">Selected Supplier</label>
                @if($selectedSupplier)
                <div class="border rounded p-3 bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fw-bold mb-1">{{ $selectedSupplier->name }}</h6>
                            <p class="mb-1 text-muted">
                                <i class="bi bi-telephone me-1"></i>{{ $selectedSupplier->mobile }}
                                @if($selectedSupplier->email)
                                | <i class="bi bi-envelope me-1"></i>{{ $selectedSupplier->email }}
                                @endif
                            </p>
                            @if($supplierOverpayment > 0)
                            <p class="mb-0 text-success fw-bold">
                                <i class="bi bi-wallet2 me-1"></i>Overpayment Credit: Rs.{{ number_format($supplierOverpayment, 2) }}
                            </p>
                            @endif
                        </div>
                        <button class="btn btn-outline-secondary btn-sm" wire:click="clearSelectedSupplier">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                </div>
                @else
                <div class="border rounded p-3 text-center text-muted">
                    <i class="bi bi-person-x display-6 d-block mb-2"></i>
                    No supplier selected
                </div>
                @endif
            </div>
        </div>
    </div>
</div>


        {{-- Supplier List --}}
        @if(!$selectedSupplier)
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">
                    <i class="bi bi-people me-2"></i> Suppliers with Due Payments
                </h5>
                <span class="badge bg-primary">{{ $suppliers->total() }} suppliers</span>
            </div>
            <div class="card-body p-0 overflow-auto">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Supplier Name</th>
                                <th class="text-center">Total Due</th>
                                <th class="text-center">Due Orders</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($suppliers as $supplier)
                            @php
                            $dueOrders = $supplier->orders->count();
                            // due_amount already has returns deducted, just sum it
                            $totalDue = $supplier->orders->sum('due_amount');
                            @endphp
                            <tr wire:key="supplier-{{ $supplier->id }}">
                                <td class="ps-4 fw-semibold">{{ $supplier->name }}</td>
                                <td class="text-center text-danger fw-bold">
                                    Rs.{{ number_format($totalDue, 2) }}
                                </td>
                                <td class="text-center"><span class="badge bg-warning">{{ $dueOrders }}</span></td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-primary btn-sm" wire:click="selectSupplier({{ $supplier->id }})">
                                        <i class="bi bi-credit-card me-1"></i> Pay
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="bi bi-search display-4 d-block mb-2"></i>
                                    @if($search)
                                    No suppliers found for "<span class="fw-bold">{{ $search }}</span>".
                                    @else
                                    No suppliers with due payments found.
                                    @endif
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if($suppliers->hasPages())
                <div class="card-footer">
                    <div class="d-flex justify-content-center">
                        {{ $suppliers->links('livewire.custom-pagination') }}
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- Due Orders and Payment Allocation --}}
        @if($selectedSupplier && count($supplierOrders) > 0)
        <div class="row">
            {{-- Due Orders List --}}
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header bg-warning text-white d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0">
                            <i class="bi bi-receipt me-2"></i> Due Orders - {{ $selectedSupplier->name }}
                        </h5>
                        <div>
                            @if(count($selectedOrders) > 0)
                                <button class="btn btn-light btn-sm me-2" wire:click="clearOrderSelection">
                                    <i class="bi bi-x-circle me-1"></i> Clear Selection
                                </button>
                            @endif
                            <button class="btn btn-light btn-sm" wire:click="selectAllOrders">
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
                                        <th>Order Code</th>
                                        <th>Date</th>
                                        <th>Total Amount</th>
                                        <th>Due Amount</th>
                                        <th>Status</th>
                                        <th width="100">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($supplierOrders as $order)
                                    @php
                                        $isSelected = in_array($order->id, $selectedOrders);
                                    @endphp
                                    <tr 
                                        wire:key="order-{{ $order->id }}" 
                                        class="{{ $isSelected ? 'table-success' : 'table-warning' }}"
                                        style="cursor: pointer;"
                                        wire:click="toggleOrderSelection({{ $order->id }})">
                                        <td class="text-center">
                                            <div class="form-check d-flex justify-content-center">
                                                <input 
                                                    class="form-check-input" 
                                                    type="checkbox" 
                                                    {{ $isSelected ? 'checked' : '' }}
                                                    wire:click="toggleOrderSelection({{ $order->id }})"
                                                    style="pointer-events: auto;">
                                            </div>
                                        </td>
                                        <td class="fw-bold">{{ $order->order_code }}</td>
                                        <td>{{ $order->order_date ? date('M d, Y', strtotime($order->order_date)) : '-' }}</td>
                                        <td>Rs.{{ number_format($order->total_amount, 2) }}</td>
                                        <td class="fw-bold {{ $isSelected ? 'text-success' : 'text-danger' }}">
                                            Rs.{{ number_format($order->due_amount, 2) }}
                                        </td>
                                        <td>
                                            @if($order->due_amount == 0)
                                                <span class="badge bg-success">Complete</span>
                                            @elseif($order->due_amount > 0 && $order->due_amount < $order->total_amount)
                                                <span class="badge bg-warning text-dark">Partial Paid</span>
                                            @else
                                                <span class="badge bg-danger">Payment Pending</span>
                                            @endif
                                        </td>
                                        <td>
                                            <button class="btn btn-outline-primary btn-sm" wire:click="viewOrderDetails({{ $order->id }})">
                                                <i class="bi bi-eye"></i> View
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if(count($selectedOrders) > 0)
                            <div class="card-footer bg-light">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-semibold">
                                        <i class="bi bi-check-circle-fill text-success me-2"></i>
                                        {{ count($selectedOrders) }} order(s) selected
                                    </span>
                                    <span class="fw-bold text-success">
                                        Total Due: Rs.{{ number_format($totalDueAmount, 2) }}
                                    </span>
                                </div>
                            </div>
                        @else
                            <div class="card-footer bg-light text-center text-muted">
                                <i class="bi bi-hand-index me-2"></i>
                                Click on rows or checkboxes to select orders for payment
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
                        @if(count($selectedOrders) > 0)
                            <div class="alert alert-info mb-4">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>Total Due (Selected)</span>
                                    <span class="fw-bold text-danger">Rs.{{ number_format($totalDueAmount, 2) }}</span>
                                </div>
                                <small class="text-muted d-block mt-2">
                                    <i class="bi bi-info-circle me-1"></i>
                                    {{ count($selectedOrders) }} order(s) selected for payment
                                </small>
                            </div>

                            {{-- Overpayment Credit Section --}}
                            @if($supplierOverpayment > 0)
                            <div class="alert alert-success mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold">
                                        <i class="bi bi-wallet2 me-1"></i> Overpayment Credit Available
                                    </span>
                                    <span class="fw-bold text-success">Rs.{{ number_format($supplierOverpayment, 2) }}</span>
                                </div>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="useOverpayment" 
                                        wire:click="toggleOverpayment" {{ $useOverpayment ? 'checked' : '' }}>
                                    <label class="form-check-label" for="useOverpayment">
                                        Apply overpayment credit to this payment
                                    </label>
                                </div>
                                @if($useOverpayment)
                                <div class="mt-2">
                                    <label class="form-label small fw-semibold">Credit Amount to Apply</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">Rs.</span>
                                        <input type="number" class="form-control" 
                                            wire:model.lazy="overpaymentToApply" 
                                            min="0" 
                                            max="{{ min($supplierOverpayment, $totalDueAmount) }}" 
                                            step="0.01">
                                    </div>
                                    <small class="text-muted">Max: Rs.{{ number_format(min($supplierOverpayment, $totalDueAmount), 2) }}</small>
                                </div>
                                @endif
                            </div>
                            @endif

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Enter Payment Amount <span class="text-danger">*</span></label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text">Rs.</span>
                                    <input type="number" class="form-control" wire:model.lazy="totalPaymentAmount" min="0" max="{{ $totalDueAmount - $overpaymentToApply }}" step="0.01" placeholder="Enter amount...">
                                </div>
                                <small class="text-muted">Maximum: Rs.{{ number_format($totalDueAmount - $overpaymentToApply, 2) }}</small>
                                @error('totalPaymentAmount')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Payment Summary --}}
                            @if($totalPaymentAmount > 0 || $overpaymentToApply > 0)
                            <div class="border rounded p-3 bg-light mb-3">
                                <h6 class="fw-bold mb-2">Payment Summary</h6>
                                @if($overpaymentToApply > 0)
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted">Overpayment Credit:</span>
                                    <span class="fw-bold text-success">Rs.{{ number_format($overpaymentToApply, 2) }}</span>
                                </div>
                                @endif
                                @if($totalPaymentAmount > 0)
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted">Cash Payment:</span>
                                    <span class="fw-bold">Rs.{{ number_format($totalPaymentAmount, 2) }}</span>
                                </div>
                                @endif
                                <hr class="my-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold">Total Payment:</span>
                                    <span class="fw-bold text-primary">Rs.{{ number_format($totalPaymentAmount + $overpaymentToApply, 2) }}</span>
                                </div>
                            </div>
                            <div class="alert alert-{{ $remainingAmount > 0 ? 'warning' : 'success' }} mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>Remaining Amount</span>
                                    <span class="fw-bold">Rs.{{ number_format($remainingAmount, 2) }}</span>
                                </div>
                            </div>
                            @endif

                            <div class="d-grid mt-3">
                                <button class="btn btn-success btn-lg" wire:click="openPaymentModal" wire:loading.attr="disabled" wire:loading.class="disabled">
                                    <span wire:loading.remove wire:target="openPaymentModal">
                                        <i class="bi bi-credit-card me-1"></i> Process Payment
                                    </span>
                                    <span wire:loading wire:target="openPaymentModal">
                                        <span class="spinner-border spinner-border-sm"></span> Processing...
                                    </span>
                                </button>
                            </div>

                               {{-- Quick Payment Options --}}
                        <div class="mt-3">
                            <small class="text-muted d-block mb-2">Quick Options:</small>
                            <div class="d-grid gap-2">
                                @php
                                    $remainingAfterOverpayment = $totalDueAmount - $overpaymentToApply;
                                @endphp
                                <button
                                    class="btn btn-outline-primary btn-sm"
                                    wire:click="$set('totalPaymentAmount', {{ $remainingAfterOverpayment }})" >
                                    Pay Full Amount (Rs.{{ number_format($remainingAfterOverpayment, 2) }})
                                </button>
                                @if($supplierOverpayment > 0 && !$useOverpayment)
                                <button
                                    class="btn btn-outline-success btn-sm"
                                    wire:click="toggleOverpayment">
                                    <i class="bi bi-wallet2 me-1"></i> Use Overpayment Credit
                                </button>
                                @endif
                            </div>
                        </div>
                        @else
                            <div class="alert alert-warning text-center">
                                <i class="bi bi-exclamation-triangle-fill display-4 d-block mb-3"></i>
                                <h6 class="fw-bold mb-2">No Orders Selected</h6>
                                <p class="mb-0 small">Please select at least one order from the list to make a payment.</p>
                            </div>
                            <div class="d-grid">
                                <button class="btn btn-secondary btn-lg" disabled>
                                    <i class="bi bi-lock me-1"></i> Select Orders First
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @elseif($selectedSupplier && count($supplierOrders) == 0)
        <div class="alert alert-success">
            <div class="d-flex align-items-center">
                <i class="bi bi-check-circle-fill fs-2 me-3"></i>
                <div>
                    <h5 class="mb-1">No Pending Payments</h5>
                    <p class="mb-0">{{ $selectedSupplier->name }} has no pending purchase orders. All orders are fully paid.</p>
                </div>
            </div>
        </div>
        @endif
    </div>
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

    /* Order selection styles */
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

    /* Receipt styles */
    #receipt-content {
        background: white;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    @media print {
        body * {
            visibility: hidden;
        }
        #receipt-content, #receipt-content * {
            visibility: visible;
        }
        #receipt-content {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            padding: 20px;
            background: white;
        }
        .btn {
            display: none !important;
        }
    }

    /* Modal animations */
    .modal.fade.show {
        animation: fadeIn 0.3s ease-in-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Success animation */
    .alert-success {
        border-left: 4px solid #000000;
        animation: slideIn 0.5s ease-out;
    }

    @keyframes slideIn {
        from { transform: translateX(-100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }

    /* Checkbox styling */
    .form-check-input:checked {
        background-color: #000000;
        border-color: #000000;
    }
</style>
@endpush

@push('scripts')
<script>
    function printReceipt() {
        const receiptContent = document.getElementById('receipt-content').innerHTML;
        const originalContent = document.body.innerHTML;
        
        // Create a print-friendly version
        const printContent = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Payment Receipt</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
                <style>
                    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; }
                    .receipt-header { border-bottom: 2px solid #000000; padding-bottom: 15px; margin-bottom: 20px; }
                    .text-success { color: #198754 !important; }
                    .table th { background-color: #f8f9fa !important; }
                    @media print { 
                        .no-print { display: none !important; }
                        body { margin: 0; padding: 15px; }
                    }
                </style>
            </head>
            <body>
                ${receiptContent}
                <div class="text-center mt-4 no-print">
                    <button class="btn btn-secondary" onclick="window.close()">Close</button>
                    
                </div>
                <script>
                    window.onload = function() {
                        window.print();
                    }
                <\/script>
            </body>
            </html>
        `;
        
        const printWindow = window.open('', '_blank');
        printWindow.document.write(printContent);
        printWindow.document.close();
    }

    // Toast notification handler
    document.addEventListener('DOMContentLoaded', function() {
        window.addEventListener('show-toast', (event) => {
            const toastData = Array.isArray(event.detail) ? event.detail[0] : event.detail;
            const type = toastData.type || 'info';
            const message = toastData.message || 'Notification';
            
            // You can integrate with a more sophisticated toast library here
            console.log(`Toast: ${type} - ${message}`);
        });
        window.addEventListener('refreshPage', () => {
            window.location.reload();
        });
    });
</script>
@endpush