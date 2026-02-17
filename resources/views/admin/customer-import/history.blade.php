@extends('admin.layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header Section -->
    <div class="mb-8">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Import History</h1>
                <p class="text-gray-600 mt-1">Track all customer import operations</p>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('admin.customer-import.index') }}"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                    <i class="fas fa-plus mr-2"></i>New Import
                </a>
                <a href="{{ route('admin.customer-import.export-history') }}"
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors">
                    <i class="fas fa-download mr-2"></i>Export CSV
                </a>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-blue-50 rounded-lg p-6">
            <div class="flex items-center">
                <div class="p-3 bg-blue-500 rounded-full">
                    <i class="fas fa-history text-white text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-blue-600">Total Imports</p>
                    <p class="text-2xl font-bold text-blue-900">{{ $importHistory->total() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-green-50 rounded-lg p-6">
            <div class="flex items-center">
                <div class="p-3 bg-green-500 rounded-full">
                    <i class="fas fa-check-circle text-white text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-green-600">Successful</p>
                    <p class="text-2xl font-bold text-green-900">{{ $importHistory->where('import_status', 'completed')->count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-yellow-50 rounded-lg p-6">
            <div class="flex items-center">
                <div class="p-3 bg-yellow-500 rounded-full">
                    <i class="fas fa-clock text-white text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-yellow-600">In Progress</p>
                    <p class="text-2xl font-bold text-yellow-900">{{ $importHistory->where('import_status', 'in_progress')->count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-red-50 rounded-lg p-6">
            <div class="flex items-center">
                <div class="p-3 bg-red-500 rounded-full">
                    <i class="fas fa-times-circle text-white text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-red-600">Failed</p>
                    <p class="text-2xl font-bold text-red-900">{{ $importHistory->where('import_status', 'failed')->count() }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Import History Table -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="p-6 border-b">
            <h2 class="text-xl font-semibold text-gray-900">Import Records</h2>
        </div>

        @if($importHistory->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Batch ID
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Location
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Results
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Success Rate
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Duration
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Date
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($importHistory as $import)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $import->import_batch_id }}
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $import->location_label }}</div>
                            <div class="text-sm text-gray-500">
                                @if($import->district)
                                {{ $import->district }}
                                @if($import->sub_district), {{ $import->sub_district }}@endif
                                @if($import->village), {{ $import->village }}@endif
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex flex-col space-y-1">
                                <div class="flex items-center space-x-3">
                                    <span class="text-green-600 text-sm">
                                        <i class="fas fa-check-circle mr-1"></i>{{ $import->total_imported }}
                                    </span>
                                    @if($import->total_failed > 0)
                                    <span class="text-red-600 text-sm">
                                        <i class="fas fa-times-circle mr-1"></i>{{ $import->total_failed }}
                                    </span>
                                    @endif
                                    @if($import->total_skipped > 0)
                                    <span class="text-yellow-600 text-sm">
                                        <i class="fas fa-exclamation-triangle mr-1"></i>{{ $import->total_skipped }}
                                    </span>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ $import->total_selected }} selected
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                    <div class="bg-green-600 h-2 rounded-full"
                                        style="width: {{ $import->success_rate }}%"></div>
                                </div>
                                <span class="text-sm font-medium text-gray-900">
                                    {{ number_format($import->success_rate, 1) }}%
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                @if($import->import_status === 'completed') bg-green-100 text-green-800
                                @elseif($import->import_status === 'failed') bg-red-100 text-red-800
                                @elseif($import->import_status === 'in_progress') bg-yellow-100 text-yellow-800
                                @else bg-gray-100 text-gray-800 @endif">
                                {{ ucfirst(str_replace('_', ' ', $import->import_status)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            @if($import->duration)
                            {{ $import->duration }}
                            @elseif($import->started_at && $import->completed_at)
                            {{ $import->started_at->diffForHumans($import->completed_at, true) }}
                            @else
                            -
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <div>{{ $import->created_at->format('M d, Y') }}</div>
                            <div class="text-xs">{{ $import->created_at->format('H:i A') }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center space-x-2">
                                <button class="text-blue-600 hover:text-blue-900 view-details-btn"
                                    data-batch-id="{{ $import->import_batch_id }}">
                                    <i class="fas fa-eye mr-1"></i>Details
                                </button>
                                @if($import->import_status === 'failed' || $import->total_failed > 0)
                                <button class="text-yellow-600 hover:text-yellow-900 retry-import-btn"
                                    data-batch-id="{{ $import->import_batch_id }}">
                                    <i class="fas fa-redo mr-1"></i>Retry
                                </button>
                                @endif
                                <button class="text-red-600 hover:text-red-900 delete-batch-btn"
                                    data-batch-id="{{ $import->import_batch_id }}">
                                    <i class="fas fa-trash mr-1"></i>Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t">
            {{ $importHistory->links() }}
        </div>
        @else
        <div class="p-12 text-center">
            <i class="fas fa-history text-gray-300 text-6xl mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No Import History</h3>
            <p class="text-gray-500 mb-4">You haven't imported any customers yet.</p>
            <a href="{{ route('admin.customer-import.index') }}"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                Start Your First Import
            </a>
        </div>
        @endif
    </div>
</div>

<!-- Import Details Modal -->
<div id="importDetailsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-2/3 shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-900">Import Details</h3>
            <button id="closeDetailsModal" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div id="importDetailsContent">
            <!-- Import details will be loaded here -->
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteConfirmModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-red-600">
                <i class="fas fa-exclamation-triangle mr-2"></i>Delete Import Batch
            </h3>
            <button id="closeDeleteModal" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="mb-6">
            <p class="text-gray-700 mb-4">Are you sure you want to delete this import batch?</p>
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex items-start">
                    <i class="fas fa-exclamation-triangle text-yellow-400 mr-3 mt-1"></i>
                    <div>
                        <p class="text-sm text-yellow-800 mb-2">
                            <strong>Warning:</strong> This action will:
                        </p>
                        <ul class="text-sm text-yellow-700 list-disc list-inside space-y-1">
                            <li>Delete the import history record</li>
                            <li>Remove any associated conflict records</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="mt-4">
                <label class="flex items-center">
                    <input type="checkbox" id="deleteCustomersCheckbox" class="mr-2">
                    <span class="text-sm text-gray-700">Also delete the imported customers from my business</span>
                </label>
            </div>
        </div>
        <div class="flex justify-end space-x-3">
            <button id="cancelDeleteBtn" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg">
                Cancel
            </button>
            <button id="confirmDeleteBtn" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg">
                <span id="deleteIcon">
                    <i class="fas fa-trash mr-2"></i>
                </span>
                <span id="deleteSpinner" class="hidden">
                    <i class="fas fa-spinner fa-spin mr-2"></i>
                </span>
                <span id="deleteButtonText">Delete Batch</span>
            </button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        let currentBatchId = null;

        // CSRF token setup
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // View import details
        $('.view-details-btn').on('click', function() {
            const batchId = $(this).data('batch-id');
            loadImportDetails(batchId);
        });

        function loadImportDetails(batchId) {
            $('#importDetailsContent').html('<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-2xl"></i><p class="mt-2">Loading details...</p></div>');
            $('#importDetailsModal').removeClass('hidden');

            $.get(`{{ route("admin.customer-import.history") }}/${batchId}`)
                .done(function(response) {
                    if (response.success) {
                        displayImportDetails(response.import);
                    } else {
                        $('#importDetailsContent').html('<div class="text-red-600 text-center py-8">Error loading import details</div>');
                    }
                })
                .fail(function() {
                    $('#importDetailsContent').html('<div class="text-red-600 text-center py-8">Error loading import details</div>');
                });
        }

        function displayImportDetails(importData) {
            const detailsHtml = `
            <div class="space-y-6">
                <!-- Summary Section -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-blue-50 rounded-lg p-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-600">${importData.total_selected}</div>
                            <div class="text-sm text-blue-800">Selected</div>
                        </div>
                    </div>
                    <div class="bg-green-50 rounded-lg p-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-green-600">${importData.total_imported}</div>
                            <div class="text-sm text-green-800">Imported</div>
                        </div>
                    </div>
                    <div class="bg-red-50 rounded-lg p-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-red-600">${importData.total_failed}</div>
                            <div class="text-sm text-red-800">Failed</div>
                        </div>
                    </div>
                </div>

                <!-- Import Information -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-900 mb-3">Import Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="font-medium text-gray-700">Batch ID:</span>
                            <span class="text-gray-900">${importData.import_batch_id}</span>
                        </div>
                        <div>
                            <span class="font-medium text-gray-700">Location:</span>
                            <span class="text-gray-900">${importData.location_label}</span>
                        </div>
                        <div>
                            <span class="font-medium text-gray-700">Started:</span>
                            <span class="text-gray-900">${importData.started_at}</span>
                        </div>
                        <div>
                            <span class="font-medium text-gray-700">Completed:</span>
                            <span class="text-gray-900">${importData.completed_at || 'N/A'}</span>
                        </div>
                        <div>
                            <span class="font-medium text-gray-700">Duration:</span>
                            <span class="text-gray-900">${importData.duration || 'N/A'}</span>
                        </div>
                        <div>
                            <span class="font-medium text-gray-700">Success Rate:</span>
                            <span class="text-gray-900">${importData.success_rate}%</span>
                        </div>
                    </div>
                </div>

                ${importData.import_notes ? `
                <div class="bg-blue-50 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-900 mb-2">Notes</h4>
                    <p class="text-gray-700">${importData.import_notes}</p>
                </div>
                ` : ''}

                                ${importData.error_details && importData.error_details.length > 0 ? `
                <div class="bg-red-50 rounded-lg p-4">
                    <h4 class="font-semibold text-red-900 mb-3">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Errors & Issues
                    </h4>
                    <div class="space-y-2 max-h-48 overflow-y-auto">
                        ${importData.error_details.map(error => `
                            <div class="bg-white border border-red-200 rounded p-3">
                                <div class="flex items-start space-x-2">
                                    <i class="fas fa-times-circle text-red-500 mt-1"></i>
                                    <div class="flex-1">
                                        <div class="font-medium text-red-800">${error.customer_name || 'Unknown Customer'}</div>
                                        <div class="text-sm text-red-700">${error.error_message}</div>
                                        ${error.error_code ? `<div class="text-xs text-red-600 mt-1">Code: ${error.error_code}</div>` : ''}
                                    </div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
                ` : ''}

                ${importData.imported_customers && importData.imported_customers.length > 0 ? `
                <div class="bg-green-50 rounded-lg p-4">
                    <h4 class="font-semibold text-green-900 mb-3">
                        <i class="fas fa-check-circle mr-2"></i>Successfully Imported Customers
                    </h4>
                    <div class="space-y-2 max-h-64 overflow-y-auto">
                        ${importData.imported_customers.map(customer => `
                            <div class="bg-white border border-green-200 rounded p-3">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center text-white font-bold text-xs">
                                            ${getInitials(customer.name)}
                                        </div>
                                        <div>
                                            <div class="font-medium text-green-800">${customer.name}</div>
                                            <div class="text-sm text-green-700">${customer.type}</div>
                                            ${customer.phone ? `<div class="text-xs text-green-600">${customer.phone}</div>` : ''}
                                        </div>
                                    </div>
                                    <div class="text-xs text-green-600">
                                        ID: ${customer.local_id}
                                    </div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
                ` : ''}

                <!-- Actions -->
                <div class="flex justify-end space-x-3 pt-4 border-t">
                    ${importData.import_status === 'failed' || importData.total_failed > 0 ? `
                        <button class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg retry-failed-btn" 
                                data-batch-id="${importData.import_batch_id}">
                            <i class="fas fa-redo mr-2"></i>Retry Failed
                        </button>
                    ` : ''}
                    <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg export-batch-btn" 
                            data-batch-id="${importData.import_batch_id}">
                        <i class="fas fa-download mr-2"></i>Export Report
                    </button>
                </div>
            </div>
        `;

            $('#importDetailsContent').html(detailsHtml);
        }

        // Retry import
        $(document).on('click', '.retry-import-btn, .retry-failed-btn', function() {
            const batchId = $(this).data('batch-id');

            if (confirm('Are you sure you want to retry this import? This will attempt to import any failed customers again.')) {
                const btn = $(this);
                const originalText = btn.html();

                btn.html('<i class="fas fa-spinner fa-spin mr-1"></i>Retrying...').prop('disabled', true);

                $.post(`{{ route("admin.customer-import.retry") }}`, {
                        batch_id: batchId
                    })
                    .done(function(response) {
                        if (response.success) {
                            showAlert('Retry initiated successfully!', 'success');
                            setTimeout(() => location.reload(), 2000);
                        } else {
                            showAlert('Retry failed: ' + response.message, 'error');
                        }
                    })
                    .fail(function(xhr) {
                        const message = xhr.responseJSON?.message || 'Retry failed';
                        showAlert(message, 'error');
                    })
                    .always(function() {
                        btn.html(originalText).prop('disabled', false);
                    });
            }
        });

        // Export batch report
        $(document).on('click', '.export-batch-btn', function() {
            const batchId = $(this).data('batch-id');
            window.open(`{{ route("admin.customer-import.export-batch", "") }}/${batchId}`, '_blank');
        });

        // Delete batch
        $('.delete-batch-btn').on('click', function() {
            currentBatchId = $(this).data('batch-id');
            $('#deleteConfirmModal').removeClass('hidden');
        });

        $('#confirmDeleteBtn').on('click', function() {
            if (!currentBatchId) return;

            const deleteCustomers = $('#deleteCustomersCheckbox').is(':checked');
            const btn = $(this);

            // Show loading state
            $('#deleteIcon').addClass('hidden');
            $('#deleteSpinner').removeClass('hidden');
            $('#deleteButtonText').text('Deleting...');
            btn.prop('disabled', true);

            $.ajax({
                    url: `{{ route("admin.customer-import.delete-batch", "") }}/${currentBatchId}`,
                    method: 'DELETE',
                    data: {
                        delete_customers: deleteCustomers
                    }
                })
                .done(function(response) {
                    if (response.success) {
                        showAlert('Import batch deleted successfully!', 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showAlert('Delete failed: ' + response.message, 'error');
                    }
                })
                .fail(function(xhr) {
                    const message = xhr.responseJSON?.message || 'Delete failed';
                    showAlert(message, 'error');
                })
                .always(function() {
                    // Reset button state
                    $('#deleteIcon').removeClass('hidden');
                    $('#deleteSpinner').addClass('hidden');
                    $('#deleteButtonText').text('Delete Batch');
                    btn.prop('disabled', false);
                    $('#deleteConfirmModal').addClass('hidden');
                    currentBatchId = null;
                });
        });

        // Modal handlers
        $('#closeDetailsModal, #closeDeleteModal').on('click', function() {
            $(this).closest('.fixed').addClass('hidden');
        });

        $('#cancelDeleteBtn').on('click', function() {
            $('#deleteConfirmModal').addClass('hidden');
            currentBatchId = null;
        });

        // Helper functions
        function getInitials(name) {
            return name.split(' ').map(word => word.charAt(0).toUpperCase()).slice(0, 2).join('');
        }

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

        // Close modals when clicking outside
        $('.fixed').on('click', function(e) {
            if (e.target === this) {
                $(this).addClass('hidden');
            }
        });
    });
</script>
@endpush