<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Print Document' }}</title>
    <style>
        /* Global Print Layout Styles */
        @page {
            size: letter portrait;
            margin: 0;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            background: white;
            color: #000;
        }

        .print-container {
            width: 100%;
            background: white;
            position: relative;
            box-sizing: border-box;
            padding: 0;
            margin: 0;
        }

        /* Global Header Styles */
        .global-header {
            border-bottom: 3px solid #000000;
            padding: 10px 15px 10px 15px;
            margin: 0;
            background: white;
        }



        .global-header .d-flex {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .global-header img {
            max-height: 40px;
        }

        .global-header h2 {
            font-size: 2rem;
            letter-spacing: 1.5px;
            font-weight: bold;
            margin-bottom: 0;
        }

        .global-header .text-muted {
            color: #666;
            font-size: 0.75rem;
        }

        .global-header h3 {
            font-size: 1rem;
            margin-bottom: 0;
            font-weight: bold;
        }

        .global-header h5,
        .global-header h6 {
            margin-bottom: 0;
            font-weight: bold;
            font-size: 0.9rem;
        }

        .global-header h6 {
            color: #666;
            margin-top: 5px;
        }

        .global-header hr {
            border-top: 2px solid #000;
            margin: 0.25rem 0;
        }

        /* Content Area */
        .print-content {
            padding: 15px;
            min-height: auto;
        }

        /* Global Footer Styles */
        .global-footer {
            background: white;
            padding: 10px 15px;
            margin: 0;
        }

        .global-footer .row {
            display: flex;
            margin-left: 0;
            margin-right: 0;
        }

        .global-footer .col-4 {
            flex: 0 0 30%;
            max-width: 30%;
            text-align: center;
            padding: 0 10px;
        }

        .global-footer img {
            height: 25px;
            margin: auto;
        }

        .global-footer .border-top {
            border-top: 1px solid #000000;
            padding-top: 8px;
        }

        .global-footer .text-center {
            text-align: center;
            font-size: 9px;
            color: #666;
            margin: 1px 0;
        }

        .global-footer p {
            margin: 1px 0;
            font-size: 9px;
        }

        /* Sale Receipt Specific Styles */
        .invoice-info-row {
            display: flex;
            margin-bottom: 15px;
            font-size: 11px;
            page-break-inside: avoid;
        }

        .invoice-info-row .col-6 {
            flex: 0 0 50%;
            max-width: 50%;
        }

        .invoice-info-row p {
            margin: 2px 0;
            line-height: 1.4;
        }

        .invoice-info-row table {
            font-size: 11px;
        }

        .invoice-info-row td {
            padding: 2px 0;
        }

        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 10px;
            page-break-inside: auto;
        }

        .invoice-table th {
            background-color: #f0f0f0;
            border: 1px solid #000;
            padding: 6px 8px;
            font-weight: bold;
            text-transform: uppercase;
            text-align: center;
            page-break-after: avoid;
        }

        .invoice-table td {
            border: 1px solid #000;
            padding: 5px 8px;
        }

        .invoice-table tbody tr {
            page-break-inside: avoid;
        }

        .invoice-table tfoot .totals-row td {
            border-top: 1px solid #000;
            padding: 5px 8px;
            font-weight: bold;
            page-break-inside: avoid;
        }

        .invoice-table tfoot .grand-total td {
            border-top: 2px solid #000;
            font-size: 11px;
            padding: 7px 8px;
            background-color: #f8f9fa;
            page-break-inside: avoid;
        }

        /* Avoid breaking returned items section */
        .returned-items-section {
            page-break-inside: avoid;
            margin-top: 20px;
        }

        .returned-items-section h6 {
            margin-bottom: 10px;
            font-weight: bold;
            color: #000;
            page-break-after: avoid;
        }

        .text-end {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .text-left {
            text-align: left;
        }

        .fw-bold {
            font-weight: bold;
        }

        .mb-0 {
            margin-bottom: 0;
        }

        .mb-2 {
            margin-bottom: 0.5rem;
        }

        .mb-3 {
            margin-bottom: 1rem;
        }

        .mt-2 {
            margin-top: 0.5rem;
        }

        .pt-3 {
            padding-top: 1rem;
        }

        /* Hide on screen, show on print */
        @media screen {
            body {
                background: #f5f5f5;
                padding: 20px 0;
            }

            .print-container {
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                max-width: 210mm;
                margin: 0 auto;
            }
        }

        @media print {
            html, body {
                background: white !important;
                margin: 0 !important;
                padding: 0 !important;
                height: auto !important;
                min-height: 0 !important;
            }

            .print-container {
                box-shadow: none !important;
                margin: 0 !important;
                padding: 0 !important;
                min-height: 0 !important;
                display: block !important;
                page-break-inside: auto !important;
            }

            @page {
                size: letter portrait;
                margin: 5mm 8mm;
            }

            tr {
                page-break-inside: avoid !important;
            }
        }
    </style>
</head>

<body>
    <div class="print-container">
        <!-- Global Header -->
        <div class="global-header">
            <div class="d-flex align-items-center justify-content-between mb-3">
                {{-- Left: Logo --}}
                <div style="flex: 0 0 120px; margin-right:10px;">
                    <img src="{{ url('images/HARDMEN.png') }}" alt="Logo" class="img-fluid" style="max-height:50px; width:auto;">
                </div>
                {{-- Center: Company Name --}}
                <div class="text-center" style="flex: 1;">
                    <h2 class="mb-0 fw-bold" style="font-size: 2rem; letter-spacing: 1.5px;">HARDMEN (PVT) LTD</h2>
                    <p class="mb-0 text-muted small">TOOLS WITH POWER</p>
                </div>
                {{-- Right:  & Invoice --}}
                <div class="text-end" style="flex: 0 0 120px;">
                    <h3 class="mb-0 fw-bold"></h3>
                    <h6 class="mb-0 text-muted">{{ $documentType ?? 'INVOICE' }}</h6>
                </div>
            </div>
        </div>

        <!-- Dynamic Content Area -->
        <div class="print-content">
            {{ $slot }}
        </div>

        <!-- Global Footer -->
        <div class="global-footer" style="position: absolute; bottom: 5;top: auto; width: 100%;">
            <div class="row text-center mb-3">
                <div class="col-4">
                    <p class=""><strong>.............................</strong></p>
                    <p class="mb-2"><strong>Checked By</strong></p>
                    
                </div>
                <div class="col-4">
                    <p class=""><strong>.............................</strong></p>
                    <p class="mb-2"><strong>Authorized Officer</strong></p>
                    
                </div>
                <div class="col-4">
                    <p class=""><strong>.............................</strong></p>
                    <p class="mb-2"><strong>Customer Stamp</strong></p>
                    
                </div>
            </div>
            <div class="border-top pt-3">
                <p class="text-center"><strong>ADDRESS :</strong> 421/2, Doolmala, thihariya, Kalagedihena.</p>
                <p class="text-center"><strong>TEL :</strong> (076) 9085252, <strong>EMAIL :</strong> Hardmenlanka@gmail.com</p>
                <p class="text-center mt-2" style="font-size: 9px;"><strong></strong></p>
            </div>
        </div>
    </div>

    <!-- Auto-print script -->
    <script>
        // Auto-trigger print when page loads
        window.onload = function() {
            // Small delay to ensure content is fully rendered
            setTimeout(function() {
                window.print();
            }, 500);
        };

        // Optional: Close window after printing or canceling
        window.onafterprint = function() {
            // You can uncomment the line below to auto-close the window after printing
            // window.close();
        };
    </script>
</body>

</html>