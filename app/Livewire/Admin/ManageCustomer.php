<?php

namespace App\Livewire\Admin;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\Payment;
use App\Models\Cheque;
use Livewire\Component;
use Exception;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Livewire\Concerns\WithDynamicLayout;
use Illuminate\Support\Facades\Auth;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Title('Manage Customer')]
class ManageCustomer extends Component
{
    use WithDynamicLayout;
    use WithPagination, WithFileUploads;

    public $name;
    public $contactNumber;
    public $address;
    public $email;
    public $customerType;
    public $businessName;
    public $openingBalance = 0;
    public $overpaidAmount = 0;
    public $showMoreInfo = false;

    public $editCustomerId;
    public $editName;
    public $editContactNumber;
    public $editAddress;
    public $editEmail;
    public $editCustomerType;
    public $editBusinessName;
    public $editOpeningBalance = 0;
    public $editOverpaidAmount = 0;
    public $showEditMoreInfo = false;

    public $deleteId;
    public $showEditModal = false;
    public $showCreateModal = false;
    public $showDeleteModal = false;
    public $showViewModal = false;
    public $viewCustomerDetail = [];
    public $viewCustomerSales = [];
    public $viewCustomerPayments = [];
    public $viewCustomerDues = [];
    public $viewCustomerCheques = [];
    public $viewCustomerLedger = [];
    public $activeTab = 'overview';
    public $perPage = 10;
    public $search = '';
    public $showImportModal = false;
    public $importFile;

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function render()
    {
        $customers = Customer::when($this->search, function ($query) {
            return $query->where('name', 'like', '%' . $this->search . '%')
                ->orWhere('phone', 'like', '%' . $this->search . '%')
                ->orWhere('email', 'like', '%' . $this->search . '%')
                ->orWhere('business_name', 'like', '%' . $this->search . '%')
                ->orWhere('address', 'like', '%' . $this->search . '%');
        })
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.admin.manage-customer', [
            'customers' => $customers,
        ])->layout($this->layout);
    }

    /** ----------------------------
     * Create Customer
     * ---------------------------- */
    public function createCustomer()
    {
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function resetForm()
    {
        $this->reset([
            'name',
            'contactNumber',
            'address',
            'email',
            'customerType',
            'businessName',
            'openingBalance',
            'overpaidAmount',
            'showMoreInfo',
            'editCustomerId',
            'editName',
            'editContactNumber',
            'editAddress',
            'editEmail',
            'editCustomerType',
            'editBusinessName',
            'editOpeningBalance',
            'editOverpaidAmount',
            'showEditMoreInfo',
        ]);
        $this->resetErrorBag();
    }

    public function closeModal()
    {
        $this->showCreateModal = false;
        $this->showEditModal = false;
        $this->showDeleteModal = false;
        $this->showViewModal = false;
        $this->resetForm();
    }

    /** ----------------------------
     * View Customer Details
     * ---------------------------- */
    public function viewDetails($id)
    {
        $customer = Customer::find($id);
        if (!$customer) {
            $this->js("Swal.fire('Error!', 'Customer Not Found', 'error')");
            return;
        }

        $this->activeTab = 'overview';

        // Basic info
        $this->viewCustomerDetail = [
            'id' => $customer->id,
            'name' => $customer->name,
            'business_name' => $customer->business_name,
            'phone' => $customer->phone,
            'email' => $customer->email,
            'type' => $customer->type,
            'address' => $customer->address,
            'opening_balance' => $customer->opening_balance ?? 0,
            'due_amount' => $customer->due_amount ?? 0,
            'overpaid_amount' => $customer->overpaid_amount ?? 0,
            'total_due' => $customer->total_due ?? 0,
            'created_at' => $customer->created_at,
            'updated_at' => $customer->updated_at,
        ];

        // Sales
        $sales = Sale::where('customer_id', $customer->id)
            ->with(['items', 'payments'])
            ->orderBy('created_at', 'desc')
            ->get();

        $this->viewCustomerSales = $sales->map(function ($sale) {
            // Calculate paid amount using total minus due
            $paidAmount = ($sale->total_amount ?? 0) - ($sale->due_amount ?? 0);
            
            return [
                'id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'sale_id' => $sale->sale_id,
                'total_amount' => $sale->total_amount ?? 0,
                'due_amount' => $sale->due_amount ?? 0,
                'payment_status' => $sale->payment_status,
                'status' => $sale->status,
                'payment_type' => $sale->payment_type,
                'created_at' => $sale->created_at ? $sale->created_at->format('M d, Y h:i A') : '-',
                'items_count' => $sale->items->count(),
                'paid_amount' => $paidAmount,
            ];
        })->toArray();

        // Payments
        $payments = Payment::where('customer_id', $customer->id)
            ->with(['sale'])
            ->orderBy('created_at', 'desc')
            ->get();

        $this->viewCustomerPayments = $payments->map(function ($payment) {
            return [
                'id' => $payment->id,
                'amount' => $payment->amount ?? 0,
                'payment_method' => $payment->payment_method,
                'payment_reference' => $payment->payment_reference,
                'payment_date' => $payment->payment_date ? $payment->payment_date->format('M d, Y') : '-',
                'status' => $payment->status,
                'invoice_number' => $payment->sale ? $payment->sale->invoice_number : '-',
                'notes' => $payment->notes,
                'created_at' => $payment->created_at ? $payment->created_at->format('M d, Y h:i A') : '-',
            ];
        })->toArray();

        // Dues (sales with pending payment)
        $this->viewCustomerDues = $sales->filter(function ($sale) {
            return ($sale->due_amount ?? 0) > 0;
        })->map(function ($sale) {
            // Calculate paid amount using total minus due
            $paidAmount = ($sale->total_amount ?? 0) - ($sale->due_amount ?? 0);
            
            return [
                'id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'total_amount' => $sale->total_amount ?? 0,
                'due_amount' => $sale->due_amount ?? 0,
                'paid_amount' => $paidAmount,
                'status' => $sale->status,
                'payment_status' => $sale->payment_status,
                'created_at' => $sale->created_at ? $sale->created_at->format('M d, Y h:i A') : '-',
            ];
        })->values()->toArray();

        // Cheques
        $cheques = Cheque::where('customer_id', $customer->id)
            ->with(['payment'])
            ->orderBy('created_at', 'desc')
            ->get();

        $this->viewCustomerCheques = $cheques->map(function ($cheque) {
            return [
                'id' => $cheque->id,
                'cheque_number' => $cheque->cheque_number,
                'cheque_date' => $cheque->cheque_date,
                'bank_name' => $cheque->bank_name,
                'cheque_amount' => $cheque->cheque_amount ?? 0,
                'status' => $cheque->status,
            ];
        })->toArray();

        // Ledger - combine all transactions chronologically
        $ledgerEntries = collect();

        // Add opening balance as first entry
        if (($customer->opening_balance ?? 0) > 0) {
            $ledgerEntries->push([
                'date' => $customer->created_at ? $customer->created_at->format('M d, Y h:i A') : '-',
                'description' => 'Opening Balance',
                'reference' => '-',
                'debit' => $customer->opening_balance,
                'credit' => 0,
                'type' => 'opening',
            ]);
        }

        // Add sales as debit entries
        foreach ($sales as $sale) {
            $ledgerEntries->push([
                'date' => $sale->created_at ? $sale->created_at->format('M d, Y h:i A') : '-',
                'description' => 'Sale Invoice',
                'reference' => $sale->invoice_number ?? $sale->sale_id,
                'debit' => $sale->total_amount ?? 0,
                'credit' => 0,
                'type' => 'sale',
            ]);
        }

        // Add payments as credit entries
        foreach ($payments as $payment) {
            // Only include non-rejected and non-pending payments in ledger
            if ($payment->status === 'rejected' || $payment->status === 'pending') continue;
            $ledgerEntries->push([
                'date' => $payment->payment_date ? $payment->payment_date->format('M d, Y h:i A') : ($payment->created_at ? $payment->created_at->format('M d, Y h:i A') : '-'),
                'description' => 'Payment Received (' . ucfirst($payment->payment_method ?? 'N/A') . ')',
                'reference' => $payment->payment_reference ?? ($payment->sale ? $payment->sale->invoice_number : '-'),
                'debit' => 0,
                'credit' => $payment->amount ?? 0,
                'type' => 'payment',
            ]);
        }

        // Sort by date
        $this->viewCustomerLedger = $ledgerEntries->sortBy('date')->values()->toArray();

        $this->showViewModal = true;
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function saveCustomer()
    {
        $this->validate([
            'name' => 'required',
            'customerType' => 'required',
            'contactNumber' => 'nullable',
            'address' => 'nullable',
            'email' => 'nullable|email|unique:customers,email',
            'businessName' => 'nullable',
            'openingBalance' => 'nullable|numeric|min:0',
            'overpaidAmount' => 'nullable|numeric|min:0',
        ]);

        try {
            $openingBalance = floatval($this->openingBalance ?? 0);
            $overpaidAmount = floatval($this->overpaidAmount ?? 0);
            $totalDue = $openingBalance;

            Customer::create([
                'name' => $this->name,
                'phone' => $this->contactNumber,
                'address' => $this->address,
                'email' => $this->email,
                'type' => $this->customerType,
                'business_name' => $this->businessName,
                'user_id' => Auth::id(),
                'opening_balance' => $openingBalance,
                'due_amount' => 0,
                'total_due' => $totalDue,
                'overpaid_amount' => $overpaidAmount,
            ]);

            $this->js("Swal.fire('Success!', 'Customer Created Successfully', 'success')");
            $this->closeModal();
        } catch (Exception $e) {
            $this->js("Swal.fire('Error!', '" . $e->getMessage() . "', 'error')");
        }
    }

    /** ----------------------------
     * Edit Customer
     * ---------------------------- */
    public function editCustomer($id)
    {
        $customer = Customer::find($id);

        if (!$customer) {
            $this->js("Swal.fire('Error!', 'Customer not found.', 'error')");
            return;
        }

        $this->editCustomerId = $customer->id;
        $this->editName = $customer->name;
        $this->editContactNumber = $customer->phone;
        $this->editBusinessName = $customer->business_name;
        $this->editCustomerType = $customer->type;
        $this->editAddress = $customer->address;
        $this->editEmail = $customer->email;
        $this->editOpeningBalance = $customer->opening_balance ?? 0;
        $this->editOverpaidAmount = $customer->overpaid_amount ?? 0;
        $this->showEditMoreInfo = ($this->editOpeningBalance > 0 || $this->editOverpaidAmount > 0);

        $this->showEditModal = true;
    }

    public function updateCustomer()
    {
        $this->validate([
            'editName' => 'required',
            'editCustomerType' => 'required',
            'editBusinessName' => 'nullable',
            'editContactNumber' => 'nullable',
            'editAddress' => 'nullable',
            'editEmail' => 'nullable|email|unique:customers,email,' . $this->editCustomerId,
            'editOpeningBalance' => 'nullable|numeric|min:0',
            'editOverpaidAmount' => 'nullable|numeric|min:0',
        ]);

        try {
            $customer = Customer::find($this->editCustomerId);
            if (!$customer) {
                $this->js("Swal.fire('Error!', 'Customer not found.', 'error')");
                return;
            }

            $openingBalance = floatval($this->editOpeningBalance ?? 0);
            $overpaidAmount = floatval($this->editOverpaidAmount ?? 0);
            $dueAmount = floatval($customer->due_amount ?? 0);
            $totalDue = $openingBalance + $dueAmount;

            $customer->update([
                'name' => $this->editName,
                'phone' => $this->editContactNumber,
                'business_name' => $this->editBusinessName,
                'type' => $this->editCustomerType,
                'address' => $this->editAddress,
                'email' => $this->editEmail,
                'opening_balance' => $openingBalance,
                'total_due' => $totalDue,
                'overpaid_amount' => $overpaidAmount,
            ]);

            $this->js("Swal.fire('Success!', 'Customer Updated Successfully', 'success')");
            $this->closeModal();
        } catch (Exception $e) {
            $this->js("Swal.fire('Error!', '" . $e->getMessage() . "', 'error')");
        }
    }

    /** ----------------------------
     * Delete Customer
     * ---------------------------- */
    public function confirmDelete($id)
    {
        $this->deleteId = $id;
        $this->showDeleteModal = true;
    }

    public function cancelDelete()
    {
        $this->showDeleteModal = false;
        $this->deleteId = null;
    }

    public function deleteCustomer()
    {
        try {
            Customer::where('id', $this->deleteId)->delete();
            $this->js("Swal.fire('Success!', 'Customer deleted successfully.', 'success')");
            $this->dispatch('refreshPage');
            $this->cancelDelete();
        } catch (Exception $e) {
            $this->js("Swal.fire('Error!', '" . $e->getMessage() . "', 'error')");
        }
    }

    /** ----------------------------
     * Import Customers from Excel
     * ---------------------------- */
    public function openImportModal()
    {
        $this->showImportModal = true;
    }

    public function closeImportModal()
    {
        $this->showImportModal = false;
        $this->importFile = null;
    }

    public function downloadTemplate()
    {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Set headers
            $headers = ['Customer Name', 'Contact Number', 'Email', 'Business Name', 'Customer Type', 'Address'];
            $sheet->fromArray([$headers], null, 'A1');

            // Style headers
            $headerStyle = $sheet->getStyle('A1:F1');
            $headerStyle->getFont()->setBold(true);
            $headerStyle->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
            $headerStyle->getFill()->getStartColor()->setARGB('FF4361EE');
            $headerStyle->getFont()->getColor()->setARGB('FFFFFFFF');

            // Add sample data
            $sampleData = [
                ['Ahmed Hameed', '0771234567, 0772345678', 'ahmed@example.com', 'Ahmed Trading', 'retail', 'Colombo'],
                ['Lakshmi Enterprises', '0701234567 / 0702345678', 'info@lakshmi.com', 'Lakshmi Trading', 'wholesale', 'Kandy'],
            ];
            $sheet->fromArray($sampleData, null, 'A2');

            // Auto adjust columns
            foreach (range('A', 'F') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $filename = 'customer_import_template_' . date('Y-m-d') . '.xlsx';

            $response = new StreamedResponse(function () use ($spreadsheet) {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
            });

            $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
            $response->headers->set('Cache-Control', 'no-cache');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');

            return $response;
        } catch (Exception $e) {
            $this->js("Swal.fire('Error!', 'Failed to download template: " . addslashes($e->getMessage()) . "', 'error')");
        }
    }

    public function importCustomers()
    {
        $this->validate([
            'importFile' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            $file = $this->importFile->getRealPath();
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $spreadsheet = $reader->load($file);
            $sheet = $spreadsheet->getActiveSheet();
            $data = $sheet->toArray();

            $imported = 0;
            $skipped = 0;

            // Skip header row
            foreach (array_slice($data, 1) as $row) {
                if (empty($row[0])) continue; // Skip empty rows

                $name = $row[0] ?? null;
                $phone = $row[1] ?? null;
                $email = $row[2] ?? null;
                $businessName = $row[3] ?? null;
                $type = strtolower($row[4] ?? 'retail');
                $address = $row[5] ?? null;

                if (!$name) {
                    $skipped++;
                    continue;
                }

                // Validate type
                if (!in_array($type, ['retail', 'wholesale'])) {
                    $type = 'retail';
                }

                // Check if customer already exists
                $exists = Customer::where('name', $name)
                    ->where('phone', $phone)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                // Create customer
                Customer::create([
                    'name' => $name,
                    'phone' => $phone,
                    'email' => $email,
                    'business_name' => $businessName,
                    'type' => $type,
                    'address' => $address,
                    'user_id' => Auth::id(),
                ]);

                $imported++;
            }

            $this->js("Swal.fire('Success!', 'Imported: $imported | Skipped: $skipped', 'success')");
            $this->closeImportModal();
        } catch (Exception $e) {
            $this->js("Swal.fire('Error!', '" . addslashes($e->getMessage()) . "', 'error')");
        }
    }
}
