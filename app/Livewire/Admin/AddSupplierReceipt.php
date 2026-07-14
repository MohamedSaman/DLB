<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ProductSupplier;
use App\Models\PurchaseOrder;
use App\Models\PurchasePayment;
use App\Models\PurchasePaymentAllocation;
use App\Models\POSSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Livewire\Concerns\WithDynamicLayout;

#[Title("Add Supplier Receipt")]
class AddSupplierReceipt extends Component
{
    use WithDynamicLayout;

    use WithPagination;

    public $search = '';
    public $selectedSupplier = null;
    public $supplierOrders = [];
    public $selectedOrders = [];
    public $totalDueAmount = 0;
    public $totalPaymentAmount = 0;
    public $remainingAmount = 0;
    public $showPaymentModal = false;
    public $showReceiptModal = false;
    public $lastPayment = null;
    public $selectedOrderForView = null;
    public $showOrderDetailsModal = false;

    // Overpayment tracking
    public $supplierOverpayment = 0;
    public $useOverpayment = false;
    public $overpaymentToApply = 0;

    // Payment modal fields
    public $paymentData = [
        'payment_date' => '',
        'payment_method' => 'cash',
        'reference_number' => '',
        'notes' => ''
    ];

    public $cheque = [
        'cheque_number' => '',
        'bank_name' => '',
        'cheque_date' => '',
        'amount' => 0
    ];

    public $bankTransfer = [
        'bank_name' => '',
        'transfer_date' => '',
        'reference_number' => ''
    ];

    public $allocations = [];

    public function mount()
    {
        $this->paymentData['payment_date'] = now()->format('Y-m-d');
        $this->cheque['cheque_date'] = now()->format('Y-m-d');
        $this->bankTransfer['transfer_date'] = now()->format('Y-m-d');
    }

    public function updatedSearch($value)
    {
        $this->resetPage();
        $this->selectedSupplier = null;
        $this->supplierOrders = [];
        $this->resetPaymentData();
    }

    public function updatedTotalPaymentAmount()
    {
        $maxPayment = $this->totalDueAmount - $this->overpaymentToApply;
        if ($this->totalPaymentAmount > $maxPayment) {
            $this->totalPaymentAmount = $maxPayment;
        }
        if ($this->totalPaymentAmount < 0) {
            $this->totalPaymentAmount = 0;
        }

        $this->calculateRemainingAmount();
        $this->autoAllocatePayment();

        // Sync cheque amount
        if ($this->paymentData['payment_method'] === 'cheque') {
            $this->cheque['amount'] = $this->totalPaymentAmount;
        }
    }

    public function updatedPaymentDataPaymentMethod($value)
    {
        // Reset method-specific fields
        if ($value === 'cheque') {
            $this->cheque['cheque_date'] = now()->format('Y-m-d');
            $this->cheque['amount'] = $this->totalPaymentAmount;
        } else {
            $this->cheque = [
                'cheque_number' => '',
                'bank_name' => '',
                'cheque_date' => now()->format('Y-m-d'),
                'amount' => 0
            ];
        }

        if ($value === 'bank_transfer') {
            $this->bankTransfer['transfer_date'] = now()->format('Y-m-d');
        } else {
            $this->bankTransfer = [
                'bank_name' => '',
                'transfer_date' => now()->format('Y-m-d'),
                'reference_number' => ''
            ];
        }
    }

    public function selectSupplier($supplierId)
    {
        $this->selectedSupplier = ProductSupplier::find($supplierId);
        $this->loadSupplierOrders();
        $this->selectedOrders = [];
        $this->totalPaymentAmount = 0;
        $this->totalDueAmount = 0;
        $this->initializeAllocations();
        
        // Load supplier overpayment
        $this->supplierOverpayment = $this->selectedSupplier->getAvailableOverpayment();
        $this->useOverpayment = false;
        $this->overpaymentToApply = 0;
    }

    public function clearSelectedSupplier()
    {
        $this->selectedSupplier = null;
        $this->supplierOrders = [];
        $this->selectedOrders = [];
        $this->allocations = [];
        $this->totalDueAmount = 0;
        $this->totalPaymentAmount = 0;
        $this->remainingAmount = 0;
        $this->supplierOverpayment = 0;
        $this->useOverpayment = false;
        $this->overpaymentToApply = 0;
        $this->resetPaymentData();
    }

    private function loadSupplierOrders()
    {
        if (!$this->selectedSupplier) return;

        $orders = PurchaseOrder::where('supplier_id', $this->selectedSupplier->id)
            ->where('due_amount', '>', 0)
            ->orderBy('order_date', 'asc')
            ->get();

        $this->supplierOrders = $orders;
    }

    public function toggleOrderSelection($orderId)
    {
        if (in_array($orderId, $this->selectedOrders)) {
            $this->selectedOrders = array_values(array_diff($this->selectedOrders, [$orderId]));
        } else {
            $this->selectedOrders[] = $orderId;
        }
        
        $this->calculateTotalDue();
        $this->totalPaymentAmount = 0;
        $this->remainingAmount = $this->totalDueAmount;
        $this->initializeAllocations();
    }

    public function selectAllOrders()
    {
        $this->selectedOrders = collect($this->supplierOrders)->pluck('id')->toArray();
    $this->calculateTotalDue();
    $this->totalPaymentAmount = 0;
    $this->remainingAmount = $this->totalDueAmount;
    $this->initializeAllocations();
    }

    public function clearOrderSelection()
    {
        $this->selectedOrders = [];
        $this->totalDueAmount = 0;
        $this->totalPaymentAmount = 0;
        $this->remainingAmount = 0;
        $this->allocations = [];
    }

    private function calculateTotalDue()
    {
        $this->totalDueAmount = collect($this->supplierOrders)
            ->whereIn('id', $this->selectedOrders)
            ->sum('due_amount');
        $this->remainingAmount = $this->totalDueAmount;
        
        // Reset overpayment application when orders change
        $this->useOverpayment = false;
        $this->overpaymentToApply = 0;
    }

    public function toggleOverpayment()
    {
        $this->useOverpayment = !$this->useOverpayment;
        
        if ($this->useOverpayment && $this->supplierOverpayment > 0) {
            // Apply overpayment (max of available overpayment or total due)
            $this->overpaymentToApply = min($this->supplierOverpayment, $this->totalDueAmount);
        } else {
            $this->overpaymentToApply = 0;
        }
        
        $this->calculateRemainingAmount();
    }

    public function updatedOverpaymentToApply()
    {
        // Validate overpayment amount
        if ($this->overpaymentToApply > $this->supplierOverpayment) {
            $this->overpaymentToApply = $this->supplierOverpayment;
        }
        if ($this->overpaymentToApply > $this->totalDueAmount) {
            $this->overpaymentToApply = $this->totalDueAmount;
        }
        if ($this->overpaymentToApply < 0) {
            $this->overpaymentToApply = 0;
        }
        
        $this->calculateRemainingAmount();
    }

    private function calculateRemainingAmount()
    {
        $this->remainingAmount = $this->totalDueAmount - $this->totalPaymentAmount - $this->overpaymentToApply;
        if ($this->remainingAmount < 0) {
            $this->remainingAmount = 0;
        }
    }

    private function initializeAllocations()
    {
        $this->allocations = [];
        foreach ($this->supplierOrders as $order) {
            if (in_array($order->id, $this->selectedOrders)) {
                $this->allocations[$order->id] = [
                    'order_code' => $order->order_code,
                    'due_amount' => $order->due_amount,
                    'payment_amount' => 0,
                    'is_fully_paid' => false
                ];
            }
        }
    }

    private function autoAllocatePayment()
    {
        // Total payment includes both cash payment and overpayment credit
        $remainingPayment = $this->totalPaymentAmount + $this->overpaymentToApply;

        foreach ($this->supplierOrders as $order) {
            $orderId = $order->id;
            
            // Only allocate to selected orders
            if (!in_array($orderId, $this->selectedOrders)) {
                continue;
            }

            $dueAmount = $order->due_amount;

            if ($remainingPayment <= 0) {
                $this->allocations[$orderId]['payment_amount'] = 0;
                $this->allocations[$orderId]['is_fully_paid'] = false;
            } elseif ($remainingPayment >= $dueAmount) {
                $this->allocations[$orderId]['payment_amount'] = $dueAmount;
                $this->allocations[$orderId]['is_fully_paid'] = true;
                $remainingPayment -= $dueAmount;
            } else {
                $this->allocations[$orderId]['payment_amount'] = $remainingPayment;
                $this->allocations[$orderId]['is_fully_paid'] = false;
                $remainingPayment = 0;
            }
        }
    }

    public function openPaymentModal()
    {
        if (empty($this->selectedOrders)) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Please select at least one order to make a payment.'
            ]);
            return;
        }

        // Allow payment if either cash payment or overpayment is being applied
        if ($this->totalPaymentAmount <= 0 && $this->overpaymentToApply <= 0) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Please enter a payment amount or apply overpayment credit.'
            ]);
            return;
        }

        $totalPaymentWithOverpayment = $this->totalPaymentAmount + $this->overpaymentToApply;
        if ($totalPaymentWithOverpayment > $this->totalDueAmount) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Total payment amount cannot exceed total due amount.'
            ]);
            return;
        }

        $this->autoAllocatePayment();

        if ($this->paymentData['payment_method'] === 'cheque') {
            $this->cheque['amount'] = $this->totalPaymentAmount;
        }

        $this->showPaymentModal = true;
    }

    public function closePaymentModal()
    {
        $this->showPaymentModal = false;
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function closeReceiptModal()
    {
        $this->showReceiptModal = false;
        $this->lastPayment = null;
        
    $this->dispatch('refreshPage');
        
         
    }

    public function closeOrderDetailsModal()
    {
        $this->showOrderDetailsModal = false;
        $this->selectedOrderForView = null;
    }

    public function viewOrderDetails($orderId)
    {
        $this->selectedOrderForView = PurchaseOrder::with(['supplier', 'items.product.variant', 'returns.product'])
            ->find($orderId);
        $this->showOrderDetailsModal = true;
    }

    private function resetPaymentData()
    {
        $this->paymentData = [
            'payment_date' => now()->format('Y-m-d'),
            'payment_method' => 'cash',
            'reference_number' => '',
            'notes' => ''
        ];
        $this->totalPaymentAmount = 0;
        $this->cheque = [
            'cheque_number' => '',
            'bank_name' => '',
            'cheque_date' => now()->format('Y-m-d'),
            'amount' => 0
        ];
        $this->bankTransfer = [
            'bank_name' => '',
            'transfer_date' => now()->format('Y-m-d'),
            'reference_number' => ''
        ];
    }

    public function processPayment()
    {
        // Base validation - allow zero payment amount if using overpayment
        $minPayment = $this->overpaymentToApply > 0 ? 0 : 0.01;
        
        $this->validate([
            'paymentData.payment_date' => 'required|date',
            'paymentData.payment_method' => 'required|in:cash,cheque,bank_transfer,others',
            'totalPaymentAmount' => "required|numeric|min:{$minPayment}",
        ]);

        $totalPaymentWithOverpayment = $this->totalPaymentAmount + $this->overpaymentToApply;
        if ($totalPaymentWithOverpayment > $this->totalDueAmount) {
            $this->addError('totalPaymentAmount', 'Total payment amount cannot exceed total due.');
            return;
        }

        $hasAllocation = collect($this->allocations)->sum('payment_amount') > 0;
        if (!$hasAllocation) {
            $this->dispatch('show-toast', ['type' => 'error', 'message' => 'No payment allocated to any order.']);
            return;
        }

        // Conditional validation for cheque
        if ($this->paymentData['payment_method'] === 'cheque' && $this->totalPaymentAmount > 0) {
            $this->validate([
                'cheque.cheque_number' => 'required|string|max:255',
                'cheque.bank_name' => 'required|string|max:255',
                'cheque.cheque_date' => 'required|date',
                'cheque.amount' => 'required|numeric|min:0.01',
            ]);

            // Check for duplicate cheque number
            $exists = PurchasePayment::where('payment_method', 'cheque')
                ->where('cheque_number', $this->cheque['cheque_number'])
                ->exists();
            if ($exists) {
                $this->addError('cheque.cheque_number', 'This cheque number has already been used. Please enter a unique cheque number.');
                return;
            }

            if (abs($this->cheque['amount'] - $this->totalPaymentAmount) > 0.01) {
                $this->addError('cheque.amount', 'Cheque amount must match payment amount.');
                return;
            }
        }

        if ($this->paymentData['payment_method'] === 'bank_transfer' && $this->totalPaymentAmount > 0) {
            $this->validate([
                'bankTransfer.bank_name' => 'required|string|max:255',
                'bankTransfer.transfer_date' => 'required|date',
                'bankTransfer.reference_number' => 'required|string|max:255',
            ]);
        }

        DB::beginTransaction();
        try {
            $payment = null;
            $overpaymentUsed = $this->overpaymentToApply;

            // Create payment record only if there's actual payment (not just overpayment)
            if ($this->totalPaymentAmount > 0) {
                $paymentRecord = [
                    'supplier_id' => $this->selectedSupplier->id,
                    'amount' => $this->totalPaymentAmount,
                    'payment_method' => $this->paymentData['payment_method'],
                    'payment_reference' => $this->paymentData['reference_number'] ?? null,
                    'payment_date' => $this->paymentData['payment_date'],
                    'notes' => $this->paymentData['notes'] . ($overpaymentUsed > 0 ? " (Overpayment credit applied: " . number_format($overpaymentUsed, 2) . ")" : ""),
                    'status' => $this->paymentData['payment_method'] === 'cash' ? 'paid' : 'pending',
                    'is_completed' => $this->paymentData['payment_method'] === 'cash' ? 1 : 0,
                    'overpayment_used' => $overpaymentUsed,
                ];

                if ($this->paymentData['payment_method'] === 'cheque') {
                    $paymentRecord = array_merge($paymentRecord, [
                        'cheque_number' => $this->cheque['cheque_number'],
                        'bank_name' => $this->cheque['bank_name'],
                        'cheque_date' => $this->cheque['cheque_date'],
                        'cheque_status' => 'pending',
                    ]);
                }

                if ($this->paymentData['payment_method'] === 'bank_transfer') {
                    $paymentRecord = array_merge($paymentRecord, [
                        'bank_name' => $this->bankTransfer['bank_name'],
                        'bank_transaction' => $this->bankTransfer['reference_number'],
                    ]);
                }

                $payment = PurchasePayment::create($paymentRecord);
            } else {
                // Create a record for overpayment-only transaction
                $payment = PurchasePayment::create([
                    'supplier_id' => $this->selectedSupplier->id,
                    'amount' => 0,
                    'payment_method' => 'overpayment_credit',
                    'payment_date' => $this->paymentData['payment_date'],
                    'notes' => "Payment made using overpayment credit: " . number_format($overpaymentUsed, 2),
                    'status' => 'paid',
                    'is_completed' => 1,
                    'overpayment_used' => $overpaymentUsed,
                ]);
            }

            foreach ($this->allocations as $orderId => $allocation) {
                if ($allocation['payment_amount'] > 0) {
                    PurchasePaymentAllocation::create([
                        'purchase_payment_id' => $payment->id,
                        'purchase_order_id' => $orderId,
                        'allocated_amount' => $allocation['payment_amount'],
                    ]);

                    $order = PurchaseOrder::find($orderId);
                    if ($order) {
                        $order->due_amount -= $allocation['payment_amount'];
                        $order->due_amount = max(0, round($order->due_amount, 2));
                        $order->save();
                    }
                }
            }

            // Deduct overpayment from supplier if used
            if ($overpaymentUsed > 0) {
                $this->selectedSupplier->useOverpayment($overpaymentUsed);
            }

            // If payment method is cash, update POS session credit_payment
            if ($this->totalPaymentAmount > 0 && $this->paymentData['payment_method'] === 'cash') {
                $activeSession = POSSession::where('user_id', auth()->id())
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($activeSession) {
                    $activeSession->supplier_payment += $this->totalPaymentAmount;
                    $activeSession->save();
                }
            }

            DB::commit();

            // Store the last payment for receipt
            $this->lastPayment = PurchasePayment::with(['supplier', 'allocations.order'])
                ->find($payment->id);
            
            // Add overpayment info to lastPayment for display
            $this->lastPayment->overpayment_applied = $overpaymentUsed;

            $this->showPaymentModal = false;
            $this->showReceiptModal = true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment processing error: ' . $e->getMessage());
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Failed to save payment. Please try again.'
            ]);
        }
    }

    public function printReceipt()
    {
        $this->dispatch('print-receipt', ['paymentId' => $this->lastPayment->id]);
    }

    public function downloadReceipt()
    {
        if (!$this->lastPayment) {
            return;
        }

        // Load the payment with relationships for PDF
        $payment = PurchasePayment::with(['supplier', 'allocations.order'])
            ->find($this->lastPayment->id);

        $pdf = Pdf::loadView('components.payment-receipt', ['payment' => $payment]);
        
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'payment-receipt-' . $payment->id . '.pdf');
    }
    
    private function generateReceiptPDF()
    {
        // This is a simplified version - you might want to use DomPDF or similar
        $receipt = view('pdf.payment-receipt', ['payment' => $this->lastPayment])->render();
        // PDF generation logic would go here
    }

    public function getSuppliersProperty()
    {
        return ProductSupplier::with(['orders' => function ($query) {
            $query->where('due_amount', '>', 0);
        }])
            ->whereHas('orders', function ($query) {
                $query->where('due_amount', '>', 0);
            })
            ->when($this->search, function ($query) {
                $query->where('name', 'like', "%{$this->search}%");
            })
            ->orderBy('name')
            ->paginate(10);
    }

    /**
     * Format product name and append variant value if available. Tries to match by supplier_price or selling_price.
     */
    public function formatProductName($product, $unitPrice = null)
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

    public function render()
    {
        return view('livewire.admin.add-supplier-receipt', [
            'suppliers' => $this->suppliers
        ])->layout($this->layout);
    }
}