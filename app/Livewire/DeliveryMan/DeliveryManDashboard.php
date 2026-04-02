<?php

namespace App\Livewire\DeliveryMan;

use App\Models\Sale;
use App\Models\Payment;
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
            ->limit(10)
            ->get();
    }

    public function render()
    {
        return view('livewire.delivery-man.delivery-man-dashboard');
    }
}
