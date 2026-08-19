<div class="container-fluid py-3">

    {{-- Page Header --}}
    <div class="d-flex align-items-center mb-4">
        <i class="bi bi-gear-fill text-success fs-2"></i>
        <div class="ms-3">
            <h1 class="h3 fw-bold mb-0">System Settings</h1>
            <p class="text-muted mb-0">Manage all system configurations.</p>
        </div>
    </div>

    {{-- Accordion --}}
    <div class="accordion" id="settingsAccordion">

        {{-- Product Pricing Model & Code Settings Accordion --}}
        <div class="accordion-item border-0 mb-4 shadow-sm rounded-4">
            <h2 class="accordion-header" id="headingPricingModel">
                <button class="accordion-button fw-semibold bg-white text-dark rounded-4"
                    type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapsePricingModel" aria-expanded="true"
                    aria-controls="collapsePricingModel">
                    <i class="bi bi-tags-fill fs-5 me-3 text-dark"></i>
                    Pricing Model & Code Settings
                </button>
            </h2>
            <div id="collapsePricingModel" class="accordion-collapse collapse show"
                aria-labelledby="headingPricingModel" data-bs-parent="#settingsAccordion">
                <div class="accordion-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label fw-bold text-dark">Company Prefix Code</label>
                            <input type="text" wire:model="companyCode" class="form-control text-uppercase" placeholder="e.g. DLB">
                            <small class="text-muted">Used for auto SKU generation (DLBSAMCAR-0001)</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold text-dark">Selling Price Markup %</label>
                            <div class="input-group">
                                <input type="number" step="0.1" wire:model="sellingPriceMarkup" class="form-control" placeholder="80">
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted">Markup over Cost (Cost 100 &rarr; Price 180)</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold text-dark">Normal Discount Profit %</label>
                            <div class="input-group">
                                <input type="number" step="0.1" wire:model="discountPriceMargin" class="form-control" placeholder="50">
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted">Discount Selling Price (Cost 100 &rarr; Price 150)</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold text-dark">Lower Profit Margin Floor %</label>
                            <div class="input-group">
                                <input type="number" step="0.1" wire:model="lowerProfitMargin" class="form-control border-dark" placeholder="40">
                                <span class="input-group-text bg-dark text-white">%</span>
                            </div>
                            <small class="text-muted text-danger fw-bold">Billing floor price limit (Cost 100 &rarr; Min 140)</small>
                        </div>
                    </div>
                    <div class="mt-3 text-end">
                        <button class="btn btn-dark px-4" wire:click="savePricingSettings">
                            <i class="bi bi-save me-1"></i> Save Pricing Settings
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Staff Type Permissions Accordion --}}
        <div class="accordion-item border-0 mb-4 shadow-sm rounded-4">
            <h2 class="accordion-header" id="headingStaffPermissions">
                <button class="accordion-button fw-semibold bg-white text-dark rounded-4"
                    type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapseStaffPermissions" aria-expanded="true"
                    aria-controls="collapseStaffPermissions">
                    <i class="bi bi-person-gear fs-5 me-3 text-primary"></i>
                    Staff Type Permissions
                </button>
            </h2>
            <div id="collapseStaffPermissions" class="accordion-collapse collapse show"
                aria-labelledby="headingStaffPermissions" data-bs-parent="#settingsAccordion">
                <div class="accordion-body">
                    {{-- Staff Type Selector --}}
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Select Staff Type</label>
                            <select wire:model.live="selectedStaffType" class="form-select">
                                @foreach($this->staffTypes as $typeKey => $typeName)
                                    <option value="{{ $typeKey }}">{{ $typeName }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <button class="btn btn-outline-secondary me-2" wire:click="resetStaffTypePermissions">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset to Defaults
                            </button>
                            <button class="btn btn-primary" wire:click="saveStaffTypePermissions">
                                <i class="bi bi-check-circle me-1"></i> Save Permissions
                            </button>
                        </div>
                    </div>

                    {{-- Staff Type Info Card --}}
                    <div class="alert alert-info mb-4">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>{{ $this->staffTypes[$selectedStaffType] ?? 'Staff' }}:</strong>
                        @if($selectedStaffType === 'salesman')
                            Salesmen can create sales orders, view their own sales, create returns, view customer dues (no payment collection), and add expenses.
                        @elseif($selectedStaffType === 'delivery_man')
                            Delivery men can view pending/completed deliveries, confirm deliveries, collect payments, view customer dues, and add expenses.
                        @elseif($selectedStaffType === 'shop_staff')
                            Shop staff can view products (without cost prices), only wholesale and retail prices are visible.
                        @endif
                    </div>

                    {{-- Permissions Grid --}}
                    <div class="row">
                        @foreach($this->permissionCategories as $category => $permissions)
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0 fw-bold text-dark">
                                        <i class="bi bi-folder2-open me-2 text-primary"></i>{{ $category }}
                                    </h6>
                                </div>
                                <div class="card-body">
                                    @foreach($permissions as $permKey)
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" 
                                               id="perm_{{ $permKey }}"
                                               {{ in_array($permKey, $staffTypePermissions) ? 'checked' : '' }}
                                               wire:click="togglePermission('{{ $permKey }}')">
                                        <label class="form-check-label" for="perm_{{ $permKey }}">
                                            {{ $this->availablePermissions[$permKey] ?? $permKey }}
                                        </label>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    {{-- Pricing Visibility Note --}}
                    <div class="alert alert-warning mt-3">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Pricing Visibility:</strong> 
                        <ul class="mb-0 mt-2">
                            <li><strong>Salesman & Delivery Man:</strong> Should only see distributor prices.</li>
                            <li><strong>Shop Staff:</strong> Can see wholesale and retail prices, but NOT cost prices.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- Expense Categories Management Accordion --}}
        <div class="accordion-item border-0 mb-4 shadow-sm rounded-4">
            <h2 class="accordion-header" id="headingExpenseCategories">
                <button class="accordion-button fw-semibold bg-white text-dark rounded-4 collapsed"
                    type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapseExpenseCategories" aria-expanded="false"
                    aria-controls="collapseExpenseCategories">
                    <i class="bi bi-tag fs-5 me-3 text-info"></i>
                    Expense Categories & Types
                </button>
            </h2>
            <div id="collapseExpenseCategories" class="accordion-collapse collapse"
                aria-labelledby="headingExpenseCategories" data-bs-parent="#settingsAccordion">
                <div class="accordion-body">
                    {{-- Add Button --}}
                    <div class="mb-3 d-flex justify-content-end">
                        <button class="btn btn-info shadow-sm" wire:click="openAddCategoryModal">
                            <i class="bi bi-plus-circle"></i> Add Expense Category/Type
                        </button>
                    </div>
                    @php
                        $allCategories = \App\Models\ExpenseCategory::orderBy('expense_category')->orderBy('type')->get()->groupBy('expense_category');
                    @endphp

                    @if($allCategories->isNotEmpty())
                    <div class="row">
                        @foreach($allCategories as $categoryName => $items)
                        <div class="col-md-6 mb-4">
                            <div class="card shadow-sm border-0 h-100">
                                <div class="card-header bg-info bg-opacity-10">
                                    <h6 class="mb-0 fw-bold text-dark">
                                        <i class="bi bi-folder2-open me-2"></i>{{ $categoryName }}
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <ul class="list-group list-group-flush">
                                        @foreach($items as $item)
                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                            <span><i class="bi bi-tag me-2 text-muted"></i>{{ $item->type }}</span>
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    wire:click="confirmDeleteCategoryType({{ $item->id }})"
                                                    title="Delete this type">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-inbox display-4 d-block mb-3"></i>
                        No expense categories found.
                    </div>
                    @endif

                </div>
            </div>
        </div>

        {{-- System Configurations Accordion --}}
        <div class="accordion-item border-0 mb-4 shadow-sm rounded-4">
            <h2 class="accordion-header" id="headingSystemConfigs">
                <button class="accordion-button fw-semibold bg-white text-dark rounded-4 collapsed"
                    type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapseSystemConfigs" aria-expanded="false"
                    aria-controls="collapseSystemConfigs">
                    <i class="bi bi-sliders fs-5 me-3 text-success"></i>
                    System Configurations
                </button>
            </h2>
            <div id="collapseSystemConfigs" class="accordion-collapse collapse"
                aria-labelledby="headingSystemConfigs" data-bs-parent="#settingsAccordion">
                <div class="accordion-body">

                    {{-- Add Button inside accordion --}}
                    <div class="mb-3 d-flex justify-content-end">
                        <button class="btn btn-primary shadow-sm" wire:click="openAddModal">
                            <i class="bi bi-plus-circle"></i> Add Configuration
                        </button>
                    </div>

                    {{-- Existing Configurations --}}
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            @if($settings->isNotEmpty())
                            <table class="table table-bordered align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-dark fw-bold">Key</th>
                                        <th class="text-dark fw-bold">Value</th>
                                        <th class="text-center text-dark fw-bold" style="width: 180px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($settings as $setting)
                                    <tr>
                                        <td class="text-dark">{{ $setting->key }}</td>
                                        <td class="text-dark">{{ $setting->value }}</td>
                                        <td class="text-center">
    <div class="dropdown">
        <button class="btn btn-sm btn-light border-0 dropdown-toggle" 
                type="button" 
                data-bs-toggle="dropdown" 
                aria-expanded="false">
            <i class="bi bi-three-dots-vertical"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
            <li>
                <a class="dropdown-item text-primary" 
                   href="#" 
                   wire:click.prevent="openEditModal({{ $setting->id }})">
                    <i class="bi bi-pencil me-2"></i>Edit
                </a>
            </li>
            <li>
                <a class="dropdown-item text-danger" 
                   href="#" 
                   wire:click.prevent="confirmDelete({{ $setting->id }})">
                    <i class="bi bi-trash me-2"></i>Delete
                </a>
            </li>
        </ul>
    </div>
</td>


                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @else
                            <div class="text-center py-5 text-muted">
                                <i class="bi bi-inbox display-4 d-block mb-3"></i>
                                No configurations found. <br>
                                <small>Click "Add Configuration" to create your first setting.</small>
                            </div>
                            @endif
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- Modal: Add/Edit --}}
    @if($showModal)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" wire:key="modal-{{ $isEdit ? 'edit' : 'add' }}">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow-lg">
                <div class="modal-header bg-primary text-white rounded-top-4">
                    <h5 class="modal-title fw-bold">
                        @if($isEdit)
                        <i class="bi bi-pencil-square"></i> Edit Configuration
                        @else
                        <i class="bi bi-plus-circle"></i> Add Configuration
                        @endif
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeModal"></button>
                </div>

                <form wire:submit.prevent="{{ $isEdit ? 'updateConfiguration' : 'saveConfiguration' }}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Key</label>
                            <input type="text" wire:model="key"
                                class="form-control @error('key') is-invalid @enderror"
                                placeholder="Enter configuration key">
                            @error('key')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Value</label>
                            <input type="text" wire:model="value"
                                class="form-control @error('value') is-invalid @enderror"
                                placeholder="Enter configuration value">
                            @error('value')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-secondary shadow-sm" wire:click="closeModal" wire:loading.attr="disabled">
                            <i class="bi bi-x-circle"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-success shadow-sm" wire:loading.attr="disabled">
                            <span wire:loading.remove>
                                <i class="bi bi-check-circle"></i>
                                @if($isEdit)
                                Update Configuration
                                @else
                                Save Configuration
                                @endif
                            </span>
                            <span wire:loading>
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                                Processing...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- Expense Modal --}}
    @if($showExpenseModal)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" wire:key="expense-modal-{{ $isEditExpense ? 'edit' : 'add' }}">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 rounded-4 shadow-lg">
                <div class="modal-header bg-warning text-white rounded-top-4">
                    <h5 class="modal-title fw-bold">
                        @if($isEditExpense)
                        <i class="bi bi-pencil-square"></i> Edit Expense
                        @else
                        <i class="bi bi-plus-circle"></i> Add New Expense
                        @endif
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeExpenseModal"></button>
                </div>

                <form wire:submit.prevent="{{ $isEditExpense ? 'updateExpense' : 'saveExpense' }}">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                                <select wire:model.live="expenseCategory"
                                    class="form-select @error('expenseCategory') is-invalid @enderror">
                                    <option value="">Select Category</option>
                                    @foreach($expenseCategories as $category)
                                        <option value="{{ $category }}">{{ $category }}</option>
                                    @endforeach
                                </select>
                                @error('expenseCategory')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Expense Type <span class="text-danger">*</span></label>
                                <select wire:model="expenseType"
                                    class="form-select @error('expenseType') is-invalid @enderror"
                                    {{ empty($expenseCategory) ? 'disabled' : '' }}>
                                    <option value="">Select Type</option>
                                    @foreach($expenseTypes as $type)
                                        <option value="{{ $type }}">{{ $type }}</option>
                                    @endforeach
                                </select>
                                @error('expenseType')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                @if(empty($expenseCategory))
                                    <small class="text-muted">Please select a category first</small>
                                @endif
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Amount <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" step="0.01" wire:model="expenseAmount"
                                        class="form-control @error('expenseAmount') is-invalid @enderror"
                                        placeholder="0.00">
                                    @error('expenseAmount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                                <input type="date" wire:model="expenseDate"
                                    class="form-control @error('expenseDate') is-invalid @enderror">
                                @error('expenseDate')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                            <select wire:model="expenseStatus"
                                class="form-select @error('expenseStatus') is-invalid @enderror">
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>
                            @error('expenseStatus')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea wire:model="expenseDescription"
                                class="form-control @error('expenseDescription') is-invalid @enderror"
                                rows="3"
                                placeholder="Enter expense description or notes..."></textarea>
                            @error('expenseDescription')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-secondary shadow-sm" wire:click="closeExpenseModal" wire:loading.attr="disabled">
                            <i class="bi bi-x-circle"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-warning text-white shadow-sm" wire:loading.attr="disabled">
                            <span wire:loading.remove>
                                <i class="bi bi-check-circle"></i>
                                @if($isEditExpense)
                                Update Expense
                                @else
                                Save Expense
                                @endif
                            </span>
                            <span wire:loading>
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                                Processing...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- Expense Category Modal --}}
    @if($showCategoryModal)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" wire:key="category-modal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow-lg">
                <div class="modal-header bg-info text-white rounded-top-4">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-plus-circle"></i> Add Expense Category/Type
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeCategoryModal"></button>
                </div>

                <form wire:submit.prevent="saveCategoryType">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Expense Category <span class="text-danger">*</span></label>
                            <select wire:model="newExpenseCategory"
                                class="form-select @error('newExpenseCategory') is-invalid @enderror">
                                <option value="">Select Existing Category</option>
                                <option value="Monthly Expenses">Monthly Expenses</option>
                                <option value="Daily Expenses">Daily Expenses</option>
                                <option value="__new__">+ Create New Category</option>
                            </select>
                            @error('newExpenseCategory')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @if($newExpenseCategory === '__new__')
                        <div class="mb-3">
                            <label class="form-label fw-semibold">New Category Name <span class="text-danger">*</span></label>
                            <input type="text" wire:model="customExpenseCategory"
                                class="form-control @error('customExpenseCategory') is-invalid @enderror"
                                placeholder="e.g., Annual Expenses, Weekly Expenses">
                            @error('customExpenseCategory')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Expense Type <span class="text-danger">*</span></label>
                            <input type="text" wire:model="newExpenseType"
                                class="form-control @error('newExpenseType') is-invalid @enderror"
                                placeholder="e.g., Snacks, Electricity Bill, Rent">
                            @error('newExpenseType')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Enter the type of expense for the selected category</small>
                        </div>

                        <div class="alert alert-warning border-0 shadow-sm">
                            <i class="bi bi-lightbulb me-2"></i>
                            <strong>Tip:</strong> Category groups types together (e.g., "Monthly Expenses" can have types like "Rent", "Electricity Bill").
                        </div>
                    </div>

                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-secondary shadow-sm" wire:click="closeCategoryModal" wire:loading.attr="disabled">
                            <i class="bi bi-x-circle"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-info text-white shadow-sm" wire:loading.attr="disabled">
                            <span wire:loading.remove>
                                <i class="bi bi-check-circle"></i> Save Category/Type
                            </span>
                            <span wire:loading>
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                                Saving...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

</div>

@push('styles')
<style>
    .list-group-item {
        background-color: #fff;
        transition: all 0.2s ease-in-out;
        border: 1px solid #dee2e6;
    }

    .list-group-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.1);
    }

    .modal.fade.show {
        display: block !important;
    }

    .btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .spinner-border-sm {
        width: 1rem;
        height: 1rem;
    }

    .table th,
    .table td {
        vertical-align: middle;
    }

    .table-bordered {
        border-color: #dee2e6;
    }

    .accordion-button:not(.collapsed) {
        background-color: #fff;
        color: #000;
        box-shadow: none;
    }

    .accordion-button:focus {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }

    .accordion-body {
        padding: 1.5rem;
    }
</style>
@endpush

@push('scripts')
<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // SweetAlert for delete confirmation (System Configurations)
    window.addEventListener('swal:confirm-delete', event => {
        Swal.fire({
            title: 'Are you sure?',
            text: "This configuration will be permanently deleted!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                Livewire.dispatch('deleteConfirmed', {
                    id: event.detail.id
                });
            }
        });
    });

    // SweetAlert for delete confirmation (Expenses)
    window.addEventListener('swal:confirm-delete-expense', event => {
        Swal.fire({
            title: 'Are you sure?',
            text: "This expense will be permanently deleted!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                Livewire.dispatch('deleteExpenseConfirmed', {
                    id: event.detail.id
                });
            }
        });
    });

    // SweetAlert for delete confirmation (Expense Category/Type)
    window.addEventListener('swal:confirm-delete-category-type', event => {
        Swal.fire({
            title: 'Are you sure?',
            text: "This expense category/type will be permanently deleted!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                Livewire.dispatch('deleteCategoryTypeConfirmed', {
                    id: event.detail.id
                });
            }
        });
    });
</script>
@endpush