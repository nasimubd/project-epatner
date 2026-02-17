@extends('super-admin.layouts.app')

@section('content')
<div class="bg-white shadow-md rounded-lg overflow-hidden">
    <div class="p-6 bg-white border-b border-gray-200">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Edit Location</h2>
            <p class="text-gray-600 mt-1">Update location information in the hierarchy system</p>
        </div>

        <form action="{{ route('super-admin.location-data.update', $location_datum) }}" method="POST" id="locationForm">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="district" class="block text-sm font-medium text-gray-700 mb-1">
                        District <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="district" id="district" value="{{ old('district', $location_datum->district) }}"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('district') border-red-500 @enderror"
                        required list="district-list">
                    <datalist id="district-list">
                        @foreach($districts as $district)
                        <option value="{{ $district }}">
                            @endforeach
                    </datalist>
                    @error('district')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="sub_district" class="block text-sm font-medium text-gray-700 mb-1">
                        Sub District <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="sub_district" id="sub_district" value="{{ old('sub_district', $location_datum->sub_district) }}"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('sub_district') border-red-500 @enderror"
                        required list="sub-district-list">
                    <datalist id="sub-district-list"></datalist>
                    @error('sub_district')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="village" class="block text-sm font-medium text-gray-700 mb-1">
                        Village <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="village" id="village" value="{{ old('village', $location_datum->village) }}"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('village') border-red-500 @enderror"
                        required list="village-list">
                    <datalist id="village-list"></datalist>
                    @error('village')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Preview Section -->
            <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                <h4 class="text-sm font-medium text-gray-700 mb-2">Location Preview:</h4>
                <p id="location-preview" class="text-gray-600">{{ $location_datum->full_location }}</p>
            </div>

            <!-- Warning if location is in use -->
            <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">
                            Important Notice
                        </h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <p>Changing this location data may affect existing customer records that reference this location.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-3">
                <a href="{{ route('super-admin.location-data.index') }}"
                    class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition">
                    Cancel
                </a>
                <button type="submit"
                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                    Update Location
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    // Auto-complete functionality
    document.getElementById('district').addEventListener('input', function() {
        const district = this.value;
        const subDistrictList = document.getElementById('sub-district-list');

        // Clear dependent lists
        subDistrictList.innerHTML = '';
        document.getElementById('village-list').innerHTML = '';

        if (district.length > 2) {
            fetch(`{{ route('super-admin.location-data.get-sub-districts') }}?district=${district}`)
                .then(response => response.json())
                .then(data => {
                    data.forEach(subDistrict => {
                        const option = document.createElement('option');
                        option.value = subDistrict;
                        subDistrictList.appendChild(option);
                    });
                });
        }

        updatePreview();
    });

    document.getElementById('sub_district').addEventListener('input', function() {
        const district = document.getElementById('district').value;
        const subDistrict = this.value;
        const villageList = document.getElementById('village-list');

        // Clear dependent list
        villageList.innerHTML = '';

        if (district && subDistrict.length > 2) {
            fetch(`{{ route('super-admin.location-data.get-villages') }}?district=${district}&sub_district=${subDistrict}`)
                .then(response => response.json())
                .then(data => {
                    data.forEach(village => {
                        const option = document.createElement('option');
                        option.value = village;
                        villageList.appendChild(option);
                    });
                });
        }

        updatePreview();
    });

    document.getElementById('village').addEventListener('input', updatePreview);

    function updatePreview() {
        const district = document.getElementById('district').value;
        const subDistrict = document.getElementById('sub_district').value;
        const village = document.getElementById('village').value;

        const parts = [village, subDistrict, district].filter(part => part.trim() !== '');
        const preview = parts.length > 0 ? parts.join(', ') : 'Enter location details to see preview';

        document.getElementById('location-preview').textContent = preview;
    }

    // Form validation
    document.getElementById('locationForm').addEventListener('submit', function(e) {
        const district = document.getElementById('district').value.trim();
        const subDistrict = document.getElementById('sub_district').value.trim();
        const village = document.getElementById('village').value.trim();

        if (!district || !subDistrict || !village) {
            e.preventDefault();
            alert('Please fill in all required fields.');
            return false;
        }

        // Show loading state
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Updating...';
    });

    // Initialize preview on page load
    updatePreview();
</script>
@endpush
@endsection