<?php

namespace App\Livewire\DeliveryMan;

use Livewire\Component;
use App\Models\Customer;
use App\Models\ProductDetail;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\ProductStock;
use App\Models\ReturnsProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Title("Product Return")]
#[Layout('components.layouts.delivery-man')]
class DeliveryManReturnProduct extends Component
{
    public $searchCustomer = '';
    public $customers = [];
    public $selectedCustomer = null;

    public $customerInvoices = [];
    public $selectedInvoice = null;
    public $selectedInvoices = [];

    public $invoiceProducts = [];
    public $returnItems = [];
    public $totalReturnValue = 0;

    public $showInvoiceModal = false;
    public $invoiceModalData = null;

    public $showReturnSection = false;
    public $searchReturnProduct = '';
    public $availableProducts = [];
    public $invoiceProductsForSearch = [];
    public $selectedProducts = [];

    public $previousReturns = []; // Track previously returned items
    public $showFullReturnConfirmModal = false;

    /** 🔍 Search Customer or Invoice */
    public function updatedSearchCustomer()
    {
        $userId = Auth::id();
        if (strlen($this->searchCustomer) > 2) {
            $this->customers = Customer::query()
                ->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->searchCustomer . '%')
                      ->orWhere('phone', 'like', '%' . $this->searchCustomer . '%')
                      ->orWhere('email', 'like', '%' . $this->searchCustomer . '%');
                })
                ->whereHas('sales', function ($q) use ($userId) {
                    $q->where('status', 'confirm')
                      ->where('delivery_status', 'pending')
                      ->where(function ($sq) use ($userId) {
                          $sq->whereNull('delivered_by')
                            ->orWhere('delivered_by', $userId);
                      });
                })
                ->limit(10)
                ->get();

            $this->customerInvoices = Sale::where('invoice_number', 'like', '%' . $this->searchCustomer . '%')
                ->where('status', 'confirm')
                ->where('delivery_status', 'pending')
                ->where(function ($sq) use ($userId) {
                    $sq->whereNull('delivered_by')
                      ->orWhere('delivered_by', $userId);
                })
                ->latest()
                ->limit(5)
                ->get();
        } else {
            $this->customers = [];
            $this->customerInvoices = [];
        }
    }

    /** 👤 Select Customer */
    public function selectCustomer($customerId)
    {
        $this->selectedCustomer = Customer::find($customerId);
        $this->searchCustomer = '';
        $this->customers = [];

        $this->resetReturnData();
        $this->loadCustomerInvoices();
    }

    /** 🧾 Load Selected Customer's Invoices */
    public function loadCustomerInvoices()
    {
        if (!$this->selectedCustomer) {
            $this->customerInvoices = [];
            return;
        }

        $userId = Auth::id();
        $this->customerInvoices = Sale::where('customer_id', $this->selectedCustomer->id)
            ->where('status', 'confirm')
            ->where('delivery_status', 'pending')
            ->where(function ($sq) use ($userId) {
                $sq->whereNull('delivered_by')
                  ->orWhere('delivered_by', $userId);
            })
            ->latest()
            ->limit(5)
            ->get();
    }

    /** 🎯 Simple Invoice Selection for Return */
    public function selectInvoiceForReturn($invoiceId)
    {
        $this->resetReturnData();

        $userId = Auth::id();
        $this->selectedInvoice = Sale::with(['items.product', 'customer'])
            ->where('status', 'confirm')
            ->where('delivery_status', 'pending')
            ->where(function ($sq) use ($userId) {
                $sq->whereNull('delivered_by')
                  ->orWhere('delivered_by', $userId);
            })
            ->find($invoiceId);

        if (!$this->selectedInvoice) {
            $this->js("Swal.fire('Error!', 'Selected invoice is not available or not pending for delivery.', 'error')");
            return;
        }

        $this->selectedInvoices = [$invoiceId];
        $this->showReturnSection = true;

        if ($this->selectedInvoice->customer) {
            $this->selectedCustomer = $this->selectedInvoice->customer;
        }

        // Load previous returns for this invoice
        $this->loadPreviousReturns();

        // Build return items with remaining quantities
        foreach ($this->selectedInvoice->items as $item) {
            $alreadyReturned = $this->getAlreadyReturnedQuantity($item->product_id, $item->variant_id, $item->variant_value);
            $remainingQty = $item->quantity - $alreadyReturned;

            if ($remainingQty > 0) {
                $sellingPrice = $item->unit_price;

                // Build display name with variant
                $displayName = $item->product_name ?? $item->product->name;
                if ($item->variant_value) {
                    $displayName .= ' (' . $item->variant_value . ')';
                }

                $this->returnItems[] = [
                    'product_id' => $item->product_id,
                    'product_code' => $item->product_code ?? ($item->product?->code),
                    'name' => $displayName,
                    'product_name' => $item->product_name ?? $item->product->name,
                    'selling_price' => $sellingPrice,
                    'original_qty' => $item->quantity,
                    'already_returned' => $alreadyReturned,
                    'max_qty' => $remainingQty,
                    'usable_qty' => 0,
                    'damage_qty' => 0,
                    'variant_id' => $item->variant_id ?? null,
                    'variant_value' => $item->variant_value ?? null,
                ];
            }
        }

        $this->loadInvoiceProductsForSearch();
        $this->searchCustomer = '';
    }

    /**  Load Previous Returns */
    private function loadPreviousReturns()
    {
        if (!$this->selectedInvoice) {
            $this->previousReturns = [];
            return;
        }

        $this->previousReturns = ReturnsProduct::where('sale_id', $this->selectedInvoice->id)
            ->with('product')
            ->get()
            ->groupBy(function ($item) {
                return $item->product_id . '_' . ($item->variant_id ?? '') . '_' . ($item->variant_value ?? '');
            })
            ->map(function ($returns) {
                $firstReturn = $returns->first();
                $productName = $firstReturn->product->name ?? 'Unknown';
                
                if ($firstReturn->variant_value) {
                    $productName .= ' (' . $firstReturn->variant_value . ')';
                }
                
                return [
                    'product_name' => $productName,
                    'total_returned' => $returns->sum('return_quantity'),
                    'total_amount' => $returns->sum('total_amount'),
                    'returns' => $returns->map(function ($return) {
                        return [
                            'quantity' => $return->return_quantity,
                            'amount' => $return->total_amount,
                            'date' => $return->created_at->format('Y-m-d H:i'),
                        ];
                    })->toArray()
                ];
            })
            ->toArray();
    }

    /** 🔢 Get Already Returned Quantity */
    private function getAlreadyReturnedQuantity($productId, $variantId = null, $variantValue = null)
    {
        if (!$this->selectedInvoice) return 0;

        $query = ReturnsProduct::where('sale_id', $this->selectedInvoice->id)
            ->where('product_id', $productId);

        if ($variantId) {
            $query->where('variant_id', $variantId);
        } else {
            $query->whereNull('variant_id');
        }

        if ($variantValue) {
            $query->where('variant_value', $variantValue);
        } else {
            $query->where(function ($q) {
                $q->whereNull('variant_value')
                  ->orWhere('variant_value', '')
                  ->orWhere('variant_value', 'null');
            });
        }

        return $query->sum('return_quantity');
    }

    /** 👁️ View Invoice Details in Modal */
    public function viewInvoice($invoiceId)
    {
        $invoice = Sale::with(['items.product', 'customer'])->find($invoiceId);

        if ($invoice) {
            $totalDiscountAmount = $invoice->discount_amount ?? 0;
            $totalQty = $invoice->items->sum('quantity');

            $totalUnitDiscounts = $invoice->items->sum(function ($item) {
                return ($item->discount_per_unit ?? 0) * $item->quantity;
            });

            $remainingOverallDiscount = $totalDiscountAmount - $totalUnitDiscounts;
            $overallDiscountPerItem = $totalQty > 0 ? ($remainingOverallDiscount / $totalQty) : 0;

            $this->invoiceModalData = [
                'invoice_number' => $invoice->invoice_number,
                'customer_name' => $invoice->customer->name,
                'date' => $invoice->created_at->format('Y-m-d H:i:s'),
                'total_amount' => $invoice->total_amount,
                'overall_discount' => $totalDiscountAmount,
                'items' => $invoice->items->map(function ($item) use ($overallDiscountPerItem) {
                    $itemDiscount = $item->discount_per_unit ?? 0;
                    $totalDiscountPerUnit = $itemDiscount + $overallDiscountPerItem;
                    $netPrice = $item->unit_price - $totalDiscountPerUnit;

                    $displayName = $item->product_name ?? $item->product->name;
                    if ($item->variant_value) {
                        $displayName .= ' (' . $item->variant_value . ')';
                    }

                    return [
                        'product_name' => $displayName,
                        'product_code' => $item->product_code ?? $item->product->code,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'item_discount' => $itemDiscount,
                        'overall_discount' => $overallDiscountPerItem,
                        'net_price' => $netPrice,
                        'total' => $item->quantity * $netPrice,
                    ];
                })->toArray()
            ];
            $this->showInvoiceModal = true;
            $this->dispatch('show-invoice-modal');
        }
    }

    /** ❌ Close Invoice Modal */
    public function closeInvoiceModal()
    {
        $this->showInvoiceModal = false;
        $this->invoiceModalData = null;
    }

    /** 📦 Load Products from Selected Invoice for Search */
    private function loadInvoiceProductsForSearch()
    {
        if (empty($this->selectedInvoices)) {
            $this->invoiceProductsForSearch = [];
            return;
        }

        $allProducts = collect();

        foreach ($this->selectedInvoices as $invoiceId) {
            $invoice = Sale::with(['items.product.price'])->find($invoiceId);
            if ($invoice) {
                $products = $invoice->items->map(function ($item) use ($invoice) {
                    $alreadyReturned = $this->getAlreadyReturnedQuantity($item->product_id, $item->variant_id, $item->variant_value);
                    $remainingQty = $item->quantity - $alreadyReturned;

                    $displayName = $item->product_name ?? $item->product->name;
                    if ($item->variant_value) {
                        $displayName .= ' (' . $item->variant_value . ')';
                    }

                    return [
                        'id' => $item->product_id,
                        'variant_id' => $item->variant_id,
                        'variant_value' => $item->variant_value,
                        'name' => $displayName,
                        'code' => $item->product_code ?? ($item->product?->code),
                        'image' => $item->product?->image,
                        'selling_price' => $item->unit_price,
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'max_qty' => $remainingQty,
                    ];
                });
                $allProducts = $allProducts->merge($products);
            }
        }

        $this->invoiceProductsForSearch = $allProducts->unique(function ($p) {
            return $p['id'] . '_' . ($p['variant_id'] ?? '') . '_' . ($p['variant_value'] ?? '');
        })->values()->toArray();
    }

    /** ❌ Remove Product from Return Cart */
    public function removeFromReturn($index)
    {
        unset($this->returnItems[$index]);
        $this->returnItems = array_values($this->returnItems);
        $this->calculateTotalReturnValue();
    }

    /** 🧹 Clear Cart */
    public function clearReturnCart()
    {
        $this->returnItems = [];
        $this->totalReturnValue = 0;
    }

    /** ♻️ Auto-update total when quantities change */
    public function updatedReturnItems()
    {
        $this->calculateTotalReturnValue();
    }

    /** 💰 Calculate Total Return Value */
    private function calculateTotalReturnValue()
    {
        $this->totalReturnValue = collect($this->returnItems)->sum(
            fn($item) => ((int)($item['usable_qty'] ?? 0) + (int)($item['damage_qty'] ?? 0)) * $item['selling_price']
        );
    }

    /** ✅ Validate before showing confirmation */
    public function processReturn()
    {
        $this->calculateTotalReturnValue();

        if (empty($this->returnItems) || !$this->selectedInvoice) {
            $this->js("Swal.fire('Error!', 'Please select items for return.', 'error')");
            return;
        }

        $hasReturnItems = false;
        foreach ($this->returnItems as $item) {
            $usable = (int)($item['usable_qty'] ?? 0);
            $damage = (int)($item['damage_qty'] ?? 0);
            $totalReturn = $usable + $damage;

            if ($usable < 0 || $damage < 0) {
                $this->js("Swal.fire('Error!', 'Return quantity cannot be negative for " . $item['name'] . "', 'error')");
                return;
            }

            if ($totalReturn > 0) {
                if ($totalReturn > $item['max_qty']) {
                    $this->js("Swal.fire('Error!', 'Invalid return quantity for " . $item['name'] . ". Maximum available: " . $item['max_qty'] . "', 'error')");
                    return;
                }
                $hasReturnItems = true;
            }
        }

        if (!$hasReturnItems) {
            $this->dispatch('alert', ['message' => 'Please enter at least one return quantity.']);
            return;
        }

        $this->dispatch('show-return-modal');
    }

    /** 💾 Confirm Return & Save to Database */
    public function confirmReturn()
    {
        $this->calculateTotalReturnValue();

        if (empty($this->returnItems) || !$this->selectedCustomer || !$this->selectedInvoice) return;

        $itemsToReturn = array_filter($this->returnItems, function ($item) {
            return ((int)($item['usable_qty'] ?? 0) + (int)($item['damage_qty'] ?? 0)) > 0;
        });

        if (empty($itemsToReturn)) {
            $this->dispatch('alert', ['message' => 'No valid return quantities entered.']);
            return;
        }

        DB::transaction(function () use ($itemsToReturn) {
            $totalReturnAmount = 0;

            foreach ($itemsToReturn as $item) {
                $usable = (int)($item['usable_qty'] ?? 0);
                $damage = (int)($item['damage_qty'] ?? 0);
                $totalReturn = $usable + $damage;
                $returnAmount = $totalReturn * $item['selling_price'];
                $totalReturnAmount += $returnAmount;

                $notes = 'Customer return processed by delivery man';
                if ($usable > 0 && $damage > 0) {
                    $notes = "Usable: {$usable}, Damaged: {$damage}. " . $notes;
                } elseif ($usable > 0) {
                    $notes = "Usable: {$usable}. " . $notes;
                } elseif ($damage > 0) {
                    $notes = "Damaged: {$damage}. " . $notes;
                }

                ReturnsProduct::create([
                    'sale_id' => $this->selectedInvoice->id,
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'] ?? null,
                    'variant_value' => $item['variant_value'] ?? null,
                    'return_quantity' => $totalReturn,
                    'usable_quantity' => $usable,
                    'damaged_quantity' => $damage,
                    'selling_price' => $item['selling_price'],
                    'total_amount' => $returnAmount,
                    'notes' => $notes,
                ]);

                $this->updateProductStock(
                    $item['product_id'],
                    $usable,
                    $damage,
                    $item['variant_id'] ?? null,
                    $item['variant_value'] ?? null
                );
            }

            if ($this->selectedInvoice && $totalReturnAmount > 0) {
                $currentSubtotal = SaleItem::where('sale_id', $this->selectedInvoice->id)
                    ->get()
                    ->sum(function ($item) {
                        return ($item->unit_price * $item->quantity) - ($item->discount_per_unit * $item->quantity);
                    });

                $newSubtotal = $currentSubtotal - $totalReturnAmount;

                $discountAmount = 0;
                if ($this->selectedInvoice->additional_discount_type === 'percentage' && $this->selectedInvoice->additional_discount_percentage > 0) {
                    $discountAmount = ($newSubtotal * $this->selectedInvoice->additional_discount_percentage) / 100;
                } elseif ($this->selectedInvoice->additional_discount_type === 'fixed') {
                    $discountAmount = min($this->selectedInvoice->discount_amount ?? 0, $newSubtotal);
                }

                $newTotal = $newSubtotal - $discountAmount;

                $previousTotal = $this->selectedInvoice->total_amount;
                $totalReduction = $previousTotal - $newTotal;
                $newDue = max(0, $this->selectedInvoice->due_amount - $totalReduction);

                // Check if invoice is fully returned (subtotal == 0)
                $isFullyReturned = $newSubtotal <= 0;

                $this->selectedInvoice->update([
                    'subtotal' => $newSubtotal,
                    'discount_amount' => $discountAmount,
                    'total_amount' => $newTotal,
                    'due_amount' => $newDue,
                    // If fully returned, we cancel the delivery status
                    'delivery_status' => $isFullyReturned ? 'cancelled' : $this->selectedInvoice->delivery_status,
                    'delivered_by' => Auth::id(), // Assign to current delivery man
                ]);

                if ($this->selectedCustomer && $totalReduction > 0) {
                    $customer = Customer::find($this->selectedCustomer->id);
                    if ($customer) {
                        $customer->due_amount = max(0, ($customer->due_amount ?? 0) - $totalReduction);
                        $customer->total_due = ($customer->opening_balance ?? 0) + $customer->due_amount;
                        $customer->save();
                    }
                }
            }
        });

        $this->clearReturnCart();
        $this->dispatch('alert', ['message' => 'Return processed successfully!']);
        $this->dispatch('close-return-modal');
        $this->dispatch('reload-page');
    }

    public function processFullReturn()
    {
        if (!$this->selectedInvoice) {
            $this->showFullReturnConfirmModal = false;
            return;
        }

        try {
            DB::transaction(function () {
                $totalReturnAmount = 0;
                $hasItemsToReturn = false;

                foreach ($this->selectedInvoice->items as $item) {
                    $alreadyReturned = $this->getAlreadyReturnedQuantity($item->product_id, $item->variant_id, $item->variant_value);
                    $remainingQty = $item->quantity - $alreadyReturned;

                    if ($remainingQty > 0) {
                        $hasItemsToReturn = true;
                        $returnAmount = $remainingQty * $item->unit_price;
                        $totalReturnAmount += $returnAmount;

                        $notes = "Full invoice return by delivery man - Usable: {$remainingQty}";

                        ReturnsProduct::create([
                            'sale_id' => $this->selectedInvoice->id,
                            'product_id' => $item->product_id,
                            'variant_id' => $item->variant_id ?? null,
                            'variant_value' => $item->variant_value ?? null,
                            'return_quantity' => $remainingQty,
                            'usable_quantity' => $remainingQty,
                            'damaged_quantity' => 0,
                            'selling_price' => $item->unit_price,
                            'total_amount' => $returnAmount,
                            'notes' => $notes,
                        ]);

                        $this->updateProductStock(
                            $item->product_id,
                            $remainingQty,
                            0,
                            $item->variant_id ?? null,
                            $item->variant_value ?? null
                        );
                    }
                }

                if (!$hasItemsToReturn) {
                    throw new \Exception('All items in this invoice have already been returned.');
                }

                if ($totalReturnAmount > 0) {
                    $currentSubtotal = SaleItem::where('sale_id', $this->selectedInvoice->id)
                        ->get()
                        ->sum(function ($item) {
                            return ($item->unit_price * $item->quantity) - ($item->discount_per_unit * $item->quantity);
                        });

                    $newSubtotal = $currentSubtotal - $totalReturnAmount;

                    $discountAmount = 0;
                    if ($this->selectedInvoice->additional_discount_type === 'percentage' && $this->selectedInvoice->additional_discount_percentage > 0) {
                        $discountAmount = ($newSubtotal * $this->selectedInvoice->additional_discount_percentage) / 100;
                    } elseif ($this->selectedInvoice->additional_discount_type === 'fixed') {
                        $discountAmount = min($this->selectedInvoice->discount_amount ?? 0, $newSubtotal);
                    }

                    $newTotal = $newSubtotal - $discountAmount;

                    $previousTotal = $this->selectedInvoice->total_amount;
                    $totalReduction = $previousTotal - $newTotal;
                    $newDue = max(0, $this->selectedInvoice->due_amount - $totalReduction);

                    $this->selectedInvoice->update([
                        'subtotal' => 0,
                        'discount_amount' => 0,
                        'total_amount' => 0,
                        'due_amount' => 0,
                        'delivery_status' => 'cancelled', // fully returned/cancelled
                        'delivered_by' => Auth::id(), // Assign to current delivery man
                    ]);

                    if ($this->selectedCustomer && $totalReduction > 0) {
                        $customer = Customer::find($this->selectedCustomer->id);
                        if ($customer) {
                            $customer->due_amount = max(0, ($customer->due_amount ?? 0) - $totalReduction);
                            $customer->total_due = ($customer->opening_balance ?? 0) + $customer->due_amount;
                            $customer->save();
                        }
                    }
                }
            });

            $this->showFullReturnConfirmModal = false;
            $this->clearReturnCart();
            $this->dispatch('alert', ['message' => 'Full invoice return processed successfully!']);
            $this->dispatch('reload-page');
        } catch (\Exception $e) {
            $this->showFullReturnConfirmModal = false;
            $this->js("Swal.fire('Error!', '" . $e->getMessage() . "', 'error')");
        }
    }

    /** 📈 Update Product Stock */
    private function updateProductStock($productId, $usableQty, $damageQty, $variantId = null, $variantValue = null)
    {
        $stock = null;

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

        $totalQty = $usableQty + $damageQty;

        if ($stock) {
            $stock->available_stock += $usableQty;
            $stock->damage_stock += $damageQty;
            if ($stock->sold_count >= $totalQty) {
                $stock->sold_count -= $totalQty;
            }
            $stock->updateTotals();
        } else {
            ProductStock::create([
                'product_id' => $productId,
                'available_stock' => $usableQty,
                'damage_stock' => $damageQty,
                'total_stock' => $totalQty,
                'sold_count' => 0,
                'restocked_quantity' => 0,
                'variant_id' => $variantId,
                'variant_value' => $variantValue,
            ]);
        }
    }

    /** 🔄 Reset Return Data */
    private function resetReturnData()
    {
        $this->selectedInvoice = null;
        $this->selectedInvoices = [];
        $this->invoiceProducts = [];
        $this->returnItems = [];
        $this->selectedProducts = [];
        $this->showReturnSection = false;
        $this->searchReturnProduct = '';
        $this->availableProducts = [];
        $this->invoiceProductsForSearch = [];
        $this->totalReturnValue = 0;
        $this->previousReturns = [];
        $this->showFullReturnConfirmModal = false;
    }

    public function render()
    {
        return view('livewire.delivery-man.delivery-man-return-product');
    }
}
