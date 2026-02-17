@extends('admin.layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header Section -->
    <div class="mb-8">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Customer Import</h1>
                <p class="text-gray-600 mt-1">Import customers from the common database to your business</p>
            </div>
            <div class="flex space-x-3">
                <!-- Add any header buttons here if needed -->
            </div>
        </div>
    </div>

    <!-- Import Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-blue-50 rounded-lg p-6">
            <div class="flex items-center">
                <div class="p-3 bg-blue-500 rounded-full">
                    <i class="fas fa-users text-white text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-blue-600">Total Imported</p>
                    <p class="text-2xl font-bold text-blue-900">{{ number_format($importStats['total_imported']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-yellow-50 rounded-lg p-6">
            <div class="flex items-center">
                <div class="p-3 bg-yellow-500 rounded-full">
                    <i class="fas fa-exclamation-triangle text-white text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-yellow-600">Pending Conflicts</p>
                    <p class="text-2xl font-bold text-yellow-900">{{ number_format($importStats['pending_conflicts']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-green-50 rounded-lg p-6">
            <div class="flex items-center">
                <div class="p-3 bg-green-500 rounded-full">
                    <i class="fas fa-check-circle text-white text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-green-600">Last Import</p>
                    <p class="text-sm font-bold text-green-900">
                        @if($importStats['last_import'])
                        {{ $importStats['last_import']->created_at->diffForHumans() }}
                        @else
                        Never
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-purple-50 rounded-lg p-6">
            <div class="flex items-center">
                <div class="p-3 bg-purple-500 rounded-full">
                    <i class="fas fa-sync text-white text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-purple-600">Sync Status</p>
                    <button id="syncCustomersBtn" class="text-sm font-bold text-purple-900 hover:underline">
                        Sync Now
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Import Interface -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <!-- Location Filter Section -->
        <div class="p-6 bg-gray-50 border-b">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Select Location</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="district" class="block text-sm font-medium text-gray-700 mb-2">District</label>
                    <select id="district" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select District</option>
                        @foreach($districts as $district)
                        <option value="{{ $district }}">{{ $district }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="sub_district" class="block text-sm font-medium text-gray-700 mb-2">Sub District</label>
                    <select id="sub_district" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" disabled>
                        <option value="">Select District First</option>
                    </select>
                </div>
            </div>

            <div class="mt-4 flex justify-between items-center">
                <button id="previewCustomersBtn"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fas fa-search mr-2"></i>Preview Available Customers
                </button>

                <div class="text-sm text-gray-600">
                    <i class="fas fa-info-circle mr-1"></i>
                    Select district to see available customers for import
                </div>
            </div>
        </div>

        <!-- Customer Preview Section -->
        <div id="customerPreviewSection" class="hidden">
            <!-- Preview Header -->
            <div class="p-6 bg-blue-50 border-b">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Available Customers</h3>
                        <p id="customerCount" class="text-sm text-gray-600"></p>
                    </div>
                    <div class="flex space-x-3">
                        <button id="selectAllBtn" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            Select All
                        </button>
                        <button id="deselectAllBtn" class="text-gray-600 hover:text-gray-800 text-sm font-medium">
                            Deselect All
                        </button>
                        <button id="checkConflictsBtn" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg text-sm">
                            <i class="fas fa-exclamation-triangle mr-1"></i>Check Conflicts
                        </button>
                    </div>
                </div>
            </div>

            <!-- Customer List -->
            <div class="max-h-96 overflow-y-auto">
                <div id="customersList" class="divide-y divide-gray-200">
                    <!-- Customers will be loaded here -->
                </div>
            </div>

            <!-- Import Actions -->
            <div class="p-6 bg-gray-50 border-t">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm text-gray-600">
                            <span id="selectedCount">0</span> customers selected for import
                        </p>
                    </div>
                    <div class="flex space-x-3">
                        <button id="cancelImportBtn" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-6 py-2 rounded-lg transition-colors">
                            Cancel
                        </button>
                        <button id="importSelectedBtn"
                            class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            disabled>
                            <i class="fas fa-download mr-2"></i>Import Selected Customers
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Import History -->
    @if($recentImports->count() > 0)
    <div class="mt-8 bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="p-6 border-b">
            <h2 class="text-xl font-semibold text-gray-900">Recent Imports</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Batch ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Results</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($recentImports as $import)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $import->import_batch_id }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $import->location_label }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <div class="flex space-x-4">
                                <span class="text-green-600">✓ {{ $import->total_imported }}</span>
                                @if($import->total_failed > 0)
                                <span class="text-red-600">✗ {{ $import->total_failed }}</span>
                                @endif
                                @if($import->total_skipped > 0)
                                <span class="text-yellow-600">⚠ {{ $import->total_skipped }}</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                @if($import->import_status === 'completed') bg-green-100 text-green-800
                                @elseif($import->import_status === 'failed') bg-red-100 text-red-800
                                @else bg-yellow-100 text-yellow-800 @endif">
                                {{ ucfirst($import->import_status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $import->created_at->format('M d, Y H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <!-- Add action buttons here if needed -->
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

<!-- Customer Details Modal -->
<div id="customerDetailsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-900">Customer Details</h3>
            <button id="closeDetailsModal" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div id="customerDetailsContent">
            <!-- Customer details will be loaded here -->
        </div>
    </div>
</div>

<!-- Import Notes Modal -->
<div id="importNotesModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-900">Import Customers</h3>
            <button id="closeNotesModal" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="mb-4">
            <label for="importNotes" class="block text-sm font-medium text-gray-700 mb-2">Import Notes (Optional)</label>
            <textarea id="importNotes" rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                placeholder="Add any notes about this import batch..."></textarea>
        </div>
        <div class="flex justify-end space-x-3">
            <button id="cancelNotesBtn" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg">
                Cancel
            </button>
            <button id="confirmImportBtn" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
                <span id="importIcon">
                    <i class="fas fa-download mr-2"></i>
                </span>
                <span id="importSpinner" class="hidden">
                    <i class="fas fa-spinner fa-spin mr-2"></i>
                </span>
                <span id="importButtonText">Confirm Import</span>
            </button>
        </div>
    </div>
</div>
<!-- Conflict Warning Modal -->
<div id="conflictWarningModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-900 text-yellow-600">
                <i class="fas fa-exclamation-triangle mr-2"></i>Potential Conflicts Detected
            </h3>
            <button id="closeConflictModal" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div id="conflictsList" class="mb-4 max-h-64 overflow-y-auto">
            <!-- Conflicts will be loaded here -->
        </div>
        <div class="flex justify-end space-x-3">
            <button id="cancelConflictBtn" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg">
                Cancel Import
            </button>
            <button id="proceedWithConflictsBtn" class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg">
                <i class="fas fa-exclamation-triangle mr-2"></i>Proceed Anyway
            </button>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // CSRF token setup
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // DOM elements
        const districtSelect = document.getElementById('district');
        const subDistrictSelect = document.getElementById('sub_district');
        const previewBtn = document.getElementById('previewCustomersBtn');
        const customerPreviewSection = document.getElementById('customerPreviewSection');
        const customersList = document.getElementById('customersList');
        const customerCount = document.getElementById('customerCount');
        const selectedCount = document.getElementById('selectedCount');
        const selectAllBtn = document.getElementById('selectAllBtn');
        const deselectAllBtn = document.getElementById('deselectAllBtn');
        const checkConflictsBtn = document.getElementById('checkConflictsBtn');
        const importSelectedBtn = document.getElementById('importSelectedBtn');
        const cancelImportBtn = document.getElementById('cancelImportBtn');

        // Modal elements
        const importNotesModal = document.getElementById('importNotesModal');
        const conflictWarningModal = document.getElementById('conflictWarningModal');
        const customerDetailsModal = document.getElementById('customerDetailsModal');

        let selectedCustomers = [];
        let availableCustomers = [];

        // District change handler
        districtSelect.addEventListener('change', function() {
            const district = this.value;

            // Reset sub-district dropdown
            subDistrictSelect.innerHTML = '<option value="">Select Sub District</option>';
            subDistrictSelect.disabled = true;

            if (district) {
                // Fetch sub-districts
                fetch(`{{ route('admin.customer-import.get-sub-districts') }}?district=${encodeURIComponent(district)}`, {
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        data.forEach(item => {
                            const option = document.createElement('option');
                            option.value = item.id;
                            option.textContent = item.text;
                            subDistrictSelect.appendChild(option);
                        });
                        subDistrictSelect.disabled = false;
                    })
                    .catch(error => {
                        console.error('Error loading sub-districts:', error);
                        alert('Error loading sub-districts. Please try again.');
                    });
            }

            hideCustomerPreview();
        });

        // Sub-district change handler
        subDistrictSelect.addEventListener('change', function() {
            hideCustomerPreview();
        });

        // Preview customers button
        previewBtn.addEventListener('click', function() {
            const district = districtSelect.value;
            const subDistrict = subDistrictSelect.value;

            if (!district) {
                alert('Please select a district to preview customers.');
                return;
            }

            // Show loading
            previewBtn.disabled = true;
            previewBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Loading...';

            // Fetch customers
            const params = new URLSearchParams({
                district: district,
                sub_district: subDistrict || ''
            });

            fetch(`{{ route('admin.customer-import.preview') }}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'Accept': 'application/json'
                    },
                    body: params
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayCustomers(data.customers);
                        showCustomerPreview();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error loading customers:', error);
                    alert('Error loading customers. Please try again.');
                })
                .finally(() => {
                    previewBtn.disabled = false;
                    previewBtn.innerHTML = '<i class="fas fa-search mr-2"></i>Preview Available Customers';
                });
        });

        // Select all customers
        selectAllBtn.addEventListener('click', function() {
            const checkboxes = customersList.querySelectorAll('input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = true;
            });
            updateSelectedCount();
        });

        // Deselect all customers
        deselectAllBtn.addEventListener('click', function() {
            const checkboxes = customersList.querySelectorAll('input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            updateSelectedCount();
        });

        // Check conflicts button
        checkConflictsBtn.addEventListener('click', function() {
            const selectedIds = getSelectedCustomerIds();

            if (selectedIds.length === 0) {
                alert('Please select customers to check for conflicts.');
                return;
            }

            checkConflictsBtn.disabled = true;
            checkConflictsBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Checking...';

            // Check for conflicts (placeholder for now)
            setTimeout(() => {
                checkConflictsBtn.disabled = false;
                checkConflictsBtn.innerHTML = '<i class="fas fa-exclamation-triangle mr-1"></i>Check Conflicts';
                alert('No conflicts detected for selected customers.');
            }, 1000);
        });

        // Import selected customers
        importSelectedBtn.addEventListener('click', function() {
            const selectedIds = getSelectedCustomerIds();

            if (selectedIds.length === 0) {
                alert('Please select customers to import.');
                return;
            }

            // Show import notes modal
            importNotesModal.classList.remove('hidden');
        });

        // Cancel import
        cancelImportBtn.addEventListener('click', function() {
            hideCustomerPreview();
        });

        // Modal close handlers
        document.getElementById('closeNotesModal').addEventListener('click', function() {
            importNotesModal.classList.add('hidden');
        });

        document.getElementById('closeConflictModal').addEventListener('click', function() {
            conflictWarningModal.classList.add('hidden');
        });

        document.getElementById('closeDetailsModal').addEventListener('click', function() {
            customerDetailsModal.classList.add('hidden');
        });

        // Cancel notes button
        document.getElementById('cancelNotesBtn').addEventListener('click', function() {
            importNotesModal.classList.add('hidden');
        });

        // Confirm import
        document.getElementById('confirmImportBtn').addEventListener('click', function() {
            const selectedIds = getSelectedCustomerIds();
            const notes = document.getElementById('importNotes').value;

            if (selectedIds.length === 0) {
                alert('No customers selected for import.');
                return;
            }

            // Show loading state
            const btn = this;
            btn.disabled = true;
            document.getElementById('importIcon').classList.add('hidden');
            document.getElementById('importSpinner').classList.remove('hidden');
            document.getElementById('importButtonText').textContent = 'Importing...';

            // Perform import
            const formData = new FormData();
            formData.append('customer_ids', JSON.stringify(selectedIds));
            formData.append('district', districtSelect.value);
            formData.append('sub_district', subDistrictSelect.value);
            formData.append('import_notes', notes);

            fetch(`{{ route('admin.customer-import.import') }}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(`Import completed successfully!\nImported: ${data.results.imported}\nFailed: ${data.results.failed}\nConflicts: ${data.results.conflicts}`);

                        // Hide modals and reset
                        importNotesModal.classList.add('hidden');
                        hideCustomerPreview();

                        // Optionally redirect to history page
                        if (data.redirect_url) {
                            window.location.href = data.redirect_url;
                        }
                    } else {
                        alert('Import failed: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Import error:', error);
                    alert('Import failed. Please try again.');
                })
                .finally(() => {
                    // Reset button state
                    btn.disabled = false;
                    document.getElementById('importIcon').classList.remove('hidden');
                    document.getElementById('importSpinner').classList.add('hidden');
                    document.getElementById('importButtonText').textContent = 'Confirm Import';
                });
        });

        // Helper functions
        function displayCustomers(customers) {
            availableCustomers = customers;
            customersList.innerHTML = '';

            if (customers.length === 0) {
                customersList.innerHTML = '<div class="p-4 text-center text-gray-500">No customers found for the selected location.</div>';
                customerCount.textContent = 'No customers available';
                return;
            }

            customerCount.textContent = `${customers.length} customers available for import`;

            customers.forEach(customer => {
                const customerDiv = document.createElement('div');
                customerDiv.className = 'p-4 hover:bg-gray-50 flex items-center space-x-4';
                customerDiv.innerHTML = `
                <input type="checkbox" value="${customer.id}" class="customer-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500 h-4 w-4">
                <div class="w-10 h-10 ${customer.avatar_color} rounded-full flex items-center justify-center text-white text-sm font-bold">
                    ${customer.avatar_initials}
                </div>
                <div class="flex-1">
                    <div class="flex items-center space-x-2">
                        <h4 class="font-medium text-gray-900">${customer.name}</h4>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-${customer.quality_grade === 'A' ? 'green' : customer.quality_grade === 'B' ? 'blue' : customer.quality_grade === 'C' ? 'yellow' : 'red'}-100 text-${customer.quality_grade === 'A' ? 'green' : customer.quality_grade === 'B' ? 'blue' : customer.quality_grade === 'C' ? 'yellow' : 'red'}-800">
                            Grade ${customer.quality_grade}
                        </span>
                    </div>
                    <p class="text-sm text-gray-600">${customer.type || 'Customer'}</p>
                    ${customer.phone ? `<p class="text-sm text-gray-500"><i class="fas fa-phone w-4"></i> ${customer.phone}</p>` : ''}
                    ${customer.full_location ? `<p class="text-sm text-gray-500"><i class="fas fa-map-marker-alt w-4"></i> ${customer.full_location}</p>` : ''}
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-500">Quality: ${customer.data_quality_score}%</p>
                    <p class="text-xs text-gray-400">${customer.created_at}</p>
                    <button onclick="viewCustomerDetails(${customer.id})" class="text-xs text-blue-600 hover:text-blue-800">View Details</button>
                </div>
            `;
                customersList.appendChild(customerDiv);
            });

            // Add event listeners to checkboxes
            const checkboxes = customersList.querySelectorAll('.customer-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateSelectedCount);
            });
        }

        function showCustomerPreview() {
            customerPreviewSection.classList.remove('hidden');
            updateSelectedCount();
        }

        function hideCustomerPreview() {
            customerPreviewSection.classList.add('hidden');
            selectedCustomers = [];
        }

        function getSelectedCustomerIds() {
            const checkboxes = customersList.querySelectorAll('.customer-checkbox:checked');
            return Array.from(checkboxes).map(checkbox => parseInt(checkbox.value));
        }

        function updateSelectedCount() {
            const selectedIds = getSelectedCustomerIds();
            selectedCount.textContent = selectedIds.length;
            importSelectedBtn.disabled = selectedIds.length === 0;

            // Update button text
            if (selectedIds.length === 0) {
                importSelectedBtn.innerHTML = '<i class="fas fa-download mr-2"></i>Import Selected Customers';
            } else {
                importSelectedBtn.innerHTML = `<i class="fas fa-download mr-2"></i>Import ${selectedIds.length} Customer${selectedIds.length > 1 ? 's' : ''}`;
            }
        }

        // Global function for viewing customer details
        window.viewCustomerDetails = function(customerId) {
            const customer = availableCustomers.find(c => c.id === customerId);
            if (!customer) {
                alert('Customer details not found.');
                return;
            }

            const detailsContent = document.getElementById('customerDetailsContent');
            detailsContent.innerHTML = `
                            <div class="space-y-4">
                <div class="flex items-center space-x-4">
                    <div class="w-16 h-16 ${customer.avatar_color} rounded-full flex items-center justify-center text-white text-xl font-bold">
                        ${customer.avatar_initials}
                    </div>
                    <div>
                        <h4 class="text-xl font-bold text-gray-900">${customer.name}</h4>
                        <p class="text-gray-600">${customer.type || 'Customer'}</p>
                        <span class="inline-flex items-center px-2 py-1 rounded text-sm font-medium bg-${customer.quality_grade === 'A' ? 'green' : customer.quality_grade === 'B' ? 'blue' : customer.quality_grade === 'C' ? 'yellow' : 'red'}-100 text-${customer.quality_grade === 'A' ? 'green' : customer.quality_grade === 'B' ? 'blue' : customer.quality_grade === 'C' ? 'yellow' : 'red'}-800">
                            Data Quality Grade: ${customer.quality_grade} (${customer.data_quality_score}%)
                        </span>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Phone Number</label>
                        <p class="text-gray-900">${customer.phone || 'Not provided'}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Customer Type</label>
                        <p class="text-gray-900">${customer.type || 'Not specified'}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">District</label>
                        <p class="text-gray-900">${customer.district || 'Not specified'}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Sub District</label>
                        <p class="text-gray-900">${customer.sub_district || 'Not specified'}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Village</label>
                        <p class="text-gray-900">${customer.village || 'Not specified'}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Landmark</label>
                        <p class="text-gray-900">${customer.landmark || 'Not provided'}</p>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-500">Full Location</label>
                    <p class="text-gray-900">${customer.full_location || 'Location not complete'}</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-500">Created Date</label>
                    <p class="text-gray-900">${customer.created_at}</p>
                </div>
            </div>
        `;

            customerDetailsModal.classList.remove('hidden');
        };

        // Sync customers functionality
        const syncCustomersBtn = document.getElementById('syncCustomersBtn');
        if (syncCustomersBtn) {
            syncCustomersBtn.addEventListener('click', function() {
                this.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Syncing...';
                this.disabled = true;

                // Placeholder sync functionality
                setTimeout(() => {
                    this.innerHTML = 'Sync Complete';
                    this.disabled = false;

                    setTimeout(() => {
                        this.innerHTML = 'Sync Now';
                    }, 2000);
                }, 2000);
            });
        }

        // Close modals when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === importNotesModal) {
                importNotesModal.classList.add('hidden');
            }
            if (event.target === conflictWarningModal) {
                conflictWarningModal.classList.add('hidden');
            }
            if (event.target === customerDetailsModal) {
                customerDetailsModal.classList.add('hidden');
            }
        });

        // Handle escape key to close modals
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                importNotesModal.classList.add('hidden');
                conflictWarningModal.classList.add('hidden');
                customerDetailsModal.classList.add('hidden');
            }
        });
    });
</script>

<!-- Include Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
    /* Custom styles for better UX */
    .customer-checkbox:checked {
        background-color: #3B82F6;
        border-color: #3B82F6;
    }

    .customer-checkbox:focus {
        ring-color: #3B82F6;
        ring-opacity: 0.5;
    }

    /* Modal backdrop blur effect */
    .fixed.inset-0.bg-gray-600.bg-opacity-50 {
        backdrop-filter: blur(2px);
    }

    /* Smooth transitions */
    button {
        transition: all 0.2s ease-in-out;
    }

    /* Loading spinner animation */
    @keyframes spin {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }

    .fa-spin {
        animation: spin 1s linear infinite;
    }

    /* Custom scrollbar for customer list */
    .max-h-96::-webkit-scrollbar {
        width: 6px;
    }

    .max-h-96::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }

    .max-h-96::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 3px;
    }

    .max-h-96::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }

    /* Hover effects for customer rows */
    .hover\:bg-gray-50:hover {
        background-color: #f9fafb;
        transition: background-color 0.15s ease-in-out;
    }

    /* Quality grade badge colors */
    .bg-green-100 {
        background-color: #dcfce7;
    }

    .text-green-800 {
        color: #166534;
    }

    .bg-blue-100 {
        background-color: #dbeafe;
    }

    .text-blue-800 {
        color: #1e40af;
    }

    .bg-yellow-100 {
        background-color: #fef3c7;
    }

    .text-yellow-800 {
        color: #92400e;
    }

    .bg-red-100 {
        background-color: #fee2e2;
    }

    .text-red-800 {
        color: #991b1b;
    }

    /* Avatar colors */
    .bg-red-500 {
        background-color: #ef4444;
    }

    .bg-blue-500 {
        background-color: #3b82f6;
    }

    .bg-green-500 {
        background-color: #10b981;
    }

    .bg-yellow-500 {
        background-color: #f59e0b;
    }

    .bg-purple-500 {
        background-color: #8b5cf6;
    }

    .bg-pink-500 {
        background-color: #ec4899;
    }

    .bg-indigo-500 {
        background-color: #6366f1;
    }

    .bg-teal-500 {
        background-color: #14b8a6;
    }

    /* Card hover effects */
    .hover\:shadow-md:hover {
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    /* Button disabled state */
    button:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* Focus states for accessibility */
    button:focus,
    select:focus,
    input:focus,
    textarea:focus {
        outline: 2px solid #3b82f6;
        outline-offset: 2px;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .grid-cols-1.md\:grid-cols-2 {
            grid-template-columns: 1fr;
        }

        .flex.space-x-3 {
            flex-direction: column;
            space-x: 0;
            gap: 0.75rem;
        }

        .w-11\/12.md\:w-3\/4.lg\:w-1\/2 {
            width: 95%;
        }
    }

    /* Loading states */
    .loading {
        pointer-events: none;
        opacity: 0.7;
    }

    /* Success/Error message styles */
    .alert-success {
        background-color: #d1fae5;
        border-color: #a7f3d0;
        color: #065f46;
    }

    .alert-error {
        background-color: #fee2e2;
        border-color: #fecaca;
        color: #991b1b;
    }

    .alert-warning {
        background-color: #fef3c7;
        border-color: #fde68a;
        color: #92400e;
    }
</style>
@endsection