@extends('super-admin.layouts.app')

@section('content')
<div class="bg-white shadow-md rounded-lg overflow-hidden">
    <div class="p-6 bg-white border-b border-gray-200">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Default Customers</h2>
                <p class="text-gray-600 mt-1">Manage customer and supplier ledgers</p>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('super-admin.customer-ledgers.create') }}"
                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                    <i class="fas fa-plus mr-2"></i>Add New Customer
                </a>
                <a href="{{ route('super-admin.location-data.index') }}"
                    class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition">
                    <i class="fas fa-map-marker-alt mr-2"></i>Manage Locations
                </a>
                <!-- ADD NEW BUTTONS -->
                <a href="{{ route('super-admin.customer-ledgers.data-quality-report') }}"
                    class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 transition">
                    <i class="fas fa-chart-bar mr-2"></i>Quality Report
                </a>
                <a href="{{ route('super-admin.customer-ledgers.merge-history') }}"
                    class="px-4 py-2 bg-orange-600 text-white rounded hover:bg-orange-700 transition">
                    <i class="fas fa-history mr-2"></i>Merge History
                </a>

                <a href="{{ route('test.duplicate-detection') }}"
                    class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition">
                    <i class="fas fa-search-plus mr-2"></i>Test Duplicates & Merge
                </a>
            </div>
        </div>

        <!-- Enhanced Search and Filter Form -->
        <div class="mb-6 bg-gray-50 p-4 rounded-lg">
            <form action="{{ route('super-admin.customer-ledgers.index') }}" method="GET" class="space-y-4">
                <!-- Search Row -->
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Name, phone, location..."
                            class="w-full px-3 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                        <select name="ledger_type" class="w-full px-3 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm">
                            <option value="">All Types</option>
                            @foreach($ledgerTypes as $type)
                            <option value="{{ $type }}" {{ request('ledger_type') == $type ? 'selected' : '' }}>
                                {{ $type }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <!-- ADD DATA QUALITY FILTER -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data Quality</label>
                        <select name="quality_grade" class="w-full px-3 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm">
                            <option value="">All Grades</option>
                            <option value="A" {{ request('quality_grade') == 'A' ? 'selected' : '' }}>Grade A (90-100%)</option>
                            <option value="B" {{ request('quality_grade') == 'B' ? 'selected' : '' }}>Grade B (80-89%)</option>
                            <option value="C" {{ request('quality_grade') == 'C' ? 'selected' : '' }}>Grade C (70-79%)</option>
                            <option value="D" {{ request('quality_grade') == 'D' ? 'selected' : '' }}>Grade D (60-69%)</option>
                            <option value="F" {{ request('quality_grade') == 'F' ? 'selected' : '' }}>Grade F (<60%)< /option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">District</label>
                        <select name="district" id="filter-district" class="w-full px-3 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm">
                            <option value="">All Districts</option>
                            @foreach($districts as $district)
                            <option value="{{ $district }}" {{ request('district') == $district ? 'selected' : '' }}>
                                {{ $district }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sub District</label>
                        <select name="sub_district" id="filter-sub-district" class="w-full px-3 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm">
                            <option value="">All Sub Districts</option>
                            @if(request('district'))
                            @foreach(\App\Models\LocationData::getSubDistricts(request('district')) as $subDistrict)
                            <option value="{{ $subDistrict }}" {{ request('sub_district') == $subDistrict ? 'selected' : '' }}>
                                {{ $subDistrict }}
                            </option>
                            @endforeach
                            @endif
                        </select>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-between">
                    <div class="flex gap-2">
                        <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 transition text-sm">
                            <i class="fas fa-search mr-2"></i>Filter
                        </button>
                        @if(request()->hasAny(['search', 'ledger_type', 'quality_grade', 'district', 'sub_district', 'village']))
                        <a href="{{ route('super-admin.customer-ledgers.index') }}"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition text-sm">
                            <i class="fas fa-times mr-2"></i>Clear
                        </a>
                        @endif
                        <!-- ADD BULK ACTIONS -->
                        <button type="button" id="bulkUpdateQuality" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition text-sm">
                            <i class="fas fa-sync mr-2"></i>Update All Quality Scores
                        </button>
                    </div>

                    <!-- Results Count -->
                    <div class="text-sm text-gray-600">
                        Showing {{ $customers->firstItem() ?? 0 }} to {{ $customers->lastItem() ?? 0 }}
                        of {{ $customers->total() }} results
                    </div>
                </div>
            </form>
        </div>

        <!-- Enhanced Customers Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Customer Info
                        </th>
                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Type
                        </th>
                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Contact
                        </th>
                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Location
                        </th>
                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            QR Code
                        </th>
                        <!-- ADD DATA QUALITY COLUMN -->
                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Data Quality
                        </th>
                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($customers as $customer)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <!-- Customer Info -->
                        <td class="py-4 px-4">
                            <div class="flex items-start">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                        <span class="text-sm font-medium text-blue-600">
                                            {{ strtoupper(substr($customer->ledger_name, 0, 2)) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $customer->ledger_name }}
                                        <!-- ADD MERGE INDICATOR -->
                                        @if($customer->is_merged)
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                            Merged
                                        </span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        ID: {{ $customer->ledger_id }}
                                        @if($customer->global_customer_uuid)
                                        <br>UUID: {{ substr($customer->global_customer_uuid, 0, 8) }}...
                                        @endif
                                    </div>
                                    @if($customer->landmark)
                                    <div class="text-xs text-gray-600 mt-1">
                                        <i class="fas fa-map-pin mr-1"></i>{{ $customer->landmark }}
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </td>

                        <!-- Type -->
                        <td class="py-4 px-4">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                {{ str_contains($customer->type, 'Customer') || str_contains($customer->type, 'Debtors') ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                                {{ str_contains($customer->type, 'Customer') || str_contains($customer->type, 'Debtors') ? 'Customer' : 'Supplier' }}
                            </span>
                        </td>

                        <!-- Contact -->
                        <td class="py-4 px-4">
                            @if($customer->contact_number)
                            <div class="text-sm text-gray-900">
                                <i class="fas fa-phone mr-1 text-gray-400"></i>
                                {{ $customer->contact_number }}
                            </div>
                            @else
                            <span class="text-xs text-gray-400 italic">No phone</span>
                            @endif
                        </td>

                        <!-- Location -->
                        <td class="py-4 px-4">
                            <div class="text-sm text-gray-900">
                                @php
                                $locationParts = array_filter([
                                $customer->village,
                                $customer->sub_district,
                                $customer->district
                                ]);
                                @endphp

                                @if(count($locationParts) > 0)
                                <div class="space-y-1">
                                    @if($customer->village)
                                    <div class="text-sm font-medium">{{ $customer->village }}</div>
                                    @endif
                                    @if($customer->sub_district || $customer->district)
                                    <div class="text-xs text-gray-600">
                                        {{ implode(', ', array_filter([$customer->sub_district, $customer->district])) }}
                                    </div>
                                    @endif
                                </div>
                                @else
                                @if($customer->location)
                                <div class="text-sm text-gray-600">{{ Str::limit($customer->location, 30) }}</div>
                                @else
                                <span class="text-xs text-gray-400 italic">No location</span>
                                @endif
                                @endif
                            </div>
                        </td>


                        <!-- QR Code -->
                        <td class="py-4 px-4">
                            @if($customer->qr_code)
                            <div class="flex items-center space-x-2">
                                <button type="button"
                                    class="text-blue-600 hover:text-blue-900 transition-colors p-1 rounded hover:bg-blue-50 show-qr-btn"
                                    title="Show QR Code"
                                    data-qr-code="{{ $customer->qr_code }}"
                                    data-customer-name="{{ $customer->ledger_name }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 16h4.01M12 8h4.01M8 12h.01M16 8h.01M8 16h.01m2.99-4h.01m0 0h.01m0 0h.01M8 8h.01"></path>
                                    </svg>
                                </button>
                                <span class="text-xs text-gray-500">Available</span>
                            </div>
                            @else
                            <button type="button"
                                class="text-green-600 hover:text-green-900 transition-colors p-1 rounded hover:bg-green-50 generate-qr-btn"
                                title="Generate QR Code"
                                data-customer-id="{{ $customer->ledger_id }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                            </button>
                            @endif
                        </td>

                        <!-- ADD DATA QUALITY COLUMN -->
                        <td class="py-4 px-4">
                            @php
                            $score = $customer->data_quality_score ?? 0;
                            $grade = $customer->getDataQualityGrade();
                            $gradeColors = [
                            'A' => 'bg-green-100 text-green-800',
                            'B' => 'bg-blue-100 text-blue-800',
                            'C' => 'bg-yellow-100 text-yellow-800',
                            'D' => 'bg-orange-100 text-orange-800',
                            'F' => 'bg-red-100 text-red-800'
                            ];
                            @endphp
                            <div class="flex items-center space-x-2">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $gradeColors[$grade] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ $grade }}
                                </span>
                                <span class="text-xs text-gray-500">{{ number_format($score, 1) }}%</span>
                            </div>
                        </td>

                        <!-- Actions -->
                        <td class="py-4 px-4 text-sm font-medium">
                            <div class="flex items-center space-x-2">
                                <!-- View Details -->
                                <a href="{{ route('super-admin.customer-ledgers.show', $customer) }}"
                                    class="text-indigo-600 hover:text-indigo-900 transition-colors p-1 rounded hover:bg-indigo-50"
                                    title="View Details">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </a>

                                <!-- Edit -->
                                <a href="{{ route('super-admin.customer-ledgers.edit', $customer) }}"
                                    class="text-blue-600 hover:text-blue-900 transition-colors p-1 rounded hover:bg-blue-50"
                                    title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>

                                <!-- Check Duplicates Button -->
                                <button type="button"
                                    class="text-purple-600 hover:text-purple-900 transition-colors p-1 rounded hover:bg-purple-50 check-duplicates-btn"
                                    title="Check Duplicates"
                                    data-customer-id="{{ $customer->ledger_id }}"
                                    data-customer-name="{{ $customer->ledger_name }}"
                                    data-customer-phone="{{ $customer->contact_number }}"
                                    data-customer-district="{{ $customer->district }}"
                                    data-customer-sub-district="{{ $customer->sub_district }}"
                                    data-customer-village="{{ $customer->village }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                </button>

                                <!-- Delete -->
                                @if(!$customer->is_merged)
                                <form action="{{ route('super-admin.customer-ledgers.destroy', $customer) }}"
                                    method="POST"
                                    onsubmit="return confirm('Are you sure you want to delete this customer? This action cannot be undone.');"
                                    class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="text-red-600 hover:text-red-900 transition-colors p-1 rounded hover:bg-red-50"
                                        title="Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </form>
                                @else
                                <span class="text-gray-400 text-xs">Merged</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-12 px-4 text-center">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-users text-gray-300 text-4xl mb-4"></i>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">No customers found</h3>
                                <p class="text-gray-500 mb-4">
                                    @if(request()->hasAny(['search', 'ledger_type', 'quality_grade', 'district', 'sub_district']))
                                    No customers match your current filters.
                                    @else
                                    Get started by creating your first customer.
                                    @endif
                                </p>
                                @if(request()->hasAny(['search', 'ledger_type', 'quality_grade', 'district', 'sub_district']))
                                <a href="{{ route('super-admin.customer-ledgers.index') }}"
                                    class="text-blue-600 hover:text-blue-800">
                                    Clear filters
                                </a>
                                @else
                                <a href="{{ route('super-admin.customer-ledgers.create') }}"
                                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                                    Create First Customer
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Enhanced Pagination -->
        <div class="mt-6 flex items-center justify-between">
            <div class="text-sm text-gray-700">
                @if($customers->total() > 0)
                Showing {{ $customers->firstItem() }} to {{ $customers->lastItem() }} of {{ $customers->total() }} customers
                @endif
            </div>
            <div>
                {{ $customers->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Duplicate Check Modal -->
<div id="duplicateModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Duplicate Check Results</h3>
                <button id="closeDuplicateModal" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div id="duplicateResults" class="space-y-4">
                <!-- Results will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- QR Code Modal -->
<div id="qrModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <h3 class="text-lg font-medium text-gray-900 mb-4" id="qrModalTitle">Customer QR Code</h3>
            <div id="qrCodeContainer" class="mb-4 flex justify-center">
                <!-- QR Code will be inserted here -->
            </div>
            <button id="closeQrModal" class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400">
                Close
            </button>
        </div>
    </div>
</div>


<!-- Filter JavaScript -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const districtFilter = document.getElementById('filter-district');
        const subDistrictFilter = document.getElementById('filter-sub-district');

        // Track if we're currently loading to prevent loops
        let isLoading = false;

        // Handle district filter change
        districtFilter.addEventListener('change', function() {
            if (isLoading) return;

            const district = this.value;
            subDistrictFilter.innerHTML = '<option value="">All Sub Districts</option>';

            if (district) {
                isLoading = true;
                subDistrictFilter.innerHTML = '<option value="">Loading...</option>';
                subDistrictFilter.disabled = true;

                fetch(`{{ route('super-admin.location-data.get-sub-districts') }}?district=${encodeURIComponent(district)}`)
                    .then(response => response.json())
                    .then(data => {
                        subDistrictFilter.innerHTML = '<option value="">All Sub Districts</option>';

                        data.forEach(subDistrict => {
                            const option = document.createElement('option');
                            option.value = subDistrict;
                            option.textContent = subDistrict;

                            if ('{{ request("sub_district") }}' === subDistrict) {
                                option.selected = true;
                            }

                            subDistrictFilter.appendChild(option);
                        });

                        subDistrictFilter.disabled = false;
                        isLoading = false;
                    })
                    .catch(error => {
                        console.error('Error loading sub-districts:', error);
                        subDistrictFilter.innerHTML = '<option value="">Error loading sub-districts</option>';
                        subDistrictFilter.disabled = false;
                        isLoading = false;
                    });
            } else {
                isLoading = false;
            }
        });

        // Bulk update quality scores
        document.getElementById('bulkUpdateQuality').addEventListener('click', function() {
            if (confirm('This will recalculate data quality scores for all customers. This may take a while. Continue?')) {
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Updating...';

                fetch('{{ route("super-admin.customer-ledgers.bulk-update-quality") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(data.message);
                            location.reload();
                        } else {
                            alert('Error updating quality scores');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error updating quality scores');
                    })
                    .finally(() => {
                        this.disabled = false;
                        this.innerHTML = '<i class="fas fa-sync mr-2"></i>Update All Quality Scores';
                    });
            }
        });

        // Duplicate check functionality
        const duplicateModal = document.getElementById('duplicateModal');
        const duplicateResults = document.getElementById('duplicateResults');
        const closeDuplicateModal = document.getElementById('closeDuplicateModal');

        // Handle duplicate check button clicks
        document.querySelectorAll('.check-duplicates-btn').forEach(button => {
            button.addEventListener('click', function() {
                const customerData = {
                    name: this.dataset.customerName,
                    phone: this.dataset.customerPhone,
                    district: this.dataset.customerDistrict,
                    sub_district: this.dataset.customerSubDistrict,
                    village: this.dataset.customerVillage
                };

                // Show modal and loading state
                duplicateModal.classList.remove('hidden');
                duplicateResults.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin mr-2"></i>Checking for duplicates...</div>';

                // Make AJAX request
                fetch('{{ route("super-admin.customer-ledgers.check-duplicates") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(customerData)
                    })
                    .then(response => response.json())
                    .then(duplicates => {
                        displayDuplicateResults(duplicates);
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        duplicateResults.innerHTML = '<div class="text-red-600 text-center py-4">Error checking for duplicates</div>';
                    });
            });
        });

        // Close modal
        closeDuplicateModal.addEventListener('click', function() {
            duplicateModal.classList.add('hidden');
        });

        // Close modal when clicking outside
        duplicateModal.addEventListener('click', function(e) {
            if (e.target === duplicateModal) {
                duplicateModal.classList.add('hidden');
            }
        });

        function displayDuplicateResults(duplicates) {
            if (duplicates.length === 0) {
                duplicateResults.innerHTML = '<div class="text-green-600 text-center py-4"><i class="fas fa-check-circle mr-2"></i>No duplicates found!</div>';
                return;
            }

            let html = `<div class="mb-4"><h4 class="font-medium text-gray-900">Found ${duplicates.length} potential duplicate(s):</h4></div>`;

            duplicates.forEach((duplicate, index) => {
                const riskColors = {
                    'critical': 'border-red-500 bg-red-50',
                    'high': 'border-orange-500 bg-orange-50',
                    'medium': 'border-yellow-500 bg-yellow-50',
                    'low': 'border-blue-500 bg-blue-50'
                };

                const riskClass = riskColors[duplicate.risk_level] || riskColors['low'];

                html += `
                        <div class="border-l-4 ${riskClass} p-4 mb-4 rounded-r-lg">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center mb-2">
                                        <span class="font-medium text-gray-900">Match #${index + 1}</span>
                                        <span class="ml-2 px-2 py-1 text-xs font-medium rounded-full ${
                                            duplicate.type === 'phone' ? 'bg-red-100 text-red-800' :
                                            duplicate.type === 'name' ? 'bg-blue-100 text-blue-800' :
                                            duplicate.type === 'location' ? 'bg-green-100 text-green-800' :
                                            'bg-gray-100 text-gray-800'
                                        }">
                                            ${duplicate.type.toUpperCase()}
                                        </span>
                                        <span class="ml-2 px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">
                                            ${duplicate.similarity}% match
                                        </span>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <div class="font-medium text-gray-700">Customer Details:</div>
                                            <div class="mt-1 space-y-1">
                                                <div><span class="text-gray-500">Name:</span> ${duplicate.customer.ledger_name}</div>
                                                <div><span class="text-gray-500">Phone:</span> ${duplicate.customer.contact_number || 'N/A'}</div>
                                                <div><span class="text-gray-500">ID:</span> ${duplicate.customer.ledger_id}</div>
                                                <div><span class="text-gray-500">Created:</span> ${new Date(duplicate.customer.created_at).toLocaleDateString()}</div>
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <div class="font-medium text-gray-700">Location:</div>
                                            <div class="mt-1 space-y-1">
                                                <div><span class="text-gray-500">District:</span> ${duplicate.customer.district || 'N/A'}</div>
                                                <div><span class="text-gray-500">Sub District:</span> ${duplicate.customer.sub_district || 'N/A'}</div>
                                                <div><span class="text-gray-500">Village:</span> ${duplicate.customer.village || 'N/A'}</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-3 p-2 bg-gray-50 rounded text-sm">
                                        <span class="font-medium text-gray-700">Reason:</span> ${duplicate.reason}
                                    </div>
                                    
                                    ${duplicate.customer.data_quality_score ? `
                                        <div class="mt-2 text-sm">
                                            <span class="text-gray-500">Data Quality:</span> 
                                            <span class="font-medium">${duplicate.customer.data_quality_score}%</span>
                                        </div>
                                    ` : ''}
                                </div>
                                
                                <div class="ml-4 flex flex-col space-y-2">
                                    <a href="/super-admin/customer-ledgers/${duplicate.customer.ledger_id}" 
                                       class="px-3 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700 transition text-center">
                                        View Details
                                    </a>
                                    <a href="/super-admin/customer-ledgers/${duplicate.customer.ledger_id}/edit" 
                                       class="px-3 py-1 text-xs bg-green-600 text-white rounded hover:bg-green-700 transition text-center">
                                        Edit
                                    </a>
                                </div>
                            </div>
                        </div>
                    `;
            });

            duplicateResults.innerHTML = html;
        }

        // Initialize on page load - trigger district change if district is selected
        if (districtFilter.value && !isLoading) {
            districtFilter.dispatchEvent(new Event('change'));
        }
    });
</script>

@push('styles')
<style>
    /* Custom styles for better mobile responsiveness */
    @media (max-width: 768px) {
        .table-responsive {
            font-size: 0.875rem;
        }

        .table-responsive th,
        .table-responsive td {
            padding: 0.5rem 0.25rem;
        }

        .mobile-stack {
            display: block;
        }

        .mobile-stack>div {
            margin-bottom: 0.25rem;
        }
    }

    /* Enhanced hover effects */
    .action-button {
        transition: all 0.2s ease-in-out;
    }

    .action-button:hover {
        transform: scale(1.1);
    }

    /* Loading states */
    .loading-overlay {
        position: relative;
    }

    .loading-overlay::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.8);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Filter badge styles */
    .filter-badge {
        background: #3B82F6;
        color: white;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 0.75rem;
        margin-left: 0.5rem;
    }

    /* Duplicate modal styles */
    .duplicate-item {
        transition: all 0.2s ease-in-out;
    }

    .duplicate-item:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
</style>
@endpush
@endsection