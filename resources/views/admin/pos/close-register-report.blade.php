<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Close Register Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #000;
            padding: 15mm;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #000;
        }

        .header img {
            height: 50px;
            width: auto;
            margin-bottom: px;
        }

        .header h1 {
            font-size: 18px;
            font-weight: bold;
            margin: 10px 0;
            text-transform: uppercase;
        }

        .header p {
            font-size: 10px;
            margin: 3px 0;
        }

        .header .company-name {
            font-size: 14px;
            font-weight: bold;
            margin: 5px 0;
        }

        .report-info {
            margin: 15px 0;
            padding: 8px;
            background-color: #f0f0f0;
            border: 1px solid #000;
        }

        .report-info p {
            margin: 3px 0;
            font-size: 10px;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        .summary-table td {
            border: 1px solid #000;
            padding: 6px 8px;
            font-size: 10px;
        }

        .summary-table td:first-child {
            width: 60%;
        }

        .summary-table td:last-child {
            text-align: right;
            width: 40%;
        }

        .summary-table tr.highlight {
            background-color: #fff3cd;
        }

        .summary-table tr.subtotal {
            background-color: #f8f9fa;
            border-top: 2px solid #000;
        }

        .summary-table tr.total {
            background-color: #d1e7dd;
            border-top: 2px solid #000;
            font-weight: bold;
        }

        .summary-table tr.indent td:first-child {
            padding-left: 25px;
        }

        .summary-table .fw-bold {
            font-weight: bold;
        }

        .notes-section {
            margin: 15px 0;
            padding: 10px;
            border: 1px solid #000;
            min-height: 60px;
        }

        .notes-section h4 {
            font-size: 11px;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .notes-section p {
            font-size: 10px;
            line-height: 1.4;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #000;
            font-size: 9px;
        }

        .footer p {
            margin: 3px 0;
        }

        .page-break {
            page-break-after: always;
        }

        @page {
            size: A4 portrait;
            margin: 10mm;
        }
    </style>
</head>

<body>
    {{-- Header --}}
    <div class="header">
        <img src="{{ public_path('images/HARDMEN') }}" alt="HARDMEN Logo">
        <p>Sample address</p>
        <p><strong>TEL:</strong> (077) 1234567 | <strong>EMAIL:</strong> Sample email</p>
        <h1>CLOSE REGISTER SUMMARY</h1>
    </div>

    {{-- Report Information --}}
    <div class="report-info">
        <p><strong>Date:</strong> {{ $close_date }}</p>
        <p><strong>Time:</strong> {{ $close_time }}</p>
        <p><strong>Cashier:</strong> {{ $user }}</p>
        <p><strong>Session Date:</strong> {{ $session->session_date ?? 'N/A' }}</p>
    </div>

    {{-- Summary Table --}}
    <table class="summary-table">
        <tbody>
                            <tr>
                                <td>Cash in hand:</td>
                                <td class="text-end">Rs.{{ number_format($summary['opening_cash'] ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <td>Cash Sales (POS):</td>
                                <td class="text-end">Rs.{{ number_format($summary['pos_cash_sales'] ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <td>Cheque Payment (POS):</td>
                                <td class="text-end">Rs.{{ number_format($summary['pos_cheque_payment'] ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <td>Bank / Online Transfer (POS):</td>
                                <td class="text-end">Rs.{{ number_format($summary['pos_bank_transfer'] ?? 0, 2) }}</td>
                            </tr>
                            <tr class="table-warning">
                                <td class="fw-semibold">Admin Payments - Total:</td>
                                <td class="text-end fw-semibold">Rs.{{ number_format($summary['total_admin_payment'] ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="ps-4">└ Cash:</td>
                                <td class="text-end">Rs.{{ number_format($summary['total_admin_cash_payment'] ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="ps-4">└ Cheque:</td>
                                <td class="text-end">Rs.{{ number_format($summary['total_admin_cheque_payment'] ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="ps-4">└ Bank Transfer:</td>
                                <td class="text-end">Rs.{{ number_format($summary['total_admin_bank_transfer'] ?? 0, 2) }}</td>
                            </tr>
                            <tr class="table-info">
                                <td class="fw-semibold">Total Cash Amount (POS + Admin):</td>
                                <td class="text-end fw-semibold">Rs.{{ number_format($summary['total_cash_from_sales'] ?? 0, 2) }}</td>
                            </tr>
                            <tr class="table-light">
                                <td class="fw-semibold">Total POS Sales:</td>
                                <td class="text-end fw-bold">Rs.{{ number_format($summary['total_pos_sales'] ?? 0, 2) }}</td>
                            </tr>
                            <tr class="table-light">
                                <td class="fw-semibold">Total Admin Sales:</td>
                                <td class="text-end fw-bold">Rs.{{ number_format($summary['total_admin_sales'] ?? 0, 2) }}</td>
                            </tr>
                            <tr class="table-primary">
                                <td class="fw-semibold">Total Cash Payment Today:</td>
                                <td class="text-end fw-bold">Rs.{{ number_format($summary['total_cash_payment_today'] ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <td>Expenses:</td>
                                <td class="text-end">Rs.{{ number_format($summary['expenses'] ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <td>Refunds:</td>
                                <td class="text-end">Rs.{{ number_format($summary['refunds'] ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <td>Total Cash Supplier Payment:</td>
                                <td class="text-end">Rs.{{ number_format($summary['supplier_cash_payment'] ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <td>Total Supplier Payment:</td>
                                <td class="text-end">Rs.{{ number_format($summary['supplier_payment'] ?? 0, 2) }}</td>
                            </tr>

                            <tr class="table-success">
                                <td class="fw-bold">Total Cash in Hand:</td>
                                <td class="text-end fw-bold">Rs.{{ number_format($summary['expected_cash'] ?? 0, 2) }}</td>
                            </tr>
                        </tbody>
    </table>

    {{-- Notes Section --}}
    <div class="notes-section">
        <h4>Notes:</h4>
        <p>{{ $session->notes ?? 'No notes added.' }}</p>
    </div>

    {{-- Cash Difference --}}
    @if(isset($session->closing_cash) && $session->closing_cash > 0)
    @php
    $difference = $session->closing_cash - ($summary['expected_cash'] ?? 0);
    @endphp
    @if($difference != 0)
    <div style="margin: 10px 0; padding: 8px; border: 1px solid #000; background-color: {{ $difference > 0 ? '#fff3cd' : '#f8d7da' }};">
        <p><strong>Cash Difference:</strong> Rs.{{ number_format(abs($difference), 2) }} ({{ $difference > 0 ? 'Excess' : 'Short' }})</p>
    </div>
    @else
    @endif
    @endif

    {{-- Footer --}}
    <div class="footer">
        <p>Date: {{ now()->format('d/m/Y H:i:s') }}</p>
        <p><strong>DLB ENTERPRISES</strong> - Point of Sale System</p>
    </div>
</body>

</html>