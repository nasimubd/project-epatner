@extends('admin.layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8 sm:px-6 lg:px-8">
    <div class="bg-white shadow-xl rounded-lg overflow-hidden">
        <!-- Header Section -->
        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex flex-col sm:flex-row justify-between items-center">
            <h2 class="text-2xl font-bold text-gray-800 mb-4 sm:mb-0">
                <i class="fas fa-file-import mr-2 text-blue-500"></i>Import Categories
            </h2>
            <a href="{{ route('admin.inventory.categories.index') }}"
                class="bg-gradient-to-r from-gray-500 to-gray-600 hover:from-gray-600 hover:to-gray-700 text-white font-bold py-2.5 px-6 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 shadow-md flex items-center">
                <span class="inline-flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Back to Categories</span>
                </span>
            </a>
        </div>

        <!-- Description Section -->
        <div class="bg-blue-50 px-6 py-4 border-b border-blue-100">
            <p class="text-blue-700">
                <i class="fas fa-info-circle mr-2"></i>
                Import categories from the common database to your business. Categories that are already imported will be marked as "Imported".
            </p>
        </div>

        <!-- Responsive Table -->
        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-gray-200">
                <thead class="bg-gray-100">
                    <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <th class="px-6 py-3">Name</th>
                        <th class="px-6 py-3 hidden md:table-cell">Slug</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($commonCategories as $category)
                    <tr class="hover:bg-gray-50 transition duration-200">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $category->category_name }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap hidden md:table-cell">
                            <div class="text-sm text-gray-500">{{ $category->slug }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                Active
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            @if(in_array($category->slug, $existingCategorySlugs))
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                Already Imported
                            </span>
                            @else
                            <button type="button"
                                onclick="showImportModal('{{ $category->category_id }}', '{{ addslashes($category->category_name) }}')"
                                class="bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 shadow-md">
                                Import
                            </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                            <div class="flex flex-col items-center justify-center">
                                <i class="fas fa-box-open text-4xl text-gray-300 mb-4"></i>
                                <p>No categories found in common database</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div id="importModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                <i class="fas fa-file-import text-green-600 text-xl"></i>
            </div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 mt-2">Import Category</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500" id="modalCategoryName"></p>
            </div>
            <form id="importForm" action="{{ route('admin.categories.import') }}" method="POST">
                @csrf
                <input type="hidden" name="common_category_id" id="commonCategoryId">

                <div class="mb-4">
                    <label for="ledger_id" class="block text-gray-700 text-sm font-bold mb-2">Select Supplier Ledger</label>
                    <select name="ledger_id" id="ledger_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                        <option value="">Select a supplier ledger</option>
                        @foreach($ledgers as $ledger)
                        <option value="{{ $ledger->id }}">{{ $ledger->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center justify-between px-4 py-3">
                    <button type="button" id="closeModal" class="px-4 py-2 bg-gray-300 text-gray-800 text-base font-medium rounded-md shadow-sm hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white text-base font-medium rounded-md shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                        Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
<style>
    /* Additional custom styles can be added here */
    @media (max-width: 640px) {
        .container {
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    function showImportModal(categoryId, categoryName) {
        document.getElementById('commonCategoryId').value = categoryId;
        document.getElementById('modalCategoryName').textContent = 'You are about to import: ' + categoryName;
        document.getElementById('importModal').classList.remove('hidden');
    }

    document.getElementById('closeModal').addEventListener('click', function() {
        document.getElementById('importModal').classList.add('hidden');
    });

    // Close modal when clicking outside of it
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('importModal');
        if (event.target === modal) {
            modal.classList.add('hidden');
        }
    });
</script>
@endpush
@endsection