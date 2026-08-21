<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payment Receipt - #{{ $payment->id }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #ddd;
            padding: 20px;
            background: white;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        .company-info {
            margin-bottom: 5px;
        }
        .receipt-title {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0;
            color: #198754;
        }
        .receipt-info {
            margin-bottom: 20px;
        }
        .section {
            margin-bottom: 15px;
        }
        .section-title {
            font-weight: bold;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            margin-bottom: 10px;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table th {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-weight: bold;
        }
        table td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .total-row {
            font-weight: bold;
            background-color: #f8f9fa;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        hr {
            border: none;
            border-top: 1px solid #ddd;
            margin: 15px 0;
        }
        .status-paid {
            color: #198754;
            font-weight: bold;
        }
        .status-pending {
            color: #fd7e14;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        {{-- Header --}}
        <div class="header">
            <div class="company-info">
                <h2> DLB ENTERPRISES</h2>
            </div>
            <div class="receipt-title">PAYMENT RECEIPT</div>
        </div>

        {{-- Receipt Information --}}
        <div class="receipt-info">
            <table class="table table-bordered">
                <tr>
                    <td width="50%">
                        <strong>Receipt #:</strong> {{ $payment->id }}<br>
                        <strong>Payment Date:</strong> {{ \Carbon\Carbon::parse($payment->payment_date)->format('d-m-Y') }}<br>
                        <strong>Generated On:</strong> {{ now()->format('d-m-Y H:i') }}
                    </td>
                    <td width="50%">
                        <strong>Payment Method:</strong> {{ strtoupper($payment->payment_method) }}<br>
                        <strong>Status:</strong> 
                        <span class="status-{{ $payment->status }}">{{ strtoupper($payment->status) }}</span><br>
                        @if($payment->payment_reference)
                        <strong>Reference:</strong> {{ $payment->payment_reference }}
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        {{-- Supplier Information --}}
        <div class="section">
            <div class="section-title">SUPPLIER INFORMATION</div>
            <table class="table table-bordered">
                <tr>
                    <td width="30%"><strong>Name:</strong></td>
                    <td>{{ $payment->supplier->name }}</td>
                </tr>
                <tr>
                    <td><strong>Mobile:</strong></td>
                    <td>{{ $payment->supplier->mobile }}</td>
                </tr>
                <tr>
                    <td><strong>Email:</strong></td>
                    <td>{{ $payment->supplier->email ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td><strong>Address:</strong></td>
                    <td>{{ $payment->supplier->address ?? 'N/A' }}</td>
                </tr>
            </table>
        </div>

        {{-- Payment Details --}}
        <div class="section">
            <div class="section-title">PAYMENT DETAILS</div>
            <table class="table table-bordered">
                <tr>
                    <td width="30%"><strong>Total Amount Paid:</strong></td>
                    <td class="text-right"><strong>Rs. {{ number_format($payment->amount, 2) }}</strong></td>
                </tr>
                @if($payment->payment_method === 'cheque')
                <tr>
                    <td><strong>Cheque Number:</strong></td>
                    <td>{{ $payment->cheque_number }}</td>
                </tr>
                <tr>
                    <td><strong>Bank Name:</strong></td>
                    <td>{{ $payment->bank_name }}</td>
                </tr>
                <tr>
                    <td><strong>Cheque Date:</strong></td>
                    <td>{{ $payment->cheque_date ? \Carbon\Carbon::parse($payment->cheque_date)->format('d-m-Y') : 'N/A' }}</td>
                </tr>
                @endif
                @if($payment->payment_method === 'bank_transfer')
                <tr>
                    <td><strong>Bank Name:</strong></td>
                    <td>{{ $payment->bank_name }}</td>
                </tr>
                <tr>
                    <td><strong>Transaction Ref:</strong></td>
                    <td>{{ $payment->bank_transaction }}</td>
                </tr>
                @endif
            </table>
        </div>

        {{-- Payment Allocation --}}
        <div class="section">
            <div class="section-title">PAYMENT ALLOCATION</div>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Order Code</th>
                        <th class="text-right">Due Amount</th>
                        <th class="text-right">Paid Amount</th>
                        <th class="text-right">Remaining</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payment->allocations as $allocation)
                    @php
                        $order = $allocation->order;
                        $remaining = $order->due_amount;
                        $paidAmount = $allocation->allocated_amount;
                    @endphp
                    <tr>
                        <td><strong>{{ $order->order_code }}</strong></td>
                        <td class="text-right">Rs. {{ number_format($order->due_amount + $paidAmount, 2) }}</td>
                        <td class="text-right">Rs. {{ number_format($paidAmount, 2) }}</td>
                        <td class="text-right">Rs. {{ number_format($remaining, 2) }}</td>
                        <td>
                            @if($remaining == 0)
                                <span class="status-paid">FULLY PAID</span>
                            @else
                                <span class="status-pending">PARTIAL PAID</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td><strong>TOTAL</strong></td>
                        <td class="text-right"><strong>Rs. {{ number_format($payment->allocations->sum(function($alloc) { return $alloc->order->due_amount + $alloc->allocated_amount; }), 2) }}</strong></td>
                        <td class="text-right"><strong>Rs. {{ number_format($payment->amount, 2) }}</strong></td>
                        <td class="text-right"><strong>Rs. {{ number_format($payment->allocations->sum('order.due_amount'), 2) }}</strong></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Notes --}}
        @if($payment->notes)
        <div class="section">
            <div class="section-title">NOTES</div>
            <p>{{ $payment->notes }}</p>
        </div>
        @endif

        {{-- Footer --}}
        <div class="footer">
            <p>This is a computer-generated receipt. No signature required.</p>
            <p>Thank you for your payment!</p>
            <p>Generated on: {{ now()->format('d-m-Y H:i:s') }}</p>
        </div>
    </div>
</body>
</html>