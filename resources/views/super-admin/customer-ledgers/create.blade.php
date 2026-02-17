@extends('admin.layouts.app')

@push('styles')
<!-- Move Select2 and FontAwesome to head section -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    .uppercase-input {
        text-transform: uppercase;
    }

    .input-warning {
        border-color: #f59e0b !important;
        background-color: #fef3c7;
    }

    .warning-text {
        color: #d97706;
        font-size: 0.75rem;
        margin-top: 0.25rem;
    }
</style>
@endpush

@section('content')
<!-- Add CSRF meta tag in the head section -->
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="bg-white shadow-md rounded-lg overflow-hidden">
    <div class="p-6 bg-white border-b border-gray-200">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Create New Customer/Supplier</h2>
            <p class="text-gray-600 mt-1">Add a new default customer or supplier to the system</p>
        </div>

        <form action="{{ route('super-admin.customer-ledgers.store') }}" method="POST" id="customerForm">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                        Name
                        <div class="flex items-center space-x-2 mt-1">
                            <label class="inline-flex items-center opacity-60 cursor-not-allowed">
                                <input type="checkbox" id="uppercaseToggle" class="form-checkbox h-4 w-4 text-blue-600" checked disabled>
                                <span class="ml-2 text-xs text-gray-600">Auto Uppercase (Always On)</span>
                            </label>
                        </div>
                    </label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500 uppercase-input @error('name') border-red-500 @enderror"
                        required
                        data-english-only="true"
                        placeholder="ENTER CUSTOMER NAME">
                    <div id="name-warning" class="warning-text hidden">
                        <i class="fas fa-exclamation-triangle"></i> Only English letters, numbers, and basic symbols are allowed
                    </div>
                    @error('name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="ledger_type" class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                    <select name="ledger_type" id="ledger_type"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('ledger_type') border-red-500 @enderror"
                        required>
                        <option value="Sundry Debtors (Customer)" {{ old('ledger_type') == 'Sundry Debtors (Customer)' ? 'selected' : '' }}>Sundry Debtors (Customer)</option>
                    </select>
                    @error('ledger_type')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">
                        Phone Number <span class="text-red-500">*</span>
                    </label>
                    <input type="tel"
                        name="phone"
                        id="phone"
                        value="{{ old('phone') }}"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('phone') border-red-500 @enderror"
                        data-english-only="true"
                        placeholder="Enter 11-digit phone number (e.g., 01712345678)"
                        pattern="[0-9]{11}"
                        maxlength="11"
                        minlength="11"
                        required>
                    <div id="phone-warning" class="warning-text hidden">
                        <i class="fas fa-exclamation-triangle"></i> Phone number must be exactly 11 digits (numbers only)
                    </div>
                    <div class="text-xs text-gray-500 mt-1">
                        Format: 11 digits only (e.g., 01712345678)
                    </div>
                    @error('phone')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>


                <!-- Location Information Section -->
                <div class="md:col-span-2">
                    <h3 class="text-lg font-medium text-gray-800 mb-4 border-b pb-2">Location Information</h3>
                </div>

                <div>
                    <label for="district" class="block text-sm font-medium text-gray-700 mb-1">
                        District <span class="text-red-500">*</span>
                    </label>
                    <select name="district" id="district"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('district') border-red-500 @enderror"
                        required>
                        <option value="">Select District</option>
                        @if(isset($districts) && $districts->count() > 0)
                        @foreach($districts as $district)
                        <option value="{{ $district }}" {{ old('district') == $district ? 'selected' : '' }}>
                            {{ $district }}
                        </option>
                        @endforeach
                        @else
                        <option value="" disabled>No districts available</option>
                        @endif
                    </select>
                    @error('district')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="sub_district" class="block text-sm font-medium text-gray-700 mb-1">
                        Sub District <span class="text-red-500">*</span>
                    </label>
                    <select name="sub_district" id="sub_district"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('sub_district') border-red-500 @enderror"
                        required>
                        <option value="">Select Sub District</option>
                    </select>
                    @error('sub_district')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="village" class="block text-sm font-medium text-gray-700 mb-1">
                        Village <span class="text-red-500">*</span>
                    </label>
                    <select name="village" id="village"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('village') border-red-500 @enderror"
                        required>
                        <option value="">Select Village</option>
                    </select>
                    @error('village')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>


                <div>
                    <label for="landmark" class="block text-sm font-medium text-gray-700 mb-1">
                        Landmark (Optional)
                        <div class="flex items-center space-x-2 mt-1">
                            <label class="inline-flex items-center opacity-60 cursor-not-allowed">
                                <input type="checkbox" id="landmarkUppercaseToggle" class="form-checkbox h-4 w-4 text-blue-600" checked disabled>
                                <span class="ml-2 text-xs text-gray-600">Auto Uppercase (Always On)</span>
                            </label>
                        </div>
                    </label>

                    <input type="text" name="landmark" id="landmark" value="{{ old('landmark') }}"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500 uppercase-input @error('landmark') border-red-500 @enderror"
                        placeholder="E.G., NEAR CITY HOSPITAL, OPPOSITE SCHOOL"
                        data-english-only="true">
                    <div id="landmark-warning" class="warning-text hidden">
                        <i class="fas fa-exclamation-triangle"></i> Only English letters, numbers, and basic symbols are allowed
                    </div>
                    @error('landmark')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Location Preview -->
                <div class="md:col-span-2">
                    <div class="p-4 bg-gray-50 rounded-lg">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Full Address Preview:</h4>
                        <p id="address-preview" class="text-gray-600 italic">Enter location details to see preview</p>
                    </div>
                </div>
            </div>

            <!-- Duplicate Detection Section -->
            <div id="duplicates-section" class="mb-6 hidden">
                <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3 flex-1">
                            <h3 class="text-sm font-medium text-yellow-800">Potential Duplicates Found</h3>
                            <div class="mt-2 text-sm text-yellow-700">
                                <p>We found similar customers that might be duplicates. Please review before creating:</p>
                            </div>
                            <div id="duplicates-list" class="mt-3 space-y-2">
                                <!-- Duplicates will be populated here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-3">
                <a href="{{ route('super-admin.customer-ledgers.index') }}"
                    class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition">
                    Cancel
                </a>
                <button type="submit" id="submitBtn"
                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                    <span id="submitIcon">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                    </span>
                    <span id="submitSpinner" class="hidden">
                        <svg class="w-4 h-4 inline mr-2 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span>
                    <span id="submitButtonText">Create Customer</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<!-- Load jQuery and Select2 in proper order -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        // Check if Select2 is available
        if (typeof $.fn.select2 === 'undefined') {
            console.error('Select2 is not loaded! Please check the CDN links.');
            alert('Select2 library failed to load. Please refresh the page or check your internet connection.');
            return;
        }

        console.log('Select2 loaded successfully');

        // Set up CSRF token for all AJAX requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // UPPERCASE AND ENGLISH-ONLY INPUT VALIDATION
        function initializeInputValidation() {
            // English-only validation patterns
            const patterns = {
                name: /^[A-Za-z0-9\s\.\-\_\&\(\)\,\'\"]*$/,
                phone: /^[0-9\+\-\(\)\s]*$/,
                landmark: /^[A-Za-z0-9\s\.\-\_\&\(\)\,\'\"]*$/
            };

            // Function to validate English-only input
            function validateEnglishOnly(input, pattern, warningId) {
                const value = input.value;
                const isValid = pattern.test(value);
                const warningElement = document.getElementById(warningId);

                if (!isValid && value.length > 0) {
                    input.classList.add('input-warning');
                    warningElement.classList.remove('hidden');
                    return false;
                } else {
                    input.classList.remove('input-warning');
                    warningElement.classList.add('hidden');
                    return true;
                }
            }

            // Function to convert to uppercase
            function convertToUppercase(input) {
                const cursorPosition = input.selectionStart;
                const oldValue = input.value;
                const newValue = oldValue.toUpperCase();

                input.value = newValue;

                // Restore cursor position
                input.setSelectionRange(cursorPosition, cursorPosition);
            }

            // Function to filter non-English characters in real-time
            function filterNonEnglishChars(input, pattern) {
                const cursorPosition = input.selectionStart;
                const oldValue = input.value;
                let newValue = '';

                // Filter character by character
                for (let i = 0; i < oldValue.length; i++) {
                    const char = oldValue[i];
                    if (pattern.test(char)) {
                        newValue += char;
                    }
                }

                if (newValue !== oldValue) {
                    input.value = newValue;
                    // Adjust cursor position
                    const newCursorPosition = Math.min(cursorPosition, newValue.length);
                    input.setSelectionRange(newCursorPosition, newCursorPosition);
                }
            }

            // Initialize validation for each input field
            const inputConfigs = [{
                    id: 'name',
                    pattern: patterns.name,
                    warning: 'name-warning'
                },
                {
                    id: 'phone',
                    pattern: patterns.phone,
                    warning: 'phone-warning'
                },
                {
                    id: 'landmark',
                    pattern: patterns.landmark,
                    warning: 'landmark-warning'
                }
            ];

            inputConfigs.forEach(config => {
                const input = document.getElementById(config.id);
                if (!input) return;

                // Real-time input validation and filtering
                input.addEventListener('input', function(e) {
                    // Filter non-English characters immediately
                    filterNonEnglishChars(this, config.pattern);

                    // Apply uppercase if enabled and not phone field
                    if (config.id !== 'phone') {
                        const uppercaseToggle = config.id === 'landmark' ?
                            document.getElementById('landmarkUppercaseToggle') :
                            document.getElementById('uppercaseToggle');

                        if (uppercaseToggle && uppercaseToggle.checked) {
                            convertToUppercase(this);
                        }
                    }

                    // Validate after filtering
                    validateEnglishOnly(this, config.pattern, config.warning);
                });

                // Prevent pasting non-English content
                input.addEventListener('paste', function(e) {
                    e.preventDefault();

                    // Get pasted text
                    const pastedText = (e.clipboardData || window.clipboardData).getData('text');
                    let filteredText = '';

                    // Filter pasted text
                    for (let i = 0; i < pastedText.length; i++) {
                        const char = pastedText[i];
                        if (config.pattern.test(char)) {
                            filteredText += char;
                        }
                    }

                    // Apply uppercase if enabled and not phone field
                    if (config.id !== 'phone') {
                        const uppercaseToggle = config.id === 'landmark' ?
                            document.getElementById('landmarkUppercaseToggle') :
                            document.getElementById('uppercaseToggle');

                        if (uppercaseToggle && uppercaseToggle.checked) {
                            filteredText = filteredText.toUpperCase();
                        }
                    }

                    // Insert filtered text
                    const cursorPosition = this.selectionStart;
                    const currentValue = this.value;
                    const newValue = currentValue.substring(0, cursorPosition) +
                        filteredText +
                        currentValue.substring(this.selectionEnd);

                    this.value = newValue;
                    this.setSelectionRange(cursorPosition + filteredText.length, cursorPosition + filteredText.length);

                    // Trigger input event for other validations
                    this.dispatchEvent(new Event('input'));
                });

                // Prevent drag and drop of non-English content
                input.addEventListener('drop', function(e) {
                    e.preventDefault();

                    const droppedText = e.dataTransfer.getData('text');
                    let filteredText = '';

                    // Filter dropped text
                    for (let i = 0; i < droppedText.length; i++) {
                        const char = droppedText[i];
                        if (config.pattern.test(char)) {
                            filteredText += char;
                        }
                    }

                    // Apply uppercase if enabled and not phone field
                    if (config.id !== 'phone') {
                        const uppercaseToggle = config.id === 'landmark' ?
                            document.getElementById('landmarkUppercaseToggle') :
                            document.getElementById('uppercaseToggle');

                        if (uppercaseToggle && uppercaseToggle.checked) {
                            filteredText = filteredText.toUpperCase();
                        }
                    }

                    this.value = filteredText;
                    this.dispatchEvent(new Event('input'));
                });
            });

            // Handle uppercase toggle changes
            document.getElementById('uppercaseToggle').addEventListener('change', function() {
                const nameInput = document.getElementById('name');
                if (this.checked && nameInput.value) {
                    nameInput.value = nameInput.value.toUpperCase();
                    nameInput.dispatchEvent(new Event('input'));
                }

                // Update CSS class
                if (this.checked) {
                    nameInput.classList.add('uppercase-input');
                } else {
                    nameInput.classList.remove('uppercase-input');
                }
            });

            document.getElementById('landmarkUppercaseToggle').addEventListener('change', function() {
                const landmarkInput = document.getElementById('landmark');
                if (this.checked && landmarkInput.value) {
                    landmarkInput.value = landmarkInput.value.toUpperCase();
                    landmarkInput.dispatchEvent(new Event('input'));
                }

                // Update CSS class
                if (this.checked) {
                    landmarkInput.classList.add('uppercase-input');
                } else {
                    landmarkInput.classList.remove('uppercase-input');
                }
            });

            console.log('Input validation and uppercase conversion initialized');
        }

        // Initialize input validation
        initializeInputValidation();

        // Global functions for duplicate display
        function getInitials(name) {
            return name.split(' ').map(word => word.charAt(0).toUpperCase()).slice(0, 2).join('');
        }

        function getAvatarColor(name) {
            const colors = ['bg-red-500', 'bg-blue-500', 'bg-green-500', 'bg-yellow-500', 'bg-purple-500'];
            const index = name.charCodeAt(0) % colors.length;
            return colors[index];
        }

        function getFullLocation(customer) {
            const parts = [customer.village, customer.sub_district, customer.district].filter(Boolean);
            let location = parts.join(', ');
            if (customer.landmark) {
                location += ` (Near: ${customer.landmark})`;
            }
            return location;
        }

        function getSimilarityBadgeClass(similarity) {
            if (similarity >= 90) return 'bg-red-100 text-red-800';
            if (similarity >= 70) return 'bg-yellow-100 text-yellow-800';
            return 'bg-blue-100 text-blue-800';
        }

        function viewCustomer(customerId) {
            window.open(`{{ route('super-admin.customer-ledgers.show', '') }}/${customerId}`, '_blank');
        }

        // Initialize Select2 for location dropdowns with error handling
        try {
            $('#district').select2({
                placeholder: 'Select District',
                allowClear: true,
                width: '100%'
            });

            $('#sub_district').select2({
                placeholder: 'Select Sub District',
                allowClear: true,
                width: '100%'
            });

            $('#village').select2({
                placeholder: 'Select Village',
                allowClear: true,
                width: '100%'
            });

            console.log('Select2 initialized successfully for all dropdowns');
        } catch (error) {
            console.error('Error initializing Select2:', error);
            alert('Error initializing dropdown menus. Please refresh the page.');
        }

        // District change handler
        $('#district').on('change', function() {
            const district = $(this).val();
            const subDistrictSelect = $('#sub_district');
            const villageSelect = $('#village');

            console.log('District changed to:', district);

            // Clear and reset dependent dropdowns
            subDistrictSelect.empty().append('<option value="">Select Sub District</option>');
            villageSelect.empty().append('<option value="">Select Village</option>');

            if (district) {
                // Show loading
                subDistrictSelect.prop('disabled', true);
                subDistrictSelect.append('<option value="">Loading...</option>');

                console.log('Fetching sub-districts for district:', district);
                // Fetch sub-districts
                $.ajax({
                    url: '{{ route("super-admin.customer-ledgers.get-sub-districts") }}',
                    type: 'GET',
                    data: {
                        district: district
                    },
                    success: function(data) {
                        console.log('Sub-districts received:', data);
                        subDistrictSelect.empty().append('<option value="">Select Sub District</option>');

                        if (data && data.length > 0) {
                            $.each(data, function(index, item) {
                                subDistrictSelect.append('<option value="' + item.id + '">' + item.text + '</option>');
                            });
                        } else {
                            subDistrictSelect.append('<option value="">No sub-districts found</option>');
                        }
                        subDistrictSelect.prop('disabled', false);
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading sub-districts:', error);
                        console.error('Response:', xhr.responseText);
                        subDistrictSelect.empty().append('<option value="">Error loading sub-districts</option>');
                        subDistrictSelect.prop('disabled', false);
                    }
                });
            }

            updateAddressPreview();
            checkForDuplicates();
        });

        // Sub District change handler
        $('#sub_district').on('change', function() {
            const district = $('#district').val();
            const subDistrict = $(this).val();
            const villageSelect = $('#village');

            console.log('Sub-district changed to:', subDistrict);

            // Clear village dropdown
            villageSelect.empty().append('<option value="">Select Village</option>');

            if (district && subDistrict) {
                // Show loading
                villageSelect.prop('disabled', true);
                villageSelect.append('<option value="">Loading...</option>');

                console.log('Fetching villages for district:', district, 'sub-district:', subDistrict);

                // Fetch villages
                $.ajax({
                    url: '{{ route("super-admin.customer-ledgers.get-villages") }}',
                    type: 'GET',
                    data: {
                        district: district,
                        sub_district: subDistrict
                    },
                    success: function(data) {
                        console.log('Villages received:', data);
                        villageSelect.empty().append('<option value="">Select Village</option>');

                        if (data && data.length > 0) {
                            $.each(data, function(index, item) {
                                villageSelect.append('<option value="' + item.id + '">' + item.text + '</option>');
                            });
                        } else {
                            villageSelect.append('<option value="">No villages found</option>');
                        }
                        villageSelect.prop('disabled', false);
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading villages:', error);
                        console.error('Response:', xhr.responseText);
                        villageSelect.empty().append('<option value="">Error loading villages</option>');
                        villageSelect.prop('disabled', false);
                    }
                });
            }

            updateAddressPreview();
            checkForDuplicates();
        });

        // Village change handler
        $('#village').on('change', function() {
            console.log('Village changed to:', $(this).val());
            updateAddressPreview();
            checkForDuplicates();
        });

        // Name and phone change handlers for duplicate detection
        let duplicateCheckTimeout;
        $('#name, #phone').on('input', function() {
            clearTimeout(duplicateCheckTimeout);
            duplicateCheckTimeout = setTimeout(checkForDuplicates, 500); // Debounce for 500ms
        });

        // Landmark change handler
        $('#landmark').on('input', updateAddressPreview);

        function updateAddressPreview() {
            const landmark = $('#landmark').val();
            const village = $('#village option:selected').text();
            const subDistrict = $('#sub_district option:selected').text();
            const district = $('#district option:selected').text();

            const addressParts = [];

            if (landmark && landmark.trim()) {
                addressParts.push(landmark.trim());
            }

            const locationParts = [];
            if (village && village !== 'Select Village') locationParts.push(village);
            if (subDistrict && subDistrict !== 'Select Sub District') locationParts.push(subDistrict);
            if (district && district !== 'Select District') locationParts.push(district);

            if (locationParts.length > 0) {
                addressParts.push(locationParts.join(', '));
            }

            const preview = addressParts.length > 0 ? addressParts.join(', ') : 'Enter location details to see preview';
            $('#address-preview').text(preview);
        }

        // DUPLICATE DETECTION FUNCTIONALITY
        function checkForDuplicates() {
            const name = $('#name').val();
            const district = $('#district').val();
            const subDistrict = $('#sub_district').val();
            const village = $('#village').val();
            const phone = $('#phone').val();

            console.log('Checking for duplicates with data:', {
                name: name,
                district: district,
                subDistrict: subDistrict,
                village: village,
                phone: phone
            });

            // Clear previous warnings
            $('#duplicates-section').addClass('hidden');

            // Only check if we have minimum data
            if ((!name || name.length < 3) && (!phone || phone.length < 10)) {
                console.log('Insufficient data for duplicate check');
                return;
            }

            // Show checking indicator
            showDuplicateCheckingIndicator();

            $.ajax({
                url: '{{ route("super-admin.customer-ledgers.check-duplicates") }}',
                type: 'POST',
                data: {
                    name: name,
                    district: district,
                    sub_district: subDistrict,
                    village: village,
                    phone: phone,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(duplicates) {
                    console.log('Duplicate check response:', duplicates);
                    hideDuplicateCheckingIndicator();

                    if (duplicates && duplicates.length > 0) {
                        displayDuplicates(duplicates);
                        $('#duplicates-section').removeClass('hidden');
                    } else {
                        $('#duplicates-section').addClass('hidden');
                        enableSubmitButton();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error checking for duplicates:', error);
                    console.error('Response:', xhr.responseText);
                    hideDuplicateCheckingIndicator();

                    // Show error message to user
                    showErrorMessage('Unable to check for duplicates. Please verify manually before creating.');

                    // Enable submit but with warning
                    enableSubmitButton();
                }
            });
        }

        function showDuplicateCheckingIndicator() {
            const indicator = `
        <div id="duplicate-checking" class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="flex items-center">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-blue-700">Checking for duplicate customers...</span>
            </div>
        </div>
    `;

            // Remove existing indicator
            $('#duplicate-checking').remove();

            // Add new indicator before the form
            $('#customerForm').before(indicator);
        }

        function hideDuplicateCheckingIndicator() {
            $('#duplicate-checking').remove();
        }

        function showErrorMessage(message) {
            const errorHtml = `
        <div id="duplicate-error" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-red-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                <span class="text-red-700">${message}</span>
            </div>
        </div>
    `;

            $('#duplicate-error').remove();
            $('#customerForm').before(errorHtml);

            // Auto-hide after 5 seconds
            setTimeout(() => {
                $('#duplicate-error').fadeOut();
            }, 5000);
        }

        function enableSubmitButton() {
            const submitBtn = $('#submitBtn');
            submitBtn.prop('disabled', false)
                .removeClass('bg-red-600 cursor-not-allowed')
                .addClass('bg-blue-600 hover:bg-blue-700');
            $('#submitButtonText').text('Create Customer');
        }

        function disableSubmitButton(reason) {
            const submitBtn = $('#submitBtn');
            submitBtn.prop('disabled', true)
                .removeClass('bg-blue-600 hover:bg-blue-700')
                .addClass('bg-red-600 cursor-not-allowed');
            $('#submitButtonText').text(reason);
        }

        // Display duplicates function
        function displayDuplicates(duplicates) {
            const duplicatesList = $('#duplicates-list');
            duplicatesList.empty();

            duplicates.forEach(function(duplicate) {
                const customer = duplicate.customer;
                const similarity = duplicate.similarity;
                const reason = duplicate.reason;

                const duplicateHtml = `
            <div class="p-3 bg-white border border-yellow-300 rounded-lg duplicate-card">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <div class="flex items-center space-x-2">
                            <div class="w-8 h-8 ${getAvatarColor(customer.ledger_name)} rounded-full flex items-center justify-center text-white text-xs font-bold customer-avatar">
                                ${getInitials(customer.ledger_name)}
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">${customer.ledger_name}</h4>
                                <p class="text-sm text-gray-600">${customer.type || 'Customer'}</p>
                            </div>
                        </div>
                        
                       <div class="mt-2 space-y-1">
                            ${customer.contact_number ? `
                                <div class="flex items-center text-sm text-gray-600">
                                    <i class="fas fa-phone w-4"></i>
                                    <span class="ml-2">${customer.contact_number}</span>
                                </div>
                            ` : ''}
                            
                            ${getFullLocation(customer) ? `
                                <div class="flex items-center text-sm text-gray-600">
                                    <i class="fas fa-map-marker-alt w-4"></i>
                                    <span class="ml-2">${getFullLocation(customer)}</span>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                    
                    <div class="text-right">
                        <div class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium similarity-badge ${getSimilarityBadgeClass(similarity)}">
                            ${similarity}% Match
                        </div>
                        <p class="text-xs text-gray-500 mt-1">${reason}</p>
                    </div>
                </div>
                
                <div class="mt-3 flex space-x-2">
                    <button type="button" onclick="viewCustomer(${customer.ledger_id})" 
                            class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded hover:bg-blue-200 action-btn">
                        View Details
                    </button>
                </div>
            </div>
        `;

                duplicatesList.append(duplicateHtml);
            });
        }

        // Enhanced form submission with validation
        $('#customerForm').on('submit', function(e) {
            const duplicatesVisible = !$('#duplicates-section').hasClass('hidden');
            const phone = $('#phone').val();

            console.log('Form submission started', {
                duplicatesVisible: duplicatesVisible,
                phone: phone
            });

            // Validate all English-only fields before submission
            let hasValidationErrors = false;
            const inputConfigs = [{
                    id: 'name',
                    pattern: /^[A-Za-z0-9\s\.\-\_\&\(\)\,\'\"]*$/,
                    warning: 'name-warning'
                },
                {
                    id: 'phone',
                    pattern: /^[0-9\+\-\(\)\s]*$/,
                    warning: 'phone-warning'
                },
                {
                    id: 'landmark',
                    pattern: /^[A-Za-z0-9\s\.\-\_\&\(\)\,\'\"]*$/,
                    warning: 'landmark-warning'
                }
            ];

            inputConfigs.forEach(config => {
                const input = document.getElementById(config.id);
                if (input && input.value) {
                    if (!config.pattern.test(input.value)) {
                        hasValidationErrors = true;
                        input.classList.add('input-warning');
                        document.getElementById(config.warning).classList.remove('hidden');
                    }
                }
            });

            if (hasValidationErrors) {
                e.preventDefault();
                alert('❌ Please fix the validation errors before submitting the form.');
                return false;
            }

            // Show loading state
            $('#submitIcon').addClass('hidden');
            $('#submitSpinner').removeClass('hidden');
            $('#submitButtonText').text('Creating...');
            $('#submitBtn').prop('disabled', true);

            // Final client-side validation for phone duplicates
            if (phone && phone.length >= 10) {
                const phoneExists = checkPhoneExistsSync(phone);
                if (phoneExists) {
                    e.preventDefault();
                    alert('❌ Phone number already exists. Please use a different number.');
                    resetSubmitButton();
                    return false;
                }
            }

            if (duplicatesVisible) {
                const confirmed = confirm('⚠️ Potential duplicates were found!\n\nAre you sure you want to create this customer anyway?\n\nClick OK to proceed or Cancel to review the duplicates.');
                if (!confirmed) {
                    e.preventDefault();
                    resetSubmitButton();
                    return false;
                } else {
                    // Add hidden input to indicate duplicates were ignored
                    if (!$('#ignore_duplicates').length) {
                        $(this).append('<input type="hidden" id="ignore_duplicates" name="ignore_duplicates" value="1">');
                    }
                }
            }
        });

        function resetSubmitButton() {
            $('#submitIcon').removeClass('hidden');
            $('#submitSpinner').addClass('hidden');
            $('#submitButtonText').text('Create Customer');
            $('#submitBtn').prop('disabled', false);
        }

        // Synchronous phone check function
        function checkPhoneExistsSync(phone) {
            let exists = false;
            $.ajax({
                url: '{{ route("super-admin.customer-ledgers.check-duplicates") }}',
                type: 'POST',
                async: false, // Synchronous for final validation
                data: {
                    phone: phone,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(duplicates) {
                    exists = duplicates.some(d => d.type === 'phone');
                },
                error: function(xhr, status, error) {
                    console.error('Error checking phone existence:', error);
                    // Assume phone doesn't exist if check fails
                    exists = false;
                }
            });
            return exists;
        }

        // Load sub-districts and villages if old values exist (for form validation errors)
        var oldDistrict = '{{ old("district") }}';
        var oldSubDistrict = '{{ old("sub_district") }}';
        var oldVillage = '{{ old("village") }}';

        console.log('Old form values:', {
            district: oldDistrict,
            subDistrict: oldSubDistrict,
            village: oldVillage
        });

        if (oldDistrict) {
            $('#district').val(oldDistrict).trigger('change');
            if (oldSubDistrict) {
                setTimeout(function() {
                    $('#sub_district').val(oldSubDistrict).trigger('change');
                    if (oldVillage) {
                        setTimeout(function() {
                            $('#village').val(oldVillage);
                            updateAddressPreview();
                        }, 1000);
                    }
                }, 500);
            }
        }

        // Initial address preview update
        updateAddressPreview();

        console.log('Customer form initialization completed');
    });

    // QR Code functionality (outside of document ready)
    document.addEventListener('DOMContentLoaded', function() {
        console.log('QR Code functionality initialized');

        const qrModal = document.getElementById('qrModal');
        const qrCodeContainer = document.getElementById('qrCodeContainer');
        const qrModalTitle = document.getElementById('qrModalTitle');
        const closeQrModal = document.getElementById('closeQrModal');

        // Show QR Code
        document.querySelectorAll('.show-qr-btn').forEach(button => {
            button.addEventListener('click', function() {
                const qrCode = this.dataset.qrCode;
                const customerName = this.dataset.customerName;

                if (qrModalTitle) qrModalTitle.textContent = `QR Code - ${customerName}`;
                if (qrCodeContainer) qrCodeContainer.innerHTML = qrCode;
                if (qrModal) qrModal.classList.remove('hidden');
            });
        });

        // Generate QR Code
        document.querySelectorAll('.generate-qr-btn').forEach(button => {
            button.addEventListener('click', function() {
                const customerId = this.dataset.customerId;
                const originalButton = this;

                // Show loading state
                originalButton.innerHTML = '<svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>';
                originalButton.disabled = true;

                // Make AJAX request to generate QR code
                fetch(`{{ route('super-admin.customer-ledgers.index') }}/${customerId}/generate-qr`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Reload the page to show the new QR code
                            location.reload();
                        } else {
                            alert('Error generating QR code: ' + data.message);
                            // Restore button
                            originalButton.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>';
                            originalButton.disabled = false;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error generating QR code');
                        // Restore button
                        originalButton.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>';
                        originalButton.disabled = false;
                    });
            });
        });

        // Close QR modal
        if (closeQrModal) {
            closeQrModal.addEventListener('click', function() {
                if (qrModal) qrModal.classList.add('hidden');
            });
        }

        // Close modal when clicking outside
        if (qrModal) {
            qrModal.addEventListener('click', function(e) {
                if (e.target === qrModal) {
                    qrModal.classList.add('hidden');
                }
            });
        }
    });

    // Global function to make viewCustomer available
    window.viewCustomer = function(customerId) {
        window.open(`{{ route('super-admin.customer-ledgers.show', '') }}/${customerId}`, '_blank');
    };

    // Debug information
    console.log('Customer Ledger Create Form Script Loaded');
    console.log('Available routes:', {
        checkDuplicates: '{{ route("super-admin.customer-ledgers.check-duplicates") }}',
        getSubDistricts: '{{ route("super-admin.customer-ledgers.get-sub-districts") }}',
        getVillages: '{{ route("super-admin.customer-ledgers.get-villages") }}',
        store: '{{ route("super-admin.customer-ledgers.store") }}'
    });

    // Additional validation functions for better user experience
    function showValidationTooltip(element, message) {
        // Create tooltip element
        const tooltip = document.createElement('div');
        tooltip.className = 'absolute z-50 px-3 py-2 text-sm text-white bg-red-600 rounded-lg shadow-lg';
        tooltip.style.top = '-40px';
        tooltip.style.left = '0';
        tooltip.textContent = message;

        // Position tooltip relative to input
        element.style.position = 'relative';
        element.parentNode.appendChild(tooltip);

        // Remove tooltip after 3 seconds
        setTimeout(() => {
            if (tooltip.parentNode) {
                tooltip.parentNode.removeChild(tooltip);
            }
        }, 3000);
    }

    // Enhanced keyboard event handling for better UX
    document.addEventListener('keydown', function(e) {
        const activeElement = document.activeElement;

        // Handle specific key combinations for input fields
        if (activeElement && activeElement.hasAttribute('data-english-only')) {
            // Prevent common non-English input methods
            if (e.altKey || e.ctrlKey) {
                // Allow common shortcuts like Ctrl+C, Ctrl+V, etc.
                const allowedKeys = ['c', 'v', 'x', 'a', 'z', 'y'];
                if (!allowedKeys.includes(e.key.toLowerCase())) {
                    // Don't prevent, but warn user
                    console.log('Non-standard key combination detected');
                }
            }
        }
    });

    // Form auto-save functionality (optional)
    let autoSaveTimeout;

    function autoSaveFormData() {
        clearTimeout(autoSaveTimeout);
        autoSaveTimeout = setTimeout(() => {
            const formData = {
                name: document.getElementById('name').value,
                phone: document.getElementById('phone').value,
                landmark: document.getElementById('landmark').value,
                district: document.getElementById('district').value,
                sub_district: document.getElementById('sub_district').value,
                village: document.getElementById('village').value,
                timestamp: new Date().toISOString()
            };

            // Save to localStorage for recovery
            localStorage.setItem('customer_form_draft', JSON.stringify(formData));
            console.log('Form data auto-saved');
        }, 2000);
    }

    // Attach auto-save to form inputs
    document.querySelectorAll('#customerForm input, #customerForm select').forEach(input => {
        input.addEventListener('input', autoSaveFormData);
        input.addEventListener('change', autoSaveFormData);
    });

    // Load draft data on page load
    window.addEventListener('load', function() {
        const savedDraft = localStorage.getItem('customer_form_draft');
        if (savedDraft) {
            try {
                const draftData = JSON.parse(savedDraft);
                const draftAge = new Date() - new Date(draftData.timestamp);

                // Only restore if draft is less than 1 hour old
                if (draftAge < 3600000) {
                    const shouldRestore = confirm('We found a saved draft of your form. Would you like to restore it?');
                    if (shouldRestore) {
                        // Restore form data
                        if (draftData.name) document.getElementById('name').value = draftData.name;
                        if (draftData.phone) document.getElementById('phone').value = draftData.phone;
                        if (draftData.landmark) document.getElementById('landmark').value = draftData.landmark;

                        console.log('Form draft restored');
                    }
                }
            } catch (error) {
                console.error('Error restoring form draft:', error);
            }
        }
    });

    // Clear draft on successful form submission
    document.getElementById('customerForm').addEventListener('submit', function() {
        localStorage.removeItem('customer_form_draft');
    });

    console.log('Enhanced customer form with uppercase and English-only validation loaded successfully');
</script>
@endpush