@extends('admin.layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header Section -->
    <div class="mb-8">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Import Conflicts</h1>
                <p class="text-gray-600 mt-1">Resolve conflicts from customer imports</p>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('admin.customer-import.index') }}"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                    <i class="fas fa-plus mr-2"></i>New Import
                </a>
                <button id="resolveAllBtn"
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors"
                    {{ $conflicts->count() === 0 ? 'disabled' : '' }}>
                    <i class="fas fa-check-double mr-2"></i>Resolve All
                </button>
            </div>
        </div>
    </div>

    <!-- Conflict Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-yellow-50 rounded-lg p-6">
            <div class="flex items-center">
                <div class="p-3 bg-yellow-500 rounded-full">
                    <i class="fas fa-exclamation-triangle text-white text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-yellow-600">Total Conflicts</p>
                    <p class="text-2xl font-bold text-yellow-900">{{ $conflicts->total() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-red-50 rounded-lg p-6">
            <div class="flex items-center">
                <div class="p-3 bg-red-500 rounded-full">
                    <i class="fas fa-user-times text-white text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-red-600">Name Conflicts</p>
                    <p class="text-2xl font-bold text-red-900">{{ $conflicts->where('conflict_type', 'name_similarity')->count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-orange-50 rounded-lg p-6">
            <div class="flex items-center">
                <div class="p-3 bg-orange-500 rounded-full">
                    <i class="fas fa-phone text-white text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-orange-600">Phone Conflicts</p>
                    <p class="text-2xl font-bold text-orange-900">{{ $conflicts->where('conflict_type', 'phone_duplicate')->count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-purple-50 rounded-lg p-6">
            <div class="flex items-center">
                <div class="p-3 bg-purple-500 rounded-full">
                    <i class="fas fa-map-marker-alt text-white text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-purple-600">Location Conflicts</p>
                    <p class="text-2xl font-bold text-purple-900">{{ $conflicts->where('conflict_type', 'location_similarity')->count() }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Conflicts List -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="p-6 border-b">
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-900">Pending Conflicts</h2>
                <div class="flex space-x-3">
                    <select id="conflictTypeFilter" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <option value="">All Types</option>
                        <option value="name_similarity">Name Conflicts</option>
                        <option value="phone_duplicate">Phone Conflicts</option>
                        <option value="location_similarity">Location Conflicts</option>
                    </select>
                    <select id="confidenceFilter" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <option value="">All Confidence</option>
                        <option value="high">High (90%+)</option>
                        <option value="medium">Medium (70-89%)</option>
                        <option value="low">Low (<70%)< /option>
                    </select>
                </div>
            </div>
        </div>

        @if($conflicts->count() > 0)
        <div class="divide-y divide-gray-200">
            @foreach($conflicts as $conflict)
            <div class="p-6 conflict-item" data-conflict-id="{{ $conflict->id }}"
                data-type="{{ $conflict->conflict_type }}"
                data-confidence="{{ $conflict->confidence_score }}">

                <!-- Conflict Header -->
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center space-x-3">
                        <div class="p-2 rounded-full 
                            @if($conflict->conflict_type === 'phone_duplicate') bg-red-100 text-red-600
                            @elseif($conflict->conflict_type === 'name_similarity') bg-yellow-100 text-yellow-600
                            @elseif($conflict->conflict_type === 'location_similarity') bg-purple-100 text-purple-600
                            @else bg-gray-100 text-gray-600 @endif">
                            @if($conflict->conflict_type === 'phone_duplicate')
                            <i class="fas fa-phone text-sm"></i>
                            @elseif($conflict->conflict_type === 'name_similarity')
                            <i class="fas fa-user text-sm"></i>
                            @elseif($conflict->conflict_type === 'location_similarity')
                            <i class="fas fa-map-marker-alt text-sm"></i>
                            @else
                            <i class="fas fa-exclamation-triangle text-sm"></i>
                            @endif
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">
                                {{ ucfirst(str_replace('_', ' ', $conflict->conflict_type)) }} Conflict
                            </h3>
                            <div class="flex items-center space-x-4 text-sm text-gray-500">
                                <span>Confidence: {{ number_format($conflict->confidence_score, 1) }}%</span>
                                <span>•</span>
                                <span>Batch: {{ $conflict->import_batch_id }}</span>
                                <span>•</span>
                                <span>{{ $conflict->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="px-3 py-1 text-xs font-medium rounded-full
                            @if($conflict->confidence_score >= 90) bg-red-100 text-red-800
                            @elseif($conflict->confidence_score >= 70) bg-yellow-100 text-yellow-800
                            @else bg-blue-100 text-blue-800 @endif">
                            @if($conflict->confidence_score >= 90) High Risk
                            @elseif($conflict->confidence_score >= 70) Medium Risk
                            @else Low Risk @endif
                        </span>
                    </div>
                </div>

                <!-- Conflict Details -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- New Customer (From Import) -->
                    <div class="border border-blue-200 rounded-lg p-4 bg-blue-50">
                        <div class="flex items-center mb-3">
                            <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold text-sm">
                                {{ substr($conflict->new_customer_data['name'] ?? 'N', 0, 1) }}
                            </div>
                            <div class="ml-3">
                                <h4 class="font-semibold text-blue-900">New Customer (Import)</h4>
                                <p class="text-sm text-blue-700">From {{ $conflict->import_batch_id }}</p>
                            </div>
                        </div>
                        <div class="space-y-2 text-sm">
                            <div>
                                <span class="font-medium text-blue-800">Name:</span>
                                <span class="text-blue-900">{{ $conflict->new_customer_data['name'] ?? 'N/A' }}</span>
                            </div>
                            @if(isset($conflict->new_customer_data['phone']))
                            <div>
                                <span class="font-medium text-blue-800">Phone:</span>
                                <span class="text-blue-900">{{ $conflict->new_customer_data['phone'] }}</span>
                            </div>
                            @endif
                            @if(isset($conflict->new_customer_data['type']))
                            <div>
                                <span class="font-medium text-blue-800">Type:</span>
                                <span class="text-blue-900">{{ $conflict->new_customer_data['type'] }}</span>
                            </div>
                            @endif
                            @if(isset($conflict->new_customer_data['location']))
                            <div>
                                <span class="font-medium text-blue-800">Location:</span>
                                <span class="text-blue-900">{{ $conflict->new_customer_data['location'] }}</span>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Existing Customer -->
                    <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                        <div class="flex items-center mb-3">
                            <div class="w-8 h-8 bg-gray-500 rounded-full flex items-center justify-center text-white font-bold text-sm">
                                {{ substr($conflict->existing_customer_data['name'] ?? 'E', 0, 1) }}
                            </div>
                            <div class="ml-3">
                                <h4 class="font-semibold text-gray-900">Existing Customer</h4>
                                <p class="text-sm text-gray-700">In your business</p>
                            </div>
                        </div>
                        <div class="space-y-2 text-sm">
                            <div>
                                <span class="font-medium text-gray-800">Name:</span>
                                <span class="text-gray-900">{{ $conflict->existing_customer_data['name'] ?? 'N/A' }}</span>
                            </div>
                            @if(isset($conflict->existing_customer_data['phone']))
                            <div>
                                <span class="font-medium text-gray-800">Phone:</span>
                                <span class="text-gray-900">{{ $conflict->existing_customer_data['phone'] }}</span>
                            </div>
                            @endif
                            @if(isset($conflict->existing_customer_data['type']))
                            <div>
                                <span class="font-medium text-gray-800">Type:</span>
                                <span class="text-gray-900">{{ $conflict->existing_customer_data['type'] }}</span>
                            </div>
                            @endif
                            @if(isset($conflict->existing_customer_data['location']))
                            <div>
                                <span class="font-medium text-gray-800">Location:</span>
                                <span class="text-gray-900">{{ $conflict->existing_customer_data['location'] }}</span>
                            </div>
                            @endif
                            @if(isset($conflict->existing_customer_data['created_at']))
                            <div>
                                <span class="font-medium text-gray-800">Created:</span>
                                <span class="text-gray-900">{{ \Carbon\Carbon::parse($conflict->existing_customer_data['created_at'])->format('M d, Y') }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Conflict Reason -->
                <div class="mb-6">
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <div class="flex items-start">
                            <i class="fas fa-info-circle text-yellow-500 mr-3 mt-1"></i>
                            <div>
                                <h5 class="font-medium text-yellow-800 mb-1">Conflict Reason</h5>
                                <p class="text-sm text-yellow-700">{{ $conflict->conflict_reason }}</p>
                                @if($conflict->similarity_details)
                                <div class="mt-2 text-xs text-yellow-600">
                                    <strong>Details:</strong> {{ json_encode($conflict->similarity_details) }}
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resolution Actions -->
                <div class="flex items-center justify-between pt-4 border-t">
                    <div class="flex items-center space-x-4">
                        <button class="text-blue-600 hover:text-blue-800 text-sm view-full-details-btn"
                            data-conflict-id="{{ $conflict->id }}">
                            <i class="fas fa-eye mr-1"></i>View Full Details
                        </button>
                        @if(isset($conflict->existing_customer_data['id']))
                        <a href="{{ route('admin.customers.show', $conflict->existing_customer_data['id']) }}"
                            target="_blank"
                            class="text-green-600 hover:text-green-800 text-sm">
                            <i class="fas fa-external-link-alt mr-1"></i>View Existing Customer
                        </a>
                        @endif
                    </div>
                    <div class="flex items-center space-x-3">
                        <button class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg text-sm skip-conflict-btn"
                            data-conflict-id="{{ $conflict->id }}">
                            <i class="fas fa-times mr-1"></i>Skip
                        </button>
                        <button class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg text-sm merge-customers-btn"
                            data-conflict-id="{{ $conflict->id }}">
                            <i class="fas fa-compress-arrows-alt mr-1"></i>Merge
                        </button>
                        <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm import-anyway-btn"
                            data-conflict-id="{{ $conflict->id }}">
                            <i class="fas fa-plus mr-1"></i>Import Anyway
                        </button>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t">
            {{ $conflicts->links() }}
        </div>
        @else
        <div class="p-12 text-center">
            <i class="fas fa-check-circle text-green-300 text-6xl mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No Conflicts Found</h3>
            <p class="text-gray-500 mb-4">All your imports are conflict-free!</p>
            <a href="{{ route('admin.customer-import.index') }}"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                Import More Customers
            </a>
        </div>
        @endif
    </div>
</div>

<!-- Conflict Details Modal -->
<div id="conflictDetailsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-2/3 shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-900">Conflict Details</h3>
            <button id="closeConflictDetailsModal" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div id="conflictDetailsContent">
            <!-- Conflict details will be loaded here -->
        </div>
    </div>
</div>

<!-- Merge Confirmation Modal -->
<div id="mergeConfirmModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-yellow-600">
                <i class="fas fa-compress-arrows-alt mr-2"></i>Merge Customers
            </h3>
            <button id="closeMergeModal" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="mb-6">
            <p class="text-gray-700 mb-4">Choose how to merge these customers:</p>
            <div class="space-y-3">
                <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                    <input type="radio" name="merge_strategy" value="keep_existing" class="mr-3" checked>
                    <div>
                        <div class="font-medium text-gray-900">Keep Existing Customer</div>
                        <div class="text-sm text-gray-600">Update existing customer with any missing information from import</div>
                    </div>
                </label>
                <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                    <input type="radio" name="merge_strategy" value="keep_new" class="mr-3">
                    <div>
                        <div class="font-medium text-gray-900">Replace with New Customer</div>
                        <div class="text-sm text-gray-600">Replace existing customer with imported data</div>
                    </div>
                </label>
                <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                    <input type="radio" name="merge_strategy" value="merge_best" class="mr-3">
                    <div>
                        <div class="font-medium text-gray-900">Smart Merge</div>
                        <div class="text-sm text-gray-600">Automatically choose the best data from both customers</div>
                    </div>
                </label>
            </div>
        </div>
        <div class="flex justify-end space-x-3">
            <button id="cancelMergeBtn" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg">
                Cancel
            </button>
            <button id="confirmMergeBtn" class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg">
                <span id="mergeIcon">
                    <i class="fas fa-compress-arrows-alt mr-2"></i>
                </span>
                <span id="mergeSpinner" class="hidden">
                    <i class="fas fa-spinner fa-spin mr-2"></i>
                </span>
                <span id="mergeButtonText">Merge Customers</span>
            </button>
        </div>
    </div>
</div>

<!-- Resolve All Confirmation Modal -->
<div id="resolveAllModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-green-600">
                <i class="fas fa-check-double mr-2"></i>Resolve All Conflicts
            </h3>
            <button id="closeResolveAllModal" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="mb-6">
            <p class="text-gray-700 mb-4">Choose how to resolve all pending conflicts:</p>
            <div class="space-y-3">
                <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                    <input type="radio" name="resolve_strategy" value="skip_all" class="mr-3" checked>
                    <div>
                        <div class="font-medium text-gray-900">Skip All Conflicts</div>
                        <div class="text-sm text-gray-600">Mark all conflicts as resolved without importing</div>
                    </div>
                </label>
                <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                    <input type="radio" name="resolve_strategy" value="import_all" class="mr-3">
                    <div>
                        <div class="font-medium text-gray-900">Import All Anyway</div>
                        <div class="text-sm text-gray-600">Import all conflicting customers as new entries</div>
                    </div>
                </label>
                <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                    <input type="radio" name="resolve_strategy" value="merge_all" class="mr-3">
                    <div>
                        <div class="font-medium text-gray-900">Smart Merge All</div>
                        <div class="text-sm text-gray-600">Automatically merge all conflicts using best data</div>
                    </div>
                </label>
            </div>
            <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                <div class="flex items-start">
                    <i class="fas fa-exclamation-triangle text-yellow-500 mr-2 mt-1"></i>
                    <div class="text-sm text-yellow-700">
                        <strong>Warning:</strong> This action will affect {{ $conflicts->count() }} conflicts and cannot be undone.
                    </div>
                </div>
            </div>
        </div>
        <div class="flex justify-end space-x-3">
            <button id="cancelResolveAllBtn" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg">
                Cancel
            </button>
            <button id="confirmResolveAllBtn" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
                <span id="resolveAllIcon">
                    <i class="fas fa-check-double mr-2"></i>
                </span>
                <span id="resolveAllSpinner" class="hidden">
                    <i class="fas fa-spinner fa-spin mr-2"></i>
                </span>
                <span id="resolveAllButtonText">Resolve All</span>
            </button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        let currentConflictId = null;

        // CSRF token setup
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Filter functionality
        $('#conflictTypeFilter, #confidenceFilter').on('change', function() {
            filterConflicts();
        });

        function filterConflicts() {
            const typeFilter = $('#conflictTypeFilter').val();
            const confidenceFilter = $('#confidenceFilter').val();

            $('.conflict-item').each(function() {
                const $item = $(this);
                const itemType = $item.data('type');
                const itemConfidence = $item.data('confidence');

                let showItem = true;

                // Type filter
                if (typeFilter && itemType !== typeFilter) {
                    showItem = false;
                }

                // Confidence filter
                if (confidenceFilter) {
                    if (confidenceFilter === 'high' && itemConfidence < 90) {
                        showItem = false;
                    } else if (confidenceFilter === 'medium' && (itemConfidence < 70 || itemConfidence >= 90)) {
                        showItem = false;
                    } else if (confidenceFilter === 'low' && itemConfidence >= 70) {
                        showItem = false;
                    }
                }

                if (showItem) {
                    $item.show();
                } else {
                    $item.hide();
                }
            });
        }

        // View full conflict details
        $('.view-full-details-btn').on('click', function() {
            const conflictId = $(this).data('conflict-id');
            loadConflictDetails(conflictId);
        });

        function loadConflictDetails(conflictId) {
            $('#conflictDetailsContent').html('<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-2xl"></i><p class="mt-2">Loading details...</p></div>');
            $('#conflictDetailsModal').removeClass('hidden');

            $.get(`{{ route("admin.customer-import.conflicts") }}/${conflictId}`)
                .done(function(response) {
                    if (response.success) {
                        displayConflictDetails(response.conflict);
                    } else {
                        $('#conflictDetailsContent').html('<div class="text-red-600 text-center py-8">Error loading conflict details</div>');
                    }
                })
                .fail(function() {
                    $('#conflictDetailsContent').html('<div class="text-red-600 text-center py-8">Error loading conflict details</div>');
                });
        }

        function displayConflictDetails(conflict) {
            const detailsHtml = `
            <div class="space-y-6">
                <!-- Conflict Summary -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-900 mb-3">Conflict Summary</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="font-medium text-gray-700">Type:</span>
                            <span class="text-gray-900">${conflict.conflict_type.replace('_', ' ')}</span>
                        </div>
                        <div>
                            <span class="font-medium text-gray-700">Confidence:</span>
                            <span class="text-gray-900">${conflict.confidence_score}%</span>
                        </div>
                        <div>
                            <span class="font-medium text-gray-700">Batch ID:</span>
                            <span class="text-gray-900">${conflict.import_batch_id}</span>
                        </div>
                        <div>
                            <span class="font-medium text-gray-700">Detected:</span>
                            <span class="text-gray-900">${conflict.created_at}</span>
                        </div>
                    </div>
                </div>

                <!-- Detailed Comparison -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="border border-blue-200 rounded-lg p-4 bg-blue-50">
                        <h5 class="font-semibold text-blue-900 mb-3">New Customer Data</h5>
                        <div class="space-y-2 text-sm">
                            ${Object.entries(conflict.new_customer_data).map(([key, value]) => `
                                <div class="flex justify-between">
                                    <span class="font-medium text-blue-800">${key.replace('_', ' ')}:</span>
                                    <span class="text-blue-900">${value || 'N/A'}</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                    
                    <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                        <h5 class="font-semibold text-gray-900 mb-3">Existing Customer Data</h5>
                        <div class="space-y-2 text-sm">
                            ${Object.entries(conflict.existing_customer_data).map(([key, value]) => `
                                <div class="flex justify-between">
                                    <span class="font-medium text-gray-800">${key.replace('_', ' ')}:</span>
                                    <span class="text-gray-900">${value || 'N/A'}</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>

                <!-- Similarity Analysis -->
                ${conflict.similarity_details ? `
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <h5 class="font-semibold text-yellow-900 mb-3">Similarity Analysis</h5>
                    <div class="text-sm text-yellow-800">
                        <pre class="whitespace-pre-wrap">${JSON.stringify(conflict.similarity_details, null, 2)}</pre>
                    </div>
                </div>
                ` : ''}

                <!-- Resolution History -->
                ${conflict.resolution_history && conflict.resolution_history.length > 0 ? `
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <h5 class="font-semibold text-green-900 mb-3">Resolution History</h5>
                    <div class="space-y-2">
                        ${conflict.resolution_history.map(resolution => `
                            <div class="text-sm text-green-800">
                                <div class="flex justify-between">
                                    <span>${resolution.action}</span>
                                    <span>${resolution.timestamp}</span>
                                </div>
                                ${resolution.notes ? `<div class="text-green-700 mt-1">${resolution.notes}</div>` : ''}
                            </div>
                        `).join('')}
                    </div>
                </div>
                ` : ''}
            </div>
        `;

            $('#conflictDetailsContent').html(detailsHtml);
        }

        // Skip conflict
        $('.skip-conflict-btn').on('click', function() {
            const conflictId = $(this).data('conflict-id');

            if (confirm('Are you sure you want to skip this conflict? The customer will not be imported.')) {
                resolveConflict(conflictId, 'skip', null, $(this));
            }
        });

        // Import anyway
        $('.import-anyway-btn').on('click', function() {
            const conflictId = $(this).data('conflict-id');

            if (confirm('Are you sure you want to import this customer anyway? This will create a duplicate entry.')) {
                resolveConflict(conflictId, 'import_anyway', null, $(this));
            }
        });

        // Merge customers
        $('.merge-customers-btn').on('click', function() {
            currentConflictId = $(this).data('conflict-id');
            $('#mergeConfirmModal').removeClass('hidden');
        });

        $('#confirmMergeBtn').on('click', function() {
            const strategy = $('input[name="merge_strategy"]:checked').val();

            if (!currentConflictId || !strategy) return;

            const btn = $(this);

            // Show loading state
            $('#mergeIcon').addClass('hidden');
            $('#mergeSpinner').removeClass('hidden');
            $('#mergeButtonText').text('Merging...');
            btn.prop('disabled', true);

            resolveConflict(currentConflictId, 'merge', {
                strategy: strategy
            }, btn);
        });

        // Resolve all conflicts
        $('#resolveAllBtn').on('click', function() {
            $('#resolveAllModal').removeClass('hidden');
        });

        $('#confirmResolveAllBtn').on('click', function() {
            const strategy = $('input[name="resolve_strategy"]:checked').val();

            if (!strategy) return;

            const btn = $(this);

            // Show loading state
            $('#resolveAllIcon').addClass('hidden');
            $('#resolveAllSpinner').removeClass('hidden');
            $('#resolveAllButtonText').text('Resolving...');
            btn.prop('disabled', true);

            $.post('{{ route("admin.customer-import.resolve-all-conflicts") }}', {
                    strategy: strategy
                })
                .done(function(response) {
                    if (response.success) {
                        showAlert(`Successfully resolved ${response.resolved_count} conflicts!`, 'success');
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        showAlert('Failed to resolve conflicts: ' + response.message, 'error');
                    }
                })
                .fail(function(xhr) {
                    const message = xhr.responseJSON?.message || 'Failed to resolve conflicts';
                    showAlert(message, 'error');
                })
                .always(function() {
                    // Reset button state
                    $('#resolveAllIcon').removeClass('hidden');
                    $('#resolveAllSpinner').addClass('hidden');
                    $('#resolveAllButtonText').text('Resolve All');
                    btn.prop('disabled', false);
                    $('#resolveAllModal').addClass('hidden');
                });
        });

        function resolveConflict(conflictId, action, data, buttonElement) {
            const originalText = buttonElement ? buttonElement.html() : '';

            if (buttonElement) {
                buttonElement.html('<i class="fas fa-spinner fa-spin mr-1"></i>Processing...').prop('disabled', true);
            }

            $.post('{{ route("admin.customer-import.resolve-conflict") }}', {
                    conflict_id: conflictId,
                    action: action,
                    data: data
                })
                .done(function(response) {
                    if (response.success) {
                        showAlert('Conflict resolved successfully!', 'success');

                        // Remove the conflict item from the page
                        $(`.conflict-item[data-conflict-id="${conflictId}"]`).fadeOut();

                        // Close any open modals
                        $('.fixed').addClass('hidden');
                        currentConflictId = null;

                        // Update conflict count in header if needed
                        setTimeout(() => {
                            const remainingConflicts = $('.conflict-item:visible').length;
                            if (remainingConflicts === 0) {
                                location.reload();
                            }
                        }, 500);
                    } else {
                        showAlert('Failed to resolve conflict: ' + response.message, 'error');
                    }
                })
                .fail(function(xhr) {
                    const message = xhr.responseJSON?.message || 'Failed to resolve conflict';
                    showAlert(message, 'error');
                })
                .always(function() {
                    if (buttonElement) {
                        buttonElement.html(originalText).prop('disabled', false);
                    }

                    // Reset merge modal button state
                    $('#mergeIcon').removeClass('hidden');
                    $('#mergeSpinner').addClass('hidden');
                    $('#mergeButtonText').text('Merge Customers');
                    $('#confirmMergeBtn').prop('disabled', false);
                    $('#mergeConfirmModal').addClass('hidden');
                });
        }

        // Modal handlers
        $('#closeConflictDetailsModal, #closeMergeModal, #closeResolveAllModal').on('click', function() {
            $(this).closest('.fixed').addClass('hidden');
        });

        $('#cancelMergeBtn').on('click', function() {
            $('#mergeConfirmModal').addClass('hidden');
            currentConflictId = null;
        });

        $('#cancelResolveAllBtn').on('click', function() {
            $('#resolveAllModal').addClass('hidden');
        });

        // Close modals when clicking outside
        $('.fixed').on('click', function(e) {
            if (e.target === this) {
                $(this).addClass('hidden');
                if (this.id === 'mergeConfirmModal') {
                    currentConflictId = null;
                }
            }
        });

        // Helper functions
        function showAlert(message, type = 'info') {
            const alertClass = {
                'success': 'bg-green-100 border-green-400 text-green-700',
                'error': 'bg-red-100 border-red-400 text-red-700',
                'warning': 'bg-yellow-100 border-yellow-400 text-yellow-700',
                'info': 'bg-blue-100 border-blue-400 text-blue-700'
            };

            const alertHtml = `
            <div class="fixed top-4 right-4 z-50 ${alertClass[type]} border px-4 py-3 rounded shadow-lg max-w-md" role="alert">
                <div class="flex justify-between items-center">
                    <span>${message}</span>
                    <button class="ml-4 text-lg font-bold" onclick="$(this).parent().parent().remove()">&times;</button>
                </div>
            </div>
        `;

            $('body').append(alertHtml);

            // Auto-remove after 5 seconds
            setTimeout(() => {
                $('.fixed[role="alert"]').fadeOut();
            }, 5000);
        }

        // Auto-refresh conflicts every 30 seconds if there are any in progress
        setInterval(function() {
            const hasInProgressConflicts = $('.conflict-item').length > 0;
            if (hasInProgressConflicts) {
                // Check for any updates without full page reload
                checkForConflictUpdates();
            }
        }, 30000);

        function checkForConflictUpdates() {
            $.get('{{ route("admin.customer-import.conflicts") }}?ajax=1')
                .done(function(response) {
                    if (response.conflicts_count !== $('.conflict-item').length) {
                        // Conflict count changed, reload page
                        location.reload();
                    }
                })
                .fail(function() {
                    // Silently fail - don't disturb user experience
                });
        }

        // Keyboard shortcuts
        $(document).on('keydown', function(e) {
            // ESC key to close modals
            if (e.key === 'Escape') {
                $('.fixed').addClass('hidden');
                currentConflictId = null;
            }

            // Ctrl+A to resolve all (when not in input field)
            if (e.ctrlKey && e.key === 'a' && !$(e.target).is('input, textarea')) {
                e.preventDefault();
                if ($('.conflict-item').length > 0) {
                    $('#resolveAllBtn').click();
                }
            }
        });

        // Initialize tooltips for better UX
        $('[title]').each(function() {
            $(this).tooltip();
        });

        // Initialize page
        filterConflicts(); // Apply any initial filters
    });
</script>
@endpush