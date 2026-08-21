<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt - #{{ isset($payments) ? implode('-', $payments->pluck('id')->toArray()) : $payment->id }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            margin: 10mm;
            size: A4;
        }

        body {
            margin: 20px;
            padding: 20px;
            font-family: "DejaVu Sans", Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            background: white;
            color: #000;
        }

        .receipt-container {
            width: 100%;
            padding: 0;
            margin: 0;
            background: white;
            box-sizing: border-box;
        }

        /* Global Header Styles */
        .global-header {
            border-bottom: 3px solid #000000;
            padding-bottom: 5px;
            margin-bottom: 25px;
        }

        .global-header table {
            width: 100%;
            border: none;
            margin-bottom: 25px;
        }

        .global-header td {
            vertical-align: middle;
            border: none;
            padding: 0;
        }

        .global-header .logo-section {
            width: 80px;
        }

        .global-header .logo-section img {
            max-height: 80px;
            width: auto;
        }

        .global-header .company-section {
            text-align: center;
            padding: 0 10px;
        }

        .global-header .company-section h2 {
            font-size: 20pt;
            letter-spacing: 1.5px;
            font-weight: bold;
            margin: 0;
            padding: 0;
            line-height: 1.1;
        }

        .global-header .company-section p {
            font-size: 7pt;
            color: #000000;
            font-weight: 600;
            margin: 2px 0 0 0;
            padding: 0;
            line-height: 1.1;
        }

        .global-header .invoice-section {
            width: 250px;
            text-align: right;
        }

        .global-header .invoice-section h3 {
            font-size: 10pt;
            font-weight: bold;
            margin: 0;
            padding: 0;
            line-height: 1.2;
        }

        .global-header .invoice-section h6 {
            font-size: 9pt;
            font-weight: bold;
            color: #666;
            margin: 1px 0 0 0;
            padding: 0;
            line-height: 1.2;
        }

        /* Content Area */
        .print-content {
            min-height: auto;
            margin-bottom: 20px;
        }

        /* Global Footer Styles */
        .global-footer {
            margin-top: 50px;
            clear: both;
        }

        .global-footer table {
            width: 100%;
            border: none;
            margin-top: 50px;
            margin-bottom: 10px;
        }

        .global-footer td {
            text-align: center;
            vertical-align: bottom;
            border: none;
            padding: 5px;
        }

        .global-footer .signature-line {
            font-size: 9pt;
            font-weight: bold;
            margin: 0;
            padding: 0;
        }

        .global-footer .signature-label {
            font-size: 9pt;
            font-weight: bold;
            margin: 3px 0;
            padding: 0;
        }

        .global-footer img {
            height: 40px;
            margin: 5px auto 0;
            display: block;
        }

        .global-footer .info-section {
            border-top: 2px solid #000000;
            padding-top: 8px;
            margin-top: 8px;
        }

        .global-footer .info-section p {
            text-align: center;
            font-size: 8pt;
            margin: 2px 0;
            padding: 0;
        }

        .info-row {
            margin-bottom: 15px;
        }

        .info-row table {
            width: 100%;
            font-size: 9pt;
        }

        .info-row td {
            vertical-align: top;
            padding: 5px;
        }

        .customer-info {
            width: 50%;
        }

        .invoice-info {
            width: 50%;
            text-align: right;
        }

        .invoice-info table {
            float: right;
            text-align: left;
        }

        .invoice-info table td {
            padding: 2px 5px;
            font-size: 9pt;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .items-table {
            margin: 15px 0;
            font-size: 9pt;
        }

        .items-table th {
            background: #e9ecef;
            padding: 8px 6px;
            border: 1px solid #999;
            font-weight: bold;
            text-align: left;
        }

        .items-table td {
            padding: 6px;
            border: 1px solid #999;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .totals-section {
            margin: 15px 0;
            text-align: right;
            clear: both;
        }

        .totals-table {
            float: right;
            width: 45%;
            font-size: 9pt;
        }

        .totals-table td {
            padding: 4px 8px;
        }

        .totals-table .total-row td {
            border-top: 1px solid #000;
            font-weight: bold;
            padding-top: 8px;
        }

        .info-badge {
            display: inline-block;
            padding: 2px 6px;
            background-color: #e3f2fd;
            color: #1976d2;
            font-size: 8pt;
            border-radius: 3px;
            margin-left: 5px;
        }

        .section-title {
            font-size: 10pt;
            font-weight: bold;
            background: #f8f9fa;
            padding: 6px;
            border-left: 3px solid #000;
            margin-top: 20px;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
    </style>
</head>

<body>
    <div class="receipt-container">

        <div class="global-header">
            <table>
                <tr>
                    <td class="logo-section">
                        <img src="{{ public_path('images/HARDMEN.png') }}" alt="Logo">
                    </td>
                    <td class="invoice-section">
                        <span style="font-size:10pt; font-weight:bold;"></span>
                        <span style="font-size:9pt; font-weight:bold; color:#666; margin-left:8px;">PAYMENT RECEIPT</span>
                    </td>
                </tr>
            </table>
        </div>

        {{-- Customer and Receipt Info --}}
        <div class="info-row">
            <table>
                <tr>
                    <td class="customer-info">
                        <strong>Customer :</strong><br>
                        {{ $customer->name ?? 'Walk-in Customer' }}<br>
                        @if(isset($customer->address) && $customer->address)
                        {{ $customer->address }}<br>
                        @endif
                        Tel: {{ $customer->phone ?? 'N/A' }}
                    </td>
                    <td class="invoice-info">
                        <table>
                            <tr>
                                <td><strong>Receipt ID(s)</strong></td>
                                <td>
                                    #{{ isset($payments) ? implode(', #', $payments->pluck('id')->toArray()) : $payment->id }}
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Date</strong></td>
                                <td>{{ \Carbon\Carbon::parse($payment_date)->format('d/m/Y') }}</td>
                            </tr>
                            <tr>
                                <td><strong>Received By</strong></td>
                                <td>{{ $received_by }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        {{-- Show payment breakdown if there are multiple payments --}}
        @if(isset($payments) && count($payments) > 1)
        <div class="section-title">Payment Breakdown</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 15%;">Receipt ID</th>
                    <th style="width: 25%;">Payment Method</th>
                    <th style="width: 40%;">Reference / Notes</th>
                    <th style="width: 20%;" class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payments as $p)
                <tr>
                    <td>#{{ $p->id }}</td>
                    <td class="text-capitalize">{{ str_replace('_', ' ', $p->payment_method) }}</td>
                    <td>
                        @if($p->payment_method === 'cheque' && $p->cheques->count() > 0)
                            Cheque: {{ $p->cheques->first()->cheque_number }} ({{ $p->cheques->first()->bank_name }})
                        @elseif($p->payment_method === 'bank_transfer')
                            Transfer Ref: {{ $p->transfer_reference ?: 'N/A' }} ({{ $p->bank_name }})
                        @else
                            {{ $p->payment_reference ?: 'N/A' }}
                        @endif
                        @if($p->notes)
                            <div style="font-size: 8pt; color: #555; margin-top: 3px;">Note: {{ $p->notes }}</div>
                        @endif
                    </td>
                    <td class="text-right">Rs.{{ number_format($p->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="section-title">Payment Details</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 30%;">Payment Method</th>
                    <th style="width: 50%;">Reference / Notes</th>
                    <th style="width: 20%;" class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-capitalize">{{ str_replace('_', ' ', $payment->payment_method) }}</td>
                    <td>
                        @if($payment->payment_method === 'cheque' && $payment->cheques && count($payment->cheques) > 0)
                            Cheque: {{ $payment->cheques->first()->cheque_number }} ({{ $payment->cheques->first()->bank_name }})
                        @elseif($payment->payment_method === 'bank_transfer')
                            Transfer Ref: {{ $payment->transfer_reference ?: 'N/A' }} ({{ $payment->bank_name }})
                        @else
                            {{ $payment->payment_reference ?: 'N/A' }}
                        @endif
                        @if($payment->notes)
                            <div style="font-size: 8pt; color: #555; margin-top: 3px;">Note: {{ $payment->notes }}</div>
                        @endif
                    </td>
                    <td class="text-right">Rs.{{ number_format($payment->amount, 2) }}</td>
                </tr>
            </tbody>
        </table>
        @endif

        {{-- Show cheque details if any cheque payments exist --}}
        @if(isset($allCheques) && count($allCheques) > 0)
        <div class="section-title">Cheque Details</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Cheque Number</th>
                    <th>Bank Name</th>
                    <th>Cheque Date</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($allCheques as $cheque)
                <tr>
                    <td>{{ $cheque->cheque_number }}</td>
                    <td>{{ $cheque->bank_name }}</td>
                    <td>{{ \Carbon\Carbon::parse($cheque->cheque_date)->format('d/m/Y') }}</td>
                    <td class="text-right">Rs.{{ number_format($cheque->cheque_amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        @if((!isset($payments) || count($payments) <= 1) && $payment->payment_method === 'bank_transfer')
        <div class="section-title">Bank Transfer Details</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Bank Name</th>
                    <th>Transfer Date</th>
                    <th>Transfer Reference</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $payment->bank_name }}</td>
                    <td>{{ $payment->transfer_date ? \Carbon\Carbon::parse($payment->transfer_date)->format('d/m/Y') : 'N/A' }}</td>
                    <td>{{ $payment->transfer_reference ?: 'N/A' }}</td>
                </tr>
            </tbody>
        </table>
        @endif

        {{-- Payment Allocation Details --}}
        @if($allocations && count($allocations) > 0)
        <div class="section-title">Payment Allocation</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 40%;">Invoice Number</th>
                    <th style="width: 30%;">Invoice Total</th>
                    <th style="width: 30%;" class="text-right">Allocated Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($allocations as $allocation)
                <tr>
                    <td>
                        {{ $allocation->invoice_number }}
                        @if(isset($allocation->return_amount) && $allocation->return_amount > 0)
                        <span class="info-badge">Returns: Rs.{{ number_format($allocation->return_amount, 2) }}</span>
                        @endif
                    </td>
                    <td>
                        @if(isset($allocation->return_amount) && $allocation->return_amount > 0)
                            <span style="text-decoration: line-through; color: #999;">Rs.{{ number_format($allocation->total_amount, 2) }}</span>
                            <br>
                            <strong>Rs.{{ number_format($allocation->adjusted_total, 2) }}</strong>
                        @else
                            Rs.{{ number_format($allocation->total_amount, 2) }}
                        @endif
                    </td>
                    <td class="text-right">Rs.{{ number_format($allocation->allocated_amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        {{-- Totals --}}
        <div class="totals-section">
            <table class="totals-table">
                <tr class="total-row">
                    <td>Total Amount Paid</td>
                    <td class="text-right">Rs.{{ number_format($totalAmountPaid ?? $payment->amount, 2) }}</td>
                </tr>
            </table>
        </div>

        <div class="global-footer" style="position: absolute; bottom: 5;top: auto; width: 100%;">
            <table>
                <tr>
                    <td>
                        <p class="signature-line"><strong>.............................</strong></p>
                        <p class="signature-label"><strong>Customer Signature</strong></p>
                    </td>
                    <td>
                        <p class="signature-line"><strong>.............................</strong></p>
                        <p class="signature-label"><strong>Received By: {{ $received_by }}</strong></p>
                    </td>
                </tr>
            </table>
            <div class="info-section">
                <p><strong>ADDRESS:</strong> Sample address</p>
                <p><strong>TEL:</strong> (077) 1234567 | <strong>EMAIL:</strong> Sample email</p>
            </div>
        </div>
    </div>
</body>

</html>