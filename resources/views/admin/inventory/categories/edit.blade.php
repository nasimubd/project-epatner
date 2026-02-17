@extends('admin.layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white shadow-xl rounded-lg overflow-hidden">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-2xl font-bold text-gray-800 flex items-center">
                    <i class="fas fa-edit mr-3 text-blue-500"></i>
                    Edit Category
                </h2>
                <a href="{{ route('admin.inventory.categories.index') }}" id="backBtn"
                    class="group flex items-center justify-center w-10 h-10 bg-gradient-to-r from-gray-500 to-gray-600 hover:from-gray-600 hover:to-gray-700 text-white rounded-full transition duration-300 ease-in-out transform hover:scale-110 shadow-md relative">
                    <svg id="backIcon" class="w-5 h-5 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <svg id="backSpinner" class="hidden absolute w-5 h-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </a>
            </div>

            <form action="{{ route('admin.categories.update', $category) }}"
                method="POST"
                class="p-6 space-y-6">
                @csrf
                @method('PUT')

                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Category Name
                        </label>
                        <input
                            type="text"
                            name="name"
                            value="{{ old('name', $category->name) }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 
                                   @error('name') border-red-500 @enderror"
                            required>
                        @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Supplier Ledger
                        </label>
                        <select name="ledger_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('ledger_id') border-red-500 @enderror"
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
                        @error('ledger_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Status
                    </label>
                    <div class="flex items-center space-x-4">
                        <label class="inline-flex items-center">
                            <input
                                type="radio"
                                name="status"
                                value="1"
                                {{ old('status', $category->status) ? 'checked' : '' }}
                                class="form-radio text-blue-600 focus:ring-blue-500">
                            <span class="ml-2">Active</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input
                                type="radio"
                                name="status"
                                value="0"
                                {{ old('status', $category->status) ? '' : 'checked' }}
                                class="form-radio text-red-600 focus:ring-red-500">
                            <span class="ml-2">Inactive</span>
                        </label>
                    </div>
                    @error('status')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end">
                    <button type="submit" id="updateCategoryBtn"
                        class="bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-bold py-2.5 px-6 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 shadow-md flex items-center group">
                        <span class="inline-flex items-center">
                            <svg id="updateIcon" class="w-5 h-5 mr-2 transition-transform group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <svg id="updateSpinner" class="hidden w-5 h-5 mr-2 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span id="updateButtonText">Update Category</span>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
<style>
    @media (max-width: 640px) {
        .grid-cols-2 {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    $(document).ready(function() {
        $('#updateCategoryBtn').on('click', function() {
            $('#updateIcon').addClass('hidden');
            $('#updateSpinner').removeClass('hidden');
            $('#updateButtonText').text('Updating...');
        });

        $('#backBtn').on('click', function() {
            $('#backIcon').addClass('hidden');
            $('#backSpinner').removeClass('hidden');
        });
    });
</script>
@endpush
@endsection