<?php

namespace App\Livewire\Salesman;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Customer;
use App\Models\ReturnsProduct;
use App\Models\ProductStock;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

#[Title('My Sales')]
#[Layout('components.layouts.salesman')]
class SalesmanSalesList extends Component
{
    use WithPagination;

    public $search = '';
    public $selectedMonth = '';
    public $statusFilter = '';
    public $deliveryFilter = '';
    public $selectedSale = null;
    public $showDetailsModal = false;

    // Edit sale properties
    public $showEditModal = false;
    public $editingSale = null;
    public $editItems = [];
    public $editNotes = '';
    public $editDiscount = 0;
    public $editDiscountType = 'fixed'; // 'fixed' or 'percentage'

    // Return properties
    public $showReturnModal = false;
    public $returnItems = [];
    public $returnNotes = '';
    public $saleReturns = [];
    public $showFullReturnConfirmModal = false;

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedSelectedMonth()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function updatedDeliveryFilter()
    {
        $this->resetPage();
    }

    public function viewDetails($saleId)
    {
        $this->selectedSale = Sale::with(['customer', 'items.product', 'payments', 'approvedBy', 'deliveredBy', 'returns.product'])
            ->find($saleId);

        // Load returns for this sale
        $this->saleReturns = ReturnsProduct::where('sale_id', $saleId)
            ->with('product')
            ->get();

        $this->showDetailsModal = true;
    }

    public function closeDetailsModal()
    {
        $this->showDetailsModal = false;
        $this->selectedSale = null;
        $this->saleReturns = [];
    }

    // Edit Sale Methods
    public function openEditModal($saleId)
    {
        $sale = Sale::with(['items', 'customer'])->find($saleId);

        if (!$sale) {
            $this->showToast('error', 'Sale not found.');
            return;
        }

        // Allow editing of pending and approved sales
        if (!in_array($sale->status, ['pending', 'confirm'])) {
            $this->showToast('error', 'Delivered or rejected sales cannot be edited. Only pending and approved sales can be modified.');
            return;
        }

        // Only allow editing own sales
        if ($sale->user_id !== Auth::id()) {
            $this->showToast('error', 'You can only edit your own sales.');
            return;
        }

        $this->editingSale = $sale;
        $this->editNotes = $sale->notes ?? '';

        // Load discount type first
        $this->editDiscountType = $sale->discount_type ?? 'fixed';

        // Load discount value based on type
        if ($this->editDiscountType === 'percentage') {
            // For percentage type, discount_amount stores the percentage value
            $this->editDiscount = $sale->discount_amount ?? 0;
        } else {
            // For fixed type, discount_amount stores the rupee amount
            $this->editDiscount = $sale->discount_amount ?? 0;
        }

        // Prepare edit items with available stock info
        $this->editItems = [];
        foreach ($sale->items as $item) {
            // Calculate available stock for this product (excluding this sale's pending qty)
            $stockRecord = ProductStock::where('product_id', $item->product_id)
                ->when($item->variant_value, function ($q) use ($item) {
                    return $q->where('variant_value', $item->variant_value);
                }, function ($q) {
                    return $q->where(function ($sq) {
                        $sq->whereNull('variant_value')
                            ->orWhere('variant_value', '')
                            ->orWhere('variant_value', 'null');
                    })->whereNull('variant_id');
                })
                ->first();

            $rawAvailable = $stockRecord ? ($stockRecord->available_stock ?? 0) : 0;

            if ($sale->status === 'confirm') {
                $rawAvailable += $item->quantity;
            }

            // Subtract OTHER pending sales' quantities
            $otherPending = SaleItem::whereHas('sale', function ($q) use ($sale) {
                $q->where('status', 'pending')->where('id', '!=', $sale->id);
            })
                ->where('product_id', $item->product_id)
                ->when($item->variant_value, function ($q) use ($item) {
                    return $q->where('variant_value', $item->variant_value);
                })
                ->sum('quantity');

            $availableStock = max(0, $rawAvailable - $otherPending);

            $this->editItems[] = [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'quantity' => $item->quantity,
                'original_qty' => $item->quantity,
                'unit_price' => $item->unit_price,
                'discount' => $item->discount_per_unit ?? 0,
                'available' => $availableStock,
            ];
        }

        $this->showEditModal = true;
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->editingSale = null;
        $this->editItems = [];
        $this->editNotes = '';
        $this->editDiscount = 0;
        $this->editDiscountType = 'fixed';
    }

    public function updateEditItemQuantity($index, $quantity)
    {
        if (isset($this->editItems[$index])) {
            $newQty = max(1, (int)$quantity);
            $available = $this->editItems[$index]['available'] ?? 999;

            if ($newQty > $available) {
                $this->showToast('error', 'Not enough stock! Available: ' . $available);
                return;
            }

            $this->editItems[$index]['quantity'] = $newQty;
        }
    }

    public function removeEditItem($index)
    {
        if (count($this->editItems) > 1) {
            unset($this->editItems[$index]);
            $this->editItems = array_values($this->editItems);
        } else {
            $this->showToast('error', 'Sale must have at least one item.');
        }
    }

    public function getEditSubtotalProperty()
    {
        $subtotal = 0;
        foreach ($this->editItems as $item) {
            $subtotal += ($item['unit_price'] - ($item['discount'] ?? 0)) * $item['quantity'];
        }
        return $subtotal;
    }

    public function getEditTotalProperty()
    {
        $discountRupees = 0;

        if ($this->editDiscountType === 'percentage') {
            // editDiscount stores percentage, calculate rupees
            $discountRupees = ($this->editSubtotal * $this->editDiscount) / 100;
        } else {
            // editDiscount stores rupee amount
            $discountRupees = min($this->editDiscount, $this->editSubtotal);
        }

        return $this->editSubtotal - $discountRupees;
    }

    public function saveEditedSale()
    {
        if (!$this->editingSale || !in_array($this->editingSale->status, ['pending', 'confirm'])) {
            $this->showToast('error', 'Cannot update this sale.');
            return;
        }

        try {
            DB::beginTransaction();

            // Update sale items
            foreach ($this->editItems as $editItem) {
                $saleItem = SaleItem::find($editItem['id']);
                if ($saleItem) {
                    $oldQty = $saleItem->quantity;
                    $newQty = $editItem['quantity'];
                    $quantityDelta = $newQty - $oldQty;

                    if ($this->editingSale->status === 'confirm' && $quantityDelta !== 0) {
                        if ($quantityDelta > 0) {
                            \App\Services\FIFOStockService::deductStock($saleItem->product_id, $quantityDelta, $saleItem->variant_id, $saleItem->variant_value);
                        } else {
                            $restoreQty = abs($quantityDelta);
                            
                            $batchQuery = \App\Models\ProductBatch::where('product_id', $saleItem->product_id)
                                ->whereIn('status', ['active', 'depleted']);
                            if ($saleItem->variant_id) $batchQuery->where('variant_id', $saleItem->variant_id);
                            if ($saleItem->variant_value) $batchQuery->where('variant_value', $saleItem->variant_value);
                            
                            $batch = $batchQuery->orderBy('received_date', 'desc')->orderBy('id', 'desc')->first();
                            
                            if ($batch) {
                                $batch->remaining_quantity += $restoreQty;
                                if ($batch->status === 'depleted') {
                                    $batch->status = 'active';
                                }
                                $batch->save();
                            }
                            
                            $stockQuery = \App\Models\ProductStock::where('product_id', $saleItem->product_id);
                            if ($saleItem->variant_id) {
                                $stockQuery->where('variant_id', $saleItem->variant_id);
                            } else {
                                $stockQuery->whereNull('variant_id');
                            }
                            if ($saleItem->variant_value) {
                                $stockQuery->where('variant_value', $saleItem->variant_value);
                            } else {
                                $stockQuery->where(function ($q) {
                                    $q->whereNull('variant_value')->orWhere('variant_value', '')->orWhere('variant_value', 'null');
                                });
                            }
                            $stockRecord = $stockQuery->first();
                            if (!$stockRecord) {
                                $stockRecord = \App\Models\ProductStock::where('product_id', $saleItem->product_id)->first();
                            }
                            
                            if ($stockRecord) {
                                $stockRecord->available_stock += $restoreQty;
                                $stockRecord->sold_count = max(0, (int) $stockRecord->sold_count - $restoreQty);
                                $stockRecord->updateTotals();
                            }
                        }
                    }

                    $newTotal = ($editItem['unit_price'] - ($editItem['discount'] ?? 0)) * $newQty;
                    $saleItem->update([
                        'quantity' => $newQty,
                        'discount_per_unit' => $editItem['discount'] ?? 0,
                        'total' => $newTotal,
                    ]);
                }
            }

            // Recalculate sale totals
            $subtotal = $this->editSubtotal;

            // Calculate actual rupee discount and store appropriately
            $discountRupees = 0;
            if ($this->editDiscountType === 'percentage') {
                // For percentage: store percentage value, calculate rupee amount for total
                $discountRupees = ($subtotal * $this->editDiscount) / 100;
                $discountToStore = $this->editDiscount; // Store percentage value
            } else {
                // For fixed: store rupee amount
                $discountRupees = min($this->editDiscount, $subtotal);
                $discountToStore = $discountRupees; // Store rupee amount
            }

            $total = $subtotal - $discountRupees;
            $newDueAmount = $total - ($this->editingSale->paid_amount ?? 0);

            // Update customer due_amount: subtract old due, add new due
            $customer = Customer::find($this->editingSale->customer_id);
            if ($customer) {
                $oldDueAmount = $this->editingSale->due_amount;
                // Only subtract old due if it was actually added (>0)
                if ($oldDueAmount > 0) {
                    $customer->due_amount = max(0, ($customer->due_amount ?? 0) - $oldDueAmount);
                }
                // Only add new due if it exists (>0)
                if ($newDueAmount > 0) {
                    $customer->due_amount = ($customer->due_amount ?? 0) + $newDueAmount;
                }
                $customer->total_due = ($customer->opening_balance ?? 0) + $customer->due_amount;
                $customer->save();
            }

            $this->editingSale->update([
                'subtotal' => $subtotal,
                'discount_amount' => $discountToStore,
                'discount_type' => $this->editDiscountType,
                'total_amount' => $total,
                'due_amount' => $newDueAmount,
                'notes' => $this->editNotes,
            ]);

            DB::commit();

            $this->closeEditModal();
            $this->showToast('success', 'Sale updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Sale edit error: ' . $e->getMessage());
            $this->showToast('error', 'Error updating sale.');
        }
    }

    // Return Methods
    public function openReturnModal($saleId)
    {
        $sale = Sale::with(['items.product', 'customer'])->find($saleId);

        if (!$sale) {
            $this->showToast('error', 'Sale not found.');
            return;
        }

        // Only allow returns on approved/confirmed sales
        if ($sale->status !== 'confirm') {
            $this->showToast('error', 'Returns can only be made on approved sales.');
            return;
        }

        $this->selectedSale = $sale;
        $this->returnNotes = '';

        // Prepare return items
        $this->returnItems = [];
        foreach ($sale->items as $item) {
            // Calculate already returned quantity
            $returnedQty = ReturnsProduct::where('sale_id', $saleId)
                ->where('product_id', $item->product_id)
                ->sum('return_quantity');

            $availableQty = $item->quantity - $returnedQty;

            $this->returnItems[] = [
                'item_id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'variant_id' => $item->variant_id ?? null,
                'variant_value' => $item->variant_value ?? null,
                'original_qty' => $item->quantity,
                'returned_qty' => $returnedQty,
                'available_qty' => $availableQty,
                'usable_qty' => 0,
                'damage_qty' => 0,
                'return_qty' => 0,
                'unit_price' => $item->unit_price,
            ];
        }

        $this->showReturnModal = true;
    }

    public function closeReturnModal()
    {
        $this->showReturnModal = false;
        $this->showFullReturnConfirmModal = false;
        $this->returnItems = [];
        $this->returnNotes = '';
    }

    public function updateUsableQty($index, $qty)
    {
        if (isset($this->returnItems[$index])) {
            $qty = max(0, (int)$qty);
            $damageQty = (int)$this->returnItems[$index]['damage_qty'];
            $maxAvailable = (int)$this->returnItems[$index]['available_qty'];
            if ($qty + $damageQty > $maxAvailable) {
                $qty = $maxAvailable - $damageQty;
            }
            $this->returnItems[$index]['usable_qty'] = $qty;
            $this->returnItems[$index]['return_qty'] = $qty + $damageQty;
        }
    }

    public function updateDamageQty($index, $qty)
    {
        if (isset($this->returnItems[$index])) {
            $qty = max(0, (int)$qty);
            $usableQty = (int)$this->returnItems[$index]['usable_qty'];
            $maxAvailable = (int)$this->returnItems[$index]['available_qty'];
            if ($qty + $usableQty > $maxAvailable) {
                $qty = $maxAvailable - $usableQty;
            }
            $this->returnItems[$index]['damage_qty'] = $qty;
            $this->returnItems[$index]['return_qty'] = $usableQty + $qty;
        }
    }

    public function getReturnTotalProperty()
    {
        $total = 0;
        foreach ($this->returnItems as $item) {
            $total += ($item['usable_qty'] + $item['damage_qty']) * $item['unit_price'];
        }
        return $total;
    }

    public function processReturn()
    {
        if (!$this->selectedSale) {
            return;
        }

        // Check if any items are being returned
        $hasReturns = false;
        foreach ($this->returnItems as $item) {
            $totalRtn = (int)$item['usable_qty'] + (int)$item['damage_qty'];
            if ($totalRtn > 0) {
                $hasReturns = true;
                break;
            }
        }

        if (!$hasReturns) {
            $this->showToast('error', 'Please select at least one item to return.');
            return;
        }

        try {
            DB::beginTransaction();

            $totalReturnAmount = 0;

            foreach ($this->returnItems as $item) {
                $usable = (int)$item['usable_qty'];
                $damage = (int)$item['damage_qty'];
                $totalRtn = $usable + $damage;

                if ($totalRtn > 0) {
                    $returnAmount = $totalRtn * $item['unit_price'];
                    $totalReturnAmount += $returnAmount;

                    // Build notes specifying usable and damage count
                    $notes = 'Usable: ' . $usable . ', Damaged: ' . $damage;
                    if ($this->returnNotes) {
                        $notes .= '. ' . $this->returnNotes;
                    }

                    // Create return record
                    ReturnsProduct::create([
                        'sale_id' => $this->selectedSale->id,
                        'product_id' => $item['product_id'],
                        'variant_id' => $item['variant_id'] ?? null,
                        'variant_value' => $item['variant_value'] ?? null,
                        'return_quantity' => $totalRtn,
                        'usable_quantity' => $usable,
                        'damaged_quantity' => $damage,
                        'selling_price' => $item['unit_price'],
                        'total_amount' => $returnAmount,
                        'notes' => $notes,
                        'return_type' => 'customer',
                        'user_id' => Auth::id(),
                    ]);

                    // Update stock (add back returned items)
                    $stock = null;
                    $productId = $item['product_id'];
                    $variantId = $item['variant_id'];
                    $variantValue = $item['variant_value'];

                    if ($variantId || $variantValue) {
                        $stockQuery = ProductStock::where('product_id', $productId);
                        if ($variantId) {
                            $stockQuery->where('variant_id', $variantId);
                        }
                        if ($variantValue) {
                            $stockQuery->where('variant_value', $variantValue);
                        }
                        $stock = $stockQuery->first();
                    } else {
                        $stock = ProductStock::where('product_id', $productId)
                            ->where(function ($q) {
                                $q->whereNull('variant_value')
                                    ->orWhere('variant_value', '')
                                    ->orWhere('variant_value', 'null');
                            })
                            ->whereNull('variant_id')
                            ->first();

                        if (!$stock) {
                            $stock = ProductStock::where('product_id', $productId)->first();
                        }
                    }

                    if ($stock) {
                        $stock->available_stock += $usable;
                        $stock->damage_stock += $damage;
                        if ($stock->sold_count >= $totalRtn) {
                            $stock->sold_count -= $totalRtn;
                        }
                        $stock->updateTotals();
                    }
                }
            }

            // ✅ Correct calculation: Recalculate with discount percentage
            // Step 1: Get current subtotal from all sale items
            $currentSubtotal = SaleItem::where('sale_id', $this->selectedSale->id)
                ->get()
                ->sum(function ($item) {
                    return ($item->unit_price * $item->quantity) - ($item->discount_per_unit * $item->quantity);
                });

            // Step 2: Subtract return amount from subtotal
            $newSubtotal = $currentSubtotal - $totalReturnAmount;

            // Step 3: Recalculate discount based on sale's additional discount settings
            $discountAmount = 0;
            if ($this->selectedSale->additional_discount_type === 'percentage' && $this->selectedSale->additional_discount_percentage > 0) {
                $discountAmount = ($newSubtotal * $this->selectedSale->additional_discount_percentage) / 100;
            } elseif ($this->selectedSale->additional_discount_type === 'fixed') {
                // For fixed discount, keep it as is (but don't exceed new subtotal)
                $discountAmount = min($this->selectedSale->discount_amount ?? 0, $newSubtotal);
            }

            // Step 4: Calculate new total
            $newTotal = $newSubtotal - $discountAmount;

            // Step 5: Update due amount proportionally
            $previousTotal = $this->selectedSale->total_amount;
            $totalReduction = $previousTotal - $newTotal;
            $newDue = max(0, $this->selectedSale->due_amount - $totalReduction);

            $this->selectedSale->update([
                'subtotal' => $newSubtotal,
                'discount_amount' => $discountAmount,
                'total_amount' => $newTotal,
                'due_amount' => $newDue,
            ]);

            // Adjust customer's due_amount
            $customer = Customer::find($this->selectedSale->customer_id);
            if ($customer && $totalReduction != 0) {
                $customer->due_amount = max(0, ($customer->due_amount ?? 0) - $totalReduction);
                $customer->total_due = ($customer->opening_balance ?? 0) + $customer->due_amount;
                $customer->save();
            }

            DB::commit();

            $this->closeReturnModal();
            $this->showToast('success', 'Return processed successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Return processing error: ' . $e->getMessage());
            $this->showToast('error', 'Error processing return.');
        }
    }

    public function processFullReturn()
    {
        if (!$this->selectedSale) {
            return;
        }

        try {
            DB::beginTransaction();

            $totalReturnAmount = 0;
            $hasItemsToReturn = false;

            foreach ($this->selectedSale->items as $item) {
                // Calculate already returned quantity
                $returnedQty = ReturnsProduct::where('sale_id', $this->selectedSale->id)
                    ->where('product_id', $item->product_id)
                    ->sum('return_quantity');

                $availableQty = $item->quantity - $returnedQty;

                if ($availableQty > 0) {
                    $hasItemsToReturn = true;
                    $returnAmount = $availableQty * $item->unit_price;
                    $totalReturnAmount += $returnAmount;

                    // Create return record
                    ReturnsProduct::create([
                        'sale_id' => $this->selectedSale->id,
                        'product_id' => $item->product_id,
                        'variant_id' => $item->variant_id ?? null,
                        'variant_value' => $item->variant_value ?? null,
                        'return_quantity' => $availableQty,
                        'usable_quantity' => $availableQty,
                        'damaged_quantity' => 0,
                        'selling_price' => $item->unit_price,
                        'total_amount' => $returnAmount,
                        'notes' => 'Full invoice return processed as usable quantity',
                        'return_type' => 'customer',
                        'user_id' => Auth::id(),
                    ]);

                    // Update stock (add back returned items as usable)
                    $stock = null;
                    $productId = $item->product_id;
                    $variantId = $item->variant_id;
                    $variantValue = $item->variant_value;

                    if ($variantId || $variantValue) {
                        $stockQuery = ProductStock::where('product_id', $productId);
                        if ($variantId) {
                            $stockQuery->where('variant_id', $variantId);
                        }
                        if ($variantValue) {
                            $stockQuery->where('variant_value', $variantValue);
                        }
                        $stock = $stockQuery->first();
                    } else {
                        $stock = ProductStock::where('product_id', $productId)
                            ->where(function ($q) {
                                $q->whereNull('variant_value')
                                    ->orWhere('variant_value', '')
                                    ->orWhere('variant_value', 'null');
                            })
                            ->whereNull('variant_id')
                            ->first();

                        if (!$stock) {
                            $stock = ProductStock::where('product_id', $productId)->first();
                        }
                    }

                    if ($stock) {
                        $stock->available_stock += $availableQty;
                        if ($stock->sold_count >= $availableQty) {
                            $stock->sold_count -= $availableQty;
                        }
                        $stock->updateTotals();
                    }
                }
            }

            if (!$hasItemsToReturn) {
                $this->showToast('error', 'All items in this invoice have already been returned.');
                DB::rollBack();
                return;
            }

            // Recalculate sale totals
            $currentSubtotal = SaleItem::where('sale_id', $this->selectedSale->id)
                ->get()
                ->sum(function ($item) {
                    return ($item->unit_price * $item->quantity) - ($item->discount_per_unit * $item->quantity);
                });

            $newSubtotal = $currentSubtotal - $totalReturnAmount;

            $discountAmount = 0;
            if ($this->selectedSale->additional_discount_type === 'percentage' && $this->selectedSale->additional_discount_percentage > 0) {
                $discountAmount = ($newSubtotal * $this->selectedSale->additional_discount_percentage) / 100;
            } elseif ($this->selectedSale->additional_discount_type === 'fixed') {
                $discountAmount = min($this->selectedSale->discount_amount ?? 0, $newSubtotal);
            }

            $newTotal = $newSubtotal - $discountAmount;

            $previousTotal = $this->selectedSale->total_amount;
            $totalReduction = $previousTotal - $newTotal;
            $newDue = max(0, $this->selectedSale->due_amount - $totalReduction);

            $this->selectedSale->update([
                'subtotal' => $newSubtotal,
                'discount_amount' => $discountAmount,
                'total_amount' => $newTotal,
                'due_amount' => $newDue,
            ]);

            // Adjust customer's due_amount
            $customer = Customer::find($this->selectedSale->customer_id);
            if ($customer && $totalReduction != 0) {
                $customer->due_amount = max(0, ($customer->due_amount ?? 0) - $totalReduction);
                $customer->total_due = ($customer->opening_balance ?? 0) + $customer->due_amount;
                $customer->save();
            }

            DB::commit();

            $this->closeReturnModal();
            $this->showToast('success', 'Full invoice return processed successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Full return processing error: ' . $e->getMessage());
            $this->showToast('error', 'Error processing full return.');
        }
    }

    public function render()
    {
        $baseQuery = Sale::where('user_id', Auth::id())
            ->when($this->selectedMonth, function ($q) {
                $q->whereYear('created_at', substr($this->selectedMonth, 0, 4))
                    ->whereMonth('created_at', substr($this->selectedMonth, 5, 2));
            });

        $query = Sale::where('user_id', Auth::id())
            ->when($this->selectedMonth, function ($q) {
                $q->whereYear('created_at', substr($this->selectedMonth, 0, 4))
                    ->whereMonth('created_at', substr($this->selectedMonth, 5, 2));
            })
            ->when($this->search, function ($q) {
                $q->where(function ($sq) {
                    $sq->where('sale_id', 'like', '%' . $this->search . '%')
                        ->orWhere('invoice_number', 'like', '%' . $this->search . '%')
                        ->orWhereHas('customer', function ($cq) {
                            $cq->where('name', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->statusFilter, function ($q) {
                $q->where('status', $this->statusFilter);
            })
            ->when($this->deliveryFilter, function ($q) {
                $q->where('delivery_status', $this->deliveryFilter);
            })
            ->with(['customer'])
            ->orderBy('created_at', 'desc');

        $statsQuery = Sale::where('user_id', Auth::id())
            ->when($this->selectedMonth, function ($q) {
                $q->whereYear('created_at', substr($this->selectedMonth, 0, 4))
                    ->whereMonth('created_at', substr($this->selectedMonth, 5, 2));
            })
            ->when($this->search, function ($q) {
                $q->where(function ($sq) {
                    $sq->where('sale_id', 'like', '%' . $this->search . '%')
                        ->orWhere('invoice_number', 'like', '%' . $this->search . '%')
                        ->orWhereHas('customer', function ($cq) {
                            $cq->where('name', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->statusFilter, function ($q) {
                $q->where('status', $this->statusFilter);
            })
            ->when($this->deliveryFilter, function ($q) {
                $q->where('delivery_status', $this->deliveryFilter);
            });

        $monthOptions = Sale::where('user_id', Auth::id())
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month_key")
            ->selectRaw("DATE_FORMAT(created_at, '%b %Y') as month_label")
            ->groupBy('month_key', 'month_label')
            ->orderByDesc('month_key')
            ->get();

        return view('livewire.salesman.salesman-sales-list', [
            'sales' => $query->paginate(10),
            'totalSaleAmount' => (float) (clone $statsQuery)->sum('total_amount'),
            'totalDueAmount' => (float) (clone $statsQuery)->sum('due_amount'),
            'monthOptions' => $monthOptions,
        ]);
    }

    /**
     * Show toast notification with custom styling
     * 
     * @param string $type - 'success', 'error', 'warning', 'info'
     * @param string $message - The message to display
     */
    private function showToast($type, $message)
    {
        $bgColors = [
            'success' => '#10b981',
            'error' => '#ef4444',
            'warning' => '#f59e0b',
            'info' => '#3b82f6',
        ];

        $icons = [
            'success' => '✓',
            'error' => '✕',
            'warning' => '⚠',
            'info' => 'ℹ',
        ];

        $bg = $bgColors[$type] ?? $bgColors['info'];
        $icon = $icons[$type] ?? $icons['info'];

        $escapedMessage = addslashes($message);

        $this->js("
            const toast = document.createElement('div');
            toast.style.cssText = 'position:fixed;top:20px;right:20px;background:{$bg};color:white;padding:16px 24px;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.15);z-index:9999;font-size:14px;font-weight:600;display:flex;align-items:center;gap:12px;animation:slideIn 0.3s ease;min-width:300px;max-width:500px;';
            toast.innerHTML = '<span style=\"font-size:20px;font-weight:bold;\">{$icon}</span><span>{$escapedMessage}</span>';
            document.body.appendChild(toast);
            
            const style = document.createElement('style');
            style.textContent = '@keyframes slideIn { from { transform: translateX(400px); opacity: 0; } to { transform: translateX(0); opacity: 1; } } @keyframes slideOut { from { transform: translateX(0); opacity: 1; } to { transform: translateX(400px); opacity: 0; } }';
            document.head.appendChild(style);
            
            setTimeout(() => {
                toast.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        ");
    }
}
