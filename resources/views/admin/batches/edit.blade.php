@extends('admin.layouts.app')

@section('content')
<div class="p-2 sm:p-4">
    <div class="max-w-xl mx-auto bg-white rounded-lg shadow">
        <div class="flex items-center justify-between p-3 border-b">
            <h2 class="text-lg font-medium">Edit Batch</h2>
            <a href="{{ route('admin.batches.index') }}" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </a>
        </div>

        <!-- Adjustment Notice -->
        <div class="p-3 bg-yellow-50 border-l-4 border-yellow-400">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        <strong>Note:</strong> You can only adjust Dealer Price, Trade Price, and Quantity.
                        Any changes will automatically create accounting adjustment entries.
                    </p>
                </div>
            </div>
        </div>

        <form action="{{ route('admin.batches.update', $batch) }}" method="POST" class="p-3 space-y-4">
            @csrf
            @method('PUT')

            <div class="space-y-4">
                <!-- Product and Batch Number (Read-only Display) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Product</label>
                        <div class="w-full rounded border-gray-300 text-sm bg-gray-100 px-3 py-2">
                            {{ $batch->product->name }}
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Product cannot be changed</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Batch Number</label>
                        <div class="w-full rounded border-gray-300 text-sm bg-gray-100 px-3 py-2">
                            {{ $batch->batch_number }}
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Batch number cannot be changed</p>
                    </div>
                </div>

                <!-- Adjustable Prices -->
                <div class="bg-blue-50 p-3 rounded-lg border border-blue-200">
                    <h3 class="text-sm font-semibold text-blue-800 mb-3">Adjustable Fields</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-blue-700 mb-1">
                                Dealer Price <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-2 flex items-center text-gray-500">৳</span>
                                <input type="number" step="0.01" name="dealer_price" value="{{ $batch->dealer_price }}"
                                    class="w-full pl-6 rounded border-blue-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                    required>
                            </div>
                            <p class="text-xs text-blue-600 mt-1">Original: ৳{{ number_format($batch->dealer_price, 2) }}</p>
                            @error('dealer_price')<span class="text-xs text-red-500">{{ $message }}</span>@enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-blue-700 mb-1">
                                Trade Price <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-2 flex items-center text-gray-500">৳</span>
                                <input type="number" step="0.01" name="trade_price" value="{{ $batch->trade_price }}"
                                    class="w-full pl-6 rounded border-blue-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                    required>
                            </div>
                            <p class="text-xs text-blue-600 mt-1">Original: ৳{{ number_format($batch->trade_price, 2) }}</p>
                            @error('trade_price')<span class="text-xs text-red-500">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="block text-sm font-medium text-blue-700 mb-1">
                            Quantity <span class="text-red-500">*</span>
                        </label>
                        <input type="number"
                            step="0.001"
                            name="remaining_quantity"
                            value="{{ $batch->remaining_quantity }}"
                            class="w-full rounded border-blue-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                            min="0"
                            required>
                        <p class="text-xs text-blue-600 mt-1">Original: {{ number_format($batch->remaining_quantity, 3) }} units</p>
                        @error('remaining_quantity')<span class="text-xs text-red-500">{{ $message }}</span>@enderror
                    </div>

                </div>

                <!-- Read-only Information Display -->
                <div class="bg-gray-50 p-3 rounded-lg border border-gray-200">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">Read-only Information</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Batch Date</label>
                            <div class="w-full rounded border-gray-300 text-sm bg-gray-100 px-3 py-2">
                                {{ \Carbon\Carbon::parse($batch->batch_date)->format('d M, Y') }}
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Batch date cannot be changed</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Expiry Date</label>
                            <div class="w-full rounded border-gray-300 text-sm bg-gray-100 px-3 py-2">
                                {{ $batch->expiry_date ? \Carbon\Carbon::parse($batch->expiry_date)->format('d M, Y') : 'Not set' }}
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Expiry date cannot be changed</p>
                        </div>
                    </div>

                    <!-- Opening Batch Status Display -->
                    <div class="mt-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Batch Type</label>
                        <div class="w-full rounded border-gray-300 text-sm bg-gray-100 px-3 py-2">
                            {{ $batch->is_opening_batch ? 'Opening Batch' : 'Regular Batch' }}
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Batch type cannot be changed</p>
                    </div>
                </div>

                <!-- Value Calculation Display -->
                <div class="bg-green-50 p-3 rounded-lg border border-green-200">
                    <h3 class="text-sm font-semibold text-green-800 mb-2">Value Calculation</h3>
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <span class="text-green-700">Original Value:</span>
                            <span class="font-medium">৳{{ number_format($batch->dealer_price * $batch->remaining_quantity, 2) }}</span>
                        </div>
                        <div>
                            <span class="text-green-700">Current Value:</span>
                            <span class="font-medium" id="currentValue">৳{{ number_format($batch->dealer_price * $batch->remaining_quantity, 2) }}</span>
                        </div>
                    </div>
                    <div class="mt-2 pt-2 border-t border-green-200">
                        <span class="text-green-700">Adjustment Amount:</span>
                        <span class="font-medium" id="adjustmentAmount">৳0.00</span>
                        <span class="text-xs text-green-600 ml-2" id="adjustmentNote">(No changes)</span>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-2 pt-4 border-t">
                <a href="{{ route('admin.batches.index') }}"
                    class="px-4 py-2 text-sm border rounded text-gray-600 hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit"
                    class="px-4 py-2 text-sm rounded bg-blue-600 text-white hover:bg-blue-700">
                    Update & Create Adjustment Entry
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Store PHP values in data attributes -->
<div id="batch-data"
    data-original-dealer-price="{{ $batch->dealer_price }}"
    data-original-quantity="{{ $batch->remaining_quantity }}"
    style="display: none;">
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const dealerPriceInput = document.querySelector('input[name="dealer_price"]');
        const quantityInput = document.querySelector('input[name="remaining_quantity"]');
        const currentValueSpan = document.getElementById('currentValue');
        const adjustmentAmountSpan = document.getElementById('adjustmentAmount');
        const adjustmentNoteSpan = document.getElementById('adjustmentNote');

        // Get original values from data attributes
        const batchData = document.getElementById('batch-data');
        const originalDealerPrice = parseFloat(batchData.dataset.originalDealerPrice);
        const originalQuantity = parseFloat(batchData.dataset.originalQuantity);
        const originalValue = originalDealerPrice * originalQuantity;

        function formatCurrency(amount) {
            return '৳' + amount.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function updateCalculations() {
            const newDealerPrice = parseFloat(dealerPriceInput.value) || 0;
            const newQuantity = parseFloat(quantityInput.value) || 0;
            const newValue = newDealerPrice * newQuantity;
            const adjustment = newValue - originalValue;

            currentValueSpan.textContent = formatCurrency(newValue);
            adjustmentAmountSpan.textContent = formatCurrency(Math.abs(adjustment));

            if (adjustment > 0) {
                adjustmentAmountSpan.className = 'font-medium text-red-600';
                adjustmentNoteSpan.textContent = '(Increase - will create debit entry)';
                adjustmentNoteSpan.className = 'text-xs text-red-600 ml-2';
            } else if (adjustment < 0) {
                adjustmentAmountSpan.className = 'font-medium text-green-600';
                adjustmentNoteSpan.textContent = '(Decrease - will create credit entry)';
                adjustmentNoteSpan.className = 'text-xs text-green-600 ml-2';
            } else {
                adjustmentAmountSpan.className = 'font-medium text-gray-600';
                adjustmentNoteSpan.textContent = '(No changes)';
                adjustmentNoteSpan.className = 'text-xs text-gray-600 ml-2';
            }
        }

        // Add event listeners
        if (dealerPriceInput) {
            dealerPriceInput.addEventListener('input', updateCalculations);
        }

        if (quantityInput) {
            quantityInput.addEventListener('input', updateCalculations);
        }
    });
</script>
@endsection