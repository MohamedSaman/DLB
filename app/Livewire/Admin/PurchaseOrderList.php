<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\ProductSupplier;
use App\Models\ProductDetail;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\ProductBatch;
use App\Models\ProductPrice;
use App\Models\ProductStock;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\WithPagination;
use App\Livewire\Concerns\WithDynamicLayout;

#[Title("Purchase Order")]

class PurchaseOrderList extends Component
{
    use WithDynamicLayout;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $suppliers = [];
    public $supplier_id = '';
    public $orderDate = '';
    public $Product_id = '';
    public $search = ''; // Main search for table filtering
    public $searchProduct = ''; // Separate search for product selection in modals
    public $products = [];
    public $selectedProduct = null;
    public $quantity = 1;
    public $orderItems = [];
    public $selectedOrder;

    // Edit flow
    public $editOrderId = null;
    public $editOrderItems = [];

    // Add these properties
    public $selectedProductPrice = 0;
    public $totalPrice = 0;

    //GRN properties
    public $purchaseOrders = [];
    public $selectedPO = null;
    public $grnItems = [];
    public $searchResults = [];
    public $newItem = ['product_id' => null, 'name' => '', 'qty' => 1, 'unit_price' => 0, 'discount' => 0, 'status' => 'received'];

    protected $listeners = ['deleteGRNItem'];

    public $grandTotal = 0;

    // Add this property to track new products
    public $newProducts = [];
    public $perPage = 10;

    public function mount()
    {
        $this->suppliers = ProductSupplier::all();
        $this->searchResults = []; // Initialize searchResults array
        $this->orderDate = now()->format('Y-m-d'); // Initialize with current date
    }

    // Reset pagination when search changes
    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function calculateGrandTotal()
    {
        $this->grandTotal = floatval(collect($this->orderItems)->sum('total_price'));
    }

    // Discount is now applied individually per item - no bulk apply
    // Separate method for product search in modals
    public function updatedSearchProduct()
    {
        if (strlen($this->searchProduct) >= 2) {
            $matches = ProductDetail::where('name', 'like', '%' . $this->searchProduct . '%')
                ->orWhere('code', 'like', '%' . $this->searchProduct . '%')
                ->with(['stock', 'price', 'variant'])
                ->limit(10)
                ->get();

            $results = [];
            foreach ($matches as $p) {
                // If product uses a variant and variant values exist, expose each variant value as a selectable item
                if (!empty($p->variant_id) && $p->variant && is_array($p->variant->variant_values) && count($p->variant->variant_values) > 0) {
                    foreach ($p->variant->variant_values as $val) {
                        $supplierPrice = ProductPrice::where('product_id', $p->id)
                            ->where('variant_value', $val)
                            ->value('supplier_price');

                        // Prefer latest active batch supplier price if no variant price
                        if (!$supplierPrice) {
                            $latestBatch = ProductBatch::where('product_id', $p->id)
                                ->where('status', 'active')
                                ->orderBy('received_date', 'desc')
                                ->orderBy('id', 'desc')
                                ->first();
                            if ($latestBatch && floatval($latestBatch->supplier_price) > 0) {
                                $supplierPrice = floatval($latestBatch->supplier_price);
                            } else {
                                $supplierPrice = $p->price->supplier_price ?? 0;
                            }
                        }

                        $available = ProductStock::where('product_id', $p->id)
                            ->where('variant_value', $val)
                            ->value('available_stock');

                        $results[] = [
                            'type' => 'variant',
                            'product_id' => $p->id,
                            'name' => $p->name,
                            'code' => $p->code,
                            'image' => $p->image,
                            'variant_value' => (string) $val,
                            'variant_name' => $p->variant->variant_name ?? null,
                            'supplier_price' => $supplierPrice ?: 0,
                            'available_stock' => $available ?: 0,
                        ];
                    }
                } else {
                    // Determine supplier price for simple product (prefer latest batch)
                    $supplierPrice = $p->price->supplier_price ?? 0;
                    $latestBatch = ProductBatch::where('product_id', $p->id)
                        ->where('status', 'active')
                        ->orderBy('received_date', 'desc')
                        ->orderBy('id', 'desc')
                        ->first();
                    if ($latestBatch && floatval($latestBatch->supplier_price) > 0) {
                        $supplierPrice = floatval($latestBatch->supplier_price);
                    }

                    $results[] = [
                        'type' => 'product',
                        'product_id' => $p->id,
                        'name' => $p->name,
                        'code' => $p->code,
                        'image' => $p->image,
                        'supplier_price' => $supplierPrice,
                        'available_stock' => $p->stock->available_stock ?? 0,
                    ];
                }
            }

            $this->products = $results;
        } else {
            $this->products = [];
        }
    }

    /**
     * Select a specific variant value for a product and add it to the order items.
     */
    public function selectProductVariant($productId, $variantValue)
    {
        $product = ProductDetail::find($productId);
        if (!$product) {
            $this->js("Swal.fire('Error', 'Product not found!', 'error');");
            return;
        }

        // Check if this exact product+variant already exists in the order
        $existingIndex = null;
        foreach ($this->orderItems as $index => $item) {
            if (($item['product_id'] == $productId) && (isset($item['variant_value']) && $item['variant_value'] === (string)$variantValue)) {
                $existingIndex = $index;
                break;
            }
        }

        // Try variant-specific supplier price first
        $price = ProductPrice::where('product_id', $productId)
            ->where('variant_value', $variantValue)
            ->value('supplier_price');

        if (!$price) {
            // If no variant price, prefer latest batch supplier price for product
            $latestBatch = ProductBatch::where('product_id', $productId)
                ->where('status', 'active')
                ->orderBy('received_date', 'desc')
                ->orderBy('id', 'desc')
                ->first();
            if ($latestBatch && floatval($latestBatch->supplier_price) > 0) {
                $price = floatval($latestBatch->supplier_price);
            } else {
                $price = $product->price->supplier_price ?? 0;
            }
        }
        $price = floatval($price);

        $variantId = $product->variant_id;
        $variantName = $product->variant->variant_name ?? 'Variant';
        $displayName = $product->name . ' - ' . $variantName . ': ' . $variantValue;

        if ($existingIndex !== null) {
            $this->orderItems[$existingIndex]['quantity'] += 1;
            $this->orderItems[$existingIndex]['total_price'] =
                floatval($this->orderItems[$existingIndex]['quantity']) * floatval($this->orderItems[$existingIndex]['supplier_price']);

            $item = $this->orderItems[$existingIndex];
            unset($this->orderItems[$existingIndex]);
            $this->orderItems = array_values($this->orderItems);
            array_unshift($this->orderItems, $item);
        } else {
            array_unshift($this->orderItems, [
                'product_id' => $product->id,
                'variant_id' => $variantId,
                'variant_value' => (string)$variantValue,
                'code' => $product->code,
                'name' => $displayName,
                'quantity' => 1,
                'supplier_price' => $price,
                'total_price' => $price,
            ]);
        }

        // Clear search
        $this->products = [];
        $this->searchProduct = '';
        $this->calculateGrandTotal();

        // Dispatch event for keyboard focus management
        $this->dispatch('product-added-to-po-order', index: 0);
    }

    /**
     * Add a specific product variant to the edit order items list.
     */
    public function addProductVariantToEdit($productId, $variantValue)
    {
        $product = ProductDetail::find($productId);
        if (!$product) {
            $this->js("Swal.fire('Error', 'Product not found!', 'error');");
            return;
        }

        $existingIndex = null;
        foreach ($this->editOrderItems as $index => $item) {
            if (($item['product_id'] == $productId) && (isset($item['variant_value']) && $item['variant_value'] === (string)$variantValue)) {
                $existingIndex = $index;
                break;
            }
        }

        // Try variant-specific supplier price first
        $price = ProductPrice::where('product_id', $productId)
            ->where('variant_value', $variantValue)
            ->value('supplier_price');

        if (!$price) {
            // If no variant price, prefer latest batch supplier price for product
            $latestBatch = ProductBatch::where('product_id', $productId)
                ->where('status', 'active')
                ->orderBy('received_date', 'desc')
                ->orderBy('id', 'desc')
                ->first();
            if ($latestBatch && floatval($latestBatch->supplier_price) > 0) {
                $price = floatval($latestBatch->supplier_price);
            } else {
                $price = $product->price->supplier_price ?? 0;
            }
        }
        $price = floatval($price);

        $variantId = $product->variant_id;
        $variantName = $product->variant->variant_name ?? 'Variant';
        $displayName = $product->name . ' - ' . $variantName . ': ' . $variantValue;

        if ($existingIndex !== null) {
            $this->editOrderItems[$existingIndex]['quantity'] += 1;
            $this->editOrderItems[$existingIndex]['total_price'] =
                floatval($this->editOrderItems[$existingIndex]['quantity']) * floatval($this->editOrderItems[$existingIndex]['unit_price']);

            $item = $this->editOrderItems[$existingIndex];
            unset($this->editOrderItems[$existingIndex]);
            $this->editOrderItems = array_values($this->editOrderItems);
            array_unshift($this->editOrderItems, $item);
        } else {
            array_unshift($this->editOrderItems, [
                'product_id' => $product->id,
                'variant_id' => $variantId,
                'variant_value' => (string)$variantValue,
                'code' => $product->code,
                'name' => $displayName,
                'quantity' => 1,
                'unit_price' => $price,
                'discount' => 0,
                'total_price' => $price,
                'status' => 'received',
            ]);
        }

        $this->products = [];
        $this->searchProduct = '';
        $this->calculateGrandTotal();

        // Dispatch event for keyboard focus management
        $this->dispatch('product-added-to-po-edit-order', index: 0);
    }

    public function selectProduct($id)
    {
        $product = ProductDetail::with(['stock', 'price'])->find($id);

        if (!$product) {
            Log::error("Product not found with ID: " . $id);
            $this->js("Swal.fire('Error', 'Product not found!', 'error');");
            return;
        }

        // Check if product already exists in order items
        $existingIndex = null;
        foreach ($this->orderItems as $index => $item) {
            if ($item['product_id'] == $id) {
                $existingIndex = $index;
                break;
            }
        }

        // Determine supplier price, preferring latest active batch supplier price when available
        $price = 0;
        $latestBatch = ProductBatch::where('product_id', $id)
            ->where('status', 'active')
            ->orderBy('received_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();
        if ($latestBatch && floatval($latestBatch->supplier_price) > 0) {
            $price = floatval($latestBatch->supplier_price);
        } else {
            // Fallback to product_price records or product fields
            $price = \App\Models\ProductPrice::where('product_id', $id)->value('supplier_price');
            if (!$price && isset($product->price)) {
                $price = $product->price->supplier_price ?? $product->price;
            }
            if (!$price && isset($product->cost_price)) {
                $price = $product->cost_price;
            }
            if (!$price && isset($product->purchase_price)) {
                $price = $product->purchase_price;
            }
            if (!$price) {
                $price = $product->selling_price ?? 0;
            }
        }
        $price = floatval($price);

        if ($existingIndex !== null) {
            // Product already exists, increment quantity and move to top
            $this->orderItems[$existingIndex]['quantity'] += 1;
            $this->orderItems[$existingIndex]['total_price'] =
                $this->orderItems[$existingIndex]['quantity'] * $this->orderItems[$existingIndex]['supplier_price'];

            // Move the updated item to the top
            $item = $this->orderItems[$existingIndex];
            unset($this->orderItems[$existingIndex]);
            $this->orderItems = array_values($this->orderItems); // Re-index array
            array_unshift($this->orderItems, $item); // Add to top
        } else {
            // Add new product to the top of order items
            array_unshift($this->orderItems, [
                'product_id' => $product->id,
                'code' => $product->code,
                'name' => $product->name,
                'quantity' => 1,
                'supplier_price' => $price,
                'total_price' => $price
            ]);
        }

        // Clear search
        $this->products = [];
        $this->searchProduct = '';
        $this->calculateGrandTotal();

        // Dispatch event for keyboard focus management
        $this->dispatch('product-added-to-po-order', index: 0);

        Log::info("Product added: " . $product->name . ", Price: " . $price);
    }

    public function calculateTotalPrice()
    {
        $this->totalPrice = floatval($this->quantity ?? 0) * floatval($this->selectedProductPrice ?? 0);
        Log::info("Total calculated: " . $this->quantity . " * " . $this->selectedProductPrice . " = " . $this->totalPrice);
    }

    public function updatedQuantity($value)
    {
        $this->calculateTotalPrice();
    }

    // Update order item quantity
    public function updateOrderItemQuantity($index, $quantity)
    {
        if (isset($this->orderItems[$index]) && $quantity >= 1) {
            $this->orderItems[$index]['quantity'] = $quantity;
            $this->orderItems[$index]['total_price'] =
                floatval($quantity) * floatval($this->orderItems[$index]['supplier_price']);
            $this->calculateGrandTotal();

            // Dispatch event for keyboard focus management
            $this->dispatch('qty-updated-po');
        }
    }

    // Update order item price
    public function updateOrderItemPrice($index, $price)
    {
        if (isset($this->orderItems[$index]) && $price >= 0) {
            $this->orderItems[$index]['supplier_price'] = $price;
            $this->orderItems[$index]['total_price'] =
                floatval($this->orderItems[$index]['quantity']) * floatval($price);
            $this->calculateGrandTotal();
        }
    }

    public function removeItem($index)
    {
        unset($this->orderItems[$index]);
        $this->orderItems = array_values($this->orderItems);
        $this->calculateGrandTotal();
    }

    public function saveOrder()
    {
        try {
            // Validation
            if (!$this->supplier_id) {
                $this->js("Swal.fire('Error', 'Please select a supplier!', 'error');");
                return;
            }

            if (!$this->orderDate) {
                $this->js("Swal.fire('Error', 'Please select an order date!', 'error');");
                return;
            }

            if (empty($this->orderItems) || count($this->orderItems) == 0) {
                $this->js("Swal.fire('Error', 'Please add at least one product to the order!', 'error');");
                return;
            }

            // Validate each order item
            foreach ($this->orderItems as $item) {
                if (!isset($item['product_id']) || !isset($item['quantity'])) {
                    $this->js("Swal.fire('Error', 'Invalid order item data!', 'error');");
                    return;
                }

                if ($item['quantity'] < 1) {
                    $this->js("Swal.fire('Error', 'Product quantity must be at least 1!', 'error');");
                    return;
                }

                // Ensure supplier price is set and is a valid number
                if (!isset($item['supplier_price']) || trim($item['supplier_price']) === '') {
                    $this->js("Swal.fire('Error', 'Please enter a valid supplier price for all products!', 'error');");
                    return;
                }

                $price = floatval($item['supplier_price']);
                if ($price < 0) {
                    $this->js("Swal.fire('Error', 'Supplier price cannot be negative!', 'error');");
                    return;
                }
            }

            // Generate unique order code for the selected date
            $year = \Carbon\Carbon::createFromFormat('Y-m-d', $this->orderDate)->format('Ymd');
            $lastOrder = PurchaseOrder::where('order_code', 'like', 'ORD-' . $year . '-%')
                ->orderByDesc('order_code')
                ->first();

            if ($lastOrder && preg_match('/ORD-' . $year . '-(\d+)/', $lastOrder->order_code, $matches)) {
                $nextNumber = intval($matches[1]) + 1;
            } else {
                $nextNumber = 1;
            }

            $orderCode = 'ORD-' . $year . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

            // Convert selected date to datetime
            $orderDateTime = \Carbon\Carbon::createFromFormat('Y-m-d', $this->orderDate)->startOfDay();

            // Create order with transaction
            DB::beginTransaction();

            $order = PurchaseOrder::create([
                'order_code' => $orderCode,
                'supplier_id' => $this->supplier_id,
                'order_date' => $this->orderDate,
                'status' => 'pending',
                'created_at' => $orderDateTime,
                'updated_at' => $orderDateTime,
            ]);

            foreach ($this->orderItems as $item) {
                $supplierPrice = isset($item['supplier_price']) && trim($item['supplier_price']) !== ''
                    ? floatval($item['supplier_price'])
                    : 0;

                PurchaseOrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'] ?? null,
                    'variant_value' => $item['variant_value'] ?? null,
                    'quantity' => floatval($item['quantity']),
                    'unit_price' => $supplierPrice,
                    'discount' => 0,
                    'status' => 'pending',
                    'created_at' => $orderDateTime,
                    'updated_at' => $orderDateTime,
                ]);
            }

            DB::commit();

            // Reset form
            $this->reset(['supplier_id', 'orderDate', 'searchProduct', 'selectedProduct', 'selectedProductPrice', 'quantity', 'orderItems', 'totalPrice', 'grandTotal', 'products']);
            $this->orderDate = now()->format('Y-m-d'); // Reset to current date

            // Close modal and show success
            $this->js("
                const modal = bootstrap.Modal.getInstance(document.getElementById('addPurchaseOrderModal'));
                if (modal) modal.hide();
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Purchase Order {$orderCode} created successfully!',
                    timer: 2000,
                    showConfirmButton: false
                });
            ");

            Log::info("Purchase Order created successfully: " . $orderCode);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error creating purchase order: " . $e->getMessage());
            $this->js("Swal.fire('Error', 'Failed to create purchase order: " . addslashes($e->getMessage()) . "', 'error');");
        }
    }

    public function loadOrders()
    {
        // This method is no longer needed - pagination handled in render()
    }

    public function viewOrder($id)
    {
        $this->selectedOrder = PurchaseOrder::with(['supplier', 'items.product.variant'])->find($id);

        if (!$this->selectedOrder) {
            $this->dispatch('showToast', ['type' => 'error', 'message' => 'Order not found!']);
            return;
        }

        // Build display names for items (include variant value when available)
        foreach ($this->selectedOrder->items as $item) {
            $item->display_name = $this->formatOrderItemName($item);
        }

        // Open the view order modal using dispatch event
        $this->dispatch('openViewOrderModal');
    }

    public function editOrder($id)
    {
        $order = PurchaseOrder::with(['supplier', 'items.product.variant'])->find($id);
        if (!$order) {
            $this->dispatch('showToast', ['type' => 'error', 'message' => 'Order not found!']);
            return;
        }

        $this->editOrderId = $order->id;
        $this->editOrderItems = [];
        foreach ($order->items as $item) {
            $this->editOrderItems[] = [
                'item_id'    => $item->id,
                'product_id' => $item->product_id,
                'code'       => $item->product->code ?? 'N/A',
                'name'       => $this->formatOrderItemName($item),
                'quantity'   => $item->quantity,
                'unit_price' => $item->unit_price,
                'discount'   => $item->discount ?? 0,
            ];
        }

        // Clear search to avoid conflicts
        $this->searchProduct = '';
        $this->products = [];

        // Open the edit order modal - with cleanup
        $this->dispatch('openEditOrderModal');
    }

    // Add product to edit order items
    public function addProductToEdit($id)
    {
        $product = ProductDetail::with(['stock', 'price'])->find($id);

        if (!$product) {
            Log::error("Product not found with ID: " . $id);
            $this->js("Swal.fire('Error', 'Product not found!', 'error');");
            return;
        }

        // Check if product already exists in edit items
        $existingIndex = null;
        foreach ($this->editOrderItems as $index => $item) {
            if ($item['product_id'] == $id) {
                $existingIndex = $index;
                break;
            }
        }

        // Determine supplier price, preferring latest active batch supplier price when available
        $price = 0;
        $latestBatch = ProductBatch::where('product_id', $id)
            ->where('status', 'active')
            ->orderBy('received_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();
        if ($latestBatch && floatval($latestBatch->supplier_price) > 0) {
            $price = floatval($latestBatch->supplier_price);
        } else {
            // Fallback to product_price records or product fields
            $price = \App\Models\ProductPrice::where('product_id', $id)->value('supplier_price');
            if (!$price && isset($product->price)) {
                $price = $product->price->supplier_price ?? $product->price;
            }
            if (!$price && isset($product->cost_price)) {
                $price = $product->cost_price;
            }
            if (!$price && isset($product->purchase_price)) {
                $price = $product->purchase_price;
            }
            if (!$price) {
                $price = $product->selling_price ?? 0;
            }
        }
        $price = floatval($price);

        if ($existingIndex !== null) {
            // Product already exists, increment quantity and move to top
            $this->editOrderItems[$existingIndex]['quantity'] += 1;

            // Move the updated item to the top
            $item = $this->editOrderItems[$existingIndex];
            unset($this->editOrderItems[$existingIndex]);
            $this->editOrderItems = array_values($this->editOrderItems); // Re-index array
            array_unshift($this->editOrderItems, $item); // Add to top
        } else {
            // Add new product to the top of edit items
            array_unshift($this->editOrderItems, [
                'item_id' => null, // New item, no database ID yet
                'product_id' => $product->id,
                'code' => $product->code,
                'name' => $product->name,
                'quantity' => 1,
                'unit_price' => $price,
                'discount' => 0,
            ]);
        }

        // Clear search
        $this->products = [];
        $this->searchProduct = '';

        Log::info("Product added to edit order: " . $product->name);

        // Dispatch event for keyboard focus management
        $this->dispatch('product-added-to-po-edit-order', index: 0);
    }

    // Update total when quantity or price changes in edit modal
    public function updateEditItemTotal($index)
    {
        // This method is called automatically when wire:model.live triggers
        // No calculation needed here as it's done in the blade template

        // Dispatch event for keyboard focus management
        $this->dispatch('qty-updated-po-edit');
    }

    public function removeEditItem($index)
    {
        if (isset($this->editOrderItems[$index])) {
            unset($this->editOrderItems[$index]);
            $this->editOrderItems = array_values($this->editOrderItems);
        }
    }

    public function updateOrder()
    {
        try {
            if (!$this->editOrderId) {
                $this->js("Swal.fire('Error', 'No order selected for editing!', 'error');");
                return;
            }

            $order = PurchaseOrder::find($this->editOrderId);
            if (!$order) {
                $this->js("Swal.fire('Error', 'Order not found!', 'error');");
                return;
            }

            if (empty($this->editOrderItems)) {
                $this->js("Swal.fire('Error', 'Order must have at least one item!', 'error');");
                return;
            }

            DB::beginTransaction();

            // Track current item ids to keep
            $keepIds = [];

            foreach ($this->editOrderItems as $item) {
                // Validation: quantity must be >=1
                $qty = isset($item['quantity']) ? floatval($item['quantity']) : 0;
                if ($qty < 1) continue; // skip invalid rows

                // Ensure unit price is a valid number, default to 0 if empty
                $unitPrice = isset($item['unit_price']) && trim($item['unit_price']) !== ''
                    ? floatval($item['unit_price'])
                    : 0;
                $discount = isset($item['discount']) ? floatval($item['discount']) : 0;

                if (!empty($item['item_id'])) {
                    // update existing
                    $orderItem = PurchaseOrderItem::find($item['item_id']);
                    if ($orderItem) {
                        $orderItem->quantity = $qty;
                        $orderItem->unit_price = $unitPrice;
                        $orderItem->discount = $discount;
                        $orderItem->save();
                        $keepIds[] = $orderItem->id;
                    }
                } else {
                    // create new
                    $new = PurchaseOrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item['product_id'],
                        'variant_id' => $item['variant_id'] ?? null,
                        'variant_value' => $item['variant_value'] ?? null,
                        'quantity' => $qty,
                        'unit_price' => $unitPrice,
                        'discount' => $discount,
                        'status' => 'pending',
                    ]);
                    $keepIds[] = $new->id;
                }
            }

            // Delete any order items not present in keepIds
            PurchaseOrderItem::where('order_id', $order->id)
                ->whereNotIn('id', $keepIds ?: [0])
                ->delete();

            DB::commit();

            $this->editOrderId = null;
            $this->editOrderItems = [];

            $this->js("
                const modal = bootstrap.Modal.getInstance(document.getElementById('editOrderModal'));
                if (modal) modal.hide();
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Order updated successfully!',
                    timer: 2000,
                    showConfirmButton: false
                });
            ");

            Log::info("Purchase Order updated: " . $order->order_code);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error updating purchase order: " . $e->getMessage());
            $this->js("Swal.fire('Error', 'Failed to update order: " . addslashes($e->getMessage()) . "', 'error');");
        }
    }

    public function confirmDelete($id)
    {
        $this->js("Swal.fire({
                title: 'Delete order?',
                text: 'This will remove the purchase order and its items. Continue?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    \$wire.deleteOrderConfirmed({$id});
                }
            });
        ");
    }

    public function deleteOrderConfirmed($id)
    {
        try {
            $order = PurchaseOrder::find($id);

            if (!$order) {
                $this->js("Swal.fire('Error', 'Order not found!', 'error');");
                return;
            }

            DB::beginTransaction();

            $order->status = 'cancelled';
            $order->save();

            DB::commit();

            $this->js("Swal.fire({
                icon: 'success',
                title: 'Cancelled!',
                text: 'Order has been cancelled.',
                timer: 2000,
                showConfirmButton: false
            });");

            Log::info("Purchase Order cancelled: " . $order->order_code);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error cancelling order: " . $e->getMessage());
            $this->js("Swal.fire('Error', 'Failed to cancel order: " . addslashes($e->getMessage()) . "', 'error');");
        }
    }

    public function confirmComplete($id)
    {
        $this->js("Swal.fire({
                title: 'Mark order as complete?',
                text: 'Are you sure you want to complete this order?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, complete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    \$wire.completeOrderConfirmed({$id});
                }
            });
        ");
    }

    public function completeOrderConfirmed($id)
    {
        try {
            $order = PurchaseOrder::find($id);

            if (!$order) {
                $this->js("Swal.fire('Error', 'Order not found!', 'error');");
                return;
            }

            DB::beginTransaction();

            $order->status = 'complete';
            $order->save();

            DB::commit();

            $this->js("Swal.fire({
                icon: 'success',
                title: 'Completed!',
                text: 'Order has been marked as completed.',
                timer: 2000,
                showConfirmButton: false
            });");

            Log::info("Purchase Order completed: " . $order->order_code);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error completing order: " . $e->getMessage());
            $this->js("Swal.fire('Error', 'Failed to complete order: " . addslashes($e->getMessage()) . "', 'error');");
        }
    }

    // Force complete partial receipt order and mark pending items as not received
    public function confirmForceComplete($id)
    {
        $this->js("Swal.fire({
                title: 'Force Complete Order?',
                html: 'This will:<br>• Mark the order as <b>Complete</b><br>• Mark all pending items as <b>Not Received</b><br><br>Are you sure?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, force complete!'
            }).then((result) => {
                if (result.isConfirmed) {
                    \$wire.forceCompleteOrder({$id});
                }
            });
        ");
    }

    public function forceCompleteOrder($id)
    {
        try {
            $order = PurchaseOrder::find($id);

            if (!$order) {
                $this->js("Swal.fire('Error', 'Order not found!', 'error');");
                return;
            }

            DB::beginTransaction();

            // Get all pending items and mark them as not received
            $pendingItems = PurchaseOrderItem::where('order_id', $order->id)
                ->where('status', 'pending')
                ->get();

            $pendingCount = $pendingItems->count();

            foreach ($pendingItems as $item) {
                $item->status = 'notreceived';
                $item->save();
            }

            // Mark order as complete
            $order->status = 'complete';
            $order->save();

            DB::commit();

            $this->js("Swal.fire({
                icon: 'success',
                title: 'Order Completed!',
                text: 'Order completed. {$pendingCount} pending item(s) marked as not received.',
                timer: 3000,
                showConfirmButton: false
            });");

            Log::info("Purchase Order force completed: " . $order->order_code . " with {$pendingCount} items marked as not received");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error force completing order: " . $e->getMessage());
            $this->js("Swal.fire('Error', 'Failed to complete order: " . addslashes($e->getMessage()) . "', 'error');");
        }
    }

    protected function updateProductStock($productId, $quantity, $supplierPrice = 0, $sellingPrice = 0, $purchaseOrderId = null, $wholesalePrice = 0, $retailPrice = 0, $distributorPrice = 0, $variantId = null, $variantValue = null)
    {
        $product = ProductDetail::with('price')->find($productId);
        if (!$product) return;

        $productPrice = $product->price;

        // If prices not provided, get from product (considering variant-specific prices if applicable)
        if ($supplierPrice == 0 && $productPrice) {
            if ($variantId) {
                $variantPriceQuery = ProductPrice::where('product_id', $productId)
                    ->where('variant_id', $variantId);
                if ($variantValue) {
                    $variantPriceQuery->where('variant_value', $variantValue);
                }
                $variantPrice = $variantPriceQuery->first();
                $supplierPrice = $variantPrice ? $variantPrice->supplier_price : $productPrice->supplier_price;
            } else {
                $supplierPrice = $productPrice->supplier_price;
            }
        }
        // Set selling_price to 0 always
        $sellingPrice = 0;

        if ($wholesalePrice == 0 && $productPrice) {
            if ($variantId) {
                $variantPriceQuery = ProductPrice::where('product_id', $productId)
                    ->where('variant_id', $variantId);
                if ($variantValue) {
                    $variantPriceQuery->where('variant_value', $variantValue);
                }
                $variantPrice = $variantPriceQuery->first();
                $wholesalePrice = $variantPrice ? $variantPrice->wholesale_price : $productPrice->wholesale_price;
            } else {
                $wholesalePrice = $productPrice->wholesale_price;
            }
        }
        if ($retailPrice == 0 && $productPrice) {
            if ($variantId) {
                $variantPriceQuery = ProductPrice::where('product_id', $productId)
                    ->where('variant_id', $variantId);
                if ($variantValue) {
                    $variantPriceQuery->where('variant_value', $variantValue);
                }
                $variantPrice = $variantPriceQuery->first();
                $retailPrice = $variantPrice ? $variantPrice->retail_price : $productPrice->retail_price;
            } else {
                $retailPrice = $productPrice->retail_price;
            }
        }
        if ($distributorPrice == 0 && $productPrice) {
            if ($variantId) {
                $variantPriceQuery = ProductPrice::where('product_id', $productId)
                    ->where('variant_id', $variantId);
                if ($variantValue) {
                    $variantPriceQuery->where('variant_value', $variantValue);
                }
                $variantPrice = $variantPriceQuery->first();
                $distributorPrice = $variantPrice ? $variantPrice->distributor_price : ($productPrice->distributor_price ?? 0);
            } else {
                $distributorPrice = $productPrice->distributor_price ?? 0;
            }
        }

        // Check if product already has stock (with variant consideration)
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
        $hasExistingStock = $stock && $stock->available_stock > 0;

        // Check for an existing active batch with the exact same prices and variant
        $matchingBatchQuery = ProductBatch::where('product_id', $productId)
            ->where('status', 'active')
            ->where('supplier_price', $supplierPrice)
            ->where('wholesale_price', $wholesalePrice)
            ->where('retail_price', $retailPrice)
            ->where('distributor_price', $distributorPrice);

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
            // Same price batch found - increase quantities instead of creating a new batch
            $matchingBatch->quantity += $quantity;
            $matchingBatch->remaining_quantity += $quantity;
            $matchingBatch->save();
            $batch = $matchingBatch;
            Log::info("Added {$quantity} units to existing batch {$matchingBatch->batch_number} for product {$productId}" . ($variantValue ? " variant: {$variantValue}" : ""));
        } else {
            // No matching batch - create a new one
            $batchNumber = ProductBatch::generateBatchNumber($productId);
            $batch = ProductBatch::create([
                'product_id' => $productId,
                'batch_number' => $batchNumber,
                'purchase_order_id' => $purchaseOrderId,
                'variant_id' => $variantId,
                'variant_value' => $variantValue,
                'supplier_price' => $supplierPrice,
                'selling_price' => $sellingPrice,
                'wholesale_price' => $wholesalePrice,
                'retail_price' => $retailPrice,
                'distributor_price' => $distributorPrice,
                'quantity' => $quantity,
                'remaining_quantity' => $quantity,
                'received_date' => now(),
                'status' => 'active',
            ]);

            Log::info("Created new batch {$batchNumber} for product {$productId} ({$quantity} units)" . ($variantValue ? " variant: {$variantValue}" : ""));
        }

        // Update product stock totals (with variant consideration)
        if ($stock) {
            $stock->available_stock += $quantity;
            $stock->updateTotals();
        } else {
            // Create new stock record with variant information
            ProductStock::create([
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

        // Update main product prices ONLY if no existing stock (FIFO logic)
        // When old stock reaches 0, the new batch prices become the main prices
        if ($variantId) {
            // Update variant-specific price
            $variantPriceQuery = ProductPrice::where('product_id', $productId)
                ->where('variant_id', $variantId);
            if ($variantValue) {
                $variantPriceQuery->where('variant_value', $variantValue);
            }
            $variantPrice = $variantPriceQuery->first();

            if ($variantPrice) {
                // ONLY update if no existing stock (FIFO principle)
                if (!$hasExistingStock) {
                    $variantPrice->supplier_price = $supplierPrice;
                    $variantPrice->selling_price = 0;
                    $variantPrice->wholesale_price = $wholesalePrice;
                    $variantPrice->retail_price = $retailPrice;
                    $variantPrice->distributor_price = $distributorPrice ?? ($variantPrice->distributor_price ?? 0);
                    $variantPrice->save();
                }
            } else {
                // Create variant price if it doesn't exist
                ProductPrice::create([
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'variant_value' => $variantValue,
                    'supplier_price' => $supplierPrice,
                    'selling_price' => 0,
                    'wholesale_price' => $wholesalePrice,
                    'retail_price' => $retailPrice,
                    'distributor_price' => $distributorPrice ?? 0,
                    'discount_price' => 0,
                ]);
            }
        } else {
            // Update base product price
            if (!$hasExistingStock) {
                if ($productPrice) {
                    $productPrice->supplier_price = $supplierPrice;
                    $productPrice->selling_price = 0;
                    $productPrice->wholesale_price = $wholesalePrice;
                    $productPrice->retail_price = $retailPrice;
                    $productPrice->distributor_price = $distributorPrice ?? ($productPrice->distributor_price ?? 0);
                    $productPrice->save();
                } else {
                    ProductPrice::create([
                        'product_id' => $productId,
                        'supplier_price' => $supplierPrice,
                        'selling_price' => 0,
                        'wholesale_price' => $wholesalePrice,
                        'retail_price' => $retailPrice,
                        'distributor_price' => $distributorPrice ?? 0,
                        'discount_price' => 0,
                    ]);
                }
            }
        }
    }

    // Update saveGRN method to handle new products
    public function saveGRN()
    {
        try {
            if (!$this->selectedPO) {
                $this->js("Swal.fire('Error', 'No purchase order selected!', 'error');");
                return;
            }

            if (empty($this->grnItems)) {
                $this->js("Swal.fire('Error', 'No items to process!', 'error');");
                return;
            }

            // Validate all items before processing
            foreach ($this->grnItems as $index => $item) {
                // Skip items that are not being processed
                if (!isset($item['received_quantity']) || $item['received_quantity'] <= 0) {
                    continue;
                }

                // Validate supplier price is greater than 0
                $unitPrice = floatval($item['unit_price'] ?? 0);
                if ($unitPrice <= 0) {
                    $productName = $item['name'] ?? 'Item ' . ($index + 1);
                    $this->js("Swal.fire('Error', 'Supplier price must be greater than 0 for {$productName}!', 'error');");
                    return;
                }

                // Ensure discount has a valid number, default to 0 if empty
                $discountValue = $item['discount'] ?? 0;
                if (trim($discountValue) === '') {
                    $this->grnItems[$index]['discount'] = 0;
                } else {
                    $this->grnItems[$index]['discount'] = floatval($discountValue);
                }
            }

            DB::beginTransaction();

            $receivedItemsCount = 0;
            $notReceivedItemsCount = 0;
            $orderTotal = 0;

            foreach ($this->grnItems as $item) {
                $productId = $item['product_id'];
                $receivedQty = (int) ($item['received_quantity'] ?? 0);
                $status = strtolower($item['status'] ?? '');
                $isNewProduct = $item['is_new'] ?? false;

                // Count statuses
                if ($status === 'received') {
                    $receivedItemsCount++;
                } elseif ($status === 'notreceived') {
                    $notReceivedItemsCount++;
                }

                // Skip items without a valid product_id and not new products
                if (!$productId && !$isNewProduct) {
                    continue;
                }

                // Handle new product creation
                if ($isNewProduct && !empty($item['name']) && !empty($item['code'])) {
                    $existingProduct = ProductDetail::where('code', $item['code'])->first();
                    if ($existingProduct) {
                        $productId = $existingProduct->id;
                    } else {
                        $newProduct = ProductDetail::create([
                            'code' => $item['code'],
                            'name' => $item['name'],
                            'description' => 'Added via GRN',
                            'category_id' => 1,
                            'brand_id' => 1,
                            'status' => 'active',
                        ]);
                        \App\Models\ProductPrice::create([
                            'product_id' => $newProduct->id,
                            'supplier_price' => $item['unit_price'] ?? 0,
                            'selling_price' => ($item['unit_price'] ?? 0) * 1.2,
                            'wholesale_price' => ($item['unit_price'] ?? 0) * 1.1,
                            'retail_price' => ($item['retail_price'] ?? ($item['unit_price'] ?? 0) * 1.2),
                            'distributor_price' => ($item['distributor_price'] ?? 0),
                        ]);
                        \App\Models\ProductStock::create([
                            'product_id' => $newProduct->id,
                            'available_stock' => 0,
                            'reserved_stock' => 0,
                            'total_stock' => 0,
                        ]);
                        $productId = $newProduct->id;
                    }
                }

                // Calculate total for this item (with discount)
                $unitPrice = (float) ($item['unit_price'] ?? 0);
                // Ensure discount is always a valid number, default to 0 if empty or whitespace
                $discountValue = $item['discount'] ?? 0;
                $discount = (float) (trim($discountValue) === '' ? 0 : $discountValue);
                // Always treat as percentage
                $discountType = 'percent';
                $receivedQty = (int) $receivedQty; // Ensure qty is integer
                $subtotal = $receivedQty * $unitPrice;

                // Calculate supplier price (unit price after discount per unit)
                $supplierPrice = $unitPrice;
                if ($discountType === 'percent') {
                    // Percentage discount - apply to unit price
                    $discountAmount = ($subtotal * $discount) / 100;
                    $itemTotal = $subtotal - $discountAmount;
                    $supplierPrice = $unitPrice - ($unitPrice * $discount / 100);
                } else {
                    // Fixed Rs discount - apply directly to unit price (not distributed)
                    $supplierPrice = $unitPrice - $discount;
                    $itemTotal = $supplierPrice * $receivedQty;
                }
                $supplierPrice = max(0, $supplierPrice);

                // Set selling price to 0 (not used)
                $sellingPrice = 0;

                // Get wholesale, distributor and retail prices from the form
                $wholesalePrice = floatval($item['wholesale_price'] ?? 0);
                $distributorPrice = floatval($item['distributor_price'] ?? 0);
                $retailPrice = floatval($item['retail_price'] ?? 0);

                $orderTotal += max(0, $itemTotal);
                if (isset($item['id'])) {
                    // Update existing order item
                    $orderItem = PurchaseOrderItem::find($item['id']);
                    if ($orderItem) {
                        // Keep original ordered quantity, update received quantity
                        $orderItem->received_quantity = $receivedQty;
                        $orderItem->unit_price = $item['unit_price'];
                        // Ensure discount is always a valid number
                        $discountValue = $item['discount'] ?? 0;
                        $orderItem->discount = floatval($discountValue === '' ? 0 : $discountValue);
                        $orderItem->discount_type = $item['discount_type'] ?? 'rs';
                        $orderItem->status = $status;
                        $orderItem->save();

                        if ($status === 'received' && $receivedQty > 0) {
                            $variantId = $item['variant_id'] ?? null;
                            $variantValue = $item['variant_value'] ?? null;
                            $this->updateProductStock($productId, $receivedQty, $supplierPrice, $sellingPrice, $this->selectedPO->id, $wholesalePrice, $retailPrice, floatval($item['distributor_price'] ?? 0), $variantId, $variantValue);
                        }
                    }
                } else {
                    // Save new GRN item
                    // Ensure discount is always a valid number
                    $discountValue = $item['discount'] ?? 0;
                    $finalDiscount = floatval($discountValue === '' ? 0 : $discountValue);

                    $newOrderItem = PurchaseOrderItem::create([
                        'order_id' => $this->selectedPO->id,
                        'product_id' => $productId,
                        'variant_id' => $item['variant_id'] ?? null,
                        'variant_value' => $item['variant_value'] ?? null,
                        'quantity' => $item['ordered_qty'] ?? $receivedQty, // Store ordered quantity
                        'received_quantity' => $receivedQty, // Store received quantity
                        'unit_price' => $item['unit_price'] ?? 0,
                        'discount' => $finalDiscount,
                        'discount_type' => $item['discount_type'] ?? 'rs',
                        'status' => 'received',
                    ]);

                    if ($receivedQty > 0) {
                        $variantId = $item['variant_id'] ?? null;
                        $variantValue = $item['variant_value'] ?? null;
                        $this->updateProductStock($productId, $receivedQty, $supplierPrice, $sellingPrice, $this->selectedPO->id, $wholesalePrice, $retailPrice, floatval($item['distributor_price'] ?? 0), $variantId, $variantValue);
                    }
                }
            }

            // Update order received date to match order date
            $this->selectedPO->received_date = $this->selectedPO->order_date;

            // Determine overall order status based on ALL order items
            $allOrderItems = PurchaseOrderItem::where('order_id', $this->selectedPO->id)->get();
            $totalItemsCount = $allOrderItems->count();
            $receivedItemsTotal = $allOrderItems->where('status', 'received')->count();
            $notReceivedItemsTotal = $allOrderItems->where('status', 'notreceived')->count();
            $pendingItemsTotal = $allOrderItems->whereNotIn('status', ['received', 'notreceived'])->count();

            // If all items are received, mark order as complete
            if ($receivedItemsTotal === $totalItemsCount && $totalItemsCount > 0) {
                $this->selectedPO->status = 'complete';
            }
            // If some items are received but others are pending/not received
            elseif ($receivedItemsTotal > 0 && ($pendingItemsTotal > 0 || $notReceivedItemsTotal > 0)) {
                $this->selectedPO->status = 'received'; // Partial receipt
            }
            // If no items received yet
            else {
                $this->selectedPO->status = 'pending';
            }

            // Set total_amount and due_amount
            $this->selectedPO->total_amount = $orderTotal;
            $this->selectedPO->due_amount = $orderTotal;
            $this->selectedPO->save();

            DB::commit();

            // Reset GRN data
            $orderCode = $this->selectedPO->order_code;
            $this->selectedPO = null;
            $this->grnItems = [];
            $this->searchResults = [];

            // Close modal and show success message
            $this->js("
                const modal = bootstrap.Modal.getInstance(document.getElementById('grnModal'));
                if (modal) modal.hide();
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'GRN processed successfully! Stock updated for order {$orderCode}.',
                    timer: 2000,
                    showConfirmButton: false
                });
            ");

            Log::info("GRN processed successfully for order: " . $orderCode);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error processing GRN: " . $e->getMessage());
            $this->js("Swal.fire('Error', 'Failed to process GRN: " . addslashes($e->getMessage()) . "', 'error');");
        }
    }

    public function deleteGRNItem($index)
    {
        if (isset($this->grnItems[$index])) {
            // Mark as not received instead of deleting
            $this->grnItems[$index]['status'] = 'notreceived';
            $this->grnItems[$index]['received_quantity'] = 0;

            // Remove the item completely from GRN items array
            unset($this->grnItems[$index]);
        }
    }

    public function correctGRNItem($index)
    {
        $item = $this->grnItems[$index];
        $productId = $item['product_id'];
        $receivedQty = (int) ($item['received_quantity'] ?? 0);

        // Mark the item as received in the UI
        $this->grnItems[$index]['status'] = 'received';

        // Update stock immediately if we have a valid product and quantity
        if ($productId && $receivedQty > 0) {
            $this->updateProductStock($productId, $receivedQty);
        }
        if (isset($this->grnItems[$index])) {
            $this->grnItems[$index]['status'] = 'received';
            // Reset to ordered quantity if needed
            if ($this->grnItems[$index]['received_quantity'] == 0) {
                $this->grnItems[$index]['received_quantity'] = $this->grnItems[$index]['ordered_qty'];
            }
        }
    }

    public function searchGRNProducts($searchTerm, $index)
    {
        if (!empty($searchTerm)) {
            $this->searchResults[$index] = ProductDetail::where('name', 'like', '%' . $searchTerm . '%')
                ->orWhere('code', 'like', '%' . $searchTerm . '%')
                ->with(['stock'])
                ->limit(5)
                ->get();
        } else {
            $this->searchResults[$index] = [];
        }
    }

    public function calculateGRNTotal($index)
    {
        if (!isset($this->grnItems[$index])) {
            return 0.0;
        }

        $item = $this->grnItems[$index];

        // Convert to numbers and ensure they are valid
        $receivedQty = floatval($item['received_quantity'] ?? 0);
        $unitPrice = floatval($item['unit_price'] ?? 0);
        $discount = floatval($item['discount'] ?? 0);

        // Calculate cost per unit after discount (percentage)
        $discountAmountPerUnit = ($unitPrice * $discount) / 100;
        $costPerUnit = $unitPrice - $discountAmountPerUnit;

        // Total = cost per unit × quantity
        $total = $costPerUnit * $receivedQty;

        Log::info("GRN Total Calc: Qty={$receivedQty}, UnitPrice={$unitPrice}, Discount={$discount}%, CostPerUnit={$costPerUnit}, Total={$total}");

        // Ensure total is not negative
        return floatval(max(0, $total));
    }

    public function updatedGrnItems($value, $index)
    {
        // Handle search when product name is updated
        $parts = explode('.', $index);
        if (count($parts) === 3) {
            $itemIndex = $parts[1];
            $field = $parts[2];

            if ($field === 'name') {
                $this->searchGRNProducts($value, $itemIndex);
            }

            // Log discount updates for debugging
            if ($field === 'discount' || $field === 'discount_type') {
                Log::info("Discount updated for item {$itemIndex}: Field={$field}, Value={$value}, Type={$this->grnItems[$itemIndex]['discount_type']}, Discount={$this->grnItems[$itemIndex]['discount']}");
            }

            // Calculate GRN total when numeric fields change
            if (in_array($field, ['received_quantity', 'unit_price', 'discount', 'selling_price'])) {
                $this->calculateGRNTotal($itemIndex);
            }
        }
    }

    public function selectGRNProduct($index, $productId)
    {
        $product = ProductDetail::find($productId);
        if ($product) {
            $this->grnItems[$index]['product_id'] = $product->id;
            $this->grnItems[$index]['code'] = $product->code;
            // Determine best guess unit price (prefer latest batch then product price)
            $supplierPrice = 0;
            $latestBatch = ProductBatch::where('product_id', $productId)
                ->where('status', 'active')
                ->orderBy('received_date', 'desc')
                ->orderBy('id', 'desc')
                ->first();
            if ($latestBatch && floatval($latestBatch->supplier_price) > 0) {
                $supplierPrice = floatval($latestBatch->supplier_price);
            } else {
                $price = \App\Models\ProductPrice::where('product_id', $productId)->value('supplier_price');
                if (!$price && isset($product->price)) {
                    $price = $product->price->supplier_price ?? $product->price;
                }
                if (!$price && isset($product->cost_price)) {
                    $price = $product->cost_price;
                }
                $supplierPrice = is_object($price) ? (float)($price->supplier_price ?? 0) : (float)($price ?? 0);
            }

            // Format name including variant when possible
            $this->grnItems[$index]['name'] = $this->formatProductName($product, $supplierPrice);

            $this->grnItems[$index]['unit_price'] = $supplierPrice;
            $this->grnItems[$index]['discount'] = 0;
            $this->grnItems[$index]['is_new'] = false; // Not a new product
            $this->searchResults[$index] = [];

            // Trigger calculation
            $this->calculateGRNTotal($index);
        }
    }

    public function addNewRow()
    {
        $this->grnItems[] = [
            'product_id' => null,
            'variant_id' => null,
            'variant_value' => null,
            'code' => '',
            'name' => '',
            'ordered_qty' => 0,
            'received_quantity' => 0,
            'unit_price' => 0,
            'discount' => 0,
            'discount_type' => 'percent',
            'selling_price' => 0,
            'wholesale_price' => 0,
            'retail_price' => 0,
            'distributor_price' => 0,
            'status' => 'received',
            'is_new' => true
        ];
    }

    public function convertToGRN($orderId)
    {
        $this->selectedPO = PurchaseOrder::with(['supplier', 'items.product.detail', 'items.product.variant'])->find($orderId);

        if (!$this->selectedPO) {
            $this->dispatch('showToast', ['type' => 'error', 'message' => 'Order not found!']);
            return;
        }

        // Initialize GRN items from purchase order items
        $this->grnItems = [];
        foreach ($this->selectedPO->items as $item) {
            // Get current product prices - check for variant-specific prices first
            $product = ProductDetail::with('price')->find($item->product_id);

            $currentWholesalePrice = 0;
            $currentRetailPrice = 0;
            $currentDistributorPrice = 0;

            if ($item->variant_id) {
                // Try to get variant-specific price
                $variantPrice = ProductPrice::where('product_id', $item->product_id)
                    ->where('variant_id', $item->variant_id);
                if ($item->variant_value) {
                    $variantPrice->where('variant_value', $item->variant_value);
                }
                $variantPrice = $variantPrice->first();

                if ($variantPrice) {
                    $currentWholesalePrice = $variantPrice->wholesale_price ?? 0;
                    $currentRetailPrice = $variantPrice->retail_price ?? 0;
                    $currentDistributorPrice = $variantPrice->distributor_price ?? 0;
                }
            }

            // Fall back to product's main price if no variant price found
            if (!$currentWholesalePrice && $product && $product->price) {
                $currentWholesalePrice = $product->price->wholesale_price ?? 0;
                $currentRetailPrice = $product->price->retail_price ?? 0;
                $currentDistributorPrice = $product->price->distributor_price ?? 0;
            }

            // Set default discount_type to 'percent'
            $discountType = $item->discount_type ?? 'percent';

            $this->grnItems[] = [
                'id'                => $item->id,
                'product_id'        => $item->product_id,
                'variant_id'        => $item->variant_id ?? null,
                'variant_value'     => $item->variant_value ?? null,
                'code'              => $item->product->code ?? 'N/A',
                'name'              => $this->formatOrderItemName($item),
                'ordered_qty'       => $item->quantity,
                'received_quantity' => $item->quantity,
                'unit_price'        => $item->unit_price,
                'discount'          => $item->discount ?? 0,
                'discount_type'     => $discountType,
                'wholesale_price'   => $currentWholesalePrice,
                'retail_price'      => $currentRetailPrice,
                'distributor_price' => $currentDistributorPrice,
                'status'            => 'received'
            ];
        }

        // Open the GRN modal - with cleanup
        $this->dispatch('openGRNModal');
    }

    public function reProcessGRN($orderId)
    {
        $this->selectedPO = PurchaseOrder::with(['supplier', 'items.product.detail', 'items.product.variant'])->find($orderId);

        if (!$this->selectedPO) {
            $this->dispatch('showToast', ['type' => 'error', 'message' => 'Order not found!']);
            return;
        }

        // Initialize GRN items from ONLY pending purchase order items
        $this->grnItems = [];
        foreach ($this->selectedPO->items as $item) {
            // Only include items that are pending or not received
            if (in_array(strtolower($item->status ?? 'pending'), ['pending', 'notreceived', ''])) {
                // Get current product prices - check for variant-specific prices first
                $product = ProductDetail::with('price')->find($item->product_id);

                $currentSellingPrice = 0;
                $currentWholesalePrice = 0;
                $currentRetailPrice = 0;
                $currentDistributorPrice = 0;

                if ($item->variant_id) {
                    // Try to get variant-specific price
                    $variantPrice = ProductPrice::where('product_id', $item->product_id)
                        ->where('variant_id', $item->variant_id);
                    if ($item->variant_value) {
                        $variantPrice->where('variant_value', $item->variant_value);
                    }
                    $variantPrice = $variantPrice->first();

                    if ($variantPrice) {
                        $currentSellingPrice = $variantPrice->selling_price ?? 0;
                        $currentWholesalePrice = $variantPrice->wholesale_price ?? 0;
                        $currentRetailPrice = $variantPrice->retail_price ?? 0;
                        $currentDistributorPrice = $variantPrice->distributor_price ?? 0;
                    }
                }

                // Fall back to product's main price if no variant price found
                if (!$currentWholesalePrice && $product && $product->price) {
                    $currentSellingPrice = $product->price->selling_price ?? 0;
                    $currentWholesalePrice = $product->price->wholesale_price ?? 0;
                    $currentRetailPrice = $product->price->retail_price ?? 0;
                    $currentDistributorPrice = $product->price->distributor_price ?? 0;
                }

                // Set default discount_type to 'percent'
                $discountType = $item->discount_type ?? 'percent';

                $this->grnItems[] = [
                    'id'                => $item->id,
                    'product_id'        => $item->product_id,
                    'variant_id'        => $item->variant_id ?? null,
                    'variant_value'     => $item->variant_value ?? null,
                    'code'              => $item->product->code ?? 'N/A',
                    'name'              => $this->formatOrderItemName($item),
                    'ordered_qty'       => $item->quantity,
                    'received_quantity' => $item->quantity,
                    'unit_price'        => $item->unit_price,
                    'discount'          => $item->discount ?? 0,
                    'discount_type'     => $discountType,
                    'selling_price'     => $currentSellingPrice,
                    'wholesale_price'   => $currentWholesalePrice,
                    'retail_price'      => $currentRetailPrice,
                    'distributor_price' => $currentDistributorPrice,
                    'status'            => 'received'
                ];
            }
        }

        if (empty($this->grnItems)) {
            $this->dispatch('showToast', ['type' => 'info', 'message' => 'No pending items to process!']);
            return;
        }

        // Open the GRN modal - with cleanup
        $this->dispatch('openGRNModal');
    }

    public function calculateCost($index)
    {
        if (!isset($this->grnItems[$index])) {
            return 0.0;
        }

        $item = $this->grnItems[$index];
        $unitPrice = floatval($item['unit_price'] ?? 0);
        $discount = floatval($item['discount'] ?? 0);
        // Always treat as percentage
        $discountAmountPerUnit = ($unitPrice * $discount) / 100;
        $costPerUnit = $unitPrice - $discountAmountPerUnit;

        // Ensure cost is not negative
        return floatval(max(0, $costPerUnit));
    }

    public function setDiscountType($index, $type)
    {
        if (isset($this->grnItems[$index])) {
            $this->grnItems[$index]['discount_type'] = $type;
            // Recalculate total when discount type changes
            $this->calculateGRNTotal($index);
        }
    }

    public function calculateDiscountAmount($index)
    {
        if (!isset($this->grnItems[$index])) {
            return 0.0;
        }

        $item = $this->grnItems[$index];
        $receivedQty = floatval($item['received_quantity'] ?? 0);
        $unitPrice = floatval($item['unit_price'] ?? 0);
        $discount = floatval($item['discount'] ?? 0);
        // Always treat as percentage
        $subtotal = $receivedQty * $unitPrice;
        return floatval(($subtotal * $discount) / 100);
    }

    public function calculateSellingPrice($index)
    {
        if (!isset($this->grnItems[$index])) {
            return 0.0;
        }

        $item = $this->grnItems[$index];
        $unitPrice = floatval($item['unit_price'] ?? 0);
        $discount = floatval($item['discount'] ?? 0);

        // If no unit price, return 0
        if ($unitPrice <= 0) {
            return 0.0;
        }

        // Calculate selling price based on discount percentage conditions
        $sellingPrice = 0;

        if ($discount == 25) {
            // 25% discount → add 15% markup
            $sellingPrice = $unitPrice * 1.15;
        } elseif ($discount == 20 || $discount == 15 || $discount == 7) {
            // 20%, 15%, or 7% discount → add 20% markup
            $sellingPrice = $unitPrice * 1.20;
        } else {
            // Default: add 20% for any other percentage
            $sellingPrice = $unitPrice * 1.20;
        }

        // Round to nearest 10
        $sellingPrice = round($sellingPrice / 10) * 10;

        return floatval(max(0, $sellingPrice));
    }

    public function autoCalculateSellingPrice($index)
    {
        if (!isset($this->grnItems[$index])) {
            return;
        }

        // Calculate the new selling price based on current supplier price and discount
        $calculatedPrice = $this->calculateSellingPrice($index);

        // Update the selling price in the item
        $this->grnItems[$index]['selling_price'] = $calculatedPrice;

        // Log the calculation
        $item = $this->grnItems[$index];
        $unitPrice = floatval($item['unit_price'] ?? 0);
        $discount = floatval($item['discount'] ?? 0);
        $discountType = $item['discount_type'] ?? 'percent';

        Log::info("Auto-calculated Selling Price: Index={$index}, UnitPrice={$unitPrice}, Discount={$discount}{$discountType}, SellingPrice={$calculatedPrice}");
    }

    public function loadPurchaseOrders()
    {
        $this->purchaseOrders = PurchaseOrder::where('status', 'pending')
            ->with(['supplier', 'items.product'])
            ->latest()
            ->get();
    }

    public function confirmPermanentDelete($orderId)
    {
        $this->js("
        Swal.fire({
            title: 'Permanently Delete Order?',
            text: 'This action cannot be undone! The purchase order will be permanently removed from the system.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete permanently!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                \$wire.permanentDeleteOrder({$orderId});
            }
        });
    ");
    }

    public function permanentDeleteOrder($orderId)
    {
        try {
            $order = PurchaseOrder::findOrFail($orderId);

            DB::beginTransaction();

            // Delete all order items first
            PurchaseOrderItem::where('order_id', $order->id)->delete();

            // Delete the order
            $orderCode = $order->order_code;
            $order->delete();

            DB::commit();

            $this->js("Swal.fire({
                icon: 'success',
                title: 'Deleted!',
                text: 'Purchase order {$orderCode} has been permanently deleted.',
                timer: 2000,
                showConfirmButton: false
            });");

            Log::info("Purchase order permanently deleted: " . $orderCode);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error permanently deleting order: " . $e->getMessage());
            $this->js("Swal.fire('Error', 'Failed to delete order: " . addslashes($e->getMessage()) . "', 'error');");
        }
    }

    public function getViewOrderTotalProperty()
    {
        if (!$this->selectedOrder) {
            return 0.0;
        }

        return floatval($this->selectedOrder->items->sum(function ($item) {
            $qty = floatval($item->received_quantity ?? 0);
            $unitPrice = floatval($item->unit_price);
            $discount = floatval($item->discount ?? 0);
            // Apply discount as percentage
            $discountAmount = ($unitPrice * $discount) / 100;
            $netUnitPrice = $unitPrice - $discountAmount;
            return floatval($qty) * $netUnitPrice;
        }));
    }

    public function getGrnGrandTotalProperty()
    {
        $total = 0.0;
        foreach ($this->grnItems as $index => $item) {
            $total += $this->calculateGRNTotal($index);
        }
        return floatval($total);
    }

    /**
     * Return a formatted display name for an order item using stored variant_id/variant_value.
     */
    private function formatOrderItemName($orderItem)
    {
        $product = $orderItem->product ?? null;
        if (!$product) return 'N/A';

        $name = trim($product->name ?? '');
        $variantValue = $orderItem->variant_value ?? null;
        $variantId    = $orderItem->variant_id    ?? null;

        if (!empty($variantId) && !empty($variantValue)) {
            $variantName = $product->variant->variant_name ?? null;
            if (!empty($variantName)) {
                return rtrim(trim($name . ' - ' . $variantName . ': ' . $variantValue));
            }
            return rtrim(trim($name . ' - ' . $variantValue), ' -:');
        }

        // No variant info stored – fall back to plain name
        return rtrim($name, ' -:');
    }

    /**
     * Format product name and append variant value if available. Tries to match by supplier_price or selling_price.
     */
    private function formatProductName($product, $unitPrice = null)
    {
        if (!$product) return 'N/A';
        $name = trim($product->name ?? '');

        if (!empty($product->variant_id) && $product->variant) {
            $variantName = $product->variant->variant_name ?? null;
            $variantValue = null;

            if ($unitPrice !== null) {
                $price = \App\Models\ProductPrice::where('product_id', $product->id)
                    ->whereNotNull('variant_value')
                    ->where('supplier_price', $unitPrice)
                    ->first();

                if (!$price) {
                    $price = \App\Models\ProductPrice::where('product_id', $product->id)
                        ->whereNotNull('variant_value')
                        ->where('selling_price', $unitPrice)
                        ->first();
                }

                if ($price && !empty($price->variant_value)) {
                    $variantValue = trim($price->variant_value);
                }
            }

            if (!$variantValue) {
                $fallback = \App\Models\ProductPrice::where('product_id', $product->id)
                    ->whereNotNull('variant_value')
                    ->where('variant_value', '!=', '')
                    ->first();
                if ($fallback) $variantValue = trim($fallback->variant_value);
            }

            if (!empty($variantValue)) {
                if (!empty($variantName)) {
                    $display = trim($name . ' - ' . $variantName . ': ' . $variantValue);
                } else {
                    $display = trim($name . ' - ' . $variantValue);
                }
                // Remove accidental trailing punctuation/hyphens
                return rtrim($display, " -:");
            }
        }

        return rtrim($name, " -:");
    }

    public function downloadPDF($orderId)
    {
        $order = PurchaseOrder::with('supplier', 'items.product')->find($orderId);

        if (!$order) {
            $this->js("Swal.fire('Error', 'Order not found!', 'error');");
            return;
        }

        // Inline HTML for the PDF
        $html = '<!DOCTYPE html>
    <html>
    <head>
        <title>Purchase Order - ' . $order->order_code . '</title>
        <style>
            body { font-family: sans-serif; font-size: 12px; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            table, th, td { border: 1px solid #333; }
            th, td { padding: 5px; text-align: left; }
            th { background-color: #f0f0f0; }
        </style>
    </head>
    <body>
        <h2>Purchase Order: ' . $order->order_code . '</h2>
        <p><strong>Supplier:</strong> ' . ($order->supplier->name ?? 'N/A') . '</p>
        <p><strong>Order Date:</strong> ' . $order->order_date . '</p>
        <p><strong>Received Date:</strong> ' . ($order->received_date ?? '-') . '</p>
        <table>
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Unit Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($order->items as $item) {
            $qty = $item->received_quantity > 0 ? $item->received_quantity : $item->quantity;
            $html .= '<tr>
                    <td>' . $item->product->code . '</td>
                    <td>' . $this->formatProductName($item->product ?? null, $item->unit_price ?? null) . '</td>
                    <td>' . $qty . '</td>
                    <td>' . number_format(floatval($item->unit_price), 2) . '</td>
                    <td>' . number_format(floatval($qty) * floatval($item->unit_price), 2) . '</td>
                  </tr>';
        }

        $totalAmount = floatval($order->items->sum(function ($item) {
            $qty = $item->received_quantity > 0 ? $item->received_quantity : $item->quantity;
            return floatval($qty) * floatval($item->unit_price);
        }));

        $html .= '</tbody>
        </table>
        <h3 style="text-align:right;">Grand Total: ' . number_format($totalAmount, 2) . '</h3>
    </body>
    </html>';

        $pdf = Pdf::loadHTML($html);

        return response()->streamDownload(
            fn() => print($pdf->output()),
            "Purchase_Order_{$order->order_code}.pdf"
        );
    }

    public function render()
    {
        $pendingCount = PurchaseOrder::where('status', 'pending')->count();
        $completedCount = PurchaseOrder::where('status', 'complete')->count();

        // Optional: Count orders that have been fully received (all items received)
        $fullyReceivedCount = PurchaseOrder::whereHas('items', function ($query) {
            $query->where('status', 'received');
        })->whereDoesntHave('items', function ($query) {
            $query->where('status', '!=', 'received');
        })->count();

        // Get paginated orders with search functionality
        $query = PurchaseOrder::whereIn('status', ['pending', 'complete', 'received', 'cancelled'])
            ->with(['supplier', 'items.product']);

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

        $orders = $query->orderByRaw("FIELD(status, 'pending', 'received', 'complete', 'cancelled')")
            ->orderBy('id', 'desc')
            ->paginate($this->perPage);

        return view('livewire.admin.purchase-order-list', compact('pendingCount', 'completedCount', 'fullyReceivedCount', 'orders'))->layout($this->layout);
    }
}
