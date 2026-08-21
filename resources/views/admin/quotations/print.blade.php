<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation - {{ $quotation->quotation_number }}</title>
    <style>
        @page {
            margin: 15mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #000;
            background: white;
            padding: 20px;
        }

        .quotation-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
        }

        /* Header with Logo and Company Name */
        .header-section {
            text-align: center;
            margin-bottom: 15px;
        }

        .logo {
            max-width: 120px;
            height: auto;
            margin-bottom: 10px;
        }

        .company-name {
            font-size: 24px;
            font-weight: bold;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }

        .company-tagline {
            font-size: 10px;
            color: #666;
            margin-bottom: 15px;
        }

        .quotation-title {
            font-size: 16px;
            font-weight: bold;
            padding: 8px 0;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            margin-bottom: 20px;
        }

        /* Customer and Quotation Info */
        .info-section {
            margin-bottom: 20px;
        }

        .info-row {
            margin-bottom: 15px;
        }

        .info-label {
            font-weight: bold;
            margin-bottom: 3px;
        }

        .info-details {
            line-height: 1.6;
        }

        .quotation-details {
            margin-top: 10px;
        }

        .detail-row {
            display: flex;
            margin-bottom: 3px;
        }

        .detail-label {
            font-weight: bold;
            width: 100px;
            flex-shrink: 0;
        }

        .detail-value {
            flex: 1;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .items-table th {
            background-color: #f0f0f0;
            border: 1px solid #000;
            padding: 8px 5px;
            text-align: left;
            font-weight: bold;
            font-size: 10px;
        }

        .items-table td {
            border: 1px solid #000;
            padding: 6px 5px;
            font-size: 10px;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .items-table tbody tr:nth-child(even) {
            background-color: #fafafa;
        }

        /* Totals Section */
        .totals-section {
            margin: 20px 0;
            text-align: right;
        }

        .totals-table {
            display: inline-block;
            min-width: 300px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            border-bottom: 1px solid #ddd;
        }

        .total-row.grand-total {
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            font-weight: bold;
            font-size: 12px;
            padding: 8px 0;
            margin-top: 5px;
        }

        .total-label {
            font-weight: bold;
            padding-right: 30px;
        }

        .total-value {
            text-align: right;
            min-width: 100px;
        }

        /* Terms and Notes */
        .terms-section {
            margin: 20px 0;
        }

        .section-title {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .section-content {
            white-space: pre-line;
            line-height: 1.6;
        }

        /* Footer Signatures */
        .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-around;
            text-align: center;
        }

        .signature-box {
            flex: 1;
        }

        .signature-line {
            border-top: 1px solid #000;
            width: 150px;
            margin: 40px auto 5px;
        }

        .signature-label {
            font-weight: bold;
            font-size: 10px;
        }

        /* Company Footer */
        .company-footer {
            margin-top: 30px;
            text-align: center;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            font-size: 10px;
        }

        .company-footer p {
            margin: 3px 0;
        }

        .small-text {
            font-size: 9px;
            font-style: italic;
        }

        @media print {
            body {
                padding: 0;
            }
            .quotation-container {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="quotation-container">
        {{-- Header Section --}}
        <div class="header-section">
            @if(file_exists(public_path('images/HARDMEN.png')))
            <img src="{{ public_path('images/HARDMEN.png') }}" alt="Company Logo" class="logo">
            @endif
            <div class="company-name">DLB ENTERPRISES</div>
            <div class="company-tagline">TOOLS WITH POWER</div>
            <div class="quotation-title">QUOTATION</div>
        </div>

        {{-- Customer Information --}}
        <div class="info-section">
            <div class="info-row">
                <div class="info-label">Customer :</div>
                <div class="info-details">
                    {{ $quotation->customer_name }}<br>
                    {{ $quotation->customer_address }}<br>
                    <strong>Tel:</strong> {{ $quotation->customer_phone }}
                </div>
            </div>

            <div class="quotation-details">
                <div class="detail-row">
                    <div class="detail-label">Quotation #:</div>
                    <div class="detail-value">{{ $quotation->quotation_number }}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Date:</div>
                    <div class="detail-value">{{ $quotation->quotation_date->format('d/m/Y') }}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Valid Until:</div>
                    <div class="detail-value">{{ \Carbon\Carbon::parse($quotation->valid_until)->format('d/m/Y') }}</div>
                </div>
            </div>
        </div>

        {{-- Items Table --}}
        <table class="items-table">
            <thead>
                <tr>
                    <th width="5%" class="text-center">#</th>
                    <th width="40%">DESCRIPTION</th>
                    <th width="10%" class="text-center">QTY</th>
                    <th width="15%" class="text-right">UNIT PRICE</th>
                    <th width="15%" class="text-right">DISCOUNT</th>
                    <th width="15%" class="text-right">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                @foreach($quotation->items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        {{ $item['product_name'] }}<br>
                        <span style="color: #666; font-size: 9px;">{{ $item['product_code'] }}</span>
                    </td>
                    <td class="text-center">{{ $item['quantity'] }}</td>
                    <td class="text-right">Rs.{{ number_format($item['unit_price'], 2) }}</td>
                    <td class="text-right">Rs.{{ number_format($item['discount_per_unit'] ?? 0, 2) }}</td>
                    <td class="text-right">Rs.{{ number_format($item['total'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Totals Section --}}
        <div class="totals-section">
            <div class="totals-table">
                <div class="total-row">
                    <div class="total-label">Subtotal</div>
                    <div class="total-value">Rs.{{ number_format($quotation->subtotal, 2) }}</div>
                </div>
                @if($quotation->discount_amount > 0)
                <div class="total-row">
                    <div class="total-label">Discount</div>
                    <div class="total-value">-Rs.{{ number_format($quotation->discount_amount, 2) }}</div>
                </div>
                @endif
                <div class="total-row grand-total">
                    <div class="total-label">Grand Total</div>
                    <div class="total-value">Rs.{{ number_format($quotation->total_amount, 2) }}</div>
                </div>
            </div>
        </div>

        {{-- Terms & Conditions --}}
        @if($quotation->terms_conditions)
        <div class="terms-section">
            <div class="section-title">Terms & Conditions</div>
            <div class="section-content">{{ $quotation->terms_conditions }}</div>
        </div>
        @endif

        {{-- Signature Section --}}
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-label">Checked By</div>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-label">Authorized Officer</div>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-label">Customer Stamp</div>
            </div>
        </div>

        {{-- Company Footer --}}
        <div class="company-footer">
            <p><strong>ADDRESS :</strong> Sample address</p>
            <p><strong>TEL :</strong> (077) 1234567, <strong>EMAIL :</strong> Sample email</p>
            <p class="small-text">This quotation is valid until {{ \Carbon\Carbon::parse($quotation->valid_until)->format('d/m/Y') }}.</p>
        </div>
    </div>

    <script>
        window.onload = function() {
            window.print();
            // Close window after printing (optional)
            // window.onafterprint = function() { window.close(); };
        };
    </script>
</body>
</html>