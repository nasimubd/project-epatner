@extends('admin.layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6 sm:px-6 lg:px-8">
    <div class="bg-white shadow-xl rounded-lg overflow-hidden">
        <!-- Header Section - Made more responsive -->
        <div class="bg-gray-50 px-4 py-4 sm:px-6 border-b border-gray-200 flex flex-col sm:flex-row justify-between items-center">
            <h2 class="text-xl sm:text-2xl font-bold text-gray-800 mb-4 sm:mb-0">
                <i class="fas fa-tags mr-2 text-blue-500"></i>Categories Management
            </h2>
            <div class="flex flex-col sm:flex-row w-full sm:w-auto gap-3">
                <a href="{{ route('admin.categories.common') }}"
                    class="bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white font-bold py-2 px-4 sm:px-6 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 shadow-md flex items-center justify-center group">
                    <span class="inline-flex items-center">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        <span class="text-sm sm:text-base">Import Categories</span>
                    </span>
                </a>
                <a href="{{ route('admin.categories.create') }}" id="addCategoryBtn"
                    class="bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-bold py-2 px-4 sm:px-6 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 shadow-md flex items-center justify-center group">
                    <span class="inline-flex items-center">
                        <svg id="categoryIcon" class="w-4 h-4 sm:w-5 sm:h-5 mr-2 transition-transform group-hover:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <svg id="categorySpinner" class="hidden w-4 h-4 sm:w-5 sm:h-5 mr-2 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span id="categoryButtonText" class="text-sm sm:text-base">Add New Category</span>
                    </span>
                </a>
            </div>
        </div>

        <!-- Mobile-optimized Table -->
        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-gray-200">
                <thead class="bg-gray-100">
                    <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <th class="px-4 py-3 sm:px-6">Name</th>
                        <th class="px-4 py-3 sm:px-6 hidden md:table-cell">Slug</th>
                        <th class="px-4 py-3 sm:px-6">Status</th>
                        <th class="px-4 py-3 sm:px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($categories as $category)
                    <tr class="hover:bg-gray-50 transition duration-200">
                        <td class="px-4 py-3 sm:px-6 whitespace-normal">
                            <div class="flex items-center">
                                <div class="ml-0 sm:ml-4">
                                    <div class="text-xs sm:text-sm font-medium text-gray-900 break-words">
                                        {{ $category->name }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 sm:px-6 whitespace-normal hidden md:table-cell">
                            <div class="text-xs sm:text-sm text-gray-500 break-words">{{ $category->slug }}</div>
                        </td>
                        <td class="px-4 py-3 sm:px-6 whitespace-nowrap">
                            <span class="status-text text-xs font-semibold rounded-full px-2 py-1 
                                {{ $category->status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $category->status ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 sm:px-6 whitespace-nowrap text-right text-xs sm:text-sm font-medium">
                            <div class="flex flex-col sm:flex-row justify-end gap-2 sm:space-x-3">
                                <!-- Edit Button - Responsive sizing -->
                                <a href="{{ route('admin.categories.edit', $category) }}" id="editBtn{{ $category->id }}"
                                    class="bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-bold py-1.5 sm:py-2 px-3 sm:px-4 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 shadow-md flex items-center justify-center group">
                                    <span class="inline-flex items-center">
                                        <svg id="editIcon{{ $category->id }}" class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        <svg id="editSpinner{{ $category->id }}" class="hidden w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span id="editText{{ $category->id }}" class="text-xs sm:text-sm">EDIT</span>
                                    </span>
                                </a>

                                <!-- Delete Button - Responsive sizing -->
                                <button type="button"
                                    class="delete-category-btn bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white font-bold py-1.5 sm:py-2 px-3 sm:px-4 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 shadow-md flex items-center justify-center group"
                                    data-category-id="{{ $category->id }}"
                                    data-category-name="{{ $category->name }}">
                                    <span class="inline-flex items-center">
                                        <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        <span class="text-xs sm:text-sm">DELETE</span>
                                    </span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 sm:px-6 text-center text-gray-500">
                            <div class="flex flex-col items-center justify-center">
                                <i class="fas fa-box-open text-3xl sm:text-4xl text-gray-300 mb-3 sm:mb-4"></i>
                                <p class="text-sm sm:text-base">No categories found</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination - Made responsive -->
        <div class="bg-white px-4 py-3 sm:px-6 border-t border-gray-200">
            {{ $categories->links('vendor.pagination.tailwind') }}
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal - Made responsive -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-auto overflow-hidden">
        <div class="bg-red-50 px-4 py-3 sm:px-6 border-b border-red-100">
            <h3 class="text-lg font-medium text-red-800">Confirm Deletion</h3>
        </div>
        <div class="p-4 sm:p-6">
            <p class="text-sm sm:text-base text-gray-700">
                Are you sure you want to delete the category <span id="categoryNameToDelete" class="font-semibold"></span>?
                This action cannot be undone ALL <b>THE PRODUCTS IN THIS CATEGORY & CONTRUBUTOR RECORD</b> WILL BE DELETED.
            </p>
            <div class="mt-6 flex flex-col sm:flex-row gap-3 sm:gap-4 justify-end">
                <button id="cancelDelete" type="button" class="inline-flex justify-center items-center px-4 py-2 border border-gray-300 shadow-sm text-xs sm:text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    Cancel
                </button>
                <form id="deleteCategoryForm" method="POST" action="">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex justify-center items-center px-4 py-2 border border-transparent shadow-sm text-xs sm:text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        Delete Category
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
<style>
    /* Toggle Switch Styles */
    .toggle-checkbox:checked {
        right: 0;
        border-color: #68D391;
    }

    .toggle-checkbox:checked+.toggle-label {
        background-color: #68D391;
    }

    .toggle-label {
        transition: background-color 0.3s ease;
    }

    .toggle-checkbox {
        transition: all 0.3s ease;
        right: 4px;
        border-color: #CBD5E0;
    }

    /* Mobile optimizations */
    @media (max-width: 640px) {
        .container {
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }

        /* Make table rows more readable on small screens */
        tbody tr {
            display: flex;
            flex-direction: column;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e5e7eb;
        }

        /* Hide table headers on small screens */
        @media (max-width: 480px) {
            thead {
                display: none;
            }

            tbody td {
                display: flex;
                justify-content: space-between;
                padding: 0.5rem 1rem;
                border-bottom: none;
            }

            tbody td:before {
                content: attr(data-label);
                font-weight: 600;
                margin-right: 1rem;
            }
        }
    }
</style>
@endpush

@push('scripts')
<script>
    $(document).ready(function() {
        $('#addCategoryBtn').on('click', function() {
            $('#categoryIcon').addClass('hidden');
            $('#categorySpinner').removeClass('hidden');
            $('#categoryButtonText').text('Loading...');
        });

        // Edit button click handler
        $('[id^="editBtn"]').on('click', function() {
            const id = this.id.replace('editBtn', '');
            $(`#editIcon${id}`).addClass('hidden');
            $(`#editSpinner${id}`).removeClass('hidden');
            $(`#editText${id}`).text('Editing...');
        });

        // Delete category functionality
        $('.delete-category-btn').on('click', function() {
            const categoryId = $(this).data('category-id');
            const categoryName = $(this).data('category-name');

            // Set the category name in the modal
            $('#categoryNameToDelete').text(categoryName);

            // Set the form action URL using the correct route pattern
            $('#deleteCategoryForm').attr('action', "{{ route('admin.inventory.categories.destroy', '') }}/" + categoryId);

            // Show the modal
            $('#deleteModal').removeClass('hidden').addClass('flex');
        });

        // Cancel delete button
        $('#cancelDelete').on('click', function() {
            $('#deleteModal').removeClass('flex').addClass('hidden');
        });

        // Close modal when clicking outside
        $('#deleteModal').on('click', function(e) {
            if (e.target === this) {
                $(this).removeClass('flex').addClass('hidden');
            }
        });
    });
</script>
@endpush
@endsection