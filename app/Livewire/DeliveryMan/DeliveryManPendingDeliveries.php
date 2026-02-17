<?php

namespace App\Livewire\DeliveryMan;

use App\Models\Sale;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

#[Title('Pending Deliveries')]
#[Layout('components.layouts.delivery-man')]
class DeliveryManPendingDeliveries extends Component
{
    use WithPagination;

    public $search = '';
    public $selectedSale = null;
    public $showDetailsModal = false;
    public $showConfirmModal = false;
    public $confirmAction = '';
    public $confirmSaleId = null;
    public $showPaymentModal = false;
    public $paymentSale = null;
    public $showEditDiscountModal = false;
    public $editDiscountSale = null;
    public $newDiscountPercentage = 0;

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function viewDetails($saleId)
    {
        $this->selectedSale = Sale::with(['customer', 'items.product', 'user', 'payments'])
            ->find($saleId);
        $this->showDetailsModal = true;
    }

    public function closeDetailsModal()
    {
        $this->showDetailsModal = false;
        $this->selectedSale = null;
    }

    /**
     * Show confirmation modal
     */
    public function showConfirmation($action, $saleId)
    {
        $this->confirmAction = $action;
        $this->confirmSaleId = $saleId;
        $this->showConfirmModal = true;
    }

    /**
     * Close confirmation modal
     */
    public function closeConfirmModal()
    {
        $this->showConfirmModal = false;
        $this->confirmAction = '';
        $this->confirmSaleId = null;
    }

    /**
     * Execute confirmed action
     */
    public function executeConfirmedAction()
    {
        if ($this->confirmAction === 'transit') {
            $this->markInTransit($this->confirmSaleId);
        } elseif ($this->confirmAction === 'delivered') {
            $this->markDelivered($this->confirmSaleId);
        }

        $this->closeConfirmModal();
    }

    /**
     * Mark delivery as in transit
     */
    public function markInTransit($saleId)
    {
        $sale = Sale::find($saleId);

        if ($sale && $sale->status === 'confirm') {
            $sale->update([
                'delivery_status' => 'in_transit',
                'delivered_by' => Auth::id(),
            ]);

            $this->dispatch('show-toast', type: 'success', message: 'Marked as in transit.');
        }
    }

    /**
     * Mark delivery as completed
     */
    public function markDelivered($saleId)
    {
        $sale = Sale::find($saleId);

        if ($sale && $sale->status === 'confirm') {
            $sale->update([
                'delivery_status' => 'delivered',
                'delivered_by' => Auth::id(),
                'delivered_at' => now(),
            ]);

            // Check if there's a due amount
            if ($sale->due_amount > 0) {
                $this->paymentSale = $sale;
                $this->showPaymentModal = true;
                $this->closeDetailsModal();
            } else {
                $this->dispatch('show-toast', type: 'success', message: 'Delivery completed!');
                $this->closeDetailsModal();
            }
        }
    }

    /**
     * Close payment modal
     */
    public function closePaymentModal()
    {
        $this->showPaymentModal = false;
        $this->paymentSale = null;
    }

    /**
     * Redirect to payment page
     */
    public function goToPayment()
    {
        if ($this->paymentSale) {
            return redirect()->route('delivery.payments', ['customer_id' => $this->paymentSale->customer_id]);
        }
    }

    /**
     * Open edit discount modal
     */
    public function openEditDiscountModal($saleId)
    {
        $this->editDiscountSale = Sale::with(['customer', 'items.product'])->find($saleId);

        if ($this->editDiscountSale) {
            $this->newDiscountPercentage = $this->editDiscountSale->discount_amount ?? 0;
        }

        $this->showEditDiscountModal = true;
    }

    /**
     * Close edit discount modal
     */
    public function closeEditDiscountModal()
    {
        $this->showEditDiscountModal = false;
        $this->editDiscountSale = null;
        $this->newDiscountPercentage = 0;
    }

    /**
     * Update sale discount
     */
    public function updateDiscount()
    {
        $this->validate([
            'newDiscountPercentage' => 'required|numeric|min:0|max:100',
        ]);

        if (!$this->editDiscountSale) {
            $this->dispatch('show-toast', type: 'error', message: 'Sale not found.');
            return;
        }

        try {
            DB::beginTransaction();

            $sale = $this->editDiscountSale;
            $customer = $sale->customer;

            // Store old values
            $oldTotalAmount = $sale->total_amount;
            $oldDueAmount = $sale->due_amount;

            // Calculate new amounts with the new discount
            $subtotal = $sale->subtotal;
            $discountAmount = ($subtotal * $this->newDiscountPercentage) / 100;
            $newTotalAmount = $subtotal - $discountAmount;

            // Calculate new due amount
            // Paid amount = old total - old due
            $paidAmount = $oldTotalAmount - $oldDueAmount;
            $newDueAmount = max(0, $newTotalAmount - $paidAmount);

            // Update sale
            $sale->update([
                'discount_type' => 'percentage',
                'discount_amount' => $this->newDiscountPercentage,
                'total_amount' => $newTotalAmount,
                'due_amount' => $newDueAmount,
                'payment_status' => $newDueAmount > 0 ? ($paidAmount > 0 ? 'partial' : 'pending') : 'paid',
            ]);

            // Update customer due amount
            if ($customer) {
                // Calculate the difference in due amounts
                $dueAmountDifference = $newDueAmount - $oldDueAmount;

                $customer->due_amount = ($customer->due_amount ?? 0) + $dueAmountDifference;
                $customer->total_due = ($customer->opening_balance ?? 0) + $customer->due_amount;
                $customer->save();
            }

            DB::commit();

            $this->closeEditDiscountModal();
            $this->dispatch('show-toast', type: 'success', message: 'Discount updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Discount update error: ' . $e->getMessage());
            $this->dispatch('show-toast', type: 'error', message: 'Error updating discount: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $sales = Sale::where('status', 'confirm')
            ->whereIn('delivery_status', ['pending', 'in_transit'])
            ->when($this->search, function ($q) {
                $q->where(function ($sq) {
                    $sq->where('sale_id', 'like', '%' . $this->search . '%')
                        ->orWhere('invoice_number', 'like', '%' . $this->search . '%')
                        ->orWhereHas('customer', function ($cq) {
                            $cq->where('name', 'like', '%' . $this->search . '%')
                                ->orWhere('phone', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->with(['customer', 'items'])
            ->orderBy('created_at', 'asc')
            ->paginate(15);

        return view('livewire.delivery-man.delivery-man-pending-deliveries', [
            'sales' => $sales,
        ]);
    }
}
