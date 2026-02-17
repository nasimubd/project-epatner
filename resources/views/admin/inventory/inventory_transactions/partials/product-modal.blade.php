<div class="bg-white rounded-lg shadow-sm h-full product-grid-container">
    <div class="p-3 border-b">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
                <input type="text" id="productSearch" class="w-full px-3 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm" placeholder="Search products...">
            </div>
            <div class="grid grid-cols-2 gap-2">
                <select id="categoryFilter" class="w-full px-3 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
                <div class="flex items-center">
                    <label for="availabilityToggle" class="mr-2 text-sm text-gray-600">Show All</label>
                    <div class="relative inline-block w-10 align-middle select-none">
                        <input type="checkbox" name="availabilityToggle" id="availabilityToggle" class="toggle-checkbox absolute block w-5 h-5 rounded-full bg-white border-4 appearance-none cursor-pointer" />
                        <label for="availabilityToggle" class="toggle-label block overflow-hidden h-5 rounded-full bg-gray-300 cursor-pointer"></label>
                    </div>
                    <span class="ml-2 text-sm text-gray-600">In Stock</span>
                </div>

            </div>
        </div>
    </div>

    <div class="p-3 overflow-y-auto" style="max-height: calc(100vh - 250px);">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2" id="productGrid">
            @foreach($products as $product)
            @php
            $availableBatches = $product->batches->where('remaining_quantity', '>', 0);
            $isCommonProduct = isset($product->is_common) && $product->is_common;
            @endphp

            @if($isCommonProduct || $availableBatches->count() > 0)
            <div class="product-card bg-white rounded-lg shadow-sm border hover:shadow-md transition-shadow duration-200 flex flex-col h-full" data-category-id="{{ $product->category_id }}">
                <!-- Product Image Section - Reduced height -->
                <div class="relative h-24 flex-shrink-0">
                    <img src="{{ !empty($product->image) ? 'data:image/jpeg;base64,'.$product->image : asset('images/default-product.jpeg') }}"
                        alt="{{ $product->name }}"
                        class="w-full h-full object-contain rounded-t-lg">

                    @if(!$isCommonProduct)
                    <!-- Stock badge - Top Right Corner -->
                    <div class="absolute top-1 right-1">
                        <div class="bg-blue-600 text-white px-1.5 py-0.5 rounded-full text-[10px] whitespace-nowrap">
                            {{ $product->current_stock }}
                        </div>
                    </div>

                    <!-- Expiry badge - Top Left Corner -->
                    <!-- <div class="absolute top-1 left-1">
                        <div class="bg-red-600 text-white px-1.5 py-0.5 rounded-full text-[10px] whitespace-nowrap">
                            {{ $availableBatches->first() ? date('d M Y', strtotime($availableBatches->first()->expiry_date)) : 'N/A' }}
                        </div>
                    </div> -->
                    @endif
                </div>


                <!-- Product Details Section - More compact -->
                <div class="p-2 flex-grow flex flex-col">
                    <!-- Product Name - Smaller font size with line clamp -->
                    <h3 class="text-[9px] font-medium text-gray-800 line-clamp-2 min-h-[1rem] product-name" title="{{ $product->name }}">
                        {{ $product->name }}
                    </h3>
                    <div class="space-y-1 mt-1 flex-grow">
                        <!-- Category and Price - Inline to save space -->
                        <div class="flex justify-between items-center">
                            <p class="text-[10px] text-gray-600 truncate max-w-[60%] product-category">{{ $product->category->name }}</p>
                            <p class="text-[10px] font-medium text-gray-800 trade-price-display">৳{{ number_format($product->trade_price, 2) }}</p>
                        </div>

                        @if(!$isCommonProduct)
                        <!-- Batch Selection - Only show batches with remaining quantity > 0 -->
                        <select class="batch-select w-full text-[10px] border rounded py-0.5 mt-1">
                            <option value="">Select Batch</option>
                            @foreach($availableBatches->sortBy('expiry_date') as $batch)
                            <option value="{{ $batch->id }}"
                                data-remaining="{{ $batch->remaining_quantity }}"
                                data-price="{{ $batch->trade_price }}"
                                data-batch-number="{{ $batch->batch_number }}">
                                #{{ $batch->batch_number }} ({{ $batch->remaining_quantity }})
                            </option>
                            @endforeach
                        </select>
                        @else
                        <div class="text-[10px] text-gray-500 italic mt-1">
                            Add to create inventory
                        </div>
                        @endif

                        <!-- Quantity Input -->
                        <input type="number"
                            class="quantity-input w-full text-[10px] border rounded py-0.5"
                            placeholder="Quantity"
                            min="1"
                            @if(!$isCommonProduct && $availableBatches->count() > 0)
                        max="{{ $availableBatches->sum('remaining_quantity') }}"
                        @endif>
                    </div>

                    <!-- Action Buttons - More compact -->
                    <div class="grid {{ $isCommonProduct ? 'grid-cols-1' : 'grid-cols-2' }} gap-1 mt-1">
                        @if($isCommonProduct)
                        <button class="add-product-btn px-1 py-0.5 bg-green-600 text-white rounded text-[10px] hover:bg-green-700 transition-colors duration-200"
                            data-product-id="{{ $product->id }}"
                            data-product-name="{{ $product->name }}"
                            data-trade-price="{{ $product->trade_price }}"
                            data-type="common">
                            Add to Inventory
                        </button>
                        @else
                        <button class="add-product-btn px-1 py-0.5 bg-blue-600 text-white rounded text-[10px] hover:bg-blue-700 transition-colors duration-200"
                            data-product-id="{{ $product->id }}"
                            data-product-name="{{ $product->name }}"
                            data-trade-price="{{ $product->trade_price }}"
                            data-type="regular">
                            Add
                        </button>

                        <button class="add-product-btn damaged-btn px-1 py-0.5 bg-red-600 text-white rounded text-[10px] hover:bg-red-700 transition-colors duration-200"
                            data-product-id="{{ $product->id }}"
                            data-product-name="{{ $product->name }}"
                            data-trade-price="{{ $product->trade_price }}"
                            data-type="damaged">
                            Damaged
                        </button>
                        @endif
                    </div>
                </div>
            </div>
            @endif
            @endforeach
        </div>
    </div>
</div>
<style>
    /* Add this to your existing styles */
    .toggle-checkbox:checked {
        right: 0;
        border-color: #3B82F6;
    }

    .toggle-checkbox:checked+.toggle-label {
        background-color: #3B82F6;
    }

    .toggle-label {
        transition: background-color 0.3s ease;
    }
</style>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.x.x/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const productGrid = document.getElementById('productGrid');
        const isPurchaseForm = document.getElementById('purchaseForm') !== null;

        // Hide damage buttons if it's a purchase form
        if (isPurchaseForm) {
            document.querySelectorAll('[data-type="damaged"]').forEach(button => {
                button.style.display = 'none';
            });

            document.querySelectorAll('.batch-select').forEach(select => {
                select.style.display = 'none';
            });
        }

        // Only attach this event listener once
        if (productGrid && !productGrid.hasEventListener) {
            productGrid.hasEventListener = true; // Mark that we've attached an event

            productGrid.addEventListener('click', function(e) {
                if (e.target.classList.contains('add-product-btn')) {
                    const productCard = e.target.closest('.product-card');
                    const batchSelect = productCard.querySelector('.batch-select');
                    const quantityInput = productCard.querySelector('.quantity-input');

                    // Validate quantity for all transactions
                    if (!quantityInput.value || quantityInput.value <= 0) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Invalid Quantity',
                            text: 'Please enter a valid quantity'
                        });
                        return;
                    }

                    // Batch validation only for sales
                    if (!isPurchaseForm) {
                        if (!batchSelect.value) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Batch Required',
                                text: 'Please select a batch'
                            });
                            return;
                        }

                        const selectedBatch = batchSelect.options[batchSelect.selectedIndex];
                        const remainingQuantity = parseInt(selectedBatch.dataset.remaining);
                        const requestedQuantity = parseInt(quantityInput.value);

                        if (requestedQuantity > remainingQuantity) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Insufficient Stock',
                                text: `Only ${remainingQuantity} items available in this batch`
                            });
                            return;
                        }
                    }

                    // Prepare product data
                    const productData = {
                        productId: e.target.dataset.productId,
                        productName: e.target.dataset.productName,
                        quantity: parseInt(quantityInput.value),
                        price: isPurchaseForm ? e.target.dataset.tradePrice : batchSelect.options[batchSelect.selectedIndex].dataset.price,
                        tradePrice: isPurchaseForm ? e.target.dataset.tradePrice : batchSelect.options[batchSelect.selectedIndex].dataset.price,
                        type: e.target.dataset.type // Add type to identify damaged products
                    };

                    // Add batch data only for sales
                    if (!isPurchaseForm) {
                        productData.batchId = batchSelect.value;
                        productData.batchNumber = batchSelect.options[batchSelect.selectedIndex].dataset.batchNumber;
                    }

                    console.log('Adding product with data:', productData);

                    // Call the appropriate function based on form type and product type
                    if (isPurchaseForm) {
                        addPurchaseProductToTransaction(productData);
                    } else {
                        if (productData.type === 'damaged') {
                            addDamagedProductToTransaction(productData);
                        } else {
                            addProductToTransaction(productData);
                        }
                    }

                    // Clear the quantity input after adding to prevent accidental double-clicks
                    quantityInput.value = '';
                }
            });
        }
    });


    document.querySelectorAll('.batch-select').forEach(select => {
        select.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const productCard = this.closest('.product-card');
            const tradePriceDisplay = productCard.querySelector('.trade-price-display');
            const addProductBtn = productCard.querySelector('.add-product-btn');

            if (selectedOption.value) {
                const batchPrice = parseFloat(selectedOption.dataset.price).toFixed(2);
                tradePriceDisplay.textContent = '৳' + batchPrice;
                addProductBtn.dataset.tradePrice = selectedOption.dataset.price;
            }
        });
    });


    // Add this JavaScript after the existing script
    document.addEventListener('DOMContentLoaded', function() {
        const productGrid = document.getElementById('productGrid');
        const categoryFilter = document.getElementById('categoryFilter');
        const productSearch = document.getElementById('productSearch');

        function filterProducts() {
            const searchTerm = productSearch.value.toLowerCase();
            const selectedCategory = categoryFilter.value;
            const productCards = document.querySelectorAll('.product-card');

            productCards.forEach(card => {
                // Use the correct selectors that match the HTML structure
                const productName = card.querySelector('.product-name').textContent.toLowerCase();
                const categoryId = card.getAttribute('data-category-id');

                // Check if product name contains search term
                const matchesSearch = productName.includes(searchTerm);

                // Check if product category matches selected category or if no category is selected
                const matchesCategory = !selectedCategory || categoryId === selectedCategory;

                // Show or hide the card based on both conditions
                card.style.display = (matchesSearch && matchesCategory) ? '' : 'none';
            });
        }

        // Add event listeners for search and category filter
        if (categoryFilter) {
            categoryFilter.addEventListener('change', filterProducts);
        }

        if (productSearch) {
            productSearch.addEventListener('input', filterProducts);
        }
    });


    document.addEventListener('DOMContentLoaded', function() {
        const productGrid = document.getElementById('productGrid');
        const categoryFilter = document.getElementById('categoryFilter');
        const productSearch = document.getElementById('productSearch');
        const availabilityToggle = document.getElementById('availabilityToggle');
        const isPurchaseForm = document.getElementById('purchaseForm') !== null;

        // Set the toggle to checked (In Stock) by default
        if (availabilityToggle) {
            availabilityToggle.checked = true;
        }

        // Hide damage buttons if it's a purchase form
        if (isPurchaseForm) {
            document.querySelectorAll('[data-type="damaged"]').forEach(button => {
                button.style.display = 'none';
            });

            document.querySelectorAll('.batch-select').forEach(select => {
                select.style.display = 'none';
            });
        }

        function filterProducts() {
            const searchTerm = productSearch.value.toLowerCase();
            const selectedCategory = categoryFilter.value;
            const showOnlyAvailable = availabilityToggle.checked;
            const productCards = document.querySelectorAll('.product-card');

            productCards.forEach(card => {
                const productName = card.querySelector('.product-name').textContent.toLowerCase();
                const categoryId = card.getAttribute('data-category-id');

                // Check if it's a common product by looking for the "Common Product" badge
                const isCommonProduct = card.querySelector('.bg-gray-600') !== null;

                // Handle both in-stock and common products
                let stockCount = 0;
                const stockElement = card.querySelector('.bg-blue-600');
                if (stockElement && !isCommonProduct) {
                    const stockText = stockElement.textContent;
                    stockCount = parseInt(stockText.replace('Stock:', '').trim()) || 0;
                }

                // Check if product name contains search term
                const matchesSearch = productName.includes(searchTerm);

                // Check if product category matches selected category or if no category is selected
                const matchesCategory = !selectedCategory || categoryId === selectedCategory;

                // Check availability based on toggle state
                let matchesAvailability;
                if (showOnlyAvailable) {
                    // Show only products with stock > 0 (hide common products)
                    matchesAvailability = !isCommonProduct && stockCount > 0;
                } else {
                    // Show all products (both in-stock and common products)
                    matchesAvailability = true;
                }

                // Show or hide the card based on all conditions
                card.style.display = (matchesSearch && matchesCategory && matchesAvailability) ? '' : 'none';
            });
        }

        // Apply initial filtering
        filterProducts();

        // Add event listeners
        if (availabilityToggle) {
            availabilityToggle.addEventListener('change', filterProducts);
        }

        if (categoryFilter) {
            categoryFilter.addEventListener('change', filterProducts);
        }

        if (productSearch) {
            productSearch.addEventListener('input', filterProducts);
        }

        // Product click handler
        if (productGrid && !productGrid.hasEventListener) {
            productGrid.hasEventListener = true;

            productGrid.addEventListener('click', function(e) {
                if (e.target.classList.contains('add-product-btn')) {
                    const productCard = e.target.closest('.product-card');
                    const batchSelect = productCard.querySelector('.batch-select');
                    const quantityInput = productCard.querySelector('.quantity-input');
                    const isCommonProduct = e.target.dataset.type === 'common';

                    // Validate quantity for all transactions
                    if (!quantityInput.value || quantityInput.value <= 0) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Invalid Quantity',
                            text: 'Please enter a valid quantity'
                        });
                        return;
                    }

                    // Batch validation only for sales and non-common products
                    if (!isPurchaseForm && !isCommonProduct && batchSelect) {
                        if (!batchSelect.value) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Batch Required',
                                text: 'Please select a batch'
                            });
                            return;
                        }

                        const selectedBatch = batchSelect.options[batchSelect.selectedIndex];
                        const remainingQuantity = parseInt(selectedBatch.dataset.remaining);
                        const requestedQuantity = parseInt(quantityInput.value);

                        if (requestedQuantity > remainingQuantity) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Insufficient Stock',
                                text: `Only ${remainingQuantity} items available in this batch`
                            });
                            return;
                        }
                    }

                    // Prepare product data
                    const productData = {
                        productId: e.target.dataset.productId,
                        productName: e.target.dataset.productName,
                        quantity: parseInt(quantityInput.value),
                        price: (isPurchaseForm || isCommonProduct) ? e.target.dataset.tradePrice : (batchSelect ? batchSelect.options[batchSelect.selectedIndex].dataset.price : e.target.dataset.tradePrice),
                        tradePrice: (isPurchaseForm || isCommonProduct) ? e.target.dataset.tradePrice : (batchSelect ? batchSelect.options[batchSelect.selectedIndex].dataset.price : e.target.dataset.tradePrice),
                        type: e.target.dataset.type
                    };

                    // Add batch data only for sales and non-common products
                    if (!isPurchaseForm && !isCommonProduct && batchSelect) {
                        productData.batchId = batchSelect.value;
                        productData.batchNumber = batchSelect.options[batchSelect.selectedIndex].dataset.batchNumber;
                    }

                    console.log('Adding product with data:', productData);

                    // Call the appropriate function based on form type and product type
                    if (isPurchaseForm) {
                        addPurchaseProductToTransaction(productData);
                    } else {
                        if (productData.type === 'damaged') {
                            addDamagedProductToTransaction(productData);
                        } else if (productData.type === 'common') {
                            // You'll need to create this function to handle common products
                            addCommonProductToTransaction(productData);
                        } else {
                            addProductToTransaction(productData);
                        }
                    }

                    // Clear the quantity input after adding
                    quantityInput.value = '';
                }
            });
        }
    });
</script>