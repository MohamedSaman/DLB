<?php

namespace App\Livewire\Salesman;

use App\Models\Sale;
use App\Models\ProductStock;
use App\Models\StaffExpense;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;

#[Title('Salesman Dashboard')]
#[Layout('components.layouts.salesman')]
class SalesmanDashboard extends Component
{
    public $todaySalesAmount = 0;
    public $todaySalesCount = 0;
    public $todayCashAmount = 0;
    public $todayDueAmount = 0;
    public $todayExpenseAmount = 0;

    public $lifetimeSalesAmount = 0;
    public $lifetimeCashAmount = 0;

    public $systemStockUnits = 0;
    public $systemStockCapacity = 0;
    public $recentInvoicesCount = 0;

    public $recentSales = [];
    public $showDaySummaryModal = false;

    // Summary variables (for the modal filter)
    public $summaryPeriod = 'today';          // today | current_month | last_month | custom | all
    public $summaryCustomMonth = '';           // format: YYYY-MM
    public $summaryPeriodLabel = 'Today';
    public $summaryMonthOptions = [];

    public $summarySalesAmount = 0;
    public $summarySalesCount = 0;
    public $summaryCashAmount = 0;
    public $summaryDueAmount = 0;
    public $summaryExpenseAmount = 0;

    public function mount()
    {
        $userId = Auth::id();
        $today = now()->toDateString();

        $todaySalesQuery = Sale::where('user_id', $userId)
            ->where('status', 'confirm')
            ->whereDate('created_at', $today);

        $this->todaySalesAmount = (float) (clone $todaySalesQuery)->sum('total_amount');
        $this->todaySalesCount = (int) (clone $todaySalesQuery)->count();
        $this->todayDueAmount = (float) (clone $todaySalesQuery)->sum('due_amount');
        $this->todayCashAmount = max(0, $this->todaySalesAmount - $this->todayDueAmount);

        $this->todayExpenseAmount = (float) StaffExpense::where('staff_id', $userId)
            ->where('status', 'approved')
            ->whereDate('expense_date', $today)
            ->sum('amount');

        $this->lifetimeSalesAmount = (float) Sale::where('user_id', $userId)
            ->where('status', 'confirm')
            ->sum('total_amount');

        $lifetimeDueAmount = (float) Sale::where('user_id', $userId)
            ->where('status', 'confirm')
            ->sum('due_amount');

        $this->lifetimeCashAmount = max(0, $this->lifetimeSalesAmount - $lifetimeDueAmount);

        $this->systemStockUnits = (int) ProductStock::sum('available_stock');
        $this->systemStockCapacity = (int) ProductStock::sum('total_stock');

        // Get recent sales
        $this->recentSales = Sale::where('user_id', $userId)
            ->with('customer')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $this->recentInvoicesCount = count($this->recentSales);

        // Build the last-12-months list for the dropdown
        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $date = Carbon::now()->subMonths($i)->startOfMonth();
            $months[] = [
                'value' => $date->format('Y-m'),
                'label' => $date->format('F Y'),
            ];
        }
        $this->summaryMonthOptions = $months;
        $this->loadSummary();
    }

    public function loadSummary()
    {
        $userId = Auth::id();

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
                $this->summaryPeriodLabel = 'Today (' . Carbon::today()->format('d M Y') . ')';
                $isRange = false;
                break;
        }

        $salesQuery = Sale::where('user_id', $userId)->where('status', 'confirm');
        if ($isRange) {
            $salesQuery->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()]);
        } else {
            $salesQuery->whereDate('created_at', $from);
        }

        $this->summarySalesAmount = (float) (clone $salesQuery)->sum('total_amount');
        $this->summarySalesCount = (int) (clone $salesQuery)->count();
        $this->summaryDueAmount = (float) (clone $salesQuery)->sum('due_amount');
        $this->summaryCashAmount = max(0, $this->summarySalesAmount - $this->summaryDueAmount);

        $expenseQuery = StaffExpense::where('staff_id', $userId)->where('status', 'approved');
        if ($isRange) {
            $expenseQuery->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()]);
        } else {
            $expenseQuery->whereDate('expense_date', $from);
        }
        $this->summaryExpenseAmount = (float) $expenseQuery->sum('amount');
    }

    public function switchSummaryPeriod(string $period)
    {
        $this->summaryPeriod = $period;
        if ($period !== 'custom') {
            $this->summaryCustomMonth = '';
        }
        $this->loadSummary();
    }

    public function switchSummaryMonth(string $ym)
    {
        $this->summaryPeriod = 'custom';
        $this->summaryCustomMonth = $ym;
        $this->loadSummary();
    }
    public function openDaySummaryModal()
    {
        $this->summaryPeriod = 'today';
        $this->summaryCustomMonth = '';
        $this->loadSummary();
        $this->showDaySummaryModal = true;
    }

    public function closeDaySummaryModal()
    {
        $this->showDaySummaryModal = false;
    }

    public function render()
    {
        return view('livewire.salesman.salesman-dashboard');
    }
}
