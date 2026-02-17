@extends('admin.layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <!-- Header Section -->
        <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
            <h2 class="text-2xl font-semibold text-gray-800 flex items-center">
                <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Invoice Printing Center
            </h2>
        </div>

        <!-- Search and Filter Section -->
        <div class="p-4 bg-gray-50 border-b">
            <form id="filterForm" action="{{ route('admin.invoices.index') }}" method="GET" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                        <div class="relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <input type="date" id="start_date" name="start_date" value="{{ $filters['start_date'] ?? now()->format('Y-m-d') }}"
                                class="pl-10 w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 transition duration-200">
                        </div>
                    </div>

                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                        <div class="relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <input type="date" id="end_date" name="end_date" value="{{ $filters['end_date'] ?? now()->format('Y-m-d') }}"
                                class="pl-10 w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 transition duration-200">
                        </div>
                    </div>

                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <div class="relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input type="text" id="search" name="search" value="{{ $filters['search'] ?? '' }}"
                                placeholder="Search by invoice # or customer"
                                class="pl-10 w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 transition duration-200">
                        </div>
                    </div>

                    <div>
                        <label for="entry_type" class="block text-sm font-medium text-gray-700 mb-1">Transaction Type</label>
                        <div class="relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </div>
                            <select id="entry_type" name="entry_type"
                                class="pl-10 w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 transition duration-200">
                                <option value="">All Types</option>
                                <option value="sale" {{ isset($filters['entry_type']) && $filters['entry_type'] == 'sale' ? 'selected' : '' }}>Sales</option>
                                <option value="purchase" {{ isset($filters['entry_type']) && $filters['entry_type'] == 'purchase' ? 'selected' : '' }}>Purchases</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-2">
                    <button type="submit" class="inline-flex justify-center items-center px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg hover:from-blue-600 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200 transform hover:scale-105">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        Filter
                    </button>

                    <a href="{{ route('admin.invoices.index', ['reset' => true]) }}"
                        class="inline-flex justify-center items-center px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition duration-200 transform hover:scale-105">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Batch Print Form -->
        <form action="{{ route('admin.invoices.batch-print') }}" method="POST" id="batchPrintForm">
            @csrf
            <div class="p-4 flex flex-col sm:flex-row justify-between items-center bg-gradient-to-r from-green-50 to-green-100 border-b">
                <div class="mb-2 sm:mb-0">
                    <h3 class="text-lg font-medium text-gray-800 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Select invoices to print
                    </h3>
                </div>
                <div>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg hover:from-green-600 hover:to-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-200 transform hover:scale-105">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Print Selected
                    </button>
                </div>
            </div>

            <!-- Mobile Invoice Cards -->
            <!-- Mobile Invoice Cards -->
            <div class="md:hidden">
                @if(isset($transactions) && count($transactions) > 0)
                <!-- Add Select All option for mobile -->
                <div class="border-b border-gray-200 p-4 bg-gray-50">
                    <div class="flex items-center">
                        <input type="checkbox" id="mobile-select-all" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-3 h-5 w-5">
                        <label for="mobile-select-all" class="text-sm font-medium text-gray-700">Select All Invoices</label>
                    </div>
                </div>
                @foreach($transactions as $transaction)
                <div class="border-b border-gray-200 p-4">
                    <div class="flex items-center mb-2">
                        <input type="checkbox" name="invoice_ids[]" value="{{ $transaction->id }}" class="invoice-checkbox mobile-invoice-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-3 h-5 w-5">
                        <div>
                            <div class="text-sm font-medium text-gray-900">
                                @php
                                $customerName = 'Walk-in Customer';
                                if(isset($transaction->ledger_id)) {
                                $ledger = \App\Models\Ledger::find($transaction->ledger_id);
                                if($ledger) {
                                $customerName = $ledger->name;
                                }
                                }
                                @endphp
                                {{ $customerName }}
                            </div>
                            <div class="text-xs text-gray-500">
                                {{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d F') }}
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-between items-center">
                        <div class="text-sm font-semibold text-gray-900">
                            ৳{{ number_format($transaction->grand_total ?? $transaction->amount, 2) }}
                        </div>
                        <a href="{{ route('admin.invoices.print', $transaction->id) }}" target="_blank"
                            class="inline-flex items-center px-3 py-1.5 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition duration-200">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            Print
                        </a>
                    </div>
                </div>
                @endforeach
                @else
                <div class="p-6 text-center text-gray-500">
                    <svg class="w-12 h-12 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p>No invoices found</p>
                </div>
                @endif
            </div>


            <!-- Desktop Invoices Table -->
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <div class="flex items-center">
                                    <input type="checkbox" id="select-all" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 h-4 w-4">
                                    <label for="select-all" class="ml-2 text-xs font-medium text-gray-500 uppercase">Select All</label>
                                </div>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Customer
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Total Amount
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody id="invoices-table-body" class="bg-white divide-y divide-gray-200">
                        @if(isset($transactions) && count($transactions) > 0)
                        @foreach($transactions as $transaction)
                        <tr class="hover:bg-gray-50 transition duration-150">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="checkbox" name="invoice_ids[]" value="{{ $transaction->id }}" class="invoice-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500 h-4 w-4">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d F') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                $customerName = 'Walk-in Customer';
                                if(isset($transaction->ledger_id)) {
                                $ledger = \App\Models\Ledger::find($transaction->ledger_id);
                                if($ledger) {
                                $customerName = $ledger->name;
                                }
                                }
                                @endphp
                                <div class="text-sm font-medium text-gray-900">{{ $customerName }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-semibold text-gray-900">৳{{ number_format($transaction->grand_total ?? $transaction->amount, 2) }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('admin.invoices.print', $transaction->id) }}" target="_blank"
                                    class="inline-flex items-center px-3 py-1.5 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition duration-200">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                    </svg>
                                    Print
                                </a>
                            </td>
                        </tr>
                        @endforeach
                        @else
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                                <svg class="w-12 h-12 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p>No invoices found</p>
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <!-- Load More Button -->
            @if(isset($hasMore) && $hasMore)
            <div class="px-6 py-4 text-center">
                <button type="button" id="load-more" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600" data-page="{{ $currentPage + 1 }}">
                    Load More
                </button>
            </div>
            @endif
        </form>
    </div>
</div>
@include('components.print-modal')

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectAllCheckbox = document.getElementById('select-all');
        const mobileSelectAllCheckbox = document.getElementById('mobile-select-all');
        const invoiceCheckboxes = document.querySelectorAll('.invoice-checkbox');
        const mobileInvoiceCheckboxes = document.querySelectorAll('.mobile-invoice-checkbox');
        const loadMoreBtn = document.getElementById('load-more');
        const tableBody = document.getElementById('invoices-table-body');

        // Handle "Select All" checkbox for desktop
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                document.querySelectorAll('.invoice-checkbox').forEach(checkbox => {
                    checkbox.checked = selectAllCheckbox.checked;
                });
            });
        }

        // Handle "Select All" checkbox for mobile
        if (mobileSelectAllCheckbox) {
            mobileSelectAllCheckbox.addEventListener('change', function() {
                document.querySelectorAll('.mobile-invoice-checkbox').forEach(checkbox => {
                    checkbox.checked = mobileSelectAllCheckbox.checked;
                });
            });
        }

        // Update "Select All" checkbox when individual checkboxes change
        function updateSelectAllState() {
            if (selectAllCheckbox) {
                const checkboxes = document.querySelectorAll('.invoice-checkbox');
                if (checkboxes.length === 0) return;

                const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                const noneChecked = Array.from(checkboxes).every(cb => !cb.checked);

                selectAllCheckbox.checked = allChecked;
                selectAllCheckbox.indeterminate = !allChecked && !noneChecked;
            }

            // Update mobile select all state
            if (mobileSelectAllCheckbox) {
                const mobileCheckboxes = document.querySelectorAll('.mobile-invoice-checkbox');
                if (mobileCheckboxes.length === 0) return;

                const allMobileChecked = Array.from(mobileCheckboxes).every(cb => cb.checked);
                const noneMobileChecked = Array.from(mobileCheckboxes).every(cb => !cb.checked);

                mobileSelectAllCheckbox.checked = allMobileChecked;
                mobileSelectAllCheckbox.indeterminate = !allMobileChecked && !noneMobileChecked;
            }
        }

        // Add event listeners to initial checkboxes
        if (invoiceCheckboxes && invoiceCheckboxes.length > 0) {
            invoiceCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateSelectAllState);
            });
        }

        // Load More functionality
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', function() {
                const nextPage = parseInt(this.dataset.page);
                const loadingText = this.innerHTML;

                // Show loading state
                this.innerHTML = 'Loading...';
                this.disabled = true;

                // Get current filter values
                const startDate = document.getElementById('start_date').value || '';
                const endDate = document.getElementById('end_date').value || '';
                const search = document.getElementById('search').value || '';
                const entryType = document.getElementById('entry_type').value || '';

                // Make AJAX request
                fetch(`{{ route('admin.invoices.index') }}?page=${nextPage}&start_date=${startDate}&end_date=${endDate}&search=${search}&entry_type=${entryType}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        // Append new rows to the table
                        if (data.html) {
                            tableBody.insertAdjacentHTML('beforeend', data.html);
                        }

                        // Update page number for next load
                        this.dataset.page = nextPage + 1;

                        // Hide load more button if no more data
                        if (!data.hasMore) {
                            this.style.display = 'none';
                        }

                        // Reset button state
                        this.innerHTML = loadingText;
                        this.disabled = false;

                        // Add event listeners to new checkboxes
                        document.querySelectorAll('.invoice-checkbox:not([data-initialized])').forEach(checkbox => {
                            checkbox.addEventListener('change', updateSelectAllState);
                            checkbox.setAttribute('data-initialized', 'true');
                        });

                        // Add event listeners to new mobile checkboxes
                        document.querySelectorAll('.mobile-invoice-checkbox:not([data-initialized])').forEach(checkbox => {
                            checkbox.addEventListener('change', updateSelectAllState);
                            checkbox.setAttribute('data-initialized', 'true');
                        });
                    })
                    .catch(error => {
                        console.error('Error loading more invoices:', error);
                        this.innerHTML = 'Error loading more. Try again.';
                        this.disabled = false;
                    });
            });
        }
    });
</script>
@endpush
@endsection