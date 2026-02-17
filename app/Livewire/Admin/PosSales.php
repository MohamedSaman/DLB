<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\SaleItem;
use App\Models\ProductStock;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Livewire\Concerns\WithDynamicLayout;

#[Title('POS Sales Management')]
class PosSales extends Component
{
    use WithDynamicLayout;

    use WithPagination;

    public $search = '';
    public $selectedSale = null;
    public $paymentStatusFilter = 'all';
    public $dateFilter = '';
    public $showViewModal = false;
    public $showEditModal = false;
    public $showDeleteModal = false;
    public $showPaymentHistoryModal = false;
    public $paymentHistory = [];

    // Edit form properties
    public $editSaleId;
    public $editCustomerId;
    public $editPaymentStatus;
    public $editNotes;
    public $editDueAmount;
    public $editPaidAmount;
    public $editPayBalanceAmount = 0;
    public $perPage = 10;

    public function mount()
    {
        // Initialize component
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedPaymentStatusFilter()
    {
        $this->resetPage();
    }

    public function updatedDateFilter()
    {
        $this->resetPage();
    }

    public function viewSale($saleId)
    {
        $this->selectedSale = Sale::with([
            'customer',
            'items' => function ($q) {
                $q->with('variant');
            },
            'user',
            'returns' => function ($q) {
                $q->with('product');
            }
        ])
            ->where('sale_type', 'pos')
            ->find($saleId);

        $this->showViewModal = true;
        $this->dispatch('showModal', 'viewModal');
    }

    public function editSale($saleId)
    {
        $sale = Sale::with(['customer'])->find($saleId);

        if ($sale) {
            $this->editSaleId = $sale->id;
            $this->editCustomerId = $sale->customer_id;
            $this->editPaymentStatus = $sale->payment_status;
            $this->editNotes = $sale->notes;
            $this->editDueAmount = $sale->due_amount;
            $this->editPaidAmount = $sale->total_amount - $sale->due_amount;
            $this->editPayBalanceAmount = 0;

            $this->showEditModal = true;
            $this->dispatch('showModal', 'editModal');
        }
    }

    // Auto-calculate amounts when payment status changes
    public function updatedEditPaymentStatus($value)
    {
        if ($this->editSaleId) {
            $sale = Sale::find($this->editSaleId);
            if ($sale) {
                if ($value === 'paid') {
                    $this->editPaidAmount = $sale->total_amount;
                    $this->editDueAmount = 0;
                    $this->editPayBalanceAmount = $sale->due_amount;
                } elseif ($value === 'pending') {
                    $this->editPaidAmount = 0;
                    $this->editDueAmount = $sale->total_amount;
                    $this->editPayBalanceAmount = 0;
                } else {
                    $this->editPayBalanceAmount = 0;
                }
            }
        }
    }

    // Auto-update amounts when pay balance amount changes
    public function updatedEditPayBalanceAmount($value)
    {
        if ($this->editSaleId) {
            $sale = Sale::find($this->editSaleId);
            if ($sale) {
                $value = floatval($value);
                $maxPayable = $sale->due_amount;

                // Ensure pay balance doesn't exceed due amount
                if ($value > $maxPayable) {
                    $this->editPayBalanceAmount = $maxPayable;
                    $value = $maxPayable;
                }

                if ($value < 0) {
                    $this->editPayBalanceAmount = 0;
                    $value = 0;
                }

                // Calculate new amounts
                $this->editPaidAmount = $sale->total_amount - $sale->due_amount + $value;
                $this->editDueAmount = $sale->due_amount - $value;

                // Auto-update payment status based on amounts
                if ($this->editDueAmount <= 0) {
                    $this->editPaymentStatus = 'paid';
                } elseif ($value > 0) {
                    $this->editPaymentStatus = 'partial';
                } else {
                    $this->editPaymentStatus = 'pending';
                }
            }
        }
    }

    public function updateSale()
    {
        $this->validate([
            'editPaymentStatus' => 'required|in:paid,partial,pending',
            'editDueAmount' => 'required|numeric|min:0',
            'editPaidAmount' => 'required|numeric|min:0',
            'editPayBalanceAmount' => 'required|numeric|min:0',
        ]);

        try {
            $sale = Sale::find($this->editSaleId);

            if ($sale) {
                // Store old values for customer update
                $oldDueAmount = $sale->due_amount;
                $oldCustomerId = $sale->customer_id;

                // Ensure paid amount + due amount equals total amount
                $totalAmount = $sale->total_amount;
                $paidAmount = $this->editPaidAmount;
                $dueAmount = $this->editDueAmount;

                // If amounts don't match, adjust due amount
                if (($paidAmount + $dueAmount) != $totalAmount) {
                    $dueAmount = $totalAmount - $paidAmount;
                }

                // Update customer due_amount: subtract old due, add new due
                // Handle both old customer and new customer (if customer changed)
                if ($oldCustomerId && $oldCustomerId != $this->editCustomerId) {
                    // Customer changed: remove old due from old customer (only if it was added)
                    $oldCustomer = Customer::find($oldCustomerId);
                    if ($oldCustomer && $oldDueAmount > 0) {
                        $oldCustomer->due_amount = max(0, ($oldCustomer->due_amount ?? 0) - $oldDueAmount);
                        $oldCustomer->total_due = ($oldCustomer->opening_balance ?? 0) + $oldCustomer->due_amount;
                        $oldCustomer->save();
                    }
                }

                // Update current customer
                $customer = Customer::find($this->editCustomerId);
                if ($customer) {
                    if ($oldCustomerId == $this->editCustomerId) {
                        // Same customer: adjust the difference
                        // Only subtract old due if it was actually added (>0)
                        if ($oldDueAmount > 0) {
                            $customer->due_amount = max(0, ($customer->due_amount ?? 0) - $oldDueAmount);
                        }
                        // Only add new due if it exists (>0)
                        if ($dueAmount > 0) {
                            $customer->due_amount = ($customer->due_amount ?? 0) + $dueAmount;
                        }
                    } else {
                        // New customer: just add new due (only if exists)
                        if ($dueAmount > 0) {
                            $customer->due_amount = ($customer->due_amount ?? 0) + $dueAmount;
                        }
                    }
                    $customer->total_due = ($customer->opening_balance ?? 0) + $customer->due_amount;
                    $customer->save();
                }

                $sale->update([
                    'customer_id' => $this->editCustomerId,
                    'payment_status' => $this->editPaymentStatus,
                    'notes' => $this->editNotes,
                    'due_amount' => $dueAmount,
                    'payment_type' => $this->editPaymentStatus === 'paid' ? 'full' : 'partial',
                ]);

                // Create Payment record if balance was paid
                if ($this->editPayBalanceAmount > 0) {
                    \App\Models\Payment::create([
                        'sale_id' => $sale->id,
                        'customer_id' => $this->editCustomerId,
                        'amount' => $this->editPayBalanceAmount,
                        'payment_method' => 'cash',
                        'payment_reference' => 'POS-EDIT-' . now()->format('YmdHis'),
                        'is_completed' => true,
                        'status' => 'paid',
                        'payment_date' => now(),
                        'notes' => 'Balance paid via POS sales edit',
                    ]);
                }

                $this->showEditModal = false;
                $this->resetEditForm();
                $this->dispatch('hideModal', 'editModal');
                $this->dispatch('showToast', ['type' => 'success', 'message' => 'Sale updated successfully!']);
            }
        } catch (\Exception $e) {
            $this->dispatch('showToast', ['type' => 'error', 'message' => 'Error updating sale: ' . $e->getMessage()]);
        }
    }

    // Pay full balance
    public function payFullBalance()
    {
        if ($this->editSaleId) {
            $sale = Sale::find($this->editSaleId);
            if ($sale) {
                $this->editPayBalanceAmount = $sale->due_amount;
                $this->updatedEditPayBalanceAmount($sale->due_amount);
            }
        }
    }

    // Reset pay balance
    public function resetPayBalance()
    {
        $this->editPayBalanceAmount = 0;
        $this->updatedEditPayBalanceAmount(0);
    }

    public function deleteSale($saleId)
    {
        $this->selectedSale = Sale::find($saleId);
        $this->showDeleteModal = true;
        $this->dispatch('showModal', 'deleteModal');
    }

    public function confirmDelete()
    {
        try {
            DB::transaction(function () {
                // Store sale details before deletion
                $saleDueAmount = $this->selectedSale->due_amount ?? 0;
                $customerId = $this->selectedSale->customer_id;

                // Get sale items to restore stock
                $saleItems = SaleItem::where('sale_id', $this->selectedSale->id)->get();

                // Restore stock
                foreach ($saleItems as $item) {
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
                \App\Models\Payment::where('sale_id', $this->selectedSale->id)->delete();
                SaleItem::where('sale_id', $this->selectedSale->id)->delete();

                // Delete the sale
                $this->selectedSale->delete();

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
            });

            $this->showDeleteModal = false;
            $this->selectedSale = null;
            $this->dispatch('hideModal', 'deleteModal');
            $this->dispatch('showToast', ['type' => 'success', 'message' => 'Sale deleted successfully and customer due amount updated!']);
        } catch (\Exception $e) {
            $this->dispatch('showToast', ['type' => 'error', 'message' => 'Error deleting sale: ' . $e->getMessage()]);
        }
    }
    public function editSaleRedirect($saleId)
    {
        $sale = Sale::find($saleId);
        if (!$sale) {
            $this->dispatch('showToast', ['type' => 'error', 'message' => 'Sale not found.']);
            return;
        }
        // Redirect to store-billing page with sale ID for editing
        return redirect()->route('admin.store-billing', ['edit' => $sale->id]);
    }

    public function printSaleModal($saleId)
    {
        // Load the sale and show the view modal
        $this->selectedSale = Sale::with([
            'customer',
            'items' => function ($q) {
                $q->with('variant');
            },
            'user',
            'returns' => function ($q) {
                $q->with('product');
            }
        ])
            ->where('sale_type', 'pos')
            ->find($saleId);

        if (!$this->selectedSale) {
            $this->dispatch('showToast', ['type' => 'error', 'message' => 'Sale not found.']);
            return;
        }

        $this->showViewModal = true;
        $this->dispatch('showModal', 'viewModal');

        // Trigger print after modal is shown
        $this->js("setTimeout(() => { printInvoice(); }, 500);");
    }

    public function printInvoice($saleId)
    {
        $sale = \App\Models\Sale::with(['customer', 'items' => function ($q) {
            $q->with('variant');
        }, 'payments', 'returns' => function ($q) {
            $q->with('product');
        }])->find($saleId);
        if (!$sale) {
            $this->dispatch('showToast', ['type' => 'error', 'message' => 'Sale not found.']);
            return;
        }
        // Store sale ID in session for print route
        session(['print_sale_id' => $sale->id]);
        // Open print page in new window
        $printUrl = route('admin.print.sale', $sale->id);
        $this->js("window.open('$printUrl', '_blank', 'width=800,height=600');");
    }

    public function downloadInvoice($saleId)
    {
        $sale = Sale::with(['customer', 'items' => function ($q) {
            $q->with('variant');
        }, 'returns' => function ($q) {
            $q->with('product');
        }])->find($saleId);

        if (!$sale) {
            $this->dispatch('showToast', ['type' => 'error', 'message' => 'Sale not found.']);
            return;
        }

        try {
            // Calculate paid amount for the invoice
            $sale->paid_amount = $sale->total_amount - $sale->due_amount;
            $sale->balance_amount = $sale->due_amount;

            $pdf = PDF::loadView('admin.sales.invoice', compact('sale'));

            $pdf->setPaper('a4', 'portrait');
            $pdf->setOption('dpi', 150);
            $pdf->setOption('defaultFont', 'sans-serif');

            return response()->streamDownload(
                function () use ($pdf) {
                    echo $pdf->output();
                },
                'invoice-' . $sale->invoice_number . '.pdf'
            );
        } catch (\Exception $e) {
            $this->dispatch('showToast', ['type' => 'error', 'message' => 'Failed to generate PDF: ' . $e->getMessage()]);
        }
    }

    public function showPaymentHistory($saleId)
    {
        $this->selectedSale = Sale::with('customer')->find($saleId);

        if ($this->selectedSale) {
            // Get all payments for this sale
            $this->paymentHistory = \App\Models\Payment::with(['cheques'])
                ->where('sale_id', $saleId)
                ->orderBy('payment_date', 'desc')
                ->get();

            $this->showPaymentHistoryModal = true;
            $this->dispatch('showModal', 'paymentHistoryModal');
        }
    }

    public function closeModals()
    {
        $this->showViewModal = false;
        $this->showEditModal = false;
        $this->showDeleteModal = false;
        $this->showPaymentHistoryModal = false;
        $this->selectedSale = null;
        $this->paymentHistory = [];
        $this->resetEditForm();

        // Hide all modals
        $this->dispatch('hideModal', 'viewModal');
        $this->dispatch('hideModal', 'editModal');
        $this->dispatch('hideModal', 'deleteModal');
        $this->dispatch('hideModal', 'paymentHistoryModal');
    }

    private function resetEditForm()
    {
        $this->editSaleId = null;
        $this->editCustomerId = null;
        $this->editPaymentStatus = '';
        $this->editNotes = '';
        $this->editDueAmount = 0;
        $this->editPaidAmount = 0;
        $this->editPayBalanceAmount = 0;
    }

    public function getSalesProperty()
    {
        return Sale::with(['customer', 'user', 'items'])
            ->where('sale_type', 'pos')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('invoice_number', 'like', '%' . $this->search . '%')
                        ->orWhere('sale_id', 'like', '%' . $this->search . '%')
                        ->orWhereHas('customer', function ($customerQuery) {
                            $customerQuery->where('name', 'like', '%' . $this->search . '%')
                                ->orWhere('phone', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->paymentStatusFilter !== 'all', function ($query) {
                $query->where('payment_status', $this->paymentStatusFilter);
            })
            ->when($this->dateFilter, function ($query) {
                $query->whereDate('created_at', $this->dateFilter);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);
    }
    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function getSalesStatsProperty()
    {
        $totalSales = Sale::where('sale_type', 'pos');
        $todaySales = Sale::where('sale_type', 'pos')->whereDate('created_at', today());

        return [
            'total_sales' => $totalSales->count(),
            'total_amount' => $totalSales->sum('total_amount'),
            'pending_payments' => Sale::where('sale_type', 'pos')->where('payment_status', 'pending')->sum('due_amount'),
            'partial_payments' => Sale::where('sale_type', 'pos')->where('payment_status', 'partial')->sum('due_amount'),
            'paid_amount' => Sale::where('sale_type', 'pos')->where('payment_status', 'paid')->sum('total_amount'),
            'today_sales' => $todaySales->count(),
            'today_amount' => $todaySales->sum('total_amount'),
        ];
    }

    public function getCustomersProperty()
    {
        return Customer::orderBy('name')->get();
    }

    // Mark sale as paid
    public function markAsPaid($saleId)
    {
        try {
            $sale = Sale::find($saleId);

            if ($sale) {
                $previousDue = $sale->due_amount;

                $sale->update([
                    'payment_status' => 'paid',
                    'due_amount' => 0,
                    'payment_type' => 'full'
                ]);

                // Create Payment record for the amount that was due
                if ($previousDue > 0) {
                    \App\Models\Payment::create([
                        'sale_id' => $sale->id,
                        'customer_id' => $sale->customer_id,
                        'amount' => $previousDue,
                        'payment_method' => 'cash',
                        'payment_reference' => 'POS-PAID-' . now()->format('YmdHis'),
                        'is_completed' => true,
                        'status' => 'paid',
                        'payment_date' => now(),
                        'notes' => 'Marked as paid via POS sales management',
                    ]);
                }

                $this->dispatch('showToast', ['type' => 'success', 'message' => 'Sale marked as paid successfully!']);
            }
        } catch (\Exception $e) {
            $this->dispatch('showToast', ['type' => 'error', 'message' => 'Error updating sale: ' . $e->getMessage()]);
        }
    }

    // Quick actions
    public function markAsPending($saleId)
    {
        try {
            $sale = Sale::find($saleId);

            if ($sale) {
                $sale->update([
                    'payment_status' => 'pending',
                    'due_amount' => $sale->total_amount,
                    'payment_type' => 'partial'
                ]);

                $this->dispatch('showToast', ['type' => 'success', 'message' => 'Sale marked as pending successfully!']);
            }
        } catch (\Exception $e) {
            $this->dispatch('showToast', ['type' => 'error', 'message' => 'Error updating sale: ' . $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.admin.pos-sales', [
            'sales' => $this->sales,
            'stats' => $this->salesStats,
            'customers' => $this->customers,
        ])->layout($this->layout);
    }
}
