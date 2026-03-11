<!-- Returns History Partial -->
@php
    $historyHasVariants = $historyHasVariants ?? false;
    $historyVariantName = $historyVariantName ?? '';
    $historyVariantFilter = $historyVariantFilter ?? '';
    $filteredReturns = collect($returnsHistory ?? []);
    if ($historyVariantFilter !== '') {
        $filteredReturns = $filteredReturns->where('variant_value', $historyVariantFilter);
    }
@endphp

@if($filteredReturns->count() > 0)
@if($historyHasVariants && $historyVariantFilter !== '')
<div class="bg-light border rounded px-3 py-2 mb-3 d-flex align-items-center">
    <i class="bi bi-tag-fill text-warning me-2"></i>
    <strong>{{ $historyVariantName }}: {{ $historyVariantFilter }}</strong>
    <span class="badge bg-warning ms-2">{{ $filteredReturns->count() }} record(s)</span>
</div>
@endif
<div class="table-responsive">
    <table class="table table-hover table-bordered align-middle">
        <thead class="table-warning">
            <tr>
                <th class="text-center" style="width: 5%;">#</th>
                <th style="width: 15%;">Invoice No.</th>
                <th style="width: 15%;">Return Date</th>
                <th style="width: 20%;">Customer</th>
                <th class="text-center" style="width: 10%;">Qty Returned</th>
                <th class="text-end" style="width: 12%;">Unit Price</th>
                <th class="text-end" style="width: 12%;">Total Amount</th>
                <th style="width: 25%;">Notes</th>
            </tr>
        </thead>
        <tbody>
            @foreach($filteredReturns as $index => $return)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>
                    <strong class="text-warning">{{ $return['invoice_number'] ?? 'N/A' }}</strong>
                </td>
                <td>
                    <small>{{ \Carbon\Carbon::parse($return['return_date'])->format('d M Y, h:i A') }}</small>
                </td>
                <td>
                    <div class="fw-semibold">{{ $return['customer_name'] ?? 'Walk-in' }}</div>
                    <small class="text-muted">{{ $return['customer_phone'] ?? 'N/A' }}</small>
                </td>
                <td class="text-center">
                    <span class="badge bg-danger">{{ $return['return_quantity'] ?? 0 }}</span>
                </td>
                <td class="text-end">
                    Rs. {{ number_format($return['selling_price'] ?? 0, 2) }}
                </td>
                <td class="text-end">
                    <strong class="text-danger">Rs. {{ number_format($return['total_amount'] ?? 0, 2) }}</strong>
                </td>
                <td>
                    <small class="text-muted">{{ $return['notes'] ?? 'No notes' }}</small>
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot class="table-light">
            <tr>
                <td colspan="4" class="text-end fw-bold">Total:</td>
                <td class="text-center fw-bold">{{ $filteredReturns->sum('return_quantity') }}</td>
                <td colspan="1"></td>
                <td class="text-end fw-bold text-danger">
                    Rs. {{ number_format($filteredReturns->sum('total_amount'), 2) }}
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
    <p class="text-muted mt-3 fs-5">No return history found for variant "{{ $historyVariantFilter }}".</p>
    @else
    <p class="text-muted mt-3 fs-5">No return history found for this product.</p>
    @endif
</div>
@endif
