<div>
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">
                <i class="bi bi-clock-history text-orange me-2"></i> Returns History
            </h3>
            <p class="text-muted mb-0">Track and view returns processed for your deliveries</p>
        </div>
        <a href="{{ route('delivery.return-product') }}" class="btn btn-orange text-white">
            <i class="bi bi-plus-circle me-1"></i> New Return
        </a>
    </div>

    <!-- Filter and Search -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" class="form-control border-start-0 ps-0"
                            placeholder="Search by invoice number or product..."
                            wire:model.live.debounce.300ms="returnSearch">
                    </div>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <span class="text-muted">Total Returns: <strong>{{ $returnsCount }}</strong></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Returns Table -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            @if(count($returns) > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-dark">
                    <thead>
                        <tr class="table-light">
                            <th class="ps-4 text-dark">Invoice #</th>
                            <th class="text-dark">Customer</th>
                            <th class="text-dark text-center">Usable Qty</th>
                            <th class="text-dark text-center">Damaged Qty</th>
                            <th class="text-dark">Total Returned Amount</th>
                            <th class="text-dark">Date Returned</th>
                            <th class="text-end pe-4 text-dark">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($returns as $row)
                        <tr>
                            <td class="ps-4">
                                <span class="fw-bold text-dark">#{{ $row->sale->invoice_number ?? 'N/A' }}</span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $row->sale->customer->name ?? 'N/A' }}</div>
                                <small class="text-muted">{{ $row->sale->customer->phone ?? 'N/A' }}</small>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-success bg-opacity-10 text-success px-2.5 py-1.5 fw-bold">
                                    {{ $row->total_usable }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-danger bg-opacity-10 text-danger px-2.5 py-1.5 fw-bold">
                                    {{ $row->total_damaged }}
                                </span>
                            </td>
                            <td>
                                <span class="fw-bold text-success">Rs.{{ number_format($row->total_return_amount, 2) }}</span>
                            </td>
                            <td>
                                <span class="text-dark">{{ \Carbon\Carbon::parse($row->latest_return_date)->format('M d, Y H:i') }}</span>
                            </td>
                            <td class="text-end pe-4">
                                <button class="btn btn-outline-orange btn-sm" wire:click="showReceipt({{ $row->sale_id }})">
                                    <i class="bi bi-file-earmark-text me-1"></i> Receipt
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-5">
                <i class="bi bi-arrow-return-left text-muted display-1 mb-3 d-block"></i>
                <h5 class="fw-bold text-dark">No Returns Found</h5>
                <p class="text-muted">No product return records match your search or filter.</p>
            </div>
            @endif
        </div>
        @if($returns->hasPages())
        <div class="card-footer bg-light">
            <div class="d-flex justify-content-center">
                {{ $returns->links('livewire.custom-pagination') }}
            </div>
        </div>
        @endif
    </div>

    <!-- Receipt / Return Details Modal -->
    <div wire:ignore.self class="modal fade" id="receiptModal" tabindex="-1">
        <div class="modal-dialog modal-md">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-orange text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-file-earmark-text me-2"></i> Return Receipt
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeModal"></button>
                </div>
                <div class="modal-body text-dark" id="printArea">
                    @if($selectedReturn && !empty($selectedReturnItems))
                    <!-- Receipt Header -->
                    <div class="text-center mb-4">
                        <h4 class="fw-bold mb-1" style="color: #f58320;">HARDMEN</h4>
                        <p class="text-muted mb-0 small">Product Return Receipt</p>
                    </div>

                    <!-- Receipt Info -->
                    <div class="row mb-3 small bg-light p-3 rounded">
                        <div class="col-6 mb-2">
                            <span class="text-muted d-block">Invoice #</span>
                            <span class="fw-bold text-dark">#{{ $selectedReturn->sale->invoice_number }}</span>
                        </div>
                        <div class="col-6 mb-2">
                            <span class="text-muted d-block">Return Date</span>
                            <span class="fw-bold text-dark">{{ $selectedReturn->created_at->format('M d, Y H:i') }}</span>
                        </div>
                        <div class="col-12">
                            <span class="text-muted d-block">Customer</span>
                            <span class="fw-bold text-dark">{{ $selectedReturn->sale->customer->name }}</span>
                            <span class="text-muted small d-block">{{ $selectedReturn->sale->customer->phone }}</span>
                        </div>
                    </div>

                    <!-- Return Items -->
                    <h6 class="fw-bold mb-2">Returned Items</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered table-sm align-middle small text-dark">
                            <thead>
                                <tr class="bg-light">
                                    <th class="text-dark">Product</th>
                                    <th class="text-dark text-center">Usable</th>
                                    <th class="text-dark text-center">Damaged</th>
                                    <th class="text-dark">Price</th>
                                    <th class="text-dark">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $totalQty = 0; $totalValue = 0; @endphp
                                @foreach($selectedReturnItems as $item)
                                <tr>
                                    <td class="text-dark">
                                        {{ $item->product->name ?? 'Unknown Product' }}
                                        @if($item->variant_value)
                                        <div class="text-muted x-small">({{ $item->variant_value }})</div>
                                        @endif
                                    </td>
                                    <td class="text-center text-dark">{{ $item->usable_quantity }}</td>
                                    <td class="text-center text-danger fw-bold">{{ $item->damaged_quantity }}</td>
                                    <td class="text-dark">Rs.{{ number_format($item->selling_price, 2) }}</td>
                                    <td class="fw-bold text-dark">Rs.{{ number_format($item->total_amount, 2) }}</td>
                                </tr>
                                @php 
                                    $totalQty += $item->return_quantity; 
                                    $totalValue += $item->total_amount; 
                                @endphp
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold bg-light">
                                    <td class="text-dark">Total Qty:</td>
                                    <td colspan="2" class="text-center text-dark">{{ $totalQty }}</td>
                                    <td class="text-dark">Total Return:</td>
                                    <td class="text-success">Rs.{{ number_format($totalValue, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Receipt Footer -->
                    <div class="text-center border-top pt-3 small text-muted">
                        <p class="mb-1">Thank you!</p>
                        <p class="mb-0">Receipt generated by Delivery System</p>
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" wire:click="closeModal">Close</button>
                    <button class="btn btn-orange text-white" wire:click="printReceipt">
                        <i class="bi bi-printer me-1"></i> Print Receipt
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .btn-orange {
        background-color: #f58320;
        border-color: #f58320;
    }

    .btn-orange:hover, .btn-orange:focus {
        background-color: #e07010;
        border-color: #e07010;
        color: white;
    }

    .btn-outline-orange {
        color: #f58320;
        border-color: #f58320;
    }

    .btn-outline-orange:hover {
        background-color: #f58320;
        border-color: #f58320;
        color: white;
    }

    .text-orange {
        color: #f58320 !important;
    }

    .border-orange {
        border-color: #f58320 !important;
    }

    @media print {
        body * {
            visibility: hidden;
        }
        #printArea, #printArea * {
            visibility: visible;
        }
        #printArea {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    window.addEventListener('showModal', event => {
        var modalEl = document.getElementById(event.detail);
        var modal = new bootstrap.Modal(modalEl);
        modal.show();
    });

    window.addEventListener('hideModal', event => {
        var modalEl = document.getElementById(event.detail);
        var modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) {
            modal.hide();
        }
    });

    window.addEventListener('printReceipt', () => {
        var printContents = document.getElementById('printArea').innerHTML;
        var originalContents = document.body.innerHTML;
        document.body.innerHTML = printContents;
        window.print();
        document.body.innerHTML = originalContents;
        window.location.reload(); // Reload to restore Livewire bindings
    });
</script>
@endpush
