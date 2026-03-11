<!-- Quotations History Partial -->
@php
    $historyHasVariants = $historyHasVariants ?? false;
    $historyVariantName = $historyVariantName ?? '';
    $historyVariantFilter = $historyVariantFilter ?? '';
    $filteredQuotations = collect($quotationsHistory ?? []);
    if ($historyVariantFilter !== '') {
        $filteredQuotations = $filteredQuotations->where('variant_value', $historyVariantFilter);
    }
@endphp

@if($filteredQuotations->count() > 0)
@if($historyHasVariants && $historyVariantFilter !== '')
<div class="bg-light border rounded px-3 py-2 mb-3 d-flex align-items-center">
    <i class="bi bi-tag-fill text-info me-2"></i>
    <strong>{{ $historyVariantName }}: {{ $historyVariantFilter }}</strong>
    <span class="badge bg-info ms-2">{{ $filteredQuotations->count() }} record(s)</span>
</div>
@endif
<div class="table-responsive">
    <table class="table table-hover table-bordered align-middle">
        <thead class="table-info">
            <tr>
                <th class="text-center" style="width: 5%;">#</th>
                <th style="width: 12%;">Quotation No.</th>
                <th style="width: 10%;">Reference</th>
                <th style="width: 12%;">Date</th>
                <th style="width: 10%;">Valid Until</th>
                <th style="width: 15%;">Customer</th>
                <th class="text-center" style="width: 8%;">Qty</th>
                <th class="text-end" style="width: 10%;">Unit Price</th>
                <th class="text-end" style="width: 8%;">Discount</th>
                <th class="text-end" style="width: 10%;">Total</th>
                <th class="text-center" style="width: 10%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($filteredQuotations as $index => $quotation)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>
                    <strong class="text-info">{{ $quotation['quotation_number'] ?? 'N/A' }}</strong>
                </td>
                <td>
                    <small>{{ $quotation['reference_number'] ?? 'N/A' }}</small>
                </td>
                <td>
                    <small>{{ \Carbon\Carbon::parse($quotation['quotation_date'])->format('d M Y') }}</small>
                </td>
                <td>
                    <small>{{ \Carbon\Carbon::parse($quotation['valid_until'])->format('d M Y') }}</small>
                    @if(\Carbon\Carbon::parse($quotation['valid_until'])->isPast())
                        <div><span class="badge bg-danger badge-sm">Expired</span></div>
                    @endif
                </td>
                <td>
                    <div class="fw-semibold">{{ $quotation['customer_name'] ?? 'N/A' }}</div>
                    <small class="text-muted">{{ $quotation['customer_phone'] ?? 'N/A' }}</small>
                    @if(!empty($quotation['customer_email']))
                        <div><small class="text-muted">{{ $quotation['customer_email'] }}</small></div>
                    @endif
                </td>
                <td class="text-center">
                    <span class="badge bg-dark">{{ $quotation['quantity'] ?? 0 }}</span>
                </td>
                <td class="text-end">
                    Rs. {{ number_format($quotation['unit_price'] ?? 0, 2) }}
                </td>
                <td class="text-end">
                    @if(($quotation['discount'] ?? 0) > 0)
                        <span class="text-danger">Rs. {{ number_format($quotation['discount'], 2) }}</span>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
                <td class="text-end">
                    <strong class="text-success">Rs. {{ number_format($quotation['total'] ?? 0, 2) }}</strong>
                </td>
                <td class="text-center">
                    @php
                        $status = strtolower($quotation['status'] ?? 'pending');
                    @endphp
                    @if($status === 'accepted' || $status === 'approved')
                        <span class="badge bg-success">Accepted</span>
                    @elseif($status === 'pending')
                        <span class="badge bg-warning">Pending</span>
                    @elseif($status === 'rejected' || $status === 'declined')
                        <span class="badge bg-danger">Rejected</span>
                    @elseif($status === 'expired')
                        <span class="badge bg-secondary">Expired</span>
                    @else
                        <span class="badge bg-info">{{ ucfirst($status) }}</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot class="table-light">
            <tr>
                <td colspan="6" class="text-end fw-bold">Total:</td>
                <td class="text-center fw-bold">{{ $filteredQuotations->sum('quantity') }}</td>
                <td colspan="2"></td>
                <td class="text-end fw-bold text-success">
                    Rs. {{ number_format($filteredQuotations->sum('total'), 2) }}
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
    <p class="text-muted mt-3 fs-5">No quotation history found for variant "{{ $historyVariantFilter }}".</p>
    @else
    <p class="text-muted mt-3 fs-5">No quotation history found for this product.</p>
    @endif
</div>
@endif
