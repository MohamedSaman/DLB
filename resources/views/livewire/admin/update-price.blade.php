<div>
    <div class="container-fluid p-3">
        <div id="price-update" class="tab-content active">
            <div class="d-flex justify-content-between align-items-center mb-5">
                <div>
                    <h3 class="fw-bold text-dark mb-2">
                        <i class="bi bi-currency-dollar text-success me-2"></i> Product Price Management
                    </h3>
                    <p class="text-muted mb-0">Manage wholesale, distributor, and retail prices efficiently</p>
                </div>
            </div>

            <div class="inventory-header w-100 mb-1">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center w-100 gap-3">
                    <div class="search-bar flex-grow-1">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="bi bi-search text-muted"></i>
                            </span>
                            <input type="text"
                                   class="form-control border-start-0"
                                   wire:model.live="searchTerm"
                                   wire:keydown.enter="search"
                                   placeholder="Search by code, product name, or variant (e.g. Test 6)...">
                        </div>
                    </div>

                    <div class="d-flex gap-2 justify-content-md-end">
                        <button type="button" class="btn btn-success" wire:click="exportCSV" title="Download current prices">
                            <i class="bi bi-download"></i>
                            <span class="d-none d-sm-inline">Export CSV</span>
                        </button>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#importModal" title="Import prices from CSV/Excel">
                            <i class="bi bi-upload"></i>
                            <span class="d-none d-sm-inline">Import CSV/Excel</span>
                        </button>
                    </div>
                </div>
            </div>

            @if (session()->has('success'))
                <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                    <i class="bi bi-check-circle"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="row g-4 mt-1">
                <div class="col-12">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="fw-bold text-dark mb-1">
                                    <i class="bi bi-list-ul text-primary me-2"></i> Products Price List
                                </h5>
                                <p class="text-muted small mb-0">View and manage product and variant prices</p>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <label class="text-sm text-muted fw-medium">Show</label>
                                <select wire:model.live="perPage" class="form-select form-select-sm" style="width: 80px;">
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                    <option value="200">200</option>
                                    <option value="500">500</option>
                                </select>
                                <span class="text-sm text-muted">entries</span>
                            </div>
                        </div>

                        <div class="card-body p-0 overflow-auto">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light border-top">
                                        <tr>
                                            <th class="ps-4">Product Code</th>
                                            <th>Product Name</th>
                                            <th class="text-end">Wholesale Price</th>
                                            <th class="text-end">Distributor Price</th>
                                            <th class="text-end">Retail Price</th>
                                            <th class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($products as $product)
                                            <tr>
                                                <td class="ps-4">
                                                    <span class="fw-medium text-dark">{{ $product['product_code'] }}</span>
                                                </td>
                                                <td>
                                                    <div>
                                                        <span class="fw-medium text-dark">{{ $product['product_name'] }} - 
                                                        @if($product['has_variant'])
                                                            {{ $product['variant_name'] }}: {{ $product['variant_value'] }}
                                                        @endif
                                                        </span>
                                                        
                                                    </div>
                                                </td>
                                                <td class="text-end">
                                                    <div class="editable-cell" data-price-id="{{ $product['price_id'] }}" data-field="wholesale_price">
                                                        <span class="price-value">{{ number_format($product['wholesale_price'], 2) }}</span>
                                                        <input type="number"
                                                               step="0.01"
                                                               class="form-control form-control-sm d-none"
                                                               value="{{ $product['wholesale_price'] }}">
                                                    </div>
                                                </td>
                                                <td class="text-end">
                                                    <div class="editable-cell" data-price-id="{{ $product['price_id'] }}" data-field="distributor_price">
                                                        <span class="price-value">{{ number_format($product['distributor_price'], 2) }}</span>
                                                        <input type="number"
                                                               step="0.01"
                                                               class="form-control form-control-sm d-none"
                                                               value="{{ $product['distributor_price'] }}">
                                                    </div>
                                                </td>
                                                <td class="text-end">
                                                    <div class="editable-cell" data-price-id="{{ $product['price_id'] }}" data-field="retail_price">
                                                        <span class="price-value">{{ number_format($product['retail_price'], 2) }}</span>
                                                        <input type="number"
                                                               step="0.01"
                                                               class="form-control form-control-sm d-none"
                                                               value="{{ $product['retail_price'] }}">
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <button class="btn btn-sm btn-outline-secondary edit-price-btn" title="Edit price">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-5 text-muted">
                                                    <i class="bi bi-inbox"></i>
                                                    <p class="mt-2 mb-0">No products found</p>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        @if($products->count() > 0)
                            <div class="card-footer bg-white border-top">
                                {{ $products->links('livewire.custom-pagination') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Import Modal -->
    <div class="modal fade" id="importModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-upload"></i> Import Prices
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                @if($showImportPreview)
                    <!-- Import Preview -->
                    <div class="modal-body">
                        <div class="alert alert-info mb-3">
                            <i class="bi bi-info-circle"></i> Review the data below before applying
                        </div>

                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-sm table-hover">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th style="width: 30px;">Row</th>
                                        <th>Code</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($importedData as $item)
                                        <tr class="@if($item['status'] === 'error') table-danger @elseif($item['status'] === 'ready') table-success @endif">
                                            <td>{{ $item['row_number'] }}</td>
                                            <td>{{ $item['product_code'] }}</td>
                                            <td>
                                                @if($item['status'] === 'error')
                                                    <span class="badge bg-danger">
                                                        <i class="bi bi-x-circle"></i> {{ $item['error'] }}
                                                    </span>
                                                @elseif($item['status'] === 'ready')
                                                    <span class="badge bg-success">
                                                        <i class="bi bi-check-circle"></i> Ready
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if(!empty($importErrors))
                            <div class="alert alert-warning mt-3">
                                <strong>Issues found:</strong>
                                <ul class="mb-0 mt-2">
                                    @foreach($importErrors as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>

                    <div class="modal-footer position-sticky bottom-0 bg-white" style="z-index: 2;">
                        <button type="button" class="btn btn-secondary" wire:click="cancelImport" data-bs-dismiss="modal">
                            Cancel
                        </button>
                        <button type="button" class="btn btn-success" wire:click="applyImportedPrices">
                            <i class="bi bi-check-circle"></i> Apply Prices
                        </button>
                    </div>
                @else
                    <!-- File Upload Form -->
                    <form wire:submit.prevent="handleFileUpload">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="uploadedFile" class="form-label">Select CSV or Excel File</label>
                                <input type="file" 
                                       class="form-control @error('uploadedFile') is-invalid @enderror" 
                                       id="uploadedFile"
                                       wire:model="uploadedFile"
                                       accept=".csv,.xlsx,.xls"
                                       required>
                                <small class="form-text text-muted d-block mt-2">
                                    Accepted formats: CSV, XLSX, XLS (Max 5MB)
                                </small>
                                <small class="text-primary d-block mt-1" wire:loading wire:target="uploadedFile">
                                    Uploading file, please wait...
                                </small>
                                @error('uploadedFile')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="alert alert-info" role="alert">
                                <strong><i class="bi bi-info-circle"></i> Required Columns:</strong>
                                <ol class="mb-0 mt-2">
                                    <li>Product Code</li>
                                    <li>Product Name + Variant Value</li>
                                    <li>Wholesale Price</li>
                                    <li>Distributor Price</li>
                                    <li>Retail Price</li>
                                </ol>
                            </div>

                            <div class="alert alert-secondary">
                                <strong><i class="bi bi-lightbulb"></i> Tip:</strong>
                                <p class="mb-0 mt-2">Export current prices first to get the correct format, then update the prices and re-import.</p>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                Cancel
                            </button>
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="uploadedFile,handleFileUpload">
                                <i class="bi bi-upload"></i> Preview Data
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle inline editing
            document.querySelectorAll('.edit-price-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const cell = this.closest('tr').querySelectorAll('.editable-cell')[0];
                    if (cell) {
                        toggleEditMode(cell);
                    }
                });
            });

            // Double-click to edit
            document.querySelectorAll('.editable-cell').forEach(cell => {
                cell.addEventListener('dblclick', function() {
                    toggleEditMode(this);
                });
            });

            function toggleEditMode(cell) {
                const valueSpan = cell.querySelector('.price-value');
                const input = cell.querySelector('input');

                if (input.classList.contains('d-none')) {
                    input.classList.remove('d-none');
                    valueSpan.classList.add('d-none');
                    input.focus();

                    input.addEventListener('blur', function() {
                        savePriceChange(cell);
                    });

                    input.addEventListener('keydown', function(e) {
                        if (e.key === 'Enter') {
                            savePriceChange(cell);
                        } else if (e.key === 'Escape') {
                            cancelEdit(cell);
                        }
                    });
                }
            }

            function savePriceChange(cell) {
                const input = cell.querySelector('input');
                const valueSpan = cell.querySelector('.price-value');
                const priceId = cell.dataset.priceId;
                const field = cell.dataset.field;
                const newValue = parseFloat(input.value);

                if (isNaN(newValue)) {
                    alert('Please enter a valid number');
                    input.focus();
                    return;
                }

                // Call Livewire method to update price
                @this.updatePrice(priceId, field, newValue);

                valueSpan.textContent = newValue.toFixed(2);
                input.classList.add('d-none');
                valueSpan.classList.remove('d-none');
            }

            function cancelEdit(cell) {
                const valueSpan = cell.querySelector('.price-value');
                const input = cell.querySelector('input');

                input.classList.add('d-none');
                valueSpan.classList.remove('d-none');
            }
        });

        const importModalEl = document.getElementById('importModal');
        importModalEl?.addEventListener('hidden.bs.modal', function () {
            @this.cancelImport();
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('padding-right');
            document.querySelectorAll('.modal-backdrop').forEach((el) => el.remove());
        });

        window.addEventListener('close-import-modal', function () {
            const modalEl = document.getElementById('importModal');
            if (!modalEl) {
                return;
            }

            const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modalInstance.hide();

            setTimeout(() => {
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('padding-right');
                document.querySelectorAll('.modal-backdrop').forEach((el) => el.remove());
            }, 200);
        });
    </script>
    @endpush
</div>
