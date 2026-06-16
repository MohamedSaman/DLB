<div>
    @push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        /* P&L Analytics Styles - Default Theme */
        .pl-metric-card {
            background: white;
            border-radius: 8px;
            padding: 1.25rem;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            border: none;
            transition: all 0.3s ease;
            height: 100%;
        }

        .pl-metric-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .metric-icon {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 15px;
        }

        .revenue-icon { background: #198754; }
        .expense-icon { background: #dc3545; }
        .salary-icon { background: #ffc107; }
        .profit-icon { background: #000000; }

        .metric-content h6 {
            color: #6c757d;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .metric-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: #212529;
            margin-bottom: 8px;
            line-height: 1.2;
        }

        .metric-percentage {
            font-size: 0.875rem;
            font-weight: 500;
            color: #6c757d;
        }

        .pl-chart-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: none;
            overflow: hidden;
        }

        .chart-header {
            background: #000000;
            padding: 1.25rem;
            border-bottom: none;
        }

        .chart-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: white;
            margin-bottom: 4px;
        }

        .chart-subtitle {
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 0;
        }

        .chart-body {
            padding: 1.5rem;
        }

        .filter-card {
            background: white;
            border-radius: 10px;
            padding: 1.25rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: none;
        }

        .filter-card .form-label {
            font-size: 0.875rem;
            font-weight: 600;
            color: #212529;
            margin-bottom: 8px;
        }

        .filter-card .form-control {
            border-radius: 8px;
            border: 1px solid #dee2e6;
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
        }

        .filter-card .form-control:focus {
            border-color: #000000;
            box-shadow: 0 0 0 0.2rem rgba(142, 185, 34, 0.25);
        }

        .filter-card .btn {
            border-radius: 8px;
            font-weight: 600;
            padding: 0.5rem 1rem;
        }

        .filter-card .btn-primary {
            background: #000000;
            border-color: #000000;
        }

        .filter-card .btn-primary:hover {
            background: #7aa51d;
            border-color: #7aa51d;
        }

        .filter-card .btn-secondary {
            background: #6c757d;
            border-color: #6c757d;
        }

        .filter-card .btn-secondary:hover {
            background: #5a6268;
            border-color: #545b62;
        }

        .pl-header {
            margin-bottom: 2rem;
        }

        .pl-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #212529;
            margin-bottom: 0.5rem;
        }

        .pl-header .subtitle {
            font-size: 1rem;
            color: #6c757d;
            margin-bottom: 0;
        }

        .table thead tr th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            color: white;
            padding: 1rem;
        }

        .table tbody tr {
            transition: all 0.2s ease;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa !important;
        }

        .table tbody td {
            padding: 0.75rem;
            vertical-align: middle;
        }

        .btn-success {
            background: #198754;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            padding: 0.5rem 1.5rem;
        }

        .btn-success:hover {
            background: #157347;
        }

        .stat-badge {
            display: inline-block;
            padding: 0.35rem 0.65rem;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .badge-success {
            background: #198754;
            color: white;
        }

        .badge-danger {
            background: #dc3545;
            color: white;
        }

        .badge-warning {
            background: #ffc107;
            color: #212529;
        }

        .info-box {
            background: white;
            border-radius: 8px;
            padding: 1rem 1.25rem;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            border-left: 4px solid #000000;
        }
    </style>
    @endpush

    <!-- Header Section -->
    <div class="container-fluid mb-5 pl-header">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1>
                    <i class="fas fa-chart-line me-3" style="color: #000000;"></i>Profit & Loss Statement
                </h1>
                <p class="subtitle">📊 Financial overview and performance analysis</p>
            </div>
            <div class="col-md-4 text-end">
                <button wire:click="exportPDF" wire:loading.attr="disabled" wire:target="exportPDF" class="btn btn-success btn-lg shadow-lg" title="Export PDF">
                    <span wire:loading.remove wire:target="exportPDF">
                        <i class="fas fa-file-pdf me-2"></i>Export PDF
                    </span>
                    <span wire:loading wire:target="exportPDF">
                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                        Exporting...
                    </span>
                </button>
            </div>
        </div>
    </div>

    <!-- Date Filter Section -->
    <div class="container-fluid mb-4">
        <div class="filter-card">
            <div class="row">
                <div class="col-md-3 mb-3 mb-md-0">
                    <label class="form-label">Start Date (Optional)</label>
                    <input type="date" wire:model="startDate" class="form-control" placeholder="Leave empty for all data">
                </div>
                <div class="col-md-3 mb-3 mb-md-0">
                    <label class="form-label">End Date (Optional)</label>
                    <input type="date" wire:model="endDate" class="form-control" placeholder="Leave empty for all data">
                </div>
                <div class="col-md-3 mb-3 mb-md-0">
                    <label class="form-label">&nbsp;</label>
                    <button class="btn btn-primary w-100 btn-lg" wire:click="loadData">
                        <i class="fas fa-search me-2"></i>Apply Filter
                    </button>
                </div>
                <div class="col-md-3 mb-3 mb-md-0">
                    <label class="form-label">&nbsp;</label>
                    <button class="btn btn-secondary w-100 btn-lg" wire:click="resetFilters">
                        <i class="fas fa-redo me-2"></i>Reset
                    </button>
                </div>
            </div>
            <div class="mt-3">
                <small class="text-muted d-block info-box">
                    <i class="fas fa-info-circle me-2"></i>
                    @if($startDate && $endDate)
                        <strong>📅 Period:</strong> {{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} to {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}
                    @elseif($startDate)
                        <strong>📅 From:</strong> {{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} onwards
                    @elseif($endDate)
                        <strong>📅 Until:</strong> {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}
                    @else
                        <strong>📅 Showing:</strong> Overall P&L (All Data)
                    @endif
                </small>
            </div>
        </div>
    </div>

    <!-- Main Summary Cards -->
    <div class="container-fluid mb-5">
        <div class="row">
            <!-- Total Revenue Card -->
            <div class="col-xl-4 col-md-6 mb-3">
                <div class="pl-metric-card">
                    <div class="metric-icon revenue-icon">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                    <div class="metric-content">
                        <h6>Total Revenue</h6>
                        <div class="metric-value text-success">Rs. {{ $totalRevenue > 0 ? number_format($totalRevenue, 2) : '0.00' }}</div>
                        <p class="metric-percentage">💰 Net income from sales</p>
                    </div>
                </div>
            </div>

            <!-- Total Expenses Card -->
            <div class="col-xl-4 col-md-6 mb-3">
                <div class="pl-metric-card">
                    <div class="metric-icon expense-icon">
                        <i class="fas fa-arrow-down"></i>
                    </div>
                    <div class="metric-content">
                        <h6>Total Expenses</h6>
                        <div class="metric-value text-danger">Rs. {{ $totalExpenses > 0 ? number_format($totalExpenses, 2) : '0.00' }}</div>
                        <p class="metric-percentage">📉 Operating costs</p>
                    </div>
                </div>
            </div>

            <!-- Net Profit Card -->
            <div class="col-xl-4 col-md-6 mb-3">
                <div class="pl-metric-card">
                    <div class="metric-icon {{ $netProfit >= 0 ? 'profit-icon' : '' }}" style="{{ $netProfit < 0 ? 'background: #dc3545;' : '' }}">
                        <i class="fas fa-{{ $netProfit >= 0 ? 'chart-line' : 'exclamation-triangle' }}"></i>
                    </div>
                    <div class="metric-content">
                        <h6>Net Profit</h6>
                        <div class="metric-value {{ $netProfit >= 0 ? 'text-success' : 'text-danger' }}">Rs. {{ number_format($netProfit, 2) }}</div>
                        <p class="metric-percentage">{{ $netProfit >= 0 ? '📈' : '📉' }} {{ $netProfitPercentage }}% margin</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- P&L Statement Details -->
    <div class="container-fluid mb-5">
        <div class="pl-chart-card">
            <div class="chart-header">
                <h5 class="chart-title"><i class="fas fa-list me-2"></i>Profit & Loss Statement</h5>
                <p class="chart-subtitle">Detailed financial breakdown</p>
            </div>
            <div class="chart-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <tbody>
                            <!-- Revenue Section -->
                            <tr style="background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); border-left: 4px solid #28a745;">
                                <td colspan="2" class="fw-bold text-success fs-5">📈 GROSS SALES REVENUE</td>
                            </tr>
                            <tr>
                                <td class="ps-4">Total Sales Amount</td>
                                <td class="text-end fw-bold text-success">
                                    {{ number_format($incomeTotals['Total Sales Revenue'] ?? 0, 2) }}
                                </td>
                            </tr>
                            @if(!empty($revenueBreakdown))
                                @foreach($revenueBreakdown as $type => $amount)
                                    <tr class="table-light" style="border-left: 2px solid #28a745;">
                                        <td class="ps-5">└─ {{ $type }}</td>
                                        <td class="text-end text-success">{{ number_format($amount, 2) }}</td>
                                    </tr>
                                @endforeach
                            @endif

                            <tr style="height: 8px; background: transparent;"></tr>

                            <!-- Returns Section -->
                            <tr style="background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%); border-left: 4px solid #dc3545;">
                                <td colspan="2" class="fw-bold text-danger fs-5">🔄 PRODUCT RETURNS</td>
                            </tr>
                            <tr>
                                <td class="ps-4">Return Amount (Selling Price)</td>
                                <td class="text-end fw-bold text-danger">
                                    ({{ number_format($totalReturns, 2) }})
                                </td>
                            </tr>
                            <tr style="background: #e9ecef; border-left: 4px solid #6c757d;">
                                <td class="ps-4 fw-bold">NET SALES REVENUE</td>
                                <td class="text-end fw-bold fs-6">
                                    {{ number_format($incomeTotals['Net Sales Revenue'] ?? 0, 2) }}
                                </td>
                            </tr>

                            <tr style="height: 8px; background: transparent;"></tr>

                            <!-- Cost of Goods Sold Section -->
                            <tr style="background: linear-gradient(135deg, #ffe7e7 0%, #f5c6cb 100%); border-left: 4px solid #dc3545;">
                                <td colspan="2" class="fw-bold text-danger fs-5">📦 COST OF GOODS SOLD (COGS)</td>
                            </tr>
                            <tr>
                                <td class="ps-4">Gross Product Cost</td>
                                <td class="text-end fw-bold text-danger">
                                    ({{ number_format(($incomeTotals['Total COGS'] ?? 0) + $totalReturnsCOGS, 2) }})
                                </td>
                            </tr>
                            <tr>
                                <td class="ps-4">Less: Return COGS</td>
                                <td class="text-end fw-bold text-success">
                                    +{{ number_format($totalReturnsCOGS, 2) }}
                                </td>
                            </tr>
                            <tr style="background: #ffe7e7; border-left: 4px solid #dc3545;">
                                <td class="ps-4 fw-bold text-danger">Net COGS</td>
                                <td class="text-end fw-bold text-danger fs-6">
                                    ({{ number_format($totalCOGS, 2) }})
                                </td>
                            </tr>

                            <tr style="height: 8px; background: transparent;"></tr>

                            <!-- Net Revenue (Gross Profit) -->
                            <tr style="background: linear-gradient(135deg, #fff3cd 0%, #fce4a6 100%); border-left: 4px solid #ffc107;">
                                <td class="fw-bold text-warning fs-5">✅ GROSS PROFIT (Net Sales - Net COGS)</td>
                                <td class="text-end fw-bold text-warning" style="font-size: 1.1rem;">
                                    {{ number_format($totalRevenue, 2) }}
                                </td>
                            </tr>
                            <tr class="table-light">
                                <td class="ps-5 text-muted small">Gross Profit Margin</td>
                                <td class="text-end text-muted small">{{ $grossProfitPercentage }}%</td>
                            </tr>

                            <tr style="height: 8px; background: transparent;"></tr>

                            <!-- Operating Expenses Section -->
                            <tr style="background: linear-gradient(135deg, #fff3cd 0%, #fce4a6 100%); border-left: 4px solid #ffc107;">
                                <td colspan="2" class="fw-bold text-warning fs-5">💼 OPERATING EXPENSES</td>
                            </tr>
                            <tr>
                                <td class="ps-4">Total Expenses</td>
                                <td class="text-end fw-bold text-warning">
                                    {{ number_format($totalExpenses, 2) }}
                                </td>
                            </tr>
                            @if(!empty($expenseBreakdown))
                                @foreach($expenseBreakdown as $category => $details)
                                    <tr class="table-light" style="border-left: 2px solid #ffc107;">
                                        <td class="ps-5">└─ {{ $category }}</td>
                                        <td class="text-end text-warning">{{ number_format($details['amount'], 2) }}</td>
                                    </tr>
                                @endforeach
                            @endif

                            <tr class="table-light" style="border-left: 2px solid #ffc107;">
                                <td class="ps-5">Expenses Total</td>
                                <td class="text-end fw-bold text-warning">
                                    {{ number_format($totalExpenses, 2) }}
                                </td>
                            </tr>

                            <tr style="height: 8px; background: transparent;"></tr>

                            <!-- Net Profit -->
                            <tr style="background: linear-gradient(135deg, {{ $netProfit >= 0 ? '#d4edda 0%, #c3e6cb' : '#ffe7e7 0%, #f5c6cb' }} 100%); border-left: 4px solid {{ $netProfit >= 0 ? '#28a745' : '#dc3545' }};">
                                <td class="fw-bold {{ $netProfit >= 0 ? 'text-success' : 'text-danger' }}" style="font-size: 1.1rem;">💰 NET PROFIT / (LOSS)</td>
                                <td class="text-end fw-bold {{ $netProfit >= 0 ? 'text-success' : 'text-danger' }}" style="font-size: 1.2rem;">
                                    {{ number_format($netProfit, 2) }}
                                </td>
                            </tr>
                            <tr class="table-light">
                                <td class="ps-5 text-muted small">Net Profit Margin</td>
                                <td class="text-end text-muted small">{{ $netProfitPercentage }}%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Breakdown Sections -->
    <div class="container-fluid mb-5">
        <div class="row">
            <!-- Revenue by Payment Method -->
            <div class="col-lg-6 mb-3">
                <div class="pl-chart-card">
                    <div class="chart-header">
                        <h5 class="chart-title"><i class="fas fa-credit-card me-2"></i>Revenue by Payment Method</h5>
                        <p class="chart-subtitle">Income distribution across payment channels</p>
                    </div>
                    <div class="chart-body">
                        @if(!empty($paymentMethodWiseRevenue))
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead style="background: linear-gradient(135deg, #2a83df 0%, #1a5fb8 100%);">
                                        <tr>
                                            <th>Payment Method</th>
                                            <th class="text-end">Amount</th>
                                            <th class="text-end">%</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($paymentMethodWiseRevenue as $method => $amount)
                                            <tr style="border-left: 3px solid #28a745;">
                                                <td><i class="fas fa-circle" style="font-size: 8px; color: #28a745; margin-right: 8px;"></i>{{ $method }}</td>
                                                <td class="text-end fw-bold text-success">{{ number_format($amount, 2) }}</td>
                                                <td class="text-end text-muted small">{{ $totalRevenue > 0 ? number_format(($amount / $totalRevenue) * 100, 1) : 0 }}%</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted text-center mb-0">No data available</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Expenses by Category -->
            <div class="col-lg-6 mb-3">
                <div class="pl-chart-card">
                    <div class="chart-header">
                        <h5 class="chart-title"><i class="fas fa-tag me-2"></i>Expenses by Category</h5>
                        <p class="chart-subtitle">Operating costs breakdown</p>
                    </div>
                    <div class="chart-body">
                        @if(!empty($expenseByCategoryTotals))
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead style="background: linear-gradient(135deg, #2a83df 0%, #1a5fb8 100%);">
                                        <tr>
                                            <th>Category</th>
                                            <th class="text-end">Amount</th>
                                            <th class="text-end">%</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($expenseByCategoryTotals as $category => $amount)
                                            <tr style="border-left: 3px solid #dc3545;">
                                                <td><i class="fas fa-circle" style="font-size: 8px; color: #dc3545; margin-right: 8px;"></i>{{ $category }}</td>
                                                <td class="text-end fw-bold text-danger">{{ number_format($amount, 2) }}</td>
                                                <td class="text-end text-muted small">{{ $totalExpenses > 0 ? number_format(($amount / $totalExpenses) * 100, 1) : 0 }}%</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted text-center mb-0">No expenses recorded</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- All Outgoing Breakdown -->
    <div class="container-fluid mb-5">
        <div class="pl-chart-card">
            <div class="chart-header">
                <h5 class="chart-title"><i class="fas fa-money-bill-wave me-2"></i>Total Cashout Money Breakdown</h5>
                <p class="chart-subtitle">Complete expense distribution overview</p>
            </div>
            <div class="chart-body">
                @if(!empty($allOutgoingBreakdown))
                    <div class="row">
                        @foreach($allOutgoingBreakdown as $category => $amount)
                            <div class="col-md-4 mb-3">
                                <div class="p-4 border rounded-3 text-center transition-all" style="background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%); border: 1px solid rgba(0,0,0,0.05); cursor: pointer;" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 32px rgba(0,0,0,0.12)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.08)';">
                                    <h6 class="text-muted mb-3" style="font-weight: 600; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">{{ $category }}</h6>
                                    <h4 class="text-danger fw-bold mb-2">{{ number_format($amount, 2) }}</h4>
                                    <small class="text-muted" style="font-size: 12px;">
                                        {{ $totalOutgoing > 0 ? number_format(($amount / $totalOutgoing) * 100, 1) : 0 }}% of total
                                    </small>
                                </div>
                            </div>
                        @endforeach
                        <div class="col-md-4 mb-3">
                            <div class="p-4 border-3 rounded-3 text-center" style="background: linear-gradient(135deg, #ffe7e7 0%, #f5c6cb 100%); border: 2px solid #dc3545;">
                                <h6 class="text-danger mb-3 fw-bold" style="font-weight: 700; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">TOTAL CASHOUT</h6>
                                <h3 class="text-danger fw-bold mb-0" style="font-size: 28px;">{{ number_format($totalOutgoing, 2) }}</h3>
                            </div>
                        </div>
                    </div>
                @else
                    <p class="text-muted text-center mb-0">No outgoing data available</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Monthly Trends -->
    <div class="container-fluid mb-5">
        <div class="pl-chart-card">
            <div class="chart-header">
                <h5 class="chart-title"><i class="fas fa-chart-bar me-2"></i>Monthly Trends & Performance</h5>
                <p class="chart-subtitle">Monthly revenue, expenses, and profit analysis</p>
            </div>
            <div class="chart-body">
                @if(!empty($monthlyTrends))
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead style="background: linear-gradient(135deg, #2a83df 0%, #1a5fb8 100%);">
                                <tr>
                                    <th>📅 Month</th>
                                    <th class="text-end">📈 Revenue</th>
                                    <th class="text-end">📦 COGS</th>
                                    <th class="text-end">💰 Expenses</th>
                                    <th class="text-end">✅ Profit</th>
                                    <th class="text-end">% Margin</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($monthlyTrends as $trend)
                                    <tr style="border-left: 3px solid {{ $trend['profit'] >= 0 ? '#28a745' : '#dc3545' }};">
                                        <td class="fw-bold">{{ $trend['month'] }}</td>
                                        <td class="text-end text-success fw-bold">{{ number_format($trend['revenue'], 2) }}</td>
                                        <td class="text-end text-warning">{{ number_format($trend['cogs'], 2) }}</td>
                                        <td class="text-end text-danger">{{ number_format($trend['expenses'], 2) }}</td>
                                        <td class="text-end fw-bold {{ $trend['profit'] >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($trend['profit'], 2) }}</td>
                                        <td class="text-end text-muted small">
                                            {{ $trend['revenue'] > 0 ? number_format(($trend['profit'] / $trend['revenue']) * 100, 1) : 0 }}%
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted text-center mb-0">No monthly data available for the selected period</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Summary Statistics -->
    <div class="container-fluid mb-5">
        <div class="row">
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="pl-metric-card">
                    <div class="metric-icon" style="background: #198754;">
                        <i class="fas fa-percent"></i>
                    </div>
                    <div class="metric-content">
                        <h6>Gross Profit Margin</h6>
                        <div class="metric-value text-success">{{ $grossProfitPercentage }}%</div>
                        <p class="metric-percentage">Efficiency ratio</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="pl-metric-card">
                    <div class="metric-icon" style="background: #0d6efd;">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <div class="metric-content">
                        <h6>Net Profit Margin</h6>
                        <div class="metric-value text-primary">{{ $netProfitPercentage }}%</div>
                        <p class="metric-percentage">Bottom line ratio</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="pl-metric-card">
                    <div class="metric-icon" style="background: #dc3545;">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="metric-content">
                        <h6>Expense Ratio</h6>
                        <div class="metric-value text-danger">
                            {{ $totalRevenue > 0 ? number_format((($totalExpenses + $totalSalaries) / $totalRevenue) * 100, 1) : 0 }}%
                        </div>
                        <p class="metric-percentage">Cost burden</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="pl-metric-card">
                    <div class="metric-icon" style="background: #ffc107;">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div class="metric-content">
                        <h6>COGS Ratio</h6>
                        <div class="metric-value text-warning">
                            {{ $totalRevenue > 0 ? number_format(($totalCOGS / $totalRevenue) * 100, 1) : 0 }}%
                        </div>
                        <p class="metric-percentage">Product cost %</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="container-fluid mb-4">
        <div class="alert alert-info alert-dismissible fade show" role="alert" style="background: #d1ecf1; border: 1px solid #b8daff; border-radius: 8px; padding: 1rem;">
            <i class="fas fa-info-circle me-2" style="color: #0c5460;"></i>
            <strong style="color: #0c5460;">Report Period:</strong>
            <span style="color: #0c5460;">
                @if($startDate && $endDate)
                    {{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} to {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}
                @elseif($startDate)
                    {{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} onwards
                @elseif($endDate)
                    Up to {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}
                @else
                    Overall / All Data
                @endif
            </span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
</div>

@push('style')
<style>
    .transition-all {
        transition: all 0.3s ease;
    }

    .table tbody tr {
        transition: all 0.3s ease;
    }

    .table tbody tr:hover {
        background-color: #f8f9fa !important;
    }
</style>
@endpush
