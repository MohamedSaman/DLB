<?php

namespace App\Livewire\Admin;

use App\Models\Payment;
use App\Models\Sale;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

#[Title('Payment Approvals')]
#[Layout('components.layouts.admin')]
class PaymentApproval extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = 'pending';
    public $staffFilter = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $perPage = 15;
    public $selectedPayment = null;
    public $showApproveModal = false;
    public $showRejectModal = false;
    public $rejectionReason = '';

    protected $queryString = ['search', 'statusFilter', 'staffFilter', 'dateFrom', 'dateTo'];

    public function mount()
    {
        $this->dateFrom = Carbon::now()->format('Y-m-d');
        $this->dateTo = Carbon::now()->format('Y-m-d');
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function updatedStaffFilter()
    {
        $this->resetPage();
    }

    public function updatedDateFrom()
    {
        $this->resetPage();
    }

    public function updatedDateTo()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function openApproveModal($paymentId)
    {
        $this->selectedPayment = Payment::with('sale.customer', 'collectedBy')->find($paymentId);
        $this->showApproveModal = true;
    }

    public function closeApproveModal()
    {
        $this->showApproveModal = false;
        $this->selectedPayment = null;
    }

    /**
     * Approve a payment and update sale due amounts and opening balance
     */
    public function approvePayment()
    {
        try {
            DB::beginTransaction();

            if (!$this->selectedPayment || $this->selectedPayment->status !== 'pending') {
                $this->dispatch('show-toast', type: 'error', message: 'Payment not found or already processed.');
                return;
            }

            $payment = $this->selectedPayment;
            $customer = $payment->customer;

            // Update payment status
            $payment->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'is_completed' => true,
            ]);

            // Get payment allocations to process
            $allocations = DB::table('payment_allocations')
                ->where('payment_id', $payment->id)
                ->get();

            // Process each allocation
            $salesPaymentTotal = 0;
            foreach ($allocations as $allocation) {
                if ($allocation->sale_id) {
                    // Update sale due amount
                    $sale = Sale::find($allocation->sale_id);
                    if ($sale) {
                        $newDueAmount = max(0, $sale->due_amount - $allocation->allocated_amount);
                        $paymentStatus = $newDueAmount > 0 ? 'partial' : 'paid';

                        $sale->update([
                            'due_amount' => $newDueAmount,
                            'payment_status' => $paymentStatus,
                            'payment_type' => $newDueAmount > 0 ? 'partial' : 'full',
                        ]);
                    }
                    $salesPaymentTotal += $allocation->allocated_amount;
                } else {
                    // Opening balance payment (sale_id is null)
                    if ($customer) {
                        $customer->opening_balance_paid = ($customer->opening_balance_paid ?? 0) + $allocation->allocated_amount;
                        $customer->save();
                    }
                }
            }

            // Reduce customer's total due_amount ONLY for the portion allocated to sales
            if ($customer && $salesPaymentTotal > 0) {
                $newCustomerDueAmount = max(0, $customer->due_amount - $salesPaymentTotal);
                $customer->update([
                    'due_amount' => $newCustomerDueAmount,
                ]);
            }

            DB::commit();

            $this->closeApproveModal();
            $this->dispatch('show-toast', type: 'success', message: 'Payment approved successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment approval error: ' . $e->getMessage());
            $this->dispatch('show-toast', type: 'error', message: 'Error approving payment.');
        }
    }

    public function openRejectModal($paymentId)
    {
        $this->selectedPayment = Payment::find($paymentId);
        $this->rejectionReason = '';
        $this->showRejectModal = true;
    }

    public function closeRejectModal()
    {
        $this->showRejectModal = false;
        $this->selectedPayment = null;
        $this->rejectionReason = '';
    }

    /**
     * Reject a payment
     */
    public function rejectPayment()
    {
        if (!$this->selectedPayment) {
            return;
        }

        $this->validate([
            'rejectionReason' => 'required|min:5',
        ]);

        try {
            $this->selectedPayment->update([
                'status' => 'rejected',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'rejection_reason' => $this->rejectionReason,
                'is_completed' => false,
            ]);

            $this->closeRejectModal();
            $this->dispatch('show-toast', type: 'success', message: 'Payment rejected.');
        } catch (\Exception $e) {
            Log::error('Payment rejection error: ' . $e->getMessage());
            $this->dispatch('show-toast', type: 'error', message: 'Error rejecting payment.');
        }
    }

    public function render()
    {
        // Filter to show only staff-collected payments
        $query = Payment::with(['sale.customer', 'collectedBy', 'customer'])
            ->whereHas('collectedBy', function ($q) {
                $q->where('role', 'staff');
            })
            ->when($this->search, function ($q) {
                $q->where(function ($sq) {
                    $sq->whereHas('sale', function ($saleQ) {
                        $saleQ->where('sale_id', 'like', '%' . $this->search . '%')
                            ->orWhere('invoice_number', 'like', '%' . $this->search . '%');
                    })
                        ->orWhereHas('customer', function ($cq) {
                            $cq->where('name', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->statusFilter, function ($q) {
                $q->where('status', $this->statusFilter);
            })
            ->when($this->staffFilter, function ($q) {
                $q->where('collected_by', $this->staffFilter);
            })
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('collected_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('collected_at', '<=', $this->dateTo);
            })
            ->orderBy('created_at', 'desc');

        // Get staff users for dropdown
        $staffUsers = User::where('role', 'staff')->orderBy('name')->get(['id', 'name', 'staff_type']);

        // Base query for counts - only staff-collected payments
        $baseCountQuery = Payment::query()->whereHas('collectedBy', function ($q) {
            $q->where('role', 'staff');
        });

        return view('livewire.admin.payment-approval', [
            'payments' => $query->paginate($this->perPage),
            'staffUsers' => $staffUsers,
            'pendingCount' => (clone $baseCountQuery)->where('status', 'pending')->count(),
            'approvedCount' => (clone $baseCountQuery)->whereIn('status', ['approved', 'paid'])->count(),
            'rejectedCount' => (clone $baseCountQuery)->where('status', 'rejected')->count(),
        ]);
    }
}
