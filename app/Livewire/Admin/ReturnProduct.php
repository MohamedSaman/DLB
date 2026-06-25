<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Customer;
use App\Models\ProductDetail;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\ProductStock;
use App\Models\ReturnsProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Livewire\Concerns\WithDynamicLayout;

#[Title("Product Return")]
class ReturnProduct extends Component
{
    use WithDynamicLayout;

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

    /** 🔍 Search Customer or Invoice */
    public function updatedSearchCustomer()
    {
        if (strlen($this->searchCustomer) > 2) {
            $this->customers = Customer::query()
                ->where('name', 'like', '%' . $this->searchCustomer . '%')
                ->orWhere('phone', 'like', '%' . $this->searchCustomer . '%')
                ->orWhere('email', 'like', '%' . $this->searchCustomer . '%')
                ->limit(10)
                ->get();

            $this->customerInvoices = Sale::where('invoice_number', 'like', '%' . $this->searchCustomer . '%')
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

        $this->customerInvoices = Sale::where('customer_id', $this->selectedCustomer->id)
            ->latest()
            ->limit(5)
            ->get();
    }

    /** 🎯 Simple Invoice Selection for Return */
    public function selectInvoiceForReturn($invoiceId)
    {
        $this->resetReturnData();

        $this->selectedInvoice = Sale::with(['items.product', 'customer'])->find($invoiceId);
        $this->selectedInvoices = [$invoiceId];
        $this->showReturnSection = true;

        if ($this->selectedInvoice && $this->selectedInvoice->customer) {
            $this->selectedCustomer = $this->selectedInvoice->customer;
        }

        if ($this->selectedInvoice) {
            // Load previous returns for this invoice
            $this->loadPreviousReturns();

            // Build return items with remaining quantities
            foreach ($this->selectedInvoice->items as $item) {
                $alreadyReturned = $this->getAlreadyReturnedQuantity($item->product->id);
                $remainingQty = $item->quantity - $alreadyReturned;

                if ($remainingQty > 0) {
                    // Use the selling price directly from sale_items
                    // Discount will be recalculated after subtotal adjustment
                    $sellingPrice = $item->unit_price;

                    // Build display name with variant
                    $displayName = $item->product_name ?? $item->product->name;
                    if ($item->variant_value) {
                        $displayName .= ' (' . $item->variant_value . ')';
                    }

                    $this->returnItems[] = [
                        'product_id' => $item->product->id,
                        'product_code' => $item->product_code ?? $item->product->code,
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
            ->groupBy('product_id')
            ->map(function ($returns) {
                $firstReturn = $returns->first();
                $productName = $firstReturn->product->name ?? 'Unknown';
                
                // Add variant value if exists
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
    private function getAlreadyReturnedQuantity($productId)
    {
        if (!$this->selectedInvoice) return 0;

        return ReturnsProduct::where('sale_id', $this->selectedInvoice->id)
            ->where('product_id', $productId)
            ->sum('return_quantity');
    }

    /** 👁️ View Invoice Details in Modal */
    public function viewInvoice($invoiceId)
    {
        $invoice = Sale::with(['items.product', 'customer'])->find($invoiceId);

        if ($invoice) {
            $totalDiscountAmount = $invoice->discount_amount ?? 0;
            $totalQty = $invoice->items->sum('quantity');

            // Calculate total unit discounts
            $totalUnitDiscounts = $invoice->items->sum(function ($item) {
                return ($item->discount_per_unit ?? 0) * $item->quantity;
            });

            // Calculate remaining overall discount per item
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

                    // Build display name with variant
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
                    $alreadyReturned = $this->getAlreadyReturnedQuantity($item->product->id);
                    $remainingQty = $item->quantity - $alreadyReturned;

                    // Build display name with variant
                    $displayName = $item->product_name ?? $item->product->name;
                    if ($item->variant_value) {
                        $displayName .= ' (' . $item->variant_value . ')';
                    }

                    return [
                        'id' => $item->product->id,
                        'name' => $displayName,
                        'code' => $item->product_code ?? $item->product->code,
                        'image' => $item->product->image,
                        'selling_price' => $item->unit_price,
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'max_qty' => $remainingQty,
                    ];
                });
                $allProducts = $allProducts->merge($products);
            }
        }

        $this->invoiceProductsForSearch = $allProducts->unique('id')->values()->toArray();
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

                $notes = 'Customer return processed via system';
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

            // ✅ Correct calculation: Recalculate sale totals with discount percentage
            if ($this->selectedInvoice && $totalReturnAmount > 0) {
                // Step 1: Get current subtotal from all sale items
                $currentSubtotal = SaleItem::where('sale_id', $this->selectedInvoice->id)
                    ->get()
                    ->sum(function ($item) {
                        return ($item->unit_price * $item->quantity) - ($item->discount_per_unit * $item->quantity);
                    });

                // Step 2: Subtract return amount from subtotal
                $newSubtotal = $currentSubtotal - $totalReturnAmount;

                // Step 3: Recalculate discount based on sale's additional discount settings
                $discountAmount = 0;
                if ($this->selectedInvoice->additional_discount_type === 'percentage' && $this->selectedInvoice->additional_discount_percentage > 0) {
                    $discountAmount = ($newSubtotal * $this->selectedInvoice->additional_discount_percentage) / 100;
                } elseif ($this->selectedInvoice->additional_discount_type === 'fixed') {
                    // For fixed discount, keep it as is (but don't exceed new subtotal)
                    $discountAmount = min($this->selectedInvoice->discount_amount ?? 0, $newSubtotal);
                }

                // Step 4: Calculate new total
                $newTotal = $newSubtotal - $discountAmount;

                // Step 5: Update due amount proportionally
                $previousTotal = $this->selectedInvoice->total_amount;
                $totalReduction = $previousTotal - $newTotal;
                $newDue = max(0, $this->selectedInvoice->due_amount - $totalReduction);

                $this->selectedInvoice->update([
                    'subtotal' => $newSubtotal,
                    'discount_amount' => $discountAmount,
                    'total_amount' => $newTotal,
                    'due_amount' => $newDue,
                ]);

                // Reduce customer's due amount by the same reduction amount
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

    /** 📈 Update Product Stock */
    private function updateProductStock($productId, $usableQty, $damageQty, $variantId = null, $variantValue = null)
    {
        $stock = null;

        if ($variantId || $variantValue) {
            // Find the specific variant stock record
            $stockQuery = ProductStock::where('product_id', $productId);
            if ($variantId) {
                $stockQuery->where('variant_id', $variantId);
            }
            if ($variantValue) {
                $stockQuery->where('variant_value', $variantValue);
            }
            $stock = $stockQuery->first();
        } else {
            // Non-variant product: find stock with no variant
            $stock = ProductStock::where('product_id', $productId)
                ->where(function ($q) {
                    $q->whereNull('variant_value')
                        ->orWhere('variant_value', '')
                        ->orWhere('variant_value', 'null');
                })
                ->whereNull('variant_id')
                ->first();

            // Fallback: just by product_id if single-stock product
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
    }

    public function render()
    {
        return view('livewire.admin.return-product')->layout($this->layout);
    }
}
