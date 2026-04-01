<?php

namespace App\Http\Controllers\Api;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\ProductStock;
use App\Models\ProductDetail;
use App\Models\ProductPrice;
use App\Models\User;
use App\Models\Payment;
use App\Models\Cheque;
use App\Models\Customer;
use App\Notifications\NewSaleNotification;
use App\Notifications\PaymentNotification;
use App\Notifications\LowStockNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class SaleController extends ApiController
{
    /**
     * Get all sales with optional filters
     */
    public function index(Request $request)
    {
        $query = Sale::with(['customer', 'items.product', 'user']);

        // Search by invoice
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        // Filter by payment status
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->get('payment_status'));
        }

        // Filter by customer
        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->get('customer_id'));
        }

        // Filter for due/credit sales only
        if ($request->has('due_only') && $request->get('due_only')) {
            $query->where('due_amount', '>', 0);
        }

        /** @var \Illuminate\Pagination\LengthAwarePaginator $sales */
        $sales = $query->orderBy('created_at', 'desc')->paginate(20);

        $transformedSales = collect($sales->items())->map(function ($sale) {
            return $this->transformSale($sale);
        });

        return $this->paginated($sales->setCollection($transformedSales));
    }

    /**
     * Get due/credit sales (sales with outstanding balance)
     */
    public function dueSales(Request $request)
    {
        $query = Sale::with(['customer', 'items.product', 'user', 'payments'])
            ->where('due_amount', '>', 0);

        // Search by invoice or customer
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by customer
        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->get('customer_id'));
        }

        /** @var \Illuminate\Pagination\LengthAwarePaginator $sales */
        $sales = $query->orderBy('due_amount', 'desc')->paginate(20);

        $transformedSales = collect($sales->items())->map(function ($sale) {
            return $this->transformSale($sale, true);
        });

        // Calculate summary
        $totalDue = Sale::where('due_amount', '>', 0)->sum('due_amount');
        $totalSales = Sale::where('due_amount', '>', 0)->count();

        return $this->success([
            'sales' => $transformedSales,
            'pagination' => [
                'current_page' => $sales->currentPage(),
                'last_page' => $sales->lastPage(),
                'per_page' => $sales->perPage(),
                'total' => $sales->total(),
            ],
            'summary' => [
                'total_due_amount' => (float) $totalDue,
                'total_due_sales' => $totalSales,
            ]
        ]);
    }

    /**
     * Get a single sale by ID
     */
    public function show($id)
    {
        $sale = Sale::with(['customer', 'items.product', 'payments', 'user'])->find($id);

        if (!$sale) {
            return $this->error('Sale not found', 404);
        }

        return $this->success($this->transformSale($sale, true));
    }

    /**
     * Create a new sale
     */
    public function store(Request $request)
    {
        // Accept both 'customer' and 'customer_id' from frontend
        $customerId = $request->customer_id ?? $request->customer;
        // Accept both 'items' and 'items_data' from frontend
        $items = $request->items ?? $request->items_data ?? [];

        if (empty($items)) {
            return $this->error('At least one item is required', 422);
        }

        try {
            DB::beginTransaction();

            // Generate invoice number
            $invoiceNumber = Sale::generateInvoiceNumber();

            // Calculate totals
            $subtotal = 0;
            foreach ($items as $item) {
                $quantity = $item['quantity'];
                $unitPrice = $item['unit_price'];
                $discount = $item['discount_amount'] ?? $item['discount'] ?? 0;
                $subtotal += ($quantity * $unitPrice) - ($discount * $quantity);
            }

            $discountAmount = $request->discount_amount ?? 0;
            $totalAmount = $request->total_amount ?? ($subtotal - $discountAmount);
            $paidAmount = $request->paid_amount ?? 0;
            $dueAmount = $totalAmount - $paidAmount;

            // Determine payment type based on paid amount (full or partial payment)
            $paymentType = $dueAmount <= 0 ? 'full' : 'partial';

            // Determine payment status
            if ($dueAmount <= 0) {
                $paymentStatus = 'paid';
            } elseif ($paidAmount > 0) {
                $paymentStatus = 'partial';
            } else {
                $paymentStatus = 'pending';
            }

            // Get user ID - use authenticated user or find/create API user
            $userId = Auth::id();
            if (!$userId) {
                $apiUser = User::where('email', 'api@system.local')->first();
                if (!$apiUser) {
                    $apiUser = User::create([
                        'name' => 'API System',
                        'email' => 'api@system.local',
                        'password' => Hash::make('api-system-user-' . time()),
                        'contact' => '0000000000',
                        'role' => 'admin',
                    ]);
                }
                $userId = $apiUser->id;
            }

            // Create sale
            $sale = Sale::create([
                'sale_id' => Sale::generateSaleId(),
                'invoice_number' => $invoiceNumber,
                'customer_id' => $customerId,
                'customer_type' => $request->customer_type ?? 'retail',
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'payment_type' => $paymentType,
                'payment_status' => $paymentStatus,
                'status' => 'confirm',
                'notes' => $request->notes,
                'due_amount' => max(0, $dueAmount),
                'user_id' => $userId,
                'sale_type' => $request->sale_type ?? 'admin',
            ]);

            // Create sale items and update stock (with variant support)
            foreach ($items as $item) {
                $productId = $item['product_id'] ?? $item['product'];
                $quantity = $item['quantity'];
                $unitPrice = $item['unit_price'];
                $discount = $item['discount_amount'] ?? $item['discount'] ?? 0;
                $variantId = $item['variant_id'] ?? null;
                $variantValue = $item['variant_value'] ?? null;

                // Get product details for required fields
                $product = ProductDetail::find($productId);
                $productCode = $product ? $product->code : 'PROD-' . $productId;
                $productName = $product ? $product->name : 'Product ' . $productId;
                $productModel = $product ? ($product->model ?? '') : '';

                // Append variant value to product name for clarity
                $displayName = $variantValue ? $productName . ' (' . $variantValue . ')' : $productName;

                // Create sale item
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $productId,
                    'product_code' => $productCode,
                    'product_name' => $displayName,
                    'product_model' => $productModel,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'discount_per_unit' => $discount,
                    'total_discount' => $discount * $quantity,
                    'total' => ($quantity * $unitPrice) - ($discount * $quantity),
                    'variant_id' => $variantId,
                    'variant_value' => $variantValue,
                ]);

                // Update stock - variant-aware (matches web billing logic)
                if ($variantValue || $variantId) {
                    $stockRecord = ProductStock::where('product_id', $productId)
                        ->when($variantId, fn($q) => $q->where('variant_id', $variantId))
                        ->when($variantValue, fn($q) => $q->where('variant_value', $variantValue))
                        ->first();
                    if ($stockRecord) {
                        $stockRecord->available_stock = max(0, $stockRecord->available_stock - $quantity);
                        $stockRecord->sold_count = ($stockRecord->sold_count ?? 0) + $quantity;
                        $stockRecord->save();
                    } else {
                        // Fallback to single stock
                        $stock = ProductStock::where('product_id', $productId)->first();
                        if ($stock) {
                            $stock->available_stock = max(0, $stock->available_stock - $quantity);
                            $stock->sold_count = ($stock->sold_count ?? 0) + $quantity;
                            $stock->save();
                        }
                    }
                } else {
                    $stock = ProductStock::where('product_id', $productId)->first();
                    if ($stock) {
                        $stock->available_stock = max(0, $stock->available_stock - $quantity);
                        $stock->sold_count = ($stock->sold_count ?? 0) + $quantity;
                        $stock->save();
                    }
                }
            }

            // If payment was made, record it
            if ($paidAmount > 0) {
                $payment = $this->createPaymentRecord($sale, $paidAmount, $request->payment_method ?? 'cash', $userId);

                // Handle cheque creation if payment method is cheque
                if (($request->payment_method === 'cheque') && $payment && $request->has('cheques')) {
                    $cheques = $request->cheques ?? [];
                    foreach ($cheques as $chequeData) {
                        Cheque::create([
                            'cheque_number' => $chequeData['cheque_number'],
                            'cheque_date' => $chequeData['cheque_date'],
                            'bank_name' => $chequeData['bank_name'],
                            'cheque_amount' => $chequeData['cheque_amount'],
                            'status' => 'pending',
                            'customer_id' => $customerId,
                            'payment_id' => $payment->id,
                        ]);
                    }
                }
            }

            // Update customer credit balance if there's due amount
            if ($dueAmount > 0 && $customerId) {
                try {
                    $customer = Customer::find($customerId);
                    if ($customer) {
                        $customer->credit_balance = ($customer->credit_balance ?? 0) + $dueAmount;
                        $customer->save();
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to update customer credit balance: ' . $e->getMessage());
                }
            }

            DB::commit();

            $sale->load(['customer', 'items.product', 'payments', 'user']);

            // Send notifications to all admin users about the new sale
            try {
                $creator = Auth::user();
                $creatorName = $creator ? $creator->name : 'System';
                $admins = User::where('role', 'admin')->get();
                foreach ($admins as $admin) {
                    $admin->notify(new NewSaleNotification($sale, $creatorName));
                }

                // Check for low stock and notify admins
                foreach ($items as $item) {
                    $productId = $item['product_id'] ?? $item['product'];
                    $stock = ProductStock::where('product_id', $productId)->first();
                    if ($stock && $stock->available_stock <= 10) {
                        $product = ProductDetail::find($productId);
                        if ($product) {
                            $product->stock = $stock->available_stock;
                            foreach ($admins as $admin) {
                                $admin->notify(new LowStockNotification($product));
                            }
                        }
                    }
                }
            } catch (\Exception $notifErr) {
                Log::warning('Failed to send sale notification: ' . $notifErr->getMessage());
            }

            return $this->success($this->transformSale($sale, true), 'Sale created successfully', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Sale creation failed: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->error('Failed to create sale: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Record payment for a sale
     */
    public function recordPayment(Request $request, $saleId = null)
    {
        // Handle both route parameter and request body
        $saleId = $saleId ?? $request->sale_id;

        $sale = Sale::find($saleId);
        if (!$sale) {
            return $this->error('Sale not found', 404);
        }

        $amount = $request->amount ?? $request->paid_amount;
        if (!$amount || $amount <= 0) {
            return $this->error('Valid payment amount is required', 422);
        }

        if ($amount > $sale->due_amount) {
            return $this->error('Payment amount cannot exceed due amount (Rs. ' . number_format($sale->due_amount, 2) . ')', 422);
        }

        try {
            DB::beginTransaction();

            $userId = Auth::id();
            if (!$userId) {
                $apiUser = User::where('email', 'api@system.local')->first();
                $userId = $apiUser ? $apiUser->id : 1;
            }

            // Record payment with cheque details if applicable
            $chequeData = null;
            if ($request->payment_method === 'cheque') {
                $chequeData = [
                    'cheque_number' => $request->cheque_number,
                    'bank_name' => $request->bank_name,
                    'cheque_date' => $request->cheque_date,
                    'name' => $request->name,
                    'note' => $request->note,
                ];
            }

            $payment = $this->createPaymentRecord($sale, $amount, $request->payment_method ?? 'cash', $userId, $request->notes, $chequeData);

            // Update sale
            $newDueAmount = max(0, $sale->due_amount - $amount);
            $sale->due_amount = $newDueAmount;

            if ($newDueAmount <= 0) {
                $sale->payment_status = 'paid';
                $sale->payment_type = 'full';
            } else {
                $sale->payment_status = 'partial';
            }
            $sale->save();

            // Update customer credit balance
            if ($sale->customer_id) {
                try {
                    $customer = Customer::find($sale->customer_id);
                    if ($customer) {
                        $customer->credit_balance = max(0, ($customer->credit_balance ?? 0) - $amount);
                        $customer->save();
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to update customer credit balance on payment: ' . $e->getMessage());
                }
            }

            DB::commit();

            $sale->load(['customer', 'items.product', 'payments', 'user']);

            // Notify admins about the payment
            try {
                $admins = User::where('role', 'admin')->get();
                foreach ($admins as $admin) {
                    $admin->notify(new PaymentNotification($payment, 'received'));
                }
            } catch (\Exception $notifErr) {
                Log::warning('Failed to send payment notification: ' . $notifErr->getMessage());
            }

            return $this->success([
                'sale' => $this->transformSale($sale, true),
                'payment' => [
                    'id' => $payment->id ?? null,
                    'amount' => (float) $amount,
                    'method' => $request->payment_method ?? 'cash',
                    'date' => now()->toDateString(),
                ]
            ], 'Payment recorded successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment recording failed: ' . $e->getMessage());
            return $this->error('Failed to record payment: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get customer credit/due summary
     */
    public function customerDueSummary(Request $request, $customerId = null)
    {
        $customerId = $customerId ?? $request->customer_id;

        if (!$customerId) {
            return $this->error('Customer ID is required', 422);
        }

        $customer = Customer::find($customerId);
        if (!$customer) {
            return $this->error('Customer not found', 404);
        }

        $dueSales = Sale::with(['items.product'])
            ->where('customer_id', $customerId)
            ->where('due_amount', '>', 0)
            ->orderBy('created_at', 'desc')
            ->get();

        $totalDue = $dueSales->sum('due_amount');
        $totalSales = $dueSales->count();

        return $this->success([
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'credit_balance' => (float) ($customer->credit_balance ?? 0),
            ],
            'due_sales' => $dueSales->map(function ($sale) {
                return $this->transformSale($sale);
            }),
            'summary' => [
                'total_due_amount' => (float) $totalDue,
                'total_due_invoices' => $totalSales,
            ]
        ]);
    }

    /**
     * Helper to record payment internally
     */
    private function createPaymentRecord($sale, $amount, $method, $userId, $notes = null, $chequeData = null)
    {
        // Check if Payment model exists and has the expected structure
        try {
            if (class_exists('App\Models\Payment')) {
                $payment = Payment::create([
                    'sale_id' => $sale->id,
                    'customer_id' => $sale->customer_id,
                    'amount' => $amount,
                    'payment_method' => $method,
                    'payment_date' => now(),
                    'notes' => $notes,
                    'created_by' => $userId,
                    'status' => 'paid',
                ]);

                // If cheque payment, create Cheque record
                if ($method === 'cheque' && $chequeData) {
                    try {
                        Cheque::create([
                            'cheque_number' => $chequeData['cheque_number'],
                            'cheque_date' => $chequeData['cheque_date'] ?? now()->toDateString(),
                            'bank_name' => $chequeData['bank_name'],
                            'cheque_amount' => $amount,
                            'status' => 'pending', // Default to pending
                            'type' => 'receive', // Default to receive for customer payments
                            'name' => $chequeData['name'] ?? null,
                            'note' => $chequeData['note'] ?? $notes,
                            'customer_id' => $sale->customer_id,
                            'payment_id' => $payment->id,
                        ]);
                    } catch (\Exception $chequeErr) {
                        Log::warning('Failed to create cheque record: ' . $chequeErr->getMessage());
                    }
                }

                return $payment;
            }
        } catch (\Exception $e) {
            // Payment model might have different structure, log and continue
            Log::info('Payment record creation skipped: ' . $e->getMessage());
        }
        return null;
    }

    /**
     * Transform sale for API response
     */
    private function transformSale($sale, $detailed = false)
    {
        $paidAmount = $sale->total_amount - $sale->due_amount;

        $data = [
            'id' => $sale->id,
            'sale_id' => $sale->sale_id,
            'invoice_number' => $sale->invoice_number,
            'customer' => $sale->customer ? [
                'id' => $sale->customer->id,
                'name' => $sale->customer->name,
                'phone' => $sale->customer->phone,
                'credit_balance' => (float) ($sale->customer->credit_balance ?? 0),
            ] : null,
            'customer_name' => $sale->customer ? $sale->customer->name : 'Walk-in Customer',
            'customer_type' => $sale->customer_type,
            'sale_date' => $sale->created_at ? $sale->created_at->toDateString() : null,
            'subtotal' => (float) $sale->subtotal,
            'discount_amount' => (float) $sale->discount_amount,
            'total_amount' => (float) $sale->total_amount,
            'paid_amount' => (float) $paidAmount,
            'due_amount' => (float) $sale->due_amount,
            'balance_due' => (float) $sale->due_amount,
            'is_credit_sale' => $sale->due_amount > 0,
            'payment_type' => $sale->payment_type,
            'payment_method' => $sale->payment_type,
            'payment_status' => $sale->payment_status,
            'status' => $sale->status,
            'sale_type' => $sale->sale_type,
            'notes' => $sale->notes,
            'created_by' => $sale->user ? $sale->user->name : null,
            'created_at' => $sale->created_at,
        ];

        if ($detailed) {
            $data['items'] = $sale->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product' => $item->product ? [
                        'id' => $item->product->id,
                        'name' => $item->product->name,
                        'code' => $item->product->code,
                    ] : null,
                    'product_name' => $item->product_name,
                    'product_code' => $item->product_code,
                    'quantity' => $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'discount' => (float) ($item->discount_per_unit ?? 0),
                    'total' => (float) $item->total,
                    'variant_id' => $item->variant_id ?? null,
                    'variant_value' => $item->variant_value ?? null,
                ];
            });

            // Include payment history if available
            if ($sale->relationLoaded('payments') && $sale->payments) {
                $data['payments'] = $sale->payments->map(function ($payment) {
                    return [
                        'id' => $payment->id,
                        'amount' => (float) $payment->amount,
                        'method' => $payment->payment_method ?? 'cash',
                        'date' => $payment->payment_date ?? $payment->created_at,
                        'notes' => $payment->notes,
                    ];
                });
            } else {
                $data['payments'] = [];
            }
        }

        return $data;
    }
}
