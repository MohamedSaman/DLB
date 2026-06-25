<!DOCTYPE html>
<html>
<head>
    <title>Return Receipt</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        .company-name { font-size: 24px; font-weight: bold; color: #333; }
        .receipt-title { font-size: 20px; margin: 10px 0; }
        .details-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .details-table td, .details-table th { padding: 8px; border: 1px solid #ddd; }
        .details-table .label { font-weight: bold; background-color: #f8f9fa; width: 30%; }
        .details-table th { background-color: #333; color: white; text-align: left; }
        .total-row { background-color: #f8f9fa; font-weight: bold; }
        .footer { margin-top: 50px; text-align: center; font-size: 12px; color: #666; }
        .signature-area { margin-top: 60px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .items-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .items-table th, .items-table td { padding: 8px; border: 1px solid #ddd; text-align: left; }
        .items-table th { background-color: #333; color: white; }
    </style>
</head>
<body>
    @php
        $representative = $returns->first();
    @endphp
    <div class="header">
        <div class="company-name">HARDMEN (PVT) LTD</div>
        <div class="receipt-title">PRODUCT RETURN RECEIPT</div>
    </div>

    <table class="details-table">
        <tr>
            <td class="label">Invoice No</td>
            <td>{{ $representative->sale?->invoice_number ?? '-' }}</td>
            <td class="label">Latest Return Date</td>
            <td>{{ $representative->created_at->format('M d, Y H:i') }}</td>
        </tr>
        <tr>
            <td class="label">Customer Name</td>
            <td>{{ $representative->sale?->customer?->name ?? 'Walk-in Customer' }}</td>
            <td class="label">Phone</td>
            <td>{{ $representative->sale?->customer?->phone ?? 'N/A' }}</td>
        </tr>
    </table>

    <div style="margin: 20px 0;">
        <h3 style="margin-bottom: 10px;">Returned Items:</h3>
        <table class="items-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Item Code</th>
                    <th>Description</th>
                    <th class="text-center">Usable Qty</th>
                    <th class="text-center">Damage Qty</th>
                    <th class="text-center">Total Qty</th>
                    <th class="text-right">Unit Price</th>
                    <th class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($returns as $idx => $item)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{{ $item->product?->code ?? 'N/A' }}</td>
                    <td>{{ $item->product?->name ?? 'N/A' }}</td>
                    <td class="text-center">{{ $item->usable_quantity }}</td>
                    <td class="text-center">{{ $item->damaged_quantity }}</td>
                    <td class="text-center">{{ $item->return_quantity }}</td>
                    <td class="text-right">Rs.{{ number_format($item->selling_price, 2) }}</td>
                    <td class="text-right">Rs.{{ number_format($item->total_amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <table class="details-table">
        <tr class="total-row">
            <td class="label">Total Return Amount</td>
            <td class="text-right">Rs.{{ number_format($returns->sum('total_amount'), 2) }}</td>
        </tr>
    </table>

    <div style="margin-top: 20px;">
        <strong>Notes:</strong>
        @foreach($returns as $item)
            @if($item->notes)
                <p><strong>{{ $item->product?->name }}:</strong> {{ $item->notes }}</p>
            @endif
        @endforeach
    </div>

    <div class="signature-area">
        <table style="width: 100%;">
            <tr>
                <td style="width: 50%;">
                    <div style="border-top: 1px solid #333; padding-top: 10px;">
                        Customer Signature
                    </div>
                </td>
                <td style="width: 50%;">
                    <div style="border-top: 1px solid #333; padding-top: 10px;">
                        Authorized Signature
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p>ADDRESS: 421/2, Doolmala, thihariya, Kalagedihena.</p>
        <p>TEL: (077) 9752950 | EMAIL: Hardmenlanka@gmail.com</p>
        <p>Generated on: {{ now()->format('M d, Y h:i A') }}</p>
    </div>
</body>
</html>
