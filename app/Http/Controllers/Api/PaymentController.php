<?php

namespace App\Http\Controllers\Api;

use App\Models\Payment;
use App\Models\PurchasePayment;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends ApiController
{
    /**
     * Get all payments with optional filters
     */
    public function index(Request $request)
    {
        $type = $request->get('payment_type', 'customer'); // Default to customer if not specified

        if ($type === 'supplier') {
            // Retrieve supplier payments
            $query = PurchasePayment::with(['purchaseOrder', 'supplier']);

            if ($request->has('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('payment_reference', 'like', "%{$search}%")
                        ->orWhereHas('supplier', function ($q2) use ($search) {
                            $q2->where('name', 'like', "%{$search}%");
                        });
                });
            }

            /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
            $paginator = $query->orderBy('payment_date', 'desc')->paginate(20);

            $paginator->through(function ($payment) {
                return $this->transformPurchasePayment($payment);
            });

            return $this->paginated($paginator);
        } else {
            // Retrieve customer payments (standard Payment model)
            $query = Payment::with(['sale', 'customer']);

            if ($request->has('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('payment_reference', 'like', "%{$search}%")
                        ->orWhere('id', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($q2) use ($search) {
                            $q2->where('name', 'like', "%{$search}%");
                        });
                });
            }

            // Explicitly filter for customer payments if possible, 
            // though Payment model seems dedicated to Sales/Customers mostly.
            $query->whereNotNull('customer_id');

            /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
            $paginator = $query->orderBy('payment_date', 'desc')->paginate(20);

            $paginator->through(function ($payment) {
                return $this->transformPayment($payment);
            });

            return $this->paginated($paginator);
        }
    }

    /**
     * Get a single payment
     */
    /**
     * Get a single payment
     */
    public function show($id, Request $request)
    {
        $type = $request->get('payment_type', 'customer');

        if ($type === 'supplier') {
            $payment = PurchasePayment::with(['purchaseOrder.items.product', 'supplier'])->find($id);
            if (!$payment)
                return $this->error('Payment not found', 404);
            return $this->success($this->transformPurchasePayment($payment));
        } else {
            // Default to customer
            // Try to find in standard payments
            $payment = Payment::with(['sale.items.product', 'customer'])->find($id);

            // If not found and no type specified, fallback to check supplier just in case (legacy behavior)
            if (!$payment && !$request->has('payment_type')) {
                $payment = PurchasePayment::with(['purchaseOrder.items.product', 'supplier'])->find($id);
                if ($payment)
                    return $this->success($this->transformPurchasePayment($payment));
            }

            if (!$payment)
                return $this->error('Payment not found', 404);
            return $this->success($this->transformPayment($payment));
        }
    }

    /**
     * Store request - mainly for supplier/unlinked payments since sales payments are handled by SaleController
     */
    /**
     * Store request - mainly for supplier/unlinked payments
     */
    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_type' => 'required|in:customer,supplier',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            if ($request->payment_type === 'supplier') {
                // Handle Supplier Payment using PurchasePayment model
                $poId = $request->purchase_order;
                $po = PurchaseOrder::find($poId);

                if (!$po) {
                    return $this->error('Purchase order required for supplier payment', 422);
                }

                // Normalize payment method - DB only accepts: cash, cheque, bank_transfer, others
                $allowedMethods = ['cash', 'cheque', 'bank_transfer', 'others'];
                $paymentMethod = $request->payment_method;
                if (!in_array($paymentMethod, $allowedMethods)) {
                    $paymentMethod = 'others'; // Map card, mobile_money, etc. to 'others'
                }

                // Prepare payment data matching AddSupplierReceipt logic
                $paymentData = [
                    'supplier_id' => $po->supplier_id,
                    'purchase_order_id' => $po->id,
                    'amount' => $request->amount,
                    'payment_method' => $paymentMethod,
                    'payment_date' => $request->payment_date,
                    'notes' => $request->notes,
                    'status' => 'paid',
                    'is_completed' => 1,
                    'payment_reference' => $request->reference,
                    'reference' => $request->reference,
                ];

                // Handle Check/Bank Details based on method
                if ($paymentMethod === 'cheque') {
                    $paymentData['cheque_number'] = $request->reference;
                    $paymentData['cheque_date'] = $request->payment_date;
                    $paymentData['cheque_status'] = 'pending';
                    $paymentData['status'] = 'pending';
                    $paymentData['is_completed'] = 0;
                } elseif ($paymentMethod === 'bank_transfer') {
                    $paymentData['bank_transaction'] = $request->reference;
                }

                // Create PurchasePayment
                $payment = PurchasePayment::create($paymentData);

                // Create Allocation
                \App\Models\PurchasePaymentAllocation::create([
                    'purchase_payment_id' => $payment->id,
                    'purchase_order_id' => $po->id,
                    'allocated_amount' => $request->amount,
                ]);

                // Create Cheque record if payment method is cheque
                if ($paymentMethod === 'cheque') {
                    try {
                        \App\Models\Cheque::create([
                            'cheque_number' => $request->cheque_number ?? $request->reference,
                            'cheque_date' => $request->cheque_date ?? $request->payment_date,
                            'bank_name' => $request->bank_name,
                            'cheque_amount' => $request->amount,
                            'status' => 'pending',
                            'type' => 'made', // This is a cheque we're making (paying out)
                            'name' => $request->name ?? null,
                            'note' => $request->note ?? $request->notes,
                            'customer_id' => null, // No customer for supplier payments
                            'payment_id' => $payment->id,
                        ]);
                    } catch (\Exception $chequeErr) {
                        Log::warning('Failed to create cheque record for supplier payment: ' . $chequeErr->getMessage());
                    }
                }

                // Update PO Due Amount
                $po->due_amount = max(0, $po->due_amount - $request->amount);
                $po->save();

                DB::commit();
                return $this->success($this->transformPurchasePayment($payment), 'Supplier payment recorded successfully', 201);

            } else {
                // Fallback for customer payments
                // If the frontend sends a customer payment here, we should probably handle it 
                // using the Payment model similar to AddCustomerReceipt logic, but simpler.

                $saleId = $request->sale;
                $sale = \App\Models\Sale::find($saleId);

                if ($sale) {
                    // Create Payment
                    $payment = Payment::create([
                        'customer_id' => $sale->customer_id,
                        'sale_id' => $sale->id,
                        'amount' => $request->amount,
                        'payment_method' => $request->payment_method ?? 'cash',
                        'payment_date' => $request->payment_date,
                        'notes' => $request->notes,
                        'status' => 'completed',
                        'is_completed' => true,
                        'payment_reference' => $request->reference,
                    ]);

                    // Create Cheque record if payment method is cheque
                    if (($request->payment_method ?? 'cash') === 'cheque') {
                        try {
                            \App\Models\Cheque::create([
                                'cheque_number' => $request->cheque_number ?? $request->reference,
                                'cheque_date' => $request->cheque_date ?? $request->payment_date,
                                'bank_name' => $request->bank_name,
                                'cheque_amount' => $request->amount,
                                'status' => 'pending',
                                'type' => 'receive', // This is a cheque we're receiving (customer payment)
                                'name' => $request->name ?? null,
                                'note' => $request->note ?? $request->notes,
                                'customer_id' => $sale->customer_id,
                                'payment_id' => $payment->id,
                            ]);
                        } catch (\Exception $chequeErr) {
                            Log::warning('Failed to create cheque record: ' . $chequeErr->getMessage());
                        }
                    }

                    // Create Allocation
                    DB::table('payment_allocations')->insert([
                        'payment_id' => $payment->id,
                        'sale_id' => $sale->id,
                        'allocated_amount' => $request->amount,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Update Sale
                    $sale->due_amount = max(0, $sale->due_amount - $request->amount);
                    if ($sale->due_amount <= 0.01) {
                        $sale->payment_status = 'paid';
                        $sale->due_amount = 0;
                    } else {
                        $sale->payment_status = 'partial';
                    }
                    $sale->save();

                    DB::commit();
                    return $this->success($this->transformPayment($payment), 'Payment recorded successfully', 201);
                }

                return $this->error('Sale ID required for customer payment via this endpoint', 400);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment update failed: ' . $e->getMessage());
            return $this->error('Failed to record payment: ' . $e->getMessage(), 500);
        }
    }

    private function transformPurchasePayment($payment)
    {
        // Safely handle payment_date which could be string or Carbon
        $paymentDate = $payment->payment_date;
        if ($paymentDate instanceof \Carbon\Carbon) {
            $paymentDate = $paymentDate->toDateString();
        } elseif (is_string($paymentDate)) {
            // Already a string, use as-is
        } else {
            $paymentDate = $payment->created_at?->toDateString() ?? date('Y-m-d');
        }

        return [
            'id' => $payment->id,
            'payment_number' => $payment->payment_reference ?? 'PAY-SUP-' . $payment->id,
            'payment_date' => $paymentDate,
            'amount' => (float) $payment->amount,
            'payment_type' => 'supplier',
            'payment_method' => $payment->payment_method ?? 'cash',
            'supplier' => $payment->supplier_id,
            'supplier_name' => $payment->supplier ? $payment->supplier->name : null,
            'purchase_order' => $payment->purchase_order_id,
            'reference' => $payment->reference ?? $payment->notes,
            'status' => $payment->status ?? 'paid',
            'notes' => $payment->notes,
            'created_at' => $payment->created_at,
            'purchase_order_details' => $payment->purchaseOrder, // Includes items if eager loaded
        ];
    }

    private function transformPayment($payment)
    {
        // Safely handle payment_date which could be string or Carbon
        $paymentDate = $payment->payment_date;
        if ($paymentDate instanceof \Carbon\Carbon) {
            $paymentDate = $paymentDate->toDateString();
        } elseif (is_string($paymentDate)) {
            // Already a string, no conversion needed
        } else {
            $paymentDate = $payment->created_at?->toDateString() ?? date('Y-m-d');
        }

        return [
            'id' => $payment->id,
            'payment_number' => 'PAY-' . $payment->id,
            'payment_date' => $paymentDate,
            'amount' => (float) $payment->amount,
            'payment_type' => $payment->customer_id ? 'customer' : 'supplier',
            'payment_method' => $payment->payment_method ?? 'cash',
            'customer' => $payment->customer_id,
            'customer_name' => $payment->customer ? $payment->customer->name : null,
            'sale' => $payment->sale_id,
            'invoice_number' => $payment->sale ? $payment->sale->invoice_number : null,
            'reference' => $payment->payment_reference ?? $payment->notes,
            'status' => $payment->status ?? 'paid',
            'notes' => $payment->notes,
            'created_at' => $payment->created_at,
            'sale_details' => $payment->sale, // Includes items if eager loaded
        ];
    }
}
