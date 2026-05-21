@if ($paginator->hasPages())
<div class="d-flex flex-column flex-sm-row align-items-center justify-content-between gap-3 mt-4">
    <!-- Show Entries Dropdown -->
    <!-- <div class="d-flex align-items-center gap-2">
            <label class="text-sm text-muted fw-medium">Show</label>
            <select wire:model.live="perPage" class="form-select form-select-sm" style="width: auto;">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="200">200</option>
                <option value="500">500</option>
            </select>
            <span class="text-sm text-muted">entries</span>
        </div> -->

    <!-- Pagination Info -->
    <div class="text-sm text-muted">
        Showing <span class="fw-semibold">{{ $paginator->firstItem() }}</span> to
        <span class="fw-semibold">{{ $paginator->lastItem() }}</span> of
        <span class="fw-semibold">{{ $paginator->total() }}</span> entries
    </div>

    <!-- Pagination Navigation -->
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center mb-0">
            {{-- First Page Link --}}
            <li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
                <a class="page-link rounded-circle" href="javascript:void(0)" wire:click="gotoPage(1)" aria-label="First">
                    <span aria-hidden="true"><i class="bi bi-chevron-double-left"></i></span>
                </a>
            </li>

            {{-- Previous Page Link --}}
            <li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
                <a class="page-link rounded-circle" href="javascript:void(0)" wire:click="previousPage" aria-label="Previous">
                    <span aria-hidden="true"><i class="bi bi-chevron-left"></i></span>
                </a>
            </li>

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
            {{-- "Three Dots" Separator --}}
            @if (is_string($element))
            <li class="page-item disabled d-none d-sm-block">
                <a class="page-link rounded-circle" href="javascript:void(0)">{{ $element }}</a>
            </li>
            @endif

            {{-- Array Of Links --}}
            @if (is_array($element))
            @foreach ($element as $page => $url)
            <li class="page-item {{ $page == $paginator->currentPage() ? 'active' : '' }} d-none d-sm-block">
                <a class="page-link rounded-circle" href="javascript:void(0)"
                    wire:click="gotoPage({{ $page }})">
                    {{ $page }}
                </a>
            </li>
            @endforeach
            @endif
            @endforeach

            {{-- Mobile Current Page Indicator --}}
            <li class="page-item active d-sm-none">
                <span class="page-link rounded-circle">{{ $paginator->currentPage() }}</span>
            </li>

            {{-- Next Page Link --}}
            <li class="page-item {{ $paginator->hasMorePages() ? '' : 'disabled' }}">
                <a class="page-link rounded-circle" href="javascript:void(0)" wire:click="nextPage" aria-label="Next">
                    <span aria-hidden="true"><i class="bi bi-chevron-right"></i></span>
                </a>
            </li>

            {{-- Last Page Link --}}
            <li class="page-item {{ $paginator->hasMorePages() ? '' : 'disabled' }}">
                <a class="page-link rounded-circle" href="javascript:void(0)" wire:click="gotoPage({{ $paginator->lastPage() }})" aria-label="Last">
                    <span aria-hidden="true"><i class="bi bi-chevron-double-right"></i></span>
                </a>
            </li>
        </ul>
    </nav>
</div>
@endif

@push('styles')
<style>
    /* Custom Circular Pagination Styling */
    .pagination .page-link {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 3px;
        padding: 0;
        border-radius: 50% !important;
        font-weight: 500;
    }

    .pagination .page-item.active .page-link {
        background-color: #3490dc;
        border-color: #3490dc;
    }

    .pagination .page-item.disabled .page-link {
        color: #aaa;
        pointer-events: none;
    }

    .pagination .page-link:hover:not(.disabled) {
        background-color: #f8f9fa;
        border-color: #dee2e6;
        z-index: 2;
    }
</style>
@endpush