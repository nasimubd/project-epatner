@php
use Illuminate\Support\Facades\Auth;
@endphp

<form id="saleForm" action="{{ route('admin.inventory.inventory_transactions.store') }}" method="POST" class="bg-white h-full">
    @csrf
    <input type="hidden" name="entry_type" value="sale">

    <!-- Header Info -->
    <div class="p-2 border-b space-y-2">
        <div>
            <label class="text-xs text-gray-600">Date</label>
            <input type="datetime-local" name="transaction_date"
                value="{{ now()->format('Y-m-d\TH:i') }}"
                class="w-full rounded border-gray-300 text-sm py-1">
        </div>
        <div>
            <label class="text-xs text-gray-600">Payment</label>
            <select name="payment_method" class="w-full rounded border-gray-300 text-sm py-1">
                @if(Auth::user()->hasRole('staff'))
                <option value="credit" selected>Credit</option>
                @else
                <!-- <option value="cash">Select Payment Type</option> -->
                <option value="cash">Cash</option>
                <!-- <option value="credit">Credit</option> -->
                @endif
            </select>
        </div>
        <div>
            <label class="text-xs text-gray-600">Customer</label>
            <div class="flex flex-col space-y-2">
                <!-- Village Filter -->
                <select id="locationFilter" class="w-full rounded border-gray-300 select2-location text-xs py-1">
                    <option value="">All Villages</option>
                    @foreach($uniqueVillages as $village)
                    <option value="{{ $village }}">{{ $village }}</option>
                    @endforeach
                </select>

                <!-- Customer Ledger Select with Search -->
                <select name="ledger_id" id="customerLedgerSelect">
                    <option value="">Select Customer</option>
                    @foreach($customerLedgers as $customer)
                    <option value="{{ $customer->id }}"
                        data-village="{{ $customer->village ?? '' }}"
                        data-is-common="{{ $customer->is_common ? 'true' : 'false' }}"
                        data-exists-locally="{{ $customer->exists_locally ? 'true' : 'false' }}"
                        data-local-id="{{ $customer->local_customer_id ?? '' }}">
                        {{ $customer->name }}@if($customer->location) - {{ $customer->location }}@endif
                        @if($customer->exists_locally)
                        <span class="text-green-600">(STN)</span>
                        @endif
                    </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
    <!-- Product Lines Container -->
    <div id="productLinesContainer" class="flex-1 overflow-y-auto p-2">
        <!-- Product lines will be added here -->
    </div>


    <div id="damagedProductLines" class="hidden">
        <!-- Damage lines will be dynamically added here -->
    </div>


    <!-- Totals Section -->
    <div class="border-t bg-gray-50 p-2 sticky bottom-0">
        <div class="space-y-2">
            <div class="flex justify-between items-center">
                <span class="text-gray-600">Sub Total:</span>
                <span id="subtotal" class="font-medium">৳0.00</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-gray-600">Damage Total:</span>
                <span id="damageTotal" class="font-medium text-red-600">৳0.00</span>
                <input type="hidden" name="damage_total" id="damageTotalInput" value="0">
            </div>
            <!-- <div class="flex justify-between items-center">
                <span class="text-gray-600">Discount:</span>
                <input type="number" name="discount" id="discount"
                    class="w-24 text-right rounded border-gray-300 py-0 text-sm" value="0">
            </div> -->
            <div class="flex justify-between items-center">
                <span class="text-gray-600">Round Off:</span>
                <input type="number" name="round_off" id="roundOff"
                    class="w-24 text-right rounded border-gray-300 py-0 text-sm" value="0" step="0.01">
            </div>
            <div class="flex justify-between items-center pt-2 border-t">
                <span class="font-medium">Net Total:</span>
                <span id="grandTotal" class="text-lg font-bold text-blue-600">৳0.00</span>
            </div>
        </div>

        <div class="flex flex-col space-y-3 mt-4">
            <!-- Submit Sale Button -->
            <button type="button" id="submitTransaction"
                class="w-full bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-bold py-2.5 px-6 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 shadow-md flex items-center justify-center group">
                <span class="inline-flex items-center">
                    <svg id="submitIcon" class="w-5 h-5 mr-2 transition-transform group-hover:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <svg id="submitSpinner" class="hidden w-5 h-5 mr-2 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span id="submitButtonText">Submit Sale</span>
                </span>
            </button>

            <button type="button" id="appendTransaction" style="display: none;"
                class="w-full bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-bold py-2.5 px-6 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 shadow-md flex items-center justify-center group">
                <span class="inline-flex items-center">
                    <svg id="appendIcon" class="w-5 h-5 mr-2 transition-transform group-hover:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <svg id="appendSpinner" class="hidden w-5 h-5 mr-2 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span id="appendButtonText">Add to Invoice</span>
                </span>
            </button>

            <!-- Clear Button -->
            <button type="button" onclick="clearForm()" id="clearBtn"
                class="w-full bg-gradient-to-r from-gray-100 to-gray-200 hover:from-gray-200 hover:to-gray-300 text-gray-700 font-bold py-2.5 px-6 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 shadow-md flex items-center justify-center group">
                <span class="inline-flex items-center">
                    <svg id="clearIcon" class="w-5 h-5 mr-2 transition-transform group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    <svg id="clearSpinner" class="hidden w-5 h-5 mr-2 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span id="clearButtonText">Clear</span>
                </span>
            </button>
        </div>
    </div>

    <input type="hidden" name="subtotal" id="subtotalInput">
    <input type="hidden" name="grand_total" id="grandTotalInput">
</form>

<!-- Template for new product line -->
<template id="productLineTemplate">
    <div class="line-item border-b border-gray-300 pb-3 mb-3 bg-gray-50/30" data-product-id="">
        <!-- Product Header -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2 p-2">
            <div class="flex-grow w-full sm:w-auto">
                <select class="product-selector select2 w-full text-sm font-semibold">
                    <option value="">Select Product</option>
                    @foreach($products->groupBy('category.name') as $category => $categoryProducts)
                    <optgroup label="{{ $category }}" data-category-id="{{ $categoryProducts->first()->category_id }}">
                        @foreach($categoryProducts as $product)
                        @foreach($product->batches->where('remaining_quantity', '>', 0) as $batch)
                        <option value="{{ $product->id }}"
                            data-batch-id="{{ $batch->id }}"
                            data-price="{{ $product->trade_price }}"
                            data-stock="{{ $batch->remaining_quantity }}">
                            {{ $product->name }} - Batch: {{ $batch->batch_number }} ({{ $batch->remaining_quantity }} units)
                        </option>
                        @endforeach
                        @endforeach
                    </optgroup>
                    @endforeach
                </select>
            </div>
            <div class="text-right">
                <span class="text-sm font-bold">৳<span class="line-total">0.00</span></span>
            </div>
        </div>

        <!-- Product Details -->
        <div class="flex flex-wrap items-center gap-2 px-2">
            <div class="flex flex-wrap items-center gap-2">
                <div class="flex items-center gap-1">
                    <span class="text-xs text-gray-600">Qty:</span>
                    <input type="number" class="quantity w-16 rounded border-gray-300 text-sm" required>
                </div>
                <div class="flex items-center gap-1">
                    <span class="text-xs text-gray-600">Price:</span>
                    <input type="number" step="0.01" class="unit-price w-20 rounded border-gray-300 text-sm" required>
                </div>
                <div class="flex items-center gap-1">
                    <span class="text-xs text-gray-600">TP:</span>
                    <input type="hidden" class="trade-price" value="0">
                    <span class="trade-price-display">0</span>
                </div>
                <div class="flex items-center gap-1">
                    <span class="text-xs text-gray-600">O/D:</span>
                    <span class="price-difference font-medium text-sm">0</span>
                </div>
            </div>

            <!-- Remove Button -->
            <button type="button"
                onclick="TransactionManager.removeLine(this.closest('.line-item'))"
                class="text-red-500 hover:text-red-700 p-1 ml-auto">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>
</template>



<!-- End of Template -->

<!-- Damage Product Modal -->
<template id="damagedProductLineTemplate">
    <div class="line-item border-b border-gray-300 pb-3 mb-3 bg-red-50/30" data-product-id="">
        <div class="text-red-600 font-semibold text-sm px-2 py-1">Damaged Products</div>

        <!-- Product Header -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2 p-2">
            <div class="flex-grow w-full sm:w-auto">
                <select class="product-selector select2 w-full text-sm font-semibold">
                    <option value="">Select Product</option>
                    @foreach($products->groupBy('category.name') as $category => $categoryProducts)
                    <optgroup label="{{ $category }}">
                        @foreach($categoryProducts as $product)
                        <option value="{{ $product->id }}" data-price="{{ $product->trade_price }}" data-stock="{{ $product->current_stock }}">{{ $product->name }} - {{ $product->barcode }}</option>
                        @endforeach
                    </optgroup>
                    @endforeach
                </select>
            </div>
            <div class="text-right">
                <span class="text-sm font-bold text-red-600"><span class="line-total">0.00</span></span>
            </div>
        </div>


        <!-- Product Details -->
        <div class="flex flex-wrap items-center gap-2 px-2">
            <div class="flex flex-wrap items-center gap-2">
                <div class="flex items-center gap-1">
                    <span class="text-xs text-gray-600">Qty:</span>
                    <input type="number" class="quantity w-16 rounded border-gray-300 text-sm" required>
                </div>
                <div class="flex items-center gap-1">
                    <span class="text-xs text-gray-600">Price:</span>
                    <input type="number" step="0.01" class="unit-price w-20 rounded border-gray-300 text-sm" required>
                </div>
            </div>

            <button type="button"
                onclick="TransactionManager.removeLine(this.closest('.line-item'))"
                class="text-red-500 hover:text-red-700 p-1 ml-auto">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>
</template>


<script>
    function clearForm() {
        // Clear all product lines
        const productLinesContainer = document.getElementById('productLinesContainer');
        productLinesContainer.innerHTML = '';

        // Reset form fields
        document.getElementById('discount').value = '0';
        document.getElementById('roundOff').value = '0';

        // Reset totals
        document.getElementById('subtotal').textContent = '৳0.00';
        document.getElementById('grandTotal').textContent = '৳0.00';
        document.getElementById('damageTotal').textContent = '৳0.00';
        document.getElementById('subtotalInput').value = '0';
        document.getElementById('grandTotalInput').value = '0';
        document.getElementById('damageTotalInput').value = '0';
    }

    $(document).ready(function() {
        $('#submitTransaction').on('click', function() {
            $('#submitIcon').addClass('hidden');
            $('#submitSpinner').removeClass('hidden');
            $('#submitButtonText').text('Submitting...');
            this.disabled = true;
        });

        // $('#clearBtn').on('click', function() {
        //     $('#clearIcon').addClass('hidden');
        //     $('#clearSpinner').removeClass('hidden');
        //     $('#clearButtonText').text('Clearing...');
        //     clearForm();
        // });
    });
</script>