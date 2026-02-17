@extends('admin.layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Header Section -->
    <div class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="py-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Batch Management</h1>
                        <p class="mt-1 text-sm text-gray-500">Manage your product batches efficiently</p>
                    </div>
                    <div class="mt-4 sm:mt-0 flex space-x-3">
                        <button id="refreshCache" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Refresh
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6" id="statsContainer">
            @include('admin.batches.partials.stats-cards', ['stats' => $stats])
        </div>

        <!-- Filters and Search -->
        <div class="bg-white rounded-lg shadow-sm border mb-6">
            <div class="p-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Filters & Search</h3>
            </div>
            <div class="p-4">
                <form id="filtersForm" class="space-y-4">
                    <!-- Search Row -->
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="flex-1">
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                            <div class="relative">
                                <input type="text" id="search" name="search" value="{{ $filters['search'] }}"
                                    placeholder="Search by batch number or product name..."
                                    class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>
                                <div id="searchSpinner" class="absolute inset-y-0 right-0 pr-3 flex items-center hidden">
                                    <svg class="animate-spin h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <div class="sm:w-48">
                            <label for="stockFilter" class="block text-sm font-medium text-gray-700 mb-1">Stock Status</label>
                            <select id="stockFilter" name="stock_filter" class="block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="all" {{ $filters['stock_filter'] == 'all' ? 'selected' : '' }}>All Batches</option>
                                <option value="zero_stock" {{ $filters['stock_filter'] == 'zero_stock' ? 'selected' : '' }}>Zero Stock</option>
                                <option value="low_stock" {{ $filters['stock_filter'] == 'low_stock' ? 'selected' : '' }}>Low Stock (≤10)</option>
                                <option value="in_stock" {{ $filters['stock_filter'] == 'in_stock' ? 'selected' : '' }}>In Stock (>10)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Sort and Display Options -->
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="sm:w-48">
                            <label for="sortBy" class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                            <select id="sortBy" name="sort_by" class="block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="created_at" {{ $filters['sort_by'] == 'created_at' ? 'selected' : '' }}>Date Created</option>
                                <option value="batch_number" {{ $filters['sort_by'] == 'batch_number' ? 'selected' : '' }}>Batch Number</option>
                                <option value="remaining_quantity" {{ $filters['sort_by'] == 'remaining_quantity' ? 'selected' : '' }}>Quantity</option>
                                <option value="dealer_price" {{ $filters['sort_by'] == 'dealer_price' ? 'selected' : '' }}>Dealer Price</option>
                                <option value="trade_price" {{ $filters['sort_by'] == 'trade_price' ? 'selected' : '' }}>Trade Price</option>
                                <option value="batch_date" {{ $filters['sort_by'] == 'batch_date' ? 'selected' : '' }}>Batch Date</option>
                            </select>
                        </div>
                        <div class="sm:w-32">
                            <label for="sortOrder" class="block text-sm font-medium text-gray-700 mb-1">Order</label>
                            <select id="sortOrder" name="sort_order" class="block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="desc" {{ $filters['sort_order'] == 'desc' ? 'selected' : '' }}>Descending</option>
                                <option value="asc" {{ $filters['sort_order'] == 'asc' ? 'selected' : '' }}>Ascending</option>
                            </select>
                        </div>
                        <div class="sm:w-32">
                            <label for="perPage" class="block text-sm font-medium text-gray-700 mb-1">Per Page</label>
                            <select id="perPage" name="per_page" class="block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="15" {{ $filters['per_page'] == 15 ? 'selected' : '' }}>15</option>
                                <option value="25" {{ $filters['per_page'] == 25 ? 'selected' : '' }}>25</option>
                                <option value="50" {{ $filters['per_page'] == 50 ? 'selected' : '' }}>50</option>
                                <option value="100" {{ $filters['per_page'] == 100 ? 'selected' : '' }}>100</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button type="button" id="clearFilters" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Clear Filters
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Bulk Actions -->
        <div id="bulkActions" class="bg-white rounded-lg shadow-sm border mb-6 hidden">
            <div class="p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center space-x-4">
                    <div class="flex items-center">
                        <input type="checkbox" id="selectAllCheckbox"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="selectAllCheckbox" class="ml-2 text-sm font-medium text-gray-700">
                            Select All
                        </label>
                    </div>
                    <span id="selectedCount" class="text-sm font-medium text-gray-900">0 selected</span>
                </div>
                <div class="mt-3 sm:mt-0 flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3">
                    <button id="bulkDeleteBtn" class="inline-flex items-center justify-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-red-600 hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed w-full sm:w-auto">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Delete Selected
                    </button>
                </div>
            </div>
        </div>

        <!-- Loading Overlay -->
        <div id="loadingOverlay" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3 text-center">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100">
                        <svg class="animate-spin h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mt-2">Loading...</h3>
                    <div class="mt-2 px-7 py-3">
                        <p class="text-sm text-gray-500">Please wait while we fetch the data.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Batches List -->
        <div class="bg-white rounded-lg shadow-sm border">
            <div id="batchesContainer">
                @include('admin.batches.partials.batches-list', ['batches' => $batches, 'stats' => $stats])
            </div>
        </div>
    </div>
</div>

<!-- Bulk Delete Confirmation Modal -->
<div id="bulkDeleteModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 mt-2">Confirm Bulk Delete</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500">
                    Are you sure you want to delete <span id="deleteCount" class="font-semibold">0</span> selected batches?
                    This action cannot be undone. Only zero-stock batches will be deleted.
                </p>
            </div>
            <div class="items-center px-4 py-3">
                <button id="confirmBulkDelete" class="px-4 py-2 bg-red-500 text-white text-base font-medium rounded-md w-24 mr-2 hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-300">
                    Delete
                </button>
                <button id="cancelBulkDelete" class="px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md w-24 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-300">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        let searchTimeout;
        let selectedBatches = new Set();

        // Elements
        const searchInput = document.getElementById('search');
        const searchSpinner = document.getElementById('searchSpinner');
        const stockFilter = document.getElementById('stockFilter');
        const sortBy = document.getElementById('sortBy');
        const sortOrder = document.getElementById('sortOrder');
        const perPage = document.getElementById('perPage');
        const clearFiltersBtn = document.getElementById('clearFilters');
        const refreshCacheBtn = document.getElementById('refreshCache');
        const bulkActions = document.getElementById('bulkActions');
        const selectedCount = document.getElementById('selectedCount');
        const selectAllCheckbox = document.getElementById('selectAllCheckbox');
        const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
        const bulkDeleteModal = document.getElementById('bulkDeleteModal');
        const confirmBulkDelete = document.getElementById('confirmBulkDelete');
        const cancelBulkDelete = document.getElementById('cancelBulkDelete');
        const deleteCount = document.getElementById('deleteCount');
        const loadingOverlay = document.getElementById('loadingOverlay');
        const batchesContainer = document.getElementById('batchesContainer');
        const statsContainer = document.getElementById('statsContainer');

        // Show/hide loading
        function showLoading() {
            loadingOverlay.classList.remove('hidden');
        }

        function hideLoading() {
            loadingOverlay.classList.add('hidden');
        }

        // Show search spinner
        function showSearchSpinner() {
            searchSpinner.classList.remove('hidden');
        }

        function hideSearchSpinner() {
            searchSpinner.classList.add('hidden');
        }

        // Update URL without page reload
        function updateURL(params) {
            const url = new URL(window.location);
            Object.keys(params).forEach(key => {
                if (params[key]) {
                    url.searchParams.set(key, params[key]);
                } else {
                    url.searchParams.delete(key);
                }
            });
            window.history.pushState({}, '', url);
        }

        // Get current filters
        function getCurrentFilters() {
            return {
                search: searchInput.value,
                stock_filter: stockFilter.value,
                sort_by: sortBy.value,
                sort_order: sortOrder.value,
                per_page: perPage.value,
                page: 1 // Reset to first page on filter change
            };
        }

        // Load batches via AJAX
        function loadBatches(showSpinner = false) {
            if (showSpinner) {
                showSearchSpinner();
            } else {
                showLoading();
            }

            const filters = getCurrentFilters();
            updateURL(filters);

            fetch(`{{ route('admin.batches.index') }}?${new URLSearchParams(filters)}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        batchesContainer.innerHTML = data.html;
                        statsContainer.innerHTML = data.stats ? createStatsHTML(data.stats) : statsContainer.innerHTML;

                        // Reinitialize batch checkboxes
                        initializeBatchCheckboxes();

                        // Update selected batches display
                        updateSelectedBatches();
                    }
                })
                .catch(error => {
                    console.error('Error loading batches:', error);
                    showNotification('Error loading batches. Please try again.', 'error');
                })
                .finally(() => {
                    hideSearchSpinner();
                    hideLoading();
                });
        }

        // Create stats HTML
        function createStatsHTML(stats) {
            return `
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Batches</dt>
                                <dd class="text-lg font-medium text-gray-900">${stats.total_batches}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-red-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Zero Stock</dt>
                                <dd class="text-lg font-medium text-gray-900">${stats.zero_stock_batches}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Low Stock</dt>
                                <dd class="text-lg font-medium text-gray-900">${stats.low_stock_batches}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">In Stock</dt>
                                <dd class="text-lg font-medium text-gray-900">${stats.in_stock_batches}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        }

        // Get all batch checkboxes
        function getAllBatchCheckboxes() {
            // Try multiple possible selectors for batch checkboxes
            const possibleSelectors = [
                '.batch-checkbox',
                'input[type="checkbox"][name*="batch"]',
                'input[type="checkbox"][data-batch-id]',
                'input[type="checkbox"][value][data-batch]',
                '.batch-select',
                'input[name="selected_batches[]"]',
                'input[type="checkbox"][data-id]'
            ];

            let checkboxes = [];

            // Find checkboxes using any of the possible selectors
            for (const selector of possibleSelectors) {
                checkboxes = batchesContainer.querySelectorAll(selector);
                if (checkboxes.length > 0) {
                    console.log(`Found ${checkboxes.length} checkboxes using selector: ${selector}`);
                    break;
                }
            }

            // If no checkboxes found with standard selectors, find all checkboxes in the container
            if (checkboxes.length === 0) {
                const allCheckboxes = batchesContainer.querySelectorAll('input[type="checkbox"]');
                // Filter out any checkboxes that might be for other purposes (like select all)
                checkboxes = Array.from(allCheckboxes).filter(checkbox => {
                    return checkbox.value && checkbox.value !== 'on' && checkbox.value !== '' && checkbox.id !== 'selectAllCheckbox';
                });
                console.log(`Found ${checkboxes.length} checkboxes by filtering all checkboxes`);
            }

            return checkboxes;
        }

        // Initialize batch checkboxes
        function initializeBatchCheckboxes() {
            const checkboxes = getAllBatchCheckboxes();

            // Add event listeners to found checkboxes
            checkboxes.forEach((checkbox, index) => {
                // Remove existing event listeners to prevent duplicates
                checkbox.removeEventListener('change', handleCheckboxChange);

                // Add new event listener
                checkbox.addEventListener('change', handleCheckboxChange);

                // Ensure checkbox has a value (batch ID)
                if (!checkbox.value && checkbox.dataset.batchId) {
                    checkbox.value = checkbox.dataset.batchId;
                }

                console.log(`Initialized checkbox ${index + 1}:`, {
                    value: checkbox.value,
                    checked: checkbox.checked,
                    dataset: checkbox.dataset
                });
            });

            // Update the count immediately
            updateSelectedBatches();
            updateSelectAllCheckbox();
        }

        // Handle checkbox change events
        function handleCheckboxChange(event) {
            const checkbox = event.target;
            const batchId = checkbox.value || checkbox.dataset.batchId || checkbox.dataset.id;

            console.log('Checkbox changed:', {
                batchId: batchId,
                checked: checkbox.checked,
                dataset: checkbox.dataset
            });

            if (!batchId) {
                console.warn('No batch ID found for checkbox:', checkbox);
                return;
            }

            if (checkbox.checked) {
                selectedBatches.add(batchId);
            } else {
                selectedBatches.delete(batchId);
            }

            updateSelectedBatches();
            updateSelectAllCheckbox();
        }

        // Update select all checkbox state
        function updateSelectAllCheckbox() {
            const allCheckboxes = getAllBatchCheckboxes();
            const checkedCheckboxes = Array.from(allCheckboxes).filter(cb => cb.checked);

            if (allCheckboxes.length === 0) {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
            } else if (checkedCheckboxes.length === allCheckboxes.length) {
                selectAllCheckbox.checked = true;
                selectAllCheckbox.indeterminate = false;
            } else if (checkedCheckboxes.length > 0) {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = true;
            } else {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
            }
        }

        // Update selected batches display
        function updateSelectedBatches() {
            const count = selectedBatches.size;
            selectedCount.textContent = `${count} selected`;

            console.log('Selected batches updated:', {
                count: count,
                selectedBatches: Array.from(selectedBatches)
            });

            if (count > 0) {
                bulkActions.classList.remove('hidden');
                bulkDeleteBtn.disabled = false;
            } else {
                bulkActions.classList.add('hidden');
                bulkDeleteBtn.disabled = true;
            }
        }

        // Show notification
        function showNotification(message, type = 'success') {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 p-4 rounded-md shadow-lg ${type === 'success' ? 'bg-green-500' : 'bg-red-500'} text-white max-w-sm`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <span class="flex-1">${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-2 text-white hover:text-gray-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            `;

            document.body.appendChild(notification);

            // Remove after 5 seconds
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 5000);
        }

        // Event Listeners

        // Search with debounce
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                loadBatches(true);
            }, 500);
        });

        // Filter changes
        [stockFilter, sortBy, sortOrder, perPage].forEach(element => {
            element.addEventListener('change', () => loadBatches());
        });

        // Clear filters
        clearFiltersBtn.addEventListener('click', function() {
            searchInput.value = '';
            stockFilter.value = 'all';
            sortBy.value = 'created_at';
            sortOrder.value = 'desc';
            perPage.value = '15';
            selectedBatches.clear();
            loadBatches();
        });

        // Refresh cache
        refreshCacheBtn.addEventListener('click', function() {
            showLoading();
            fetch('{{ route("admin.batches.refresh-cache") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message);
                        loadBatches();
                    }
                })
                .catch(error => {
                    console.error('Error refreshing cache:', error);
                    showNotification('Error refreshing cache. Please try again.', 'error');
                })
                .finally(() => {
                    hideLoading();
                });
        });

        // Select all checkbox functionality
        selectAllCheckbox.addEventListener('change', function() {
            const allCheckboxes = getAllBatchCheckboxes();
            const shouldCheck = this.checked;

            console.log(`Select all ${shouldCheck ? 'checked' : 'unchecked'}, affecting ${allCheckboxes.length} checkboxes`);

            let changedCount = 0;
            allCheckboxes.forEach(checkbox => {
                if (checkbox.checked !== shouldCheck) {
                    checkbox.checked = shouldCheck;
                    const batchId = checkbox.value || checkbox.dataset.batchId || checkbox.dataset.id;

                    if (batchId) {
                        if (shouldCheck) {
                            selectedBatches.add(batchId);
                        } else {
                            selectedBatches.delete(batchId);
                        }
                        changedCount++;
                    }
                }
            });

            updateSelectedBatches();

            if (changedCount > 0) {
                const action = shouldCheck ? 'Selected' : 'Deselected';
                showNotification(`${action} ${changedCount} batches.`);
            }
        });

        // Bulk delete
        bulkDeleteBtn.addEventListener('click', function() {
            deleteCount.textContent = selectedBatches.size;
            bulkDeleteModal.classList.remove('hidden');
        });

        // Confirm bulk delete
        confirmBulkDelete.addEventListener('click', function() {
            const batchIds = Array.from(selectedBatches);

            showLoading();
            fetch('{{ route("admin.batches.bulk-delete") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        batch_ids: batchIds
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message);
                        selectedBatches.clear();
                        loadBatches();
                    } else {
                        showNotification(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error deleting batches:', error);
                    showNotification('Error deleting batches. Please try again.', 'error');
                })
                .finally(() => {
                    hideLoading();
                    bulkDeleteModal.classList.add('hidden');
                });
        });

        // Cancel bulk delete
        cancelBulkDelete.addEventListener('click', function() {
            bulkDeleteModal.classList.add('hidden');
        });

        // Close modal when clicking outside
        bulkDeleteModal.addEventListener('click', function(e) {
            if (e.target === bulkDeleteModal) {
                bulkDeleteModal.classList.add('hidden');
            }
        });

        // Handle pagination clicks
        document.addEventListener('click', function(e) {
            if (e.target.matches('.pagination a')) {
                e.preventDefault();
                const url = new URL(e.target.href);
                const page = url.searchParams.get('page');

                const filters = getCurrentFilters();
                filters.page = page;

                updateURL(filters);
                loadBatches();
            }
        });

        // Handle escape key to close modals
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                if (!bulkDeleteModal.classList.contains('hidden')) {
                    bulkDeleteModal.classList.add('hidden');
                }
            }
        });

        // Initialize on page load
        console.log('Initializing batch checkboxes on page load');
        initializeBatchCheckboxes();

        // Debug: Log initial state
        console.log('Initial selected batches:', Array.from(selectedBatches));
        console.log('Bulk actions element:', bulkActions);
        console.log('Selected count element:', selectedCount);

        // Re-initialize checkboxes when new content is loaded via AJAX
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList' && mutation.target === batchesContainer) {
                    console.log('Batches container content changed, reinitializing checkboxes');
                    setTimeout(() => {
                        initializeBatchCheckboxes();
                    }, 100);
                }
            });
        });

        observer.observe(batchesContainer, {
            childList: true,
            subtree: true
        });
    });
</script>
@endpush