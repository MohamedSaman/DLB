<div class="container-fluid py-3">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h3 class="fw-bold text-dark mb-2">
                <i class="bi bi-people-fill text-success me-2"></i> My Customers
            </h3>
            <p class="text-muted mb-0">Manage your customer information efficiently</p>
        </div>
        <div>
            <a href="{{ route('staff.billing') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-2"></i> Add New Customer
            </a>
        </div>
    </div>

    {{-- Customer List --}}
    <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="fw-bold text-dark mb-1">
                    <i class="bi bi-journal-text text-primary me-2"></i> Customer List
                </h5>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <input type="text" class="form-control form-control-sm" placeholder="Search customers..." 
                       wire:model.live="search" style="width: 200px;">
                <select wire:model.live="customerTypeFilter" class="form-select form-select-sm" style="width: 170px;">
                    <option value="all">All Customer Types</option>
                    <option value="retail">Retail</option>
                    <option value="wholesale">Wholesale</option>
                    <option value="distributor">Distributor</option>
                </select>
                <label class="text-sm text-muted fw-medium ms-1">Show</label>
                <select wire:model.live="perPage" class="form-select form-select-sm" style="width: 80px;">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span class="text-sm text-muted">entries</span>
            </div>
        </div>
        <div class="card-body p-0 overflow-auto">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">#</th>
                            <th>Customer Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Type</th>
                            <th>Address</th>
                            <th>Created</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($customers->count() > 0)
                            @foreach($customers as $customer)
                                <tr>
                                    <td class="ps-4">{{ ($customers->currentPage() - 1) * $customers->perPage() + $loop->iteration }}</td>
                                    <td>
                                        <span class="fw-medium text-dark">{{ $customer->name }}</span>
                                    </td>
                                    <td>
                                        <span class="text-dark">{{ $customer->phone }}</span>
                                    </td>
                                    <td>
                                        <span class="text-muted small">{{ $customer->email ?? '-' }}</span>
                                    </td>
                                    <td>
                                        @if($customer->type == 'retail')
                                        <span class="badge bg-success">Retail</span>
                                        @elseif($customer->type == 'wholesale')
                                        <span class="badge bg-info">Wholesale</span>
                                        @else
                                        <span class="badge bg-secondary">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ \Illuminate\Support\Str::limit($customer->address, 30) ?? '-' }}</small>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $customer->created_at->format('d/m/Y') }}</small>
                                    </td>
                                    <td class="text-end pe-2">
                                        <div class="dropdown">
                                            <button class="btn btn-outline-secondary dropdown-toggle"
                                                    type="button"
                                                    data-bs-toggle="dropdown"
                                                    aria-expanded="false">
                                                <i class="bi bi-gear-fill"></i> Actions
                                            </button>

                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                <!-- Edit Customer -->
                                                <li>
                                                    <button class="dropdown-item"
                                                            wire:click="editCustomer({{ $customer->id }})"
                                                            wire:loading.attr="disabled"
                                                            title="Edit Customer">
                                                        <span wire:loading wire:target="editCustomer({{ $customer->id }})">
                                                            <i class="spinner-border spinner-border-sm me-2"></i> Loading...
                                                        </span>
                                                        <span wire:loading.remove wire:target="editCustomer({{ $customer->id }})">
                                                            <i class="bi bi-pencil text-primary me-2"></i> Edit
                                                        </span>
                                                    </button>
                                                </li>

                                                <!-- Delete Customer -->
                                                <li>
                                                    <button class="dropdown-item"
                                                            wire:click="confirmDelete({{ $customer->id }})"
                                                            wire:loading.attr="disabled"
                                                            title="Delete Customer">
                                                        <span wire:loading wire:target="confirmDelete({{ $customer->id }})">
                                                            <i class="spinner-border spinner-border-sm me-2"></i> Loading...
                                                        </span>
                                                        <span wire:loading.remove wire:target="confirmDelete({{ $customer->id }})">
                                                            <i class="bi bi-trash text-danger me-2"></i> Delete
                                                        </span>
                                                    </button>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="bi bi-people display-4 d-block mb-2"></i>
                                    No customers found
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-light">
                <div class="d-flex justify-content-center">
                    {{ $customers->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    @if($showEditModal)
        <div class="modal d-block" tabindex="-1" style="background: rgba(0,0,0,0.5); display: block;">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-pencil-square me-2"></i> Edit Customer
                        </h5>
                        <button type="button" class="btn-close btn-close-white" 
                                wire:click="$set('showEditModal', false)"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-12">
                                <label class="form-label fw-medium">Customer Name</label>
                                <input type="text" class="form-control" 
                                       wire:model="editingData.name" placeholder="Enter customer name">
                                @error('editingData.name') <small class="text-danger d-block mt-1">{{ $message }}</small> @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Phone</label>
                                <input type="text" class="form-control" 
                                       wire:model="editingData.phone" placeholder="Enter phone number">
                                @error('editingData.phone') <small class="text-danger d-block mt-1">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Email</label>
                                <input type="email" class="form-control" 
                                       wire:model="editingData.email" placeholder="Enter email (optional)">
                                @error('editingData.email') <small class="text-danger d-block mt-1">{{ $message }}</small> @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Type</label>
                                <select class="form-select" wire:model="editingData.type">
                                    <option value="retail">Retail</option>
                                    <option value="wholesale">Wholesale</option>
                                </select>
                                @error('editingData.type') <small class="text-danger d-block mt-1">{{ $message }}</small> @enderror
                            </div>
                        </div>

                        <div class="mb-0">
                            <label class="form-label fw-medium">Address</label>
                            <textarea class="form-control" wire:model="editingData.address" 
                                      rows="2" placeholder="Enter address (optional)"></textarea>
                            @error('editingData.address') <small class="text-danger d-block mt-1">{{ $message }}</small> @enderror
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" 
                                wire:click="$set('showEditModal', false)">
                            <i class="bi bi-x-circle me-1"></i> Cancel
                        </button>
                        <button type="button" class="btn btn-primary" wire:click="saveCustomer">
                            <i class="bi bi-check-circle me-1"></i> Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if($showDeleteConfirm)
        <div class="modal d-block" tabindex="-1" style="background: rgba(0,0,0,0.5); display: block;">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-exclamation-triangle me-2"></i> Delete Customer
                        </h5>
                        <button type="button" class="btn-close btn-close-white" 
                                wire:click="$set('showDeleteConfirm', false)"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">
                            <i class="bi bi-info-circle text-warning me-2"></i>
                            Are you sure you want to delete this customer? This action cannot be undone.
                        </p>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" 
                                wire:click="$set('showDeleteConfirm', false)">
                            <i class="bi bi-x-circle me-1"></i> Cancel
                        </button>
                        <button type="button" class="btn btn-danger" wire:click="deleteCustomer">
                            <i class="bi bi-trash me-1"></i> Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
