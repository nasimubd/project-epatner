@extends('super-admin.layouts.app')

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
            <h2 class="text-2xl font-bold text-gray-800">Edit Customer/Supplier</h2>
            <p class="text-gray-600 mt-1">Update customer or supplier information</p>
        </div>

        <form action="{{ route('super-admin.customer-ledgers.update', $customerLedger) }}" method="POST" id="customerForm">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="ledger_name" class="block text-sm font-medium text-gray-700 mb-1">
                        Name
                        <div class="flex items-center space-x-2 mt-1">
                            <label class="inline-flex items-center opacity-60 cursor-not-allowed">
                                <input type="checkbox" id="uppercaseToggle" class="form-checkbox h-4 w-4 text-blue-600" checked disabled>
                                <span class="ml-2 text-xs text-gray-600">Auto Uppercase (Always On)</span>
                            </label>
                        </div>
                    </label>
                    <input type="text" name="ledger_name" id="ledger_name" value="{{ old('ledger_name', $customerLedger->ledger_name) }}"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500 uppercase-input @error('ledger_name') border-red-500 @enderror"
                        required
                        data-english-only="true"
                        placeholder="ENTER CUSTOMER NAME">
                    <div id="ledger_name-warning" class="warning-text hidden">
                        <i class="fas fa-exclamation-triangle"></i> Only English letters, numbers, and basic symbols are allowed
                    </div>
                    @error('ledger_name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                    <select name="type" id="type"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('type') border-red-500 @enderror"
                        required>
                        <option value="Sundry Debtors (Customer)" {{ old('type', $customerLedger->type) == 'Sundry Debtors (Customer)' ? 'selected' : '' }}>Sundry Debtors (Customer)</option>
                    </select>
                    @error('type')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="contact_number" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                    <input type="text" name="contact_number" id="contact_number" value="{{ old('contact_number', $customerLedger->contact_number) }}"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('contact_number') border-red-500 @enderror"
                        data-english-only="true"
                        placeholder="Enter phone number">
                    <div id="contact_number-warning" class="warning-text hidden">
                        <i class="fas fa-exclamation-triangle"></i> Only numbers, +, -, (, ), and spaces are allowed
                    </div>
                    @error('contact_number')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Location Information Section -->
                <div class="md:col-span-2">
                    <h3 class="text-lg font-medium text-gray-800 mb-4 border-b pb-2">Location Information</h3>
                </div>

                <div>
                    <label for="district" class="block text-sm font-medium text-gray-700 mb-1">District</label>
                    <select name="district" id="district"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('district') border-red-500 @enderror">
                        <option value="">Select District</option>
                        @if(isset($districts) && $districts->count() > 0)
                        @foreach($districts as $district)
                        <option value="{{ $district }}" {{ old('district', $customerLedger->district ?? '') == $district ? 'selected' : '' }}>
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
                    <label for="sub_district" class="block text-sm font-medium text-gray-700 mb-1">Sub District</label>
                    <input type="text" name="sub_district" id="sub_district" value="{{ old('sub_district', $customerLedger->sub_district) }}"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500 uppercase-input @error('sub_district') border-red-500 @enderror"
                        data-english-only="true"
                        placeholder="ENTER SUB DISTRICT">
                    <div id="sub_district-warning" class="warning-text hidden">
                        <i class="fas fa-exclamation-triangle"></i> Only English letters, numbers, and basic symbols are allowed
                    </div>
                    @error('sub_district')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="village" class="block text-sm font-medium text-gray-700 mb-1">Village</label>
                    <input type="text" name="village" id="village" value="{{ old('village', $customerLedger->village ?? '') }}"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500 uppercase-input @error('village') border-red-500 @enderror"
                        data-english-only="true"
                        placeholder="ENTER VILLAGE">
                    <div id="village-warning" class="warning-text hidden">
                        <i class="fas fa-exclamation-triangle"></i> Only English letters, numbers, and basic symbols are allowed
                    </div>
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
                    <input type="text" name="landmark" id="landmark" value="{{ old('landmark', $customerLedger->landmark ?? '') }}"
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
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Location Preview:</h4>
                        <p id="address-preview" class="text-gray-600 italic">Current location information</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-3">
                <a href="{{ route('super-admin.customer-ledgers.index') }}" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition">
                    Cancel
                </a>
                <button type="submit" id="submitBtn" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                    <span id="submitIcon">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </span>
                    <span id="submitSpinner" class="hidden">
                        <svg class="w-4 h-4 inline mr-2 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span>
                    <span id="submitButtonText">Update Customer</span>
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
                ledger_name: /^[A-Za-z0-9\s\.\-\_\&\(\)\,\'\"]*$/,
                contact_number: /^[0-9\+\-\(\)\s]*$/,
                sub_district: /^[A-Za-z0-9\s\.\-\_\&\(\)\,\'\"]*$/,
                village: /^[A-Za-z0-9\s\.\-\_\&\(\)\,\'\"]*$/,
                landmark: /^[A-Za-z0-9\s\.\-\_\&\(\)\,\'\"]*$/,
                location: /^[A-Za-z0-9\s\.\-\_\&\(\)\,\'\"]*$/
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

                if (oldValue !== newValue) {
                    input.value = newValue;
                    // Restore cursor position
                    input.setSelectionRange(cursorPosition, cursorPosition);
                }
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
                    id: 'ledger_name',
                    pattern: patterns.ledger_name,
                    warning: 'ledger_name-warning',
                    uppercase: true
                },
                {
                    id: 'contact_number',
                    pattern: patterns.contact_number,
                    warning: 'contact_number-warning',
                    uppercase: false
                },
                {
                    id: 'sub_district',
                    pattern: patterns.sub_district,
                    warning: 'sub_district-warning',
                    uppercase: true
                },
                {
                    id: 'village',
                    pattern: patterns.village,
                    warning: 'village-warning',
                    uppercase: true
                },
                {
                    id: 'landmark',
                    pattern: patterns.landmark,
                    warning: 'landmark-warning',
                    uppercase: true
                },
                {
                    id: 'location',
                    pattern: patterns.location,
                    warning: 'location-warning',
                    uppercase: true
                }
            ];

            inputConfigs.forEach(config => {
                const input = document.getElementById(config.id);
                if (!input) return;

                // Convert existing value to uppercase on page load
                if (config.uppercase && input.value) {
                    input.value = input.value.toUpperCase();
                }

                // Real-time input validation and filtering
                input.addEventListener('input', function(e) {
                    // Filter non-English characters immediately
                    filterNonEnglishChars(this, config.pattern);

                    // Apply uppercase conversion
                    if (config.uppercase) {
                        convertToUppercase(this);
                    }

                    // Validate after filtering
                    validateEnglishOnly(this, config.pattern, config.warning);

                    // Update address preview if location fields changed
                    if (['sub_district', 'village', 'landmark', 'location'].includes(config.id)) {
                        updateAddressPreview();
                    }
                });

                // Handle keyup event for better responsiveness
                input.addEventListener('keyup', function(e) {
                    if (config.uppercase) {
                        convertToUppercase(this);
                    }
                });

                // Handle focus event to ensure uppercase on existing content
                input.addEventListener('focus', function(e) {
                    if (config.uppercase && this.value) {
                        this.value = this.value.toUpperCase();
                    }
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

                    // Apply uppercase if enabled
                    if (config.uppercase) {
                        filteredText = filteredText.toUpperCase();
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

                    // Apply uppercase if enabled
                    if (config.uppercase) {
                        filteredText = filteredText.toUpperCase();
                    }

                    this.value = filteredText;
                    this.dispatchEvent(new Event('input'));
                });
            });

            console.log('Input validation and uppercase conversion initialized');
        }

        // Initialize input validation
        initializeInputValidation();

        // Initialize Select2 for district dropdown
        try {
            $('#district').select2({
                placeholder: 'Select District',
                allowClear: true,
                width: '100%'
            });

            console.log('Select2 initialized successfully for district dropdown');
        } catch (error) {
            console.error('Error initializing Select2:', error);
            alert('Error initializing dropdown menus. Please refresh the page.');
        }

        // District change handler
        $('#district').on('change', function() {
            const district = $(this).val();
            console.log('District changed to:', district);
            updateAddressPreview();
        });

        // Additional event listeners for location fields to update preview
        $('#sub_district, #village, #landmark, #location').on('input', function() {
            updateAddressPreview();
        });

        function updateAddressPreview() {
            const landmark = $('#landmark').val();
            const village = $('#village').val();
            const subDistrict = $('#sub_district').val();
            const district = $('#district option:selected').text();
            const location = $('#location').val();

            const addressParts = [];

            if (landmark && landmark.trim()) {
                addressParts.push(landmark.trim());
            }

            const locationParts = [];
            if (village && village.trim()) locationParts.push(village.trim());
            if (subDistrict && subDistrict.trim()) locationParts.push(subDistrict.trim());
            if (district && district !== 'Select District' && district !== '') locationParts.push(district);

            if (locationParts.length > 0) {
                addressParts.push(locationParts.join(', '));
            }

            if (location && location.trim()) {
                addressParts.push('Full Address: ' + location.trim());
            }

            const preview = addressParts.length > 0 ? addressParts.join(' | ') : 'Enter location details to see preview';
            $('#address-preview').text(preview);
        }

        // Enhanced form submission with validation
        $('#customerForm').on('submit', function(e) {
            console.log('Form submission started');

            // Validate all English-only fields before submission
            let hasValidationErrors = false;
            const inputConfigs = [{
                    id: 'ledger_name',
                    pattern: /^[A-Za-z0-9\s\.\-\_\&\(\)\,\'\"]*$/,
                    warning: 'ledger_name-warning'
                },
                {
                    id: 'contact_number',
                    pattern: /^[0-9\+\-\(\)\s]*$/,
                    warning: 'contact_number-warning'
                },
                {
                    id: 'sub_district',
                    pattern: /^[A-Za-z0-9\s\.\-\_\&\(\)\,\'\"]*$/,
                    warning: 'sub_district-warning'
                },
                {
                    id: 'village',
                    pattern: /^[A-Za-z0-9\s\.\-\_\&\(\)\,\'\"]*$/,
                    warning: 'village-warning'
                },
                {
                    id: 'landmark',
                    pattern: /^[A-Za-z0-9\s\.\-\_\&\(\)\,\'\"]*$/,
                    warning: 'landmark-warning'
                },
                {
                    id: 'location',
                    pattern: /^[A-Za-z0-9\s\.\-\_\&\(\)\,\'\"]*$/,
                    warning: 'location-warning'
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
            $('#submitButtonText').text('Updating...');
            $('#submitBtn').prop('disabled', true);
        });

        // Initial address preview update
        updateAddressPreview();

        // Force uppercase conversion on page load for all existing values
        setTimeout(function() {
            const uppercaseFields = ['ledger_name', 'sub_district', 'village', 'landmark', 'location'];
            uppercaseFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field && field.value) {
                    field.value = field.value.toUpperCase();
                }
            });
            updateAddressPreview();
        }, 100);

        console.log('Customer edit form initialization completed');
    });

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
                ledger_name: document.getElementById('ledger_name').value,
                contact_number: document.getElementById('contact_number').value,
                sub_district: document.getElementById('sub_district').value,
                village: document.getElementById('village').value,
                landmark: document.getElementById('landmark').value,
                location: document.getElementById('location').value,
                district: document.getElementById('district').value,
                timestamp: new Date().toISOString()
            };

            // Save to localStorage for recovery
            localStorage.setItem('customer_edit_draft', JSON.stringify(formData));
            console.log('Form data auto-saved');
        }, 2000);
    }

    // Attach auto-save to form inputs
    document.querySelectorAll('#customerForm input, #customerForm select, #customerForm textarea').forEach(input => {
        input.addEventListener('input', autoSaveFormData);
        input.addEventListener('change', autoSaveFormData);
    });

    // Load draft data on page load
    window.addEventListener('load', function() {
        const savedDraft = localStorage.getItem('customer_edit_draft');
        if (savedDraft) {
            try {
                const draftData = JSON.parse(savedDraft);
                const draftAge = new Date() - new Date(draftData.timestamp);

                // Only restore if draft is less than 30 minutes old
                if (draftAge < 1800000) {
                    const shouldRestore = confirm('We found a saved draft of your changes. Would you like to restore it?');
                    if (shouldRestore) {
                        // Restore form data
                        if (draftData.ledger_name) document.getElementById('ledger_name').value = draftData.ledger_name;
                        if (draftData.contact_number) document.getElementById('contact_number').value = draftData.contact_number;
                        if (draftData.sub_district) document.getElementById('sub_district').value = draftData.sub_district;
                        if (draftData.village) document.getElementById('village').value = draftData.village;
                        if (draftData.landmark) document.getElementById('landmark').value = draftData.landmark;
                        if (draftData.location) document.getElementById('location').value = draftData.location;

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
        localStorage.removeItem('customer_edit_draft');
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

    // Additional function to force uppercase on all text fields
    function forceUppercaseOnAllFields() {
        const uppercaseFields = ['ledger_name', 'sub_district', 'village', 'landmark', 'location'];
        uppercaseFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                // Add event listener for real-time uppercase conversion
                field.addEventListener('input', function() {
                    const cursorPosition = this.selectionStart;
                    const oldValue = this.value;
                    const newValue = oldValue.toUpperCase();

                    if (oldValue !== newValue) {
                        this.value = newValue;
                        this.setSelectionRange(cursorPosition, cursorPosition);
                    }
                });

                // Convert existing value to uppercase
                if (field.value) {
                    field.value = field.value.toUpperCase();
                }
            }
        });
    }

    // Call the function when DOM is fully loaded
    document.addEventListener('DOMContentLoaded', function() {
        forceUppercaseOnAllFields();
    });

    // Backup function to ensure uppercase conversion works
    $(document).ready(function() {
        // Additional backup for uppercase conversion
        const uppercaseInputs = document.querySelectorAll('.uppercase-input');
        uppercaseInputs.forEach(input => {
            // Convert existing value to uppercase
            if (input.value) {
                input.value = input.value.toUpperCase();
            }

            // Add multiple event listeners to ensure uppercase conversion
            ['input', 'keyup', 'paste', 'blur'].forEach(eventType => {
                input.addEventListener(eventType, function(e) {
                    if (eventType === 'paste') {
                        setTimeout(() => {
                            this.value = this.value.toUpperCase();
                        }, 10);
                    } else {
                        const cursorPosition = this.selectionStart;
                        const oldValue = this.value;
                        const newValue = oldValue.toUpperCase();

                        if (oldValue !== newValue) {
                            this.value = newValue;
                            if (eventType !== 'blur') {
                                this.setSelectionRange(cursorPosition, cursorPosition);
                            }
                        }
                    }
                });
            });
        });
    });

    console.log('Enhanced customer edit form with uppercase and English-only validation loaded successfully');
</script>
@endpush