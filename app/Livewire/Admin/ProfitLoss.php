<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Payment;
use App\Models\PurchasePayment;
use App\Models\Salary;
use App\Models\ProductPrice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Livewire\Concerns\WithDynamicLayout;
use Illuminate\Support\Facades\Log;

#[Title("Profit & Loss Statement")]
class ProfitLoss extends Component
{
    use WithDynamicLayout;

    // Date filters
    public $startDate;
    public $endDate;

    // Summary data
    public $totalRevenue = 0;
    public $totalCOGS = 0;
    public $grossProfit = 0;
    public $grossProfitPercentage = 0;

    public $totalExpenses = 0;
    public $totalSalaries = 0;
    public $totalOutgoing = 0;

    public $totalReturns = 0; // Total return amount (selling price)
    public $totalReturnsCOGS = 0; // COGS for returned products
    public $returnImpact = 0; // Net loss from returns (Selling Price - COGS)

    public $netProfit = 0;
    public $netProfitPercentage = 0;

    // Detailed data
    public $revenueBreakdown = [];
    public $expenseBreakdown = [];
    public $paymentBreakdown = [];
    public $salaryBreakdown = [];
    public $allOutgoingBreakdown = [];

    // Monthly data for trends
    public $monthlyTrends = [];
    public $categoryWiseExpenses = [];
    public $paymentMethodWiseRevenue = [];

    // Summary totals by category
    public $expenseByCategoryTotals = [];
    public $incomeTotals = [];
    public $outgoingTotals = [];

    public function mount()
    {
        // Initialize with empty dates - shows overall data
        $this->startDate = null;
        $this->endDate = null;

        $this->loadData();
    }

    public function updatedStartDate()
    {
        $this->loadData();
    }

    public function updatedEndDate()
    {
        $this->loadData();
    }

    public function resetFilters()
    {
        $this->startDate = null;
        $this->endDate = null;
        $this->loadData();
    }

    public function loadData()
    {
        try {
            $this->calculateRevenue();
            $this->calculateCOGS();
            $this->calculateExpenses();
            $this->calculateSalaries();
            $this->calculatePaymentsByCategory();
            $this->calculateAllOutgoing();
            $this->calculateMonthlyTrends();
            $this->calculateNetProfit();
        } catch (\Exception $e) {
            Log::error('P&L Calculation Error: ' . $e->getMessage());
        }
    }

    /**
     * Calculate Total Revenue from Sales (Sale Amount - COGS - Returns)
     */
    private function calculateRevenue()
    {
        // If no dates set, get all data; otherwise use specified dates
        $query = Sale::where('status', 'confirm');

        if ($this->startDate && $this->endDate) {
            $start = Carbon::parse($this->startDate)->startOfDay();
            $end = Carbon::parse($this->endDate)->endOfDay();
            $query->whereBetween('created_at', [$start, $end]);
        }

        // Get total sales amount (Gross Revenue)
        $totalSalesAmount = $query->sum('total_amount');

        $sales = $query->get();

        $this->revenueBreakdown = [];
        $this->paymentMethodWiseRevenue = [];

        foreach ($sales as $sale) {
            // Breakdown by sale type
            $saleType = $sale->sale_type ?? 'Regular';
            if (!isset($this->revenueBreakdown[$saleType])) {
                $this->revenueBreakdown[$saleType] = 0;
            }
            $this->revenueBreakdown[$saleType] += $sale->total_amount;
        }

        // Calculate COGS from items in these sales (only confirmed sales)
        // Using left join to ensure we get all sale items even if price is missing
        $cogsQuery = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->leftJoin('product_prices', function ($join) {
                $join->on('sale_items.product_id', '=', 'product_prices.product_id')
                     ->where(function ($query) {
                         $query->where(function ($q) {
                             $q->whereNotNull('sale_items.variant_id')
                               ->whereRaw('sale_items.variant_id = product_prices.variant_id')
                               ->whereRaw('sale_items.variant_value = product_prices.variant_value');
                         })->orWhere(function ($q) {
                             $q->whereNull('sale_items.variant_id')
                               ->where('product_prices.pricing_mode', 'single');
                         });
                     });
            })
            ->where('sales.status', 'confirm')
            ->select(DB::raw('SUM(COALESCE(product_prices.supplier_price, 0) * sale_items.quantity) as total_cost'));

        if ($this->startDate && $this->endDate) {
            $start = Carbon::parse($this->startDate)->startOfDay();
            $end = Carbon::parse($this->endDate)->endOfDay();
            $cogsQuery->whereBetween('sales.created_at', [$start, $end]);
        }

        $cogsResult = $cogsQuery->first();
        $this->totalCOGS = $cogsResult ? ($cogsResult->total_cost ?? 0) : 0;

        // Calculate Returns Amount and Returns COGS
        $returnsQuery = DB::table('returns_products')
            ->join('sales', 'returns_products.sale_id', '=', 'sales.id')
            ->where('sales.status', 'confirm')
            ->select(DB::raw('SUM(returns_products.total_amount) as total_returns'));

        if ($this->startDate && $this->endDate) {
            $start = Carbon::parse($this->startDate)->startOfDay();
            $end = Carbon::parse($this->endDate)->endOfDay();
            $returnsQuery->whereBetween('sales.created_at', [$start, $end]);
        }

        $returnsResult = $returnsQuery->first();
        $this->totalReturns = $returnsResult ? ($returnsResult->total_returns ?? 0) : 0;

        // Calculate COGS for returned products
        $returnsCOGSQuery = DB::table('returns_products')
            ->join('sales', 'returns_products.sale_id', '=', 'sales.id')
            ->leftJoin('product_prices', function ($join) {
                $join->on('returns_products.product_id', '=', 'product_prices.product_id')
                     ->where(function ($query) {
                         $query->where(function ($q) {
                             $q->whereNotNull('returns_products.variant_id')
                               ->whereRaw('returns_products.variant_id = product_prices.variant_id')
                               ->whereRaw('returns_products.variant_value = product_prices.variant_value');
                         })->orWhere(function ($q) {
                             $q->whereNull('returns_products.variant_id')
                               ->where('product_prices.pricing_mode', 'single');
                         });
                     });
            })
            ->where('sales.status', 'confirm')
            ->select(DB::raw('SUM(COALESCE(product_prices.supplier_price, 0) * returns_products.return_quantity) as total_cost'));

        if ($this->startDate && $this->endDate) {
            $start = Carbon::parse($this->startDate)->startOfDay();
            $end = Carbon::parse($this->endDate)->endOfDay();
            $returnsCOGSQuery->whereBetween('sales.created_at', [$start, $end]);
        }

        $returnsCOGSResult = $returnsCOGSQuery->first();
        $this->totalReturnsCOGS = $returnsCOGSResult ? ($returnsCOGSResult->total_cost ?? 0) : 0;

        // Calculate Return Impact (Net loss from returns)
        // Return Impact = Return Selling Price - Return COGS
        $this->returnImpact = $this->totalReturns - $this->totalReturnsCOGS;

        // Log for debugging
        Log::info('Revenue Calculation', [
            'total_sales' => $totalSalesAmount,
            'total_cogs' => $this->totalCOGS,
            'total_returns' => $this->totalReturns,
            'returns_cogs' => $this->totalReturnsCOGS
        ]);

        // Calculate Net Revenue: (Sales Amount - Returns) - (COGS - Returns COGS)
        $netSalesAmount = $totalSalesAmount - $this->totalReturns;
        $netCOGS = $this->totalCOGS - $this->totalReturnsCOGS;
        $this->totalRevenue = $netSalesAmount - $netCOGS;
        $this->totalCOGS = $netCOGS; // Update COGS to reflect returns

        // Get payment method wise revenue
        $paymentQuery = Payment::query();
        if ($this->startDate && $this->endDate) {
            $start = Carbon::parse($this->startDate)->startOfDay();
            $end = Carbon::parse($this->endDate)->endOfDay();
            $paymentQuery->whereBetween('payment_date', [$start, $end]);
        }

        $paymentMethods = $paymentQuery
            ->groupBy('payment_method')
            ->selectRaw('payment_method, SUM(amount) as total')
            ->get();

        foreach ($paymentMethods as $method) {
            $this->paymentMethodWiseRevenue[$method->payment_method ?? 'Unknown'] = $method->total ?? 0;
        }

        $this->incomeTotals['Total Sales Revenue'] = $totalSalesAmount;
        $this->incomeTotals['Total Returns'] = $this->totalReturns;
        $this->incomeTotals['Net Sales Revenue'] = $netSalesAmount;
        $this->incomeTotals['Total COGS'] = $netCOGS;
        $this->incomeTotals['Net Revenue'] = $this->totalRevenue;
    }

    /**
     * Calculate Cost of Goods Sold and Gross Profit
     */
    private function calculateCOGS()
    {
        // COGS already calculated in calculateRevenue()
        // Gross Profit = Net Revenue (already has COGS deducted)
        $this->grossProfit = $this->totalRevenue;

        $totalSalesAmount = $this->incomeTotals['Total Sales Revenue'] ?? 0;
        $this->grossProfitPercentage = $totalSalesAmount > 0
            ? round(($this->grossProfit / $totalSalesAmount) * 100, 2)
            : 0;
    }

    /**
     * Calculate Operating Expenses
     */
    private function calculateExpenses()
    {
        // Get all expenses grouped by category
        $expenseQuery = Expense::query();
        if ($this->startDate && $this->endDate) {
            $start = Carbon::parse($this->startDate)->startOfDay();
            $end = Carbon::parse($this->endDate)->endOfDay();
            $expenseQuery->whereBetween('created_at', [$start, $end]);
        }

        $expenses = $expenseQuery
            ->groupBy('category')
            ->selectRaw('category, SUM(amount) as total_amount, COUNT(*) as count')
            ->get();

        $this->expenseBreakdown = [];
        $this->totalExpenses = 0;
        $this->expenseByCategoryTotals = [];

        foreach ($expenses as $expense) {
            $category = $expense->category ?? 'Uncategorized';
            $this->expenseBreakdown[$category] = [
                'amount' => $expense->total_amount,
                'count' => $expense->count,
            ];
            $this->expenseByCategoryTotals[$category] = $expense->total_amount;
            $this->totalExpenses += $expense->total_amount;
        }

        $this->outgoingTotals['Operating Expenses'] = $this->totalExpenses;
    }

    /**
     * Calculate Salaries (removed - not included in P&L)
     */
    private function calculateSalaries()
    {
        // Salaries removed from P&L calculation
        $this->totalSalaries = 0;
        $this->salaryBreakdown = [];
        $this->outgoingTotals['Salaries & Staff'] = 0;
    }

    /**
     * Calculate all outgoing payments
     */
    private function calculateAllOutgoing()
    {
        $this->allOutgoingBreakdown = [];
        $this->totalOutgoing = 0;

        // Operating Expenses only
        if ($this->totalExpenses > 0) {
            $this->allOutgoingBreakdown['Operating Expenses'] = $this->totalExpenses;
            $this->totalOutgoing += $this->totalExpenses;
        }

        // COGS
        if ($this->totalCOGS > 0) {
            $this->allOutgoingBreakdown['Cost of Goods Sold'] = $this->totalCOGS;
            $this->totalOutgoing += $this->totalCOGS;
        }
    }

    /**
     * Calculate payment methods breakdown
     */
    private function calculatePaymentsByCategory()
    {
        // Payment breakdown is already calculated in calculateRevenue()
        // This method is kept for consistency
    }

    /**
     * Calculate monthly trends
     */
    private function calculateMonthlyTrends()
    {
        // If dates are set, use them; otherwise use all data
        if ($this->startDate && $this->endDate) {
            $start = Carbon::parse($this->startDate)->startOfMonth();
            $end = Carbon::parse($this->endDate)->endOfMonth();
        } else {
            // Get the earliest date from database
            $earliestSale = Sale::orderBy('created_at')->first();
            $start = $earliestSale ? $earliestSale->created_at->copy()->startOfMonth() : now()->startOfMonth();
            $end = now();
        }

        $this->monthlyTrends = [];

        $current = $start->copy();
        while ($current <= $end) {
            $monthStart = $current->copy()->startOfMonth();
            $monthEnd = $current->copy()->endOfMonth();

            $monthSalesAmount = Sale::where('status', 'confirm')
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('total_amount');

            // Calculate COGS for this month (only confirmed sales)
            $monthCOGSValue = SaleItem::join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->leftJoin('product_prices', function ($join) {
                    $join->on('sale_items.product_id', '=', 'product_prices.product_id')
                         ->where(function ($query) {
                             $query->where(function ($q) {
                                 $q->whereNotNull('sale_items.variant_id')
                                   ->whereRaw('sale_items.variant_id = product_prices.variant_id')
                                   ->whereRaw('sale_items.variant_value = product_prices.variant_value');
                             })->orWhere(function ($q) {
                                 $q->whereNull('sale_items.variant_id')
                                   ->where('product_prices.pricing_mode', 'single');
                             });
                         });
                })
                ->where('sales.status', 'confirm')
                ->whereBetween('sales.created_at', [$monthStart, $monthEnd])
                ->selectRaw('SUM(product_prices.supplier_price * sale_items.quantity) as total')
                ->first()
                ->total ?? 0;

            // Monthly Revenue (Gross Profit) = Sale Amount - COGS
            $monthRevenue = $monthSalesAmount - $monthCOGSValue;

            $monthExpenses = Expense::whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('amount');

            $monthProfit = $monthRevenue - $monthExpenses;

            $this->monthlyTrends[] = [
                'month' => $current->format('M Y'),
                'revenue' => $monthRevenue,
                'expenses' => $monthExpenses,
                'cogs' => $monthCOGSValue,
                'profit' => $monthProfit,
            ];

            $current->addMonth();
        }
    }

    /**
     * Calculate Net Profit (Revenue - Expenses only, no salaries)
     */
    private function calculateNetProfit()
    {
        $this->netProfit = $this->totalRevenue - $this->totalExpenses;

        $totalSalesAmount = $this->incomeTotals['Total Sales Revenue'] ?? 0;
        $this->netProfitPercentage = $totalSalesAmount > 0
            ? round(($this->netProfit / $totalSalesAmount) * 100, 2)
            : 0;
    }

    /**
     * Format currency values
     */
    public function formatCurrency($value)
    {
        return number_format($value, 2);
    }

    /**
     * Export to PDF (optional - requires barryvdh/laravel-dompdf)
     */
    public function exportPDF()
    {
        $data = [
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'incomeTotals' => $this->incomeTotals,
            'totalReturns' => $this->totalReturns,
            'totalReturnsCOGS' => $this->totalReturnsCOGS,
            'totalCOGS' => $this->totalCOGS,
            'totalRevenue' => $this->totalRevenue, // This acts as Gross Profit
            'grossProfitPercentage' => $this->grossProfitPercentage,
            'totalExpenses' => $this->totalExpenses,
            'expenseBreakdown' => $this->expenseBreakdown,
            'netProfit' => $this->netProfit,
            'netProfitPercentage' => $this->netProfitPercentage,
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.profit-loss-pdf', $data);

        $filename = 'Profit_Loss_Statement';
        if ($this->startDate && $this->endDate) {
            $filename .= '_' . $this->startDate . '_to_' . $this->endDate;
        }
        $filename .= '.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename);
    }

    public function render()
    {
        return view('livewire.admin.profit-loss')->layout($this->layout);
    }
}
