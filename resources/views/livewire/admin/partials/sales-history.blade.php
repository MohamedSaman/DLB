<!-- Sales History Partial -->
@php
    $historyHasVariants = $historyHasVariants ?? false;
    $historyVariantName = $historyVariantName ?? '';
    $historyVariantFilter = $historyVariantFilter ?? '';
    $filteredSales = collect($salesHistory ?? []);
    if ($historyVariantFilter !== '') {
        $filteredSales = $filteredSales->where('variant_value', $historyVariantFilter);
    }
@endphp

@if($filteredSales->count() > 0)
@if($historyHasVariants && $historyVariantFilter !== '')
<div class="bg-light border rounded px-3 py-2 mb-3 d-flex align-items-center">
    <i class="bi bi-tag-fill text-primary me-2"></i>
    <strong>{{ $historyVariantName }}: {{ $historyVariantFilter }}</strong>
    <span class="badge bg-primary ms-2">{{ $filteredSales->count() }} record(s)</span>
</div>
@endif
<div class="table-responsive">
    <table class="table table-hover table-bordered align-middle">
        <thead class="table-primary">
            <tr>
                <th class="text-center" style="width: 5%;">#</th>
                <th style="width: 12%;">Invoice No.</th>
                <th style="width: 10%;">Date</th>
                <th style="width: 15%;">Customer</th>
                <th style="width: 10%;">Phone</th>
                <th class="text-center" style="width: 8%;">Qty</th>
                <th class="text-end" style="width: 10%;">Unit Price</th>
                <th class="text-end" style="width: 10%;">Total</th>
                <th class="text-center" style="width: 10%;">Payment</th>
                <th class="text-center" style="width: 10%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($filteredSales as $index => $sale)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
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
                    @if(($sale['customer_type'] ?? 'walk-in') === 'walk-in')
                        <span class="badge bg-secondary badge-sm">Walk-in</span>
                    @endif
                </td>
                <td>
                    <small class="text-muted">{{ $sale['customer_phone'] ?? 'N/A' }}</small>
                </td>
                <td class="text-center">
                    <span class="badge bg-dark">{{ $sale['quantity'] ?? 0 }}</span>
                </td>
                <td class="text-end">
                    <div>Rs. {{ number_format($sale['unit_price'] ?? 0, 2) }}</div>
                    @if(($sale['discount_per_unit'] ?? 0) > 0)
                        <small class="text-danger">-Rs. {{ number_format($sale['discount_per_unit'], 2) }}</small>
                    @endif
                </td>
                <td class="text-end">
                    <strong class="text-success">Rs. {{ number_format($sale['total'] ?? 0, 2) }}</strong>
                    @if(($sale['total_discount'] ?? 0) > 0)
                        <div><small class="text-danger">Discount: Rs. {{ number_format($sale['total_discount'], 2) }}</small></div>
                    @endif
                </td>
                <td class="text-center">
                    @php
                        $paymentType = strtolower($sale['payment_type'] ?? 'cash');
                        $paymentStatus = strtolower($sale['payment_status'] ?? 'unpaid');
                    @endphp
                    <div class="mb-1">
                        @if($paymentType === 'cash')
                            <span class="badge bg-success">Cash</span>
                        @elseif($paymentType === 'cheque')
                            <span class="badge bg-warning">Cheque</span>
                        @elseif($paymentType === 'bank' || $paymentType === 'online')
                            <span class="badge bg-info">Bank</span>
                        @elseif($paymentType === 'full' || $paymentType === 'advance')
                            <span class="badge bg-primary">{{ ucfirst($paymentType) }}</span>
                        @else
                            <span class="badge bg-secondary">{{ ucfirst($paymentType) }}</span>
                        @endif
                    </div>
                    <div>
                        @if($paymentStatus === 'paid' || $paymentStatus === 'full')
                            <span class="badge bg-success">Paid</span>
                        @elseif($paymentStatus === 'partial' || $paymentStatus === 'advance')
                            <span class="badge bg-warning">Partial</span>
                        @else
                            <span class="badge bg-danger">Unpaid</span>
                        @endif
                    </div>
                </td>
                <td class="text-center">
                    @php
                        $saleStatus = strtolower($sale['sale_status'] ?? 'completed');
                    @endphp
                    @if($saleStatus === 'completed')
                        <span class="badge bg-success">Completed</span>
                    @elseif($saleStatus === 'confirm' || $saleStatus === 'confirmed')
                        <span class="badge bg-primary">Confirmed</span>
                    @elseif($saleStatus === 'pending')
                        <span class="badge bg-warning">Pending</span>
                    @elseif($saleStatus === 'cancelled')
                        <span class="badge bg-danger">Cancelled</span>
                    @else
                        <span class="badge bg-secondary">{{ ucfirst($saleStatus) }}</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot class="table-light">
            <tr>
                <td colspan="5" class="text-end fw-bold">Total:</td>
                <td class="text-center fw-bold">{{ $filteredSales->sum('quantity') }}</td>
                <td colspan="1"></td>
                <td class="text-end fw-bold text-success">
                    Rs. {{ number_format($filteredSales->sum('total'), 2) }}
                </td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
</div>

@else
<div class="text-center py-5">
    <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
    @if($historyVariantFilter !== '')
    <p class="text-muted mt-3 fs-5">No sales history found for variant "{{ $historyVariantFilter }}".</p>
    @else
    <p class="text-muted mt-3 fs-5">No sales history found for this product.</p>
    @endif
</div>
@endif
