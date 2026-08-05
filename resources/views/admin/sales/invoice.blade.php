<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice - {{ $sale->invoice_number }}</title>
    <style>
        @page {
            margin: 10mm;
            size: letter portrait;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 13px;
            margin: 0;
            padding: 0;
            color: #000;
            margin-bottom: 150px;
        }
        
        .footer-container {
            position: fixed;
            bottom: 0px;
            left: 0px;
            right: 0px;
            height: 120px;
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        /* Header */
        .receipt-header {
            width: 100%;
            border-bottom: 3px solid #000;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        .receipt-header td {
            vertical-align: top;
            padding-bottom: 10px;
        }
        
        /* Info Row */
        .info-table {
            margin-bottom: 15px;
            font-size: 11px;

        }
        .info-table td {
            vertical-align: top;
        }
        .info-table .customer-col {
            width: 50%;
            text-align: left;
        }
        .info-table .invoice-col {
            width: 50%;
            text-align: right;
        }
        .info-table p {
            margin: 0;
        }
        
        /* Items Table */
        .items-table {
            width: 100%;
            min-height: 50%;
            border-collapse: collapse;
            
            margin-top: 15px;
            font-size: 11px;
        }
        .items-table th {
            
            border-bottom: 2px solid #000;
            padding: 8px;
            text-align: left;
            background: none;
            font-weight: bold;
            text-transform: uppercase;
        }
        .items-table th.text-center { text-align: center; }
        .items-table th.text-right { text-align: right; }
        .items-table td {
            border: none;
            padding: 4px 8px;
            text-align: left;
        }
        .items-table td.text-center { text-align: center; }
        .items-table td.text-right { text-align: right; }

        /* Payment & Summary Container */
        .summary-container {
            margin-top: 25px;
            border-top: 2px solid #000;
            padding-top: 12px;
        }
        .summary-table {
            width: 100%;
            font-size: 11px;
        }
        .summary-table td {
            vertical-align: top;
            width: 50%;
        }
        .summary-table h4 {
            margin: 0 0 8px 0;
            color: #000;
            font-size: 16px;
        }
        
        /* Payment Box */
        .payment-box {
            margin-bottom: 8px;
            padding: 8px;
            background: #f8f9fa;
        }
        
        /* Order Summary Box */
        .order-summary-box h4 {
            border-bottom: 1px solid #000;
            padding-bottom: 8px;
        }
        .order-summary-table {
            width: 100%;
        }
        .order-summary-table td {
            padding: 3px 0;
        }

        /* Outstanding Summary */
        .outstanding-summary {
            margin-top: 15px;
            padding: 12px;
            border: 1.5px solid #e67e22;
            background-color: #fffaf5;
            border-radius: 6px;
        }
        .outstanding-summary h6 {
            margin: 0 0 8px 0;
            color: #e67e22;
            font-weight: bold;
            border-bottom: 1px solid #ffebcc;
            padding-bottom: 4px;
            text-transform: uppercase;
            font-size: 11px;
        }
        .outstanding-table {
            width: 100%;
            font-size: 11px;
            table-layout: fixed;
        }
        .outstanding-table td {
            padding: 3px 0;
        }

        /* Signatures */
        .signatures-table {
            width: 100%;
            text-align: center;
            margin-top: 40px;
        }
        .signatures-table td {
            width: 50%;
        }
        .signatures-table p {
            margin: 5px 0;
        }
        .footer-text {
            text-align: center;
            font-size: 11px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <table class="receipt-header">
        <tr>
            <td style="width: 20%;"></td>
            <td style="width: 60%; text-align: center;">
                <h2 style="font-size: 24pt; letter-spacing: 2px; margin: 0 0 4px 0;">HARDMEN (PVT) LTD</h2>
                <p style="color:#666; font-size:12px; margin: 0;">TOOLS WITH POWER</p>
                <p style="margin: 0;"><strong>421/2, Doolmala, thihariya, Kalagedihena.</strong></p>
                <p style="margin: 0;"><strong>TEL :</strong> (077) 9752950, <strong>EMAIL :</strong> Hardmenlanka@gmail.com</p>
            </td>
            <td style="width: 20%; text-align: right;">
                <h6 style="font-size: 12pt; color: #666; margin: 0; font-weight: bold;">INVOICE</h6>
            </td>
        </tr>
    </table>

    <!-- Info Row -->
    <table class="info-table">
        <tr>
            <td class="customer-col">
                @if($sale->customer && $sale->customer->name !== 'Walking Customer' && $sale->customer->name !== 'Walk-in')
                <p><strong>Name:</strong> {{ $sale->customer->name }}</p>
                <p><strong>Phone:</strong> {{ $sale->customer->phone }}</p>
                <p><strong>Type:</strong> {{ ucfirst($sale->customer->customer_type ?? 'Retail') }}</p>
                @else
                <p style="color: #666;">Walk-in Customer</p>
                @endif
            </td>
            <td class="invoice-col">
                <p><strong>Invoice Number:</strong> {{ $sale->invoice_number }}</p>
                <p><strong>Date:</strong> {{ $sale->created_at->format('d/m/Y h:i A') }}</p>
                <p><strong>Payment Status:</strong> <span style="color:#e67e22; font-weight:bold;">{{ ucfirst($sale->payment_status ?? 'paid') }}</span></p>
            </td>
        </tr>
    </table>

    <!-- Items -->
    <table class="items-table">
        <thead>
            <tr>
                <th width="5%">#</th>
                <th width="15%">Code</th>
                <th width="35%">Item</th>
                <th width="15%" class="text-center">Price</th>
                <th width="10%" class="text-center">Qty</th>
                <th width="10%" class="text-center">Discount</th>
                <th width="10%" class="text-center">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->product_code ?? ($item->product ? $item->product->code : '') }}</td>
                <td>{{ $item->product_name ?? ($item->product ? $item->product->name : '') }}</td>
                <td class="text-right">Rs.{{ number_format($item->unit_price, 2) }}</td>
                <td class="text-right">{{ $item->quantity }}</td>
                <td class="text-right">
                    @php
                        $discountAmount = $item->discount_per_unit ?? 0;
                    @endphp
                    @if($item->discount_type === 'percentage' && $item->discount_percentage > 0)
                        {{ number_format($item->discount_percentage, 0) }}%
                    @elseif($discountAmount > 0)
                        Rs.{{ number_format($discountAmount * $item->quantity, 2) }}
                    @else
                        -
                    @endif
                </td>
                <td class="text-right">Rs.{{ number_format($item->total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Payment & Order Summary -->
    <div class="summary-container">
        <table class="summary-table">
            <tr>
                <td style="padding-right: 20px;">
                    <h4>PAYMENT INFORMATION</h4>
                    @if($sale->payments && $sale->payments->count() > 0)
                        @foreach($sale->payments as $payment)
                        <div class="payment-box" style="border-left: 3px solid {{ $payment->is_completed ? '#28a745' : '#ffc107' }};">
                            <p style="margin:0;"><strong>{{ $payment->is_completed ? 'Payment' : 'Scheduled Payment' }}:</strong> Rs.{{ number_format($payment->amount, 2) }}</p>
                            <p style="margin:0;"><strong>Method:</strong> {{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</p>
                        </div>
                        @endforeach
                    @else
                        <p style="color: #666;">No payment information available</p>
                    @endif
                </td>
                <td style="padding-left: 20px;">
                    <div class="order-summary-box">
                        <h4>ORDER SUMMARY</h4>
                        @php
                            // Calculate original subtotal (before any discounts)
                            $originalSubtotal = $sale->items->sum(function($item) {
                                return $item->unit_price * $item->quantity;
                            });
                            // Total discount = original subtotal - grand total
                            $totalDiscountRs = $originalSubtotal - $sale->total_amount;
                            // Calculate discount percentage
                            $discountPercentage = $originalSubtotal > 0 ? ($totalDiscountRs / $originalSubtotal) * 100 : 0;
                        @endphp
                        <table class="order-summary-table">
                            <tr>
                                <td>Subtotal:</td>
                                <td class="text-right">Rs.{{ number_format($originalSubtotal, 2) }}</td>
                            </tr>
                            @if($totalDiscountRs > 0)
                            <tr>
                                <td>Discount:</td>
                                <td class="text-right">- Rs. {{ number_format($discountPercentage, 2) }}%</td>
                            </tr>
                            @endif
                            <tr>
                                <td colspan="2"><hr style="border: 0; border-top: 1px solid #000; margin: 4px 0;"></td>
                            </tr>
                            <tr>
                                <td><strong>Grand Total:</strong></td>
                                <td class="text-right"><strong>Rs.{{ number_format($sale->total_amount, 2) }}</strong></td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Outstanding Financial Summary --}}
    @if(isset($showDueDetails) && $showDueDetails && $sale->customer && $sale->customer->name !== 'Walking Customer' && $sale->customer->name !== 'Walk-in')
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
    <div style="clear: both;"></div>
    <div class="outstanding-summary">
        <h6>Outstanding Financial Summary</h6>
        <table class="outstanding-table">
            @if($receiptReturnedChequeCount > 0)
            <tr>
                <td style="width: 65%;">Invoice Outstanding Due:</td>
                <td class="text-right" style="width: 35%; font-weight: bold;">Rs. {{ number_format(max(0, $receiptCustomer->total_due - $receiptReturnedChequeAmount), 2) }} ({{ $receiptDueInvoiceCount }} Invoices)</td>
            </tr>
            <tr style="color: #d32f2f;">
                <td>Returned Cheque Amount:</td>
                <td class="text-right" style="font-weight: bold;">Rs. {{ number_format($receiptReturnedChequeAmount, 2) }} ({{ $receiptReturnedChequeCount }} Cheques)</td>
            </tr>
            <tr>
                <td style="border-top: 1px dashed #ffebcc; padding-top: 4px; color: #e67e22; font-weight: bold;">Total Outstanding Due:</td>
                <td class="text-right" style="border-top: 1px dashed #ffebcc; padding-top: 4px; font-weight: bold; color: #e67e22; font-size: 12px;">Rs. {{ number_format($receiptCustomer->total_due, 2) }}</td>
            </tr>
            @else
            <tr>
                <td style="width: 65%;">Remaining Due Amount:</td>
                <td class="text-right" style="width: 35%; font-weight: bold;">Rs. {{ number_format($receiptCustomer->total_due, 2) }}</td>
            </tr>
            @endif
        </table>
    </div>
    @endif

    {{-- Notes --}}
    @if($sale->notes)
    <div style="margin-top: 20px; padding: 10px; background: #f8f9fa; border: 1px solid #dee2e6;">
        <strong>Notes:</strong> {{ $sale->notes }}
    </div>
    @endif

    <div class="footer-container">
        <!-- Signatures -->
        <table class="signatures-table">
            <tr>
                <td>
                    <p><strong>..............................</strong></p>
                    <p><strong>Authorized Signature</strong></p>
                </td>
                <td>
                    <p><strong>..............................</strong></p>
                    <p><strong>Customer Signature</strong></p>
                </td>
            </tr>
        </table>
        
        <div class="footer-text">
            <p style="margin: 0;">Returns accepted within 30 days of purchase with the original invoice. Terms and conditions apply.</p>
            <p style="margin: 0;">Thank you for your business!</p>
            <p style="margin: 0;">www.hardmen.lk | info@hardmen.lk</p>
        </div>
    </div>
</body>
</html>