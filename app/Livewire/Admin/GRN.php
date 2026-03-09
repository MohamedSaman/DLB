<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\ProductDetail;
use App\Models\ProductStock;
use App\Models\ProductBatch;
use App\Models\ProductPrice;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Livewire\Concerns\WithDynamicLayout;
use Livewire\WithPagination;

#[Title("Goods Receive Note")]
class GRN extends Component
{
    use WithDynamicLayout, WithPagination;


    public $selectedPO = null;
    public $grnItems = [];
    public $searchProduct = '';
    public $searchResults = [];
    public $search = '';
    public $newItem = ['product_id' => null, 'name' => '', 'qty' => 1, 'unit_price' => 0, 'discount' => 0, 'status' => 'received'];

    protected $listeners = ['deleteGRNItem'];
    public $perPage = 10;

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function mount()
    {

        $this->searchResults = ['unplanned' => []];
    }

    // public function loadPurchaseOrders()
    // {
    //     // Show both complete and received orders in the table
    //     $this->purchaseOrders = PurchaseOrder::whereIn('status', ['complete', 'received'])
    //         ->with(['supplier', 'items.product'])
    //         ->latest()
    //         ->paginate(10);
    // }

    // Add this method to get counts for both statuses
    public function getOrderCounts()
    {
        return [
            'complete' => PurchaseOrder::where('status', 'complete')->count(),
            'received' => PurchaseOrder::where('status', 'received')->count(),
            'total' => PurchaseOrder::whereIn('status', ['complete', 'received'])->count()
        ];
    }
    public function viewGRN($orderId)
    {
        $this->selectedPO = PurchaseOrder::with(['supplier', 'items' => function ($query) {
            $query->with(['product', 'variant'])->where('status', 'received');
        }])->find($orderId);

        if (!$this->selectedPO) {
            $this->dispatch('alert', ['message' => 'Order not found!', 'type' => 'error']);
            return;
        }

        $this->grnItems = [];
        $this->searchResults = ['unplanned' => []];

        foreach ($this->selectedPO->items as $item) {
            $productName = $item->product->name;
            if ($item->variant_id && $item->variant_value) {
                $variantName = $item->variant ? $item->variant->variant_name : 'Variant';
                $productName .= ' - ' . $variantName . ': ' . $item->variant_value;
            }

            $this->grnItems[] = [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'variant_id' => $item->variant_id,
                'variant_value' => $item->variant_value,
                'name' => $productName,
                'ordered_qty' => $item->quantity,
                'received_qty' => $item->received_quantity ?? $item->quantity,
                'unit_price' => $item->unit_price,
                'discount' => $item->discount,
                'discount_type' => $item->discount_type ?? 'rs',
                'status' => $item->status ?? 'received',
            ];
        }

        // Dispatch event to open modal after data is loaded
        $this->dispatch('open-view-grn-modal');
    }

    public function openGRN($orderId)
    {
        $this->selectedPO = PurchaseOrder::with(['supplier', 'items.product', 'items.variant'])->find($orderId);

        if (!$this->selectedPO) {
            $this->dispatch('alert', ['message' => 'Order not found!', 'type' => 'error']);
            return;
        }

        $this->grnItems = [];
        $this->searchResults = ['unplanned' => []];

        foreach ($this->selectedPO->items as $item) {
            // Get current product price for selling price reference
            $product = ProductDetail::with('price')->find($item->product_id);
            $currentSellingPrice = $product && $product->price ? $product->price->selling_price : 0;

            $productName = $item->product->name;
            if ($item->variant_id && $item->variant_value) {
                $variantName = $item->variant ? $item->variant->variant_name : 'Variant';
                $productName .= ' - ' . $variantName . ': ' . $item->variant_value;
            }

            $this->grnItems[] = [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'variant_id' => $item->variant_id,
                'variant_value' => $item->variant_value,
                'name' => $productName,
                'ordered_qty' => $item->quantity,
                'received_qty' => $item->quantity,
                'unit_price' => $item->unit_price,
                'discount' => $item->discount,
                'discount_type' => $item->discount_type ?? 'rs',
                'selling_price' => $currentSellingPrice, // Add selling price
                'status' => $item->status,
            ];
        }

        // Dispatch event to open modal after data is loaded
        $this->dispatch('open-grn-modal');
    }

    public function updated($propertyName)
    {
        if (preg_match('/grnItems\.(\d+)\.name/', $propertyName, $matches)) {
            $index = $matches[1];
            $searchTerm = $this->grnItems[$index]['name'];
            if (strlen($searchTerm) > 1) {
                $this->searchResults[$index] = ProductDetail::where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('code', 'like', "%{$searchTerm}%")
                    ->with(['price', 'stock'])
                    ->limit(5)
                    ->get();
            } else {
                $this->searchResults[$index] = [];
            }
        } elseif ($propertyName === 'searchProduct') {
            if (strlen($this->searchProduct) > 1) {
                $this->searchResults['unplanned'] = ProductDetail::where('name', 'like', "%{$this->searchProduct}%")
                    ->orWhere('code', 'like', "%{$this->searchProduct}%")
                    ->with(['price', 'stock'])
                    ->limit(5)
                    ->get();
            } else {
                $this->searchResults['unplanned'] = [];
            }
        }
    }

    public function selectProduct($index, $productId)
    {
        if (!is_numeric($productId)) return;

        $product = ProductDetail::with('price')->find($productId);
        if ($product) {
            $unitPrice = $product->price ? $product->price->supplier_price : 0;
            if ($index === -1) {
                $this->newItem['product_id'] = $product->id;
                $this->newItem['name'] = $product->name;
                $this->newItem['unit_price'] = $unitPrice;
                $this->newItem['status'] = 'received';
                $this->searchProduct = $product->name;
                $this->searchResults['unplanned'] = [];
            } else {
                $this->grnItems[$index]['product_id'] = $product->id;
                $this->grnItems[$index]['name'] = $product->name;
                $this->grnItems[$index]['unit_price'] = $unitPrice;
                $this->grnItems[$index]['status'] = 'received';
                $this->searchResults[$index] = [];
            }
        }
    }

    public function addUnplannedItem()
    {
        // Validate input fields
        if (!$this->newItem['name']) {
            $this->dispatch('alert', ['message' => 'Product name is required!', 'type' => 'error']);
            return;
        }

        $qty = (int) $this->newItem['qty'];
        $unitPrice = (float) $this->newItem['unit_price'];
        $discount = (float) $this->newItem['discount'];

        if ($qty < 1) {
            $this->dispatch('alert', ['message' => 'Quantity must be at least 1!', 'type' => 'error']);
            return;
        }

        if ($unitPrice < 0) {
            $this->dispatch('alert', ['message' => 'Unit price cannot be negative!', 'type' => 'error']);
            return;
        }

        if ($discount < 0) {
            $this->dispatch('alert', ['message' => 'Discount cannot be negative!', 'type' => 'error']);
            return;
        }

        $this->grnItems[] = [
            'product_id' => $this->newItem['product_id'],
            'name' => $this->newItem['name'],
            'ordered_qty' => 0,
            'received_qty' => $qty,
            'unit_price' => $unitPrice,
            'discount' => $discount,
            'status' => 'received',
        ];

        $this->newItem = ['product_id' => null, 'name' => '', 'qty' => 1, 'unit_price' => 0, 'discount' => 0, 'status' => 'received'];
        $this->searchProduct = '';
        $this->searchResults['unplanned'] = [];
    }

    public function addNewRow()
    {
        $this->grnItems[] = [
            'product_id' => null,
            'name' => '',
            'ordered_qty' => 0,
            'received_qty' => 1,
            'unit_price' => 0,
            'discount' => 0,
            'status' => 'received',
        ];

        // Initialize search results for the new row
        $newIndex = count($this->grnItems) - 1;
        $this->searchResults[$newIndex] = [];
    }

    public function deleteGRNItem($index)
    {
        if (isset($this->grnItems[$index]['id'])) {
            $orderItem = PurchaseOrderItem::find($this->grnItems[$index]['id']);
            if ($orderItem) {
                $orderItem->status = 'notreceived';
                $orderItem->save();
            }
        }
        $this->grnItems[$index]['status'] = 'notreceived';
        $this->searchResults[$index] = [];
    }

    public function correctGRNItem($index)
    {
        $item = $this->grnItems[$index];
        $productId = $item['product_id'];
        $receivedQty = (int) ($item['received_qty'] ?? 0);

        // Mark the item as received in the UI
        $this->grnItems[$index]['status'] = 'received';

        // Update stock immediately if we have a valid product and quantity
        if ($productId && $receivedQty > 0) {
            // Calculate prices - Cast to proper types
            $unitPrice = (float) ($item['unit_price'] ?? 0);
            $discount = (float) ($item['discount'] ?? 0);
            $discountType = $item['discount_type'] ?? 'rs';

            $supplierPrice = $unitPrice;
            if ($discountType === 'percent') {
                $supplierPrice = $unitPrice - ($unitPrice * $discount / 100);
            } else {
                $supplierPrice = $unitPrice - $discount;
            }
            $supplierPrice = max(0, $supplierPrice);

            // Get selling price
            $product = ProductDetail::with('price')->find($productId);
            $sellingPrice = $supplierPrice;
            if ($product && $product->price) {
                $currentSupplierPrice = (float) $product->price->supplier_price;
                $currentSellingPrice = (float) $product->price->selling_price;
                if ($currentSupplierPrice > 0) {
                    $ratio = $currentSellingPrice / $currentSupplierPrice;
                    $sellingPrice = $supplierPrice * $ratio;
                } else {
                    $sellingPrice = $currentSellingPrice;
                }
            }

            $wholesalePrice = floatval($item['wholesale_price'] ?? 0);
            $retailPrice = floatval($item['retail_price'] ?? 0);
            $distributorPrice = floatval($item['distributor_price'] ?? 0);

            $this->updateProductStock($productId, $receivedQty, $supplierPrice, $sellingPrice, $this->selectedPO ? $this->selectedPO->id : null, $wholesalePrice, $retailPrice, $distributorPrice, null, null);
        }
    }

    public function saveGRN()
    {
        if (!$this->selectedPO || empty($this->grnItems)) return;

        $receivedItemsCount = 0;
        $totalItemsCount = 0;

        foreach ($this->grnItems as $item) {
            // Skip items that are marked as not received
            if (strtolower($item['status'] ?? '') === 'notreceived') {
                $totalItemsCount++;
                continue;
            }

            $productId = $item['product_id'];
            $receivedQty = (int) ($item['received_qty'] ?? 0);

            // Skip items without a valid product_id (empty rows)
            if (!$productId) {
                continue;
            }

            $totalItemsCount++;

            // Calculate selling price based on unit price and discount - Cast to numeric types
            $unitPrice = (float) ($item['unit_price'] ?? 0);
            $discount = (float) ($item['discount'] ?? 0);
            $discountType = $item['discount_type'] ?? 'rs';

            // Calculate supplier price (unit price after discount per unit)
            $supplierPrice = $unitPrice;
            if ($discountType === 'percent') {
                $supplierPrice = $unitPrice - ($unitPrice * $discount / 100);
            } else {
                // Discount is total, so divide by quantity to get per unit discount
                $supplierPrice = $unitPrice - ($receivedQty > 0 ? $discount / $receivedQty : 0);
            }
            $supplierPrice = max(0, $supplierPrice); // Ensure non-negative

            // Use selling price from the form if provided, otherwise calculate
            $sellingPrice = (float) ($item['selling_price'] ?? 0);

            if ($sellingPrice <= 0) {
                // Calculate selling price based on markup ratio if not provided
                $product = ProductDetail::with('price')->find($productId);
                if ($product && $product->price) {
                    $currentSupplierPrice = (float) $product->price->supplier_price;
                    $currentSellingPrice = (float) $product->price->selling_price;
                    if ($currentSupplierPrice > 0) {
                        $ratio = $currentSellingPrice / $currentSupplierPrice;
                        $sellingPrice = $supplierPrice * $ratio;
                    } else {
                        $sellingPrice = $currentSellingPrice;
                    }
                } else {
                    // Default markup of 20% if no existing price
                    $sellingPrice = $supplierPrice * 1.2;
                }
            }

            if (isset($item['id'])) {
                // Update existing order item
                $orderItem = PurchaseOrderItem::find($item['id']);
                if ($orderItem) {
                    // Calculate delta: new received qty minus previously recorded received qty
                    $previousReceivedQty = $orderItem->received_quantity ?? 0;

                    // Update the order item
                    $orderItem->received_quantity = $receivedQty;
                    $orderItem->unit_price = $item['unit_price'];
                    $orderItem->discount = $item['discount'];
                    $orderItem->discount_type = $item['discount_type'] ?? 'rs';
                    $orderItem->status = $item['status'];
                    $orderItem->save();

                    // Update stock only with the delta (newly received quantity)
                    if (strtolower($item['status'] ?? '') === 'received' && $receivedQty > 0) {
                        $delta = $receivedQty - $previousReceivedQty;
                        if ($delta > 0) {
                            $wholesalePrice = floatval($item['wholesale_price'] ?? 0);
                            $retailPrice = floatval($item['retail_price'] ?? 0);
                            $distributorPrice = floatval($item['distributor_price'] ?? 0);
                            $variantId = $item['variant_id'] ?? null;
                            $variantValue = $item['variant_value'] ?? null;
                            $this->updateProductStock($productId, $delta, $supplierPrice, $sellingPrice, $this->selectedPO->id, $wholesalePrice, $retailPrice, $distributorPrice, $variantId, $variantValue);
                        }
                        $receivedItemsCount++;
                    }
                }
            } else {
                // Always save new GRN items as 'received' status
                $newOrderItem = PurchaseOrderItem::create([
                    'order_id' => $this->selectedPO->id,
                    'product_id' => $productId,
                    'variant_id' => $item['variant_id'] ?? null,
                    'variant_value' => $item['variant_value'] ?? null,
                    'quantity' => $item['ordered_qty'] ?? $receivedQty, // Ordered quantity
                    'received_quantity' => $receivedQty, // Received quantity
                    'unit_price' => $item['unit_price'] ?? 0,
                    'discount' => $item['discount'] ?? 0,
                    'discount_type' => $item['discount_type'] ?? 'rs',
                    'status' => 'received',
                ]);

                // Update stock for new received item
                if ($receivedQty > 0) {
                    $wholesalePrice = floatval($item['wholesale_price'] ?? 0);
                    $retailPrice = floatval($item['retail_price'] ?? 0);
                    $distributorPrice = floatval($item['distributor_price'] ?? 0);
                    $variantId = $item['variant_id'] ?? null;
                    $variantValue = $item['variant_value'] ?? null;
                    $this->updateProductStock($productId, $receivedQty, $supplierPrice, $sellingPrice, $this->selectedPO->id, $wholesalePrice, $retailPrice, $distributorPrice, $variantId, $variantValue);
                    $receivedItemsCount++;
                }
            }
        }

        // Update order received date and status based on received items
        $this->selectedPO->received_date = now();

        // Determine overall order status
        if ($receivedItemsCount > 0 && $receivedItemsCount === $totalItemsCount) {
            // All items received - mark as fully received
            $this->selectedPO->status = 'received';
        } elseif ($receivedItemsCount > 0) {
            // Some items received but not all - keep as complete (partial receipt)
            $this->selectedPO->status = 'complete';
        }
        // If no items received, status remains as it was

        $this->selectedPO->save();

        $this->dispatch('alert', ['message' => 'GRN processed successfully! Stock updated.']);
        $this->selectedPO = null;
        $this->grnItems = [];
        $this->searchResults = ['unplanned' => []];
    }

    private function updateProductStock($productId, $quantity, $supplierPrice = 0, $sellingPrice = 0, $purchaseOrderId = null, $wholesalePrice = 0, $retailPrice = 0, $distributorPrice = 0, $variantId = null, $variantValue = null)
    {
        // Find stock record with variant consideration
        $stockQuery = ProductStock::where('product_id', $productId);
        if ($variantId) {
            $stockQuery->where('variant_id', $variantId);
            if ($variantValue) {
                $stockQuery->where('variant_value', $variantValue);
            }
        } else {
            $stockQuery->where(function ($q) {
                $q->whereNull('variant_id')->orWhere('variant_id', 0);
            });
        }
        $stock = $stockQuery->first();

        // Get product details to check prices
        $product = ProductDetail::with('price')->find($productId);
        $productPrice = $product->price;

        // If prices not provided, get from product (considering variant-specific prices if applicable)
        if ($supplierPrice == 0 && $productPrice) {
            if ($variantId) {
                $variantPrice = ProductPrice::where('product_id', $productId)
                    ->where('variant_id', $variantId);
                if ($variantValue) {
                    $variantPrice->where('variant_value', $variantValue);
                }
                $variantPrice = $variantPrice->first();
                $supplierPrice = $variantPrice ? $variantPrice->supplier_price : $productPrice->supplier_price;
            } else {
                $supplierPrice = $productPrice->supplier_price;
            }
        }
        if ($sellingPrice == 0 && $productPrice) {
            if ($variantId) {
                $variantPrice = ProductPrice::where('product_id', $productId)
                    ->where('variant_id', $variantId);
                if ($variantValue) {
                    $variantPrice->where('variant_value', $variantValue);
                }
                $variantPrice = $variantPrice->first();
                $sellingPrice = $variantPrice ? $variantPrice->selling_price : $productPrice->selling_price;
            } else {
                $sellingPrice = $productPrice->selling_price;
            }
        }
        if ($wholesalePrice == 0 && $productPrice) {
            if ($variantId) {
                $variantPrice = ProductPrice::where('product_id', $productId)
                    ->where('variant_id', $variantId);
                if ($variantValue) {
                    $variantPrice->where('variant_value', $variantValue);
                }
                $variantPrice = $variantPrice->first();
                $wholesalePrice = $variantPrice ? $variantPrice->wholesale_price : ($productPrice->wholesale_price ?? 0);
            } else {
                $wholesalePrice = $productPrice->wholesale_price ?? 0;
            }
        }
        if ($retailPrice == 0 && $productPrice) {
            if ($variantId) {
                $variantPrice = ProductPrice::where('product_id', $productId)
                    ->where('variant_id', $variantId);
                if ($variantValue) {
                    $variantPrice->where('variant_value', $variantValue);
                }
                $variantPrice = $variantPrice->first();
                $retailPrice = $variantPrice ? $variantPrice->retail_price : ($productPrice->retail_price ?? 0);
            } else {
                $retailPrice = $productPrice->retail_price ?? 0;
            }
        }
        if ($distributorPrice == 0 && $productPrice) {
            if ($variantId) {
                $variantPrice = ProductPrice::where('product_id', $productId)
                    ->where('variant_id', $variantId);
                if ($variantValue) {
                    $variantPrice->where('variant_value', $variantValue);
                }
                $variantPrice = $variantPrice->first();
                $distributorPrice = $variantPrice ? $variantPrice->distributor_price : ($productPrice->distributor_price ?? 0);
            } else {
                $distributorPrice = $productPrice->distributor_price ?? 0;
            }
        }

        // Check if product already has stock
        $hasExistingStock = $stock && $stock->available_stock > 0;

        // If an active batch with the same prices and variant exists, add quantities to that batch; otherwise create a new batch
        $matchingBatchQuery = ProductBatch::where('product_id', $productId)
            ->where('status', 'active')
            ->where('supplier_price', $supplierPrice)
            ->where('wholesale_price', $wholesalePrice ?? 0)
            ->where('retail_price', $retailPrice ?? 0)
            ->where('distributor_price', $distributorPrice ?? 0);

        if ($variantId) {
            $matchingBatchQuery->where('variant_id', $variantId);
            if ($variantValue) {
                $matchingBatchQuery->where('variant_value', $variantValue);
            }
        } else {
            $matchingBatchQuery->where(function ($q) {
                $q->whereNull('variant_id')->orWhere('variant_id', 0);
            });
        }

        $matchingBatch = $matchingBatchQuery->orderBy('received_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        if ($matchingBatch) {
            $matchingBatch->quantity += $quantity;
            $matchingBatch->remaining_quantity += $quantity;
            $matchingBatch->save();
            $batch = $matchingBatch;
            Log::info("Added {$quantity} to existing batch {$matchingBatch->batch_number} for product {$productId}" . ($variantValue ? " variant: {$variantValue}" : ""));
        } else {
            $batchNumber = ProductBatch::generateBatchNumber($productId);
            $batch = ProductBatch::create([
                'product_id' => $productId,
                'batch_number' => $batchNumber,
                'purchase_order_id' => $purchaseOrderId,
                'variant_id' => $variantId,
                'variant_value' => $variantValue,
                'supplier_price' => $supplierPrice,
                'selling_price' => $sellingPrice,
                'wholesale_price' => $wholesalePrice ?? 0,
                'retail_price' => $retailPrice ?? 0,
                'distributor_price' => $distributorPrice ?? 0,
                'quantity' => $quantity,
                'remaining_quantity' => $quantity,
                'received_date' => now(),
                'status' => 'active',
            ]);

            Log::info("Created new batch {$batchNumber} for product {$productId}" . ($variantValue ? " variant: {$variantValue}" : ""));
        }

        // Update product stock totals (with variant consideration)
        if ($stock) {
            // Update existing stock
            $stock->available_stock += $quantity;
            $stock->restocked_quantity += $quantity;
            $stock->updateTotals();
        } else {
            // Create new stock record with variant information
            $stock = ProductStock::create([
                'product_id' => $productId,
                'variant_id' => $variantId,
                'variant_value' => $variantValue,
                'available_stock' => $quantity,
                'damage_stock' => 0,
                'total_stock' => $quantity,
                'sold_count' => 0,
                'restocked_quantity' => $quantity,
            ]);
        }

        // Update product prices on new GRN:
        // - Wholesale, distributor, retail prices: always update to new GRN prices
        // - Cost price (supplier_price): only update when old batch stock is empty
        if ($variantId) {
            // Update variant-specific price
            $variantPriceQuery = ProductPrice::where('product_id', $productId)
                ->where('variant_id', $variantId);
            if ($variantValue) {
                $variantPriceQuery->where('variant_value', $variantValue);
            }
            $variantPrice = $variantPriceQuery->first();

            if ($variantPrice) {
                // Always update wholesale, distributor, and retail prices to new GRN prices
                $variantPrice->wholesale_price = $wholesalePrice ?? ($variantPrice->wholesale_price ?? 0);
                $variantPrice->retail_price = $retailPrice ?? ($variantPrice->retail_price ?? 0);
                $variantPrice->distributor_price = $distributorPrice ?? ($variantPrice->distributor_price ?? 0);
                $variantPrice->selling_price = $sellingPrice;
                // Only update cost price (supplier_price) when old stock is empty
                if (!$hasExistingStock) {
                    $variantPrice->supplier_price = $supplierPrice;
                }
                $variantPrice->save();
            } else {
                // Create variant price if it doesn't exist
                ProductPrice::create([
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'variant_value' => $variantValue,
                    'pricing_mode' => 'variant',
                    'supplier_price' => $supplierPrice,
                    'selling_price' => $sellingPrice,
                    'wholesale_price' => $wholesalePrice ?? 0,
                    'retail_price' => $retailPrice ?? 0,
                    'distributor_price' => $distributorPrice ?? 0,
                    'discount_price' => 0,
                ]);
            }
        } else {
            // Update base product price
            if ($productPrice) {
                // Always update wholesale, distributor, and retail prices to new GRN prices
                $productPrice->wholesale_price = $wholesalePrice ?? ($productPrice->wholesale_price ?? 0);
                $productPrice->retail_price = $retailPrice ?? ($productPrice->retail_price ?? 0);
                $productPrice->distributor_price = $distributorPrice ?? ($productPrice->distributor_price ?? 0);
                $productPrice->selling_price = $sellingPrice;
                // Only update cost price (supplier_price) when old stock is empty
                if (!$hasExistingStock) {
                    $productPrice->supplier_price = $supplierPrice;
                }
                $productPrice->save();
            } else {
                // Create price record if doesn't exist
                ProductPrice::create([
                    'product_id' => $productId,
                    'supplier_price' => $supplierPrice,
                    'selling_price' => $sellingPrice,
                    'wholesale_price' => $wholesalePrice ?? 0,
                    'retail_price' => $retailPrice ?? 0,
                    'distributor_price' => $distributorPrice ?? 0,
                    'discount_price' => 0,
                ]);
            }
        }

        return $batch;
    }

    // Calculate discount amount in rupees
    public function calculateDiscountAmount($item)
    {
        $discountType = $item['discount_type'] ?? 'rs';
        $discount = floatval($item['discount'] ?? 0);
        $unitPrice = floatval($item['unit_price'] ?? 0);
        $receivedQty = floatval($item['received_qty'] ?? 0);

        if ($discountType === 'percent') {
            // Calculate percentage discount
            $subtotal = $receivedQty * $unitPrice;
            return ($subtotal * $discount) / 100;
        }

        // Return discount as is (it's already in rupees)
        return $discount;
    }

    // Calculate total for an item
    public function calculateItemTotal($item)
    {
        $receivedQty = floatval($item['received_qty'] ?? 0);
        $unitPrice = floatval($item['unit_price'] ?? 0);
        $subtotal = $receivedQty * $unitPrice;
        $discountAmount = $this->calculateDiscountAmount($item);

        return max(0, $subtotal - $discountAmount);
    }

    public function render()
    {
        $query = PurchaseOrder::whereIn('status', ['complete', 'received'])
            ->with(['supplier', 'items.product', 'items.variant']);

        // Apply search filter if search term exists
        if (!empty($this->search)) {
            $searchTerm = '%' . $this->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('order_code', 'like', $searchTerm)
                    ->orWhereHas('supplier', function ($supplierQuery) use ($searchTerm) {
                        $supplierQuery->where('name', 'like', $searchTerm);
                    });
            });
        }

        $purchaseOrders = $query->latest()->paginate($this->perPage);

        return view('livewire.admin.g-r-n', [
            'purchaseOrders' => $purchaseOrders,
        ])->layout($this->layout);
    }
}
