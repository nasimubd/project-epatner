@extends('admin.layouts.app')

@section('content')
<div class="py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-4 sm:p-6 text-gray-900">
                <!-- Responsive Header with Back Button -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
                    <h2 class="text-xl sm:text-2xl font-semibold mb-4 sm:mb-0">Add New Category</h2>
                    <a href="{{ route('admin.inventory.categories.index') }}" id="backBtn"
                        class="w-full sm:w-auto bg-gradient-to-r from-gray-500 to-gray-600 hover:from-gray-600 hover:to-gray-700 text-white font-bold py-2 px-4 sm:py-2.5 sm:px-6 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 shadow-md flex items-center justify-center group">
                        <span class="inline-flex items-center">
                            <svg id="backIcon" class="w-5 h-5 mr-2 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            <svg id="backSpinner" class="hidden w-5 h-5 mr-2 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span id="backButtonText">Back</span>
                        </span>
                    </a>
                </div>

                <!-- Responsive Form -->
                <form action="{{ route('admin.categories.store') }}" method="POST"
                    class="space-y-6"
                    x-data="{ 
                        isSubmitting: false,
                        submitForm(e) {
                            if (this.isSubmitting) return;
                            
                            this.isSubmitting = true;
                            document.querySelector('#categoryIcon').classList.add('hidden');
                            document.querySelector('#categorySpinner').classList.remove('hidden');
                            document.querySelector('#categoryButtonText').textContent = 'Creating...';
                            
                            e.target.submit();
                        }
                    }"
                    @submit.prevent="submitForm($event)">
                    @csrf

                    <div class="space-y-4 sm:space-y-6">
                        <!-- Category Name Field - Improved for mobile -->
                        <div class="bg-white rounded-lg">
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Category Name</label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 @error('name') border-red-500 @enderror"
                                required>
                            @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Supplier Ledger Field - Improved for mobile -->
                        <div class="bg-white rounded-lg">
                            <label for="ledger_id" class="block text-sm font-medium text-gray-700 mb-1">Supplier Ledger</label>
                            <select name="ledger_id" id="ledger_id"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 @error('ledger_id') border-red-500 @enderror"
                                required>
                                <option value="">Select a Supplier Ledger</option>
                                @forelse($ledgers as $ledger)
                                <option value="{{ $ledger->id }}"
                                    {{ old('ledger_id') == $ledger->id ? 'selected' : '' }}>
                                    {{ $ledger->name }} ({{ $ledger->ledger_type }})
                                </option>
                                @empty
                                <option value="">No supplier ledgers found</option>
                                @endforelse
                            </select>

                            @if($ledgers->isEmpty())
                            <div class="mt-2 p-3 bg-red-50 rounded-md">
                                <p class="text-red-500 text-sm">
                                    Debug: No ledgers found.
                                    Business ID: {{ auth()->user()->businessAdmin->business_id ?? 'N/A' }}
                                </p>
                            </div>
                            @endif

                            @error('ledger_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Status Field - Improved for mobile -->
                        <div class="bg-white rounded-lg">
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select name="status" id="status"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500">
                                <option value="1" {{ old('status', '1') == '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ old('status') == '0' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Submit Button - Improved for mobile -->
                    <div class="flex justify-end mt-6">
                        <button type="submit" id="createCategoryBtn"
                            class="w-full sm:w-auto bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-bold py-2 px-4 sm:py-2.5 sm:px-6 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 shadow-md flex items-center justify-center group">
                            <span class="inline-flex items-center">
                                <svg id="categoryIcon" class="w-5 h-5 mr-2 transition-transform group-hover:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <svg id="categorySpinner" class="hidden w-5 h-5 mr-2 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span id="categoryButtonText">Create Category</span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
    $(document).ready(function() {
        $('#createCategoryBtn').on('click', function() {
            $('#categoryIcon').addClass('hidden');
            $('#categorySpinner').removeClass('hidden');
            $('#categoryButtonText').text('Creating...');
        });

        $('#backBtn').on('click', function() {
            $('#backIcon').addClass('hidden');
            $('#backSpinner').removeClass('hidden');
            $('#backButtonText').text('Going back...');
        });
    });
</script>
@endpush
@endsection