<?php

namespace App\Livewire\Admin;

use App\Models\Expense;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Livewire\Concerns\WithDynamicLayout;

#[Title('Dashboard')]
class AdminDashboard extends Component
{
    use WithDynamicLayout;

    public $totalRevenue = 0;
    public $totalDueAmount = 0;
    public $totalSales = 0;
    public $revenuePercentage = 0;
    public $duePercentage = 0;
    public $previousMonthRevenue = 0;
    public $revenueChangePercentage = 0;
    public $fullPaidCount = 0;
    public $fullPaidAmount = 0;
    public $partialPaidCount = 0;
    public $partialPaidAmount = 0;

    public $totalStock = 0;
    public $soldStock = 0;
    public $availableStock = 0;
    public $soldPercentage = 0;
    public $availablePercentage = 0;
    public $damagedStock = 0;
    public $damagedPercentage = 0;
    public $damagedValue = 0;
    public $totalInventoryValue = 0;
    public $totalAvailableInventory = 0;
    public $totalStaffCount = 0;
    public $staffWithAssignmentsCount = 0;
    public $staffAssignmentPercentage = 0;
    public $totalStaffSalesValue = 0;
    public $totalStaffDueAmount = 0;
    public $totalPaidAmount = 0;

    public $recentSales = [];
    public $ProductInventory = [];
    public $dailySales = [];
    public $staffSales = [];

    // Today's Summary
    public $showTodaySummaryModal = false;
    public $todaySaleSummary = [];
    public $summaryPeriod = 'today';          // today | current_month | last_month | custom
    public $summaryCustomMonth = '';           // format: YYYY-MM
    public $summaryPeriodLabel = 'Today';
    public $summaryMonthOptions = [];          // array of {value, label} for the dropdown

    // Expenses
    public $todayTotal = 0;
    public $monthTotal = 0;
    public $totalExpenses = 0;
    public $monthlyBudget = 10000;
    public $monthlyProgressPercentage = 0;
    public $dailyAverage = 0;
    public $todayVsAverage = 0;

    public function mount()
    {
        // Get sales statistics
        $salesStats = Sale::select(
            DB::raw('SUM(total_amount) as total_sales'),
            DB::raw('SUM(due_amount) as total_due'),
            DB::raw('COUNT(*) as sales_count')
        )->first();

        // Add total expenses
        $this->totalExpenses = DB::table('expenses')->sum('amount');
        // Totals
        $this->todayTotal = Expense::whereDate('date', Carbon::today())->sum('amount');
        $this->monthTotal = Expense::whereMonth('date', Carbon::now()->month)
            ->whereYear('date', Carbon::now()->year)
            ->sum('amount');

        // Calculate monthly progress percentage
        $this->monthlyProgressPercentage = $this->monthlyBudget > 0
            ? min(round(($this->monthTotal / $this->monthlyBudget) * 100, 2), 100)
            : 0;

        // Calculate today vs average
        $daysInMonth = now()->daysInMonth;
        $this->dailyAverage = $daysInMonth > 0 ? $this->monthTotal / now()->day : 0;
        $this->todayVsAverage = $this->dailyAverage > 0
            ? round((($this->todayTotal - $this->dailyAverage) / $this->dailyAverage) * 100, 2)
            : 0;

        // Calculate total revenue (total_amount - due_amount)
        $this->totalSales = $salesStats->total_sales ?? 0;
        $this->totalDueAmount = $salesStats->total_due ?? 0;
        $this->totalRevenue = $this->totalSales - $this->totalDueAmount;
        $this->totalPaidAmount = $this->totalRevenue; // Amount already collected

        // Calculate percentages
        if ($this->totalSales > 0) {
            $this->revenuePercentage = round(($this->totalRevenue / $this->totalSales) * 100, 1);
            $this->duePercentage = round(($this->totalDueAmount / $this->totalSales) * 100, 1);
        }

        // Get previous month's revenue for comparison
        $previousMonthSales = Sale::whereMonth(
            'created_at',
            '=',
            now()->subMonth()->month
        )->select(
            DB::raw('SUM(total_amount - due_amount) as revenue')
        )->first();

        $this->previousMonthRevenue = $previousMonthSales->revenue ?? 0;

        // Calculate month-over-month change percentage
        if ($this->previousMonthRevenue > 0) {
            $this->revenueChangePercentage = round((($this->totalRevenue - $this->previousMonthRevenue) / $this->previousMonthRevenue) * 100, 1);
        }

        // Get fully paid invoices data
        $fullPaidData = Sale::where('payment_status', 'paid')
            ->select(
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total_amount) as amount')
            )->first();

        $this->fullPaidCount = $fullPaidData->count ?? 0;
        $this->fullPaidAmount = $fullPaidData->amount ?? 0;

        // Get partially paid invoices data
        $partialPaidData = Sale::where('payment_status', 'partial')
            ->select(
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(due_amount) as amount')
            )->first();

        $this->partialPaidCount = $partialPaidData->count ?? 0;
        $this->partialPaidAmount = $partialPaidData->amount ?? 0;

        // Get inventory statistics
        $stockStats = DB::table('product_stocks')
            ->select(
                DB::raw('SUM(total_stock) as total_stock'),
                DB::raw('SUM(sold_count) as sold_stock'),
                DB::raw('SUM(damage_stock) as damaged_stock'),
                DB::raw('SUM(available_stock) as available_stock')
            )->first();

        $this->totalStock = $stockStats->total_stock ?? 0;
        $this->soldStock = $stockStats->sold_stock ?? 0;
        $this->damagedStock = $stockStats->damaged_stock ?? 0;
        $this->availableStock = $stockStats->available_stock ?? 0;

        // Calculate percentages for the 3 new cards
        if ($this->totalStock > 0) {
            $this->soldPercentage = round(($this->soldStock / $this->totalStock) * 100, 1);
            $this->availablePercentage = round(($this->availableStock / $this->totalStock) * 100, 1);
            $this->damagedPercentage = round(($this->damagedStock / $this->totalStock) * 100, 1);
        }

        // Calculate damaged inventory value
        $damagedValue = DB::table('product_details')
            ->join('product_stocks', 'product_details.id', '=', 'product_stocks.product_id')
            ->join('product_prices', 'product_details.id', '=', 'product_prices.product_id')
            ->select(DB::raw('SUM(product_stocks.damage_stock * product_prices.supplier_price) as damaged_value'))
            ->first();

        $this->damagedValue = $damagedValue->damaged_value ?? 0;

        // Calculate total inventory value
        $totalInventoryValue = DB::table('product_details')
            ->join('product_stocks', 'product_details.id', '=', 'product_stocks.product_id')
            ->join('product_prices', 'product_details.id', '=', 'product_prices.product_id')
            ->select(DB::raw('SUM(product_stocks.available_stock * product_prices.supplier_price) as total_value'))
            ->first();

        $totalAvailableInventory = DB::table('product_details')
            ->join('product_stocks', 'product_details.id', '=', 'product_stocks.product_id')
            ->join('product_prices', 'product_details.id', '=', 'product_prices.product_id')
            ->select(DB::raw('SUM(product_stocks.available_stock * product_prices.supplier_price) as total_value'))
            ->first();

        $this->totalInventoryValue = $totalInventoryValue->total_value ?? 0;
        $this->totalAvailableInventory = $totalAvailableInventory->total_value ?? 0;

        $this->totalStaffCount = DB::table('users')->where('role', 'staff')->count();
        $this->staffWithAssignmentsCount = DB::table('staff_sales')
            ->select('staff_id')
            ->distinct()
            ->count('staff_id');

        // Calculate assignment percentage
        if ($this->totalStaffCount > 0) {
            $this->staffAssignmentPercentage = round(($this->staffWithAssignmentsCount / $this->totalStaffCount) * 100, 1);
        }

        $staffSalesTotal = DB::table('staff_sales')
            ->select(DB::raw('SUM(total_value) as total_value'))
            ->first();

        $this->totalStaffSalesValue = $staffSalesTotal->total_value ?? 0;

        // Calculate total staff due amount from all staff sales
        $staffDueTotal = DB::table('sales')
            ->join('users', 'sales.user_id', '=', 'users.id')
            ->where('users.role', 'staff')
            ->select(DB::raw('SUM(sales.due_amount) as total_due'))
            ->first();

        $this->totalStaffDueAmount = $staffDueTotal->total_due ?? 0;

        // Fetch recent sales
        $this->loadRecentSales();

        // Fetch Product inventory data
        $this->loadProductInventory();

        // Fetch daily sales data for last 7 days
        $this->loadDailySales();

        // Fetch staff sales data
        $this->loadStaffSales();

        // Fetch today's summary
        $this->loadTodaySummary();
    }

    public function loadRecentSales()
    {
        // Join customers table to get customer details
        $this->recentSales = DB::table('sales')
            ->join('customers', 'sales.customer_id', '=', 'customers.id')
            ->select(
                'sales.id',
                'sales.invoice_number',
                'sales.total_amount',
                'sales.payment_status',
                'sales.created_at',
                'customers.name',
                'customers.email',
                'sales.due_amount',
            )
            ->orderBy('sales.created_at', 'desc')
            ->limit(5)
            ->get();
    }

    public function loadProductInventory()
    {
        // Join Productes and stock tables to get full inventory data
        $this->ProductInventory = DB::table('product_details')
            ->join('product_stocks', 'product_details.id', '=', 'product_stocks.product_id')
            ->join('brand_lists', 'product_details.brand_id', '=', 'brand_lists.id')
            ->select(
                'product_details.id',
                'product_details.code',
                'product_details.name',
                'product_details.model',
                'brand_lists.brand_name as brand',
                'product_stocks.available_stock',
                'product_stocks.total_stock',
                'product_stocks.damage_stock'
            )
            ->orderBy('product_stocks.available_stock', 'asc')
            ->limit(5)
            ->get();
    }

    public function loadDailySales()
    {
        // Get daily sales for the last 7 days
        $dailySales = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);

            $sales = Sale::whereDate('created_at', $date->toDateString())
                ->sum('total_amount');

            $dailySales[] = [
                'date' => $date->format('M d'),
                'total_sales' => (float) $sales
            ];
        }

        $this->dailySales = $dailySales;
    }

    public function loadTodaySummary()
    {
        // Determine date range based on selected period
        switch ($this->summaryPeriod) {
            case 'current_month':
                $from = Carbon::now()->startOfMonth();
                $to   = Carbon::now()->endOfMonth();
                $this->summaryPeriodLabel = 'This Month (' . Carbon::now()->format('F Y') . ')';
                $isRange = true;
                break;
            case 'last_month':
                $from = Carbon::now()->subMonth()->startOfMonth();
                $to   = Carbon::now()->subMonth()->endOfMonth();
                $this->summaryPeriodLabel = 'Last Month (' . Carbon::now()->subMonth()->format('F Y') . ')';
                $isRange = true;
                break;
            case 'custom':
                if ($this->summaryCustomMonth) {
                    $dt   = Carbon::createFromFormat('Y-m', $this->summaryCustomMonth);
                    $from = $dt->copy()->startOfMonth();
                    $to   = $dt->copy()->endOfMonth();
                    $this->summaryPeriodLabel = $dt->format('F Y');
                } else {
                    $from = Carbon::today();
                    $to   = Carbon::today();
                    $this->summaryPeriodLabel = 'Today';
                }
                $isRange = true;
                break;
            case 'all':
                $from = Carbon::create(2000, 1, 1)->startOfDay();
                $to   = Carbon::now()->endOfDay();
                $this->summaryPeriodLabel = 'All Time';
                $isRange = true;
                break;
            default: // today
                $from = Carbon::today();
                $to   = Carbon::today();
                $this->summaryPeriodLabel = 'Today (' . Carbon::today()->format('d F Y') . ')';
                $isRange = false;
                break;
        }

        $salesQuery = function(string $field) use ($from, $to, $isRange) {
            $q = DB::table('sales');
            if ($isRange) {
                return $q->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()]);
            }
            return $q->whereDate('created_at', $from);
        };

        $paymentQuery = function() use ($from, $to, $isRange) {
            $q = DB::table('payments');
            if ($isRange) {
                return $q->whereBetween('payment_date', [$from->toDateString(), $to->toDateString()]);
            }
            return $q->whereDate('payment_date', $from);
        };

        // POS Sales
        $pos = $salesQuery('created_at')
            ->where('sale_type', 'pos')
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total_amount),0) as total, COALESCE(SUM(due_amount),0) as due')
            ->first();

        // Staff Sales
        $staff = $salesQuery('created_at')
            ->whereIn('sale_type', ['staff', 'admin'])
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total_amount),0) as total, COALESCE(SUM(due_amount),0) as due')
            ->first();

        // Grand totals
        $grandTotal = $salesQuery('created_at')
            ->selectRaw('COALESCE(SUM(total_amount),0) as total, COALESCE(SUM(due_amount),0) as due')
            ->first();

        // Cash payments
        $cashPayment = $paymentQuery()
            ->where('is_completed', true)
            ->where(function ($q) {
                $q->where('payment_method', 'cash')
                  ->orWhere('due_payment_method', 'cash');
            })
            ->sum('amount');

        // Cheque payments
        $chequePayment = $paymentQuery()
            ->where('is_completed', true)
            ->where(function ($q) {
                $q->where('payment_method', 'cheque')
                  ->orWhere('due_payment_method', 'cheque');
            })
            ->sum('amount');

        // Total collected
        $totalCollected = $paymentQuery()
            ->where('is_completed', true)
            ->sum('amount');

        $this->todaySaleSummary = [
            'pos_count'       => $pos->count ?? 0,
            'pos_total'       => $pos->total ?? 0,
            'pos_due'         => $pos->due ?? 0,
            'staff_count'     => $staff->count ?? 0,
            'staff_total'     => $staff->total ?? 0,
            'staff_due'       => $staff->due ?? 0,
            'grand_total'     => $grandTotal->total ?? 0,
            'grand_due'       => $grandTotal->due ?? 0,
            'total_collected' => $totalCollected,
            'cash_payment'    => $cashPayment,
            'cheque_payment'  => $chequePayment,
        ];
    }

    public function openTodaySummaryModal()
    {
        // Build the last-12-months list for the dropdown (excluding current month)
        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $date = Carbon::now()->subMonths($i)->startOfMonth();
            $months[] = [
                'value' => $date->format('Y-m'),
                'label' => $date->format('F Y'),
            ];
        }
        $this->summaryMonthOptions = $months;
        $this->summaryPeriod = 'today';
        $this->loadTodaySummary();
        $this->showTodaySummaryModal = true;
    }

    public function switchSummaryPeriod(string $period)
    {
        $this->summaryPeriod = $period;
        if ($period !== 'custom') {
            $this->summaryCustomMonth = '';
        }
        $this->loadTodaySummary();
    }

    public function switchSummaryMonth(string $ym)
    {
        $this->summaryPeriod    = 'custom';
        $this->summaryCustomMonth = $ym;
        $this->loadTodaySummary();
    }

    public function closeTodaySummaryModal()
    {
        $this->showTodaySummaryModal = false;
    }

    public function loadStaffSales()
    {
        $this->staffSales = DB::table('users')
            ->where('role', 'staff')
            ->leftJoin('staff_sales', 'users.id', '=', 'staff_sales.staff_id')
            ->select(
                'users.id',
                'users.name',
                'users.email',
                DB::raw('COALESCE(SUM(staff_sales.total_value), 0) as assigned_value'),
                DB::raw('COALESCE(SUM(staff_sales.sold_value), 0) as sold_value'),
                DB::raw('COALESCE(SUM(staff_sales.total_quantity), 0) as assigned_quantity'),
                DB::raw('COALESCE(SUM(staff_sales.sold_quantity), 0) as sold_quantity')
            )
            ->groupBy('users.id', 'users.name', 'users.email')
            ->get()
            ->map(function ($staff) {
                // Calculate due amount from sales table
                $salesInfo = DB::table('sales')
                    ->where('user_id', $staff->id)
                    ->select(
                        DB::raw('COALESCE(SUM(total_amount), 0) as total_sales'),
                        DB::raw('COALESCE(SUM(due_amount), 0) as total_due')
                    )
                    ->first();

                $staff->total_sales = $salesInfo->total_sales ?? 0;
                $staff->total_due = $salesInfo->total_due ?? 0;
                $staff->collected_amount = $staff->total_sales - $staff->total_due;

                // Calculate percentages for progress bars
                $staff->sales_percentage = $staff->assigned_value > 0 ?
                    round(($staff->sold_value / $staff->assigned_value) * 100, 1) : 0;

                $staff->payment_percentage = $staff->total_sales > 0 ?
                    round(($staff->collected_amount / $staff->total_sales) * 100, 1) : 0;

                return $staff;
            });
    }

    public function render()
    {
        return view('livewire.admin.admin-dashboard')->layout($this->layout);
    }
}
