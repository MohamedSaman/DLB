<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Customer;
use App\Models\Cheque;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Livewire\Concerns\WithDynamicLayout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

#[Title('List Customer Receipt')]
class ListCustomerReceipt extends Component
{
    use WithDynamicLayout;

    use WithPagination;

    public $showPaymentModal = false;
    public $showReceiptModal = false;
    public $showEditPaymentModal = false;
    public $selectedCustomer = null;
    public $selectedPayment = null;
    public $editingPayment = null;
    public $editPaymentData = [];
    public $editCheques = [];
    public $payments = [];
    public $invoiceSearch = '';
    public $customerSearch = '';
    // Filter: all | admin | staff
    public $recipientFilter = 'all';
    // selected collector id when filtering for specific user
    public $selectedCollector = null;
    public $staffUsers = [];
    public $adminUsers = [];
    public $staffIds = [];
    public $adminIds = [];

    public function getCustomersProperty()
    {
        // Get customers with total paid and receipt count (sum from payments table)
        $query = Customer::select(
            'customers.id',
            'customers.name',
            'customers.email',
            'customers.address',
            'customers.created_at',
            'customers.updated_at'
        )
            ->selectRaw('COALESCE(SUM(payments.amount),0) as total_paid')
            ->selectRaw('COUNT(payments.id) as receipts_count')
            ->leftJoin('payments', 'payments.customer_id', '=', 'customers.id');

        if (!empty(trim($this->customerSearch))) {
            $search = trim($this->customerSearch);
            $query->where(function ($q) use ($search) {
                $q->where('customers.name', 'like', '%' . $search . '%')
                    ->orWhere('customers.email', 'like', '%' . $search . '%')
                    ->orWhere('customers.address', 'like', '%' . $search . '%');
            });
        }

        // Filter by user for staff - only show customers with payments from their sales
        if ($this->isStaff()) {
            $query->leftJoin('sales', 'payments.sale_id', '=', 'sales.id')
                ->where('sales.user_id', Auth::id())
                ->where('sales.sale_type', 'staff');
        }

        // Filter by who collected the payment (admin/staff/specific user)
        if ($this->recipientFilter !== 'all') {
            // compute ids at runtime to avoid stale data from mount
            $adminIds = User::where('role', 'admin')->pluck('id')->toArray();
            $staffIds = User::where('role', 'staff')->pluck('id')->toArray();

            if ($this->selectedCollector) {
                $query->where('payments.collected_by', (int) $this->selectedCollector);
            } else {
                if ($this->recipientFilter === 'admin') {
                    $query->whereIn('payments.collected_by', $adminIds ?: [0]);
                }

                if ($this->recipientFilter === 'staff') {
                    $query->whereIn('payments.collected_by', $staffIds ?: [0]);
                }
            }
        }

        // Debug: log filter state and sample SQL
        try {
            \Log::info('Customer list filter', [
                'recipientFilter' => $this->recipientFilter,
                'selectedCollector' => $this->selectedCollector,
                'adminIds_count' => User::where('role', 'admin')->count(),
                'staffIds_count' => User::where('role', 'staff')->count(),
            ]);
        } catch (\Throwable $e) {
            // ignore logging errors
        }

        return $query->groupBy(
            'customers.id',
            'customers.name',
            'customers.email',
            'customers.address',
            'customers.created_at',
            'customers.updated_at'
        )
            ->having('total_paid', '>', 0)
            ->orderByDesc('total_paid')
            ->paginate(20);
    }

    public function showCustomerPayments($customerId)
    {
        $this->selectedCustomer = Customer::find($customerId);

        $query = Payment::with(['allocations', 'allocations.sale', 'cheques'])
            ->where('customer_id', $customerId);

        // Filter payments by user's sales for staff
        if ($this->isStaff()) {
            $query->whereHas('sale', function ($q) {
                $q->where('user_id', Auth::id())->where('sale_type', 'staff');
            });
        }

        // Apply collector filter when viewing payments list
        if ($this->recipientFilter !== 'all') {
            $adminIds = User::where('role', 'admin')->pluck('id')->toArray();
            $staffIds = User::where('role', 'staff')->pluck('id')->toArray();

            if ($this->selectedCollector) {
                $query->where('collected_by', (int) $this->selectedCollector);
            } else {
                if ($this->recipientFilter === 'admin') {
                    $query->whereIn('collected_by', $adminIds ?: [0]);
                }

                if ($this->recipientFilter === 'staff') {
                    $query->whereIn('collected_by', $staffIds ?: [0]);
                }
            }
        }

        if (!empty(trim($this->invoiceSearch))) {
            $search = trim($this->invoiceSearch);
            $query->whereHas('allocations.sale', function ($q) use ($search) {
                $q->where('invoice_number', 'like', '%' . $search . '%');
            });
        }

        // Debug: log payment filter details
        try {
            \Log::info('Show payments for customer', [
                'customer_id' => $customerId,
                'recipientFilter' => $this->recipientFilter,
                'selectedCollector' => $this->selectedCollector,
                'adminIds_count' => count($this->adminIds ?? []),
                'staffIds_count' => count($this->staffIds ?? []),
            ]);
        } catch (\Throwable $e) {
            // ignore
        }

        $this->payments = $query->orderByDesc('payment_date')->get();
        $this->showPaymentModal = true;
    }

    public function mount()
    {
        $this->staffUsers = User::where('role', 'staff')->get();
        $this->adminUsers = User::where('role', 'admin')->get();
        $this->staffIds = $this->staffUsers->pluck('id')->toArray();
        $this->adminIds = $this->adminUsers->pluck('id')->toArray();
    }

    public function updatedRecipientFilter($value)
    {
        $this->selectedCollector = null;
        $this->resetPage();
        if ($this->showPaymentModal && $this->selectedCustomer) {
            $this->showCustomerPayments($this->selectedCustomer->id);
        }
    }

    public function updatedSelectedCollector($value)
    {
        $this->resetPage();
        if ($this->showPaymentModal && $this->selectedCustomer) {
            $this->showCustomerPayments($this->selectedCustomer->id);
        }
    }

    public function closePaymentModal()
    {
        $this->showPaymentModal = false;
        $this->selectedCustomer = null;
        $this->payments = [];
        $this->invoiceSearch = '';
    }

    public function updatedInvoiceSearch($value)
    {
        if ($this->showPaymentModal && $this->selectedCustomer) {
            $this->showCustomerPayments($this->selectedCustomer->id);
        }
    }

    public function updatedCustomerSearch($value)
    {
        $this->resetPage();
    }

    public function viewPaymentReceipt($paymentId)
    {
        $this->selectedPayment = Payment::with(['customer', 'allocations.sale', 'cheques'])
            ->find($paymentId);

        // Debug log to check if allocations are loaded
        \Log::info('Payment Receipt View', [
            'payment_id' => $paymentId,
            'allocations_count' => $this->selectedPayment->allocations ? $this->selectedPayment->allocations->count() : 0,
            'payment_amount' => $this->selectedPayment->amount
        ]);

        $this->showReceiptModal = true;
    }

    public function closeReceiptModal()
    {
        $this->showReceiptModal = false;
        $this->selectedPayment = null;
    }

    public function editPayment($paymentId)
    {
        $payment = Payment::with('cheques')->find($paymentId);
        if (!$payment) {
            return;
        }

        $this->editingPayment = $payment;
        $this->editPaymentData = [
            'amount' => $payment->amount,
            'payment_date' => $payment->payment_date ? $payment->payment_date->format('Y-m-d') : now()->format('Y-m-d'),
            'payment_method' => $payment->payment_method,
            'notes' => $payment->notes ?? '',
        ];

        $this->editCheques = [];
        if ($payment->payment_method === 'cheque') {
            $this->editCheques = $payment->cheques->map(function ($cheque) {
                return [
                    'cheque_number' => $cheque->cheque_number,
                    'bank_name' => $cheque->bank_name,
                    'cheque_date' => $cheque->cheque_date ? date('Y-m-d', strtotime($cheque->cheque_date)) : now()->format('Y-m-d'),
                    'cheque_amount' => $cheque->cheque_amount,
                ];
            })->values()->toArray();
        }

        if (empty($this->editCheques)) {
            $this->editCheques[] = [
                'cheque_number' => '',
                'bank_name' => '',
                'cheque_date' => now()->format('Y-m-d'),
                'cheque_amount' => $payment->amount,
            ];
        }

        $this->showEditPaymentModal = true;
    }

    public function closeEditPaymentModal()
    {
        $this->showEditPaymentModal = false;
        $this->editingPayment = null;
        $this->editPaymentData = [];
        $this->editCheques = [];
        $this->resetErrorBag();
    }

    public function updatedEditPaymentDataPaymentMethod($value)
    {
        if ($value === 'cheque' && empty($this->editCheques)) {
            $this->editCheques[] = [
                'cheque_number' => '',
                'bank_name' => '',
                'cheque_date' => now()->format('Y-m-d'),
                'cheque_amount' => $this->editPaymentData['amount'] ?? 0,
            ];
        }
    }

    public function addEditChequeRow()
    {
        $this->editCheques[] = [
            'cheque_number' => '',
            'bank_name' => '',
            'cheque_date' => now()->format('Y-m-d'),
            'cheque_amount' => 0,
        ];
    }

    public function removeEditChequeRow($index)
    {
        if (count($this->editCheques) <= 1) {
            return;
        }

        unset($this->editCheques[$index]);
        $this->editCheques = array_values($this->editCheques);
    }

    public function saveEditPayment()
    {
        if (!$this->editingPayment) {
            return;
        }

        try {
            $oldAmount = (float) $this->editingPayment->amount;
            $newAmount = (float) ($this->editPaymentData['amount'] ?? 0);

            $this->validate([
                'editPaymentData.amount' => 'required|numeric|min:0.01',
                'editPaymentData.payment_date' => 'required|date',
                'editPaymentData.payment_method' => 'required|in:cash,cheque,bank_transfer',
            ]);

            // Do not allow editing payment to a lower value than originally paid.
            if ($newAmount < $oldAmount) {
                $this->addError('editPaymentData.amount', 'Edited amount cannot be less than the original paid amount.');
                return;
            }

            if ($this->editPaymentData['payment_method'] === 'cheque') {
                $this->validate([
                    'editCheques' => 'required|array|min:1',
                    'editCheques.*.cheque_number' => 'required|string|max:255',
                    'editCheques.*.bank_name' => 'required|string|max:255',
                    'editCheques.*.cheque_date' => 'required|date',
                    'editCheques.*.cheque_amount' => 'required|numeric|min:0.01',
                ]);

                $submittedChequeNumbers = collect($this->editCheques)
                    ->pluck('cheque_number')
                    ->map(fn($num) => trim((string) $num));

                if ($submittedChequeNumbers->count() !== $submittedChequeNumbers->unique()->count()) {
                    $this->addError('editCheques', 'Cheque numbers must be unique in the list.');
                    return;
                }

                $existingPaymentId = $this->editingPayment->id;
                $duplicateInSystem = Cheque::whereIn('cheque_number', $submittedChequeNumbers->all())
                    ->where('payment_id', '!=', $existingPaymentId)
                    ->exists();

                if ($duplicateInSystem) {
                    $this->addError('editCheques', 'One or more cheque numbers already exist in the system.');
                    return;
                }

                $chequeTotal = (float) collect($this->editCheques)->sum(function ($row) {
                    return (float) ($row['cheque_amount'] ?? 0);
                });

                if (round($chequeTotal, 2) !== round($newAmount, 2)) {
                    $this->addError('editCheques', 'Total cheque amount must match the payment amount.');
                    return;
                }
            }

            $increaseAmount = $newAmount - $oldAmount;

            DB::transaction(function () use ($increaseAmount) {
                $this->editingPayment->update([
                    'amount' => $this->editPaymentData['amount'],
                    'payment_date' => $this->editPaymentData['payment_date'],
                    'payment_method' => $this->editPaymentData['payment_method'],
                    'notes' => $this->editPaymentData['notes'] ?? null,
                ]);

                if ($this->editPaymentData['payment_method'] === 'cheque') {
                    Cheque::where('payment_id', $this->editingPayment->id)->delete();

                    foreach ($this->editCheques as $row) {
                        Cheque::create([
                            'payment_id' => $this->editingPayment->id,
                            'customer_id' => $this->editingPayment->customer_id,
                            'cheque_number' => trim((string) $row['cheque_number']),
                            'bank_name' => trim((string) $row['bank_name']),
                            'cheque_date' => $row['cheque_date'],
                            'cheque_amount' => $row['cheque_amount'],
                            'status' => 'pending',
                        ]);
                    }
                } else {
                    Cheque::where('payment_id', $this->editingPayment->id)->delete();
                }

                // If edited amount is increased, move extra into customer overpaid amount.
                if ($increaseAmount > 0 && $this->editingPayment->customer_id) {
                    $customer = Customer::find($this->editingPayment->customer_id);
                    if ($customer) {
                        $customer->overpaid_amount = (float) ($customer->overpaid_amount ?? 0) + $increaseAmount;
                        $customer->save();
                    }
                }
            });

            // Refresh the payments list
            if ($this->selectedCustomer) {
                $this->showCustomerPayments($this->selectedCustomer->id);
            }

            $this->closeEditPaymentModal();
            $this->dispatch('show-toast', type: 'success', message: 'Payment updated successfully!');
        } catch (\Exception $e) {
            $this->dispatch('show-toast', type: 'error', message: 'Error updating payment: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.admin.list-customer-receipt', [
            'customers' => $this->customers,
        ])->layout($this->layout);
    }
}
