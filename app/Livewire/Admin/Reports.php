<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Sale;
use App\Models\Payment;
use App\Models\PurchaseOrder;
use App\Models\ProductStock;
use App\Models\ProductSupplier;
use App\Models\Customer;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SalesReportExport;
use App\Exports\SalaryReportExport;
use App\Exports\InventoryReportExport;
use App\Exports\StaffReportExport;
use App\Exports\PaymentsReportExport;
use App\Exports\AttendanceReportExport;
use App\Exports\ProductValueReportExport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Livewire\Concerns\WithDynamicLayout;
use Livewire\WithPagination;

#[Title('Reports')]
class Reports extends Component
{
    use WithDynamicLayout, WithPagination;

    public $selectedReport = '';
    public $reportStartDate;
    public $reportEndDate;
    public $selectedMonth;
    public $selectedYear;

    // Separate date pickers for daily and monthly reports
    public $dailyMonth;
    public $dailyYear;
    public $monthlyYear;

    // Report data arrays
    public $salesReport = [];
    public $salaryReport = [];
    public $inventoryReport = [];
    public $staffReport = [];
    public $paymentsReport = [];
    public $attendanceReport = [];
    public $dailySalesReport = [];
    public $monthlySalesReport = [];
    public $dailyPurchasesReport = [];
    public $outstandingAccountsReport = [];
    public $productValueReport = [];
    public $reportStats = [];

    // Modal properties
    public $showDetailModal = false;
    public $selectedDetailType = null;
    public $selectedDetailData = null;

    // Report totals
    public $salesReportTotal = 0;
    public $salaryReportTotal = 0;
    public $inventoryReportTotal = 0;
    public $staffReportTotal = 0;
    public $paymentsReportTotal = 0;
    public $attendanceReportTotal = 0;
    public $dailySalesReportTotal = 0;
    public $monthlySalesReportTotal = 0;
    public $dailyPurchasesReportTotal = 0;
    public $productValueReportTotal = 0;

    public $perPage = 10;

    public function mount()
    {
        // Set default to current month and year if not set
        if (!$this->selectedMonth) {
            $this->selectedMonth = now()->month;
        }
        if (!$this->selectedYear) {
            $this->selectedYear = now()->year;
        }

        // Initialize separate date pickers
        $this->dailyMonth = now()->month;
        $this->dailyYear = now()->year;
        $this->monthlyYear = now()->year;

        // Generate initial report only if a report type is selected
        if ($this->selectedReport) {
            $this->generateReport();
        }
    }

    public function updatedSelectedReport()
    {
        $this->generateReport();
    }

    public function updatedReportStartDate()
    {
        $this->generateReport();
    }

    public function updatedReportEndDate()
    {
        $this->generateReport();
    }

    public function updatedSelectedMonth()
    {
        $this->generateReport();
    }

    public function updatedSelectedYear()
    {
        $this->generateReport();
    }

    public function updatedDailyMonth()
    {
        $this->generateReport();
    }

    public function updatedDailyYear()
    {
        $this->generateReport();
    }

    public function updatedMonthlyYear()
    {
        $this->generateReport();
    }

    public function generateReport()
    {
        // Validate date range
        if ($this->reportStartDate && $this->reportEndDate && $this->reportStartDate > $this->reportEndDate) {
            $this->addError('reportEndDate', 'End date must be after start date.');
            return;
        }

        // Clear previous errors
        $this->resetErrorBag();

        // Handle daily sales report with separate date picker
        if ($this->selectedReport === 'daily-sales' && $this->dailyMonth && $this->dailyYear) {
            $monthStart = \Carbon\Carbon::createFromDate($this->dailyYear, $this->dailyMonth, 1)->format('Y-m-d');
            $monthEnd = \Carbon\Carbon::createFromDate($this->dailyYear, $this->dailyMonth, 1)->endOfMonth()->format('Y-m-d');
            $this->reportStartDate = $monthStart;
            $this->reportEndDate = $monthEnd;
        }

        // Handle monthly sales report with separate date picker (full year)
        if ($this->selectedReport === 'monthly-sales' && $this->monthlyYear) {
            $yearStart = \Carbon\Carbon::createFromDate($this->monthlyYear, 1, 1)->format('Y-m-d');
            $yearEnd = \Carbon\Carbon::createFromDate($this->monthlyYear, 12, 31)->format('Y-m-d');
            $this->reportStartDate = $yearStart;
            $this->reportEndDate = $yearEnd;
        }

        // Handle daily-purchases report - set default to current month if not set
        if ($this->selectedReport === 'daily-purchases') {
            if (!$this->reportStartDate || !$this->reportEndDate) {
                $this->reportStartDate = now()->startOfMonth()->format('Y-m-d');
                $this->reportEndDate = now()->endOfMonth()->format('Y-m-d');
            }
        }

        // Handle other reports with month selection
        if (($this->selectedReport !== 'daily-sales' && $this->selectedReport !== 'monthly-sales' && $this->selectedReport !== 'daily-purchases' && $this->selectedReport !== 'inventory-stock' && $this->selectedReport !== 'outstanding-accounts') && $this->selectedMonth && $this->selectedYear) {
            $monthStart = \Carbon\Carbon::createFromDate($this->selectedYear, $this->selectedMonth, 1)->format('Y-m-d');
            $monthEnd = \Carbon\Carbon::createFromDate($this->selectedYear, $this->selectedMonth, 1)->endOfMonth()->format('Y-m-d');
            $this->reportStartDate = $monthStart;
            $this->reportEndDate = $monthEnd;
        }

        if ($this->selectedReport === 'sales') {
            $this->salesReport = $this->getSalesReport($this->reportStartDate, $this->reportEndDate);
            $this->salesReportTotal = collect($this->salesReport)->sum('total_amount');
        } elseif ($this->selectedReport === 'salary') {
            $this->salaryReport = $this->getSalaryReport($this->reportStartDate, $this->reportEndDate);
            $this->salaryReportTotal = collect($this->salaryReport)->sum('net_salary');
        } elseif ($this->selectedReport === 'inventory') {
            $this->inventoryReport = $this->getInventoryReport($this->reportStartDate, $this->reportEndDate);
            $this->inventoryReportTotal = collect($this->inventoryReport)->sum('available_stock');
        } elseif ($this->selectedReport === 'staff') {
            $this->staffReport = $this->getStaffReport($this->reportStartDate, $this->reportEndDate);
            $this->staffReportTotal = collect($this->staffReport)->sum('total_sales');
        } elseif ($this->selectedReport === 'payments') {
            $this->paymentsReport = $this->getPaymentsReport($this->reportStartDate, $this->reportEndDate);
            $this->paymentsReportTotal = collect($this->paymentsReport)->sum('amount');
        } elseif ($this->selectedReport === 'attendance') {
            $this->attendanceReport = $this->getAttendanceReport($this->reportStartDate, $this->reportEndDate);
            $this->attendanceReportTotal = collect($this->attendanceReport)->count();
        } elseif ($this->selectedReport === 'daily-sales') {
            $this->dailySalesReport = $this->getDailySalesReport($this->reportStartDate, $this->reportEndDate);
            $this->dailySalesReportTotal = collect($this->dailySalesReport)->sum('grand_total');
        } elseif ($this->selectedReport === 'monthly-sales') {
            $this->monthlySalesReport = $this->getMonthlySalesReport($this->reportStartDate, $this->reportEndDate);
            $this->monthlySalesReportTotal = collect($this->monthlySalesReport)->sum('grand_total');
        } elseif ($this->selectedReport === 'daily-purchases') {
            // Don't store paginated results - will be generated in render method
            $this->dailyPurchasesReportTotal = PurchaseOrder::whereBetween('order_date', [$this->reportStartDate, $this->reportEndDate])
                ->sum('total_amount');
        } elseif ($this->selectedReport === 'inventory-stock') {
            $this->inventoryReport = [];
            $this->reportStats = $this->getInventoryStats();
        } elseif ($this->selectedReport === 'outstanding-accounts') {
            $this->outstandingAccountsReport = $this->getOutstandingAccountsReport();
        } elseif ($this->selectedReport === 'product-value') {
            $this->productValueReport = $this->getProductValueReport();
            $this->productValueReportTotal = collect($this->productValueReport)->sum('total_value');
        }
    }

    public function downloadReport()
    {
        $filename = $this->selectedReport . '_report_' . now()->format('Y_m_d') . '.xlsx';

        switch ($this->selectedReport) {
            case 'sales':
                $export = new SalesReportExport($this->salesReport, $this->salesReportTotal);
                break;
            case 'salary':
                $export = new SalaryReportExport($this->salaryReport, $this->salaryReportTotal);
                break;
            case 'inventory':
                $export = new InventoryReportExport($this->inventoryReport, $this->inventoryReportTotal);
                break;
            case 'staff':
                $export = new StaffReportExport($this->staffReport, $this->staffReportTotal);
                break;
            case 'payments':
                $export = new PaymentsReportExport($this->paymentsReport, $this->paymentsReportTotal);
                break;
            case 'attendance':
                $export = new AttendanceReportExport($this->attendanceReport, $this->attendanceReportTotal);
                break;
            case 'daily-sales':
                $export = new SalesReportExport($this->dailySalesReport, $this->dailySalesReportTotal);
                break;
            case 'monthly-sales':
                $export = new SalesReportExport($this->monthlySalesReport, $this->monthlySalesReportTotal);
                break;
            case 'product-value':
                $export = new ProductValueReportExport($this->productValueReport, $this->productValueReportTotal);
                break;
            case 'inventory-stock':
                $export = new \App\Exports\InventoryStockReportExport();
                break;
            case 'daily-purchases':
                $export = new \App\Exports\DailyPurchasesReportExport($this->reportStartDate, $this->reportEndDate);
                break;
            default:
                $this->js("Swal.fire('Info', 'Export for this report type is not yet available.', 'info')");
                return;
        }

        return Excel::download($export, $filename);
    }

    public function printReport()
    {
        $this->dispatch('print-report', reportType: $this->selectedReport);
    }

    // Report data methods
    public function getSalesReport($start = null, $end = null)
    {
        $query = Sale::with('items', 'customer', 'payments')->orderBy('created_at', 'desc');

        // Filter by user for staff
        if ($this->isStaff()) {
            $query->where('user_id', Auth::id())->where('sale_type', 'staff');
        }

        if ($start) $query->whereDate('created_at', '>=', $start);
        if ($end) $query->whereDate('created_at', '<=', $end);

        return $query->limit(100)->get();
    }

    public function getSalaryReport($start = null, $end = null)
    {
        $query = DB::table('salaries')
            ->join('users', 'salaries.user_id', '=', 'users.id')
            ->select('users.name', 'salaries.net_salary', 'salaries.salary_month', 'salaries.payment_status')
            ->orderBy('salaries.salary_month', 'desc');

        if ($start) $query->whereDate('salaries.salary_month', '>=', $start);
        if ($end) $query->whereDate('salaries.salary_month', '<=', $end);

        return $query->limit(100)->get();
    }

    public function getInventoryReport($start = null, $end = null)
    {
        $query = DB::table('product_details')
            ->join('product_stocks', 'product_details.id', '=', 'product_stocks.product_id')
            ->join('brand_lists', 'product_details.brand_id', '=', 'brand_lists.id')
            ->select(
                'product_details.name',
                'product_details.model',
                'brand_lists.brand_name as brand',
                'product_stocks.total_stock',
                'product_stocks.available_stock',
                'product_stocks.sold_count',
                'product_stocks.damage_stock'
            )
            ->orderBy('product_stocks.available_stock', 'desc');

        return $query->get();
    }

    public function getStaffReport($start = null, $end = null)
    {
        $query = DB::table('users')
            ->where('role', 'staff')
            ->leftJoin('staff_sales', 'users.id', '=', 'staff_sales.staff_id')
            ->select(
                'users.name',
                'users.email',
                DB::raw('COALESCE(SUM(staff_sales.sold_value), 0) as total_sales'),
                DB::raw('COALESCE(SUM(staff_sales.sold_quantity), 0) as total_quantity')
            )
            ->groupBy('users.id', 'users.name', 'users.email');

        return $query->get();
    }

    public function getPaymentsReport($start = null, $end = null)
    {
        // Fetch all customer payments with sale and customer relationships
        $customerPayments = Payment::with(['sale' => function ($query) {
            $query->with('customer');
        }])
            ->orderBy('payment_date', 'desc');

        // Fetch all supplier payments with purchaseOrder and supplier relationships
        $supplierPayments = \App\Models\PurchasePayment::with(['purchaseOrder' => function ($query) {
            $query->with('supplier');
        }])
            ->orderBy('payment_date', 'desc');

        if ($start) {
            $customerPayments->whereDate('payment_date', '>=', $start);
            $supplierPayments->whereDate('payment_date', '>=', $start);
        }
        if ($end) {
            $customerPayments->whereDate('payment_date', '<=', $end);
            $supplierPayments->whereDate('payment_date', '<=', $end);
        }

        return [
            'customer' => $customerPayments->get(),
            'supplier' => $supplierPayments->get()
        ];
    }

    public function getAttendanceReport($start = null, $end = null)
    {
        $query = DB::table('attendances')
            ->join('users', 'attendances.user_id', '=', 'users.id')
            ->select(
                'users.name',
                'attendances.date',
                'attendances.check_in',
                'attendances.check_out',
                'attendances.status'
            )
            ->orderBy('attendances.date', 'desc');

        if ($start) $query->whereDate('attendances.date', '>=', $start);
        if ($end) $query->whereDate('attendances.date', '<=', $end);

        return $query->limit(100)->get();
    }

    public function getDailySalesReport($start = null, $end = null)
    {
        // Determine date range - keep only the selected month
        $startDate = $start ? \Carbon\Carbon::parse($start) : \Carbon\Carbon::now()->startOfMonth();
        $endDate = $end ? \Carbon\Carbon::parse($end) : \Carbon\Carbon::now()->endOfMonth();

        // Don't allow future dates - cap at today
        $today = \Carbon\Carbon::now()->endOfDay();
        if ($endDate->gt($today)) {
            $endDate = $today->copy();
        }

        // Store the original month boundaries for filtering
        $monthStartDate = $startDate->copy();
        $monthEndDate = $endDate->copy();

        // Get actual sales data
        $salesQuery = DB::table('sales')
            ->select(
                DB::raw('DATE(created_at) as sale_date'),
                DB::raw('SUM(total_amount) as grand_total'),
                DB::raw('COUNT(*) as total_sales')
            )
            ->whereDate('created_at', '>=', $monthStartDate)
            ->whereDate('created_at', '<=', $monthEndDate);

        // Filter by user for staff
        if ($this->isStaff()) {
            $salesQuery->where('user_id', Auth::id())->where('sale_type', 'staff');
        }

        $salesData = $salesQuery->groupBy('sale_date')
            ->get()
            ->keyBy('sale_date');

        // Get return data from returns_products table
        $returnsQuery = DB::table('returns_products')
            ->join('sales', 'returns_products.sale_id', '=', 'sales.id')
            ->select(
                DB::raw('DATE(sales.created_at) as sale_date'),
                DB::raw('SUM(returns_products.total_amount) as return_total')
            )
            ->whereDate('sales.created_at', '>=', $monthStartDate)
            ->whereDate('sales.created_at', '<=', $monthEndDate);

        // Filter by user for staff
        if ($this->isStaff()) {
            $returnsQuery->where('sales.user_id', Auth::id())->where('sales.sale_type', 'staff');
        }

        $returnsData = $returnsQuery->groupBy('sale_date')
            ->get()
            ->keyBy('sale_date');

        // Generate all days in the month only
        $allDays = [];
        $currentDate = $monthStartDate->copy();

        while ($currentDate->lte($monthEndDate)) {
            $dateStr = $currentDate->format('Y-m-d');
            $dayData = $salesData->get($dateStr);
            $returnData = $returnsData->get($dateStr);

            // Only add days up to today
            if ($currentDate->lte($today)) {
                $allDays[] = (object)[
                    'sale_date' => $dateStr,
                    'day_name' => $currentDate->format('l'),
                    'grand_total' => $dayData ? $dayData->grand_total : 0,
                    'return_total' => $returnData ? $returnData->return_total : 0,
                    'total_sales' => $dayData ? $dayData->total_sales : 0,
                ];
            }

            $currentDate->addDay();
        }

        return collect($allDays);
    }

    public function getMonthlySalesReport($start = null, $end = null)
    {
        $query = DB::table('sales')
            ->select(
                DB::raw('YEAR(sales.created_at) as year'),
                DB::raw('MONTH(sales.created_at) as month'),
                DB::raw('MONTHNAME(sales.created_at) as month_name'),
                DB::raw('SUM(sales.total_amount) as grand_total'),
                DB::raw('SUM(sales.discount_amount) as total_discount'),
                DB::raw('COUNT(DISTINCT sales.id) as total_sales')
            )
            ->groupBy('year', 'month', 'month_name')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'asc');

        // Filter by user for staff
        if ($this->isStaff()) {
            $query->where('sales.user_id', Auth::id())->where('sales.sale_type', 'staff');
        }

        if ($start) $query->whereDate('sales.created_at', '>=', $start);
        if ($end) $query->whereDate('sales.created_at', '<=', $end);

        $monthlyData = $query->get();

        // Get return totals from returns_products table for each month
        foreach ($monthlyData as $monthData) {
            $returnQuery = DB::table('returns_products')
                ->join('sales', 'returns_products.sale_id', '=', 'sales.id')
                ->whereYear('sales.created_at', $monthData->year)
                ->whereMonth('sales.created_at', $monthData->month);

            if ($this->isStaff()) {
                $returnQuery->where('sales.user_id', Auth::id())->where('sales.sale_type', 'staff');
            }

            $returnTotal = $returnQuery->sum('returns_products.total_amount');

            // Calculate payment adjustment (price difference between product price and sale price)
            $adjustmentQuery = DB::table('sale_items')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->join('product_prices', 'sale_items.product_id', '=', 'product_prices.product_id')
                ->whereYear('sales.created_at', $monthData->year)
                ->whereMonth('sales.created_at', $monthData->month);

            if ($this->isStaff()) {
                $adjustmentQuery->where('sales.user_id', Auth::id())->where('sales.sale_type', 'staff');
            }

            $paymentAdjustment = $adjustmentQuery->selectRaw('SUM((product_prices.selling_price - sale_items.unit_price) * sale_items.quantity) as adjustment')
                ->value('adjustment');

            $monthData->return_total = $returnTotal ?? 0;
            $monthData->payment_adjustment = abs($paymentAdjustment ?? 0);
        }

        return $monthlyData;
    }

    public function getCurrentReportData()
    {
        // For daily-purchases, generate paginated data on the fly
        if ($this->selectedReport === 'daily-purchases') {
            return $this->getDailyPurchasesReport($this->reportStartDate, $this->reportEndDate);
        }

        // For inventory-stock, generate paginated data on the fly
        if ($this->selectedReport === 'inventory-stock') {
            return $this->getInventoryStockReport();
        }

        return match ($this->selectedReport) {
            'sales' => $this->salesReport,
            'salary' => $this->salaryReport,
            'inventory' => $this->inventoryReport,
            'staff' => $this->staffReport,
            'payments' => $this->paymentsReport,
            'attendance' => $this->attendanceReport,
            'daily-sales' => $this->dailySalesReport,
            'monthly-sales' => $this->monthlySalesReport,
            'outstanding-accounts' => $this->outstandingAccountsReport,
            'product-value' => $this->productValueReport,
            default => [],
        };
    }

    public function getCurrentReportTotal()
    {
        return match ($this->selectedReport) {
            'sales' => $this->salesReportTotal,
            'salary' => $this->salaryReportTotal,
            'inventory' => $this->inventoryReportTotal,
            'staff' => $this->staffReportTotal,
            'payments' => $this->paymentsReportTotal,
            'attendance' => $this->attendanceReportTotal,
            'daily-sales' => $this->dailySalesReportTotal,
            'monthly-sales' => $this->monthlySalesReportTotal,
            'daily-purchases' => $this->dailyPurchasesReportTotal,
            'inventory-stock' => 0,
            'outstanding-accounts' => 0,
            'product-value' => $this->productValueReportTotal,
            default => 0,
        };
    }

    public function getReportTitle()
    {
        return match ($this->selectedReport) {
            'sales' => 'Sales Report',
            'salary' => 'Salary Report',
            'inventory' => 'Inventory Report',
            'staff' => 'Staff Performance Report',
            'payments' => 'Payments Report',
            'attendance' => 'Attendance Report',
            'daily-sales' => 'Daily Sales Report',
            'monthly-sales' => 'Monthly Sales Report',
            'product-value' => 'Product Value Report',
            default => 'Report',
        };
    }

    public function clearFilters()
    {
        $this->reportStartDate = null;
        $this->reportEndDate = null;
        $this->selectedMonth = now()->month;
        $this->selectedYear = now()->year;

        // Reset separate date pickers to current date
        $this->dailyMonth = now()->month;
        $this->dailyYear = now()->year;
        $this->monthlyYear = now()->year;

        $this->generateReport();
    }

    public function previousMonth()
    {
        if ($this->dailyMonth && $this->dailyYear) {
            $date = \Carbon\Carbon::createFromDate($this->dailyYear, $this->dailyMonth, 1);
            $date->subMonth();
            $this->dailyMonth = $date->month;
            $this->dailyYear = $date->year;
            $this->generateReport();
        }
    }

    public function nextMonth()
    {
        if ($this->dailyMonth && $this->dailyYear) {
            $currentDate = \Carbon\Carbon::createFromDate($this->dailyYear, $this->dailyMonth, 1);
            $nextMonthDate = $currentDate->copy()->addMonth();
            $today = \Carbon\Carbon::now();

            // Only allow navigation if next month is not in the future
            if ($nextMonthDate->lte($today)) {
                $this->dailyMonth = $nextMonthDate->month;
                $this->dailyYear = $nextMonthDate->year;
                $this->generateReport();
            }
        }
    }

    public function canNavigateNext()
    {
        if ($this->dailyMonth && $this->dailyYear) {
            $currentDate = \Carbon\Carbon::createFromDate($this->dailyYear, $this->dailyMonth, 1);
            $nextMonthDate = $currentDate->copy()->addMonth();
            $today = \Carbon\Carbon::now();
            return $nextMonthDate->lte($today);
        }
        return false;
    }

    public function previousYear()
    {
        if ($this->monthlyYear) {
            $this->monthlyYear = $this->monthlyYear - 1;
            $this->generateReport();
        }
    }

    public function nextYear()
    {
        if ($this->monthlyYear) {
            $currentYear = $this->monthlyYear;
            $today = \Carbon\Carbon::now();

            // Only allow navigation if next year is not in the future
            if ($currentYear < $today->year) {
                $this->monthlyYear = $currentYear + 1;
                $this->generateReport();
            }
        }
    }

    public function canNavigateNextYear()
    {
        if ($this->monthlyYear) {
            $today = \Carbon\Carbon::now();
            $nextYear = $this->monthlyYear + 1;
            // Can only navigate if next year is not beyond current year + 1
            return $nextYear <= ($today->year + 1);
        }
        return false;
    }

    // New Report Methods
    public function getDailyPurchasesReport($start, $end)
    {
        // Fetch purchase orders from database using Eloquent
        return PurchaseOrder::with(['supplier', 'items'])
            ->whereBetween('order_date', [$start, $end])
            ->orderBy('order_date', 'desc')
            ->paginate($this->perPage);
    }

    public function getInventoryStockReport()
    {
        // Fetch product stock information using Eloquent with pagination
        return ProductStock::with(['product.brand', 'product.category'])
            ->paginate($this->perPage);
    }

    public function getInventoryStats()
    {
        // Get stats from ALL products, not just paginated data
        $stocks = ProductStock::with(['product.brand', 'product.category'])->get();

        $totalProducts = $stocks->count();
        $totalStock = $stocks->sum('total_stock');
        $availableStock = $stocks->sum('available_stock');
        $lowStock = $stocks->where('available_stock', '<', 10)->count();

        return [
            'total_products' => $totalProducts,
            'total_stock' => $totalStock,
            'available_stock' => $availableStock,
            'low_stock' => $lowStock,
        ];
    }

    public function getOutstandingAccountsReport()
    {
        // Fetch customer outstanding accounts using Eloquent
        $customers = Customer::get()
            ->map(function ($customer) {
                $sales = Sale::where('customer_id', $customer->id)
                    ->where(function ($query) {
                        $query->where('due_amount', '>', 0)
                            ->orWhere('payment_status', '!=', 'paid');
                    })
                    ->get();

                return [
                    'customer' => $customer,
                    'invoices' => $sales->count(),
                    'total_due' => $sales->sum('due_amount'),
                ];
            })
            ->filter(function ($item) {
                return $item['total_due'] > 0;
            });

        // Fetch supplier outstanding accounts using Eloquent
        $suppliers = ProductSupplier::get()
            ->map(function ($supplier) {
                $orders = PurchaseOrder::where('supplier_id', $supplier->id)
                    ->where('status', '!=', 'received')
                    ->get();

                return [
                    'supplier' => $supplier,
                    'orders' => $orders->count(),
                    'total_due' => $orders->sum('due_amount'),
                ];
            })
            ->filter(function ($item) {
                return $item['total_due'] > 0;
            });

        return [
            'customers' => $customers,
            'suppliers' => $suppliers,
        ];
    }

    public function getProductValueReport()
    {
        $items = [];

        // Get all active products with their stocks, prices, and variants
        $products = \App\Models\ProductDetail::where('status', 'active')
            ->with(['variant', 'stocks', 'prices'])
            ->get();

        foreach ($products as $product) {
            // Check if product has variants
            if ($product->variant_id && $product->variant) {
                // Get all stock records for variant products
                $stocks = $product->stocks;

                // Ensure stocks is a valid collection before iterating
                if ($stocks && is_iterable($stocks)) {
                    foreach ($stocks as $stock) {
                        // Ensure stock is an object before accessing properties
                        if (!$stock || !is_object($stock)) {
                            continue;
                        }

                        // Calculate pending sales for this specific variant
                        $pendingQty = \App\Models\SaleItem::whereHas('sale', function ($query) {
                            $query->where('status', 'pending');
                        })
                            ->where('product_id', $product->id)
                            ->where('variant_value', $stock->variant_value)
                            ->sum('quantity');

                        // Calculate available stock (physical - pending)
                        $availableStock = max(0, ($stock->available_stock ?? 0) - $pendingQty);

                        // Only show if there's available stock
                        if ($availableStock > 0) {
                            // Find matching price for this variant
                            $price = $product->prices && count($product->prices) > 0
                                ? $product->prices->firstWhere('variant_value', $stock->variant_value)
                                : null;

                            // Get supplier price or default to 0
                            $supplierPrice = $price ? ($price->supplier_price ?? 0) : 0;

                            // Calculate total value
                            $totalValue = $availableStock * $supplierPrice;

                            $items[] = [
                                'product_code' => $product->code,
                                'product_name' => $product->name,
                                'variant_value' => $stock->variant_value,
                                'display_name' => $product->name . ' (' . $stock->variant_value . ')',
                                'available_stock' => $availableStock,
                                'supplier_price' => $supplierPrice,
                                'total_value' => $totalValue,
                            ];
                        }
                    }
                }
            } else {
                // Non-variant product
                $stock = $product->stocks && $product->stocks->count() > 0
                    ? $product->stocks->first()
                    : null;

                if ($stock) {
                    // Calculate pending sales
                    $pendingQty = \App\Models\SaleItem::whereHas('sale', function ($query) {
                        $query->where('status', 'pending');
                    })
                        ->where('product_id', $product->id)
                        ->whereNull('variant_value')
                        ->sum('quantity');

                    // Calculate available stock (physical - pending)
                    $availableStock = max(0, ($stock->available_stock ?? 0) - $pendingQty);

                    // Only show if there's available stock
                    if ($availableStock > 0) {
                        $price = $product->prices && count($product->prices) > 0
                            ? $product->prices->first()
                            : null;

                        // Get supplier price or default to 0
                        $supplierPrice = $price ? ($price->supplier_price ?? 0) : 0;

                        // Calculate total value
                        $totalValue = $availableStock * $supplierPrice;

                        $items[] = [
                            'product_code' => $product->code,
                            'product_name' => $product->name,
                            'variant_value' => null,
                            'display_name' => $product->name,
                            'available_stock' => $availableStock,
                            'supplier_price' => $supplierPrice,
                            'total_value' => $totalValue,
                        ];
                    }
                }
            }
        }

        // Sort by total value descending
        return collect($items)->sortByDesc('total_value')->values()->all();
    }

    public function render()
    {
        return view('livewire.admin.reports', [
            'currentReportData' => $this->getCurrentReportData(),
            'currentReportTotal' => $this->getCurrentReportTotal(),
            'reportStats' => $this->reportStats,
            'reportTitle' => $this->getReportTitle(),
            'showDetailModal' => $this->showDetailModal,
            'selectedDetailType' => $this->selectedDetailType,
            'selectedDetailData' => $this->selectedDetailData,
        ])->layout($this->layout);
    }

    // View Details methods
    public function viewCustomerDetails($customerId)
    {
        $customer = Customer::findOrFail($customerId);

        // Get customer's outstanding invoices
        $invoices = Sale::where('customer_id', $customerId)
            ->where(function ($query) {
                $query->where('due_amount', '>', 0)
                    ->orWhere('payment_status', '!=', 'paid');
            })
            ->with('payments')
            ->orderBy('created_at', 'desc')
            ->get();

        $this->selectedDetailData = [
            'type' => 'customer',
            'customer' => $customer,
            'invoices' => $invoices,
            'total_due' => $invoices->sum('due_amount'),
            'invoice_count' => $invoices->count(),
        ];

        $this->selectedDetailType = 'customer';
        $this->showDetailModal = true;
    }

    public function viewSupplierDetails($supplierId)
    {
        $supplier = ProductSupplier::findOrFail($supplierId);

        // Get supplier's pending orders
        $orders = PurchaseOrder::where('supplier_id', $supplierId)
            ->where('status', '!=', 'received')
            ->with('items')
            ->orderBy('created_at', 'desc')
            ->get();

        $this->selectedDetailData = [
            'type' => 'supplier',
            'supplier' => $supplier,
            'orders' => $orders,
            'total_due' => $orders->sum('due_amount'),
            'order_count' => $orders->count(),
        ];

        $this->selectedDetailType = 'supplier';
        $this->showDetailModal = true;
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedDetailData = null;
        $this->selectedDetailType = null;
    }
}
