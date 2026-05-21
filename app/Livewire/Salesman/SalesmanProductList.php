<?php

namespace App\Livewire\Salesman;

use App\Models\ProductDetail;
use App\Models\CategoryList;
use App\Models\SaleItem;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Title('Product List')]
#[Layout('components.layouts.salesman')]
class SalesmanProductList extends Component
{
    use WithPagination;

    public $search = '';
    public $categoryFilter = '';
    public $categories = [];

    // Product detail modal
    public $selectedProduct = null;
    public $showProductModal = false;

    public function mount()
    {
        $this->categories = CategoryList::orderBy('category_name')->get();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedCategoryFilter()
    {
        $this->resetPage();
    }

    public function viewProduct($productId)
    {
        $this->selectedProduct = ProductDetail::with(['stock', 'price', 'category', 'variant', 'brand'])
            ->find($productId);
        $this->showProductModal = true;
    }

    public function closeProductModal()
    {
        $this->showProductModal = false;
        $this->selectedProduct = null;
    }

    public function loadProducts()
    {
        $query = ProductDetail::with(['stock', 'price', 'category', 'variant', 'stocks', 'prices'])
            ->where('status', 'active');

        if ($this->categoryFilter) {
            $query->where('category_id', $this->categoryFilter);
        }

        $items = [];
        $products = $query->get();

        foreach ($products as $product) {
            // If product has variant stocks, expand each variant
            if (($product->variant_id ?? null) !== null && $product->stocks && $product->stocks->isNotEmpty()) {
                $orderedValues = [];
                if ($product->variant && is_array($product->variant->variant_values)) {
                    $orderedValues = $product->variant->variant_values;
                }

                $stocksByValue = [];
                foreach ($product->stocks as $stock) {
                    $stocksByValue[$stock->variant_value] = $stock;
                }

                if (!empty($orderedValues)) {
                    foreach ($orderedValues as $val) {
                        if (!isset($stocksByValue[$val])) continue;
                        $stock = $stocksByValue[$val];
                        $priceRecord = $product->prices->firstWhere('variant_value', $stock->variant_value) ?? $product->price;

                        $pendingQty = SaleItem::whereHas('sale', function ($q) {
                            $q->where('status', 'pending');
                        })
                            ->where('product_id', $product->id)
                            ->where('variant_value', $stock->variant_value)
                            ->sum('quantity');

                        $availableStock = max(0, ($stock->available_stock ?? 0) - $pendingQty);

                        $items[] = [
                            'id' => $product->id . '::' . $stock->variant_value,
                            'product_id' => $product->id,
                            'variant_value' => $stock->variant_value,
                            'name' => $product->name . ' (' . $stock->variant_value . ')',
                            'code' => $product->code,
                            'image' => $product->image ?? '',
                            'category' => $product->category->name ?? 'N/A',
                            'distributor_price' => $priceRecord->distributor_price ?? $priceRecord->wholesale_price ?? 0,
                            'stock' => $availableStock,
                            'pending' => $pendingQty,
                        ];
                    }

                    foreach ($stocksByValue as $v => $stock) {
                        if (in_array($v, $orderedValues)) continue;
                        $priceRecord = $product->prices->firstWhere('variant_value', $stock->variant_value) ?? $product->price;

                        $pendingQty = SaleItem::whereHas('sale', function ($q) {
                            $q->where('status', 'pending');
                        })
                            ->where('product_id', $product->id)
                            ->where('variant_value', $stock->variant_value)
                            ->sum('quantity');

                        $availableStock = max(0, ($stock->available_stock ?? 0) - $pendingQty);

                        $items[] = [
                            'id' => $product->id . '::' . $stock->variant_value,
                            'product_id' => $product->id,
                            'variant_value' => $stock->variant_value,
                            'name' => $product->name . ' (' . $stock->variant_value . ')',
                            'code' => $product->code,
                            'image' => $product->image ?? '',
                            'category' => $product->category->name ?? 'N/A',
                            'distributor_price' => $priceRecord->distributor_price ?? $priceRecord->wholesale_price ?? 0,
                            'stock' => $availableStock,
                            'pending' => $pendingQty,
                        ];
                    }
                } else {
                    foreach ($product->stocks as $stock) {
                        $priceRecord = $product->prices->firstWhere('variant_value', $stock->variant_value) ?? $product->price;

                        $pendingQty = SaleItem::whereHas('sale', function ($q) {
                            $q->where('status', 'pending');
                        })
                            ->where('product_id', $product->id)
                            ->where('variant_value', $stock->variant_value)
                            ->sum('quantity');

                        $availableStock = max(0, ($stock->available_stock ?? 0) - $pendingQty);

                        $items[] = [
                            'id' => $product->id . '::' . $stock->variant_value,
                            'product_id' => $product->id,
                            'variant_value' => $stock->variant_value,
                            'name' => $product->name . ' (' . $stock->variant_value . ')',
                            'code' => $product->code,
                            'image' => $product->image ?? '',
                            'category' => $product->category->name ?? 'N/A',
                            'distributor_price' => $priceRecord->distributor_price ?? $priceRecord->wholesale_price ?? 0,
                            'stock' => $availableStock,
                            'pending' => $pendingQty,
                        ];
                    }
                }
            } else {
                // Single product without variants
                $priceRecord = $product->price;
                $stockQty = $product->stock->available_stock ?? 0;

                $pendingQty = SaleItem::whereHas('sale', function ($q) {
                    $q->where('status', 'pending');
                })
                    ->where('product_id', $product->id)
                    ->sum('quantity');

                $availableStock = max(0, $stockQty - $pendingQty);

                $items[] = [
                    'id' => $product->id,
                    'product_id' => $product->id,
                    'variant_value' => null,
                    'name' => $product->name,
                    'code' => $product->code,
                    'image' => $product->image ?? '',
                    'category' => $product->category->name ?? 'N/A',
                    'distributor_price' => $priceRecord->distributor_price ?? $priceRecord->wholesale_price ?? 0,
                    'stock' => $availableStock,
                    'pending' => $pendingQty,
                ];
            }
        }

        // Filter items by search term (including variant values)
        if ($this->search) {
            $searchTerm = strtolower($this->search);
            $items = array_filter($items, function ($item) use ($searchTerm) {
                return str_contains(strtolower($item['name']), $searchTerm)
                    || str_contains(strtolower($item['code']), $searchTerm)
                    || (isset($item['variant_value']) && str_contains(strtolower($item['variant_value']), $searchTerm));
            });
        }

        return $items;
    }

    public function render()
    {
        $items = collect($this->loadProducts());
        
        $page = $this->getPage('page');
        $perPage = 15;
        
        $products = new \Illuminate\Pagination\LengthAwarePaginator(
            $items->forPage($page, $perPage),
            $items->count(),
            $perPage,
            $page,
            [
                'path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );

        return view('livewire.salesman.salesman-product-list', [
            'products' => $products,
            'categories' => $this->categories,
        ]);
    }
}
