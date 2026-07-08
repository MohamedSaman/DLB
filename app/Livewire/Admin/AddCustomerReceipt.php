<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\Payment;
use App\Models\Cheque;
use App\Models\ReturnsProduct;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Title;
use Livewire\Attributes\Layout;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Livewire\Concerns\WithDynamicLayout;

#[Title("Add Customer Receipt")]
class AddCustomerReceipt extends Component
{
    use WithDynamicLayout;

    use WithPagination;

    public $search = '';
    public $userFilter = 'admin';
    public $selectedCustomer = null;
    public $customerSales = [];
    public $selectedInvoices = [];
    public $paymentData = [
        'payment_date' => '',
    ];

    public $payments = [];
    public $createdPayments = [];
    public $createdPaymentIds = [];

    // Cheque related properties (kept for legacy references if any)
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
    public $totalDueAmount = 0;
    public $totalPaymentAmount = 0;
    public $remainingAmount = 0;
    public $overpaidAmount = 0;
    public $showPaymentModal = false;
    public $showViewModal = false;
    public $showReceiptModal = false;
    public $selectedSale = null;
    public $latestPayment = null;
    public $paymentSuccess = false;

    protected function rules()
    {
        $rules = [
            'paymentData.payment_date' => 'required|date',
            'totalPaymentAmount' => 'required|numeric|min:0.01',
            'payments' => 'required|array|min:1',
            'payments.*.payment_method' => 'required|in:cash,cheque,bank_transfer',
            'payments.*.amount' => 'required|numeric|min:0.01',
            'payments.*.notes' => 'nullable|string|max:500',
        ];

        foreach ($this->payments as $index => $payment) {
            if (($payment['payment_method'] ?? '') === 'cheque') {
                $rules["payments.{$index}.cheque_number"] = 'required|string|max:50';
                $rules["payments.{$index}.bank_name"] = 'required|string|max:100';
                $rules["payments.{$index}.cheque_date"] = 'required|date';
            } elseif (($payment['payment_method'] ?? '') === 'bank_transfer') {
                $rules["payments.{$index}.bank_name"] = 'required|string|max:100';
                $rules["payments.{$index}.transfer_date"] = 'required|date';
                $rules["payments.{$index}.reference_number"] = 'required|string|max:100';
            }
        }

        return $rules;
    }

    protected function messages()
    {
        $messages = [
            'paymentData.payment_date.required' => 'Payment date is required.',
            'totalPaymentAmount.required' => 'Payment amount is required.',
            'totalPaymentAmount.min' => 'Payment amount must be at least Rs. 0.01',
            'payments.required' => 'At least one payment method is required.',
        ];

        foreach ($this->payments as $index => $payment) {
            $num = $index + 1;
            $messages["payments.{$index}.payment_method.required"] = "Payment method is required for payment #{$num}.";
            $messages["payments.{$index}.amount.required"] = "Amount is required for payment #{$num}.";
            $messages["payments.{$index}.amount.min"] = "Amount for payment #{$num} must be at least Rs. 0.01.";
            
            $messages["payments.{$index}.cheque_number.required"] = "Cheque number is required for payment #{$num}.";
            $messages["payments.{$index}.bank_name.required"] = "Bank name is required for payment #{$num}.";
            $messages["payments.{$index}.cheque_date.required"] = "Cheque date is required for payment #{$num}.";
            
            $messages["payments.{$index}.transfer_date.required"] = "Transfer date is required for payment #{$num}.";
            $messages["payments.{$index}.reference_number.required"] = "Reference number is required for payment #{$num}.";
        }

        return $messages;
    }

    public function mount()
    {
        $this->paymentData['payment_date'] = now()->format('Y-m-d');
        $this->totalPaymentAmount = 0;

        // Auto-load customer if customer_id is passed in query params
        $customerId = request()->query('customer_id');
        if ($customerId) {
            $customer = Customer::find($customerId);
            if ($customer) {
                $this->selectCustomer($customerId);
            }
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
        $this->selectedCustomer = null;
        $this->customerSales = [];
        $this->resetPaymentData();
    }

    public function updatedUserFilter()
    {
        $this->resetPage();
        $this->selectedCustomer = null;
        $this->customerSales = [];
        $this->resetPaymentData();
    }

    public function updatedTotalPaymentAmount()
    {
        if ($this->totalPaymentAmount === '' || $this->totalPaymentAmount === null) {
            return;
        }

        if (floatval($this->totalPaymentAmount) < 0) {
            $this->totalPaymentAmount = 0;
        }

        $this->calculateRemainingAmount();
        $this->autoAllocatePayment();
    }

    public function updatedPayments($value, $key)
    {
        $parts = explode('.', $key);
        if (count($parts) >= 2) {
            $index = $parts[0];
            $field = $parts[1];

            if ($field === 'amount') {
                if ($value === '' || $value === null) {
                    // Let it be empty/null so they can type
                } elseif (floatval($value) < 0) {
                    $this->payments[$index]['amount'] = 0;
                }
            }
        }

        $this->updatePaymentTotals();
    }

    public function addPaymentMethod()
    {
        $totalAllocated = collect($this->payments)->sum(function ($payment) {
            return floatval($payment['amount'] ?? 0);
        });
        $remaining = max(0, floatval($this->totalPaymentAmount) - $totalAllocated);

        $this->payments[] = [
            'payment_method' => 'cash',
            'amount' => $remaining,
            'cheque_number' => '',
            'bank_name' => '',
            'cheque_date' => now()->format('Y-m-d'),
            'transfer_date' => now()->format('Y-m-d'),
            'reference_number' => '',
            'reference_number_opt' => '',
            'notes' => ''
        ];

        $this->updatePaymentTotals();
    }

    public function removePaymentMethod($index)
    {
        if (count($this->payments) > 1) {
            unset($this->payments[$index]);
            $this->payments = array_values($this->payments);
            $this->updatePaymentTotals();
        }
    }

    public function updatePaymentTotals()
    {
        $this->totalPaymentAmount = collect($this->payments)->sum(function ($payment) {
            return floatval($payment['amount'] ?? 0);
        });
        $this->calculateRemainingAmount();
        $this->autoAllocatePayment();
    }

    public function selectCustomer($customerId)
    {
        $this->selectedCustomer = Customer::find($customerId);
        $this->loadCustomerSales();
        $this->selectedInvoices = [];
        $this->totalPaymentAmount = 0;
        $this->totalDueAmount = 0;
        $this->initializeAllocations();
    }

    public function clearSelectedCustomer()
    {
        $this->selectedCustomer = null;
        $this->customerSales = [];
        $this->selectedInvoices = [];
        $this->allocations = [];
        $this->totalDueAmount = 0;
        $this->totalPaymentAmount = 0;
        $this->remainingAmount = 0;
        $this->resetPaymentData();
    }

    /**
     * Toggle invoice selection
     */
    public function toggleInvoiceSelection($saleId)
    {
        if (in_array($saleId, $this->selectedInvoices)) {
            $this->selectedInvoices = array_values(array_diff($this->selectedInvoices, [$saleId]));
        } else {
            $this->selectedInvoices[] = $saleId;
        }

        $this->calculateTotalDue();
        $this->totalPaymentAmount = 0;
        $this->remainingAmount = $this->totalDueAmount;
        $this->initializeAllocations();
    }

    /**
     * Select all invoices
     */
    public function selectAllInvoices()
    {
        $this->selectedInvoices = array_column($this->customerSales, 'id');
        $this->calculateTotalDue();
        $this->totalPaymentAmount = 0;
        $this->remainingAmount = $this->totalDueAmount;
        $this->initializeAllocations();
    }

    /**
     * Clear invoice selection
     */
    public function clearInvoiceSelection()
    {
        $this->selectedInvoices = [];
        $this->totalDueAmount = 0;
        $this->totalPaymentAmount = 0;
        $this->remainingAmount = 0;
        $this->allocations = [];
    }

    /**
     * Load customer sales with opening balance
     */
    private function loadCustomerSales()
    {
        if (!$this->selectedCustomer) return;

        // Start with opening balance if customer has one
        $salesList = [];
        $openingBalance = $this->selectedCustomer->opening_balance ?? 0;
        $openingBalancePaid = $this->selectedCustomer->opening_balance_paid ?? 0;
        $openingBalanceDue = max(0, $openingBalance - $openingBalancePaid);

        if ($openingBalanceDue > 0) {
            $salesList[] = [
                'id' => 'opening_balance_' . $this->selectedCustomer->id,
                'invoice_number' => 'Opening Balance',
                'sale_id' => 'OB',
                'sale_date' => 'N/A',
                'total_amount' => $openingBalance,
                'due_amount' => $openingBalanceDue,
                'paid_amount' => $openingBalancePaid,
                'payment_status' => $openingBalancePaid > 0 ? 'partial' : 'pending',
                'items_count' => 0,
                'is_opening_balance' => true,
            ];
        }

        // Add returned cheques
        $returnedCheques = Cheque::where('customer_id', $this->selectedCustomer->id)
            ->where('status', 'return')
            ->get();
            
        foreach ($returnedCheques as $cheque) {
            $salesList[] = [
                'id' => 'returned_cheque_' . $cheque->id,
                'invoice_number' => 'Returned Cheque: ' . $cheque->cheque_number,
                'sale_id' => 'CHK-' . $cheque->cheque_number,
                'sale_date' => $cheque->cheque_date,
                'total_amount' => $cheque->cheque_amount,
                'due_amount' => $cheque->cheque_amount,
                'paid_amount' => 0,
                'payment_status' => 'pending',
                'items_count' => 0,
                'is_opening_balance' => false,
                'is_returned_cheque' => true,
                'cheque_id' => $cheque->id
            ];
        }

        $query = Sale::with(['items', 'payments', 'returns'])
            ->where('customer_id', $this->selectedCustomer->id)
            ->where(function ($query) {
                $query->where('payment_status', 'pending')
                    ->orWhere('payment_status', 'partial');
            });

        // Filter by user for staff
        if ($this->isStaff()) {
            $query->where('user_id', Auth::id())->where('sale_type', 'staff');
        }

        $sales = $query->orderBy('created_at', 'asc')
            ->get();

        $mappedSales = $sales->map(function ($sale) {
            $paidAmount = $sale->total_amount - $sale->due_amount;

            // Note: due_amount is already adjusted by return processing in ReturnProduct component
            // Do NOT calculate return amounts here to avoid double reduction

            return [
                'id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'sale_id' => $sale->sale_id,
                'sale_date' => $sale->created_at->format('M d, Y'),
                'total_amount' => $sale->total_amount,
                'due_amount' => $sale->due_amount,
                'paid_amount' => $paidAmount,
                'payment_status' => $sale->payment_status,
                'items_count' => $sale->items->count(),
                'is_opening_balance' => false,
            ];
        })->filter(function ($sale) {
            // Only show sales with due amount > 0
            return $sale['due_amount'] > 0.01;
        })->values()->toArray();

        // Merge opening balance with sales
        $this->customerSales = array_merge($salesList, $mappedSales);

        $this->calculateTotalDue();
    }

    /**
     * Calculate total due amount for selected invoices (including opening balance if selected)
     */
    private function calculateTotalDue()
    {
        $this->totalDueAmount = collect($this->customerSales)
            ->whereIn('id', $this->selectedInvoices)
            ->sum('due_amount');
        $this->remainingAmount = $this->totalDueAmount;
    }

    private function calculateRemainingAmount()
    {
        $this->remainingAmount = $this->totalDueAmount - $this->totalPaymentAmount;
        $this->overpaidAmount = $this->totalPaymentAmount > $this->totalDueAmount
            ? $this->totalPaymentAmount - $this->totalDueAmount
            : 0;
    }

    private function initializeAllocations()
    {
        $this->allocations = [];

        foreach ($this->customerSales as $sale) {
            if (in_array($sale['id'], $this->selectedInvoices)) {
                $this->allocations[$sale['id']] = [
                    'sale_id' => $sale['id'],
                    'invoice_number' => $sale['invoice_number'],
                    'due_amount' => $sale['due_amount'],
                    'payment_amount' => 0,
                    'is_fully_paid' => false
                ];
            }
        }
    }

    private function autoAllocatePayment()
    {
        $remainingPayment = floatval($this->totalPaymentAmount);

        foreach ($this->customerSales as $sale) {
            $saleId = $sale['id'];

            // Only allocate to selected invoices
            if (!in_array($saleId, $this->selectedInvoices)) {
                continue;
            }

            $dueAmount = $sale['due_amount'];

            if ($remainingPayment <= 0) {
                $this->allocations[$saleId]['payment_amount'] = 0;
                $this->allocations[$saleId]['is_fully_paid'] = false;
            } elseif ($remainingPayment >= $dueAmount) {
                $this->allocations[$saleId]['payment_amount'] = $dueAmount;
                $this->allocations[$saleId]['is_fully_paid'] = true;
                $remainingPayment -= $dueAmount;
            } else {
                $this->allocations[$saleId]['payment_amount'] = $remainingPayment;
                $this->allocations[$saleId]['is_fully_paid'] = false;
                $remainingPayment = 0;
            }
        }
    }

    public function openPaymentModal()
    {
        if (empty($this->selectedInvoices)) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Please select at least one invoice to make a payment.'
            ]);
            return;
        }

        // Validate payment amount
        if (!$this->totalPaymentAmount || $this->totalPaymentAmount <= 0) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Please enter a payment amount greater than zero.'
            ]);
            return;
        }

        // Initialize payments array with a single default payment (Cash)
        $this->payments = [
            [
                'payment_method' => 'cash',
                'amount' => $this->totalPaymentAmount,
                'cheque_number' => '',
                'bank_name' => '',
                'cheque_date' => now()->format('Y-m-d'),
                'transfer_date' => now()->format('Y-m-d'),
                'reference_number' => '',
                'reference_number_opt' => '',
                'notes' => ''
            ]
        ];

        // Allocate payment
        $this->autoAllocatePayment();

        // Show modal
        $this->showPaymentModal = true;
    }

    public function closePaymentModal()
    {
        $this->showPaymentModal = false;
        $this->paymentSuccess = false;
    }

    public function closeViewModal()
    {
        $this->showViewModal = false;
        $this->selectedSale = null;
    }

    public function openReceiptModal()
    {
        $this->showReceiptModal = true;
    }

    public function closeReceiptModal()
    {
        $this->showReceiptModal = false;
        $this->latestPayment = null;

        // Reset everything
        $this->selectedCustomer = null;
        $this->customerSales = [];
        $this->selectedInvoices = [];
        $this->allocations = [];
        $this->totalDueAmount = 0;
        $this->totalPaymentAmount = 0;
        $this->remainingAmount = 0;
        $this->payments = [];
        $this->createdPayments = [];
        $this->createdPaymentIds = [];
        $this->search = '';
        $this->resetPaymentData();

        // Reset page
        $this->resetPage();

        // Dispatch event to refresh the page
        $this->dispatch('payment-completed');
    }

    private function resetPaymentData()
    {
        $this->paymentData = [
            'payment_date' => now()->format('Y-m-d'),
        ];
        $this->totalPaymentAmount = 0;
        $this->payments = [];
        $this->createdPayments = [];
        $this->createdPaymentIds = [];
    }

    public function viewSale($saleId)
    {
        $this->selectedSale = Sale::with(['customer', 'items', 'payments', 'returns.product'])->find($saleId);
        $this->showViewModal = true;
    }

    public function processPayment()
    {
        Log::info('Payment processing started', [
            'customer_id' => $this->selectedCustomer->id,
            'amount' => $this->totalPaymentAmount,
        ]);

        // Validate inputs
        try {
            $this->validate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed', ['errors' => $e->errors()]);

            // Get first error message
            $firstError = collect($e->errors())->flatten()->first();

            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => $firstError ?? 'Please fill all required fields correctly.'
            ]);
            return;
        }

        // Additional validation: Check for duplicate cheque numbers
        $chequeNumbers = [];
        foreach ($this->payments as $index => $paymentItem) {
            if ($paymentItem['payment_method'] === 'cheque') {
                $chkNo = $paymentItem['cheque_number'];
                
                // Check within the request
                if (in_array($chkNo, $chequeNumbers)) {
                    $this->dispatch('show-toast', [
                        'type' => 'error',
                        'message' => "Duplicate cheque number '{$chkNo}' entered in the form."
                    ]);
                    return;
                }
                $chequeNumbers[] = $chkNo;

                // Check in database
                $existingCheque = Cheque::where('cheque_number', $chkNo)->first();
                if ($existingCheque) {
                    $this->dispatch('show-toast', [
                        'type' => 'error',
                        'message' => "Cheque number '{$chkNo}' already exists in the system. Please use a different cheque number."
                    ]);
                    return;
                }
            }
        }

        try {
            DB::beginTransaction();

            $totalProcessed = 0;
            $processedInvoices = [];
            $this->createdPaymentIds = [];

            // Prepare sales/due items list to allocate payments sequentially
            $salesToAllocate = [];
            foreach ($this->customerSales as $sale) {
                if (in_array($sale['id'], $this->selectedInvoices)) {
                    $salesToAllocate[] = [
                        'id' => $sale['id'],
                        'invoice_number' => $sale['invoice_number'],
                        'due_amount' => $sale['due_amount'],
                        'is_opening_balance' => $sale['is_opening_balance'] ?? false,
                        'is_returned_cheque' => $sale['is_returned_cheque'] ?? false,
                        'cheque_id' => $sale['cheque_id'] ?? null,
                        'allocated' => 0,
                    ];
                }
            }

            // Loop through each payment item and process it
            foreach ($this->payments as $paymentItem) {
                $paymentAmount = floatval($paymentItem['amount']);
                if ($paymentAmount <= 0) continue;

                $paymentData = [
                    'customer_id' => $this->selectedCustomer->id,
                    'amount' => $paymentAmount,
                    'payment_method' => $paymentItem['payment_method'],
                    'payment_reference' => $paymentItem['reference_number_opt'] ?? null,
                    'payment_date' => $this->paymentData['payment_date'],
                    'status' => 'paid',
                    'is_completed' => 1,
                    'notes' => $paymentItem['notes'] ?? null,
                    'created_by' => Auth::id(),
                ];

                if ($paymentItem['payment_method'] === 'bank_transfer') {
                    $paymentData['bank_name'] = $paymentItem['bank_name'];
                    $paymentData['transfer_date'] = $paymentItem['transfer_date'];
                    $paymentData['transfer_reference'] = $paymentItem['reference_number'];
                }

                $payment = Payment::create($paymentData);
                $this->createdPaymentIds[] = $payment->id;

                Log::info('Payment record created', [
                    'payment_id' => $payment->id,
                    'amount' => $paymentAmount,
                    'method' => $paymentItem['payment_method']
                ]);

                if ($paymentItem['payment_method'] === 'cheque') {
                    Cheque::create([
                        'payment_id' => $payment->id,
                        'cheque_number' => $paymentItem['cheque_number'],
                        'bank_name' => $paymentItem['bank_name'],
                        'cheque_date' => $paymentItem['cheque_date'],
                        'cheque_amount' => $paymentAmount,
                        'status' => 'pending',
                        'customer_id' => $this->selectedCustomer->id,
                    ]);
                    Log::info('Cheque created for payment', ['payment_id' => $payment->id]);
                }

                // Allocate this payment's amount sequentially
                $remainingPaymentToAllocate = $paymentAmount;

                foreach ($salesToAllocate as &$sale) {
                    if ($remainingPaymentToAllocate <= 0) {
                        break;
                    }

                    $saleRemainingDue = $sale['due_amount'] - $sale['allocated'];
                    if ($saleRemainingDue <= 0.001) {
                        continue;
                    }

                    $allocateAmount = min($remainingPaymentToAllocate, $saleRemainingDue);
                    $sale['allocated'] += $allocateAmount;
                    $remainingPaymentToAllocate -= $allocateAmount;

                    if ($sale['is_opening_balance']) {
                        $this->selectedCustomer->opening_balance_paid = ($this->selectedCustomer->opening_balance_paid ?? 0) + $allocateAmount;
                        $this->selectedCustomer->save();

                        DB::table('payment_allocations')->insert([
                            'payment_id' => $payment->id,
                            'sale_id' => null,
                            'allocated_amount' => $allocateAmount,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        Log::info('Allocation to opening balance', [
                            'payment_id' => $payment->id,
                            'allocated_amount' => $allocateAmount
                        ]);
                    } elseif ($sale['is_returned_cheque']) {
                        $chequeModel = Cheque::find($sale['cheque_id']);
                        if ($chequeModel) {
                            if ($sale['allocated'] >= $sale['due_amount'] - 0.01) {
                                $chequeModel->status = 'complete';
                                $chequeModel->save();
                                Log::info('Returned cheque fully paid', ['cheque_id' => $chequeModel->id]);
                            }

                            DB::table('payment_allocations')->insert([
                                'payment_id' => $payment->id,
                                'sale_id' => null,
                                'allocated_amount' => $allocateAmount,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);

                            Log::info('Allocation to returned cheque', [
                                'payment_id' => $payment->id,
                                'cheque_id' => $chequeModel->id,
                                'allocated_amount' => $allocateAmount
                            ]);
                        }
                    } else {
                        $saleModel = Sale::find($sale['id']);
                        if ($saleModel) {
                            $newDueAmount = $saleModel->due_amount - $allocateAmount;
                            $saleModel->due_amount = max(0, $newDueAmount);

                            if ($saleModel->due_amount <= 0.01) {
                                $saleModel->payment_status = 'paid';
                                $saleModel->due_amount = 0;
                            } else {
                                $saleModel->payment_status = 'partial';
                            }
                            $saleModel->save();

                            DB::table('payment_allocations')->insert([
                                'payment_id' => $payment->id,
                                'sale_id' => $sale['id'],
                                'allocated_amount' => $allocateAmount,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);

                            Log::info('Allocation to sale', [
                                'payment_id' => $payment->id,
                                'sale_id' => $sale['id'],
                                'allocated_amount' => $allocateAmount,
                                'new_due' => $saleModel->due_amount
                            ]);
                        }

                        // Reduce customer's due_amount
                        if ($this->selectedCustomer->due_amount > 0) {
                            $this->selectedCustomer->due_amount = max(0, $this->selectedCustomer->due_amount - $allocateAmount);
                            $this->selectedCustomer->save();
                        }
                    }

                    if (!in_array($sale['invoice_number'], $processedInvoices)) {
                        $processedInvoices[] = $sale['invoice_number'];
                    }
                }
                unset($sale);

                // Handle overpayment for this payment
                if ($remainingPaymentToAllocate > 0.01) {
                    $this->selectedCustomer->overpaid_amount = ($this->selectedCustomer->overpaid_amount ?? 0) + $remainingPaymentToAllocate;
                    $this->selectedCustomer->save();

                    Log::info('Overpayment recorded for payment', [
                        'payment_id' => $payment->id,
                        'overpaid_amount' => $remainingPaymentToAllocate
                    ]);
                }

                $totalProcessed += $paymentAmount;
            }

            // Recalculate customer total_due before commit
            $returnedChequesAmount = Cheque::where('customer_id', $this->selectedCustomer->id)->where('status', 'return')->sum('cheque_amount');
            $this->selectedCustomer->total_due = (($this->selectedCustomer->opening_balance ?? 0) - ($this->selectedCustomer->opening_balance_paid ?? 0)) + ($this->selectedCustomer->due_amount ?? 0) + $returnedChequesAmount - ($this->selectedCustomer->overpaid_amount ?? 0);
            $this->selectedCustomer->save();

            DB::commit();

            Log::info('All payments processed successfully', [
                'total_processed' => $totalProcessed,
                'invoices' => $processedInvoices,
                'payment_ids' => $this->createdPaymentIds
            ]);

            // Set state for receipt modal
            $this->createdPayments = Payment::with(['cheques'])->whereIn('id', $this->createdPaymentIds)->get();
            $this->latestPayment = $this->createdPayments->first(); // fallback for compatibility
            $this->paymentSuccess = true;
            $this->showPaymentModal = false;

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => "Payment of Rs." . number_format($totalProcessed, 2) . " processed successfully!"
            ]);

            // Open receipt modal
            $this->openReceiptModal();
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Payment processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Check if it's a duplicate entry error for cheque number
            $errorMessage = $e->getMessage();
            if (strpos($errorMessage, 'Duplicate entry') !== false && strpos($errorMessage, 'cheques_cheque_number_unique') !== false) {
                // Extract cheque number from error message
                preg_match("/Duplicate entry '([^']+)'/", $errorMessage, $matches);
                $chequeNumber = $matches[1] ?? 'unknown';

                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => "Cheque number '{$chequeNumber}' already exists in the system. Please use a different cheque number."
                ]);
            } else {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => 'Failed to process payment: ' . $e->getMessage()
                ]);
            }
        }
    }

    public function downloadReceipt()
    {
        if (empty($this->createdPaymentIds)) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'No payment receipt available to download.'
            ]);
            return;
        }

        try {
            // Load payments with relationships
            $payments = Payment::with(['cheques', 'allocations'])
                ->whereIn('id', $this->createdPaymentIds)
                ->get();

            if ($payments->isEmpty()) {
                throw new \Exception("Payments not found.");
            }

            // Get allocations from payment_allocations table with return information
            $allocations = DB::table('payment_allocations')
                ->join('sales', 'payment_allocations.sale_id', '=', 'sales.id')
                ->whereIn('payment_allocations.payment_id', $this->createdPaymentIds)
                ->select(
                    'sales.id as sale_id',
                    'sales.invoice_number',
                    'sales.total_amount',
                    DB::raw('SUM(payment_allocations.allocated_amount) as allocated_amount')
                )
                ->groupBy('sales.id', 'sales.invoice_number', 'sales.total_amount')
                ->get();

            // Check if any payment is a cheque and collect all cheques
            $allCheques = [];
            foreach ($payments as $payment) {
                foreach ($payment->cheques as $cheque) {
                    $allCheques[] = $cheque;
                }
            }

            // For backward compatibility and header info, pass the first payment as $payment
            $firstPayment = $payments->first();

            $receiptData = [
                'payments' => $payments, // Pass the collection of payments
                'payment' => $firstPayment, // For compatibility
                'customer' => $this->selectedCustomer,
                'received_by' => Auth::user()->name,
                'payment_date' => $firstPayment->payment_date,
                'allocations' => $allocations,
                'allCheques' => $allCheques,
                'totalAmountPaid' => $payments->sum('amount'),
            ];

            $pdf = PDF::loadView('admin.receipts.payment-receipt', $receiptData);
            $pdf->setPaper('a4', 'portrait');

            $filename = 'payment-receipt-' . implode('-', $this->createdPaymentIds) . '-' . date('Y-m-d') . '.pdf';

            return response()->streamDownload(
                function () use ($pdf) {
                    echo $pdf->output();
                },
                $filename
            );
        } catch (\Exception $e) {
            Log::error('Receipt download failed', [
                'error' => $e->getMessage(),
                'payment_ids' => $this->createdPaymentIds
            ]);

            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Failed to generate receipt: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Calculate customer total due amount including opening balance
     */
    public function getCustomerTotalDue($customer)
    {
        $salesDue = $customer->sales
            ->whereIn('payment_status', ['pending', 'partial'])
            ->sum(function ($sale) {
                $returnAmount = $sale->returns ? $sale->returns->sum('total_amount') : 0;
                return max(0, $sale->due_amount - $returnAmount);
            });

        $openingBalance = $customer->opening_balance ?? 0;
        $openingBalancePaid = $customer->opening_balance_paid ?? 0;
        $openingBalanceDue = max(0, $openingBalance - $openingBalancePaid);

        $returnedChequesDue = \App\Models\Cheque::where('customer_id', $customer->id)
            ->where('status', 'return')
            ->sum('cheque_amount');

        return $openingBalanceDue + $salesDue + $returnedChequesDue;
    }

    public function getCustomersProperty()
    {
        return Customer::with(['sales' => function ($query) {
            $query->where(function ($q) {
                $q->where('payment_status', 'pending')
                    ->orWhere('payment_status', 'partial');
            });

            // Filter by user if not 'all'
            if ($this->userFilter !== 'all') {
                if ($this->userFilter === 'admin') {
                    // Show only admin sales (where user_id is null or user role is admin)
                    $query->where(function ($q) {
                        $q->whereNull('user_id')
                            ->orWhereHas('user', function ($userQuery) {
                                $userQuery->where('role', 'admin');
                            });
                    });
                } else {
                    // Filter by specific user ID
                    $query->where('user_id', $this->userFilter);
                }
            }
        }])
            ->where(function ($query) {
                // Show customers with either pending/partial sales OR opening balance
                $query->whereHas('sales', function ($q) {
                    $q->where(function ($sq) {
                        $sq->where('payment_status', 'pending')
                            ->orWhere('payment_status', 'partial');
                    });

                    // Apply the same user filter to whereHas
                    if ($this->userFilter !== 'all') {
                        if ($this->userFilter === 'admin') {
                            $q->where(function ($sq) {
                                $sq->whereNull('user_id')
                                    ->orWhereHas('user', function ($userQuery) {
                                        $userQuery->where('role', 'admin');
                                    });
                            });
                        } else {
                            $q->where('user_id', $this->userFilter);
                        }
                    }
                })
                    ->orWhere('opening_balance', '>', 0) // Also show if they have opening balance
                    ->orWhereHas('cheques', function ($q) {
                        $q->where('status', 'return');
                    });
            })
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('phone', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy('name')
            ->paginate(10);
    }

    public function getUsersProperty()
    {
        return User::where('role', 'staff')
            ->orderBy('name')
            ->get();
    }

    public function render()
    {
        return view('livewire.admin.add-customer-receipt', [
            'customers' => $this->customers,
            'users' => $this->users
        ])->layout($this->layout);
    }
}
