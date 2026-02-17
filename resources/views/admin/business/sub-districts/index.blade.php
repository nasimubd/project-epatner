@extends('admin.layouts.app')

@section('content')
<div class="py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-2xl font-bold text-gray-800">Business Sub-Districts</h2>
                <p class="text-gray-600">Import and manage sub-districts for your business</p>
            </div>

            <!-- Import Form -->
            <div class="p-6 border-b border-gray-200 bg-gray-50">
                <h3 class="text-lg font-semibold mb-4">Import New Sub-District</h3>

                <form id="importForm" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @csrf
                    <div>
                        <label for="district" class="block text-sm font-medium text-gray-700 mb-2">District</label>
                        <select name="district" id="district" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">-- Select District --</option>
                            @foreach($availableDistricts as $district)
                            <option value="{{ $district }}">{{ $district }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="sub_district" class="block text-sm font-medium text-gray-700 mb-2">Sub-District</label>
                        <select name="sub_district" id="sub_district" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" disabled>
                            <option value="">-- Select District First --</option>
                        </select>
                    </div>

                    <div class="flex items-end">
                        <button type="submit" id="importBtn" class="w-full bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                            <span class="flex items-center justify-center">
                                <svg id="importSpinner" class="hidden animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span id="importBtnText">Import Sub-District</span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Imported Sub-Districts List -->
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4">Imported Sub-Districts</h3>

                @if($importedSubDistricts->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">District</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sub-District</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customers</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Villages</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($importedSubDistricts as $subDistrict)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $subDistrict->district }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $subDistrict->sub_district }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $subDistrict->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ ucfirst($subDistrict->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $subDistrict->getCustomersCount() }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $subDistrict->getVillages()->count() }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <button onclick="toggleStatus({{ $subDistrict->id }}, '{{ $subDistrict->status }}')"
                                            class="text-indigo-600 hover:text-indigo-900">
                                            {{ $subDistrict->status === 'active' ? 'Deactivate' : 'Activate' }}
                                        </button>
                                        <button onclick="deleteSubDistrict({{ $subDistrict->id }})"
                                            class="text-red-600 hover:text-red-900">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $importedSubDistricts->links() }}
                </div>
                @else
                <div class="text-center py-8">
                    <div class="text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No sub-districts imported</h3>
                        <p class="mt-1 text-sm text-gray-500">Get started by importing your first sub-district.</p>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Success/Error Messages -->
<div id="messageContainer" class="fixed top-4 right-4 z-50"></div>

@push('scripts')
<script>
    $(document).ready(function() {
        // Handle district selection
        $('#district').on('change', function() {
            const district = $(this).val();
            const subDistrictSelect = $('#sub_district');
            const importBtn = $('#importBtn');

            // Reset sub-district dropdown
            subDistrictSelect.html('<option value="">-- Loading... --</option>').prop('disabled', true);
            importBtn.prop('disabled', true);

            if (district) {
                // Fetch sub-districts
                $.ajax({
                    url: '{{ route("admin.business.sub-districts.get-sub-districts") }}',
                    method: 'GET',
                    data: {
                        district: district
                    },
                    success: function(response) {
                        console.log('Sub-districts response:', response);

                        subDistrictSelect.html('<option value="">-- Select Sub-District --</option>');

                        if (response && response.length > 0) {
                            $.each(response, function(index, subDistrict) {
                                subDistrictSelect.append(
                                    $('<option></option>').val(subDistrict.id).text(subDistrict.text)
                                );
                            });
                            subDistrictSelect.prop('disabled', false);
                        } else {
                            subDistrictSelect.html('<option value="">-- No Sub-Districts Found --</option>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching sub-districts:', error);
                        console.error('Response:', xhr.responseText);

                        subDistrictSelect.html('<option value="">-- Error Loading Sub-Districts --</option>');
                        showMessage('Failed to load sub-districts. Please try again.', 'error');
                    }
                });
            } else {
                subDistrictSelect.html('<option value="">-- Select District First --</option>');
            }
        });

        // Handle sub-district selection
        $('#sub_district').on('change', function() {
            const subDistrict = $(this).val();
            const importBtn = $('#importBtn');

            if (subDistrict) {
                importBtn.prop('disabled', false);
            } else {
                importBtn.prop('disabled', true);
            }
        });

        // Handle form submission
        $('#importForm').on('submit', function(e) {
            e.preventDefault();

            const district = $('#district').val();
            const subDistrict = $('#sub_district').val();
            const importBtn = $('#importBtn');
            const importSpinner = $('#importSpinner');
            const importBtnText = $('#importBtnText');

            if (!district || !subDistrict) {
                showMessage('Please select both district and sub-district.', 'error');
                return;
            }

            // Show loading state
            importBtn.prop('disabled', true);
            importSpinner.removeClass('hidden');
            importBtnText.text('Importing...');

            $.ajax({
                url: '{{ route("admin.business.sub-districts.store") }}',
                method: 'POST',
                data: {
                    _token: $('input[name="_token"]').val(),
                    district: district,
                    sub_district: subDistrict
                },
                success: function(response) {
                    if (response.success) {
                        showMessage(response.message, 'success');
                        // Reset form
                        $('#district').val('');
                        $('#sub_district').html('<option value="">-- Select District First --</option>').prop('disabled', true);
                        // Reload page to show new sub-district
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        showMessage(response.message, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error importing sub-district:', error);
                    showMessage('Error importing sub-district. Please try again.', 'error');
                },
                complete: function() {
                    // Reset loading state
                    importBtn.prop('disabled', false);
                    importSpinner.addClass('hidden');
                    importBtnText.text('Import Sub-District');
                }
            });
        });
    });

    // Toggle status function
    function toggleStatus(id, currentStatus) {
        const newStatus = currentStatus === 'active' ? 'inactive' : 'active';

        $.ajax({
            url: `/admin/business/sub-districts/${id}`,
            method: 'PUT',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                status: newStatus
            },
            success: function(response) {
                if (response.success) {
                    showMessage(response.message, 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showMessage(response.message, 'error');
                }
            },
            error: function() {
                showMessage('Error updating status. Please try again.', 'error');
            }
        });
    }

    // Delete sub-district function
    function deleteSubDistrict(id) {
        if (confirm('Are you sure you want to delete this sub-district?')) {
            $.ajax({
                url: `/admin/business/sub-districts/${id}`,
                method: 'DELETE',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        showMessage(response.message, 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        showMessage(response.message, 'error');
                    }
                },
                error: function() {
                    showMessage('Error deleting sub-district. Please try again.', 'error');
                }
            });
        }
    }

    // Show message function
    function showMessage(message, type) {
        const messageContainer = $('#messageContainer');
        const alertClass = type === 'success' ? 'bg-green-500' : 'bg-red-500';

        const messageHtml = `
        <div class="alert ${alertClass} text-white px-6 py-4 rounded-lg shadow-lg mb-4">
            <div class="flex items-center">
                <span class="text-sm font-medium">${message}</span>
                <button type="button" class="ml-4 text-white hover:text-gray-200" onclick="$(this).parent().parent().remove()">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
    `;

        messageContainer.append(messageHtml);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            messageContainer.find('.alert').last().fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }
</script>
@endpush
@endsection