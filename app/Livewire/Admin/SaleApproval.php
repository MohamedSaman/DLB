<?php

namespace App\Livewire\Admin;

use App\Models\Sale;
use App\Models\User;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\SaleItem;
use App\Models\ProductStock;
use App\Services\FIFOStockService;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

#[Title('Sale Approvals')]
#[Layout('components.layouts.admin')]
class SaleApproval extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $staffFilter = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $selectedSaleId = null;
    public $showDetailsModal = false;
    public $showRejectModal = false;
    public $showApproveModal = false;
    public $showDeleteModal = false;
    public $rejectionReason = '';
    public $perPage = 10;
    public $isProcessing = false;

    public function mount()
    {
        $this->dateFrom = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = Carbon::now()->format('Y-m-d');
    }

    // Computed property to get selected sale
    public function getSelectedSaleProperty()
    {
        if (!$this->selectedSaleId) {
            return null;
        }
        return Sale::with(['customer', 'items' => function ($q) {
            $q->with('variant');
        }, 'items.product', 'user', 'returns' => function ($q) {
            $q->with('product');
        }])->find($this->selectedSaleId);
    }

    protected $queryString = ['search', 'statusFilter', 'staffFilter', 'dateFrom', 'dateTo'];

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

    public function viewDetails($saleId)
    {
        $this->selectedSaleId = $saleId;
        $this->showDetailsModal = true;
    }

    public function closeDetailsModal()
    {
        $this->showDetailsModal = false;
        $this->selectedSaleId = null;
    }

    /**
     * Approve a sale and reduce stock
     */
    public function approveSale()
    {
        if (!$this->selectedSaleId) {
            $this->showToast('error', 'No sale selected.');
            return;
        }

        if ($this->isProcessing) {
            $this->showToast('warning', 'Request is already being processed. Please wait.');
            return;
        }

        $this->isProcessing = true;

        try {
            DB::beginTransaction();

            $sale = Sale::with(['items', 'customer'])->find($this->selectedSaleId);

            if (!$sale) {
                DB::rollBack();
                $this->isProcessing = false;
                $this->showToast('error', 'Sale not found.');
                return;
            }

            if ($sale->status !== 'pending') {
                DB::rollBack();
                $this->isProcessing = false;
                $this->showToast('error', 'Sale is already processed. Current status: ' . $sale->status);
                return;
            }

            // Validate sale has items
            if (empty($sale->items) || count($sale->items) === 0) {
                DB::rollBack();
                $this->isProcessing = false;
                $this->showToast('error', 'Sale has no items. Cannot approve.');
                return;
            }

            // Update stock for each item using FIFO method
            foreach ($sale->items as $item) {
                // Skip stock validation for old sales (before Feb 7, 2026) - they're historical data
                $isOldSale = $sale->created_at < now()->subDay();

                try {
                    // Use FIFO stock service to deduct from batches and update product stock
                    FIFOStockService::deductStock(
                        $item->product_id,
                        $item->quantity,
                        $item->variant_id,
                        $item->variant_value
                    );
                } catch (\Exception $e) {
                    if (!$isOldSale) {
                        DB::rollBack();
                        $this->isProcessing = false;
                        $errorMsg = "Stock insufficient for {$item->product_name}: " . $e->getMessage();
                        Log::error("Stock deduction failed for product: {$item->product_name} (ID: {$item->product_id})", [
                            'error' => $e->getMessage(),
                            'sale_id' => $sale->id,
                            'quantity_needed' => $item->quantity
                        ]);
                        $this->showToast('error', $errorMsg);
                        return;
                    }
                    // For old sales, log warning but continue
                    Log::warning("Old sale {$sale->id}: Stock deduction failed for {$item->product_name}, but allowing approval: " . $e->getMessage());
                }
            }

            // Update sale status and set due amount
            $sale->status = 'confirm';
            $sale->approved_by = Auth::id();
            $sale->approved_at = now();

            // Check if payments already exist for this sale
            $existingPayments = \App\Models\Payment::where('sale_id', $sale->id)->sum('amount');
            $sale->due_amount = max(0, $sale->total_amount - $existingPayments);

            if ($sale->due_amount <= 0) {
                $sale->payment_status = 'paid';
            } elseif ($existingPayments > 0) {
                $sale->payment_status = 'partial';
            } else {
                $sale->payment_status = 'pending';
            }

            if (!$sale->save()) {
                throw new \Exception('Failed to save sale status update.');
            }

            // Update customer due amount
            if ($sale->customer && $sale->due_amount > 0) {
                try {
                    $sale->customer->due_amount = ($sale->customer->due_amount ?? 0) + $sale->due_amount;
                    $sale->customer->total_due = ($sale->customer->opening_balance ?? 0) + $sale->customer->due_amount;
                    $sale->customer->save();
                } catch (\Exception $e) {
                    Log::warning("Failed to update customer due amount for sale {$sale->id}: " . $e->getMessage());
                    // Continue with approval anyway, customer update is secondary
                }
            }

            DB::commit();

            $this->isProcessing = false;
            $this->showApproveModal = false;
            $this->showDetailsModal = false;
            $this->selectedSaleId = null;

            $this->showToast('success', 'Sale approved successfully! Stock has been updated.');
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            $this->isProcessing = false;

            $errorMsg = $e->getMessage();
            if (empty($errorMsg) || strlen($errorMsg) < 5) {
                $errorMsg = 'An unexpected error occurred while approving the sale. Please try again.';
            }

            Log::error('Sale approval error for sale ID: ' . $this->selectedSaleId, [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->showToast('error', $errorMsg);
        }
    }

    public function openRejectModal($saleId)
    {
        $this->selectedSaleId = $saleId;
        $this->rejectionReason = '';
        $this->showRejectModal = true;
    }

    public function closeRejectModal()
    {
        $this->showRejectModal = false;
        $this->selectedSaleId = null;
        $this->rejectionReason = '';
    }

    public function openApproveModal($saleId)
    {
        $this->selectedSaleId = $saleId;
        $this->showApproveModal = true;
    }

    public function closeApproveModal()
    {
        $this->showApproveModal = false;
        $this->selectedSaleId = null;
        $this->isProcessing = false;
    }

    /**
     * Reject a sale
     */
    public function rejectSale()
    {
        if (!$this->selectedSaleId) {
            $this->showToast('error', 'No sale selected.');
            return;
        }

        $this->validate([
            'rejectionReason' => 'required|min:5',
        ]);

        try {
            $sale = Sale::find($this->selectedSaleId);
            if (!$sale) {
                $this->showToast('error', 'Sale not found.');
                return;
            }

            if ($sale->status !== 'pending') {
                $this->showToast('error', 'Only pending sales can be rejected. Current status: ' . $sale->status);
                return;
            }

            $result = $sale->update([
                'status' => 'rejected',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'rejection_reason' => $this->rejectionReason,
            ]);

            if (!$result) {
                throw new \Exception('Failed to update sale status.');
            }

            $this->closeRejectModal();
            $this->closeDetailsModal();
            $this->showToast('success', 'Sale rejected successfully with reason recorded.');
        } catch (\Exception $e) {
            Log::error('Sale rejection error for sale ID: ' . $this->selectedSaleId, [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->showToast('error', 'Error rejecting sale: ' . $e->getMessage());
        }
    }

    public function openDeleteModal($saleId)
    {
        $this->selectedSaleId = $saleId;
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->selectedSaleId = null;
    }

    /**
     * Delete a sale and restore stock, reduce customer due amount
     */
    public function deleteSale()
    {
        if (!$this->selectedSaleId) {
            $this->showToast('error', 'No sale selected.');
            return;
        }

        if ($this->isProcessing) {
            $this->showToast('warning', 'Request is already being processed. Please wait.');
            return;
        }

        $this->isProcessing = true;

        try {
            DB::beginTransaction();

            $sale = Sale::with(['items', 'customer'])->find($this->selectedSaleId);

            if (!$sale) {
                DB::rollBack();
                $this->isProcessing = false;
                $this->showToast('error', 'Sale not found.');
                return;
            }

            // Store sale details before deletion
            $saleDueAmount = $sale->due_amount ?? 0;
            $customerId = $sale->customer_id;

            // Restore stock for each item
            foreach ($sale->items as $item) {
                $productStock = null;

                if ($item->variant_id || $item->variant_value) {
                    // Variant product: find specific variant stock
                    $stockQuery = ProductStock::where('product_id', $item->product_id);
                    if ($item->variant_id) {
                        $stockQuery->where('variant_id', $item->variant_id);
                    }
                    if ($item->variant_value) {
                        $stockQuery->where('variant_value', $item->variant_value);
                    }
                    $productStock = $stockQuery->first();
                } else {
                    // Non-variant: find stock with no variant
                    $productStock = ProductStock::where('product_id', $item->product_id)
                        ->where(function ($q) {
                            $q->whereNull('variant_value')
                                ->orWhere('variant_value', '')
                                ->orWhere('variant_value', 'null');
                        })
                        ->whereNull('variant_id')
                        ->first();

                    if (!$productStock) {
                        $productStock = ProductStock::where('product_id', $item->product_id)->first();
                    }
                }

                if ($productStock) {
                    $productStock->available_stock += $item->quantity;
                    if ($productStock->sold_count >= $item->quantity) {
                        $productStock->sold_count -= $item->quantity;
                    }
                    $productStock->updateTotals();
                }
            }

            // Delete related records
            Payment::where('sale_id', $sale->id)->delete();
            SaleItem::where('sale_id', $sale->id)->delete();

            // Delete the sale
            $sale->delete();

            // Update customer's due amount and total due
            if ($customerId && $saleDueAmount > 0) {
                $customer = Customer::find($customerId);
                if ($customer) {
                    // Reduce due amount
                    $customer->due_amount = max(0, ($customer->due_amount ?? 0) - $saleDueAmount);
                    // Recalculate total due
                    $customer->total_due = ($customer->opening_balance ?? 0) + $customer->due_amount;
                    $customer->save();
                }
            }

            DB::commit();

            $this->isProcessing = false;
            $this->closeDeleteModal();
            $this->closeDetailsModal();
            $this->selectedSaleId = null;

            $this->showToast('success', 'Sale deleted successfully! Stock restored and customer due amount updated.');
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            $this->isProcessing = false;

            Log::error('Sale deletion error for sale ID: ' . $this->selectedSaleId, [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->showToast('error', 'Error deleting sale: ' . $e->getMessage());
        }
    }

    /**
     * Show toast notification with custom styling
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

    public function render()
    {
        $query = Sale::with(['customer', 'user']);

        // Check if user is admin
        $isAdmin = Auth::user()->role === 'admin';

        // Filter to show only staff/salesman sales (exclude admin-created sales)
        $query->whereHas('user', function ($q) {
            $q->where('role', '!=', 'admin');
        });

        // If viewing user is not admin, also filter by their own sales
        if (!$isAdmin) {
            $query->where('user_id', Auth::id());
        }

        // Apply staff filter
        if ($this->staffFilter !== '') {
            $query->where('user_id', $this->staffFilter);
        }

        // Apply date range filter
        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        // Apply status filter - only if statusFilter is not empty
        if ($this->statusFilter !== '') {
            $query->where('status', $this->statusFilter);
        }

        $query->when($this->search, function ($q) {
            $q->where(function ($sq) {
                $sq->where('sale_id', 'like', '%' . $this->search . '%')
                    ->orWhere('invoice_number', 'like', '%' . $this->search . '%')
                    ->orWhereHas('customer', function ($cq) {
                        $cq->where('name', 'like', '%' . $this->search . '%');
                    });
            });
        })
            ->orderBy('created_at', 'desc');

        // Get staff users for the dropdown
        $staffUsers = User::where('role', 'staff')->orderBy('name')->get(['id', 'name', 'staff_type']);

        // Summary stats based on the same filtered query (without pagination)
        // Only confirmed/approved sales have meaningful due & collected data
        $statsQuery = (clone $query);
        $totalSalesAmount = (clone $statsQuery)->sum('total_amount');

        // Due amount only from confirmed sales (pending sales don't have due set yet)
        $confirmedStatsQuery = (clone $query)->where('status', 'confirm');
        $totalDueAmount = (clone $confirmedStatsQuery)->sum('due_amount');

        // Collected amount from actual payment records for the filtered sales
        $filteredSaleIds = (clone $confirmedStatsQuery)->pluck('id');
        $totalCollectedAmount = \App\Models\Payment::whereIn('sale_id', $filteredSaleIds)->sum('amount');

        // Base query for counts - always exclude admin-created sales
        $baseCountQuery = Sale::query()->whereHas('user', function ($q) {
            $q->where('role', '!=', 'admin');
        });

        // If not admin, also filter by their own sales
        if (!$isAdmin) {
            $baseCountQuery->where('user_id', Auth::id());
        }

        return view('livewire.admin.sale-approval', [
            'sales' => $query->paginate($this->perPage),
            'staffUsers' => $staffUsers,
            'totalSalesAmount' => $totalSalesAmount,
            'totalDueAmount' => $totalDueAmount,
            'totalCollectedAmount' => $totalCollectedAmount,
            'pendingCount' => (clone $baseCountQuery)->where('status', 'pending')->count(),
            'approvedCount' => (clone $baseCountQuery)->where('status', 'confirm')->count(),
            'rejectedCount' => (clone $baseCountQuery)->where('status', 'rejected')->count(),
        ]);
    }
}
