<?php

namespace App\Livewire\DeliveryMan;

use Livewire\Component;
use App\Models\ReturnsProduct;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

#[Title("Product Return List")]
#[Layout('components.layouts.delivery-man')]
class DeliveryManReturnList extends Component
{
    use WithPagination;

    public $returnsCount = 0;
    public $returnSearch = '';
    public $selectedReturn = null;
    public $selectedReturnItems = [];
    public $showReceiptModal = false;
    public $currentReturnId = null;
    public $perPage = 10;

    public function mount()
    {
        $this->loadReturns();
    }

    private function getReturnsBaseQuery()
    {
        $query = ReturnsProduct::query();

        // Only show returns for sales delivered by this delivery man or unassigned sales
        $query->whereHas('sale', function ($q) {
            $q->where(function ($sq) {
                $sq->where('delivered_by', Auth::id())
                   ->orWhereNull('delivered_by');
            });
        });

        if (!empty($this->returnSearch)) {
            $search = '%' . $this->returnSearch . '%';
            $query->where(function ($q) use ($search) {
                $q->whereHas('sale', function ($sq) use ($search) {
                    $sq->where('invoice_number', 'like', $search);
                })->orWhereHas('product', function ($pq) use ($search) {
                    $pq->where('name', 'like', $search)
                        ->orWhere('code', 'like', $search);
                });
            });
        }

        return $query;
    }

    protected function loadReturns()
    {
        $this->returnsCount = $this->getReturnsBaseQuery()->distinct('sale_id')->count('sale_id');
    }

    public function updatedReturnSearch()
    {
        $this->resetPage();
        $this->loadReturns();
    }

    public function showReceipt($saleId)
    {
        $this->selectedReturnItems = ReturnsProduct::with(['sale.customer', 'product.variant'])
            ->where('sale_id', $saleId)
            ->get();

        if ($this->selectedReturnItems->isEmpty()) {
            $this->dispatch('showToast', ['type' => 'error', 'message' => 'No return records found for this invoice.']);
            return;
        }

        $this->selectedReturn = $this->selectedReturnItems->first();
        $this->currentReturnId = $saleId;
        $this->showReceiptModal = true;
        $this->dispatch('showModal', 'receiptModal');
    }

    public function printReceipt()
    {
        $this->dispatch('printReceipt');
    }

    public function closeModal()
    {
        $this->selectedReturn = null;
        $this->selectedReturnItems = [];
        $this->currentReturnId = null;
        $this->showReceiptModal = false;
        $this->dispatch('hideModal', 'receiptModal');
    }

    public function render()
    {
        $returns = $this->getReturnsBaseQuery()
            ->select('sale_id')
            ->selectRaw('SUM(usable_quantity) as total_usable')
            ->selectRaw('SUM(damaged_quantity) as total_damaged')
            ->selectRaw('SUM(total_amount) as total_return_amount')
            ->selectRaw('MAX(created_at) as latest_return_date')
            ->groupBy('sale_id')
            ->orderByDesc('latest_return_date')
            ->with(['sale.customer', 'sale.items'])
            ->paginate($this->perPage);

        return view('livewire.delivery-man.delivery-man-return-list', [
            'returns' => $returns,
            'selectedReturn' => $this->selectedReturn,
            'currentReturnId' => $this->currentReturnId,
        ]);
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }
}
