<x-print-layout title="Sale Receipt - {{ $sale->invoice_number }}" :documentType="'INVOICE'">
    <!-- Sale Receipt Content -->

    <!-- Customer & Sale Details -->
    <div class="invoice-info-row">
        <div class="col-6">
            <p><strong>Customer:</strong></p>
            <p>{{ $sale->customer->name }}</p>
            <p>{{ $sale->customer->address }}</p>
            <p><strong>Tel:</strong> {{ $sale->customer->phone }}</p>
        </div>
        <div class="col-6 text-end">
            <table class="table-borderless ms-auto" style="width: auto; display: inline-table;">
                <tr>
                    <td style="padding-right: 15px;"><strong>Invoice #</strong></td>
                    <td>{{ $sale->invoice_number }}</td>
                </tr>
                <tr>
                    <td style="padding-right: 15px;"><strong>Sale ID</strong></td>
                    <td>{{ $sale->sale_id }}</td>
                </tr>
                <tr>
                    <td style="padding-right: 15px;"><strong>Date</strong></td>
                    <td>{{ $sale->created_at->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <td style="padding-right: 15px;"><strong>Time</strong></td>
                    <td>{{ $sale->created_at->format('H:i') }}</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Items Table -->
    @php
        $allItems = collect($sale->items);
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
    @endphp

    @foreach($itemChunks as $pageIndex => $chunk)
        @php
            $pageSubtotal = 0;
            foreach ($chunk as $cItem) {
                $pageSubtotal += (float) ($cItem->total ?? ($cItem->unit_price * $cItem->quantity));
            }
            $chunkOffset = $itemOffsets[$pageIndex] ?? 0;
        @endphp

        @if($pageIndex > 0)
            <div style="page-break-before: always; margin-top: 10px;"></div>
            <div class="global-header" style="border-bottom: 2px solid #000; padding-bottom: 4px; margin-bottom: 8px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-weight: bold; font-size: 13px;">DLB ENTERPRISES — INVOICE</span>
                    <span style="font-size: 11px; color: #555;">Invoice #: {{ $sale->invoice_number }} | Page {{ $pageIndex + 1 }} of {{ $totalPages }}</span>
                </div>
            </div>
        @endif

        <table class="invoice-table">
            <thead>
                <tr>
                    <th width="40">#</th>
                    <th>ITEM CODE</th>
                    <th>DESCRIPTION</th>
                    <th width="80">QTY</th>
                    <th width="120">UNIT PRICE</th>
                    <th width="120">UNIT DISCOUNT</th>
                    <th width="120">SUBTOTAL</th>
                </tr>
            </thead>
            <tbody>
                @foreach($chunk as $itemIndex => $item)
                @php $globalIndex = $chunkOffset + $loop->index + 1; @endphp
                <tr>
                    <td class="text-center">{{ $globalIndex }}</td>
                    <td>{{ $item->product_code }}</td>
                    <td>{{ $item->product_name }}</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-end">Rs.{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-end">Rs.{{ number_format($item->unit_discount, 2) }}</td>
                    <td class="text-end">Rs.{{ number_format($item->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="totals-row">
                    <td colspan="6" class="text-end">
                        <strong>
                            @if($totalPages > 1)
                                Sales Items Subtotal (Page {{ $pageIndex + 1 }} of {{ $totalPages }})
                            @else
                                Subtotal
                            @endif
                        </strong>
                    </td>
                    <td class="text-end"><strong>Rs.{{ number_format($pageSubtotal, 2) }}</strong></td>
                </tr>
                @if($loop->last)
                    @if($sale->discount_amount > 0)
                    <tr class="totals-row">
                        <td colspan="6" class="text-end"><strong>Discount</strong></td>
                        <td class="text-end"><strong>-Rs.{{ number_format($sale->discount_amount, 2) }}</strong></td>
                    </tr>
                    @endif
                    <tr class="totals-row grand-total">
                        <td colspan="6" class="text-end"><strong>Grand Total</strong></td>
                        <td class="text-end"><strong>Rs.{{ number_format($sale->total_amount, 2) }}</strong></td>
                    </tr>
                    @if($sale->payments->count() > 0)
                    <tr class="totals-row">
                        <td colspan="6" class="text-end"><strong>Paid Amount</strong></td>
                        <td class="text-end"><strong>Rs.{{ number_format($sale->payments->sum('amount'), 2) }}</strong></td>
                    </tr>
                    @endif
                    @if($sale->due_amount > 0)
                    <tr class="totals-row">
                        <td colspan="6" class="text-end"><strong>Due Amount</strong></td>
                        <td class="text-end"><strong>Rs.{{ number_format($sale->due_amount, 2) }}</strong></td>
                    </tr>
                    @endif
                @endif
            </tfoot>
        </table>
    @endforeach

    {{-- Returned Items Table --}}
    @if(isset($sale->returns) && count($sale->returns) > 0)
    <div class="returned-items-section">
        <h6 style="margin-bottom: 10px; font-weight: bold; color: #000;">RETURNED ITEMS</h6>
        <table class="invoice-table">
            <thead>
                <tr>
                    <th width="40">#</th>
                    <th>ITEM CODE</th>
                    <th>DESCRIPTION</th>
                    <th width="100">RETURN QTY</th>
                    <th width="120">UNIT PRICE</th>
                    <th width="120">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                @php $returnAmount = 0; @endphp
                @foreach($sale->returns as $rIndex => $return)
                @php $returnAmount += $return->total_amount; @endphp
                <tr>
                    <td class="text-center">{{ $rIndex + 1 }}</td>
                    <td>{{ $return->product?->code ?? '-' }}</td>
                    <td>{{ $return->product?->name ?? '-' }}</td>
                    <td class="text-center">{{ $return->return_quantity }}</td>
                    <td class="text-end">Rs.{{ number_format($return->selling_price, 2) }}</td>
                    <td class="text-end">Rs.{{ number_format($return->total_amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="totals-row">
                    <td colspan="5" class="text-end"><strong>Return Amount:</strong></td>
                    <td class="text-end"><strong>-Rs.{{ number_format($returnAmount, 2) }}</strong></td>
                </tr>
                <tr class="totals-row grand-total">
                    <td colspan="5" class="text-end"><strong>Net Amount:</strong></td>
                    <td class="text-end"><strong>Rs.{{ number_format(($sale->subtotal - $sale->discount_amount) - $returnAmount, 2) }}</strong></td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif

    @if(isset($showDueDetails) && $showDueDetails && $sale->customer && $sale->customer->name !== 'Walking Customer')
    @php
        $receiptCustomer = $sale->customer;
        $receiptDueInvoiceCount = \App\Models\Sale::where('customer_id', $receiptCustomer->id)
            ->where('due_amount', '>', 0)
            ->count();

        $receiptReturnedCheques = \App\Models\Cheque::where('customer_id', $receiptCustomer->id)
            ->where('status', 'return')
            ->get();
        $receiptReturnedChequeCount = $receiptReturnedCheques->count();
        $receiptReturnedChequeAmount = $receiptReturnedCheques->sum('cheque_amount');
    @endphp
    <div style="margin-top: 20px; padding: 12px; border: 1.5px solid #e67e22; background-color: #fffaf5; border-radius: 6px;">
        <h6 style="margin: 0 0 8px 0; color: #e67e22; font-weight: bold; border-bottom: 1px solid #ffebcc; padding-bottom: 4px; text-transform: uppercase; font-size: 12px;">Outstanding Financial Summary</h6>
        <table class="table table-sm table-borderless mb-0" style="font-size: 12px; width: 100%;">
            @if($receiptReturnedChequeCount > 0)
            <tr style="border: none;">
                <td style="padding: 2px 0; border: none; text-align: left;">Invoice Outstanding Due:</td>
                <td class="text-end" style="padding: 2px 0; font-weight: bold; border: none; text-align: right;">Rs. {{ number_format(max(0, $receiptCustomer->total_due - $receiptReturnedChequeAmount), 2) }}</td>
            </tr>
            <tr style="color: #d32f2f; border: none;">
                <td style="padding: 2px 0; border: none; text-align: left;">Returned Cheque Amount:</td>
                <td class="text-end" style="padding: 2px 0; font-weight: bold; border: none; text-align: right;">Rs. {{ number_format($receiptReturnedChequeAmount, 2) }} ({{ $receiptReturnedChequeCount }} Cheques)</td>
            </tr>
            <tr style="border-top: 1px dashed #ffebcc; padding-top: 4px;">
                <td style="padding: 4px 0 2px 0; border: none; text-align: left; color: #e67e22; font-weight: bold;">Total Outstanding Due:</td>
                <td class="text-end" style="padding: 4px 0 2px 0; font-weight: bold; border: none; text-align: right; color: #e67e22; font-size: 13px;">Rs. {{ number_format($receiptCustomer->total_due, 2) }}</td>
            </tr>
            @else
            <tr style="border: none;">
                <td style="padding: 2px 0; border: none; text-align: left;">Remaining Due Amount:</td>
                <td class="text-end" style="padding: 2px 0; font-weight: bold; border: none; text-align: right;">Rs. {{ number_format($receiptCustomer->total_due, 2) }}</td>
            </tr>
            @endif
            <tr style="border: none;">
                <td style="padding: 2px 0; border: none; text-align: left;">Due Invoice Count:</td>
                <td class="text-end" style="padding: 2px 0; font-weight: bold; border: none; text-align: right;">{{ $receiptDueInvoiceCount }}</td>
            </tr>
        </table>
    </div>
    @endif

    @if($sale->notes)
    <div style="margin-top: 20px; padding: 10px; background: #f8f9fa; border: 1px solid #dee2e6;">
        <strong>Notes:</strong> {{ $sale->notes }}
    </div>
    @endif
</x-print-layout>