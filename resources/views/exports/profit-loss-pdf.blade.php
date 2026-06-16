<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Profit & Loss Statement</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 20px; text-transform: uppercase; }
        .header p { margin: 5px 0; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }
        .section-header { background-color: #f8f9fa; font-weight: bold; }
        .indent { padding-left: 20px; }
        .total-row { border-top: 2px solid #333; font-weight: bold; }
        .double-bottom { border-bottom: 3px double #333; }
        .text-danger { color: #dc3545; }
        .text-success { color: #28a745; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Profit & Loss Statement</h1>
        <p>
            @if($startDate && $endDate)
                Period: {{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} to {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}
            @elseif($startDate)
                From: {{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} onwards
            @elseif($endDate)
                Up to {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}
            @else
                Overall Period
            @endif
        </p>
    </div>

    <table>
        <tbody>
            <tr class="section-header">
                <td colspan="2">GROSS SALES REVENUE</td>
            </tr>
            <tr>
                <td class="indent">Total Sales Amount</td>
                <td class="text-right">{{ number_format($incomeTotals['Total Sales Revenue'] ?? 0, 2) }}</td>
            </tr>

            <tr class="section-header">
                <td colspan="2">PRODUCT RETURNS</td>
            </tr>
            <tr>
                <td class="indent">Less: Return Amount (Selling Price)</td>
                <td class="text-right text-danger">({{ number_format($totalReturns, 2) }})</td>
            </tr>
            <tr class="total-row">
                <td>NET SALES REVENUE</td>
                <td class="text-right">{{ number_format($incomeTotals['Net Sales Revenue'] ?? 0, 2) }}</td>
            </tr>

            <tr><td colspan="2" style="border:none; height:10px;"></td></tr>

            <tr class="section-header">
                <td colspan="2">COST OF GOODS SOLD (COGS)</td>
            </tr>
            <tr>
                <td class="indent">Gross Product Cost</td>
                <td class="text-right text-danger">({{ number_format(($incomeTotals['Total COGS'] ?? 0) + $totalReturnsCOGS, 2) }})</td>
            </tr>
            <tr>
                <td class="indent">Less: Return COGS</td>
                <td class="text-right text-success">+{{ number_format($totalReturnsCOGS, 2) }}</td>
            </tr>
            <tr class="total-row">
                <td>NET COGS</td>
                <td class="text-right text-danger">({{ number_format($totalCOGS, 2) }})</td>
            </tr>

            <tr><td colspan="2" style="border:none; height:10px;"></td></tr>

            <tr class="section-header">
                <td colspan="2">GROSS PROFIT</td>
            </tr>
            <tr class="total-row">
                <td>GROSS PROFIT (Net Sales - Net COGS)</td>
                <td class="text-right">{{ number_format($totalRevenue, 2) }}</td>
            </tr>
            <tr>
                <td class="indent text-muted">Gross Profit Margin</td>
                <td class="text-right">{{ $grossProfitPercentage }}%</td>
            </tr>

            <tr><td colspan="2" style="border:none; height:10px;"></td></tr>

            <tr class="section-header">
                <td colspan="2">OPERATING EXPENSES</td>
            </tr>
            @if(!empty($expenseBreakdown))
                @foreach($expenseBreakdown as $category => $details)
                    <tr>
                        <td class="indent">{{ $category }}</td>
                        <td class="text-right text-danger">({{ number_format($details['amount'], 2) }})</td>
                    </tr>
                @endforeach
            @endif
            <tr class="total-row">
                <td>TOTAL EXPENSES</td>
                <td class="text-right text-danger">({{ number_format($totalExpenses, 2) }})</td>
            </tr>

            <tr><td colspan="2" style="border:none; height:10px;"></td></tr>

            <tr class="section-header">
                <td colspan="2">NET PROFIT / (LOSS)</td>
            </tr>
            <tr class="total-row double-bottom">
                <td>NET PROFIT</td>
                <td class="text-right fw-bold {{ $netProfit >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ number_format($netProfit, 2) }}
                </td>
            </tr>
            <tr>
                <td class="indent text-muted">Net Profit Margin</td>
                <td class="text-right">{{ $netProfitPercentage }}%</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
