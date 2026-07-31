<!-- Purchases History Partial -->
@php
    $historyHasVariants = $historyHasVariants ?? false;
    $historyVariantName = $historyVariantName ?? '';
    $historyVariantFilter = $historyVariantFilter ?? '';
    $historyCustomerTypeFilter = $historyCustomerTypeFilter ?? '';
    $filteredPurchases = collect($purchasesHistory ?? []);
    if ($historyVariantFilter !== '') {
        $filteredPurchases = $filteredPurchases->where('variant_value', $historyVariantFilter);
    }
    if ($historyCustomerTypeFilter !== '') {
        $filteredPurchases = collect([]);
    }
@endphp

@if($filteredPurchases->count() > 0)
@if($historyHasVariants && $historyVariantFilter !== '')
<div class="bg-light border rounded px-3 py-2 mb-3 d-flex align-items-center">
    <i class="bi bi-tag-fill text-success me-2"></i>
    <strong>{{ $historyVariantName }}: {{ $historyVariantFilter }}</strong>
    <span class="badge bg-success ms-2">{{ $filteredPurchases->count() }} record(s)</span>
</div>
@endif
<div class="table-responsive">
    <table class="table table-hover table-bordered align-middle">
        <thead class="table-success">
            <tr>
                <th class="text-center" style="width: 5%;">#</th>
                <th style="width: 15%;">Order Code</th>
                <th style="width: 12%;">Order Date</th>
                <th style="width: 12%;">Received Date</th>
                <th style="width: 18%;">Supplier</th>
                <th class="text-center" style="width: 8%;">Order Qty</th>
                <th class="text-center" style="width: 8%;">Received Qty</th>
                <th class="text-end" style="width: 12%;">Unit Price</th>
                <th class="text-end" style="width: 10%;">Discount</th>
                <th class="text-end" style="width: 20%;">Total</th>
                <th class="text-center" style="width: 10%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($filteredPurchases as $index => $purchase)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>
                    <strong class="text-success">{{ $purchase['order_code'] ?? 'N/A' }}</strong>
                </td>
                <td>
                    <small>{{ \Carbon\Carbon::parse($purchase['order_date'])->format('d M Y') }}</small>
                </td>
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
                <td class="text-center">
                    <span class="badge bg-dark">{{ $purchase['quantity'] ?? 0 }}</span>
                </td>
                <td class="text-center">
                    <span class="badge bg-dark">{{ $purchase['received_quantity'] ?? 0 }}</span>
                </td>
                <td class="text-end">
                    Rs. {{ number_format($purchase['unit_price'] ?? 0, 2) }}
                </td>
                <td class="text-end">
                    @if(($purchase['discount'] ?? 0) > 0)
                        <span class="text-danger">Rs. {{ number_format($purchase['discount'], 2) }}</span>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
                <td class="text-end">
                    <strong class="text-success">Rs. {{ number_format($purchase['total'] ?? 0, 2) }}</strong>
                </td>
                <td class="text-center">
                    @php
                        $status = strtolower($purchase['order_status'] ?? 'pending');
                    @endphp
                    @if($status === 'completed' || $status === 'received')
                        <span class="badge bg-success">Received</span>
                    @elseif($status === 'pending')
                        <span class="badge bg-warning">Pending</span>
                    @elseif($status === 'cancelled')
                        <span class="badge bg-danger">Cancelled</span>
                    @else
                        <span class="badge bg-secondary">{{ ucfirst($status) }}</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot class="table-light">
            <tr>
                <td colspan="6" class="text-end fw-bold">Total:</td>
                <td class="text-center fw-bold">{{ number_format($filteredPurchases->sum('received_quantity'), 2) }}</td>
                <td colspan="2"></td>
                <td class="text-end fw-bold text-success">
                    Rs. {{ number_format($filteredPurchases->sum('total'), 2) }}
                </td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>

@else
<div class="text-center py-5">
    <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
    @if($historyVariantFilter !== '')
    <p class="text-muted mt-3 fs-5">No purchase history found for variant "{{ $historyVariantFilter }}".</p>
    @else
    <p class="text-muted mt-3 fs-5">No purchase history found for this product.</p>
    @endif
</div>
@endif
