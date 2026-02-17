@extends('super-admin.layouts.app')

@section('content')
<div class="bg-white shadow-md rounded-lg overflow-hidden">
    <div class="p-6 bg-white border-b border-gray-200">
        <!-- Success/Warning Messages -->
        @if(session('success'))
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
            {{ session('success') }}
        </div>
        @endif

        @if(session('warning'))
        <div class="mb-4 p-4 bg-yellow-100 border border-yellow-400 text-yellow-700 rounded">
            {{ session('warning') }}
            @if(session('import_errors'))
            <details class="mt-2">
                <summary class="cursor-pointer font-medium">View Import Errors ({{ count(session('import_errors')) }})</summary>
                <div class="mt-2 max-h-40 overflow-y-auto">
                    <ul class="list-disc list-inside text-sm">
                        @foreach(session('import_errors') as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </details>
            @endif
        </div>
        @endif

        @if($errors->any())
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Location Data Management</h2>
                <p class="text-gray-600 mt-1">Manage location hierarchy data for the system</p>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('super-admin.location-data.export', request()->query()) }}"
                    class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition">
                    <i class="fas fa-download mr-2"></i>Export CSV
                </a>
                <button type="button" id="importBtn"
                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                    <i class="fas fa-upload mr-2"></i>Import CSV
                </button>
                <a href="{{ route('super-admin.location-data.create') }}"
                    class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition">
                    <i class="fas fa-plus mr-2"></i>Add Location
                </a>
            </div>
        </div>

        <!-- Search and Filter Form -->
        <form method="GET" action="{{ route('super-admin.location-data.index') }}" class="mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                <div>
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Search locations..."
                        class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <select name="district" id="filter-district"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Districts</option>
                        @foreach($districts as $district)
                        <option value="{{ $district }}" {{ request('district') == $district ? 'selected' : '' }}>
                            {{ $district }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select name="sub_district" id="filter-sub-district"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Sub Districts</option>
                        @foreach($subDistricts as $subDistrict)
                        <option value="{{ $subDistrict }}" {{ request('sub_district') == $subDistrict ? 'selected' : '' }}>
                            {{ $subDistrict }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 transition">
                        <i class="fas fa-search mr-1"></i>Filter
                    </button>
                    @if(request()->hasAny(['search', 'district', 'sub_district']))
                    <a href="{{ route('super-admin.location-data.index') }}"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition">
                        Clear
                    </a>
                    @endif
                </div>
            </div>
        </form>

        <!-- Results Summary -->
        <div class="mb-4 text-sm text-gray-600">
            Showing {{ $locations->firstItem() ?? 0 }} to {{ $locations->lastItem() ?? 0 }} of {{ $locations->total() }} locations
        </div>

        <!-- Locations Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            District
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Sub District
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Village
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Created
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($locations as $location)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $location->district }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $location->sub_district }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $location->village }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $location->created_at->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-3">
                                <!-- View Icon with background -->
                                <a href="{{ route('super-admin.location-data.show', $location) }}"
                                    class="inline-flex items-center justify-center w-8 h-8 text-blue-600 bg-blue-100 rounded-full hover:bg-blue-200 hover:text-blue-800 transition-colors"
                                    title="View Location Details">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </a>

                                <!-- Edit Icon with background -->
                                <a href="{{ route('super-admin.location-data.edit', $location) }}"
                                    class="inline-flex items-center justify-center w-8 h-8 text-indigo-600 bg-indigo-100 rounded-full hover:bg-indigo-200 hover:text-indigo-800 transition-colors"
                                    title="Edit Location">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>

                                <!-- Delete Icon with background -->
                                <form action="{{ route('super-admin.location-data.destroy', $location) }}"
                                    method="POST" class="inline-block"
                                    onsubmit="return confirm('⚠️ Delete Location?\n\nThis will permanently delete:\n• District: {{ $location->district }}\n• Sub District: {{ $location->sub_district }}\n• Village: {{ $location->village }}\n\nThis action cannot be undone!')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="inline-flex items-center justify-center w-8 h-8 text-red-600 bg-red-100 rounded-full hover:bg-red-200 hover:text-red-800 transition-colors"
                                        title="Delete Location">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-8 px-6 text-sm text-center text-gray-500">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-map-marker-alt text-4xl text-gray-300 mb-4"></i>
                                <p class="text-lg font-medium text-gray-400 mb-2">No locations found</p>
                                <p class="text-gray-400 mb-4">Get started by adding your first location</p>
                                <a href="{{ route('super-admin.location-data.create') }}"
                                    class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition">
                                    Add Location
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($locations->hasPages())
        <div class="mt-6">
            {{ $locations->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Import Modal -->
<div id="importModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-lg w-full">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Import Location Data</h3>
                    <button id="closeModalBtn" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <!-- Import Instructions -->
                <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <h4 class="text-sm font-medium text-blue-800 mb-2">Import Instructions:</h4>
                    <ul class="text-xs text-blue-700 space-y-1">
                        <li>• CSV file should have 3 columns: District, Sub District, Village</li>
                        <li>• First row should contain column headers</li>
                        <li>• All fields are required</li>
                        <li>• Duplicate locations will be skipped</li>
                        <li>• Maximum file size: 2MB</li>
                    </ul>
                </div>

                <!-- Download Template -->
                <div class="mb-4">
                    <a href="{{ route('super-admin.location-data.download-template') }}"
                        class="inline-flex items-center px-3 py-2 bg-gray-100 text-gray-700 text-sm rounded hover:bg-gray-200 transition">
                        <i class="fas fa-download mr-2"></i>Download Template
                    </a>
                    <p class="text-xs text-gray-500 mt-1">Download a sample CSV file with the correct format</p>
                </div>

                <form action="{{ route('super-admin.location-data.bulk-import') }}" method="POST" enctype="multipart/form-data" id="importForm">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Select CSV File <span class="text-red-500">*</span>
                        </label>
                        <input type="file" name="import_file" accept=".csv,.xlsx,.xls" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="text-xs text-gray-500 mt-1">
                            Supported formats: CSV, Excel (.xlsx, .xls)
                        </p>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button type="button" id="cancelBtn"
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition">
                            Cancel
                        </button>
                        <button type="submit" id="importSubmitBtn"
                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                            <span id="importBtnText">Import</span>
                            <i id="importSpinner" class="fas fa-spinner fa-spin ml-2 hidden"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript moved to bottom of the page -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded, initializing modal functions...');

        // Import modal functions
        const importModal = document.getElementById('importModal');
        const importBtn = document.getElementById('importBtn');
        const closeModalBtn = document.getElementById('closeModalBtn');
        const cancelBtn = document.getElementById('cancelBtn');
        const importForm = document.getElementById('importForm');
        const importSubmitBtn = document.getElementById('importSubmitBtn');
        const importBtnText = document.getElementById('importBtnText');
        const importSpinner = document.getElementById('importSpinner');

        // Check if elements exist
        if (!importModal || !importBtn) {
            console.error('Modal elements not found');
            return;
        }

        function openImportModal() {
            console.log('Opening import modal...');
            importModal.classList.remove('hidden');
        }

        function closeImportModal() {
            console.log('Closing import modal...');
            importModal.classList.add('hidden');
            // Reset form
            if (importForm) {
                importForm.reset();
            }
            // Reset button state
            // Reset button state
            if (importSubmitBtn) {
                importSubmitBtn.disabled = false;
                if (importBtnText) importBtnText.textContent = 'Import';
                if (importSpinner) importSpinner.classList.add('hidden');
            }
        }

        // Event listeners
        importBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Import button clicked');
            openImportModal();
        });

        if (closeModalBtn) {
            closeModalBtn.addEventListener('click', function(e) {
                e.preventDefault();
                closeImportModal();
            });
        }

        if (cancelBtn) {
            cancelBtn.addEventListener('click', function(e) {
                e.preventDefault();
                closeImportModal();
            });
        }

        // Close modal on outside click
        importModal.addEventListener('click', function(e) {
            if (e.target === importModal) {
                closeImportModal();
            }
        });

        // Handle form submission
        if (importForm) {
            importForm.addEventListener('submit', function(e) {
                console.log('Form submitting...');
                // Show loading state
                if (importSubmitBtn) {
                    importSubmitBtn.disabled = true;
                    if (importBtnText) importBtnText.textContent = 'Importing...';
                    if (importSpinner) importSpinner.classList.remove('hidden');
                }
            });
        }

        // Location hierarchy filtering
        const filterDistrict = document.getElementById('filter-district');
        const filterSubDistrict = document.getElementById('filter-sub-district');

        if (filterDistrict) {
            filterDistrict.addEventListener('change', function() {
                const district = this.value;

                // Clear dependent dropdown
                if (filterSubDistrict) {
                    filterSubDistrict.innerHTML = '<option value="">All Sub Districts</option>';
                }

                if (district) {
                    fetch(`{{ route('super-admin.location-data.get-sub-districts') }}?district=${district}`)
                        .then(response => response.json())
                        .then(data => {
                            if (filterSubDistrict) {
                                data.forEach(subDistrict => {
                                    const option = new Option(subDistrict, subDistrict);
                                    filterSubDistrict.add(option);
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching sub-districts:', error);
                        });
                }
            });
        }

        // Test button click
        console.log('Import button element:', importBtn);
        console.log('Modal element:', importModal);
    });

    // Fallback function for testing
    function testModal() {
        console.log('Test function called');
        const modal = document.getElementById('importModal');
        if (modal) {
            modal.classList.remove('hidden');
            console.log('Modal should be visible now');
        } else {
            console.log('Modal not found');
        }
    }

    // Add a simple test button temporarily
    window.openTestModal = function() {
        const modal = document.getElementById('importModal');
        if (modal) {
            modal.classList.remove('hidden');
        }
    };
</script>

@endsection