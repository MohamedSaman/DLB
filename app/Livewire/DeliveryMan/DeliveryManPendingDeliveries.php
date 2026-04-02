<?php

namespace App\Livewire\DeliveryMan;

use App\Models\Sale;
use App\Models\Customer;
use App\Models\SaleItem;
use App\Models\ProductStock;
use App\Models\ReturnsProduct;
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
        } elseif ($this->confirmAction === 'return') {
            $this->returnInvoice($this->confirmSaleId);
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

    /**
     * Fully return an invoice from pending deliveries.
     * Restores stock and reduces due from sale/customer.
     */
    public function returnInvoice($saleId)
    {
        $sale = Sale::with(['items', 'customer'])
            ->where('status', 'confirm')
            ->whereIn('delivery_status', ['pending', 'in_transit'])
            ->find($saleId);

        if (!$sale) {
            $this->dispatch('show-toast', type: 'error', message: 'Sale not found for return.');
            return;
        }

        if ($sale->delivery_status === 'in_transit' && (int) $sale->delivered_by !== (int) Auth::id()) {
            $this->dispatch('show-toast', type: 'error', message: 'You can only return your own in-transit invoices.');
            return;
        }

        try {
            DB::beginTransaction();

            $dueReduction = (float) $sale->due_amount;

            foreach ($sale->items as $item) {
                $remainingQty = $this->getRemainingReturnQuantity($sale->id, $item);

                if ($remainingQty <= 0) {
                    continue;
                }

                ReturnsProduct::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item->product_id,
                    'variant_id' => $item->variant_id,
                    'variant_value' => $item->variant_value,
                    'return_quantity' => $remainingQty,
                    'selling_price' => $item->unit_price,
                    'total_amount' => $remainingQty * (float) $item->unit_price,
                    'notes' => 'Full invoice return by delivery man',
                ]);

                $this->restoreProductStock(
                    (int) $item->product_id,
                    (int) $remainingQty,
                    $item->variant_id,
                    $item->variant_value
                );
            }

            $sale->update([
                'subtotal' => 0,
                'discount_amount' => 0,
                'total_amount' => 0,
                'due_amount' => 0,
                'payment_status' => 'paid',
                'delivery_status' => 'cancelled',
                'delivered_by' => Auth::id(),
                'delivered_at' => now(),
                'notes' => trim(($sale->notes ? $sale->notes . PHP_EOL : '') . 'Invoice fully returned by delivery man on ' . now()->format('Y-m-d H:i:s')),
            ]);

            if ($sale->customer && $dueReduction > 0) {
                $customer = Customer::find($sale->customer->id);
                if ($customer) {
                    $customer->due_amount = max(0, (float) ($customer->due_amount ?? 0) - $dueReduction);
                    $customer->total_due = (float) ($customer->opening_balance ?? 0) + (float) $customer->due_amount;
                    $customer->save();
                }
            }

            DB::commit();

            $this->closeDetailsModal();
            $this->dispatch('show-toast', type: 'success', message: 'Invoice returned successfully. Stock restored and due adjusted.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Delivery return invoice error: ' . $e->getMessage(), [
                'sale_id' => $saleId,
                'user_id' => Auth::id(),
            ]);
            $this->dispatch('show-toast', type: 'error', message: 'Failed to return invoice. Please try again.');
        }
    }

    private function getRemainingReturnQuantity(int $saleId, SaleItem $item): int
    {
        $alreadyReturned = ReturnsProduct::where('sale_id', $saleId)
            ->where('product_id', $item->product_id)
            ->where(function ($q) use ($item) {
                if ($item->variant_id) {
                    $q->where('variant_id', $item->variant_id);
                } else {
                    $q->whereNull('variant_id');
                }
            })
            ->where(function ($q) use ($item) {
                if ($item->variant_value !== null && $item->variant_value !== '') {
                    $q->where('variant_value', $item->variant_value);
                } else {
                    $q->whereNull('variant_value')->orWhere('variant_value', '');
                }
            })
            ->sum('return_quantity');

        return max(0, (int) $item->quantity - (int) $alreadyReturned);
    }

    private function restoreProductStock(int $productId, int $quantity, $variantId = null, $variantValue = null): void
    {
        $stockQuery = ProductStock::where('product_id', $productId);

        if ($variantId) {
            $stockQuery->where('variant_id', $variantId);
            if ($variantValue) {
                $stockQuery->where('variant_value', $variantValue);
            }
        } else {
            $stockQuery->where(function ($q) use ($variantValue) {
                $q->whereNull('variant_id')->orWhere('variant_id', 0);

                if ($variantValue !== null && $variantValue !== '') {
                    $q->where('variant_value', $variantValue);
                } else {
                    $q->where(function ($sq) {
                        $sq->whereNull('variant_value')
                            ->orWhere('variant_value', '')
                            ->orWhere('variant_value', 'null');
                    });
                }
            });
        }

        $stock = $stockQuery->first();

        if (!$stock) {
            $stock = ProductStock::create([
                'product_id' => $productId,
                'variant_id' => $variantId,
                'variant_value' => $variantValue,
                'available_stock' => 0,
                'damage_stock' => 0,
                'total_stock' => 0,
                'sold_count' => 0,
                'restocked_quantity' => 0,
            ]);
        }

        $stock->available_stock += $quantity;
        $stock->sold_count = max(0, (int) $stock->sold_count - $quantity);
        $stock->updateTotals();
    }

    public function render()
    {
        $userId = Auth::id();

        $sales = Sale::where('status', 'confirm')
            ->where(function ($q) use ($userId) {
                $q->where(function ($pending) use ($userId) {
                    $pending->where('delivery_status', 'pending')
                        ->where(function ($assignment) use ($userId) {
                            $assignment->whereNull('delivered_by')
                                ->orWhere('delivered_by', $userId);
                        });
                })->orWhere(function ($inTransit) use ($userId) {
                    $inTransit->where('delivery_status', 'in_transit')
                        ->where('delivered_by', $userId);
                });
            })
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
