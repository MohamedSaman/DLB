<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithFileUploads;
use App\Models\Customer;
use App\Models\ProductDetail;
use App\Models\CategoryList;
use App\Models\BrandList;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Payment;
use App\Models\Cheque;
use App\Models\POSSession;
use App\Models\ProductStock;
use App\Models\ProductBatch;
use App\Services\FIFOStockService;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

#[Title('POS')]
#[Layout('components.layouts.app')]
class StoreBilling extends Component
{
    use WithFileUploads;

    // POS Session Management
    public $currentSession = null;
    public $showCloseRegisterModal = false;
    public $closeRegisterCash = 0;
    public $closeRegisterNotes = '';

    // Opening Cash Modal
    public $showOpeningCashModal = false;
    public $openingCashAmount = 0;

    // Session Summary Data
    public $sessionSummary = [];

    // Basic Properties
    public $search = '';
    public $searchResults = [];
    public $relatedProducts = [];
    public $customerId = '';

    // Categories and Products for Grid
    public $categories = [];
    public $selectedCategory = null;
    public $brands = [];
    public $selectedBrand = null;
    public $products = [];

    // UI: sliding panels for POS (opened via filter buttons)
    public $showCategoryPanel = false;
    public $showBrandPanel = false;

    // Cart Items
    public $cart = [];

    // Customer Properties
    public $customers = [];
    public $selectedCustomer = null;

    // Customer Form (for new customer - only used in modal)
    public $customerName = '';
    public $customerPhone = '';
    public $customerEmail = '';
    public $customerAddress = '';
    public $customerType = 'retail';
    public $businessName = '';
    public $customerOpeningBalance = 0;
    public $customerOverpaidAmount = 0;
    public $showCustomerMoreInfo = false;

    // Customer Balance Properties (for selected customer display)
    public $customerOpeningBalanceDisplay = 0;
    public $customerDueAmountDisplay = 0;
    public $customerOverpaidAmountDisplay = 0;
    public $customerTotalDueDisplay = 0;

    // Sale Properties
    public $notes = '';

    // Payment Properties
    public $paymentMethod = 'cash'; // 'cash', 'credit', 'cheque', 'bank_transfer'
    public $paidAmount = 0;

    // Cash Payment
    public $cashAmount = 0;

    // Cheque Payment
    public $cheques = [];
    public $tempChequeNumber = '';
    public $tempBankName = '';
    public $tempChequeDate = '';
    public $tempChequeAmount = 0;
    public $expandedChequeForm = false;

    // Bank Transfer Payment
    public $bankTransferAmount = 0;
    public $bankTransferBankName = '';
    public $bankTransferReferenceNumber = '';

    // Discount Properties
    public $additionalDiscount = 0;
    public $additionalDiscountType = 'fixed'; // 'fixed' or 'percentage'

    // Price Type Selection
    public $priceType = 'wholesale'; // 'retail', 'wholesale', or 'distribute'

    // View Type Selection
    public $productViewType = 'grid'; // 'grid' or 'list'

    // Modals
    public $showSaleModal = false;
    public $showCustomerModal = false;
    public $showPaymentConfirmModal = false;
    public $showPaymentModal = false;
    public $lastSaleId = null;
    public $createdSale = null;
    public $pendingDueAmount = 0;
    public $autoPrintAfterSale = false;

    // Payment Modal Properties
    public $amountReceived = 0;
    public $paymentNotes = '';

    // Edit Mode Properties
    public $editingSaleId = null;
    public $editingSale = null;

    public function mount()
    {
        // Check if editing an existing sale
        $editId = request()->query('edit');
        if ($editId) {
            $this->editingSaleId = $editId;
            $this->editingSale = Sale::with(['customer', 'items', 'payments'])->find($editId);

            if ($this->editingSale) {
                // Load sale data
                $this->customerId = $this->editingSale->customer_id;
                $this->selectedCustomer = $this->editingSale->customer;
                $this->notes = $this->editingSale->notes ?? '';

                // Load additional discount from sale
                if ($this->editingSale->additional_discount_type) {
                    $this->additionalDiscountType = $this->editingSale->additional_discount_type;
                    $this->additionalDiscount = $this->editingSale->additional_discount_percentage ?? 0;
                }

                // Load payment data from existing payments
                if ($this->editingSale->payments && $this->editingSale->payments->count() > 0) {
                    $payments = $this->editingSale->payments;

                    // Determine payment method based on payments
                    $paymentMethods = $payments->pluck('payment_method')->unique();

                    if ($paymentMethods->count() === 1) {
                        // Single payment method
                        $method = $paymentMethods->first();
                        $this->paymentMethod = $method;

                        if ($method === 'cash') {
                            $this->cashAmount = $payments->sum('amount');
                            $this->amountReceived = $this->cashAmount;
                        } elseif ($method === 'cheque') {
                            // Load cheques from database
                            $this->cheques = [];
                            foreach ($payments as $payment) {
                                $chequeRecords = Cheque::where('payment_id', $payment->id)->get();
                                foreach ($chequeRecords as $cheque) {
                                    $this->cheques[] = [
                                        'number' => $cheque->cheque_number,
                                        'bank_name' => $cheque->bank_name,
                                        'date' => $cheque->cheque_date,
                                        'amount' => $cheque->cheque_amount,
                                    ];
                                }
                            }
                        } elseif ($method === 'bank_transfer') {
                            $payment = $payments->first();
                            $this->bankTransferAmount = $payment->amount;
                            $this->bankTransferBankName = $payment->bank_name ?? '';
                            $this->bankTransferReferenceNumber = $payment->payment_reference ?? '';
                        } elseif ($method === 'credit') {
                            $this->paymentMethod = 'credit';
                        }
                    } elseif ($paymentMethods->count() > 1) {
                        // Multiple payment methods
                        $this->paymentMethod = 'multiple';

                        foreach ($payments as $payment) {
                            if ($payment->payment_method === 'cash') {
                                $this->cashAmount += $payment->amount;
                            } elseif ($payment->payment_method === 'cheque') {
                                // Load cheques
                                $chequeRecords = Cheque::where('payment_id', $payment->id)->get();
                                foreach ($chequeRecords as $cheque) {
                                    $this->cheques[] = [
                                        'number' => $cheque->cheque_number,
                                        'bank_name' => $cheque->bank_name,
                                        'date' => $cheque->cheque_date,
                                        'amount' => $cheque->cheque_amount,
                                    ];
                                }
                            } elseif ($payment->payment_method === 'bank_transfer') {
                                $this->bankTransferAmount += $payment->amount;
                                if (!$this->bankTransferBankName) {
                                    $this->bankTransferBankName = $payment->bank_name ?? '';
                                    $this->bankTransferReferenceNumber = $payment->payment_reference ?? '';
                                }
                            }
                        }

                        $this->amountReceived = $this->cashAmount;
                    }
                } else {
                    // No payments yet - likely credit sale or pending
                    if ($this->editingSale->payment_status === 'pending' || $this->editingSale->due_amount > 0) {
                        $this->paymentMethod = 'credit';
                    }
                }

                // Load cart items from sale with actual available stock
                $this->cart = [];
                foreach ($this->editingSale->items as $item) {
                    // Calculate actual available stock for this product
                    $stockQuery = ProductStock::where('product_id', $item->product_id);
                    if ($item->variant_value && $item->variant_value !== '' && $item->variant_value !== 'null') {
                        $stockQuery->where('variant_value', $item->variant_value);
                    } else {
                        $stockQuery->where(function ($q) {
                            $q->whereNull('variant_value')
                                ->orWhere('variant_value', '')
                                ->orWhere('variant_value', 'null');
                        })->whereNull('variant_id');
                    }
                    $stockRecord = $stockQuery->first();
                    $allocatedQty = $this->getAvailableStockForEdit($item->product_id, $item->variant_value ?? null) ?? 0;
                    $rawStock = $stockRecord ? ($stockRecord->available_stock ?? 0) : 0;
                    $availableStock = $rawStock + $allocatedQty;

                    $this->cart[] = [
                        'id' => $item->product_id,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product_name,
                        'key' => $item->product_id . '-' . $item->id,
                        'name' => $item->product_name,
                        'code' => $item->product_code ?? '',
                        'quantity' => $item->quantity,
                        'price' => $item->unit_price,
                        'discount' => $item->discount_per_unit ?? 0,
                        'total' => $item->total,
                        'variant_value' => $item->variant_value ?? '',
                        'variant_id' => $item->variant_id ?? null,
                        'model' => '',
                        'stock' => $availableStock,
                        'image' => '',
                        'retail_price' => $item->unit_price,
                        'wholesale_price' => 0,
                        'distributor_price' => 0,
                        'sale_item_id' => $item->id,
                    ];
                }
            }
        }
        // Check for any past open sessions - auto-close them
        $pastOpenSessions = POSSession::where('user_id', Auth::id())
            ->whereDate('session_date', '<', now()->toDateString())
            ->where('status', 'open')
            ->get();

        foreach ($pastOpenSessions as $pastSession) {
            // Auto-close past session
            try {
                DB::beginTransaction();

                // Calculate that session's summary
                $sessionDate = $pastSession->session_date->toDateString();

                // Get POS sales for that date
                $dateSales = Sale::whereDate('created_at', $sessionDate)
                    ->where('sale_type', 'pos')
                    ->pluck('id');

                $cashPayments = Payment::whereIn('sale_id', $dateSales)
                    ->where('payment_method', 'cash')
                    ->sum('amount');

                $totalSales = Sale::whereDate('created_at', $sessionDate)
                    ->where('sale_type', 'pos')
                    ->sum('total_amount');

                $expenses = DB::table('expenses')
                    ->whereDate('date', $sessionDate)
                    ->sum('amount');

                $refunds = DB::table('returns_products')
                    ->whereDate('created_at', $sessionDate)
                    ->sum('total_amount');

                $deposits = DB::table('deposits')
                    ->whereDate('date', $sessionDate)
                    ->sum('amount');

                // Calculate expected closing cash
                $expectedClosingCash = $pastSession->opening_cash + $cashPayments - $expenses - $refunds - $deposits;

                // Close the session
                $pastSession->update([
                    'closing_cash' => $expectedClosingCash,
                    'total_sales' => $totalSales,
                    'cash_sales' => $cashPayments,
                    'expenses' => $expenses,
                    'refunds' => $refunds,
                    'cash_deposit_bank' => $deposits,
                    'status' => 'closed',
                    'closed_at' => now(),
                    'notes' => 'Auto-closed (past open session)',
                ]);

                DB::commit();

                Log::info("Auto-closed past POS session (date: {$sessionDate}) for user: " . Auth::id());
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Failed to auto-close past session (date: {$sessionDate}): " . $e->getMessage());
            }
        }

        // Check for open session
        $this->currentSession = POSSession::getTodaySession(Auth::id());

        // If no session exists OR session is closed, auto-create with 0 cash in hand
        if (!$this->currentSession || $this->currentSession->isClosed()) {
            // Always start with 0 cash in hand for each day
            $this->openingCashAmount = 0;

            // Auto-submit opening cash with 0 amount
            try {
                DB::beginTransaction();

                // Check if a closed session exists for today
                $existingSession = POSSession::where('user_id', Auth::id())
                    ->whereDate('session_date', now()->toDateString())
                    ->where('status', 'closed')
                    ->first();

                if ($existingSession) {
                    // Reopen existing closed session with 0 opening cash
                    $existingSession->update([
                        'status' => 'open',
                        'opening_cash' => 0,
                        'closed_at' => null,
                    ]);
                    $this->currentSession = $existingSession;
                } else {
                    // Create new POS session with 0 opening cash
                    $this->currentSession = POSSession::openSession(Auth::id(), 0);
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Failed to auto-initialize POS session: ' . $e->getMessage());
            }
        }

        $this->loadCustomers();
        if (!$this->editingSaleId) {
            $this->setDefaultCustomer();
        }
        $this->loadCategories();
        $this->loadBrands();
        $this->loadProducts();
        $this->tempChequeDate = now()->format('Y-m-d');
    }

    /**
     * Load Categories for sidebar
     */
    public function loadCategories()
    {
        $this->categories = CategoryList::withCount(['products' => function ($query) {
            $query->whereHas('stock', function ($q) {
                $q->where('available_stock', '>', 0);
            });
        }])->get();
    }

    /**
     * Load Products for grid view
     */
    public function loadProducts()
    {
        // Fetch products which have stock either as single stock record or variant stocks
        $query = ProductDetail::with(['stock', 'price', 'category', 'variant', 'stocks', 'prices'])
            ->where(function ($q) {
                $q->whereHas('stock', function ($sq) {
                    $sq->where('available_stock', '>', 0);
                })->orWhereHas('stocks', function ($sq) {
                    $sq->where('available_stock', '>', 0);
                });
            });

        if ($this->selectedCategory) {
            $query->where('category_id', $this->selectedCategory);
        }

        if ($this->selectedBrand) {
            $query->where('brand_id', $this->selectedBrand);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('code', 'like', '%' . $this->search . '%')
                    ->orWhere('model', 'like', '%' . $this->search . '%');
            });
        }

        $items = [];

        $products = $query->take(50)->get(); // fetch more to allow expansion into variants

        foreach ($products as $product) {
            // If product has variant stocks/values, expand each variant as its own product entry
            if (($product->variant_id ?? null) !== null && $product->stocks && $product->stocks->isNotEmpty()) {
                // Use the variant's stored values order if available
                $orderedValues = [];
                if ($product->variant && is_array($product->variant->variant_values) && count($product->variant->variant_values) > 0) {
                    $orderedValues = $product->variant->variant_values;
                }

                // Map stocks by variant_value for quick lookup
                $stocksByValue = [];
                foreach ($product->stocks as $stock) {
                    if (($stock->available_stock ?? 0) <= 0) continue;
                    $stocksByValue[$stock->variant_value] = $stock;
                }

                if (!empty($orderedValues)) {
                    // Add variants in the DB-stored order
                    foreach ($orderedValues as $val) {
                        if (!isset($stocksByValue[$val])) continue;
                        $stock = $stocksByValue[$val];

                        // find matching price record for this variant value if exists
                        $priceRecord = $product->prices->firstWhere('variant_value', $stock->variant_value) ?? $product->price;

                        $priceValue = $this->getPriceValue($priceRecord);

                        // Calculate pending quantity for this variant
                        $pendingQty = SaleItem::whereHas('sale', function ($q) {
                            $q->where('status', 'pending');
                        })
                            ->where('product_id', $product->id)
                            ->where('variant_value', $stock->variant_value)
                            ->sum('quantity');

                        $availableStock = max(0, ($stock->available_stock ?? 0) - $pendingQty);

                        $items[] = [
                            'id' => $product->id . '::' . $stock->variant_value, // unique id per variant
                            'product_id' => $product->id,
                            'variant_id' => $stock->variant_id,
                            'variant_value' => $stock->variant_value,
                            'name' => $product->name . ' (' . $stock->variant_value . ')',
                            'code' => $product->code,
                            'model' => $product->model,
                            'price' => $priceValue,
                            'retail_price' => $priceRecord->retail_price ?? 0,
                            'wholesale_price' => $priceRecord->wholesale_price ?? 0,
                            'stock' => $availableStock,
                            'pending' => $pendingQty,
                            'image' => $product->image ?? '',
                        ];
                    }

                    // Append any stocks not listed in variant_values
                    foreach ($stocksByValue as $v => $stock) {
                        if (in_array($v, $orderedValues)) continue;
                        $priceRecord = $product->prices->firstWhere('variant_value', $stock->variant_value) ?? $product->price;
                        $priceValue = $this->getPriceValue($priceRecord);

                        // Calculate pending quantity for this variant
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
                            'variant_id' => $stock->variant_id,
                            'variant_value' => $stock->variant_value,
                            'name' => $product->name . ' (' . $stock->variant_value . ')',
                            'code' => $product->code,
                            'model' => $product->model,
                            'price' => $priceValue,
                            'retail_price' => $priceRecord->retail_price ?? 0,
                            'wholesale_price' => $priceRecord->wholesale_price ?? 0,
                            'distributor_price' => $priceRecord->distributor_price ?? 0,
                            'stock' => $availableStock,
                            'pending' => $pendingQty,
                            'image' => $product->image ?? '',
                        ];
                    }
                } else {
                    foreach ($product->stocks as $stock) {
                        if (($stock->available_stock ?? 0) <= 0) continue;

                        // find matching price record for this variant value if exists
                        $priceRecord = $product->prices->firstWhere('variant_value', $stock->variant_value) ?? $product->price;

                        $priceValue = $this->getPriceValue($priceRecord);

                        // Calculate pending quantity for this variant
                        $pendingQty = SaleItem::whereHas('sale', function ($q) {
                            $q->where('status', 'pending');
                        })
                            ->where('product_id', $product->id)
                            ->where('variant_value', $stock->variant_value)
                            ->sum('quantity');

                        $availableStock = max(0, ($stock->available_stock ?? 0) - $pendingQty);

                        $items[] = [
                            'id' => $product->id . '::' . $stock->variant_value, // unique id per variant
                            'product_id' => $product->id,
                            'variant_id' => $stock->variant_id,
                            'variant_value' => $stock->variant_value,
                            'name' => $product->name . ' (' . $stock->variant_value . ')',
                            'code' => $product->code,
                            'model' => $product->model,
                            'price' => $priceValue,
                            'retail_price' => $priceRecord->retail_price ?? 0,
                            'wholesale_price' => $priceRecord->wholesale_price ?? 0,
                            'distributor_price' => $priceRecord->distributor_price ?? 0,
                            'stock' => $availableStock,
                            'pending' => $pendingQty,
                            'image' => $product->image ?? '',
                        ];
                    }
                }
            } else {
                // Single-priced product (or variant-less), display normally
                $priceRecord = $product->price;
                $priceValue = $this->getPriceValue($priceRecord);

                $stockQty = $product->stock->available_stock ?? 0;

                // Calculate pending quantity for non-variant products
                $pendingQty = SaleItem::whereHas('sale', function ($q) {
                    $q->where('status', 'pending');
                })
                    ->where('product_id', $product->id)
                    ->sum('quantity');

                $availableStock = max(0, $stockQty - $pendingQty);

                // If a product has variant configuration but no variant stocks, fall back to single view
                $items[] = [
                    'id' => $product->id,
                    'product_id' => $product->id,
                    'variant_id' => $product->variant_id,
                    'variant_value' => null,
                    'name' => $product->name,
                    'code' => $product->code,
                    'model' => $product->model,
                    'price' => $priceValue,
                    'retail_price' => $priceRecord->retail_price ?? 0,
                    'wholesale_price' => $priceRecord->wholesale_price ?? 0,
                    'distributor_price' => $priceRecord->distributor_price ?? 0,
                    'stock' => $availableStock,
                    'pending' => $pendingQty,
                    'image' => $product->image ?? '',
                ];
            }
        }

        // Check for multiple batches with different prices and split items if needed
        $finalItems = [];
        foreach ($items as $item) {
            $productId = $item['product_id'];
            $variantId = $item['variant_id'] ?? null;
            $variantValue = $item['variant_value'] ?? null;

            // Get batch details for this product/variant
            $batches = FIFOStockService::getBatchDetails($productId, $variantId, $variantValue);

            // Determine which price field to use based on priceType
            $priceField = match ($this->priceType) {
                'retail' => 'retail_price',
                'wholesale' => 'wholesale_price',
                'distribute' => 'distributor_price',
                default => 'wholesale_price',
            };

            // Group batches by price
            $batchesByPrice = [];
            foreach ($batches as $batch) {
                $price = $batch[$priceField] ?? 0;
                if (!isset($batchesByPrice[$price])) {
                    $batchesByPrice[$price] = [
                        'quantity' => 0,
                        'batch_numbers' => [],
                    ];
                }
                $batchesByPrice[$price]['quantity'] += $batch['remaining_quantity'];
                $batchesByPrice[$price]['batch_numbers'][] = $batch['batch_number'];
            }

            // If multiple different prices exist, split into separate items
            if (count($batchesByPrice) > 1) {
                // Calculate pending once for this product (applies to all price variants)
                $pendingQty = SaleItem::whereHas('sale', function ($q) {
                    $q->where('status', 'pending');
                })
                    ->where('product_id', $productId)
                    ->when($variantValue, function ($q) use ($variantValue) {
                        return $q->where('variant_value', $variantValue);
                    })
                    ->sum('quantity');

                foreach ($batchesByPrice as $price => $info) {
                    $finalItems[] = [
                        'id' => $item['id'] . '_price_' . $price,
                        'product_id' => $productId,
                        'variant_id' => $variantId,
                        'variant_value' => $variantValue,
                        'name' => $item['name'],
                        'code' => $item['code'],
                        'model' => $item['model'] ?? null,
                        'price' => $price,
                        'retail_price' => $item['retail_price'] ?? 0,
                        'wholesale_price' => $item['wholesale_price'] ?? 0,
                        'distributor_price' => $item['distributor_price'] ?? 0,
                        'stock' => $info['quantity'],
                        'pending' => $pendingQty,
                        'image' => $item['image'] ?? '',
                        'batch_numbers' => $info['batch_numbers'],
                    ];
                }
            } else {
                // Single price, add as-is
                $finalItems[] = $item;
            }
        }

        // Limit to 20 items to keep UI performant
        $this->products = array_values(array_slice($finalItems, 0, 20));
    }

    /**
     * Select a category
     */
    public function selectCategory($categoryId)
    {
        // Set selected category and refresh product list
        $this->selectedCategory = $categoryId;
        $this->loadProducts();

        // Close the sliding category panel automatically
        $this->showCategoryPanel = false;
    }

    /**
     * Show all products (clear category filter)
     */
    public function showAllProducts()
    {
        $this->selectedCategory = null;
        $this->selectedBrand = null;
        $this->loadProducts();

        // Close the panel when user chooses "All Products"
        $this->showCategoryPanel = false;
    }

    // Toggle the sliding category panel
    public function toggleCategoryPanel()
    {
        $this->showCategoryPanel = ! $this->showCategoryPanel;
        // Close brand panel if open
        if ($this->showCategoryPanel) {
            $this->showBrandPanel = false;
        }
    }

    /**
     * Load Brands for sidebar
     */
    public function loadBrands()
    {
        // Get unique brands from products that have stock
        $this->brands = BrandList::whereHas('products', function ($q) {
            $q->where(function ($query) {
                $query->whereHas('stock', function ($sq) {
                    $sq->where('available_stock', '>', 0);
                })->orWhereHas('stocks', function ($sq) {
                    $sq->where('available_stock', '>', 0);
                });
            });
        })
            ->withCount(['products' => function ($q) {
                $q->where(function ($query) {
                    $query->whereHas('stock', function ($sq) {
                        $sq->where('available_stock', '>', 0);
                    })->orWhereHas('stocks', function ($sq) {
                        $sq->where('available_stock', '>', 0);
                    });
                });
            }])
            ->get()
            ->map(function ($brand) {
                return [
                    'id' => $brand->id,
                    'brand_name' => $brand->brand_name,
                    'products_count' => $brand->products_count
                ];
            });
    }

    /**
     * Select a brand
     */
    public function selectBrand($brandId)
    {
        // Set selected brand and refresh product list
        $this->selectedBrand = $brandId;
        $this->selectedCategory = null; // Clear category filter
        $this->loadProducts();

        // Close the sliding brand panel automatically
        $this->showBrandPanel = false;
    }

    /**
     * Show all products (clear brand filter)
     */
    public function showAllBrands()
    {
        $this->selectedBrand = null;
        $this->selectedCategory = null;
        $this->loadProducts();

        // Close the panel
        $this->showBrandPanel = false;
    }

    // Toggle the sliding brand panel
    public function toggleBrandPanel()
    {
        $this->showBrandPanel = ! $this->showBrandPanel;
        // Close category panel if open
        if ($this->showBrandPanel) {
            $this->showCategoryPanel = false;
        }
    }

    /**
     * Update Cash in Hands Table
     * Add for cash payments, subtract for expenses
     */
    private function updateCashInHands($amount)
    {
        // Update cash_amount record
        $cashAmountRecord = DB::table('cash_in_hands')->where('key', 'cash_amount')->first();

        if ($cashAmountRecord) {
            // Update existing record
            DB::table('cash_in_hands')
                ->where('key', 'cash_amount')
                ->update([
                    'value' => $cashAmountRecord->value + $amount,
                    'updated_at' => now()
                ]);
        } else {
            // Create new record
            DB::table('cash_in_hands')->insert([
                'key' => 'cash_amount',
                'value' => $amount,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // Also update cash_in_hand record
        $cashInHandRecord = DB::table('cash_in_hands')->where('key', 'cash in hand')->first();

        if ($cashInHandRecord) {
            // Update existing record
            DB::table('cash_in_hands')
                ->where('key', 'cash in hand')
                ->update([
                    'value' => $cashInHandRecord->value + $amount,
                    'updated_at' => now()
                ]);
        } else {
            // Create new record
            DB::table('cash_in_hands')->insert([
                'key' => 'cash in hand',
                'value' => $amount,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }

    // Set default walking customer
    public function setDefaultCustomer()
    {
        // Find or create walking customer (only one)
        $walkingCustomer = Customer::where('name', 'Walking Customer')->first();

        if (!$walkingCustomer) {
            $walkingCustomer = Customer::create([
                'name' => 'Walking Customer',
                'phone' => 'xxxxx', // Empty phone number
                'email' => null,
                'address' => 'xxxxx',
                'type' => 'retail',
                'business_name' => null,
            ]);

            $this->loadCustomers(); // Reload customers after creating new one
        }

        $this->customerId = $walkingCustomer->id;
        $this->selectedCustomer = $walkingCustomer;
    }

    // Load customers for dropdown
    public function loadCustomers()
    {
        $this->customers = Customer::orderBy('business_name')->get();
    }

    // Computed Properties for Totals
    public function getSubtotalProperty()
    {
        return collect($this->cart)->sum('total');
    }

    public function getTotalDiscountProperty()
    {
        return collect($this->cart)->sum(function ($item) {
            return ($item['discount'] * $item['quantity']);
        });
    }

    public function getTotalDiscountPercentageProperty()
    {
        $subtotalBeforeDiscount = collect($this->cart)->sum(function ($item) {
            return ($item['price'] * $item['quantity']);
        });

        if ($subtotalBeforeDiscount == 0) {
            return 0;
        }

        // Get item discounts total
        $itemDiscountsTotal = $this->totalDiscount;

        // Get additional discount amount
        $additionalDiscountTotal = $this->additionalDiscountAmount;

        // Combined total discount
        $combinedDiscount = $itemDiscountsTotal + $additionalDiscountTotal;

        // Calculate percentage
        return ($combinedDiscount / $subtotalBeforeDiscount) * 100;
    }

    public function getSubtotalAfterItemDiscountsProperty()
    {
        return $this->subtotal;
    }

    public function getAdditionalDiscountAmountProperty()
    {
        if (empty($this->additionalDiscount) || $this->additionalDiscount <= 0) {
            return 0;
        }

        if ($this->additionalDiscountType === 'percentage') {
            return ($this->subtotalAfterItemDiscounts * $this->additionalDiscount) / 100;
        }

        return min($this->additionalDiscount, $this->subtotalAfterItemDiscounts);
    }

    public function getGrandTotalProperty()
    {
        return $this->subtotalAfterItemDiscounts - $this->additionalDiscountAmount;
    }

    public function getTotalPaidAmountProperty()
    {
        $total = 0;

        if ($this->paymentMethod === 'cash') {
            $total = $this->cashAmount;
        } elseif ($this->paymentMethod === 'cheque') {
            $total = collect($this->cheques)->sum('amount');
        } elseif ($this->paymentMethod === 'bank_transfer') {
            $total = $this->bankTransferAmount;
        } elseif ($this->paymentMethod === 'multiple') {
            $total = $this->cashAmount + collect($this->cheques)->sum('amount');
        }

        return $total;
    }

    public function getDueAmountProperty()
    {
        if ($this->paymentMethod === 'credit') {
            return $this->grandTotal;
        }
        return max(0, $this->grandTotal - floatval($this->totalPaidAmount));
    }

    public function getPaymentStatusProperty()
    {
        if ($this->paymentMethod === 'credit' || floatval($this->totalPaidAmount) <= 0) {
            return 'pending';
        } elseif (floatval($this->totalPaidAmount) >= $this->grandTotal) {
            return 'paid';
        } else {
            return 'partial';
        }
    }

    // Determine payment_type for database (must be 'full' or 'partial')
    public function getDatabasePaymentTypeProperty()
    {
        if ($this->paymentMethod === 'credit') {
            return 'partial';
        }
        if (floatval($this->totalPaidAmount) >= $this->grandTotal) {
            return 'full';
        } else {
            return 'partial';
        }
    }

    // When customer is selected from dropdown
    public function updatedCustomerId($value)
    {
        if ($value) {
            $customer = Customer::find($value);
            if ($customer) {
                $this->selectedCustomer = $customer;
                // Load balance information
                $this->customerOpeningBalanceDisplay = $customer->opening_balance ?? 0;
                $this->customerDueAmountDisplay = $customer->due_amount ?? 0;
                $this->customerOverpaidAmountDisplay = $customer->overpaid_amount ?? 0;
                $this->customerTotalDueDisplay = $customer->total_due ?? 0;
            }
        } else {
            // If customer is deselected, set back to walking customer
            $this->setDefaultCustomer();
            // Reset balance displays
            $this->customerOpeningBalanceDisplay = 0;
            $this->customerDueAmountDisplay = 0;
            $this->customerOverpaidAmountDisplay = 0;
            $this->customerTotalDueDisplay = 0;
        }
    }

    // When payment method changes
    public function updatedPaymentMethod($value)
    {
        // Reset all payment fields
        $this->cashAmount = 0;
        $this->amountReceived = 0;
        $this->cheques = [];
        $this->bankTransferAmount = 0;
        $this->bankTransferBankName = '';
        $this->bankTransferReferenceNumber = '';
        $this->tempChequeDate = now()->format('Y-m-d');
        $this->tempChequeAmount = 0;

        if ($value === 'cash') {
            $this->cashAmount = $this->grandTotal;
            $this->amountReceived = $this->grandTotal;
        } elseif ($value === 'bank_transfer') {
            $this->bankTransferAmount = $this->grandTotal;
        } elseif ($value === 'cheque') {
            $this->tempChequeAmount = $this->grandTotal;
        } elseif ($value === 'multiple') {
            // When multiple payments selected, default the cash portion to remaining after any cheques
            $this->cashAmount = max(0, $this->grandTotal - collect($this->cheques)->sum('amount'));
        }
    }

    // Helper method to get price based on price type
    public function getPriceValue($priceRecord)
    {
        return match ($this->priceType) {
            'retail' => $priceRecord->retail_price ?? 0,
            'wholesale' => $priceRecord->wholesale_price ?? 0,
            'distribute' => $priceRecord->distributor_price ?? $priceRecord->wholesale_price ?? 0,
            default => $priceRecord->retail_price ?? 0,
        };
    }

    // When price type changes
    public function updatedPriceType($value)
    {
        $this->loadProducts();
    }

    // Auto-update cash amount when cart changes (if payment method is cash)
    public function updated($propertyName)
    {
        // If cart or discount changes, update payment amounts
        if (
            str_contains($propertyName, 'cart') ||
            str_contains($propertyName, 'additionalDiscount') ||
            str_contains($propertyName, 'additionalDiscountType')
        ) {

            if ($this->paymentMethod === 'cash') {
                $this->cashAmount = $this->grandTotal;
            } elseif ($this->paymentMethod === 'bank_transfer') {
                $this->bankTransferAmount = $this->grandTotal;
            } elseif ($this->paymentMethod === 'multiple') {
                $this->cashAmount = $this->grandTotal;
            }
        }
    }

    // Add Cheque
    public function addCheque()
    {
        $this->validate([
            'tempChequeNumber' => 'required|string|max:50',
            'tempBankName' => 'required|string|max:100',
            'tempChequeDate' => 'required|date',
            'tempChequeAmount' => 'required|numeric|min:0.01',
        ], [
            'tempChequeNumber.required' => 'Cheque number is required',
            'tempBankName.required' => 'Bank name is required',
            'tempChequeDate.required' => 'Cheque date is required',
            'tempChequeAmount.required' => 'Cheque amount is required',
            'tempChequeAmount.min' => 'Cheque amount must be greater than 0',
        ]);

        // Check if cheque number already exists
        $existingCheque = Cheque::where('cheque_number', $this->tempChequeNumber)->first();
        if ($existingCheque) {
            $this->showToast('error', 'Cheque number already exists. Please use a different cheque number.');
            return;
        }

        // Add cheque without restricting to grand total (allow overpayment)
        $this->cheques[] = [
            'number' => $this->tempChequeNumber,
            'bank_name' => $this->tempBankName,
            'date' => $this->tempChequeDate,
            'amount' => $this->tempChequeAmount,
        ];

        // Reset temporary fields and collapse form
        $this->tempChequeNumber = '';
        $this->tempBankName = '';
        $this->tempChequeDate = now()->format('Y-m-d');
        $this->tempChequeAmount = 0;
        $this->expandedChequeForm = false;

        // If using multiple payments, adjust cash amount to remaining after cheques
        if ($this->paymentMethod === 'multiple') {
            $remaining = max(0, $this->grandTotal - collect($this->cheques)->sum('amount'));
            $this->cashAmount = $remaining;
        }

        $this->showToast('success', 'Cheque added successfully!');
    }

    // Remove Cheque
    public function removeCheque($index)
    {
        unset($this->cheques[$index]);
        $this->cheques = array_values($this->cheques);

        // If using multiple payments, adjust cash amount to remaining after cheques
        if ($this->paymentMethod === 'multiple') {
            $remaining = max(0, $this->grandTotal - collect($this->cheques)->sum('amount'));
            $this->cashAmount = $remaining;
        }

        $this->showToast('success', 'Cheque removed successfully!');
    }

    // Toggle Cheque Form Visibility
    public function toggleChequeForm()
    {
        $this->expandedChequeForm = !$this->expandedChequeForm;
        if (!$this->expandedChequeForm) {
            // Reset form when closing
            $this->tempChequeNumber = '';
            $this->tempBankName = '';
            $this->tempChequeDate = now()->format('Y-m-d');
            $this->tempChequeAmount = 0;
        }
    }

    // Livewire hook when cheques array updates
    public function updatedCheques($value)
    {
        if ($this->paymentMethod === 'multiple') {
            $this->cashAmount = max(0, $this->grandTotal - collect($this->cheques)->sum('amount'));
        }
    }

    // Clamp cash amount when user edits it
    public function updatedCashAmount($value)
    {
        // Ensure cashAmount is numeric
        $value = floatval($value ?? 0);

        // Allow overpayment - don't restrict cash amount
        if ($value < 0) {
            $this->cashAmount = 0;
        } else {
            $this->cashAmount = $value;
        }
    }

    // Cancel Cheque Form
    public function cancelChequeForm()
    {
        $this->expandedChequeForm = false;
        $this->tempChequeNumber = '';
        $this->tempBankName = '';
        $this->tempChequeDate = now()->format('Y-m-d');
        $this->tempChequeAmount = 0;
    }

    // Reset customer fields
    public function resetCustomerFields()
    {
        $this->customerName = '';
        $this->customerPhone = '';
        $this->customerEmail = '';
        $this->customerAddress = '';
        $this->customerType = 'retail';
        $this->businessName = '';
        $this->customerOpeningBalance = 0;
        $this->customerOverpaidAmount = 0;
        $this->showCustomerMoreInfo = false;
    }

    // Open customer modal
    public function openCustomerModal()
    {
        $this->resetCustomerFields();
        $this->showCustomerModal = true;
    }

    // Close customer modal
    public function closeCustomerModal()
    {
        $this->showCustomerModal = false;
        $this->resetCustomerFields();
    }

    // Create new customer
    public function createCustomer()
    {
        $this->validate([
            'customerName' => 'required|string|max:255',
            'customerPhone' => 'required|string|regex:/^[0-9\s,\/\-\+]+$/',
            'customerEmail' => 'nullable|email|unique:customers,email',
            'customerAddress' => 'nullable|string',
            'customerType' => 'required|in:retail,wholesale,distributor',
            'customerOpeningBalance' => 'nullable|numeric|min:0',
            'customerOverpaidAmount' => 'nullable|numeric|min:0',
        ]);

        try {
            $openingBalance = floatval($this->customerOpeningBalance ?? 0);
            $overpaidAmount = floatval($this->customerOverpaidAmount ?? 0);
            $totalDue = $openingBalance;

            $customer = Customer::create([
                'name' => $this->customerName,
                'phone' => $this->customerPhone,
                'email' => $this->customerEmail,
                'address' => $this->customerAddress,
                'type' => $this->customerType,
                'business_name' => $this->businessName,
                'user_id' => Auth::id(),
                'opening_balance' => $openingBalance,
                'due_amount' => 0,
                'total_due' => $totalDue,
                'overpaid_amount' => $overpaidAmount,
            ]);

            $this->loadCustomers();
            $this->customerId = $customer->id;
            $this->selectedCustomer = $customer;
            // Load balance information
            $this->customerOpeningBalanceDisplay = $openingBalance;
            $this->customerDueAmountDisplay = 0;
            $this->customerOverpaidAmountDisplay = $overpaidAmount;
            $this->customerTotalDueDisplay = $totalDue;
            $this->closeCustomerModal();
            $this->resetCustomerFields();

            // Show success toast notification
            $this->showToast('success', 'Customer "' . $customer->name . '" created successfully!');
        } catch (\Exception $e) {
            $this->showToast('error', 'Failed to create customer: ' . $e->getMessage());
        }
    }

    // Search Products
    public function updatedSearch()
    {
        if (strlen($this->search) >= 2) {
            $searchTerm = trim($this->search);
            $searchWords = explode(' ', $searchTerm);

            $matches = ProductDetail::where(function ($query) use ($searchTerm, $searchWords) {
                // Search by full term in product fields
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'like', "%{$searchTerm}%")
                        ->orWhere('code', 'like', "%{$searchTerm}%")
                        ->orWhere('model', 'like', "%{$searchTerm}%");
                })
                    // OR search by full term in variant values
                    ->orWhereHas('stocks', function ($q) use ($searchTerm) {
                        $q->where('variant_value', 'like', "%{$searchTerm}%")
                            ->where('available_stock', '>', 0);
                    })
                    // OR if multiple words, search for combined product name + variant
                    ->when(count($searchWords) > 1, function ($q) use ($searchWords) {
                        $q->orWhere(function ($subQuery) use ($searchWords) {
                            foreach ($searchWords as $index => $word) {
                                if ($index === 0) {
                                    // First word should match product name
                                    $subQuery->where('name', 'like', "%{$word}%");
                                } else {
                                    // Subsequent words should match variant values
                                    $subQuery->whereHas('stocks', function ($stockQuery) use ($word) {
                                        $stockQuery->where('variant_value', 'like', "%{$word}%")
                                            ->where('available_stock', '>', 0);
                                    });
                                }
                            }
                        });
                    });
            })
                // Apply category filter if active
                ->when($this->selectedCategory, function ($query) {
                    return $query->where('category_id', $this->selectedCategory);
                })
                // Apply brand filter if active
                ->when($this->selectedBrand, function ($query) {
                    return $query->where('brand_id', $this->selectedBrand);
                })
                ->where(function ($q) {
                    $q->whereHas('stock', function ($sq) {
                        $sq->where('available_stock', '>', 0);
                    })->orWhereHas('stocks', function ($sq) {
                        $sq->where('available_stock', '>', 0);
                    });
                })
                ->with(['stock', 'price', 'category', 'stocks', 'prices'])
                ->limit(20)
                ->get();

            // Build expanded results: show each variant as its own result when available
            $results = [];
            foreach ($matches as $p) {
                // Expand variants (if present) — preserve DB-stored variant order when available
                if (($p->variant_id ?? null) !== null && isset($p->stocks) && $p->stocks->isNotEmpty()) {
                    $orderedValues = [];
                    if ($p->variant && is_array($p->variant->variant_values) && count($p->variant->variant_values) > 0) {
                        $orderedValues = $p->variant->variant_values;
                    }

                    // Filter stocks based on search: show all if product matched, or filter by variant if searching for specific variant
                    $searchTerm = trim($this->search);
                    $searchWords = explode(' ', $searchTerm);
                    $productMatched = (
                        mb_stripos($p->name, $searchWords[0]) !== false ||
                        mb_stripos($p->code, $searchTerm) !== false ||
                        (!empty($p->model) && mb_stripos($p->model, $searchTerm) !== false)
                    );

                    $stocksToShow = collect($p->stocks)->filter(function ($stock) use ($productMatched, $searchTerm, $searchWords, $p) {
                        if (($stock->available_stock ?? 0) <= 0) return false;

                        // Show all variants if product name/code matched
                        if ($productMatched && count($searchWords) == 1) return true;

                        // For multi-word search, check if combined name matches
                        if (count($searchWords) > 1) {
                            $combinedName = $p->name . ' ' . $stock->variant_value;
                            if (mb_stripos($combinedName, $searchTerm) !== false) return true;
                        }

                        // Show if variant value matches search term
                        return mb_stripos($stock->variant_value ?? '', $searchTerm) !== false;
                    })->values();

                    $stocksByValue = [];
                    foreach ($stocksToShow as $stock) {
                        $stocksByValue[$stock->variant_value] = $stock;
                    }

                    if (!empty($orderedValues)) {
                        foreach ($orderedValues as $val) {
                            if (!isset($stocksByValue[$val])) continue;
                            $stock = $stocksByValue[$val];
                            $priceRecord = $p->prices->firstWhere('variant_value', $stock->variant_value) ?? $p->price;
                            $priceValue = $this->getPriceValue($priceRecord);

                            // Calculate pending quantity for this variant
                            $pendingQty = SaleItem::whereHas('sale', function ($q) {
                                $q->where('status', 'pending');
                            })
                                ->where('product_id', $p->id)
                                ->where('variant_value', $stock->variant_value)
                                ->sum('quantity');

                            $availableStock = max(0, ($stock->available_stock ?? 0) - $pendingQty);

                            $results[] = [
                                'id' => $p->id . '::' . $stock->variant_value,
                                'product_id' => $p->id,
                                'variant_id' => $stock->variant_id,
                                'variant_value' => $stock->variant_value,
                                'name' => $p->name . ' ' . $stock->variant_value,
                                'code' => $p->code,
                                'image' => $p->image ?? '',
                                'price' => $priceValue,
                                'stock' => $availableStock,
                                'pending' => $pendingQty,
                            ];
                        }

                        // append any remaining stocks not in the variant list
                        foreach ($stocksByValue as $v => $stock) {
                            if (in_array($v, $orderedValues)) continue;
                            $priceRecord = $p->prices->firstWhere('variant_value', $stock->variant_value) ?? $p->price;
                            $priceValue = $this->getPriceValue($priceRecord);

                            // Calculate pending quantity for this variant
                            $pendingQty = SaleItem::whereHas('sale', function ($q) {
                                $q->where('status', 'pending');
                            })
                                ->where('product_id', $p->id)
                                ->where('variant_value', $stock->variant_value)
                                ->sum('quantity');

                            $availableStock = max(0, ($stock->available_stock ?? 0) - $pendingQty);

                            $results[] = [
                                'id' => $p->id . '::' . $stock->variant_value,
                                'product_id' => $p->id,
                                'variant_id' => $stock->variant_id,
                                'variant_value' => $stock->variant_value,
                                'name' => $p->name . ' ' . $stock->variant_value,
                                'code' => $p->code,
                                'image' => $p->image ?? '',
                                'price' => $priceValue,
                                'stock' => $availableStock,
                                'pending' => $pendingQty,
                            ];
                        }
                    } else {
                        foreach ($p->stocks as $stock) {
                            if (($stock->available_stock ?? 0) <= 0) continue;

                            $priceRecord = $p->prices->firstWhere('variant_value', $stock->variant_value) ?? $p->price;
                            $priceValue = $this->getPriceValue($priceRecord);

                            // Calculate pending quantity for this variant
                            $pendingQty = SaleItem::whereHas('sale', function ($q) {
                                $q->where('status', 'pending');
                            })
                                ->where('product_id', $p->id)
                                ->where('variant_value', $stock->variant_value)
                                ->sum('quantity');

                            $availableStock = max(0, ($stock->available_stock ?? 0) - $pendingQty);

                            $results[] = [
                                'id' => $p->id . '::' . $stock->variant_value,
                                'product_id' => $p->id,
                                'variant_id' => $stock->variant_id,
                                'variant_value' => $stock->variant_value,
                                'name' => $p->name . ' ' . $stock->variant_value,
                                'code' => $p->code,
                                'image' => $p->image ?? '',
                                'price' => $priceValue,
                                'stock' => $availableStock,
                                'pending' => $pendingQty,
                            ];
                        }
                    }
                } else {
                    // Check for multiple batches with different prices
                    $batches = FIFOStockService::getBatchDetails($p->id, null, null);

                    // Determine which price field to use based on priceType
                    $priceField = match ($this->priceType) {
                        'retail' => 'retail_price',
                        'wholesale' => 'wholesale_price',
                        'distribute' => 'distributor_price',
                        default => 'wholesale_price',
                    };

                    // Group batches by price
                    $batchesByPrice = [];
                    foreach ($batches as $batch) {
                        $price = $batch[$priceField] ?? 0;
                        if (!isset($batchesByPrice[$price])) {
                            $batchesByPrice[$price] = [
                                'quantity' => 0,
                                'batch_numbers' => [],
                            ];
                        }
                        $batchesByPrice[$price]['quantity'] += $batch['remaining_quantity'];
                        $batchesByPrice[$price]['batch_numbers'][] = $batch['batch_number'];
                    }

                    // Calculate pending quantity for non-variant products
                    $pendingQty = SaleItem::whereHas('sale', function ($q) {
                        $q->where('status', 'pending');
                    })
                        ->where('product_id', $p->id)
                        ->sum('quantity');

                    $availableStock = max(0, ($p->stock->available_stock ?? 0) - $pendingQty);

                    // If multiple different prices exist, split into separate items
                    if (count($batchesByPrice) > 1) {
                        foreach ($batchesByPrice as $price => $info) {
                            $results[] = [
                                'id' => $p->id . '_price_' . $price,
                                'product_id' => $p->id,
                                'variant_id' => $p->variant_id ?? null,
                                'variant_value' => null,
                                'name' => $p->name,
                                'code' => $p->code,
                                'image' => $p->image ?? '',
                                'price' => $price,
                                'stock' => min($info['quantity'], $availableStock),
                                'pending' => $pendingQty,
                                'batch_numbers' => $info['batch_numbers'],
                            ];
                        }
                    } else {
                        $results[] = [
                            'id' => $p->id,
                            'product_id' => $p->id,
                            'variant_id' => $p->variant_id ?? null,
                            'variant_value' => null,
                            'name' => $p->name,
                            'code' => $p->code,
                            'image' => $p->image ?? '',
                            'price' => $this->getPriceValue($p->price),
                            'stock' => $availableStock,
                            'pending' => $pendingQty,
                        ];
                    }
                }
            }

            // Show all search results in the scrollable panel (no limit)
            $this->searchResults = array_values($results);

            // Fetch Related Products (same category)
            if ($matches->isNotEmpty()) {
                $categoryIds = $matches->pluck('category_id')->unique();
                $matchedIds = $matches->pluck('id');

                $related = ProductDetail::whereIn('category_id', $categoryIds)
                    ->whereNotIn('id', $matchedIds)
                    ->where(function ($q) {
                        $q->whereHas('stock', function ($sq) {
                            $sq->where('available_stock', '>', 0);
                        })->orWhereHas('stocks', function ($sq) {
                            $sq->where('available_stock', '>', 0);
                        });
                    })
                    ->with(['stock', 'price'])
                    ->limit(6)
                    ->get();

                $this->relatedProducts = $related->map(function ($p) {
                    // Calculate pending quantity for related product
                    $pendingQty = SaleItem::whereHas('sale', function ($q) {
                        $q->where('status', 'pending');
                    })
                        ->where('product_id', $p->id)
                        ->sum('quantity');

                    $stockQty = $p->stock->available_stock ?? 0;
                    $availableStock = max(0, $stockQty - $pendingQty);

                    return [
                        'id' => $p->id,
                        'name' => $p->name,
                        'code' => $p->code,
                        'image' => $p->image ?? '',
                        'price' => $this->getPriceValue($p->price),
                        'stock' => $availableStock,
                        'pending' => $pendingQty,
                    ];
                })->toArray();
            } else {
                $this->relatedProducts = [];
            }
        } else {
            $this->searchResults = [];
            $this->relatedProducts = [];
            $this->loadProducts();
        }
    }

    /**
     * Get available stock accounting for quantities already in the current sale being edited
     */
    private function getAvailableStockForEdit($productId, $variantValue = null)
    {
        if (!$this->editingSaleId) {
            return null; // Not editing, use regular stock check
        }

        // Find how much of this product is already in the original sale
        $allocatedQty = SaleItem::where('sale_id', $this->editingSaleId)
            ->where('product_id', $productId)
            ->when($variantValue, function ($q) use ($variantValue) {
                return $q->where('variant_value', $variantValue);
            }, function ($q) {
                return $q->where(function ($sq) {
                    $sq->whereNull('variant_value')
                        ->orWhere('variant_value', '')
                        ->orWhere('variant_value', 'null');
                });
            })
            ->sum('quantity');

        return $allocatedQty;
    }

    // Add to Cart
    public function addToCart($product)
    {
        // Determine base product id (handles variant entries where 'product_id' is provided)
        $baseProductId = $product['product_id'] ?? $product['id'];
        $variantId = $product['variant_id'] ?? null;
        $variantValue = $product['variant_value'] ?? null;

        // Get allocated stock if editing
        $allocatedStock = $this->getAvailableStockForEdit($baseProductId, $variantValue) ?? 0;
        $availableStock = ($product['stock'] ?? 0) + $allocatedStock;

        // Check stock availability
        if ($availableStock <= 0) {
            $this->showToast('error', 'Not enough stock available!');
            return;
        }

        // Get batch numbers if product was split by batch
        $batchNumbers = $product['batch_numbers'] ?? null;

        // Create unique cart ID that includes batch info if present
        $cartId = $product['id'];
        if ($batchNumbers && !empty($batchNumbers)) {
            $cartId .= '_batches_' . implode('_', $batchNumbers);
        }

        $existing = collect($this->cart)->firstWhere('id', $cartId);

        if ($existing) {
            // Check if adding more exceeds stock (with edit mode adjustment)
            if (($existing['quantity'] + 1) > $availableStock) {
                $this->showToast('error', 'Not enough stock available!');
                return;
            }

            $this->cart = collect($this->cart)->map(function ($item) use ($cartId) {
                if ($item['id'] == $cartId) {
                    $item['quantity'] += 1;
                    $item['total'] = ($item['price'] - $item['discount']) * $item['quantity'];
                    // Ensure key exists
                    if (!isset($item['key'])) {
                        $item['key'] = uniqid('cart_');
                    }
                }
                return $item;
            })->toArray();
        } else {
            $discountPrice = ProductDetail::find($baseProductId)->price->discount_price ?? 0;

            $newItem = [
                'key' => uniqid('cart_'),  // Add unique key to maintain state
                'id' => $cartId, // unique id (if variant: productId::variantValue, if batches: add batch info)
                'product_id' => $baseProductId,
                'variant_id' => $variantId,
                'variant_value' => $variantValue,
                'name' => $product['name'],
                'code' => $product['code'] ?? null,
                'model' => $product['model'] ?? null,
                'price' => $product['price'],
                'quantity' => 1,
                'discount' => $discountPrice,
                'discount_type' => 'fixed',  // Default discount type
                'discount_percentage' => 0,  // Store percentage value if applicable
                'total' => $product['price'] - $discountPrice,
                'stock' => $product['stock'],
                'pending' => $product['pending'] ?? 0,
                'image' => $product['image'] ?? null,
                'batch_numbers' => $batchNumbers, // Store batch info for later reference
            ];

            // Prepend new item to the beginning of the cart so latest appears at top
            array_unshift($this->cart, $newItem);
        }

        $this->search = '';
        $this->searchResults = [];
        $this->relatedProducts = [];

        // Dispatch event so Alpine can focus the qty input of the newly added item (index 0)
        $this->dispatch('product-added-to-cart', index: 0);
    }

    // Update Quantity
    public function updateQuantity($index, $quantity)
    {
        if ($quantity < 1) $quantity = 1;

        $baseProductId = $this->cart[$index]['product_id'];
        $variantValue = $this->cart[$index]['variant_value'] ?? null;

        // Get allocated stock if editing
        $allocatedStock = $this->getAvailableStockForEdit($baseProductId, $variantValue) ?? 0;
        $availableStock = $this->cart[$index]['stock'] + $allocatedStock;

        if ($quantity > $availableStock) {
            $this->showToast('error', 'Not enough stock available! Maximum: ' . $availableStock);
            return;
        }

        $this->cart[$index]['quantity'] = $quantity;
        $this->cart[$index]['total'] = ($this->cart[$index]['price'] - $this->cart[$index]['discount']) * $quantity;

        // After qty update, focus price input
        $this->dispatch('qty-updated', index: $index);
    }

    // Increment Quantity
    public function incrementQuantity($index)
    {
        $currentQuantity = $this->cart[$index]['quantity'];
        $baseProductId = $this->cart[$index]['product_id'];
        $variantValue = $this->cart[$index]['variant_value'] ?? null;

        // Get allocated stock if editing
        $allocatedStock = $this->getAvailableStockForEdit($baseProductId, $variantValue) ?? 0;
        $availableStock = $this->cart[$index]['stock'] + $allocatedStock;

        if (($currentQuantity + 1) > $availableStock) {
            $this->showToast('error', 'Not enough stock available! Maximum: ' . $availableStock);
            return;
        }

        $this->cart[$index]['quantity'] += 1;
        $this->cart[$index]['total'] = ($this->cart[$index]['price'] - $this->cart[$index]['discount']) * $this->cart[$index]['quantity'];
    }

    // Decrement Quantity
    public function decrementQuantity($index)
    {
        if ($this->cart[$index]['quantity'] > 1) {
            $this->cart[$index]['quantity'] -= 1;
            $this->cart[$index]['total'] = ($this->cart[$index]['price'] - $this->cart[$index]['discount']) * $this->cart[$index]['quantity'];
        }
    }

    // Update Price
    public function updatePrice($index, $price)
    {
        if ($price < 0) $price = 0;

        $this->cart[$index]['price'] = $price;

        // If discount is percentage-based, recalculate the discount amount based on new price
        if (($this->cart[$index]['discount_type'] ?? 'fixed') === 'percentage' && ($this->cart[$index]['discount_percentage'] ?? 0) > 0) {
            $percentage = $this->cart[$index]['discount_percentage'];
            $this->cart[$index]['discount'] = round(($price * $percentage) / 100, 2);
        }

        $this->cart[$index]['total'] = ($price - $this->cart[$index]['discount']) * $this->cart[$index]['quantity'];

        // After price update, return focus to search input
        $this->dispatch('price-updated');
    }

    // Update Discount - Auto-detects "10" as Rs.10 or "10%" as 10%
    public function updateDiscount($index, $discount)
    {
        if (!isset($this->cart[$index])) return;

        $discountValue = trim($discount);

        // Check if percentage (contains %)
        if (str_contains($discountValue, '%')) {
            // Percentage discount
            $percentage = (float) str_replace('%', '', $discountValue);
            $percentage = max(0, min(100, $percentage));
            $discountAmount = ($this->cart[$index]['price'] * $percentage) / 100;

            // Store discount type and percentage
            $this->cart[$index]['discount_type'] = 'percentage';
            $this->cart[$index]['discount_percentage'] = $percentage;
        } else {
            // Fixed discount
            $discountAmount = max(0, (float)$discountValue);
            $discountAmount = min($discountAmount, $this->cart[$index]['price']);

            // Store discount type
            $this->cart[$index]['discount_type'] = 'fixed';
            $this->cart[$index]['discount_percentage'] = 0;
        }

        $this->cart[$index]['discount'] = round($discountAmount, 2);
        $this->cart[$index]['total'] = ($this->cart[$index]['price'] - $this->cart[$index]['discount']) * $this->cart[$index]['quantity'];
    }

    // Remove from Cart
    public function removeFromCart($index)
    {
        unset($this->cart[$index]);
        $this->cart = array_values($this->cart);
        $this->showToast('success', 'Product removed from sale!');
    }

    // Clear Cart
    public function clearCart()
    {
        $this->cart = [];
        $this->additionalDiscount = 0;
        $this->additionalDiscountType = 'fixed';
        $this->resetPaymentFields();
        $this->showToast('success', 'Cart cleared!');
    }

    // Reset payment fields
    public function resetPaymentFields()
    {
        $this->cashAmount = 0;
        $this->cheques = [];
        $this->bankTransferAmount = 0;
        $this->bankTransferBankName = '';
        $this->bankTransferReferenceNumber = '';
        $this->paymentMethod = 'cash';
    }

    // Update additional discount
    public function updatedAdditionalDiscount($value)
    {
        if ($value === '') {
            $this->additionalDiscount = 0;
            return;
        }

        if ($value < 0) {
            $this->additionalDiscount = 0;
            return;
        }

        if ($this->additionalDiscountType === 'percentage' && $value > 100) {
            $this->additionalDiscount = 100;
            return;
        }

        if ($this->additionalDiscountType === 'fixed' && $value > $this->subtotalAfterItemDiscounts) {
            $this->additionalDiscount = $this->subtotalAfterItemDiscounts;
            return;
        }
    }

    // ------------------ Sale Discount Modal ------------------
    public $showSaleDiscountModal = false;
    public $saleDiscountType = 'fixed';
    public $saleDiscountValue = 0;

    public function openSaleDiscountModal()
    {
        $this->saleDiscountType = $this->additionalDiscountType ?? 'fixed';
        $this->saleDiscountValue = 0; // Always reset to 0 when opening modal
        $this->showSaleDiscountModal = true;
    }

    public function applySaleDiscount()
    {
        // Validate based on discount type
        if ($this->saleDiscountType === 'percentage') {
            $this->validate([
                'saleDiscountValue' => 'required|numeric|min:0|max:100'
            ], [
                'saleDiscountValue.max' => 'Percentage discount cannot exceed 100%'
            ]);

            $this->additionalDiscountType = 'percentage';
            $this->additionalDiscount = $this->saleDiscountValue;
        } else {
            $this->validate([
                'saleDiscountValue' => 'required|numeric|min:0'
            ]);

            // For fixed amount, ensure it doesn't exceed subtotal
            if ($this->saleDiscountValue > $this->subtotalAfterItemDiscounts) {
                session()->flash('error', 'Discount amount cannot exceed total sale amount (Rs. ' . number_format($this->subtotalAfterItemDiscounts, 2) . ')');
                return;
            }

            $this->additionalDiscountType = 'fixed';
            $this->additionalDiscount = $this->saleDiscountValue;
        }

        $this->showSaleDiscountModal = false;
        $this->showToast('success', 'Sale discount applied successfully!');
    }

    // -----------------------------------------------------------------

    public function toggleDiscountType()
    {
        $this->additionalDiscountType = $this->additionalDiscountType === 'percentage' ? 'fixed' : 'percentage';
        $this->additionalDiscount = 0;
    }

    public function removeAdditionalDiscount()
    {
        $this->additionalDiscount = 0;
        $this->showToast('success', 'Additional discount removed!');
    }

    // Validate Payment Before Creating Sale
    public function validateAndCreateSale()
    {
        if (empty($this->cart)) {
            $this->showToast('error', 'Please add at least one product to the sale.');
            return;
        }

        // If no customer selected, use walking customer
        if (!$this->selectedCustomer && !$this->customerId) {
            $this->setDefaultCustomer();
        }

        // Set default amount received for cash
        if ($this->paymentMethod === 'cash') {
            $this->amountReceived = $this->grandTotal;
        }

        // Open payment modal
        $this->showPaymentModal = true;
    }

    // Close Payment Modal
    public function closePaymentModal()
    {
        $this->showPaymentModal = false;
        $this->amountReceived = 0;
        $this->paymentNotes = '';
    }

    // Complete Sale with Payment
    public function completeSaleWithPayment()
    {
        // Validate payment method
        if (empty($this->paymentMethod)) {
            $this->showToast('error', 'Please select a payment method.');
            return;
        }

        // Validate payment method specific fields
        if ($this->paymentMethod === 'cash') {
            if ($this->amountReceived < $this->grandTotal) {
                $this->showToast('error', 'Amount received must be at least Rs. ' . number_format($this->grandTotal, 2));
                return;
            }
        } elseif ($this->paymentMethod === 'cheque') {
            if (empty($this->cheques)) {
                $this->showToast('error', 'Please add at least one cheque.');
                return;
            }
            // Allow cheque amounts >= grand total (including overpayment)
            $totalCheques = round(collect($this->cheques)->sum('amount'), 2);
            $grand = round($this->grandTotal, 2);
            if ($totalCheques < $grand) {
                $this->showToast('error', 'Total cheque amount must be at least Rs. ' . number_format($this->grandTotal, 2));
                return;
            }
        } elseif ($this->paymentMethod === 'multiple') {
            $totalMultiple = ($this->cashAmount ?? 0) + collect($this->cheques)->sum('amount');
            if ($totalMultiple <= 0) {
                $this->showToast('error', 'Please specify a cash amount or add at least one cheque.');
                return;
            }
            // Allow overpayment in multiple payments
            // (removed check that prevented exceeding grand total)
        } elseif ($this->paymentMethod === 'bank_transfer') {
            if ($this->bankTransferAmount <= 0) {
                $this->showToast('error', 'Please enter bank transfer amount.');
                return;
            }
            // Allow overpayment in bank transfers (removed check that prevented exceeding grand total)
        }

        // Set cash amount from amount received for cash payments
        if ($this->paymentMethod === 'cash') {
            $this->cashAmount = $this->amountReceived;
        }

        // Add payment notes to sale notes
        if (!empty($this->paymentNotes)) {
            $this->notes = $this->paymentNotes;
        }

        // Close payment modal
        $this->showPaymentModal = false;

        // Proceed to create sale
        $this->createSale();
    }

    // Complete Sale with Payment and Auto-Print
    public function completeSaleWithPaymentAndPrint()
    {
        // Validate payment method
        if (empty($this->paymentMethod)) {
            $this->showToast('error', 'Please select a payment method.');
            return;
        }

        // Validate payment method specific fields
        if ($this->paymentMethod === 'cash') {
            if ($this->amountReceived < $this->grandTotal) {
                $this->showToast('error', 'Amount received must be at least Rs. ' . number_format($this->grandTotal, 2));
                return;
            }
        } elseif ($this->paymentMethod === 'cheque') {
            if (empty($this->cheques)) {
                $this->showToast('error', 'Please add at least one cheque.');
                return;
            }
            // Allow cheque amounts >= grand total (including overpayment)
            $totalCheques = round(collect($this->cheques)->sum('amount'), 2);
            $grand = round($this->grandTotal, 2);
            if ($totalCheques < $grand) {
                $this->showToast('error', 'Total cheque amount must be at least Rs. ' . number_format($this->grandTotal, 2));
                return;
            }
        } elseif ($this->paymentMethod === 'multiple') {
            $totalMultiple = ($this->cashAmount ?? 0) + collect($this->cheques)->sum('amount');
            if ($totalMultiple <= 0) {
                $this->showToast('error', 'Please specify a cash amount or add at least one cheque.');
                return;
            }
            // Allow overpayment in multiple payments
            // (removed check that prevented exceeding grand total)
        } elseif ($this->paymentMethod === 'bank_transfer') {
            if ($this->bankTransferAmount <= 0) {
                $this->showToast('error', 'Please enter bank transfer amount.');
                return;
            }
            // Allow overpayment in bank transfers (removed check that prevented exceeding grand total)
        }

        // Set cash amount from amount received for cash payments
        if ($this->paymentMethod === 'cash') {
            $this->cashAmount = $this->amountReceived;
        }

        // Add payment notes to sale notes
        if (!empty($this->paymentNotes)) {
            $this->notes = $this->paymentNotes;
        }

        // Close payment modal
        $this->showPaymentModal = false;

        // Set flag to auto-print after sale creation
        $this->autoPrintAfterSale = true;

        // Proceed to create sale
        $this->createSale();
    }

    // Confirm and Create Sale with Due Amount
    public function confirmSaleWithDue()
    {
        $this->showPaymentConfirmModal = false;
        $this->createSale();
    }

    // Cancel Sale Confirmation
    public function cancelSaleConfirmation()
    {
        $this->showPaymentConfirmModal = false;
        $this->pendingDueAmount = 0;
    }

    // Create Sale
    public function createSale()
    {
        try {
            Log::info('createSale: starting', [
                'editingSaleId' => $this->editingSaleId,
                'cart' => $this->cart,
                'subtotal' => $this->subtotal,
                'grandTotal' => $this->grandTotal,
                'paymentMethod' => $this->paymentMethod,
                'totalPaid' => $this->totalPaidAmount,
                'amountReceived' => $this->amountReceived,
                'cheques' => $this->cheques,
                'bankTransfer' => ['amount' => $this->bankTransferAmount, 'ref' => $this->bankTransferReferenceNumber]
            ]);

            DB::beginTransaction();

            // Get customer data
            $customer = $this->selectedCustomer ?? Customer::find($this->customerId);

            if (!$customer) {
                $this->showToast('error', 'Customer not found.');
                return;
            }

            // Check if editing existing sale
            if ($this->editingSaleId) {
                // UPDATE EXISTING SALE
                $sale = Sale::find($this->editingSaleId);
                if (!$sale) {
                    $this->showToast('error', 'Sale not found.');
                    return;
                }

                // Store old due amount for customer update
                $oldDueAmount = $sale->due_amount;
                $oldCustomerId = $sale->customer_id;

                // Restore previous stock quantities (both ProductStock and ProductBatch)
                $previousItems = SaleItem::where('sale_id', $sale->id)->get();
                foreach ($previousItems as $prevItem) {
                    $baseProductId = $prevItem->product_id;
                    $variantValue = $prevItem->variant_value ?? null;
                    $variantId = $prevItem->variant_id ?? null;
                    $quantity = $prevItem->quantity;

                    // Restore batch stock (add to most recent active batch first)
                    $batchQuery = ProductBatch::where('product_id', $baseProductId)
                        ->where('status', 'active');

                    if ($variantId) {
                        $batchQuery->where('variant_id', $variantId);
                    }
                    if ($variantValue) {
                        $batchQuery->where('variant_value', $variantValue);
                    }

                    $batch = $batchQuery->orderBy('received_date', 'desc')
                        ->orderBy('id', 'desc')
                        ->first();

                    if ($batch) {
                        $batch->remaining_quantity += $quantity;
                        $batch->quantity += $quantity;
                        $batch->save();
                    }

                    // Restore ProductStock
                    if ($variantValue || $variantId) {
                        $stockRecord = ProductStock::where('product_id', $baseProductId)
                            ->when($variantId, function ($q) use ($variantId) {
                                return $q->where('variant_id', $variantId);
                            })
                            ->when($variantValue, function ($q) use ($variantValue) {
                                return $q->where('variant_value', $variantValue);
                            })
                            ->first();

                        if ($stockRecord) {
                            $stockRecord->available_stock += $prevItem->quantity;
                            $stockRecord->updateTotals();
                        }
                    } else {
                        $product = ProductDetail::find($baseProductId);
                        if ($product && $product->stock) {
                            $product->stock->available_stock += $prevItem->quantity;
                            $product->stock->updateTotals();
                        }
                    }
                }

                // Delete previous sale items and payments
                SaleItem::where('sale_id', $sale->id)->delete();
                Payment::where('sale_id', $sale->id)->delete();
                Cheque::where('payment_id', '!=', null)->whereIn('payment_id', function ($q) use ($sale) {
                    $q->select('id')->from('payments')->where('sale_id', $sale->id);
                })->delete();

                // Update customer due_amount for EDIT: subtract old due, add new due
                // Handle both old customer and new customer (if customer changed)
                if ($oldCustomerId) {
                    $oldCustomer = Customer::find($oldCustomerId);
                    if ($oldCustomer && $oldCustomer->id !== $customer->id) {
                        // Customer changed: remove old due from old customer (only if it was added)
                        if ($oldDueAmount > 0) {
                            $oldCustomer->due_amount = max(0, ($oldCustomer->due_amount ?? 0) - $oldDueAmount);
                            $oldCustomer->total_due = ($oldCustomer->opening_balance ?? 0) + $oldCustomer->due_amount;
                            $oldCustomer->save();
                        }
                    }
                }

                // Update or add due amount to current customer (only if due amount exists)
                if ($customer->id == $oldCustomerId) {
                    // Same customer: adjust the difference
                    // Only subtract old due if it was actually added (>0)
                    if ($oldDueAmount > 0) {
                        $customer->due_amount = max(0, ($customer->due_amount ?? 0) - $oldDueAmount);
                    }
                    // Only add new due if it exists (>0)
                    if ($this->dueAmount > 0) {
                        $customer->due_amount = ($customer->due_amount ?? 0) + $this->dueAmount;
                    }
                } else {
                    // New customer: just add new due (only if exists)
                    if ($this->dueAmount > 0) {
                        $customer->due_amount = ($customer->due_amount ?? 0) + $this->dueAmount;
                    }
                }
                $customer->total_due = ($customer->opening_balance ?? 0) + $customer->due_amount;
                $customer->save();

                // Update sale
                $sale->update([
                    'customer_id' => $customer->id,
                    'customer_type' => $customer->type,
                    'subtotal' => $this->subtotal,
                    'discount_amount' => $this->totalDiscount + $this->additionalDiscountAmount,
                    'additional_discount_type' => $this->additionalDiscountType,
                    'additional_discount_percentage' => $this->additionalDiscountType === 'percentage' ? $this->additionalDiscount : 0,
                    'total_amount' => $this->grandTotal,
                    'payment_type' => $this->databasePaymentType,
                    'payment_status' => $this->paymentStatus,
                    'due_amount' => $this->dueAmount,
                    'notes' => $this->notes,
                    'delivery_status' => 'delivered',
                ]);
            } else {
                // CREATE NEW SALE
                $sale = Sale::create([
                    'sale_id' => Sale::generateSaleId(),
                    'invoice_number' => Sale::generateInvoiceNumber(),
                    'customer_id' => $customer->id,
                    'customer_type' => $customer->type,
                    'subtotal' => $this->subtotal,
                    'discount_amount' => $this->totalDiscount + $this->additionalDiscountAmount,
                    'additional_discount_type' => $this->additionalDiscountType,
                    'additional_discount_percentage' => $this->additionalDiscountType === 'percentage' ? $this->additionalDiscount : 0,
                    'total_amount' => $this->grandTotal,
                    'payment_type' => $this->databasePaymentType,
                    'payment_status' => $this->paymentStatus,
                    'due_amount' => $this->dueAmount,
                    'notes' => $this->notes,
                    'user_id' => Auth::id(),
                    'status' => 'confirm',
                    'sale_type' => 'pos',
                    'delivery_status' => 'delivered'
                ]);
            }

            // Create sale items and deduct stock using FIFO method
            foreach ($this->cart as $item) {
                // Resolve base product id (handle variant entries where 'product_id' exists)
                $baseProductId = $item['product_id'] ?? $item['id'];
                $variantId = $item['variant_id'] ?? null;
                $variantValue = $item['variant_value'] ?? null;
                $quantity = $item['quantity'];

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $baseProductId,
                    'product_code' => $item['code'] ?? '',
                    'product_name' => $item['name'],
                    'product_model' => $item['model'] ?? null,
                    'quantity' => $quantity,
                    'unit_price' => $item['price'],
                    'discount_per_unit' => $item['discount'],
                    'total_discount' => $item['discount'] * $quantity,
                    'discount_type' => $item['discount_type'] ?? 'fixed',
                    'discount_percentage' => $item['discount_percentage'] ?? 0,
                    'total' => $item['total'],
                    'variant_value' => $variantValue,
                    'variant_id' => $variantId,
                ]);

                // Deduct stock using FIFO method (updates both ProductBatch and ProductStock)
                try {
                    FIFOStockService::deductStock(
                        $baseProductId,
                        $quantity,
                        $variantId,
                        $variantValue
                    );
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Stock deduction failed', [
                        'product_id' => $baseProductId,
                        'quantity' => $quantity,
                        'variant_id' => $variantId,
                        'variant_value' => $variantValue,
                        'error' => $e->getMessage()
                    ]);
                    $this->showToast('error', 'Stock deduction failed: ' . $e->getMessage());
                    return;
                }
            }

            // Create Payment Record(s)
            if ($this->paymentMethod !== 'credit' && floatval($this->totalPaidAmount) > 0) {

                if ($this->paymentMethod === 'multiple') {
                    // Create a cash payment for the cash portion (if any)
                    if (!empty($this->cashAmount) && (float)$this->cashAmount > 0) {
                        $cashPayment = Payment::create([
                            'customer_id' => $customer->id,
                            'sale_id' => $sale->id,
                            'amount' => round(floatval($this->cashAmount), 2),
                            'payment_method' => 'cash',
                            'payment_date' => now(),
                            'is_completed' => true,
                            'status' =>  'paid',
                        ]);

                        $cashPayment->update([
                            'payment_reference' => 'CASH-' . now()->format('YmdHis'),
                        ]);

                        // Update cash in hands - add cash payment
                        $this->updateCashInHands(round(floatval($this->cashAmount), 2));
                    }

                    // Create a separate payment for each cheque and link cheque records
                    foreach ($this->cheques as $cheque) {
                        $chPayment = Payment::create([
                            'customer_id' => $customer->id,
                            'sale_id' => $sale->id,
                            'amount' => round(floatval($cheque['amount']), 2),
                            'payment_method' => 'cheque',
                            'payment_date' => now(),
                            'is_completed' => true,
                            'status' =>  'paid',
                        ]);

                        Cheque::create([
                            'cheque_number' => $cheque['number'],
                            'cheque_date' => $cheque['date'],
                            'bank_name' => $cheque['bank_name'],
                            'cheque_amount' => $cheque['amount'],
                            'status' => 'pending',
                            'customer_id' => $customer->id,
                            'payment_id' => $chPayment->id,
                        ]);

                        $chPayment->update([
                            'payment_reference' => 'CHQ-' . ($cheque['number'] ?? ''),
                            'bank_name' => $cheque['bank_name'] ?? null,
                        ]);
                    }
                } else {
                    // Single payment (cash / cheque / bank_transfer)
                    $payment = Payment::create([
                        'customer_id' => $customer->id,
                        'sale_id' => $sale->id,
                        'amount' => round(floatval($this->totalPaidAmount), 2),
                        'payment_method' => $this->paymentMethod,
                        'payment_date' => now(),
                        'is_completed' => true,
                        'status' =>  'paid',
                    ]);

                    // Handle payment method specific data
                    if ($this->paymentMethod === 'cash') {
                        $payment->update([
                            'payment_reference' => 'CASH-' . now()->format('YmdHis'),
                        ]);

                        // Update cash in hands - add cash payment
                        $this->updateCashInHands(round(floatval($this->totalPaidAmount), 2));
                    } elseif ($this->paymentMethod === 'cheque') {
                        // Create cheque records
                        foreach ($this->cheques as $cheque) {
                            Cheque::create([
                                'cheque_number' => $cheque['number'],
                                'cheque_date' => $cheque['date'],
                                'bank_name' => $cheque['bank_name'],
                                'cheque_amount' => $cheque['amount'],
                                'status' => 'pending',
                                'customer_id' => $customer->id,
                                'payment_id' => $payment->id,
                            ]);
                        }

                        $payment->update([
                            'payment_reference' => 'CHQ-' . collect($this->cheques)->pluck('number')->implode(','),
                            'bank_name' => collect($this->cheques)->pluck('bank_name')->unique()->implode(', '),
                        ]);
                    } elseif ($this->paymentMethod === 'bank_transfer') {
                        $payment->update([
                            'payment_reference' => $this->bankTransferReferenceNumber ?: 'BANK-' . now()->format('YmdHis'),
                            'bank_name' => $this->bankTransferBankName,
                            'transfer_date' => now(),
                            'transfer_reference' => $this->bankTransferReferenceNumber,
                        ]);
                    }
                }
            }

            // UPDATE CUSTOMER BALANCE FOR CREDIT SALES AND OVERPAYMENTS
            if ($customer && $customer->name !== 'Walking Customer') {
                $dueAmount = floatval($this->dueAmount);
                $totalPaid = floatval($this->totalPaidAmount);
                $grandTotal = floatval($this->grandTotal);

                // Handle Credit Sales (due amount gets added to customer's due_amount)
                // SKIP for edits — due amount was already adjusted in the edit block above
                if ($dueAmount > 0 && !$this->editingSaleId) {
                    // Add due amount to customer's due_amount column
                    $customer->due_amount = ($customer->due_amount ?? 0) + $dueAmount;

                    // Recalculate total_due = opening_balance + due_amount
                    $customer->total_due = ($customer->opening_balance ?? 0) + $customer->due_amount;
                }

                // Handle Overpayments (payment exceeds grand total)
                if ($totalPaid > $grandTotal) {
                    $excessAmount = $totalPaid - $grandTotal;

                    // Distribution logic: opening_balance → due_amount → overpaid_amount
                    if ($excessAmount >= ($customer->opening_balance ?? 0)) {
                        // Excess enough to clear opening balance
                        $excessAmount -= ($customer->opening_balance ?? 0);
                        $customer->opening_balance = 0;
                    } else {
                        // Reduce opening balance only
                        $customer->opening_balance = ($customer->opening_balance ?? 0) - $excessAmount;
                        $excessAmount = 0;
                    }

                    // If still excess, reduce due_amount
                    if ($excessAmount > 0) {
                        if ($excessAmount >= ($customer->due_amount ?? 0)) {
                            $excessAmount -= ($customer->due_amount ?? 0);
                            $customer->due_amount = 0;
                        } else {
                            $customer->due_amount = ($customer->due_amount ?? 0) - $excessAmount;
                            $excessAmount = 0;
                        }
                    }

                    // Remaining excess goes to overpaid_amount
                    if ($excessAmount > 0) {
                        $customer->overpaid_amount = ($customer->overpaid_amount ?? 0) + $excessAmount;
                    }

                    // Recalculate total_due
                    $customer->total_due = ($customer->opening_balance ?? 0) + ($customer->due_amount ?? 0);
                }

                // Save updated customer balance
                $customer->save();
            }

            DB::commit();

            // Ensure there is an open POS session for this user and update its totals
            $this->currentSession = POSSession::getTodaySession(Auth::id());
            if (! $this->currentSession) {
                // If no open session, create one with zero opening cash so sales still get tracked
                $this->currentSession = POSSession::openSession(Auth::id(), 0);
            }

            // Recalculate session totals from sales/payments for the day
            try {
                $this->currentSession->updateFromSales();
                // Recalculate expected cash (cash difference will stay null until close)
                $this->currentSession->calculateDifference();
            } catch (\Exception $e) {
                Log::error('Failed to update POS session after sale: ' . $e->getMessage());
            }

            $this->lastSaleId = $sale->id;
            $this->createdSale = Sale::with(['customer', 'items', 'payments'])->find($sale->id);
            $this->showSaleModal = true;

            $isEditMode = (bool)$this->editingSaleId;
            $actionType = $isEditMode ? 'updated' : 'created';
            $statusMessage = 'Sale ' . $actionType . ' successfully! Payment status: ' . ucfirst($this->paymentStatus);
            if ($this->dueAmount > 0) {
                $statusMessage .= ' | Due Amount: Rs.' . number_format($this->dueAmount, 2);
            }

            // Clear cart and reset payment fields after successful sale
            $this->cart = [];
            $this->additionalDiscount = 0;
            $this->additionalDiscountType = 'fixed';
            $this->resetPaymentFields();
            $this->notes = '';
            $this->editingSaleId = null;
            $this->editingSale = null;

            // Reset to walking customer
            $this->setDefaultCustomer();

            $this->showToast('success', $statusMessage);

            // If edited, redirect to POS sales list after showing toast
            if ($isEditMode) {
                $this->js("
                    setTimeout(() => {
                        window.location.href = '" . route('admin.pos-sales') . "';
                    }, 1500);
                ");
                return;
            }

            // Auto-print if flag is set
            if ($this->autoPrintAfterSale && $this->createdSale) {
                // Reset the flag
                $this->autoPrintAfterSale = false;

                // Trigger the same print function as the manual print button
                $this->js("
                    setTimeout(() => {
                        if (typeof printInvoice === 'function') {
                            printInvoice();
                        } else {
                            console.error('printInvoice function not found');
                        }
                    }, 800);
                ");
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('createSale failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'cart' => $this->cart,
                'paymentMethod' => $this->paymentMethod,
                'amountReceived' => $this->amountReceived,
                'totalPaidAmount' => $this->totalPaidAmount,
            ]);
            $this->showToast('error', 'Failed to create sale: ' . addslashes($e->getMessage()) . ' (see server logs)');
        }
    }

    // Download Invoice
    public function downloadInvoice()
    {
        if (!$this->lastSaleId) {
            $this->showToast('error', 'No sale found to download.');
            return;
        }

        $sale = Sale::with(['customer', 'items', 'returns' => function ($q) {
            $q->with('product');
        }])->find($this->lastSaleId);

        if (!$sale) {
            $this->showToast('error', 'Sale not found.');
            return;
        }

        $pdf = PDF::loadView('receipts.download', compact('sale'));
        $pdf->setPaper('a4', 'portrait');
        $pdf->setOption('dpi', 150);
        $pdf->setOption('defaultFont', 'sans-serif');

        return response()->streamDownload(
            function () use ($pdf) {
                echo $pdf->output();
            },
            'invoice-' . $sale->invoice_number . '.pdf'
        );
    }

    // Print Sale Receipt
    public function printSaleReceipt()
    {
        if (!$this->createdSale) {
            $this->showToast('error', 'No sale found to print.');
            return;
        }

        $sale = Sale::with(['customer', 'items', 'payments', 'returns' => function ($q) {
            $q->with('product');
        }])->find($this->createdSale->id);

        if (!$sale) {
            $this->showToast('error', 'Sale not found.');
            return;
        }

        // Store sale ID in session for print route
        session(['print_sale_id' => $sale->id]);

        // Open print page in new window
        $this->js("
            const printUrl = '" . route('admin.print.sale', $sale->id) . "';
            const printWindow = window.open(printUrl, '_blank', 'width=800,height=600');
            if (printWindow) {
                printWindow.focus();
            }
        ");
    }

    // Download Close Register Report
    public function downloadCloseRegisterReport()
    {
        if (!$this->currentSession) {
            $this->showToast('error', 'No session found to download.');
            return;
        }

        // Prepare data for PDF
        $sessionData = [
            'session' => $this->currentSession,
            'summary' => $this->sessionSummary,
            'close_date' => now()->format('d/m/Y'),
            'close_time' => now()->format('H:i'),
            'user' => Auth::user()->name,
        ];

        $pdf = PDF::loadView('admin.pos.close-register-report', $sessionData);

        return response()->streamDownload(
            function () use ($pdf) {
                echo $pdf->output();
            },
            'close-register-' . now()->format('Y-m-d-His') . '.pdf'
        );
    }

    // Close Modal
    public function closeModal()
    {
        $this->showSaleModal = false;
        $this->lastSaleId = null;
        $this->createdSale = null;
        $this->loadProducts(); // Refresh product stock display
    }

    // Continue creating new sale
    public function createNewSale()
    {
        $this->resetExcept(['customers', 'currentSession']);
        $this->loadCustomers();
        $this->setDefaultCustomer(); // Set walking customer again for new sale
        $this->loadCategories(); // Reload categories after sale
        $this->loadProducts(); // Reload products after sale
        $this->showSaleModal = false;

        // Dispatch event to clean up modal backdrop
        $this->dispatch('saleSaved');
    }

    /**
     * Submit Opening Cash and Create/Reopen POS Session
     */
    public function submitOpeningCash()
    {
        $this->validate([
            'openingCashAmount' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            // Check if a closed session exists for today
            $existingSession = POSSession::where('user_id', Auth::id())
                ->whereDate('session_date', now()->toDateString())
                ->where('status', 'closed')
                ->first();

            if ($existingSession) {
                // Reopen existing closed session with new opening cash
                $existingSession->update([
                    'status' => 'open',
                    'opening_cash' => $this->openingCashAmount,
                    'closed_at' => null,
                    'notes' => ($existingSession->notes ? $existingSession->notes . ' | ' : '') . 'Reopened with opening cash: Rs. ' . number_format($this->openingCashAmount, 2)
                ]);
                $this->currentSession = $existingSession;
                $message = 'POS Session Reopened!';

                // For reopening, don't update cash_in_hands as it should retain the session's opening amount
            } else {
                // Create new POS session with opening cash (first time opening)
                $this->currentSession = POSSession::openSession(Auth::id(), $this->openingCashAmount);
                $message = 'POS Session Started!';

                // Update cash_in_hands table only for new sessions (first time opening)
                $cashInHandRecord = DB::table('cash_in_hands')->where('key', 'cash_amount')->first();

                if ($cashInHandRecord) {
                    DB::table('cash_in_hands')
                        ->where('key', 'cash_amount')
                        ->update([
                            'value' => $this->openingCashAmount,
                            'updated_at' => now()
                        ]);
                } else {
                    DB::table('cash_in_hands')->insert([
                        'key' => 'cash_amount',
                        'value' => $this->openingCashAmount,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }

            DB::commit();

            // Close the modal
            $this->showOpeningCashModal = false;

            $this->showToast('success', $message . ' - Opening cash: Rs. ' . number_format($this->openingCashAmount, 2));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to open/reopen POS session: ' . $e->getMessage());
            $this->showToast('error', 'Failed to start POS session: ' . addslashes($e->getMessage()));
        }
    }

    /**
     * View Close Register Report - Show summary WITHOUT closing the session
     */
    public function viewCloseRegisterReport()
    {
        // Refresh session data
        $this->currentSession = POSSession::getTodaySession(Auth::id());

        // If no session exists, show info message
        if (!$this->currentSession) {
            $this->showToast('info', 'No Active Session - Please open a POS session first by accessing the POS page.');
            return;
        }

        // If session is already closed, show alert
        if ($this->currentSession->isClosed()) {
            $this->showToast('warning', 'Register Already Closed - The POS register has already been closed for today. You cannot access the close register function again.');
            return;
        }

        $today = now()->toDateString();

        // 1. Cash in Hand - Get Opening Amount from session
        $sessionOpeningCash = $this->currentSession->opening_cash;

        // Get today's POS sales IDs (sale_type = 'pos')
        $posSalesToday = Sale::whereDate('created_at', $today)
            ->where('sale_type', 'pos')
            ->pluck('id');

        // Get today's Admin sales IDs (sale_type = 'admin')
        $adminSalesToday = Sale::whereDate('created_at', $today)
            ->where('sale_type', 'admin')
            ->pluck('id');

        // 2. POS Cash Sale - Get from payment table where sale_type = 'pos' and method = 'cash'
        $posCashPayments = Payment::whereIn('sale_id', $posSalesToday)
            ->where('payment_method', 'cash')
            ->whereDate('payment_date', $today)
            ->sum('amount');

        // 3. POS Cheque Payment - Get from payment table where sale_type = 'pos' and method = 'cheque'
        $posChequePayments = Payment::whereIn('sale_id', $posSalesToday)
            ->where('payment_method', 'cheque')
            ->whereDate('payment_date', $today)
            ->sum('amount');

        // POS Bank Transfer Payment - Get from payment table where sale_type = 'pos' and method = 'bank_transfer'
        $posBankTransfers = Payment::whereIn('sale_id', $posSalesToday)
            ->where('payment_method', 'bank_transfer')
            ->whereDate('payment_date', $today)
            ->sum('amount');

        // 4. Late Payments - Include both Admin Sales and payments with null sale_id
        // 4.1 Admin Cash Payments (from admin sales)
        $adminCashPayments = Payment::whereIn('sale_id', $adminSalesToday)
            ->where('payment_method', 'cash')
            ->whereDate('payment_date', $today)
            ->sum('amount');

        // 4.1.1 Late Cash Payments (sale_id is null)
        $lateCashPayments = Payment::whereNull('sale_id')
            ->where('payment_method', 'cash')
            ->whereDate('payment_date', $today)
            ->sum('amount');

        // Total Cash Payments from Admin and Late Payments
        $totalAdminCashPayments = $adminCashPayments + $lateCashPayments;

        // 4.2 Admin Cheque Payments (from admin sales)
        $adminChequePayments = Payment::whereIn('sale_id', $adminSalesToday)
            ->where('payment_method', 'cheque')
            ->whereDate('payment_date', $today)
            ->sum('amount');

        // 4.2.1 Late Cheque Payments (sale_id is null)
        $lateChequePayments = Payment::whereNull('sale_id')
            ->where('payment_method', 'cheque')
            ->whereDate('payment_date', $today)
            ->sum('amount');

        // Total Cheque Payments from Admin and Late Payments
        $totalAdminChequePayments = $adminChequePayments + $lateChequePayments;

        // 4.3 Admin Bank Transfer Payments (from admin sales)
        $adminBankTransfers = Payment::whereIn('sale_id', $adminSalesToday)
            ->where('payment_method', 'bank_transfer')
            ->whereDate('payment_date', $today)
            ->sum('amount');

        // 4.3.1 Late Bank Transfer Payments (sale_id is null)
        $lateBankTransfers = Payment::whereNull('sale_id')
            ->where('payment_method', 'bank_transfer')
            ->whereDate('payment_date', $today)
            ->sum('amount');

        // Total Bank Transfer Payments from Admin and Late Payments
        $totalAdminBankTransfers = $adminBankTransfers + $lateBankTransfers;

        // Calculate total late payments (admin + null sale_id)
        $totalAdminPayments = $totalAdminCashPayments + $totalAdminChequePayments + $totalAdminBankTransfers;

        // 5. Total Cash Amount (POS Cash + Admin Cash + Late Cash)
        $totalCashFromSales = $posCashPayments + $totalAdminCashPayments;

        // 6. Total POS Sales - Get from sales table where sale_type = 'pos'
        $totalPosSales = Sale::whereDate('created_at', $today)
            ->where('sale_type', 'pos')
            ->sum('total_amount');

        // 7. Total Admin Sales - Get from sales table where sale_type = 'admin'
        $totalAdminSales = Sale::whereDate('created_at', $today)
            ->where('sale_type', 'admin')
            ->sum('total_amount');

        // 8. Total Cash from Payment Table (All cash payments for the day)
        $totalCashPaymentsToday = Payment::whereDate('payment_date', $today)
            ->where('payment_method', 'cash')
            ->sum('amount');

        // 9. Expenses, Refunds, and Cash Deposit Bank
        // Get refunds today (returns)
        $refundsToday = DB::table('returns_products')
            ->whereDate('created_at', $today)
            ->sum('total_amount');

        // Get expenses today
        $expensesToday = DB::table('expenses')
            ->whereDate('date', $today)
            ->where('expense_type', 'daily')
            ->sum('amount');

        // Get cash deposits to bank from deposit table
        $cashDepositBank = DB::table('deposits')
            ->whereDate('date', $today)
            ->sum('amount');
        $supplierPaymentToday = DB::table('purchase_payments')

            ->whereDate('payment_date', $today)
            ->sum('amount');

        $supplierCashPaymentToday = DB::table('purchase_payments')
            ->where('payment_method', 'cash')
            ->whereDate('payment_date', $today)
            ->sum('amount');

        // Calculate Total Cash in Hand
        $totalCashInHand = ($sessionOpeningCash + $totalCashPaymentsToday) - ($refundsToday + $expensesToday + $cashDepositBank + $supplierCashPaymentToday);

        // Update session data
        $this->currentSession->update([
            'total_sales' => $totalPosSales,
            'cash_sales' => $totalCashFromSales,
            'late_payment_bulk' => $totalAdminPayments,
            'cheque_payment' => $posChequePayments,
            'bank_transfer' => $posBankTransfers,
            'refunds' => $refundsToday,
            'expenses' => $expensesToday,
            'cash_deposit_bank' => $cashDepositBank,
            'spupplier_payment' => $supplierPaymentToday,
        ]);

        // Prepare summary data
        $this->sessionSummary = [
            'opening_cash' => $sessionOpeningCash,

            // POS Sales Breakdown
            'pos_cash_sales' => $posCashPayments,
            'pos_cheque_payment' => $posChequePayments,
            'pos_bank_transfer' => $posBankTransfers,
            'total_pos_sales' => $totalPosSales,

            // Admin Sales (Late Payments) Breakdown
            'admin_cash_payment' => $adminCashPayments,
            'admin_cheque_payment' => $adminChequePayments,
            'admin_bank_transfer' => $adminBankTransfers,

            // Late Payments (sale_id is null)
            'late_cash_payment' => $lateCashPayments,
            'late_cheque_payment' => $lateChequePayments,
            'late_bank_transfer' => $lateBankTransfers,

            // Combined Late Payments
            'total_admin_cash_payment' => $totalAdminCashPayments,
            'total_admin_cheque_payment' => $totalAdminChequePayments,
            'total_admin_bank_transfer' => $totalAdminBankTransfers,
            'total_admin_payment' => $totalAdminPayments,
            'total_admin_sales' => $totalAdminSales,

            // Combined Totals
            'total_cash_from_sales' => $totalCashFromSales, // POS Cash + Admin Cash
            'total_cash_payment_today' => $totalCashPaymentsToday, // All cash payments

            // Deductions
            'refunds' => $refundsToday,
            'expenses' => $expensesToday,
            'cash_deposit_bank' => $cashDepositBank,
            'supplier_payment' => $supplierPaymentToday,
            'supplier_cash_payment' => $supplierCashPaymentToday,

            // Final Cash in Hand
            'expected_cash' => $totalCashInHand,
        ];

        $this->closeRegisterCash = $this->sessionSummary['expected_cash'];

        // Just show the modal, don't close the session yet
        $this->showCloseRegisterModal = true;

        $this->dispatch('showModal', 'closeRegisterModal');
    }

    /**
     * Cancel Close Register - Just close modal without doing anything
     */
    public function cancelCloseRegister()
    {
        $this->showCloseRegisterModal = false;
    }

    /**
     * Close Register and Redirect to Dashboard
     * This actually closes the POS session when user clicks "Close & Go to Dashboard"
     */
    public function closeRegisterAndRedirect()
    {
        try {
            DB::beginTransaction();

            // Refresh session data
            $this->currentSession = POSSession::where('user_id', Auth::id())
                ->whereDate('session_date', now()->toDateString())
                ->where('status', 'open')
                ->first();

            if (!$this->currentSession) {
                DB::rollBack();

                session()->flash('error', 'No active POS session found.');

                return redirect()->route('admin.dashboard');
            }

            // Get the expected closing cash from sessionSummary
            $expectedClosingCash = $this->sessionSummary['expected_cash'] ?? $this->closeRegisterCash;

            // Close the session
            $this->currentSession->update([
                'closing_cash' => $expectedClosingCash,
                'total_sales' => $this->sessionSummary['total_pos_sales'] ?? 0,
                'cash_sales' => $this->sessionSummary['total_cash_from_sales'] ?? 0,
                'late_payment_bulk' => $this->sessionSummary['total_admin_payment'] ?? 0,
                'cheque_payment' => $this->sessionSummary['pos_cheque_payment'] ?? 0,
                'bank_transfer' => $this->sessionSummary['pos_bank_transfer'] ?? 0,
                'refunds' => $this->sessionSummary['refunds'] ?? 0,
                'expenses' => $this->sessionSummary['expenses'] ?? 0,
                'cash_deposit_bank' => $this->sessionSummary['cash_deposit_bank'] ?? 0,
                'status' => 'closed',
                'closed_at' => now(),
                'notes' => $this->closeRegisterNotes ?? 'Closed from close register modal',
            ]);

            // Update both 'cash in hand' and 'cash_amount' keys in cash_in_hands table
            $keysToUpdate = ['cash in hand', 'cash_amount'];

            foreach ($keysToUpdate as $key) {
                $cashInHandRecord = DB::table('cash_in_hands')->where('key', $key)->first();

                if ($cashInHandRecord) {
                    DB::table('cash_in_hands')
                        ->where('key', $key)
                        ->update([
                            'value' => $expectedClosingCash,
                            'updated_at' => now()
                        ]);
                } else {
                    DB::table('cash_in_hands')->insert([
                        'key' => $key,
                        'value' => $expectedClosingCash,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }

            DB::commit();

            // Close modal
            $this->showCloseRegisterModal = false;

            // Flash success message
            session()->flash('success', 'POS register closed successfully! Closing cash: Rs. ' . number_format($expectedClosingCash, 2));

            // Redirect to dashboard
            return redirect()->route('admin.dashboard');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to close POS session: ' . $e->getMessage());

            session()->flash('error', 'Failed to close register: ' . $e->getMessage());

            return redirect()->route('admin.dashboard');
        }
    }

    /**
     * Reopen today's closed POS session (for admin)
     * Called via AJAX from header modal
     */
    public function reopenPOSSession()
    {
        $today = now()->toDateString();
        $userId = Auth::id();
        $session = POSSession::where('user_id', $userId)
            ->whereDate('session_date', $today)
            ->where('status', 'closed')
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'No closed POS session found for today.'
            ], 404);
        }

        try {
            // Reset specified columns to 0 and change status to open
            $session->update([
                'status' => 'open',
                'closing_cash' => 0,
                'total_sales' => 0,
                'cash_sales' => 0,
                'cheque_payment' => 0,
                'credit_card_payment' => 0,
                'bank_transfer' => 0,
                'late_payment_bulk' => 0,
                'refunds' => 0,
                'expenses' => 0,
                'cash_deposit_bank' => 0,
                'expected_cash' => 0,
                'cash_difference' => 0,
                'notes' => null,
                'closed_at' => null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'POS session reopened successfully. All transaction data has been reset.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reopen POS session: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Show toast notification (POS-friendly alternative to SweetAlert)
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

    /**
     * Get image URL with caching and fallback
     * 
     * @param string $imagePath - The image path from database
     * @return string - The processed image URL
     */
    public function getImageUrl($imagePath)
    {
        // Check if image path is empty or invalid
        if (
            empty($imagePath) ||
            str_contains($imagePath, 'default.png') ||
            str_contains($imagePath, 'placeholder') ||
            str_contains($imagePath, 'no-image')
        ) {
            // Return a default image URL instead of SVG
            return 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSrn_80I-lMAa0pVBNmFmQ7VI6l4rr74JW-eQ&s';
        }

        // Cache the processed image URL to prevent recalculation
        static $imageCache = [];
        $cacheKey = md5($imagePath);

        if (isset($imageCache[$cacheKey])) {
            return $imageCache[$cacheKey];
        }

        // Check if it's already a full URL
        if (str_starts_with($imagePath, 'http://') || str_starts_with($imagePath, 'https://')) {
            $imageCache[$cacheKey] = $imagePath;
            return $imagePath;
        }

        // Otherwise, treat it as a local asset path
        $assetUrl = asset($imagePath);
        $imageCache[$cacheKey] = $assetUrl;
        return $assetUrl;
    }

    public function render()
    {
        return view('livewire.admin.store-billing', [
            'subtotal' => $this->subtotal,
            'totalDiscount' => $this->totalDiscount,
            'subtotalAfterItemDiscounts' => $this->subtotalAfterItemDiscounts,
            'additionalDiscountAmount' => $this->additionalDiscountAmount,
            'grandTotal' => $this->grandTotal,
            'dueAmount' => $this->dueAmount,
            'paymentStatus' => $this->paymentStatus,
            'databasePaymentType' => $this->databasePaymentType,
            'totalPaidAmount' => round(floatval($this->totalPaidAmount), 2),
            'products' => $this->products,
            'categories' => $this->categories,
        ]);
    }
}
