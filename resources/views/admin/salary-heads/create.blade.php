@extends('admin.layouts.app')

@section('content')
@php
$user = Auth::user();
@endphp

<div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 p-2 sm:p-4">
    <div class="max-w-4xl mx-auto">
        {{-- Enhanced Header --}}
        <div class="bg-white/80 backdrop-blur-sm shadow-xl rounded-2xl overflow-hidden border border-white/20 mb-6">
            <!-- Header Section -->
            <div class="bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-700 p-4 sm:p-6">
                <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div class="text-center sm:text-left">
                        <h1 class="text-2xl sm:text-3xl font-bold text-white mb-1">
                            <i class="fas fa-user-plus mr-2"></i>Create Salary Head
                        </h1>
                        <p class="text-blue-100 text-sm">Add a new staff member with salary details</p>
                    </div>

                    <div class="flex space-x-3">
                        <a href="{{ route('admin.salary-heads.index') }}"
                            class="group relative overflow-hidden bg-gradient-to-r from-gray-500 to-gray-600 hover:from-gray-600 hover:to-gray-700 text-white font-bold py-3 px-6 rounded-xl transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl">
                            <div class="absolute inset-0 bg-white/20 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
                            <span class="relative flex items-center justify-center">
                                <svg class="w-5 h-5 mr-2 transition-transform group-hover:-rotate-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                                <span class="hidden sm:inline">Back to List</span>
                                <span class="sm:hidden">Back</span>
                            </span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Error Messages --}}
        @if($errors->any())
        <div class="bg-gradient-to-r from-red-50 to-pink-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r-xl shadow-lg" role="alert">
            <div class="flex items-start">
                <i class="fas fa-exclamation-circle text-red-500 mr-3 text-lg mt-0.5"></i>
                <div>
                    <h4 class="font-medium mb-2">Please fix the following errors:</h4>
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                        <li class="text-sm">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        @endif

        {{-- Create Form --}}
        <div class="bg-white/90 backdrop-blur-sm rounded-xl shadow-lg border border-white/20 overflow-hidden">
            <form action="{{ route('admin.salary-heads.store') }}" method="POST" id="salaryHeadForm" class="space-y-6">
                @csrf

                <div class="p-6 sm:p-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- User Selection --}}
                        <div class="md:col-span-2">
                            <label for="user_id" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-user mr-2 text-blue-600"></i>Select User
                            </label>
                            <select name="user_id" id="user_id" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-300 @error('user_id') border-red-500 @enderror">
                                <option value="">Choose a user...</option>
                                @foreach($availableUsers as $availableUser)
                                <option value="{{ $availableUser->id }}" {{ old('user_id') == $availableUser->id ? 'selected' : '' }}>
                                    {{ $availableUser->name }} ({{ $availableUser->email }})
                                </option>
                                @endforeach
                            </select>
                            @error('user_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Role Selection --}}
                        <div>
                            <label for="role_name" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-user-tag mr-2 text-purple-600"></i>Role
                            </label>
                            <select name="role_name" id="role_name" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-300 @error('role_name') border-red-500 @enderror">
                                <option value="">Select role...</option>
                                @foreach($roles as $role)
                                <option value="{{ $role->name }}" {{ old('role_name') == $role->name ? 'selected' : '' }}>
                                    {{ ucfirst($role->name) }}
                                </option>
                                @endforeach
                            </select>
                            @error('role_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Salary Amount --}}
                        <div>
                            <label for="salary_amount" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-money-bill mr-2 text-green-600"></i>Salary Amount (৳)
                            </label>
                            <input type="number" name="salary_amount" id="salary_amount"
                                step="0.01" min="0" max="999999.99" required
                                value="{{ old('salary_amount') }}"
                                placeholder="Enter salary amount"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-300 @error('salary_amount') border-red-500 @enderror">
                            @error('salary_amount')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Salary Account Ledger --}}
                        <div class="md:col-span-2">
                            <label for="salary_account_ledger_id" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-book mr-2 text-indigo-600"></i>Salary Account Ledger
                            </label>
                            <select name="salary_account_ledger_id" id="salary_account_ledger_id" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-300 @error('salary_account_ledger_id') border-red-500 @enderror">
                                <option value="">Select salary account ledger...</option>
                                @foreach($salaryLedgers as $ledger)
                                <option value="{{ $ledger->id }}" {{ old('salary_account_ledger_id') == $ledger->id ? 'selected' : '' }}>
                                    {{ $ledger->name }} (Balance: ৳{{ number_format($ledger->current_balance, 2) }})
                                </option>
                                @endforeach
                            </select>
                            @error('salary_account_ledger_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500">
                                <i class="fas fa-info-circle mr-1"></i>
                                Only "Salary Payable" type ledgers are shown
                            </p>
                        </div>
                    </div>

                    {{-- Information Box --}}
                    <div class="mt-6 bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex items-start">
                            <i class="fas fa-info-circle text-blue-600 mr-3 mt-0.5"></i>
                            <div class="text-sm text-blue-800">
                                <h4 class="font-medium mb-2">Important Information:</h4>
                                <ul class="space-y-1 text-xs">
                                    <li>• The salary head will be created with "Pending" status</li>
                                    <li>• Admin approval is required before the staff member becomes active</li>
                                    <li>• A unique Staff ID will be automatically generated</li>
                                    <li>• Only users without existing salary heads can be selected</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="bg-gradient-to-r from-gray-50 to-blue-50 px-6 py-4 sm:px-8 border-t border-gray-200">
                    <div class="flex flex-col sm:flex-row justify-end space-y-3 sm:space-y-0 sm:space-x-3">
                        <a href="{{ route('admin.salary-heads.index') }}"
                            class="w-full sm:w-auto px-6 py-3 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:border-transparent transition-all duration-300 text-center font-medium">
                            <i class="fas fa-times mr-2"></i>Cancel
                        </a>

                        <button type="submit" id="submitBtn"
                            class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl">
                            <i id="submitIcon" class="fas fa-save mr-2"></i>
                            <i id="submitSpinner" class="hidden fas fa-spinner fa-spin mr-2"></i>
                            <span id="submitText">Create Salary Head</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    /* Backdrop blur fallback */
    .backdrop-blur-sm {
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
    }

    /* Glassmorphism effect */
    .bg-white\/90 {
        background: rgba(255, 255, 255, 0.9);
    }

    .bg-white\/80 {
        background: rgba(255, 255, 255, 0.8);
    }

    /* Select2 custom styling */
    .select2-container--default .select2-selection--single {
        height: 48px;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        padding: 0 1rem;
        display: flex;
        align-items: center;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #374151;
        line-height: 48px;
        padding: 0;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 46px;
        right: 10px;
    }

    .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: #3b82f6;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
    }

    .select2-dropdown {
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }

    .select2-container.is-invalid .select2-selection {
        border-color: #dc3545 !important;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
    }

    /* Button hover effects */
    .group:hover .group-hover\:translate-x-full {
        transform: translateX(100%);
    }

    .group:hover .group-hover\:-rotate-12 {
        transform: rotate(-12deg);
    }

    /* Loading states */
    .fa-spin {
        animation: fa-spin 2s infinite linear;
    }

    @keyframes fa-spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    /* Enhanced focus states */
    .focus\:ring-2:focus {
        outline: 2px solid transparent;
        outline-offset: 2px;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5);
    }

    /* Smooth transitions */
    * {
        transition-property: color, background-color, border-color, text-decoration-color, fill, stroke, opacity, box-shadow, transform, filter, backdrop-filter;
        transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
        transition-duration: 150ms;
    }

    /* Mobile responsive improvements */
    @media (max-width: 640px) {
        .p-2 {
            padding: 0.25rem;
        }
    }

    /* Form validation styling */
    .border-red-500 {
        border-color: #ef4444 !important;
    }

    .text-red-600 {
        color: #dc2626;
    }

    /* Custom input styling */
    input[type="number"]::-webkit-outer-spin-button,
    input[type="number"]::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    input[type="number"] {
        -moz-appearance: textfield;
    }
</style>
@endpush

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        // Initialize Select2 for better user experience
        $('#user_id').select2({
            placeholder: 'Choose a user...',
            allowClear: true,
            width: '100%'
        });

        $('#role_name').select2({
            placeholder: 'Select role...',
            allowClear: true,
            width: '100%'
        });

        $('#salary_account_ledger_id').select2({
            placeholder: 'Select salary account ledger...',
            allowClear: true,
            width: '100%'
        });

        // Form validation
        $('#salaryHeadForm').on('submit', function(e) {
            let isValid = true;
            const requiredFields = ['user_id', 'role_name', 'salary_amount', 'salary_account_ledger_id'];

            // Clear previous error states
            $('.border-red-500').removeClass('border-red-500');
            $('.select2-container.is-invalid').removeClass('is-invalid');

            // Validate required fields
            requiredFields.forEach(function(fieldName) {
                const field = $(`[name="${fieldName}"]`);
                const value = field.val();

                if (!value || value.trim() === '') {
                    isValid = false;

                    if (field.hasClass('select2-hidden-accessible')) {
                        // Handle Select2 fields
                        field.next('.select2-container').addClass('is-invalid');
                    } else {
                        // Handle regular input fields
                        field.addClass('border-red-500');
                    }
                }
            });

            // Validate salary amount
            const salaryAmount = parseFloat($('#salary_amount').val());
            if (salaryAmount && (salaryAmount < 0 || salaryAmount > 999999.99)) {
                isValid = false;
                $('#salary_amount').addClass('border-red-500');
                alert('Salary amount must be between 0 and 999,999.99');
            }

            if (!isValid) {
                e.preventDefault();

                // Focus on first invalid field
                const firstInvalid = $('.border-red-500, .select2-container.is-invalid').first();
                if (firstInvalid.hasClass('select2-container')) {
                    firstInvalid.prev('select').select2('open');
                } else {
                    firstInvalid.focus();
                }

                // Show error message
                $('html, body').animate({
                    scrollTop: 0
                }, 500);

                return false;
            }

            // Show loading state
            showLoadingState();
        });

        // Real-time validation
        $('input[required], select[required]').on('blur change', function() {
            const field = $(this);
            const value = field.val();

            if (value && value.trim() !== '') {
                field.removeClass('border-red-500');
                if (field.hasClass('select2-hidden-accessible')) {
                    field.next('.select2-container').removeClass('is-invalid');
                }
            }
        });

        // Format salary amount input
        $('#salary_amount').on('input', function() {
            let value = $(this).val();

            // Remove any non-numeric characters except decimal point
            value = value.replace(/[^0-9.]/g, '');

            // Ensure only one decimal point
            const parts = value.split('.');
            if (parts.length > 2) {
                value = parts[0] + '.' + parts.slice(1).join('');
            }

            // Limit to 2 decimal places
            if (parts[1] && parts[1].length > 2) {
                value = parts[0] + '.' + parts[1].substring(0, 2);
            }

            $(this).val(value);
        });

        // Auto-focus first field
        setTimeout(function() {
            $('#user_id').select2('open');
        }, 500);

        // Prevent form resubmission
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        // Handle Select2 validation styling
        $('#user_id, #role_name, #salary_account_ledger_id').on('select2:select select2:unselect', function() {
            $(this).next('.select2-container').removeClass('is-invalid');
        });

        // Keyboard shortcuts
        $(document).keydown(function(e) {
            // Ctrl/Cmd + S to submit form
            if ((e.ctrlKey || e.metaKey) && e.keyCode === 83) {
                e.preventDefault();
                $('#salaryHeadForm').submit();
            }

            // Escape to go back
            if (e.keyCode === 27) {
                if (confirm('Are you sure you want to leave? Any unsaved changes will be lost.')) {
                    window.location.href = "{{ route('admin.salary-heads.index') }}";
                }
            }
        });

        // Warn before leaving with unsaved changes
        let formChanged = false;
        $('#salaryHeadForm input, #salaryHeadForm select').on('change input', function() {
            formChanged = true;
        });

        $(window).on('beforeunload', function(e) {
            if (formChanged) {
                const message = 'You have unsaved changes. Are you sure you want to leave?';
                e.returnValue = message;
                return message;
            }
        });

        // Don't warn when submitting the form
        $('#salaryHeadForm').on('submit', function() {
            formChanged = false;
        });

        // Mobile-friendly interactions
        if ('ontouchstart' in window) {
            $('.hover\\:scale-105').addClass('active:scale-95');
        }
    });

    function showLoadingState() {
        const submitBtn = $('#submitBtn');
        const submitIcon = $('#submitIcon');
        const submitSpinner = $('#submitSpinner');
        const submitText = $('#submitText');

        // Update button appearance
        submitIcon.addClass('hidden');
        submitSpinner.removeClass('hidden');
        submitText.text('Creating...');
        submitBtn.prop('disabled', true);

        // Add visual feedback
        submitBtn.addClass('opacity-75 cursor-not-allowed');
    }

    // Auto-hide error messages
    setTimeout(function() {
        $('.bg-gradient-to-r.from-red-50').fadeOut('slow');
    }, 10000);
</script>
@endpush
@endsection