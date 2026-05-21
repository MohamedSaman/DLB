<?php

namespace App\Livewire\DeliveryMan;

use App\Models\Sale;
use App\Models\Payment;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;

#[Title('Delivery Man Dashboard')]
#[Layout('components.layouts.delivery-man')]
class DeliveryManDashboard extends Component
{
    public $pendingDeliveries = 0;
    public $completedDeliveries = 0;
    public $deliveredSalesTotal = 0;
    public $todayDeliveredSalesTotal = 0;
    public $totalReceivedAmount = 0;
    public $todayReceivedAmount = 0;
    public $recentDeliveries = [];

    public $showDaySummaryModal = false;

    // Summary variables (for the modal filter)
    public $summaryPeriod = 'today';
    public $summaryCustomMonth = '';
    public $summaryPeriodLabel = 'Today';
    public $summaryMonthOptions = [];

    public $summaryDeliveredCount = 0;
    public $summaryDeliveredAmount = 0;
    public $summaryTotalReceived = 0;
    public $summaryCashReceived = 0;
    public $summaryChequeReceived = 0;
    public $summaryBankReceived = 0;

    public function mount()
    {
        $userId = Auth::id();

        // Delivery counts for this delivery man
        $this->pendingDeliveries = Sale::where('status', 'confirm')
            ->where('delivered_by', $userId)
            ->whereIn('delivery_status', ['pending', 'in_transit'])
            ->count();

        $this->completedDeliveries = Sale::where('delivered_by', $userId)
            ->where('delivery_status', 'delivered')
            ->count();

        // Delivered sales total (all time and today)
        $this->deliveredSalesTotal = Sale::where('delivered_by', $userId)
            ->where('delivery_status', 'delivered')
            ->sum('total_amount');

        $this->todayDeliveredSalesTotal = Sale::where('delivered_by', $userId)
            ->where('delivery_status', 'delivered')
            ->whereDate('delivered_at', today())
            ->sum('total_amount');

        // Received payment amounts (all time and today)
        $this->totalReceivedAmount = Payment::where('collected_by', $userId)
            ->whereIn('status', ['approved', 'paid'])
            ->sum('amount');

        $this->todayReceivedAmount = Payment::where('collected_by', $userId)
            ->whereIn('status', ['approved', 'paid'])
            ->whereDate('collected_at', today())
            ->sum('amount');

        // Recent deliveries
        $this->recentDeliveries = Sale::where('status', 'confirm')
            ->where('delivered_by', $userId)
            ->with('customer')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

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

        $salesQuery = Sale::where('delivered_by', $userId)->where('delivery_status', 'delivered');
        if ($isRange) {
            $salesQuery->whereBetween('delivered_at', [$from->startOfDay(), $to->endOfDay()]);
        } else {
            $salesQuery->whereDate('delivered_at', $from);
        }

        $this->summaryDeliveredAmount = (float) (clone $salesQuery)->sum('total_amount');
        $this->summaryDeliveredCount = (int) (clone $salesQuery)->count();

        $paymentQuery = Payment::where('collected_by', $userId)->whereIn('status', ['approved', 'paid']);
        if ($isRange) {
            $paymentQuery->whereBetween('collected_at', [$from->startOfDay(), $to->endOfDay()]);
        } else {
            $paymentQuery->whereDate('collected_at', $from);
        }

        $this->summaryTotalReceived = (float) (clone $paymentQuery)->sum('amount');
        $this->summaryCashReceived = (float) (clone $paymentQuery)->where('payment_method', 'cash')->sum('amount');
        $this->summaryChequeReceived = (float) (clone $paymentQuery)->where('payment_method', 'cheque')->sum('amount');
        $this->summaryBankReceived = (float) (clone $paymentQuery)->where('payment_method', 'bank_transfer')->sum('amount');
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
        return view('livewire.delivery-man.delivery-man-dashboard');
    }
}
