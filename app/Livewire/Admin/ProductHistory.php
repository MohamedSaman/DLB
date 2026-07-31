<?php

namespace App\Livewire\Admin;

use App\Models\ProductDetail;
use App\Models\ProductVariant;
use App\Models\SaleItem;
use App\Models\PurchaseOrderItem;
use App\Models\ReturnsProduct;
use App\Models\Quotation;
use Livewire\Component;
use App\Livewire\Concerns\WithDynamicLayout;

class ProductHistory extends Component
{
    use WithDynamicLayout;
    public $productId;
    public $product;
    public $productName;

    // History data
    public $salesHistory = [];
    public $purchasesHistory = [];
    public $returnsHistory = [];
    public $quotationsHistory = [];

    // Variant and Customer Type filtering
    public $hasVariants = false;
    public $variantName = '';
    public $variantValues = [];
    public $variantFilter = '';
    public $customerTypeFilter = '';

    // Active tab
    public $activeTab = 'sales';

    public function mount($id)
    {
        $this->productId = $id;
        $this->loadProductData();
    }

    public function loadProductData()
    {
        $this->product = ProductDetail::with(['variant', 'price', 'stock'])->findOrFail($this->productId);
        $this->productName = $this->product->name;

        // Load all history
        $this->loadSalesHistory();
        $this->loadPurchasesHistory();
        $this->loadReturnsHistory();
        $this->loadQuotationsHistory();

        // Detect variants
        $this->detectVariants();
    }

    private function loadSalesHistory()
    {
        $salesItems = SaleItem::with(['sale.customer', 'sale.user'])
            ->where('sale_items.product_id', $this->productId)
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->select(
                'sale_items.*',
                'sales.invoice_number',
                'sales.sale_type',
                'sales.customer_type',
                'sales.payment_type',
                'sales.payment_status',
                'sales.status as sale_status',
                'sales.created_at as sale_date'
            )
            ->orderBy('sales.created_at', 'desc')
            ->get();

        $this->salesHistory = $salesItems->map(function ($sale) {
            $customerType = 'retail';
            if ($sale->sale && $sale->sale->customer && $sale->sale->customer->type) {
                $customerType = strtolower($sale->sale->customer->type);
            } elseif (!empty($sale->customer_type) && $sale->customer_type !== 'walk-in') {
                $customerType = strtolower($sale->customer_type);
            }

            return [
                'id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'sale_type' => $sale->sale_type ?? 'regular',
                'customer_type' => $customerType,
                'quantity' => $sale->quantity,
                'unit_price' => $sale->unit_price,
                'discount_per_unit' => $sale->discount_per_unit ?? 0,
                'total_discount' => $sale->total_discount ?? 0,
                'total' => $sale->total,
                'payment_type' => $sale->payment_type ?? 'cash',
                'payment_status' => $sale->payment_status ?? 'unpaid',
                'sale_status' => $sale->sale_status ?? 'completed',
                'sale_date' => $sale->sale_date,
                'customer_name' => $sale->sale && $sale->sale->customer ? $sale->sale->customer->name : 'Walk-in Customer',
                'customer_phone' => $sale->sale && $sale->sale->customer ? $sale->sale->customer->phone : 'N/A',
                'user_name' => $sale->sale && $sale->sale->user ? $sale->sale->user->name : 'N/A',
                'variant_value' => $sale->variant_value ?? null,
            ];
        })->toArray();
    }

    private function loadPurchasesHistory()
    {
        $purchaseItems = PurchaseOrderItem::with(['order.supplier'])
            ->where('purchase_order_items.product_id', $this->productId)
            ->join('purchase_orders', 'purchase_order_items.order_id', '=', 'purchase_orders.id')
            ->select(
                'purchase_order_items.*',
                'purchase_orders.order_code',
                'purchase_orders.order_date',
                'purchase_orders.received_date',
                'purchase_orders.status as order_status'
            )
            ->orderBy('purchase_orders.order_date', 'desc')
            ->get();

        $this->purchasesHistory = $purchaseItems->map(function ($purchase) {
            $total = $purchase->received_quantity * $purchase->unit_price;
            if (isset($purchase->discount) && $purchase->discount > 0) {
                $total -= $purchase->discount;
            }

            return [
                'id' => $purchase->id,
                'order_code' => $purchase->order_code,
                'order_date' => $purchase->order_date,
                'received_date' => $purchase->received_date ?? 'Pending',
                'quantity' => $purchase->quantity,
                'received_quantity' => $purchase->received_quantity,
                'unit_price' => $purchase->unit_price,
                'discount' => $purchase->discount ?? 0,
                'total' => $total,
                'order_status' => $purchase->order_status ?? 'pending',
                'supplier_name' => $purchase->order && $purchase->order->supplier ? $purchase->order->supplier->name : 'N/A',
                'supplier_phone' => $purchase->order && $purchase->order->supplier ? $purchase->order->supplier->phone : 'N/A',
                'variant_value' => $purchase->variant_value ?? null,
            ];
        })->toArray();
    }

    private function loadReturnsHistory()
    {
        $returns = ReturnsProduct::with(['sale.customer', 'product'])
            ->where('returns_products.product_id', $this->productId)
            ->join('sales', 'returns_products.sale_id', '=', 'sales.id')
            ->select(
                'returns_products.*',
                'sales.invoice_number',
                'sales.customer_type'
            )
            ->orderBy('returns_products.created_at', 'desc')
            ->get();

        $this->returnsHistory = $returns->map(function ($return) {
            $customerType = 'retail';
            if ($return->sale && $return->sale->customer && $return->sale->customer->type) {
                $customerType = strtolower($return->sale->customer->type);
            } elseif (!empty($return->customer_type) && $return->customer_type !== 'walk-in') {
                $customerType = strtolower($return->customer_type);
            }

            return [
                'id' => $return->id,
                'invoice_number' => $return->invoice_number,
                'customer_type' => $customerType,
                'return_quantity' => $return->return_quantity,
                'selling_price' => $return->selling_price ?? 0,
                'total_amount' => $return->total_amount ?? 0,
                'notes' => $return->notes ?? 'No notes provided',
                'return_date' => $return->created_at,
                'customer_name' => $return->sale && $return->sale->customer ? $return->sale->customer->name : 'Walk-in Customer',
                'customer_phone' => $return->sale && $return->sale->customer ? $return->sale->customer->phone : 'N/A',
                'variant_value' => $return->variant_value ?? null,
            ];
        })->toArray();
    }

    private function loadQuotationsHistory()
    {
        $quotations = Quotation::with(['creator', 'customer'])
            ->where('status', '!=', 'draft')
            ->orderBy('quotation_date', 'desc')
            ->get();

        $this->quotationsHistory = [];

        foreach ($quotations as $quotation) {
            $items = is_array($quotation->items) ? $quotation->items : json_decode($quotation->items, true);

            if (!empty($items)) {
                foreach ($items as $item) {
                    if (isset($item['product_id']) && $item['product_id'] == $this->productId) {
                        $customerType = 'retail';
                        if ($quotation->customer && $quotation->customer->type) {
                            $customerType = strtolower($quotation->customer->type);
                        } elseif (!empty($quotation->customer_type)) {
                            $customerType = strtolower($quotation->customer_type);
                        }

                        $this->quotationsHistory[] = [
                            'id' => $quotation->id,
                            'quotation_number' => $quotation->quotation_number,
                            'reference_number' => $quotation->reference_number ?? 'N/A',
                            'customer_type' => $customerType,
                            'customer_name' => $quotation->customer_name ?? ($quotation->customer->name ?? 'N/A'),
                            'customer_phone' => $quotation->customer_phone ?? ($quotation->customer->phone ?? 'N/A'),
                            'customer_email' => $quotation->customer_email ?? 'N/A',
                            'quotation_date' => $quotation->quotation_date,
                            'valid_until' => $quotation->valid_until,
                            'status' => $quotation->status,
                            'quantity' => $item['quantity'] ?? 0,
                            'unit_price' => $item['unit_price'] ?? 0,
                            'discount' => $item['discount'] ?? 0,
                            'total' => $item['total'] ?? 0,
                            'product_name' => $item['product_name'] ?? 'N/A',
                            'product_code' => $item['product_code'] ?? 'N/A',
                            'created_by_name' => $quotation->creator->name ?? 'N/A',
                            'variant_value' => $item['variant_value'] ?? null,
                        ];
                    }
                }
            }
        }
    }

    private function detectVariants()
    {
        // Collect all unique variant values from history records
        $allVariantValues = collect()
            ->merge(array_column($this->salesHistory, 'variant_value'))
            ->merge(array_column($this->purchasesHistory, 'variant_value'))
            ->merge(array_column($this->returnsHistory, 'variant_value'))
            ->merge(array_column($this->quotationsHistory, 'variant_value'))
            ->filter(fn($v) => $v !== null && $v !== '')
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        // Also include values from product variant config
        if ($this->product->variant && is_array($this->product->variant->variant_values)) {
            $allVariantValues = collect($allVariantValues)
                ->merge($this->product->variant->variant_values)
                ->unique()
                ->sort()
                ->values()
                ->toArray();

            $this->variantName = $this->product->variant->variant_name;
        }

        $this->hasVariants = count($allVariantValues) > 0;
        $this->variantValues = $allVariantValues;
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function clearFilter()
    {
        $this->variantFilter = '';
        $this->customerTypeFilter = '';
    }

    // Computed properties for filtered data
    public function getFilteredSalesProperty()
    {
        $collection = collect($this->salesHistory);
        if ($this->variantFilter !== '') {
            $collection = $collection->where('variant_value', $this->variantFilter);
        }
        if ($this->customerTypeFilter !== '') {
            $collection = $collection->filter(function ($item) {
                return strtolower($item['customer_type'] ?? '') === strtolower($this->customerTypeFilter);
            });
        }
        return $collection;
    }

    public function getFilteredPurchasesProperty()
    {
        $collection = collect($this->purchasesHistory);
        if ($this->variantFilter !== '') {
            $collection = $collection->where('variant_value', $this->variantFilter);
        }
        if ($this->customerTypeFilter !== '') {
            return collect([]);
        }
        return $collection;
    }

    public function getFilteredReturnsProperty()
    {
        $collection = collect($this->returnsHistory);
        if ($this->variantFilter !== '') {
            $collection = $collection->where('variant_value', $this->variantFilter);
        }
        if ($this->customerTypeFilter !== '') {
            $collection = $collection->filter(function ($item) {
                return strtolower($item['customer_type'] ?? '') === strtolower($this->customerTypeFilter);
            });
        }
        return $collection;
    }

    public function getFilteredQuotationsProperty()
    {
        $collection = collect($this->quotationsHistory);
        if ($this->variantFilter !== '') {
            $collection = $collection->where('variant_value', $this->variantFilter);
        }
        if ($this->customerTypeFilter !== '') {
            $collection = $collection->filter(function ($item) {
                return strtolower($item['customer_type'] ?? '') === strtolower($this->customerTypeFilter);
            });
        }
        return $collection;
    }

    public function render()
    {
        return view('livewire.admin.product-history', [
            'filteredSales' => $this->filteredSales,
            'filteredPurchases' => $this->filteredPurchases,
            'filteredReturns' => $this->filteredReturns,
            'filteredQuotations' => $this->filteredQuotations,
        ])->layout($this->layout);
    }
}
