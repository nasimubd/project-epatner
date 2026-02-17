@extends('admin.layouts.app')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow-lg">
            <div class="p-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Create New Staff Member</h2>

                <!-- Success/Error Messages -->
                @if(session('success'))
                <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
                @endif

                @if($errors->any())
                <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <strong>Please correct the following errors:</strong>
                    <ul class="mt-2 list-disc list-inside">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form method="POST" action="{{ route('admin.staff.store') }}" id="staffForm" class="space-y-6">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Basic Information -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-medium text-gray-900">Basic Information</h3>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Full Name <span class="text-red-500">*</span></label>
                                <input type="text" name="name" value="{{ old('name') }}" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('name') border-red-500 @enderror">
                                @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Email Address <span class="text-red-500">*</span></label>
                                <input type="email" name="email" value="{{ old('email') }}" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('email') border-red-500 @enderror">
                                @error('email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="role" class="block text-sm font-medium text-gray-700">Role <span class="text-red-500">*</span></label>
                                <select name="role" id="role" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('role') border-red-500 @enderror">
                                    <option value="">Select a role</option>
                                    @foreach($roles as $role)
                                    <option value="{{ $role->name }}" {{ old('role') == $role->name ? 'selected' : '' }}>{{ ucfirst($role->name) }}</option>
                                    @endforeach
                                </select>
                                @error('role')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- DSR Staff Assignment Field (Hidden by default) -->
                            <div id="dsrAssignmentField" class="hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Assign Staff Members <span class="text-red-500">*</span></label>
                                <select name="assigned_staff[]" id="assigned_staff" multiple
                                    class="select2 mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('assigned_staff') border-red-500 @enderror">
                                    @foreach($existingStaff as $staff)
                                    <option value="{{ $staff->id }}"
                                        {{ in_array($staff->id, old('assigned_staff', [])) ? 'selected' : '' }}>
                                        {{ $staff->user->name }} ({{ $staff->user->email }})
                                    </option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Select staff members whose products will be delivered by this DSR</p>
                                @error('assigned_staff')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="flex items-center">
                                    <input type="checkbox" name="disable_underprice" value="1"
                                        {{ old('disable_underprice') ? 'checked' : '' }}
                                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                    <span class="ml-2 text-sm text-gray-700">Disable underprice (User can only set prices higher than trade price)</span>
                                </label>
                                @error('disable_underprice')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Phone Number</label>
                                <input type="tel" name="phone" value="{{ old('phone') }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('phone') border-red-500 @enderror">
                                @error('phone')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Password <span class="text-red-500">*</span></label>
                                <input type="password" name="password" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('password') border-red-500 @enderror">
                                <p class="mt-1 text-xs text-gray-500">Password must be at least 8 characters long</p>
                                @error('password')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Confirm Password <span class="text-red-500">*</span></label>
                                <input type="password" name="password_confirmation" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                        </div>

                        <!-- Access Control -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-medium text-gray-900">Access Control</h3>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Assigned Categories <span class="text-red-500">*</span></label>
                                <select name="product_categories[]" multiple required
                                    class="select2 mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('product_categories') border-red-500 @enderror">
                                    @foreach($categories as $category)
                                    <option value="{{ $category->id }}"
                                        {{ in_array($category->id, old('product_categories', [])) ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Select one or more product categories</p>
                                @error('product_categories')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Assigned Ledgers <span class="text-red-500">*</span></label>
                                <select name="ledgers[]" multiple required
                                    class="select2 mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('ledgers') border-red-500 @enderror">
                                    @foreach($ledgers as $ledger)
                                    <option value="{{ $ledger->id }}"
                                        {{ in_array($ledger->id, old('ledgers', [])) ? 'selected' : '' }}>
                                        {{ $ledger->name }} ({{ $ledger->ledger_type }})
                                    </option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Select one or more ledgers</p>
                                @error('ledgers')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex flex-col sm:flex-row justify-end space-y-3 sm:space-y-0 sm:space-x-3 pt-6 border-t">
                        <!-- Cancel Button -->
                        <a href="{{ route('admin.staff.index') }}" id="cancelBtn"
                            class="w-full sm:w-auto bg-gradient-to-r from-gray-100 to-gray-200 hover:from-gray-200 hover:to-gray-300 text-gray-700 font-bold py-2.5 px-6 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 shadow-md flex items-center justify-center group border border-gray-300">
                            <span class="inline-flex items-center">
                                <svg id="cancelIcon" class="w-5 h-5 mr-2 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                </svg>
                                <svg id="cancelSpinner" class="hidden w-5 h-5 mr-2 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span id="cancelButtonText">Cancel</span>
                            </span>
                        </a>

                        <!-- Create Staff Button -->
                        <button type="submit" id="createStaffBtn"
                            class="w-full sm:w-auto bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-bold py-2.5 px-6 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 shadow-md flex items-center justify-center group">
                            <span class="inline-flex items-center">
                                <svg id="createIcon" class="w-5 h-5 mr-2 transition-transform group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                <svg id="createSpinner" class="hidden w-5 h-5 mr-2 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span id="createButtonText">Create Staff Member</span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Help Section -->
        <div class="mt-6 bg-blue-50 border border-blue-200 rounded-md p-4">
            <h3 class="text-lg font-medium text-blue-900 mb-2">Need Help?</h3>
            <div class="text-sm text-blue-800 space-y-1">
                <p><strong>Roles:</strong> Determine what actions the staff member can perform in the system.</p>
                <p><strong>DSR Assignment:</strong> When creating a DSR, select which staff members' products they will deliver.</p>
                <p><strong>Product Categories:</strong> Limit which products the staff member can manage.</p>
                <p><strong>Ledgers:</strong> Control which financial accounts the staff member can access.</p>
                <p><strong>Pricing Control:</strong> Prevent staff from setting prices below trade price.</p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        // Initialize Select2 for Product Categories, Ledgers, and DSR Assignment
        $('.select2').select2({
            theme: 'default',
            placeholder: 'Select options...',
            allowClear: true,
            width: '100%'
        });

        const form = document.getElementById('staffForm');
        const submitBtn = document.getElementById('createStaffBtn');
        const buttonText = document.getElementById('createButtonText');
        const createIcon = document.getElementById('createIcon');
        const createSpinner = document.getElementById('createSpinner');
        const roleSelect = document.getElementById('role');
        const dsrAssignmentField = document.getElementById('dsrAssignmentField');
        const assignedStaffSelect = document.getElementById('assigned_staff');

        // Handle role change to show/hide DSR assignment field
        roleSelect.addEventListener('change', function() {
            if (this.value === 'dsr') {
                dsrAssignmentField.classList.remove('hidden');
                assignedStaffSelect.setAttribute('required', 'required');
            } else {
                dsrAssignmentField.classList.add('hidden');
                assignedStaffSelect.removeAttribute('required');
                // Clear selected values when hiding
                $('#assigned_staff').val(null).trigger('change');
            }
        });

        // Check initial state on page load
        if (roleSelect.value === 'dsr') {
            dsrAssignmentField.classList.remove('hidden');
            assignedStaffSelect.setAttribute('required', 'required');
        }

        // Cancel button handler
        $('#cancelBtn').on('click', function() {
            $('#cancelIcon').addClass('hidden');
            $('#cancelSpinner').removeClass('hidden');
            $('#cancelButtonText').text('Canceling...');
        });

        // Form submission handler
        form.addEventListener('submit', function(e) {
            // Validate required fields
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(function(field) {
                if (field.name === 'product_categories[]' || field.name === 'ledgers[]' || field.name === 'assigned_staff[]') {
                    // For Select2 fields, check if any options are selected
                    const fieldId = field.id || field.name.replace('[]', '').replace('_', '_');
                    if ($('#' + fieldId).val() === null || $('#' + fieldId).val().length === 0) {
                        isValid = false;
                        $('#' + fieldId).next('.select2-container').find('.select2-selection').addClass('border-red-500');
                    } else {
                        $('#' + fieldId).next('.select2-container').find('.select2-selection').removeClass('border-red-500');
                    }
                } else if (!field.value) {
                    isValid = false;
                    field.classList.add('border-red-500');
                } else {
                    field.classList.remove('border-red-500');
                }
            });

            // Special validation for DSR role
            if (roleSelect.value === 'dsr') {
                const assignedStaffValues = $('#assigned_staff').val();
                if (!assignedStaffValues || assignedStaffValues.length === 0) {
                    isValid = false;
                    $('#assigned_staff').next('.select2-container').find('.select2-selection').addClass('border-red-500');
                    alert('Please assign at least one staff member to this DSR.');
                    e.preventDefault();
                    return false;
                }
            }

            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return false;
            }

            // Show loading state
            submitBtn.disabled = true;
            createIcon.classList.add('hidden');
            createSpinner.classList.remove('hidden');
            buttonText.textContent = 'Creating...';
            submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
        });

        // Real-time validation for regular inputs
        const inputs = form.querySelectorAll('input, select:not([multiple])');
        inputs.forEach(function(input) {
            input.addEventListener('blur', function() {
                if (this.hasAttribute('required')) {
                    if (this.value) {
                        this.classList.remove('border-red-500');
                        this.classList.add('border-green-500');
                    } else {
                        this.classList.remove('border-green-500');
                        this.classList.add('border-red-500');
                    }
                }
            });
        });

        // Real-time validation for Select2 fields
        $('#product_categories').on('change', function() {
            if ($(this).val() && $(this).val().length > 0) {
                $(this).next('.select2-container').find('.select2-selection').removeClass('border-red-500');
                $(this).next('.select2-container').find('.select2-selection').addClass('border-green-500');
            } else {
                $(this).next('.select2-container').find('.select2-selection').removeClass('border-green-500');
                $(this).next('.select2-container').find('.select2-selection').addClass('border-red-500');
            }
        });

        $('#ledgers').on('change', function() {
            if ($(this).val() && $(this).val().length > 0) {
                $(this).next('.select2-container').find('.select2-selection').removeClass('border-red-500');
                $(this).next('.select2-container').find('.select2-selection').addClass('border-green-500');
            } else {
                $(this).next('.select2-container').find('.select2-selection').removeClass('border-green-500');
                $(this).next('.select2-container').find('.select2-selection').addClass('border-red-500');
            }
        });

        $('#assigned_staff').on('change', function() {
            if ($(this).val() && $(this).val().length > 0) {
                $(this).next('.select2-container').find('.select2-selection').removeClass('border-red-500');
                $(this).next('.select2-container').find('.select2-selection').addClass('border-green-500');
            } else if (roleSelect.value === 'dsr') {
                $(this).next('.select2-container').find('.select2-selection').removeClass('border-green-500');
                $(this).next('.select2-container').find('.select2-selection').addClass('border-red-500');
            }
        });

        // Email validation
        const emailField = document.querySelector('input[name="email"]');
        if (emailField) {
            emailField.addEventListener('blur', function() {
                const email = this.value;
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

                if (email && !emailRegex.test(email)) {
                    this.classList.add('border-red-500');
                    // Show error message if not already shown
                    if (!this.parentNode.querySelector('.email-error')) {
                        const errorMsg = document.createElement('p');
                        errorMsg.className = 'email-error mt-1 text-sm text-red-600';
                        errorMsg.textContent = 'Please enter a valid email address';
                        this.parentNode.appendChild(errorMsg);
                    }
                } else if (email) {
                    this.classList.remove('border-red-500');
                    // Remove error message
                    const errorMsg = this.parentNode.querySelector('.email-error');
                    if (errorMsg) {
                        errorMsg.remove();
                    }
                }
            });
        }

        // Phone number formatting
        const phoneField = document.querySelector('input[name="phone"]');
        if (phoneField) {
            phoneField.addEventListener('input', function() {
                let value = this.value.replace(/\D/g, '');
                if (value.length > 0) {
                    if (value.length <= 3) {
                        value = value;
                    } else if (value.length <= 6) {
                        value = value.slice(0, 3) + '-' + value.slice(3);
                    } else {
                        value = value.slice(0, 3) + '-' + value.slice(3, 6) + '-' + value.slice(6, 10);
                    }
                }
                this.value = value;
            });
        }

        // Auto-hide success/error messages after 5 seconds
        const alerts = document.querySelectorAll('.bg-green-100, .bg-red-100');
        alerts.forEach(function(alert) {
            setTimeout(function() {
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 300);
            }, 5000);
        });

        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + S to save form
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                form.submit();
            }

            // Escape to go back
            if (e.key === 'Escape') {
                window.location.href = '{{ route("admin.staff.index") }}';
            }
        });

        // Form data persistence in localStorage
        const formFields = ['name', 'email', 'phone', 'role'];

        // Load saved data
        formFields.forEach(function(field) {
            const savedValue = localStorage.getItem('staff_form_' + field);
            if (savedValue) {
                const fieldElement = document.querySelector('[name="' + field + '"]');
                if (fieldElement) {
                    fieldElement.value = savedValue;
                    // Trigger role change event if it's the role field
                    if (field === 'role') {
                        fieldElement.dispatchEvent(new Event('change'));
                    }
                }
            }
        });

        // Save data on input
        formFields.forEach(function(field) {
            const fieldElement = document.querySelector('[name="' + field + '"]');
            if (fieldElement) {
                fieldElement.addEventListener('input', function() {
                    localStorage.setItem('staff_form_' + field, this.value);
                });
            }
        });

        // Clear localStorage on successful form submission
        form.addEventListener('submit', function() {
            formFields.forEach(function(field) {
                localStorage.removeItem('staff_form_' + field);
            });
        });

        // Handle back button warning if form has changes
        let formChanged = false;
        const allInputs = form.querySelectorAll('input, select, textarea');

        allInputs.forEach(function(input) {
            input.addEventListener('change', function() {
                formChanged = true;
            });
        });

        // Also track Select2 changes
        $('#product_categories, #ledgers, #assigned_staff').on('change', function() {
            formChanged = true;
        });

        window.addEventListener('beforeunload', function(e) {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
                return e.returnValue;
            }
        });

        // Focus management for better UX
        const firstInput = form.querySelector('input[type="text"]');
        if (firstInput) {
            firstInput.focus();
        }

        // Add visual feedback for form completion
        function updateFormProgress() {
            const requiredFields = form.querySelectorAll('[required]');
            let filledCount = 0;

            requiredFields.forEach(function(field) {
                if (field.name === 'product_categories[]' || field.name === 'ledgers[]' || field.name === 'assigned_staff[]') {
                    // For Select2 fields
                    const fieldId = field.id || field.name.replace('[]', '').replace('_', '_');
                    if ($(field).val() && $(field).val().length > 0) {
                        filledCount++;
                    }
                } else if (field.value.trim() !== '') {
                    filledCount++;
                }
            });

            const progress = Math.round((filledCount / requiredFields.length) * 100);

            // Update submit button state
            if (progress === 100) {
                submitBtn.classList.remove('opacity-50');
                submitBtn.classList.add('hover:from-blue-600', 'hover:to-blue-700');
            } else {
                submitBtn.classList.add('opacity-50');
                submitBtn.classList.remove('hover:from-blue-600', 'hover:to-blue-700');
            }
        }

        // Call progress update on field changes
        allInputs.forEach(function(input) {
            input.addEventListener('input', updateFormProgress);
            input.addEventListener('change', updateFormProgress);
        });

        // Also track Select2 changes for progress
        $('#product_categories, #ledgers, #assigned_staff').on('change', updateFormProgress);

        // Initial progress check
        updateFormProgress();
    });
</script>
@endpush
@endsection