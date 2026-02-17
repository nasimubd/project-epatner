<form id="purchaseForm" action="{{ route('admin.inventory.inventory_transactions.store') }}" method="POST" class="bg-white h-full">
    @csrf
    <input type="hidden" name="entry_type" value="purchase">

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
                <option value="">Select Payment Type</option>
                <option value="cash">Cash</option>
                <option value="credit">Credit</option>
            </select>
        </div>
        <div>
            <label class="text-xs text-gray-600">Supplier</label>
            <select name="ledger_id" class="w-full rounded border-gray-300 select2 text-sm py-1">
                <option value="">Select Supplier</option>
                @foreach($supplierLedgers as $ledger)
                <option value="{{ $ledger->id }}">{{ $ledger->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Product Lines Container -->
    <div id="productLinesContainer">
        <!-- Product lines will be added here -->
    </div>

    <!-- Totals Section -->
    <div class="border-t bg-gray-50 p-2">
        <div class="space-y-2">
            <div class="flex justify-between items-center">
                <span class="text-gray-600">Subtotal:</span>
                <span id="subtotal" class="font-medium">৳0.00</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-gray-600">Discount:</span>
                <input type="number" name="discount" id="discount"
                    class="w-24 text-right rounded border-gray-300 py-0 text-sm" value="0">
            </div>
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
            <!-- Submit Purchase Button -->
            <button type="button" id="submitTransaction"
                class="w-full bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-bold py-2.5 px-6 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 shadow-md flex items-center justify-center group">
                <span class="inline-flex items-center">
                    <svg id="submitIcon" class="w-5 h-5 mr-2 transition-transform group-hover:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <svg id="submitSpinner" class="hidden w-5 h-5 mr-2 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span id="submitButtonText">Submit Purchase</span>
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

    <!-- Hidden inputs for form submission -->
    <input type="hidden" name="subtotal" id="subtotalInput">
    <input type="hidden" name="round_off" id="roundOffInput">
    <input type="hidden" name="discount" id="discountInput">
    <input type="hidden" name="grand_total" id="grandTotalInput">
</form>

<!-- Template for purchase product line -->
<template id="purchaseLineTemplate">
    <div class="line-item border-b border-gray-300 pb-3 mb-3 bg-gray-50/30" data-product-id="" data-trade-price="">
        <!-- Product Header -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2 p-2">
            <div class="flex-grow w-full sm:w-auto">
                <select class="product-selector select2 w-full text-sm font-semibold">
                    <option value="">Select Product</option>
                    @foreach($products->groupBy('category.name') as $category => $categoryProducts)
                    <optgroup label="{{ $category }}">
                        @foreach($categoryProducts as $product)
                        <option value="{{ $product->id }}"
                            data-price="{{ $product->trade_price }}"
                            data-stock="{{ $product->current_stock }}"
                            data-batch-price="{{ $product->batches->first() ? $product->batches->first()->trade_price : $product->trade_price }}">
                            {{ $product->name }} - {{ $product->barcode }}
                        </option>
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
                    <input type="number" class="quantity w-16 rounded border-gray-300 text-sm" required min="1" step="1">
                </div>
                <div class="flex items-center gap-1">
                    <span class="text-xs text-gray-600">Buy:</span>
                    <input type="number" class="dealer-price w-20 rounded border-gray-300 text-sm" required min="0" step="0.01">
                </div>
                <div class="flex items-center gap-1">
                    <span class="text-xs text-gray-600">Sell:</span>
                    <input type="number" class="trade-price w-20 rounded border-gray-300 text-sm" required min="0" step="0.01">
                </div>
                <div class="flex items-center gap-1">
                    <span class="text-xs text-gray-600">Profit:</span>
                    <span class="profit-margin text-xs font-medium text-green-600">0%</span>
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
        document.getElementById('subtotalInput').value = '0';
        document.getElementById('grandTotalInput').value = '0';
        document.getElementById('roundOffInput').value = '0';
        document.getElementById('discountInput').value = '0';
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Initialize event delegation for product selectors
        document.getElementById('productLinesContainer').addEventListener('change', function(e) {
            if (e.target && e.target.classList.contains('product-selector')) {
                const lineItem = e.target.closest('.line-item');
                const selectedOption = e.target.options[e.target.selectedIndex];

                if (selectedOption && selectedOption.value) {
                    // Set the product ID in the dataset
                    lineItem.dataset.productId = selectedOption.value;

                    // Get the trade price from the selected option
                    const tradePrice = selectedOption.getAttribute('data-price');

                    // Store the trade price in the dataset for reference
                    lineItem.dataset.tradePrice = tradePrice;

                    // Set the trade price input with the product's trade price
                    const tradePriceInput = lineItem.querySelector('.trade-price');
                    if (tradePriceInput && tradePrice) {
                        tradePriceInput.value = tradePrice;
                    }

                    // Calculate profit if dealer price is already set
                    const dealerPriceInput = lineItem.querySelector('.dealer-price');
                    if (dealerPriceInput && dealerPriceInput.value) {
                        calculateProfit(dealerPriceInput);
                    }

                    // Trigger calculation
                    if (window.transactionCalculator) {
                        window.transactionCalculator.recalculate();
                    }
                }
            }
        });

        // Add event listeners for quantity, dealer price, and trade price changes
        document.getElementById('productLinesContainer').addEventListener('input', function(e) {
            if (e.target && (e.target.classList.contains('quantity') ||
                    e.target.classList.contains('dealer-price') ||
                    e.target.classList.contains('trade-price'))) {

                // Calculate profit for price changes
                if (e.target.classList.contains('dealer-price') || e.target.classList.contains('trade-price')) {
                    calculateProfit(e.target);
                }

                // Trigger total recalculation
                if (window.transactionCalculator) {
                    window.transactionCalculator.recalculate();
                }
            }
        });

        // Add event listeners for discount and round off changes
        const discountInput = document.getElementById('discount');
        const roundOffInput = document.getElementById('roundOff');

        if (discountInput) {
            discountInput.addEventListener('input', function() {
                document.getElementById('discountInput').value = this.value;
                if (window.transactionCalculator) {
                    window.transactionCalculator.recalculate();
                }
            });
        }

        if (roundOffInput) {
            roundOffInput.addEventListener('input', function() {
                document.getElementById('roundOffInput').value = this.value;
                if (window.transactionCalculator) {
                    window.transactionCalculator.recalculate();
                }
            });
        }
    });
</script>