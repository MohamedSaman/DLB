<?php

namespace App\Livewire\Salesman;

use App\Models\Sale;
use App\Models\ProductStock;
use App\Models\StaffExpense;
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
    }

    public function openDaySummaryModal()
    {
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
