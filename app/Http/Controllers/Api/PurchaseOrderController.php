<?php

namespace App\Http\Controllers\Api;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\ProductStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends ApiController
{
    /**
     * Get all purchase orders with optional filters
     */
    public function index(Request $request)
    {
        $query = PurchaseOrder::with(['supplier', 'items.product']);

        // Search
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('order_code', 'like', "%{$search}%")
                    ->orWhereHas('supplier', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        // Filter by supplier
        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->get('supplier_id'));
        }

        /** @var \Illuminate\Pagination\LengthAwarePaginator $orders */
        $orders = $query->orderBy('created_at', 'desc')->paginate(20);

        $transformedOrders = collect($orders->items())->map(function ($order) {
            return $this->transformOrder($order);
        });

        return $this->paginated($orders->setCollection($transformedOrders));
    }

    /**
     * Get a single purchase order by ID
     */
    public function show($id)
    {
        $order = PurchaseOrder::with(['supplier', 'items.product'])->find($id);

        if (!$order) {
            return $this->error('Purchase order not found', 404);
        }

        return $this->success($this->transformOrder($order, true));
    }

    /**
     * Create a new purchase order
     */
    public function store(Request $request)
    {
        // Accept both 'supplier' and 'supplier_id' from frontend
        $supplierId = $request->supplier_id ?? $request->supplier;
        // Accept both 'items' and 'items_data' from frontend
        $items = $request->items ?? $request->items_data ?? [];

        if (!$supplierId) {
            return $this->error('Supplier is required', 422);
        }

        if (empty($items)) {
            return $this->error('At least one item is required', 422);
        }

        try {
            DB::beginTransaction();

            // Accept po_number from frontend or generate one
            // Format: ORD-YYYYMMDD-001
            $today = date('Ymd');
            $count = PurchaseOrder::whereDate('created_at', date('Y-m-d'))->count() + 1;
            $orderCode = $request->po_number ?? $request->order_code ??
                ('ORD-' . $today . '-' . str_pad($count, 3, '0', STR_PAD_LEFT));

            // Calculate totals
            $totalAmount = 0;
            foreach ($items as $item) {
                $productId = $item['product_id'] ?? $item['product'];
                $quantity = $item['quantity'];
                $unitPrice = $item['unit_price'];
                $totalAmount += $quantity * $unitPrice;
            }

            $discountAmount = $request->discount_amount ?? 0;
            $finalAmount = $totalAmount - $discountAmount;

            // Create order
            $order = PurchaseOrder::create([
                'order_code' => $orderCode,
                'supplier_id' => $supplierId,
                'order_date' => $request->po_date ?? $request->order_date ?? now(),
                'status' => $request->status ?? 'pending',
                'total_amount' => $finalAmount,
                'due_amount' => $request->balance ?? $finalAmount,
            ]);

            // Create order items
            foreach ($items as $item) {
                $productId = $item['product_id'] ?? $item['product'];
                $variantId = $item['variant_id'] ?? null;
                $variantValue = $item['variant_value'] ?? null;
                PurchaseOrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'variant_value' => $variantValue,
                    'quantity' => $item['quantity'],
                    'received_quantity' => 0,
                    'unit_price' => $item['unit_price'],
                    'discount' => $item['discount'] ?? $item['discount_percentage'] ?? 0,
                ]);
            }

            DB::commit();

            $order->load(['supplier', 'items.product']);
            return $this->success($this->transformOrder($order, true), 'Purchase order created successfully', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to create purchase order: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Receive items (GRN)
     */
    public function receive(Request $request, $id)
    {
        $order = PurchaseOrder::with(['items'])->find($id);

        if (!$order) {
            return $this->error('Purchase order not found', 404);
        }

        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:purchase_order_items,id',
            'items.*.received_quantity' => 'required|integer|min:0',
        ]);

        try {
            DB::beginTransaction();

            foreach ($request->items as $itemData) {
                $item = PurchaseOrderItem::find($itemData['item_id']);
                if ($item && $item->order_id == $order->id) {
                    $item->received_quantity += $itemData['received_quantity'];
                    $item->save();

                    // Update stock
                    $stock = ProductStock::where('product_id', $item->product_id)->first();
                    if ($stock) {
                        $stock->available_stock += $itemData['received_quantity'];
                        $stock->total_stock += $itemData['received_quantity'];
                        $stock->save();
                    }
                }
            }

            // Update order status
            $allReceived = true;
            foreach ($order->items as $item) {
                if ($item->received_quantity < $item->quantity) {
                    $allReceived = false;
                    break;
                }
            }

            $order->status = $allReceived ? 'received' : 'partial';
            $order->received_date = now();
            $order->save();

            DB::commit();

            $order->load(['supplier', 'items.product']);
            return $this->success($this->transformOrder($order, true), 'Items received successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to receive items: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Store GRN (Create GRN from mobile app)
     * Accepts data format from mobile app frontend
     */
    public function storeGRN(Request $request)
    {
        $poId = $request->purchase_order;
        $order = PurchaseOrder::with(['items'])->find($poId);

        if (!$order) {
            return $this->error('Purchase order not found', 404);
        }

        $items = $request->items_data ?? [];

        if (empty($items)) {
            return $this->error('At least one item is required', 422);
        }

        try {
            DB::beginTransaction();

            foreach ($items as $itemData) {
                // Get product ID from the item data
                $productId = is_array($itemData['product'])
                    ? $itemData['product']['id']
                    : $itemData['product'];

                $variantId = $itemData['variant_id'] ?? null;
                $variantValue = $itemData['variant_value'] ?? null;
                $receivedQty = $itemData['received_quantity'] ?? $itemData['accepted_quantity'] ?? 0;
                $sellingPrice = $itemData['selling_price'] ?? 0;

                // Find the matching PO item (match by product_id AND variant_id for variant-aware lookup)
                $poItemQuery = $order->items()->where('product_id', $productId);
                if ($variantId) {
                    $poItemQuery->where('variant_id', $variantId);
                } else {
                    $poItemQuery->whereNull('variant_id');
                }
                $poItem = $poItemQuery->first();

                if ($poItem) {
                    // Update received quantity
                    $poItem->received_quantity = ($poItem->received_quantity ?? 0) + $receivedQty;
                    $poItem->status = 'received';
                    // Preserve variant information
                    $poItem->variant_id = $variantId;
                    $poItem->variant_value = $variantValue;
                    $poItem->save();

                    // Update stock
                    $stock = ProductStock::where('product_id', $productId)->first();
                    if ($stock) {
                        $stock->available_stock += $receivedQty;
                        $stock->total_stock += $receivedQty;
                        $stock->save();
                    } else {
                        // Create new stock record if doesn't exist
                        ProductStock::create([
                            'product_id' => $productId,
                            'available_stock' => $receivedQty,
                            'total_stock' => $receivedQty,
                            'reorder_level' => 10,
                        ]);
                    }
                } else {
                    // Create new PO item if product wasn't in original order (unplanned item)
                    PurchaseOrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $productId,
                        'variant_id' => $variantId,
                        'variant_value' => $variantValue,
                        'quantity' => $receivedQty,
                        'received_quantity' => $receivedQty,
                        'unit_price' => $itemData['unit_price'] ?? 0,
                        'discount' => $itemData['discount_percentage'] ?? 0,
                        'status' => 'received',
                    ]);

                    // Update stock for unplanned item
                    $stock = ProductStock::where('product_id', $productId)->first();
                    if ($stock) {
                        $stock->available_stock += $receivedQty;
                        $stock->total_stock += $receivedQty;
                        $stock->save();
                    } else {
                        ProductStock::create([
                            'product_id' => $productId,
                            'available_stock' => $receivedQty,
                            'total_stock' => $receivedQty,
                            'reorder_level' => 10,
                        ]);
                    }
                }
            }

            // Update order status
            $allReceived = true;
            $order->refresh();
            foreach ($order->items as $item) {
                if ($item->received_quantity < $item->quantity) {
                    $allReceived = false;
                    break;
                }
            }

            $order->status = $allReceived ? 'received' : 'partial';
            $order->received_date = now();
            $order->save();

            DB::commit();

            $order->load(['supplier', 'items.product']);

            // Return GRN-style response
            return $this->success([
                'id' => $order->id,
                'grn_number' => $request->grn_number ?? ('GRN-' . $order->id),
                'grn_date' => $request->grn_date ?? now()->toDateString(),
                'purchase_order' => $order->id,
                'po_number' => $order->order_code,
                'supplier_name' => $order->supplier ? $order->supplier->name : null,
                'status' => $request->status ?? 'approved',
                'items' => collect($order->items)->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product' => $item->product ? [
                            'id' => $item->product->id,
                            'name' => $item->product->name,
                            'code' => $item->product->code,
                        ] : null,
                        'variant_id' => $item->variant_id,
                        'variant_value' => $item->variant_value,
                        'received_quantity' => $item->received_quantity,
                        'accepted_quantity' => $item->received_quantity,
                        'unit_price' => (float) $item->unit_price,
                    ];
                }),
                'created_at' => $order->updated_at,
            ], 'GRN created successfully', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to create GRN: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update a purchase order
     */
    public function update(Request $request, $id)
    {
        $order = PurchaseOrder::find($id);

        if (!$order) {
            return $this->error('Purchase order not found', 404);
        }

        // Prevent modification if items have been received
        if ($order->items()->where('received_quantity', '>', 0)->exists()) {
            return $this->error('Cannot update purchase order with received items', 400);
        }

        $items = $request->items ?? $request->items_data ?? [];

        if (empty($items)) {
            return $this->error('At least one item is required', 422);
        }

        try {
            DB::beginTransaction();

            // Calculate totals locally for security/accuracy
            $totalAmount = 0;
            foreach ($items as $item) {
                // Ensure quantity and price are numeric
                $quantity = $item['quantity'] ?? 0;
                $unitPrice = $item['unit_price'] ?? 0;
                $totalAmount += $quantity * $unitPrice;
            }

            // Update basic details
            $order->supplier_id = $request->supplier_id ?? $request->supplier ?? $order->supplier_id;
            $order->order_date = $request->po_date ?? $request->order_date ?? $order->order_date;
            // Only update status if explicitly provided, else keep as is (often 'pending')
            // If user explicitly sends status, use it (e.g. changing from draft to pending)
            $order->status = $request->status ?? $order->status;

            $order->total_amount = $totalAmount;
            $order->due_amount = $request->balance ?? $totalAmount;

            $order->save();

            // Delete existing items and recreate
            $order->items()->delete();

            foreach ($items as $item) {
                // Robust product ID extraction
                $productIdRaw = $item['product_id'] ?? $item['product'];
                $productId = is_array($productIdRaw) ? ($productIdRaw['id'] ?? null) : $productIdRaw;
                $variantId = $item['variant_id'] ?? null;
                $variantValue = $item['variant_value'] ?? null;

                if (!$productId) {
                    throw new \Exception("Invalid product ID encountered for item");
                }

                PurchaseOrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'variant_value' => $variantValue,
                    'quantity' => $item['quantity'] ?? 0,
                    'received_quantity' => 0,
                    'unit_price' => $item['unit_price'] ?? 0,
                    'discount' => $item['discount'] ?? 0,
                ]);
            }

            DB::commit();

            $order->load(['supplier', 'items.product']);
            return $this->success($this->transformOrder($order, true), 'Purchase order updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('PO Update Failed: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->error('Failed to update purchase order: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete a purchase order
     */
    public function destroy($id)
    {
        $order = PurchaseOrder::find($id);

        if (!$order) {
            return $this->error('Purchase order not found', 404);
        }

        // Check if order has received items
        $receivedItems = $order->items()->where('received_quantity', '>', 0)->count();
        if ($receivedItems > 0) {
            return $this->error('Cannot delete order with received items', 400);
        }

        // Delete order items first
        $order->items()->delete();
        $order->delete();

        return $this->success(null, 'Purchase order deleted successfully');
    }

    /**
     * Transform order for API response
     */
    private function transformOrder($order, $detailed = false)
    {
        $items = collect($order->items ?? []);
        $variantCount = $items
            ->filter(fn($i) => !empty($i->variant_value) || !empty($i->variant_id))
            ->map(fn($i) => ($i->product_id ?? 'p') . '::' . ($i->variant_id ?? 'v') . '::' . ($i->variant_value ?? ''))
            ->unique()
            ->count();

        $data = [
            'id' => $order->id,
            'order_code' => $order->order_code,
            'po_number' => $order->order_code, // Alias for frontend
            'supplier' => $order->supplier ? [
                'id' => $order->supplier->id,
                'name' => $order->supplier->name,
                'address' => $order->supplier->address,
            ] : null,
            'supplier_name' => $order->supplier ? $order->supplier->name : null,
            'order_date' => $order->order_date,
            'po_date' => $order->order_date, // Alias for frontend
            'expected_delivery_date' => $order->received_date,
            'actual_delivery_date' => $order->received_date,
            'received_date' => $order->received_date,
            'status' => $order->status,
            'subtotal' => (float) $order->total_amount,
            'tax_amount' => 0,
            'shipping_cost' => 0,
            'total_amount' => (float) $order->total_amount,
            'paid_amount' => (float) ($order->total_amount - $order->due_amount),
            'due_amount' => (float) $order->due_amount,
            'balance' => (float) $order->due_amount, // Alias for frontend
            'discount_amount' => (float) $order->discount_amount,
            'item_count' => $items->count(),
            'variant_count' => $variantCount,
            'created_at' => $order->created_at,
            'updated_at' => $order->updated_at,
        ];

        if ($detailed) {
            $data['items'] = $items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'variant_id' => $item->variant_id,
                    'variant_value' => $item->variant_value,
                    'product_name' => $item->product ? $item->product->name : 'Unknown',
                    'product_code' => $item->product ? $item->product->code : '',
                    'product' => $item->product ? [
                        'id' => $item->product->id,
                        'name' => $item->product->name,
                        'code' => $item->product->code,
                        'price' => $item->product->price,
                    ] : null,
                    'quantity' => $item->quantity,
                    'received_quantity' => $item->received_quantity,
                    'unit_price' => (float) $item->unit_price,
                    'total' => (float) ($item->quantity * $item->unit_price),
                    'discount' => (float) ($item->discount ?? 0),
                ];
            });
        }

        return $data;
    }
}
