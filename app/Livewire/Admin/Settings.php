<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Setting;
use App\Models\User;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\StaffTypePermission;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Livewire\Concerns\WithDynamicLayout;

#[Title("System Settings")]
class Settings extends Component
{
    use WithDynamicLayout;

    public $settings = [];
    public $key;
    public $value;
    public $showModal = false;
    public $isEdit = false;
    public $editingId = null;
    public $deleteId = null;

    // Pricing Model & Company Settings
    public $sellingPriceMarkup = 80;
    public $discountPriceMargin = 50;
    public $lowerProfitMargin = 40;
    public $companyCode = 'DLB';

    // Expense Management
    public $expenses = [];
    public $expenseCategories = [];
    public $expenseTypes = [];
    public $expenseCategory = '';
    public $expenseType = '';
    public $expenseAmount = '';
    public $expenseDate = '';
    public $expenseStatus = 'pending';
    public $expenseDescription = '';
    public $showExpenseModal = false;
    public $isEditExpense = false;
    public $editingExpenseId = null;
    public $deleteExpenseId = null;

    // New Expense Category Management
    public $showCategoryModal = false;
    public $newExpenseCategory = '';
    public $newExpenseType = '';
    public $customExpenseCategory = '';
    public $isEditCategory = false;
    public $editingCategoryId = null;
    public $deleteCategoryTypeId = null;

    // Staff Type Permission Management
    public $selectedStaffType = 'salesman';
    public $staffTypePermissions = [];
    public $showStaffPermissionModal = false;

    protected $listeners = [
        'deleteConfirmed' => 'deleteConfiguration',
        'deleteExpenseConfirmed' => 'deleteExpense',
        'deleteCategoryTypeConfirmed' => 'deleteCategoryType'
    ];

    protected $rules = [
        'key' => 'required|string|max:255|unique:settings,key',
        'value' => 'required|string|max:255',
    ];

    protected $messages = [
        'key.required' => 'The configuration key is required.',
        'key.unique' => 'This configuration key already exists. Please use a different key.',
        'key.max' => 'The configuration key cannot exceed 255 characters.',
        'value.required' => 'The configuration value is required.',
        'value.max' => 'The configuration value cannot exceed 255 characters.',
    ];

    public function mount()
    {
        $this->loadSettings();
        $this->loadExpenses();
        $this->loadExpenseCategories();
        $this->loadStaffTypePermissions();
        $this->loadPricingSettings();
        $this->expenseDate = now()->format('Y-m-d');
    }

    public function loadPricingSettings()
    {
        $this->sellingPriceMarkup = (float) Setting::getVal('default_selling_markup_pct', 80);
        $this->discountPriceMargin = (float) Setting::getVal('default_discount_pct', 50);
        $this->lowerProfitMargin = (float) Setting::getVal('min_profit_margin_pct', 40);
        $this->companyCode = Setting::getVal('company_code', 'DLB');
    }

    public function savePricingSettings()
    {
        try {
            $this->validate([
                'sellingPriceMarkup' => 'required|numeric|min:0',
                'discountPriceMargin' => 'required|numeric|min:0',
                'lowerProfitMargin' => 'required|numeric|min:0',
                'companyCode' => 'required|string|max:10',
            ]);

            Setting::setVal('default_selling_markup_pct', $this->sellingPriceMarkup, 'Default Selling Price Markup % over Cost');
            Setting::setVal('default_discount_pct', $this->discountPriceMargin, 'Standard Discount / Profit Margin % over Cost');
            Setting::setVal('min_profit_margin_pct', $this->lowerProfitMargin, 'Minimum Profit Margin Floor % over Cost');
            Setting::setVal('company_code', strtoupper($this->companyCode), 'Company Prefix Code');

            $this->js("Swal.fire('Success!', 'Pricing model settings saved successfully.', 'success')");
        } catch (\Exception $e) {
            $this->js("Swal.fire('Error!', 'Unable to save pricing settings. Please check your inputs.', 'error')");
        }
    }

    public function loadExpenseCategories()
    {
        $this->expenseCategories = ExpenseCategory::select('expense_category')
            ->distinct()
            ->pluck('expense_category')
            ->toArray();
    }

    public function updatedExpenseCategory()
    {
        // When category changes, load its types
        $this->expenseTypes = ExpenseCategory::where('expense_category', $this->expenseCategory)
            ->pluck('type')
            ->toArray();
        $this->expenseType = ''; // Reset selected type
    }

    public function loadSettings()
    {
        $this->settings = Setting::orderBy('created_at', 'desc')->get();
    }

    public function resetForm()
    {
        $this->reset(['key', 'value', 'editingId', 'isEdit', 'deleteId']);
        $this->resetErrorBag();
    }

    public function openAddModal()
    {
        $this->resetForm();
        $this->showModal = true;
        $this->isEdit = false;
    }

    public function openEditModal($id)
    {
        $setting = Setting::findOrFail($id);
        $this->editingId = $id;
        $this->key = $setting->key;
        $this->value = $setting->value;
        $this->isEdit = true;
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function saveConfiguration()
    {
        try {
            $this->validate();

            Setting::create([
                'key' => $this->key,
                'value' => $this->value,
                'date' => now(),
            ]);

            $this->closeModal();
            $this->loadSettings();

            $this->js("Swal.fire('Success!', 'Configuration has been added successfully.', 'success')");
        } catch (\Exception $e) {
            $this->js("Swal.fire('Error!', 'Unable to add configuration. Please try again.', 'error')");
        }
    }

    public function updateConfiguration()
    {
        try {
            $this->validate([
                'key' => 'required|string|max:255|unique:settings,key,' . $this->editingId,
                'value' => 'required|string|max:255',
            ]);

            $setting = Setting::findOrFail($this->editingId);
            $setting->update([
                'key' => $this->key,
                'value' => $this->value,
            ]);

            $this->closeModal();
            $this->loadSettings();

            $this->js("Swal.fire('Success!', 'Configuration has been updated successfully.', 'success')");
        } catch (\Exception $e) {
            $this->js("Swal.fire('Error!', 'Unable to update configuration. Please try again.', 'error')");
        }
    }

    public function confirmDelete($id = null)
    {
        $this->deleteId = $id;
        $this->dispatch('swal:confirm-delete', ['id' => $id]);
    }

    public function deleteConfiguration($id = null)
    {
        try {
            $deleteId = $id ?? $this->deleteId;

            if (!$deleteId) {
                throw new \Exception('No configuration selected for deletion.');
            }

            $setting = Setting::findOrFail($deleteId);
            $setting->delete();
            $this->loadSettings();

            $this->deleteId = null;

            $this->js("Swal.fire('Success!', 'Configuration has been deleted successfully.', 'success')");
        } catch (\Exception $e) {
            $this->js("Swal.fire('Error!', 'Unable to delete configuration. Please try again.', 'error')");
        }
    }

    // Expense Management Methods
    public function loadExpenses()
    {
        $this->expenses = Expense::orderBy('date', 'desc')->get();
    }

    public function resetExpenseForm()
    {
        $this->reset(['expenseCategory', 'expenseType', 'expenseAmount', 'expenseDate', 'expenseStatus', 'expenseDescription', 'editingExpenseId', 'isEditExpense', 'deleteExpenseId']);
        $this->expenseDate = now()->format('Y-m-d');
        $this->expenseStatus = 'pending';
        $this->resetErrorBag();
    }

    public function openAddExpenseModal()
    {
        $this->resetExpenseForm();
        $this->showExpenseModal = true;
        $this->isEditExpense = false;
    }

    public function openEditExpenseModal($id)
    {
        $expense = Expense::findOrFail($id);
        $this->editingExpenseId = $id;
        $this->expenseCategory = $expense->category;

        // Load types for this category
        $this->expenseTypes = ExpenseCategory::where('expense_category', $this->expenseCategory)
            ->pluck('type')
            ->toArray();

        $this->expenseType = $expense->expense_type;
        $this->expenseAmount = $expense->amount;
        $this->expenseDate = $expense->date->format('Y-m-d');
        $this->expenseStatus = $expense->status;
        $this->expenseDescription = $expense->description;
        $this->isEditExpense = true;
        $this->showExpenseModal = true;
    }

    public function closeExpenseModal()
    {
        $this->showExpenseModal = false;
        $this->resetExpenseForm();
    }

    public function saveExpense()
    {
        try {
            $this->validate([
                'expenseCategory' => 'required|string|max:255',
                'expenseType' => 'required|string|max:255',
                'expenseAmount' => 'required|numeric|min:0',
                'expenseDate' => 'required|date',
                'expenseStatus' => 'required|in:pending,approved,rejected',
                'expenseDescription' => 'nullable|string|max:1000',
            ]);

            Expense::create([
                'category' => $this->expenseCategory,
                'expense_type' => $this->expenseType,
                'amount' => $this->expenseAmount,
                'date' => $this->expenseDate,
                'status' => $this->expenseStatus,
                'description' => $this->expenseDescription,
            ]);

            $this->closeExpenseModal();
            $this->loadExpenses();

            $this->js("Swal.fire('Success!', 'Expense has been added successfully.', 'success')");
        } catch (\Exception $e) {
            $this->js("Swal.fire('Error!', 'Unable to add expense. Please try again.', 'error')");
        }
    }

    public function updateExpense()
    {
        try {
            $this->validate([
                'expenseCategory' => 'required|string|max:255',
                'expenseType' => 'required|string|max:255',
                'expenseAmount' => 'required|numeric|min:0',
                'expenseDate' => 'required|date',
                'expenseStatus' => 'required|in:pending,approved,rejected',
                'expenseDescription' => 'nullable|string|max:1000',
            ]);

            $expense = Expense::findOrFail($this->editingExpenseId);
            $expense->update([
                'category' => $this->expenseCategory,
                'expense_type' => $this->expenseType,
                'amount' => $this->expenseAmount,
                'date' => $this->expenseDate,
                'status' => $this->expenseStatus,
                'description' => $this->expenseDescription,
            ]);

            $this->closeExpenseModal();
            $this->loadExpenses();

            $this->js("Swal.fire('Success!', 'Expense has been updated successfully.', 'success')");
        } catch (\Exception $e) {
            $this->js("Swal.fire('Error!', 'Unable to update expense. Please try again.', 'error')");
        }
    }

    public function confirmDeleteExpense($id)
    {
        $this->deleteExpenseId = $id;
        $this->dispatch('swal:confirm-delete-expense', ['id' => $id]);
    }

    public function deleteExpense($id = null)
    {
        try {
            $deleteId = $id ?? $this->deleteExpenseId;

            if (!$deleteId) {
                throw new \Exception('No expense selected for deletion.');
            }

            $expense = Expense::findOrFail($deleteId);
            $expense->delete();
            $this->loadExpenses();

            $this->deleteExpenseId = null;

            $this->js("Swal.fire('Success!', 'Expense has been deleted successfully.', 'success')");
        } catch (\Exception $e) {
            $this->js("Swal.fire('Error!', 'Unable to delete expense. Please try again.', 'error')");
        }
    }

    // Expense Category Management Methods
    public function openAddCategoryModal()
    {
        $this->reset(['newExpenseCategory', 'newExpenseType', 'customExpenseCategory']);
        $this->showCategoryModal = true;
    }

    public function closeCategoryModal()
    {
        $this->showCategoryModal = false;
        $this->reset(['newExpenseCategory', 'newExpenseType', 'customExpenseCategory']);
        $this->resetErrorBag();
    }

    public function saveCategoryType()
    {
        try {
            $rules = [
                'newExpenseType' => 'required|string|max:255',
            ];

            // If creating new category
            if ($this->newExpenseCategory === '__new__') {
                $rules['customExpenseCategory'] = 'required|string|max:255';
                $this->validate($rules);
                $categoryName = $this->customExpenseCategory;
            } else {
                $rules['newExpenseCategory'] = 'required|string|max:255';
                $this->validate($rules);
                $categoryName = $this->newExpenseCategory;
            }

            // Check if this combination already exists
            $exists = ExpenseCategory::where('expense_category', $categoryName)
                ->where('type', $this->newExpenseType)
                ->exists();

            if ($exists) {
                $this->js("Swal.fire('Warning!', 'This category and type combination already exists.', 'warning')");
                return;
            }

            // Create new expense category/type
            ExpenseCategory::create([
                'expense_category' => $categoryName,
                'type' => $this->newExpenseType,
            ]);

            $this->closeCategoryModal();
            $this->loadExpenseCategories();

            $this->js("Swal.fire('Success!', 'Expense category/type has been added successfully.', 'success')");
        } catch (\Exception $e) {
            $this->js("Swal.fire('Error!', 'Unable to add category/type. Please try again.', 'error')");
        }
    }

    public function confirmDeleteCategoryType($id)
    {
        $this->deleteCategoryTypeId = $id;
        $this->dispatch('swal:confirm-delete-category-type', ['id' => $id]);
    }

    public function deleteCategoryType($id = null)
    {
        try {
            $deleteId = $id ?? $this->deleteCategoryTypeId;

            if (!$deleteId) {
                throw new \Exception('No category type selected for deletion.');
            }

            $categoryType = ExpenseCategory::findOrFail($deleteId);
            $categoryType->delete();
            $this->loadExpenseCategories();

            $this->deleteCategoryTypeId = null;

            $this->js("Swal.fire('Success!', 'Expense category/type has been deleted successfully.', 'success')");
        } catch (\Exception $e) {
            $this->js("Swal.fire('Error!', 'Unable to delete category/type. Please try again.', 'error')");
        }
    }

    // Staff Type Permission Management Methods
    public function loadStaffTypePermissions()
    {
        $this->staffTypePermissions = StaffTypePermission::getPermissions($this->selectedStaffType);

        // If no custom permissions are set, load defaults
        if (empty($this->staffTypePermissions)) {
            $this->staffTypePermissions = StaffTypePermission::defaultPermissions()[$this->selectedStaffType] ?? [];
        }
    }

    public function updatedSelectedStaffType()
    {
        $this->loadStaffTypePermissions();
    }

    public function togglePermission($permissionKey)
    {
        if (in_array($permissionKey, $this->staffTypePermissions)) {
            $this->staffTypePermissions = array_diff($this->staffTypePermissions, [$permissionKey]);
        } else {
            $this->staffTypePermissions[] = $permissionKey;
        }
        $this->staffTypePermissions = array_values($this->staffTypePermissions);
    }

    public function saveStaffTypePermissions()
    {
        try {
            StaffTypePermission::syncPermissions($this->selectedStaffType, $this->staffTypePermissions);
            $this->js("Swal.fire('Success!', 'Staff type permissions have been updated successfully.', 'success')");
        } catch (\Exception $e) {
            $this->js("Swal.fire('Error!', 'Unable to save permissions. Please try again.', 'error')");
        }
    }

    public function resetStaffTypePermissions()
    {
        try {
            StaffTypePermission::resetToDefaults($this->selectedStaffType);
            $this->loadStaffTypePermissions();
            $this->js("Swal.fire('Success!', 'Permissions have been reset to defaults.', 'success')");
        } catch (\Exception $e) {
            $this->js("Swal.fire('Error!', 'Unable to reset permissions. Please try again.', 'error')");
        }
    }

    public function getStaffTypesProperty()
    {
        return StaffTypePermission::staffTypes();
    }

    public function getAvailablePermissionsProperty()
    {
        return StaffTypePermission::availablePermissions();
    }

    public function getPermissionCategoriesProperty()
    {
        return StaffTypePermission::permissionCategories();
    }

    public function render()
    {
        return view('livewire.admin.settings')->layout($this->layout);
    }
}
