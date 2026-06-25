<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\ReturnsProduct;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Livewire\Concerns\WithDynamicLayout;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

#[Title("Product Return")]
class ReturnList extends Component
{
    use WithDynamicLayout, WithPagination;


    // Do not store the full collection/paginator in a public property
    public $returnsCount = 0;
    public $returnSearch = '';
    public $selectedReturn = null;
    public $selectedReturnItems = [];
    public $showReceiptModal = false;
    public $currentReturnId = null;
    public $perPage = 10;

    // Properties for editing returns
    public $showEditModal = false;
    public $editingReturnItems = [];

    public function mount()
    {
        // Load lightweight count; actual paginated data is returned from render()
        $this->loadReturns();
    }

    private function getReturnsBaseQuery()
    {
        $query = ReturnsProduct::query();

        // Filter by user for staff - only show returns for their own sales
        if ($this->isStaff()) {
            $query->whereHas('sale', function ($q) {
                $q->where('user_id', Auth::id());
            });
        }

        if (!empty($this->returnSearch)) {
            $search = '%' . $this->returnSearch . '%';
            $query->where(function ($q) use ($search) {
                $q->whereHas('sale', function ($sq) use ($search) {
                    $sq->where('invoice_number', 'like', $search);
                })->orWhereHas('product', function ($pq) use ($search) {
                    $pq->where('name', 'like', $search)
                        ->orWhere('code', 'like', $search);
                });
            });
        }

        return $query;
    }

    protected function loadReturns()
    {
        $this->returnsCount = $this->getReturnsBaseQuery()->distinct('sale_id')->count('sale_id');
    }

    public function updatedReturnSearch()
    {
        $this->resetPage();
        $this->loadReturns();
    }

    public function showReturnDetails($saleId)
    {
        $this->selectedReturnItems = ReturnsProduct::with(['sale.customer', 'product.variant'])
            ->where('sale_id', $saleId)
            ->get();

        if ($this->selectedReturnItems->isEmpty()) {
            $this->dispatch('showToast', ['type' => 'error', 'message' => 'No return records found for this invoice.']);
            return;
        }

        $this->selectedReturn = $this->selectedReturnItems->first();
        $this->dispatch('showModal', 'returnDetailsModal');
    }

    public function showReceipt($saleId)
    {
        $this->selectedReturnItems = ReturnsProduct::with(['sale.customer', 'product.variant'])
            ->where('sale_id', $saleId)
            ->get();

        if ($this->selectedReturnItems->isEmpty()) {
            $this->dispatch('showToast', ['type' => 'error', 'message' => 'No return records found for this invoice.']);
            return;
        }

        $this->selectedReturn = $this->selectedReturnItems->first();
        $this->currentReturnId = $saleId;
        $this->showReceiptModal = true;
        $this->dispatch('showModal', 'receiptModal');
    }

    public function downloadReturn($saleId)
    {
        // For compatibility, we can generate a PDF of the receipt for this invoice
        $returns = ReturnsProduct::with(['sale.customer', 'product.variant'])->where('sale_id', $saleId)->get();

        if ($returns->isEmpty()) {
            $this->dispatch('showToast', ['type' => 'error', 'message' => 'Return records not found.']);
            return;
        }

        try {
            // Pass $returns to the PDF view
            $pdf = PDF::loadView('admin.returns.return-receipt-pdf', compact('returns'));

            $pdf->setPaper('a4', 'portrait');
            $pdf->setOption('dpi', 150);
            $pdf->setOption('defaultFont', 'sans-serif');

            return response()->streamDownload(
                function () use ($pdf) {
                    echo $pdf->output();
                },
                'return-receipt-' . $saleId . '-' . now()->format('Y-m-d') . '.pdf'
            );
        } catch (\Exception $e) {
            $this->dispatch('showToast', ['type' => 'error', 'message' => 'Failed to generate PDF: ' . $e->getMessage()]);
        }
    }

    public function printReceipt()
    {
        $this->dispatch('printReceipt');
    }

    public function deleteReturn($saleId)
    {
        $this->selectedReturnItems = ReturnsProduct::with(['product'])->where('sale_id', $saleId)->get();
        if ($this->selectedReturnItems->isEmpty()) {
            $this->dispatch('showToast', ['type' => 'error', 'message' => 'Return records not found.']);
            return;
        }
        $this->selectedReturn = $this->selectedReturnItems->first();
        $this->currentReturnId = $saleId;
        $this->dispatch('showModal', 'deleteReturnModal');
    }

    public function confirmDeleteReturn()
    {
        try {
            if ($this->currentReturnId) {
                $saleId = $this->currentReturnId;
                $returns = ReturnsProduct::where('sale_id', $saleId)->get();

                \DB::transaction(function () use ($returns, $saleId) {
                    foreach ($returns as $return) {
                        // Restore the stock before deleting the return record
                        $this->restoreStock($return);
                        $return->delete();
                    }

                    // Recalculate sale totals
                    $this->recalculateSaleTotals($saleId);
                });

                // Refresh lightweight data and reset pagination if needed
                $this->loadReturns();
                $this->resetPage();

                $this->dispatch('hideModal', 'deleteReturnModal');
                $this->dispatch('showToast', ['type' => 'success', 'message' => 'Return records deleted successfully!']);
            }
        } catch (\Exception $e) {
            $this->dispatch('showToast', ['type' => 'error', 'message' => 'Error deleting return: ' . $e->getMessage()]);
        }
    }

    public function editReturn($saleId)
    {
        $sale = \App\Models\Sale::find($saleId);
        if (!$sale) {
            $this->dispatch('showToast', ['type' => 'error', 'message' => 'Sale record not found.']);
            return;
        }

        $returns = ReturnsProduct::with(['product.variant'])->where('sale_id', $saleId)->get();
        if ($returns->isEmpty()) {
            $this->dispatch('showToast', ['type' => 'error', 'message' => 'No return records found for this invoice.']);
            return;
        }

        $this->currentReturnId = $saleId;
        $this->editingReturnItems = [];

        foreach ($returns as $return) {
            // Determine max qty available for this product on this sale
            $saleItem = \App\Models\SaleItem::where('sale_id', $return->sale_id)
                ->where('product_id', $return->product_id)
                ->where('variant_id', $return->variant_id)
                ->first();
            if (!$saleItem) {
                $saleItem = \App\Models\SaleItem::where('sale_id', $return->sale_id)
                    ->where('product_id', $return->product_id)
                    ->first();
            }

            $originalQty = $saleItem ? $saleItem->quantity : $return->return_quantity;

            // Count other returns for this product
            $otherReturnsSum = ReturnsProduct::where('sale_id', $return->sale_id)
                ->where('product_id', $return->product_id)
                ->where('id', '!=', $return->id)
                ->sum('return_quantity');

            $maxQty = $originalQty - $otherReturnsSum;

            $productName = $return->product?->name ?? 'N/A';
            if ($return->variant_value && $return->product && $return->product->variant) {
                $productName .= ' (' . $return->product->variant->variant_name . ': ' . $return->variant_value . ')';
            } elseif ($return->variant_value) {
                $productName .= ' (' . $return->variant_value . ')';
            }

            $this->editingReturnItems[] = [
                'id' => $return->id,
                'product_name' => $productName,
                'selling_price' => $return->selling_price,
                'usable_qty' => $return->usable_quantity,
                'damage_qty' => $return->damaged_quantity,
                'notes' => $return->notes,
                'max_qty' => $maxQty,
            ];
        }

        $this->showEditModal = true;
        $this->dispatch('showModal', 'editReturnModal');
    }

    public function updateReturn()
    {
        if (empty($this->editingReturnItems)) return;

        // Validation
        foreach ($this->editingReturnItems as $item) {
            $usable = (int)$item['usable_qty'];
            $damage = (int)$item['damage_qty'];
            $totalReturn = $usable + $damage;

            if ($usable < 0 || $damage < 0) {
                $this->dispatch('showToast', ['type' => 'error', 'message' => 'Return quantity cannot be negative.']);
                return;
            }

            if ($totalReturn > $item['max_qty']) {
                $this->dispatch('showToast', [
                    'type' => 'error',
                    'message' => 'Total return quantity for ' . $item['product_name'] . ' cannot exceed remaining invoice quantity: ' . $item['max_qty']
                ]);
                return;
            }
        }

        try {
            \DB::transaction(function () {
                foreach ($this->editingReturnItems as $item) {
                    $returnRecord = ReturnsProduct::find($item['id']);
                    if (!$returnRecord) continue;

                    $usable = (int)$item['usable_qty'];
                    $damage = (int)$item['damage_qty'];
                    $totalReturn = $usable + $damage;

                    // 1. Revert previous stock adjustment
                    $this->restoreStock($returnRecord);

                    // 2. Apply new stock adjustment
                    $this->applyStock($returnRecord, $usable, $damage);

                    // 3. Update Return Record
                    $returnAmount = $totalReturn * $returnRecord->selling_price;

                    // Build notes
                    $cleanNotes = preg_replace('/^(Usable: \d+(?:,\s*Damaged: \d+)?\.\s*)?/i', '', $item['notes']);
                    $notes = 'Customer return processed via system';
                    if ($usable > 0 && $damage > 0) {
                        $notes = "Usable: {$usable}, Damaged: {$damage}. " . $cleanNotes;
                    } elseif ($usable > 0) {
                        $notes = "Usable: {$usable}. " . $cleanNotes;
                    } elseif ($damage > 0) {
                        $notes = "Damaged: {$damage}. " . $cleanNotes;
                    } else {
                        $notes = $cleanNotes ?: $notes;
                    }

                    $returnRecord->update([
                        'return_quantity' => $totalReturn,
                        'usable_quantity' => $usable,
                        'damaged_quantity' => $damage,
                        'total_amount' => $returnAmount,
                        'notes' => $notes,
                    ]);
                }

                // 4. Recalculate Sale/Invoice Totals
                if ($this->currentReturnId) {
                    $this->recalculateSaleTotals($this->currentReturnId);
                }
            });

            $this->loadReturns();
            $this->closeModal();
            $this->dispatch('showToast', ['type' => 'success', 'message' => 'Return record updated successfully!']);
        } catch (\Exception $e) {
            $this->dispatch('showToast', ['type' => 'error', 'message' => 'Error updating return: ' . $e->getMessage()]);
        }
    }

    private function applyStock($return, $usableQty, $damageQty)
    {
        $stock = null;
        $productId = $return->product_id;
        $variantId = $return->variant_id;
        $variantValue = $return->variant_value;

        if ($variantId || $variantValue) {
            $stockQuery = \App\Models\ProductStock::where('product_id', $productId);
            if ($variantId) {
                $stockQuery->where('variant_id', $variantId);
            }
            if ($variantValue) {
                $stockQuery->where('variant_value', $variantValue);
            }
            $stock = $stockQuery->first();
        } else {
            $stock = \App\Models\ProductStock::where('product_id', $productId)
                ->where(function ($q) {
                    $q->whereNull('variant_value')
                        ->orWhere('variant_value', '')
                        ->orWhere('variant_value', 'null');
                })
                ->whereNull('variant_id')
                ->first();

            if (!$stock) {
                $stock = \App\Models\ProductStock::where('product_id', $productId)->first();
            }
        }

        if ($stock) {
            $totalQty = $usableQty + $damageQty;
            $stock->available_stock += $usableQty;
            $stock->damage_stock += $damageQty;
            if ($stock->sold_count >= $totalQty) {
                $stock->sold_count -= $totalQty;
            }
            $stock->updateTotals();
        }
    }

    private function restoreStock($return)
    {
        $stock = null;
        $productId = $return->product_id;
        $variantId = $return->variant_id;
        $variantValue = $return->variant_value;

        if ($variantId || $variantValue) {
            $stockQuery = \App\Models\ProductStock::where('product_id', $productId);
            if ($variantId) {
                $stockQuery->where('variant_id', $variantId);
            }
            if ($variantValue) {
                $stockQuery->where('variant_value', $variantValue);
            }
            $stock = $stockQuery->first();
        } else {
            $stock = \App\Models\ProductStock::where('product_id', $productId)
                ->where(function ($q) {
                    $q->whereNull('variant_value')
                        ->orWhere('variant_value', '')
                        ->orWhere('variant_value', 'null');
                })
                ->whereNull('variant_id')
                ->first();

            if (!$stock) {
                $stock = \App\Models\ProductStock::where('product_id', $productId)->first();
            }
        }

        if ($stock) {
            $usableQty = $return->usable_quantity ?? $return->return_quantity;
            $damageQty = $return->damaged_quantity ?? 0;
            $totalQty = $usableQty + $damageQty;

            $stock->available_stock -= $usableQty;
            $stock->damage_stock -= $damageQty;
            $stock->sold_count += $totalQty;
            $stock->updateTotals();
        }
    }

    private function recalculateSaleTotals($saleId)
    {
        $sale = \App\Models\Sale::find($saleId);
        if (!$sale) return;

        // Get original subtotal (sum of items net price)
        $originalSubtotal = \App\Models\SaleItem::where('sale_id', $sale->id)
            ->get()
            ->sum(function ($item) {
                return ($item->unit_price * $item->quantity) - ($item->discount_per_unit * $item->quantity);
            });

        // Sum of all return amounts on this sale
        $totalReturnAmount = ReturnsProduct::where('sale_id', $sale->id)->sum('total_amount');

        // New subtotal
        $newSubtotal = $originalSubtotal - $totalReturnAmount;

        // Recalculate discount
        $discountAmount = 0;
        if ($sale->additional_discount_type === 'percentage' && $sale->additional_discount_percentage > 0) {
            $discountAmount = ($newSubtotal * $sale->additional_discount_percentage) / 100;
        } elseif ($sale->additional_discount_type === 'fixed') {
            $discountAmount = min($sale->discount_amount ?? 0, $newSubtotal);
        }

        // New total
        $newTotal = $newSubtotal - $discountAmount;

        // Update sale
        $previousTotal = $sale->total_amount;
        $totalReduction = $previousTotal - $newTotal; // positive if total decreased, negative if it increased
        $newDue = max(0, $sale->due_amount - $totalReduction);

        $sale->update([
            'subtotal' => $newSubtotal,
            'discount_amount' => $discountAmount,
            'total_amount' => $newTotal,
            'due_amount' => $newDue,
        ]);

        // Update customer's due amount
        $customer = \App\Models\Customer::find($sale->customer_id);
        if ($customer && $totalReduction != 0) {
            $customer->due_amount = max(0, ($customer->due_amount ?? 0) - $totalReduction);
            $customer->total_due = ($customer->opening_balance ?? 0) + $customer->due_amount;
            $customer->save();
        }
    }

    public function closeModal()
    {
        $this->selectedReturn = null;
        $this->selectedReturnItems = [];
        $this->currentReturnId = null;
        $this->showReceiptModal = false;
        $this->showEditModal = false;
        $this->editingReturnItems = [];
        $this->dispatch('hideModal', 'returnDetailsModal');
        $this->dispatch('hideModal', 'deleteReturnModal');
        $this->dispatch('hideModal', 'receiptModal');
        $this->dispatch('hideModal', 'editReturnModal');
    }

    public function render()
    {
        $returns = $this->getReturnsBaseQuery()
            ->select('sale_id')
            ->selectRaw('SUM(usable_quantity) as total_usable')
            ->selectRaw('SUM(damaged_quantity) as total_damaged')
            ->selectRaw('SUM(total_amount) as total_return_amount')
            ->selectRaw('MAX(created_at) as latest_return_date')
            ->groupBy('sale_id')
            ->orderByDesc('latest_return_date')
            ->with(['sale.customer', 'sale.items'])
            ->paginate($this->perPage);

        return view('livewire.admin.return-list', [
            'returns' => $returns,
            'selectedReturn' => $this->selectedReturn,
            'currentReturnId' => $this->currentReturnId,
        ])->layout($this->layout);
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }
}
