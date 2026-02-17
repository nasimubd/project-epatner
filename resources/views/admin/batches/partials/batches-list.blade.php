<!-- Batches Table/Cards -->
<div class="bg-white rounded-xl shadow-lg border border-gray-200">
    <!-- Desktop Table View -->
    <div class="hidden lg:block overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                <tr>
                    <th class="px-4 py-4 text-left">
                        <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    </th>
                    <th class="px-4 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Product</th>
                    <th class="px-4 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Batch Info</th>
                    <th class="px-4 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Pricing</th>
                    <th class="px-4 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Stock</th>
                    <th class="px-4 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Dates</th>
                    <th class="px-4 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($batches as $batch)
                <tr class="hover:bg-gray-50 transition-colors duration-200">
                    <td class="px-4 py-4">
                        <input type="checkbox" value="{{ $batch->id }}" class="batch-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    </td>
                    <td class="px-4 py-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-12 w-12">
                                @if($batch->product && isset($batch->product->image_base64))
                                <img class="h-12 w-12 rounded-lg object-cover shadow-sm border-2 border-white"
                                    src="data:image/jpeg;base64,{{ $batch->product->image_base64 }}"
                                    alt="{{ $batch->product->name ?? 'Product' }}">
                                @else
                                <div class="h-12 w-12 rounded-lg bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center shadow-sm border-2 border-white">
                                    <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                    </svg>
                                </div>
                                @endif
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-semibold text-gray-900">
                                    {{ $batch->product->name ?? 'Unknown Product' }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ $batch->product->category->name ?? 'No Category' }}
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-4">
                        <div class="text-sm font-medium text-gray-900">{{ $batch->batch_number }}</div>
                        <div class="text-xs text-gray-500">
                            @if($batch->is_opening_batch)
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                Opening Batch
                            </span>
                            @endif
                        </div>
                    </td>
                    <td class="px-4 py-4">
                        <div class="text-sm text-gray-900">
                            <div class="flex flex-col space-y-1">
                                <span class="text-xs text-gray-500">Dealer: <span class="font-medium text-gray-900">৳{{ number_format($batch->dealer_price, 2) }}</span></span>
                                <span class="text-xs text-gray-500">Trade: <span class="font-medium text-gray-900">৳{{ number_format($batch->trade_price, 2) }}</span></span>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-4">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ 
                            $batch->remaining_quantity > 10 ? 'bg-green-100 text-green-800' : 
                            ($batch->remaining_quantity > 0 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') 
                        }}">
                            {{ $batch->remaining_quantity }}
                            @if($batch->remaining_quantity <= 0)
                                <svg class="ml-1 h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                </svg>
                                @endif
                        </span>
                    </td>
                    <td class="px-4 py-4">
                        <div class="text-sm text-gray-900">
                            <div class="flex flex-col space-y-1">
                                <span class="text-xs text-gray-500">Batch: <span class="font-medium">{{ \Carbon\Carbon::parse($batch->batch_date)->format('M d, Y') }}</span></span>
                                <span class="text-xs {{ \Carbon\Carbon::parse($batch->expiry_date)->isPast() ? 'text-red-600' : 'text-gray-500' }}">
                                    Expiry: <span class="font-medium">{{ \Carbon\Carbon::parse($batch->expiry_date)->format('M d, Y') }}</span>
                                    @if(\Carbon\Carbon::parse($batch->expiry_date)->isPast())
                                    <span class="ml-1 text-red-500">⚠️</span>
                                    @endif
                                </span>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-4 text-right">
                        <div class="flex items-center justify-end space-x-2">
                            <a href="{{ route('admin.batches.edit', $batch) }}"
                                class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 transition-colors duration-200">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                Edit
                            </a>
                            <form action="{{ route('admin.batches.destroy', $batch) }}" method="POST" class="inline-block">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md text-red-700 bg-red-100 hover:bg-red-200 transition-colors duration-200"
                                    onclick="return confirm('Delete this batch?')">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center">
                        <div class="flex flex-col items-center">
                            <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                            </svg>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No batches found</h3>
                            <p class="text-gray-500">No batches match your current filters.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Mobile Card View -->
    <div class="lg:hidden">
        <div class="p-4 border-b border-gray-200">
            <label class="flex items-center">
                <input type="checkbox" id="selectAllMobile" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-2">
                <span class="text-sm font-medium text-gray-700">Select All</span>
            </label>
        </div>

        @forelse($batches as $batch)
        <div class="p-4 border-b border-gray-200 hover:bg-gray-50 transition-colors duration-200">
            <div class="flex items-start space-x-3">
                <input type="checkbox" value="{{ $batch->id }}" class="batch-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500 mt-1">

                <div class="flex-shrink-0">
                    @if($batch->product && isset($batch->product->image_base64))
                    <img class="h-16 w-16 rounded-lg object-cover shadow-sm border-2 border-white"
                        src="data:image/jpeg;base64,{{ $batch->product->image_base64 }}"
                        alt="{{ $batch->product->name ?? 'Product' }}">
                    @else
                    <div class="h-16 w-16 rounded-lg bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center shadow-sm border-2 border-white">
                        <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                    @endif
                </div>

                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h3 class="text-sm font-semibold text-gray-900 truncate">
                                {{ $batch->product->name ?? 'Unknown Product' }}
                            </h3>
                            <p class="text-xs text-gray-500 mt-1">{{ $batch->product->category->name ?? 'No Category' }}</p>

                            <div class="mt-2 flex items-center space-x-2">
                                <span class="text-xs font-medium text-gray-700">{{ $batch->batch_number }}</span>
                                @if($batch->is_opening_batch)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    Opening
                                </span>
                                @endif
                            </div>
                        </div>

                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ 
                            $batch->remaining_quantity > 10 ? 'bg-green-100 text-green-800' : 
                            ($batch->remaining_quantity > 0 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') 
                        }}">
                            {{ $batch->remaining_quantity }}
                        </span>
                    </div>

                    <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                        <div>
                            <span class="text-gray-500">Dealer Price:</span>
                            <span class="font-medium text-gray-900">৳{{ number_format($batch->dealer_price, 2) }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Trade Price:</span>
                            <span class="font-medium text-gray-900">৳{{ number_format($batch->trade_price, 2) }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Batch Date:</span>
                            <span class="font-medium text-gray-900">{{ \Carbon\Carbon::parse($batch->batch_date)->format('M d, Y') }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Expiry:</span>
                            <span class="font-medium {{ \Carbon\Carbon::parse($batch->expiry_date)->isPast() ? 'text-red-600' : 'text-gray-900' }}">
                                {{ \Carbon\Carbon::parse($batch->expiry_date)->format('M d, Y') }}
                                @if(\Carbon\Carbon::parse($batch->expiry_date)->isPast())
                                ⚠️
                                @endif
                            </span>
                        </div>
                    </div>

                    <div class="mt-3 flex space-x-2">
                        <a href="{{ route('admin.batches.edit', $batch) }}"
                            class="flex-1 inline-flex items-center justify-center px-3 py-2 border border-transparent text-xs font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 transition-colors duration-200">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            Edit
                        </a>
                        <form action="{{ route('admin.batches.destroy', $batch) }}" method="POST" class="flex-1">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="w-full inline-flex items-center justify-center px-3 py-2 border border-transparent text-xs font-medium rounded-md text-red-700 bg-red-100 hover:bg-red-200 transition-colors duration-200"
                                onclick="return confirm('Delete this batch?')">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="p-8 text-center">
            <div class="flex flex-col items-center">
                <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No batches found</h3>
                <p class="text-gray-500 text-center">No batches match your current filters. Try adjusting your search criteria.</p>
            </div>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($batches->hasPages())
    <div class="px-4 py-3 bg-gray-50 border-t border-gray-200 sm:px-6 rounded-b-xl">
        <div class="flex items-center justify-between">
            <div class="flex-1 flex justify-between sm:hidden">
                @if ($batches->onFirstPage())
                <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default leading-5 rounded-md">
                    Previous
                </span>
                @else
                <a href="{{ $batches->previousPageUrl() }}" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 rounded-md hover:text-gray-500 focus:outline-none focus:ring ring-gray-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150">
                    Previous
                </a>
                @endif

                @if ($batches->hasMorePages())
                <a href="{{ $batches->nextPageUrl() }}" class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 rounded-md hover:text-gray-500 focus:outline-none focus:ring ring-gray-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150">
                    Next
                </a>
                @else
                <span class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default leading-5 rounded-md">
                    Next
                </span>
                @endif
            </div>

            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-gray-700 leading-5">
                        Showing
                        <span class="font-medium">{{ $batches->firstItem() ?? 0 }}</span>
                        to
                        <span class="font-medium">{{ $batches->lastItem() ?? 0 }}</span>
                        of
                        <span class="font-medium">{{ $batches->total() }}</span>
                        results
                    </p>
                </div>

                <div>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                        {{-- Previous Page Link --}}
                        @if ($batches->onFirstPage())
                        <span aria-disabled="true" aria-label="Previous">
                            <span class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default rounded-l-md leading-5" aria-hidden="true">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </span>
                        @else
                        <a href="{{ $batches->previousPageUrl() }}" rel="prev" class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-l-md leading-5 hover:text-gray-400 focus:z-10 focus:outline-none focus:ring ring-gray-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-500 transition ease-in-out duration-150" aria-label="Previous">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </a>
                        @endif

                        {{-- Pagination Elements --}}
                        @foreach ($batches->getUrlRange(1, $batches->lastPage()) as $page => $url)
                        @if ($page == $batches->currentPage())
                        <span aria-current="page">
                            <span class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-white bg-blue-600 border border-blue-600 cursor-default leading-5">{{ $page }}</span>
                        </span>
                        @else
                        <a href="{{ $url }}" class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 hover:text-gray-500 focus:z-10 focus:outline-none focus:ring ring-gray-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150" aria-label="Go to page {{ $page }}">{{ $page }}</a>
                        @endif
                        @endforeach

                        {{-- Next Page Link --}}
                        @if ($batches->hasMorePages())
                        <a href="{{ $batches->nextPageUrl() }}" rel="next" class="relative inline-flex items-center px-2 py-2 -ml-px text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-r-md leading-5 hover:text-gray-400 focus:z-10 focus:outline-none focus:ring ring-gray-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-500 transition ease-in-out duration-150" aria-label="Next">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                        </a>
                        @else
                        <span aria-disabled="true" aria-label="Next">
                            <span class="relative inline-flex items-center px-2 py-2 -ml-px text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default rounded-r-md leading-5" aria-hidden="true">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </span>
                        @endif
                    </nav>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<script>
    // Handle mobile select all checkbox
    document.addEventListener('DOMContentLoaded', function() {
        const selectAllMobile = document.getElementById('selectAllMobile');
        if (selectAllMobile) {
            selectAllMobile.addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.batch-checkbox');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                    // Trigger change event to update the selection
                    checkbox.dispatchEvent(new Event('change'));
                });
            });
        }
    });
</script>