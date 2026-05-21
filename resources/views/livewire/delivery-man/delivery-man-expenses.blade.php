<div>
    <style>
        /* Mobile Responsiveness Tweaks */
        @media (max-width: 767.98px) {
            .container-fluid, .px-4 { padding-left: 10px !important; padding-right: 10px !important; padding-top: 10px !important; }
            .card-body { padding: 12px !important; }
            .table { font-size: 0.8rem !important; }
            .table th, .table td { padding: 8px 6px !important; }
            h2.fw-bold { font-size: 1.4rem !important; }
            h3.fw-bold { font-size: 1.2rem !important; }
            .mb-4 { margin-bottom: 1rem !important; }
            .g-4, .row { --bs-gutter-y: 1rem; --bs-gutter-x: 1rem; }
            .card-header { padding: 10px 12px !important; }
            .card-header h5 { font-size: 1.1rem !important; }
            .badge { font-size: 0.7rem !important; padding: 0.35em 0.5em !important; }
        }
    </style>
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-2">
                <i class="bi bi-wallet2 text-primary me-2"></i> My Expenses
            </h3>
            <p class="text-muted mb-0">Track your expenses and approval status</p>
        </div>
        <button class="btn btn-primary" wire:click="openAddModal">
            <i class="bi bi-plus-lg me-1"></i> Add Expense
        </button>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-xl-4 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="bg-warning bg-opacity-10 rounded-circle p-3">
                                <i class="bi bi-clock text-warning fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1 small">Pending Expenses</p>
                            <h4 class="fw-bold mb-0">Rs. {{ number_format($totalPending, 2) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="bg-success bg-opacity-10 rounded-circle p-3">
                                <i class="bi bi-check-circle text-success fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1 small">Approved Expenses</p>
                            <h4 class="fw-bold mb-0">Rs. {{ number_format($totalApproved, 2) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="bg-danger bg-opacity-10 rounded-circle p-3">
                                <i class="bi bi-x-circle text-danger fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1 small">Rejected Expenses</p>
                            <h4 class="fw-bold mb-0">Rs. {{ number_format($totalRejected, 2) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Expenses List Card -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold text-dark mb-1">
                        <i class="bi bi-journal-text text-primary me-2"></i> Expense History
                    </h5>
                    <p class="text-muted small mb-0">All expenses you have submitted</p>
                </div>
            </div>
        </div>

        <div class="card-body">
            <!-- Filters -->
            <div class="row mb-3">
                <div class="col-md-6 mb-2 mb-md-0">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" placeholder="Search expenses..." wire:model.live="search">
                    </div>
                </div>
                <div class="col-md-6">
                    <select class="form-select" wire:model.live="status_filter">
                        <option value="all">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
            </div>

            <!-- Expenses Table -->
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Admin Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($expenses as $expense)
                            <tr>
                                <td>{{ $expense->expense_date->format('d M, Y') }}</td>
                                <td>
                                    <span class="fw-medium">{{ $expense->expense_type }}</span>
                                </td>
                                <td>
                                    <span class="text-muted">{{ Str::limit($expense->description, 50) ?: '-' }}</span>
                                </td>
                                <td>
                                    <span class="fw-bold">Rs. {{ number_format($expense->amount, 2) }}</span>
                                </td>
                                <td>
                                    @if($expense->status === 'pending')
                                        <span class="badge bg-warning text-dark">
                                            <i class="bi bi-clock me-1"></i> Pending
                                        </span>
                                    @elseif($expense->status === 'approved')
                                        <span class="badge bg-success">
                                            <i class="bi bi-check-circle me-1"></i> Approved
                                        </span>
                                    @else
                                        <span class="badge bg-danger">
                                            <i class="bi bi-x-circle me-1"></i> Rejected
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-muted small">{{ $expense->admin_notes ?: '-' }}</span>
                                </td>
                                <td>
                                    @if($expense->status === 'pending')
                                        <button class="btn btn-sm btn-outline-danger" wire:click="confirmDelete({{ $expense->id }})" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                        No expenses found
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center mt-3">
                {{ $expenses->links() }}
            </div>
        </div>
    </div>

    <!-- Add Expense Modal -->
    @if($showAddModal)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-plus-circle text-primary me-2"></i> Add New Expense
                    </h5>
                    <button type="button" class="btn-close" wire:click="closeAddModal"></button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="addExpense">
                        <div class="mb-3">
                            <label class="form-label">Expense Type <span class="text-danger">*</span></label>
                            <select class="form-select @error('expense_type') is-invalid @enderror" wire:model="expense_type">
                                <option value="">Select Type</option>
                                <option value="Fuel">Fuel</option>
                                <option value="Vehicle Maintenance">Vehicle Maintenance</option>
                                <option value="Parking">Parking</option>
                                <option value="Toll">Toll</option>
                                <option value="Food & Refreshments">Food & Refreshments</option>
                                <option value="Communication">Communication</option>
                                <option value="Loading/Unloading">Loading/Unloading</option>
                                <option value="Other">Other</option>
                            </select>
                            @error('expense_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Amount (Rs.) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" class="form-control @error('amount') is-invalid @enderror" wire:model="amount" placeholder="0.00">
                            @error('amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('expense_date') is-invalid @enderror" wire:model="expense_date">
                            @error('expense_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" rows="3" wire:model="description" placeholder="Brief description of the expense..."></textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="alert alert-info small mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            This expense will require admin approval before processing.
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeAddModal">Cancel</button>
                    <button type="button" class="btn btn-primary" wire:click="addExpense">
                        <i class="bi bi-check-lg me-1"></i> Submit Expense
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if($showDeleteModal)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i> Confirm Delete
                    </h5>
                    <button type="button" class="btn-close" wire:click="cancelDelete"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this expense? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="cancelDelete">Cancel</button>
                    <button type="button" class="btn btn-danger" wire:click="deleteExpense">
                        <i class="bi bi-trash me-1"></i> Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
