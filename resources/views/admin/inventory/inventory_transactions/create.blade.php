@extends('admin.layouts.app')

@section('content')
<div class="container-fluid" data-disable-underprice="{{ $disableUnderprice ? 'true' : 'false' }}">
    @if(auth()->user()->hasRole('admin'))
    <div class="bg-blue-600 shadow-sm">
        <div class="flex justify-center items-center p-2">
            <div class="bg-blue-500 rounded-full flex items-center">
                <a href="{{ route('admin.inventory.inventory_transactions.create', ['type' => 'sale']) }}"
                    class="px-4 py-1.5 text-sm md:text-base md:px-6 md:py-2 rounded-full font-medium transition-all duration-300 ease-in-out {{ $transactionType !== 'purchase' ? 'bg-white text-blue-600' : 'text-white hover:bg-blue-400' }}">
                    Sales
                </a>

                <a href="{{ route('admin.inventory.inventory_transactions.create', ['type' => 'purchase']) }}"
                    class="px-4 py-1.5 text-sm md:text-base md:px-6 md:py-2 rounded-full font-medium transition-all duration-300 ease-in-out {{ $transactionType === 'purchase' ? 'bg-white text-blue-600' : 'text-white hover:bg-blue-400' }}">
                    Purchase
                </a>
            </div>
        </div>
    </div>
    @else
    <div class="bg-blue-600 shadow-sm">
        <div class="flex justify-center items-center p-2">
            <div class="bg-blue-500 rounded-full">
                <div class="px-4 py-1.5 text-sm md:text-base md:px-6 md:py-2 rounded-full font-medium bg-white text-blue-600">
                    Sales
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="bg-white">
        <!-- Mobile Toggle Button (visible only on mobile) -->
        <button id="toggleFormBtn" class="md:hidden fixed right-0 top-1/2 transform -translate-y-1/2 bg-blue-600 text-white p-2 rounded-l-lg shadow-lg z-50">
            <svg class="w-6 h-6 toggle-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </button>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3">
            <!-- Product List Section -->
            <div class="lg:col-span-2 border-r">
                @include('admin.inventory.inventory_transactions.partials.product-modal')
            </div>

            <!-- Transaction Form Section -->
            <div id="transactionForm" class="lg:col-span-1 transform translate-x-full md:translate-x-0 fixed md:relative top-0 right-0 h-full w-full md:w-auto bg-white transition-transform duration-300 ease-in-out z-40 overflow-y-auto">
                @if(auth()->user()->hasRole('admin') && $transactionType === 'purchase')
                @include('admin.inventory.inventory_transactions.partials.purchase-form')
                @else
                @include('admin.inventory.inventory_transactions.partials.sale-form')
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@endpush

<script>
    // Production Grade Session Management System
    class TransactionSessionManager {
        constructor() {
            this.sessionId = this.generateSessionId();
            this.state = new Map();
            this.initializeSession();
        }

        generateSessionId() {
            return `txn_${Date.now()}_${Math.random().toString(36).substr(2, 9)}_${window.performance.now()}`;
        }

        initializeSession() {
            sessionStorage.setItem('transactionSessionId', this.sessionId);
            this.setState('isProcessing', false);
            this.setState('selectedCustomerData', null);
            this.setState('isCustomerLocked', false);
            this.setState('formType', this.detectFormType());
        }

        detectFormType() {
            if (document.getElementById('purchaseForm')) return 'purchase';
            if (document.getElementById('saleForm')) return 'sales';
            return 'unknown';
        }

        setState(key, value) {
            const sessionKey = `${this.sessionId}_${key}`;
            this.state.set(sessionKey, value);
            sessionStorage.setItem(sessionKey, JSON.stringify(value));
        }

        getState(key) {
            const sessionKey = `${this.sessionId}_${key}`;
            let value = this.state.get(sessionKey);
            if (value === undefined) {
                const stored = sessionStorage.getItem(sessionKey);
                if (stored) {
                    value = JSON.parse(stored);
                    this.state.set(sessionKey, value);
                }
            }
            return value;
        }

        clearSession() {
            for (let key of this.state.keys()) {
                sessionStorage.removeItem(key);
            }
            this.state.clear();
        }
    }

    class FormContextManager {
        constructor(sessionManager) {
            this.session = sessionManager;
            this.formType = this.session.getState('formType');
            this.formPrefix = this.formType === 'purchase' ? 'purchase' : 'sales';
            this.initializeFormContext();
        }

        getFormElements() {
            return {
                ledgerSelect: document.querySelector('select[name="ledger_id"]'),
                dateInput: document.querySelector('input[name="transaction_date"]'),
                paymentMethod: document.querySelector('select[name="payment_method"]'),
                subtotalInput: document.getElementById('subtotalInput'),
                grandTotalInput: document.getElementById('grandTotalInput'),
                submitButton: document.getElementById('submitTransaction'),
                appendButton: document.getElementById('appendTransaction')
            };
        }

        initializeFormContext() {
            const containers = document.querySelectorAll('.container-fluid');
            containers.forEach(container => {
                container.setAttribute('data-session-id', this.session.sessionId);
                container.setAttribute('data-form-type', this.formType);
            });
        }
    }

    class CustomerSelectionManager {
        constructor(sessionManager, formContext) {
            this.session = sessionManager;
            this.formContext = formContext;
            this.processingQueue = new Map();
        }

        async acquireProcessingLock(operationId) {
            const lockKey = `processing_lock_${this.session.sessionId}`;
            const existingLock = sessionStorage.getItem(lockKey);

            if (existingLock) {
                const lockData = JSON.parse(existingLock);
                const lockAge = Date.now() - lockData.timestamp;

                if (lockAge > 30000) {
                    sessionStorage.removeItem(lockKey);
                } else {
                    return false;
                }
            }

            sessionStorage.setItem(lockKey, JSON.stringify({
                operationId,
                timestamp: Date.now()
            }));

            return true;
        }

        releaseProcessingLock(operationId) {
            const lockKey = `processing_lock_${this.session.sessionId}`;
            sessionStorage.removeItem(lockKey);
        }

        generateOperationId() {
            return `op_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
        }

        verifyOperationIntegrity(operationId) {
            const currentOperationId = this.session.getState('currentOperationId');
            return currentOperationId === operationId;
        }
    }

    class APIManager {
        constructor(sessionManager) {
            this.session = sessionManager;
            this.activeRequests = new Map();
        }

        async makeRequest(url, options = {}) {
            const requestId = this.generateRequestId();

            const enhancedOptions = {
                ...options,
                headers: {
                    ...options.headers,
                    'X-Session-ID': this.session.sessionId,
                    'X-Request-ID': requestId,
                    'X-Form-Type': this.session.getState('formType'),
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            };

            this.activeRequests.set(requestId, {
                url,
                timestamp: Date.now(),
                sessionId: this.session.sessionId
            });

            try {
                const response = await fetch(url, enhancedOptions);
                return response;
            } finally {
                this.activeRequests.delete(requestId);
            }
        }

        generateRequestId() {
            return `req_${this.session.sessionId}_${Date.now()}_${Math.random().toString(36).substr(2, 6)}`;
        }
    }

    // Initialize session managers
    let sessionManager;
    let formContextManager;
    let customerSelectionManager;
    let apiManager;

    // Production Grade Calculation System
    class TransactionCalculator {
        constructor() {
            this.isCalculating = false;
            this.calculationQueue = [];
            this.observer = null;
            this.debounceTimer = null;
            this.init();
        }

        init() {
            this.setupDOMObserver();
            this.setupErrorHandling();
        }

        scheduleCalculation() {
            if (this.debounceTimer) {
                clearTimeout(this.debounceTimer);
            }

            this.debounceTimer = setTimeout(() => {
                this.performCalculation();
            }, 150);
        }

        async performCalculation() {
            if (this.isCalculating) {
                return;
            }

            this.isCalculating = true;

            try {
                await this.calculateTotals();
            } catch (error) {
                console.error('Calculation error:', error);
                this.handleCalculationError(error);
            } finally {
                this.isCalculating = false;
            }
        }

        async calculateTotals() {
            return new Promise((resolve) => {
                requestAnimationFrame(() => {
                    try {
                        let subtotal = 0;
                        let damageTotal = 0;

                        const regularLines = document.querySelectorAll('.line-item:not(.bg-red-50\\/30)');
                        regularLines.forEach((line, index) => {
                            const result = this.calculateLineTotal(line, index, 'regular');
                            if (result.success) {
                                subtotal += result.total;
                            }
                        });

                        const damageLines = document.querySelectorAll('.line-item.bg-red-50\\/30');
                        damageLines.forEach((line, index) => {
                            const result = this.calculateLineTotal(line, index, 'damage');
                            if (result.success) {
                                damageTotal += result.total;
                            }
                        });

                        this.updateTotalDisplays(subtotal, damageTotal);

                        resolve({
                            subtotal,
                            damageTotal
                        });
                    } catch (error) {
                        console.error('Error in calculateTotals:', error);
                        resolve({
                            subtotal: 0,
                            damageTotal: 0
                        });
                    }
                });
            });
        }

        calculateLineTotal(line, index, type) {
            try {
                const quantityElement = line.querySelector('.quantity');
                const priceElement = line.querySelector('.unit-price') || line.querySelector('.dealer-price');
                const lineTotalElement = line.querySelector('.line-total');

                if (!quantityElement || !priceElement || !lineTotalElement) {
                    console.warn(`Missing elements in ${type} line ${index + 1}`);
                    return {
                        success: false,
                        total: 0
                    };
                }

                const quantity = parseFloat(quantityElement.value) || 0;
                const price = parseFloat(priceElement.value) || 0;

                if (quantity < 0 || price < 0) {
                    console.warn(`Invalid values in ${type} line ${index + 1}:`, {
                        quantity,
                        price
                    });
                    return {
                        success: false,
                        total: 0
                    };
                }

                const lineTotal = quantity * price;

                lineTotalElement.textContent = lineTotal.toFixed(2);
                lineTotalElement.setAttribute('data-calculated-value', lineTotal.toFixed(2));

                return {
                    success: true,
                    total: lineTotal
                };
            } catch (error) {
                console.error(`Error calculating ${type} line ${index + 1}:`, error);
                return {
                    success: false,
                    total: 0
                };
            }
        }

        updateTotalDisplays(subtotal, damageTotal) {
            try {
                const subtotalElement = document.getElementById('subtotal');
                const subtotalInputElement = document.getElementById('subtotalInput');

                if (subtotalElement && subtotalInputElement) {
                    subtotalElement.textContent = '৳' + subtotal.toFixed(2);
                    subtotalInputElement.value = subtotal.toFixed(2);
                }

                const grandTotalElement = document.getElementById('grandTotal');
                const grandTotalInputElement = document.getElementById('grandTotalInput');

                if (grandTotalElement && grandTotalInputElement) {
                    grandTotalElement.textContent = '৳' + subtotal.toFixed(2);
                    grandTotalInputElement.value = subtotal.toFixed(2);
                }

                const damageTotalElement = document.getElementById('damageTotal');
                const damageTotalInputElement = document.getElementById('damageTotalInput');

                if (damageTotalElement && damageTotalInputElement) {
                    damageTotalElement.textContent = '৳' + damageTotal.toFixed(2);
                    damageTotalInputElement.value = damageTotal.toFixed(2);
                }

                document.dispatchEvent(new CustomEvent('totalsUpdated', {
                    detail: {
                        subtotal,
                        damageTotal,
                        grandTotal: subtotal
                    }
                }));

            } catch (error) {
                console.error('Error updating displays:', error);
            }
        }

        setupDOMObserver() {
            const productContainer = document.getElementById('productLinesContainer');

            if (!productContainer) {
                console.warn('Product container not found, observer not setup');
                return;
            }

            this.observer = new MutationObserver((mutations) => {
                let shouldRecalculate = false;

                mutations.forEach((mutation) => {
                    if (mutation.type === 'childList') {
                        shouldRecalculate = true;
                    } else if (mutation.type === 'attributes') {
                        if (mutation.target.classList.contains('quantity') ||
                            mutation.target.classList.contains('unit-price') ||
                            mutation.target.classList.contains('dealer-price')) {
                            shouldRecalculate = true;
                        }
                    }
                });

                if (shouldRecalculate) {
                    this.scheduleCalculation();
                }
            });

            this.observer.observe(productContainer, {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: ['value']
            });

            productContainer.addEventListener('input', (e) => {
                if (e.target.classList.contains('quantity') ||
                    e.target.classList.contains('unit-price') ||
                    e.target.classList.contains('dealer-price')) {
                    this.scheduleCalculation();
                }
            });
        }

        setupErrorHandling() {
            window.addEventListener('error', (e) => {
                if (e.message.includes('calculation') || e.message.includes('total')) {
                    this.handleCalculationError(e.error);
                }
            });
        }

        handleCalculationError(error) {
            console.error('Transaction calculation error:', error);

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Calculation Error',
                    text: 'There was an issue calculating totals. Please refresh the page if the problem persists.',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000
                });
            }
        }

        recalculate() {
            this.scheduleCalculation();
        }

        destroy() {
            if (this.observer) {
                this.observer.disconnect();
            }
            if (this.debounceTimer) {
                clearTimeout(this.debounceTimer);
            }
        }
    }

    let transactionCalculator;

    document.addEventListener('DOMContentLoaded', function() {
        // Initialize session management first
        sessionManager = new TransactionSessionManager();
        formContextManager = new FormContextManager(sessionManager);
        customerSelectionManager = new CustomerSelectionManager(sessionManager, formContextManager);
        apiManager = new APIManager(sessionManager);

        // Initialize the production-grade calculator
        transactionCalculator = new TransactionCalculator();
        window.transactionCalculator = transactionCalculator;

        const isPurchaseForm = document.getElementById('purchaseForm') !== null;
        const isSaleForm = document.getElementById('saleForm') !== null;
        const submitButton = document.querySelector('#submitTransaction');
        const containerEl = document.querySelector('.container-fluid');
        const disableUnderpriceAttr = containerEl ? containerEl.getAttribute('data-disable-underprice') : null;
        const disableUnderprice = disableUnderpriceAttr === 'true';

        console.log('Submit button found:', submitButton);
        console.log('Form type detection:', {
            isPurchaseForm,
            isSaleForm,
            rawAttribute: disableUnderpriceAttr,
            parsedValue: disableUnderprice,
            containerElement: containerEl
        });

        if (submitButton) {
            submitButton.addEventListener('click', function(e) {
                e.preventDefault();
                this.disabled = true;
                this.classList.remove('from-blue-500', 'to-blue-600', 'hover:from-blue-600', 'hover:to-blue-700');
                this.classList.add('from-red-500', 'to-red-600');

                if (isPurchaseForm) {
                    console.log('Submitting purchase form');
                    submitPurchaseTransaction();
                } else if (isSaleForm) {
                    console.log('Submitting sales form');
                    submitSalesTransaction();
                }
            });
        } else {
            console.error('Submit button not found in DOM');
        }

        if (!isPurchaseForm) {
            console.log('Sales form detected - ensuring damage buttons are visible');
            document.querySelectorAll('.damaged-btn').forEach(button => {
                button.style.display = 'block';
            });
        }
    });

    const TransactionManager = {
        removeLine: function(lineItem) {
            lineItem.remove();
            updateTotalPriceDifference();
        }
    };

    function isDuplicateProduct(productId, type = 'regular') {
        const selector = type === 'damaged' ?
            '#productLinesContainer .line-item.bg-red-50\\/30' :
            '#productLinesContainer .line-item:not(.bg-red-50\\/30)';

        const productLines = document.querySelectorAll(selector);

        for (let i = 0; i < productLines.length; i++) {
            const line = productLines[i];
            const lineProductId = line.dataset.productId;

            if (lineProductId === productId) {
                console.log(`Found duplicate ${type} product:`, productId, 'in line:', line);
                return true;
            }
        }

        return false;
    }

    function validateQuantityInput(quantityInput) {
        const lineItem = quantityInput.closest('.line-item');
        const productSelector = lineItem.querySelector('.product-selector');
        const selectedOption = productSelector.options[productSelector.selectedIndex];

        if (!selectedOption || !selectedOption.value) {
            return true;
        }

        const availableStock = parseFloat(selectedOption.getAttribute('data-stock')) || 0;
        const enteredQuantity = parseFloat(quantityInput.value) || 0;

        if (enteredQuantity > availableStock) {
            Swal.fire({
                icon: 'warning',
                title: 'Insufficient Stock',
                text: `Available stock: ${availableStock} units. You cannot enter more than the available quantity.`,
                confirmButtonColor: '#3085d6'
            });

            quantityInput.value = availableStock;

            if (window.transactionCalculator) {
                window.transactionCalculator.recalculate();
            }

            return false;
        }

        return true;
    }

    function addProductToTransaction(productData) {
        console.log('Adding product to transaction:', productData);
        console.log('Current DOM state before adding:');
        console.log('- Product line container:', document.querySelector('#productLinesContainer'));
        console.log('- Existing line items:', document.querySelectorAll('#productLinesContainer .line-item'));

        if (isDuplicateProduct(productData.productId)) {
            Swal.fire({
                icon: 'error',
                title: 'Duplicate Product',
                text: 'This product is already added to the transaction. Please modify the existing line instead of adding it again.'
            });
            return;
        }

        const template = document.getElementById('productLineTemplate');
        const newLine = template.content.cloneNode(true).querySelector('.line-item');
        console.log('Created new line element:', newLine);

        console.log('Setting product ID:', productData.productId);
        console.log('Setting trade price:', productData.tradePrice);

        newLine.dataset.productId = productData.productId;
        newLine.dataset.tradePrice = productData.tradePrice;

        const productSelector = newLine.querySelector('.product-selector');
        $(productSelector).val(productData.productId).trigger('change');

        const quantityInput = newLine.querySelector('.quantity');

        const selectedOption = productSelector.options[productSelector.selectedIndex];
        const availableStock = parseFloat(selectedOption.getAttribute('data-stock')) || 0;

        if (parseFloat(productData.quantity) > availableStock) {
            Swal.fire({
                icon: 'warning',
                title: 'Quantity Adjusted',
                text: `Requested quantity (${productData.quantity}) exceeds available stock (${availableStock}). Quantity has been adjusted.`,
                confirmButtonColor: '#3085d6'
            });
            quantityInput.value = availableStock;
        } else {
            quantityInput.value = productData.quantity;
        }

        quantityInput.addEventListener('input', function() {
            validateQuantityInput(this);
            calculatePriceDifference(this, false);
        });

        quantityInput.addEventListener('change', function() {
            validateQuantityInput(this);
            calculatePriceDifference(this, false);
        });

        quantityInput.addEventListener('blur', function() {
            validateQuantityInput(this);
        });

        const containerEl = document.querySelector('.container-fluid');
        const disableUnderprice = containerEl ? containerEl.dataset.disableUnderprice === 'true' : false;

        const unitPriceInput = newLine.querySelector('.unit-price');
        if (disableUnderprice && parseFloat(productData.price) < parseFloat(productData.tradePrice)) {
            unitPriceInput.value = productData.tradePrice;
        } else {
            unitPriceInput.value = productData.price;
        }

        unitPriceInput.addEventListener('input', () => calculatePriceDifference(unitPriceInput, true));
        unitPriceInput.addEventListener('change', () => calculatePriceDifference(unitPriceInput, true));

        const tradePriceInput = newLine.querySelector('.trade-price');
        tradePriceInput.value = productData.tradePrice;
        const tradePriceDisplay = newLine.querySelector('.trade-price-display');
        if (tradePriceDisplay) {
            tradePriceDisplay.textContent = productData.tradePrice;
        }

        calculatePriceDifference(unitPriceInput, false);

        document.querySelector('#productLinesContainer').appendChild(newLine);
        $(newLine).find('.select2').select2();

        console.log('Current line items:', document.querySelectorAll('#productLinesContainer .line-item'));
    }

    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.getElementById('toggleFormBtn');
        const formSection = document.getElementById('transactionForm');
        const toggleIcon = document.querySelector('.toggle-icon');

        if (toggleBtn && formSection && toggleIcon) {
            toggleBtn.addEventListener('click', function() {
                formSection.classList.toggle('translate-x-full');
                toggleIcon.classList.toggle('rotate-180');
            });
        }
    });

    function addDamagedProductToTransaction(productData) {
        console.log('Adding damaged product to transaction:', productData);

        if (isDuplicateProduct(productData.productId, 'damaged')) {
            Swal.fire({
                icon: 'error',
                title: 'Duplicate Damaged Product',
                text: 'This product is already added to the damaged products list. Please modify the existing line instead of adding it again.'
            });
            return;
        }

        const template = document.getElementById('damagedProductLineTemplate');
        const newLine = template.content.cloneNode(true).querySelector('.line-item');

        newLine.dataset.productId = productData.productId;

        const productSelector = newLine.querySelector('.product-selector');
        $(productSelector).val(productData.productId).trigger('change');

        const quantityInput = newLine.querySelector('.quantity');
        quantityInput.value = productData.quantity;

        const unitPriceInput = newLine.querySelector('.unit-price');
        unitPriceInput.value = productData.price;

        document.querySelector('#productLinesContainer').appendChild(newLine);
        $(newLine).find('.select2').select2();

        console.log('Damaged product added:', productData.productId);
        console.log('Current damaged lines:', document.querySelectorAll('#productLinesContainer .line-item.bg-red-50\\/30'));
    }

    function calculateProfit(input) {
        const lineItem = input.closest('.line-item');
        const dp = parseFloat(lineItem.querySelector('.dealer-price').value) || 0;
        const tp = parseFloat(lineItem.querySelector('.trade-price').value) || 0;

        if (dp > 0 && tp > 0) {
            const profitMargin = Math.round((tp - dp) / dp * 100);
            const profitElement = lineItem.querySelector('.profit-margin');
            if (profitElement) {
                profitElement.textContent = profitMargin + '%';
            }
        }
    }

    function addPurchaseProductToTransaction(productData) {
        let actualProductId = productData.productId;
        if (typeof actualProductId === 'string' && actualProductId.startsWith('common_')) {
            actualProductId = actualProductId.replace('common_', '');
        }

        if (isDuplicateProduct(productData.productId)) {
            Swal.fire({
                icon: 'error',
                title: 'Duplicate Product',
                text: 'This product is already added to the transaction. Please modify the existing line instead of adding it again.'
            });
            return;
        }

        const template = document.getElementById('purchaseLineTemplate');
        const newLine = template.content.cloneNode(true).querySelector('.line-item');

        newLine.dataset.productId = productData.productId;
        newLine.dataset.tradePrice = productData.tradePrice;

        const productSelector = newLine.querySelector('.product-selector');

        if (typeof productData.productId === 'string' && productData.productId.startsWith('common_')) {
            const option = document.createElement('option');
            option.value = actualProductId;
            option.textContent = productData.productName;
            option.selected = true;
            productSelector.appendChild(option);
        } else {
            $(productSelector).val(actualProductId).trigger('change');
        }

        const quantityInput = newLine.querySelector('.quantity');
        quantityInput.value = productData.quantity;

        const dealerPriceInput = newLine.querySelector('.dealer-price');
        dealerPriceInput.value = productData.price || productData.tradePrice;

        const tradePriceInput = newLine.querySelector('.trade-price');
        tradePriceInput.value = productData.tradePrice;

        calculateProfit(dealerPriceInput);

        document.querySelector('#productLinesContainer').appendChild(newLine);
        $(newLine).find('.select2').select2();
    }

    function calculatePriceDifference(input, showWarning = true) {
        const lineItem = input.closest('.line-item');
        const quantity = parseFloat(lineItem.querySelector('.quantity').value) || 0;
        const unitPrice = parseFloat(lineItem.querySelector('.unit-price').value) || 0;
        const tradePrice = parseFloat(lineItem.dataset.tradePrice) || 0;

        const containerEl = document.querySelector('.container-fluid');
        const disableUnderprice = containerEl ? containerEl.dataset.disableUnderprice === 'true' : false;

        console.log('Price check:', {
            unitPrice,
            tradePrice,
            disableUnderprice,
            showWarning
        });

        if (disableUnderprice && unitPrice < tradePrice) {
            if (showWarning) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Price Restriction',
                    text: 'You can only do overprice, underprice is disabled for you.',
                    confirmButtonColor: '#3085d6'
                });
            }

            lineItem.querySelector('.unit-price').value = tradePrice;

            const difference = 0;
            const priceDiffElement = lineItem.querySelector('.price-difference');
            if (priceDiffElement) {
                priceDiffElement.textContent = Math.abs(difference).toFixed(2);
                priceDiffElement.className = 'price-difference font-medium text-sm text-gray-600';
            }
        } else {
            const difference = (unitPrice - tradePrice) * quantity;
            const priceDiffElement = lineItem.querySelector('.price-difference');
            if (priceDiffElement) {
                priceDiffElement.textContent = Math.abs(difference).toFixed(2);
                priceDiffElement.className = 'price-difference font-medium text-sm ' +
                    (difference < 0 ? 'text-red-600' : difference > 0 ? 'text-green-600' : 'text-gray-600');
            }
        }

        updateTotalPriceDifference();
    }

    function updateTotalPriceDifference() {
        let totalDifference = 0;
        const lineItems = document.querySelectorAll('.line-item');

        lineItems.forEach(line => {
            const quantity = parseFloat(line.querySelector('.quantity')?.value) || 0;
            const unitPrice = parseFloat(line.querySelector('.unit-price')?.value) || 0;
            const tradePriceElement = line.querySelector('.trade-price-display') || line.querySelector('.trade-price');
            const tradePrice = parseFloat(tradePriceElement?.textContent || tradePriceElement?.value) || 0;

            if (quantity && unitPrice && tradePrice) {
                totalDifference += (unitPrice - tradePrice) * quantity;
            }
        });

        const discountInput = document.getElementById('discount');
        if (discountInput && totalDifference < 0) {
            discountInput.value = Math.abs(totalDifference).toFixed(2);
        }
    }

    function submitPurchaseTransaction() {
        console.log('Step 1: Starting purchase transaction submission');

        const paymentMethodElement = document.querySelector('select[name="payment_method"]');
        console.log('Payment Method Element:', paymentMethodElement);
        console.log('Payment Method Value:', paymentMethodElement?.value);

        console.log('Step 2: Collecting form elements');
        const formElements = {
            dateInput: document.querySelector('input[name="transaction_date"]'),
            ledgerSelect: document.querySelector('select[name="ledger_id"]'),
            paymentMethod: document.querySelector('select[name="payment_method"]'),
            subtotalInput: document.getElementById('subtotalInput'),
            roundOffInput: document.getElementById('roundOffInput'),
            discountInput: document.getElementById('discountInput'),
            grandTotalInput: document.getElementById('grandTotalInput')
        };

        console.log('Step 3: Form elements collected:', formElements);

        const requiredElements = ['dateInput', 'ledgerSelect', 'paymentMethod', 'subtotalInput'];
        const missingElements = requiredElements.filter(elem => !formElements[elem]?.value);

        if (missingElements.length) {
            console.error('Missing elements:', missingElements);
            Swal.fire({
                icon: 'error',
                title: 'Form Error',
                text: `Missing required elements: ${missingElements.join(', ')}`
            });
            return;
        }

        const lines = getPurchaseLines();
        console.log('Purchase lines collected:', lines);

        if (!lines.length) {
            console.warn('No purchase lines found');
            Swal.fire({
                icon: 'warning',
                title: 'No Products Added',
                text: 'Please add at least one product to the purchase'
            });
            return;
        }

        const transactionData = {
            entry_type: 'purchase',
            transaction_date: formElements.dateInput.value,
            ledger_id: formElements.ledgerSelect.value,
            payment_method: formElements.paymentMethod.value,
            subtotal: parseFloat(formElements.subtotalInput.value),
            round_off: parseFloat(formElements.roundOffInput?.value || 0),
            discount: parseFloat(formElements.discountInput?.value || 0),
            grand_total: parseFloat(formElements.grandTotalInput?.value || formElements.subtotalInput.value),
            lines: lines
        };

        console.log('Transaction data built:', transactionData);

        // Use session-aware API manager
        apiManager.makeRequest('{{ route("admin.inventory.inventory_transactions.store") }}', {
                method: 'POST',
                body: JSON.stringify(transactionData)
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response ok:', response.ok);

                if (!response.ok) {
                    return response.json().then(errorData => {
                        throw new Error(errorData.message || `HTTP ${response.status}: ${response.statusText}`);
                    });
                }

                return response.json();
            })
            .then(data => {
                console.log('Server response:', data);

                if (!data.success) {
                    throw new Error(data.message || 'Purchase transaction failed');
                }

                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: data.message || 'Purchase transaction saved successfully',
                    showConfirmButton: true,
                    confirmButtonText: 'View Transactions'
                }).then(() => {
                    window.location.href = '{{ route("admin.inventory.inventory_transactions.index") }}';
                });
            })
            .catch(error => {
                console.error('Purchase transaction error:', error);

                Swal.fire({
                    icon: 'error',
                    title: 'Purchase Transaction Failed',
                    text: error.message || 'Failed to save purchase transaction',
                    confirmButtonText: 'OK'
                });
            });
    }

    function getPurchaseLines() {
        const lines = [];
        const lineItems = document.querySelectorAll('#productLinesContainer .line-item');

        console.log('=== DEBUGGING PURCHASE LINES ===');
        console.log('Total line items found:', lineItems.length);

        lineItems.forEach((line, index) => {
            console.log(`\n--- Analyzing Line ${index + 1} ---`);

            const quantityElement = line.querySelector('.quantity');
            const dealerPriceElement = line.querySelector('.dealer-price');
            const tradePriceElement = line.querySelector('.trade-price');
            const lineTotalElement = line.querySelector('.line-total');

            const rawProductId = line.dataset.productId;
            let productId;

            if (rawProductId && rawProductId.startsWith('common_')) {
                productId = parseInt(rawProductId.replace('common_', ''));
            } else {
                productId = parseInt(rawProductId);
            }

            const quantity = quantityElement ? parseFloat(quantityElement.value) : null;
            const dealerPrice = dealerPriceElement ? parseFloat(dealerPriceElement.value) : null;
            const tradePrice = tradePriceElement ? parseFloat(tradePriceElement.value) : null;
            const lineTotal = lineTotalElement ? parseFloat(lineTotalElement.textContent) : null;

            console.log('Values extracted:', {
                rawProductId,
                productId,
                quantity,
                dealerPrice,
                tradePrice,
                lineTotal
            });

            const validations = {
                hasProductId: !isNaN(productId) && productId > 0,
                hasValidQuantity: quantity > 0,
                hasValidDealerPrice: dealerPrice > 0,
                hasValidTradePrice: tradePrice > 0,
                hasValidLineTotal: lineTotal > 0
            };

            console.log('Validation results:', validations);

            if (!isNaN(productId) && productId > 0 && quantity > 0 && dealerPrice > 0 && tradePrice > 0 && lineTotal > 0) {
                const lineData = {
                    product_id: productId,
                    quantity: quantity,
                    unit_price: dealerPrice,
                    trade_price: tradePrice,
                    line_total: lineTotal
                };
                lines.push(lineData);
                console.log('✅ Line added to valid lines');
            } else {
                console.log('❌ Line failed validation');
            }
        });

        console.log('\n=== FINAL RESULTS ===');
        console.log('Valid lines collected:', lines.length);
        console.log('Lines data:', lines);
        return lines;
    }

    let isSubmitting = false;

    function submitSalesTransaction() {
        console.log('Step 1: Starting sales transaction submission');
        if (isSubmitting) return;
        isSubmitting = true;

        const submitButton = document.getElementById('submitTransaction');
        const submitIcon = document.getElementById('submitIcon');
        const submitSpinner = document.getElementById('submitSpinner');
        const submitButtonText = document.getElementById('submitButtonText');

        if (submitIcon) submitIcon.classList.add('hidden');
        if (submitSpinner) submitSpinner.classList.remove('hidden');
        if (submitButtonText) submitButtonText.textContent = 'Submitting...';

        const formElements = {
            dateInput: document.querySelector('input[name="transaction_date"]'),
            ledgerSelect: document.querySelector('select[name="ledger_id"]'),
            paymentMethod: document.querySelector('select[name="payment_method"]'),
            subtotalInput: document.getElementById('subtotalInput'),
            roundOffInput: document.getElementById('roundOffInput'),
            discountInput: document.getElementById('discountInput'),
            grandTotalInput: document.getElementById('grandTotalInput')
        };

        console.log('Step 2: Form elements collected:', formElements);

        const requiredElements = ['dateInput', 'ledgerSelect', 'paymentMethod', 'subtotalInput'];
        const missingElements = requiredElements.filter(elem => !formElements[elem]?.value);

        if (missingElements.length) {
            console.error('Missing required elements:', missingElements);
            resetSubmitButton();

            Swal.fire({
                icon: 'error',
                title: 'Form Error',
                text: `Please fill in all required fields: ${missingElements.join(', ')}`
            }).then(() => {
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            });
            return;
        }

        const regularLines = getRegularSalesLines();
        console.log('Regular lines collected:', regularLines);

        if (!regularLines.length) {
            resetSubmitButton();
            Swal.fire({
                icon: 'warning',
                title: 'No Products Added',
                text: 'Please add at least one product to the sale'
            });
            return;
        }

        const transactionData = {
            entry_type: 'sale',
            transaction_date: formElements.dateInput.value,
            ledger_id: formElements.ledgerSelect.value,
            payment_method: formElements.paymentMethod.value,
            subtotal: parseFloat(formElements.subtotalInput.value),
            round_off: parseFloat(formElements.roundOffInput?.value || 0),
            discount: parseFloat(formElements.discountInput?.value || 0),
            grand_total: parseFloat(formElements.grandTotalInput.value),
            lines: regularLines
        };

        console.log('Step 4: Transaction data built:', transactionData);

        // Use session-aware API manager
        apiManager.makeRequest('{{ route("admin.inventory.inventory_transactions.store") }}', {
                method: 'POST',
                body: JSON.stringify(transactionData)
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response ok:', response.ok);

                if (!response.ok) {
                    return response.json().then(errorData => {
                        throw new Error(errorData.message || `HTTP ${response.status}: ${response.statusText}`);
                    });
                }

                return response.json();
            })
            .then(data => {
                console.log('Transaction response:', data);

                if (!data.success) {
                    throw new Error(data.message || 'Transaction failed');
                }

                let transactionId;

                if (data.transaction_id) {
                    transactionId = data.transaction_id;
                } else if (data.data && data.data.id) {
                    transactionId = data.data.id;
                } else if (data.id) {
                    transactionId = data.id;
                }

                const damageLines = getDamageSalesLines();
                console.log('Transaction ID:', transactionId, 'Damage Lines:', damageLines);

                if (damageLines.length > 0) {
                    const damageData = {
                        inventory_transaction_id: transactionId,
                        transaction_date: formElements.dateInput.value,
                        customer_ledger_id: formElements.ledgerSelect.value,
                        damage_lines: damageLines
                    };

                    console.log('Submitting damage data:', damageData);

                    return apiManager.makeRequest('{{ route("admin.inventory.damage_transactions.store") }}', {
                        method: 'POST',
                        body: JSON.stringify(damageData)
                    }).then(damageResponse => {
                        if (!damageResponse.ok) {
                            return damageResponse.json().then(errorData => {
                                throw new Error('Damage transaction failed: ' + (errorData.message || damageResponse.statusText));
                            });
                        }
                        return damageResponse.json();
                    });
                }
                return data;
            })
            .then(finalData => {
                console.log('Step 7: Final response:', finalData);

                resetSubmitButton();

                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: 'Sales transaction saved successfully'
                }).then(() => {
                    window.location.href = '{{ route("admin.inventory.inventory_transactions.index") }}';
                });
            })
            .catch(error => {
                console.error('Step 8: Error processing sales:', error);

                resetSubmitButton();

                Swal.fire({
                    icon: 'error',
                    title: 'Transaction Failed',
                    text: error.message || 'Failed to save sales transaction',
                    confirmButtonText: 'OK'
                }).then(() => {
                    if (error.message && (
                            error.message.includes('WILL RELOAD') ||
                            error.message.includes('validation') ||
                            error.message.includes('permission')
                        )) {
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    }
                });
            });
    }

    function resetSubmitButton() {
        isSubmitting = false;
        const submitButton = document.getElementById('submitTransaction');
        const submitIcon = document.getElementById('submitIcon');
        const submitSpinner = document.getElementById('submitSpinner');
        const submitButtonText = document.getElementById('submitButtonText');

        if (submitIcon) submitIcon.classList.remove('hidden');
        if (submitSpinner) submitSpinner.classList.add('hidden');
        if (submitButtonText) submitButtonText.textContent = 'Submit Sale';
        if (submitButton) submitButton.disabled = false;
    }

    function getRegularSalesLines() {
        const lines = [];
        const regularLines = document.querySelectorAll('#productLinesContainer .line-item:not(.bg-red-50\\/30)');

        regularLines.forEach((line, index) => {
            const quantity = parseFloat(line.querySelector('.quantity').value);
            const unitPrice = parseFloat(line.querySelector('.unit-price').value);
            const tradePrice = parseFloat(line.dataset.tradePrice);
            const priceDifference = (unitPrice - tradePrice) * quantity;

            const lineData = {
                product_id: parseInt(line.dataset.productId),
                quantity: quantity,
                unit_price: unitPrice,
                line_total: parseFloat(line.querySelector('.line-total').textContent),
                line_discount: priceDifference || 0
            };

            if (lineData.product_id && lineData.quantity > 0 && lineData.unit_price > 0) {
                lines.push(lineData);
            } else {
                console.warn('Invalid line data skipped:', lineData);
            }
        });

        return lines;
    }

    function getDamageSalesLines() {
        const damageLines = [];
        const damageLinesElements = document.querySelectorAll('#productLinesContainer .line-item.bg-red-50\\/30');

        console.log('Found damage line elements:', damageLinesElements.length);

        damageLinesElements.forEach((line, index) => {
            const productSelector = line.querySelector('.product-selector');
            const productId = parseInt(productSelector.value);
            const quantity = parseFloat(line.querySelector('.quantity').value);
            const unitPrice = parseFloat(line.querySelector('.unit-price').value);
            const lineTotal = quantity * unitPrice;

            console.log('Processing damage line:', {
                productId,
                quantity,
                unitPrice,
                lineTotal
            });

            if (productId && quantity && unitPrice) {
                damageLines.push({
                    product_id: productId,
                    quantity: quantity,
                    unit_price: unitPrice,
                    line_total: lineTotal,
                    damage_reason: 'Damaged during delivery'
                });
            }
        });

        console.log('Processed damage lines:', damageLines);
        return damageLines;
    }

    function getAppendTransactionLines() {
        const lines = [];
        const lineItems = document.querySelectorAll('#productLinesContainer .line-item:not(.bg-red-50\\/30)');

        lineItems.forEach(line => {
            const productSelect = line.querySelector('.product-selector');
            if (!productSelect || !productSelect.value) return;

            const selectedOption = productSelect.options[productSelect.selectedIndex];
            const batchId = selectedOption.getAttribute('data-batch-id');
            const quantity = parseFloat(line.querySelector('.quantity').value);
            const unitPrice = parseFloat(line.querySelector('.unit-price').value);
            const tradePrice = parseFloat(line.dataset.tradePrice);
            const priceDifference = (unitPrice - tradePrice) * quantity;

            const lineData = {
                product_id: parseInt(line.dataset.productId),
                batch_id: parseInt(batchId),
                quantity: quantity,
                unit_price: unitPrice,
                line_total: parseFloat(line.querySelector('.line-total').textContent),
                line_discount: priceDifference,
                trade_price: tradePrice
            };

            if (lineData.product_id && lineData.batch_id && lineData.quantity > 0) {
                lines.push(lineData);
            }
        });

        return lines;
    }

    async function determineCustomerType(ledgerId) {
        try {
            console.log('🔍 Determining customer type with ledger validation for ID:', ledgerId);

            const response = await apiManager.makeRequest(`/admin/inventory/customer-type/${encodeURIComponent(ledgerId)}`, {
                method: 'GET'
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            console.log('📋 Customer type determination result:', data);

            if (!data.success) {
                throw new Error(data.message || 'Failed to determine customer type');
            }

            return {
                exists: data.exists,
                is_local: data.is_local,
                is_common: data.is_common,
                is_valid_customer: data.is_valid_customer,
                local_id: data.local_id,
                customer_name: data.customer_name,
                ledger_type: data.ledger_type,
                error_reason: data.error_reason
            };

        } catch (error) {
            console.error('💥 Customer type determination failed:', error);
            throw new Error('Failed to determine customer type: ' + error.message);
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM Content Loaded - Starting initialization');

        const isPurchaseForm = document.getElementById('purchaseForm') !== null;
        const isSaleForm = document.getElementById('saleForm') !== null;
        const ledgerSelect = document.querySelector('select[name="ledger_id"]');
        const submitButton = document.getElementById('submitTransaction');
        const appendButton = document.getElementById('appendTransaction');

        console.log('DOM Elements initialized:', {
            ledgerSelect: !!ledgerSelect,
            submitButton: !!submitButton,
            appendButton: !!appendButton
        });

        if (isPurchaseForm && submitButton) {
            submitButton.style.display = 'flex';
            return;
        }

        if (isSaleForm) {
            // Session-scoped variables using sessionManager
            let isProcessing = false;
            let selectedCustomerData = null;
            let isCustomerLocked = false;

            function setAppendButtonLoading(isLoading) {
                const appendButton = document.getElementById('appendTransaction');
                if (!appendButton) return;

                const appendIcon = appendButton.querySelector('#appendIcon');
                const appendSpinner = appendButton.querySelector('#appendSpinner');
                const appendButtonText = appendButton.querySelector('#appendButtonText');

                if (isLoading) {
                    appendButton.disabled = true;
                    appendButton.classList.remove('hover:from-green-600', 'hover:to-green-700');
                    appendButton.classList.add('opacity-75', 'cursor-not-allowed');
                    if (appendIcon) appendIcon.classList.add('hidden');
                    if (appendSpinner) appendSpinner.classList.remove('hidden');
                    if (appendButtonText) appendButtonText.textContent = 'Adding to Invoice...';
                    console.log('🔄 Append button set to loading state');
                } else {
                    appendButton.disabled = false;
                    appendButton.classList.remove('opacity-75', 'cursor-not-allowed');
                    appendButton.classList.add('hover:from-green-600', 'hover:to-green-700');
                    if (appendIcon) appendIcon.classList.remove('hidden');
                    if (appendSpinner) appendSpinner.classList.add('hidden');
                    if (appendButtonText) appendButtonText.textContent = 'Add to Invoice';
                    console.log('✅ Append button reset to normal state');
                }
            }

            function setSubmitButtonLoading(isLoading) {
                const submitButton = document.getElementById('submitTransaction');
                if (!submitButton) return;

                const submitIcon = submitButton.querySelector('#submitIcon');
                const submitSpinner = submitButton.querySelector('#submitSpinner');
                const submitButtonText = submitButton.querySelector('#submitButtonText');

                if (isLoading) {
                    submitButton.disabled = true;
                    submitButton.classList.remove('hover:from-blue-600', 'hover:to-blue-700');
                    submitButton.classList.add('opacity-75', 'cursor-not-allowed');
                    if (submitIcon) submitIcon.classList.add('hidden');
                    if (submitSpinner) submitSpinner.classList.remove('hidden');
                    if (submitButtonText) submitButtonText.textContent = 'Processing Transaction...';
                    console.log('🔄 Submit button set to loading state');
                } else {
                    submitButton.disabled = false;
                    submitButton.classList.remove('opacity-75', 'cursor-not-allowed');
                    submitButton.classList.add('hover:from-blue-600', 'hover:to-blue-700');
                    if (submitIcon) submitIcon.classList.remove('hidden');
                    if (submitSpinner) submitSpinner.classList.add('hidden');
                    if (submitButtonText) submitButtonText.textContent = 'Submit Transaction';
                    console.log('✅ Submit button reset to normal state');
                }
            }

            if (submitButton) {
                submitButton.style.display = 'none';
                submitButton.disabled = true;
                submitButton.classList.add('opacity-50', 'cursor-not-allowed');
            }
            if (appendButton) {
                appendButton.style.display = 'none';
                appendButton.disabled = true;
                appendButton.classList.add('opacity-50', 'cursor-not-allowed');
            }

            console.log('✅ Transaction form UI initialized - buttons disabled until customer selection');

            $(document).ready(function() {
                const ledgerSelect = document.querySelector('select[name="ledger_id"]');
                const submitButton = document.getElementById('submitTransaction');
                const appendButton = document.getElementById('appendTransaction');
                const locationFilter = document.getElementById('locationFilter');

                const allOriginalOptions = Array.from(ledgerSelect.querySelectorAll('option'));
                console.log('Stored', allOriginalOptions.length, 'original customer options');

                function filterCustomersByLocation(selectedVillage) {
                    console.log('Filtering customers by village:', selectedVillage);

                    const allOptions = allOriginalOptions.slice();

                    $('#customerLedgerSelect').val(null).trigger('change');
                    $('#customerLedgerSelect').select2('destroy');
                    $(ledgerSelect).find('option:not(:first)').remove();

                    let matchCount = 0;
                    allOptions.forEach(option => {
                        if (option.value === '') return;

                        const optionVillage = option.getAttribute('data-village');
                        if (!selectedVillage || optionVillage === selectedVillage) {
                            ledgerSelect.appendChild(option.cloneNode(true));
                            matchCount++;
                        }
                    });

                    console.log(`Filtered customers: ${matchCount} matches found for village "${selectedVillage || 'All'}"`);

                    $('#customerLedgerSelect').select2({
                        placeholder: 'Search and select customer',
                        allowClear: true,
                        width: '100%'
                    });

                    resetCustomerSelection();
                }

                function setButtonsLoadingState() {
                    console.log('🔄 Setting buttons to loading state');

                    if (submitButton) {
                        submitButton.style.display = 'none';
                    }
                    if (appendButton) {
                        appendButton.style.display = 'none';
                    }

                    showCustomerProcessingLoader();
                }

                function setButtonsSubmitState() {
                    console.log('✅ Setting buttons to submit state');
                    hideCustomerProcessingLoader();

                    if (submitButton) {
                        submitButton.style.display = 'flex';
                        submitButton.disabled = false;
                        submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
                        submitButton.classList.add('hover:from-blue-600', 'hover:to-blue-700');
                    }
                    if (appendButton) {
                        appendButton.style.display = 'none';
                    }
                }

                function setButtonsAppendState(transactionId, customerId, originalCustomerId) {
                    console.log('🔗 Setting buttons to append state', {
                        transactionId,
                        customerId,
                        originalCustomerId
                    });
                    hideCustomerProcessingLoader();

                    if (submitButton) {
                        submitButton.style.display = 'none';
                    }
                    if (appendButton) {
                        appendButton.style.display = 'flex';
                        appendButton.disabled = false;
                        appendButton.classList.remove('opacity-50', 'cursor-not-allowed');
                        appendButton.classList.add('hover:from-green-600', 'hover:to-green-700');

                        appendButton.setAttribute('data-transaction-id', transactionId);
                        appendButton.setAttribute('data-customer-id', customerId);
                        if (selectedCustomerData && selectedCustomerData.customerType === 'common') {
                            appendButton.setAttribute('data-original-customer-id', originalCustomerId);
                        } else {
                            appendButton.setAttribute('data-original-customer-id', customerId);
                        }
                    }
                }

                function showCustomerProcessingLoader() {
                    hideCustomerProcessingLoader();

                    const buttonContainer = document.querySelector('#submitTransaction')?.parentElement ||
                        document.querySelector('#appendTransaction')?.parentElement;

                    if (buttonContainer) {
                        const loader = document.createElement('div');
                        loader.id = 'customerProcessingLoader';
                        loader.className = 'flex items-center justify-center p-4 bg-blue-50 rounded-lg border border-blue-200';
                        loader.innerHTML = `
                                <div class="flex items-center space-x-3">
                                    <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <div class="text-blue-700">
                                        <div class="font-medium" id="loadingMessage">Processing customer selection...</div>
                                        <div class="text-sm text-blue-600">Please wait while we verify the customer</div>
                                    </div>
                                </div>
                            `;
                        buttonContainer.appendChild(loader);
                    }
                }

                function hideCustomerProcessingLoader() {
                    const loader = document.getElementById('customerProcessingLoader');
                    if (loader) {
                        loader.remove();
                    }
                }

                function updateLoadingMessage(message) {
                    const loadingMessage = document.getElementById('loadingMessage');
                    if (loadingMessage) {
                        loadingMessage.textContent = message;
                    }
                }

                function resetCustomerSelection() {
                    console.log('🔄 Resetting customer selection and UI state');

                    selectedCustomerData = null;
                    isCustomerLocked = false;

                    hideCustomerProcessingLoader();

                    if (submitButton) {
                        submitButton.style.display = 'none';
                        submitButton.disabled = true;
                        submitButton.classList.add('opacity-50', 'cursor-not-allowed');
                        submitButton.classList.remove('hover:from-blue-600', 'hover:to-blue-700');
                    }
                    if (appendButton) {
                        appendButton.style.display = 'none';
                        appendButton.disabled = true;
                        appendButton.classList.add('opacity-50', 'cursor-not-allowed');
                        appendButton.classList.remove('hover:from-green-600', 'hover:to-green-700');

                        appendButton.removeAttribute('data-transaction-id');
                        appendButton.removeAttribute('data-customer-id');
                        appendButton.removeAttribute('data-original-customer-id');
                    }

                    $('#customerLedgerSelect').val(null).trigger('change');
                }

                $('#customerLedgerSelect').select2({
                    placeholder: 'Search and select customer',
                    allowClear: true,
                    width: '100%'
                });

                $('#locationFilter').select2({
                    placeholder: 'Search and select village',
                    allowClear: true,
                    width: '100%'
                });

                const savedLocation = localStorage.getItem('selectedLocation');
                if (savedLocation) {
                    console.log('Loading saved village from localStorage:', savedLocation);
                    $('#locationFilter').val(savedLocation).trigger('change.select2');
                    filterCustomersByLocation(savedLocation);
                }

                $('#locationFilter').on('change', function() {
                    const selectedVillage = this.value;
                    console.log('Village filter changed to:', selectedVillage);

                    if (selectedVillage) {
                        localStorage.setItem('selectedLocation', selectedVillage);
                    } else {
                        localStorage.removeItem('selectedLocation');
                    }

                    filterCustomersByLocation(selectedVillage);
                });

                async function determineCustomerType(ledgerId) {
                    try {
                        console.log('🔍 Determining customer type with ledger validation for ID:', ledgerId);

                        const response = await apiManager.makeRequest(`/admin/inventory/determine-customer-type/${encodeURIComponent(ledgerId)}`, {
                            method: 'GET'
                        });

                        if (!response.ok) {
                            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                        }

                        const data = await response.json();
                        console.log('📋 Customer type determination result:', data);

                        if (!data.success) {
                            throw new Error(data.message || 'Failed to determine customer type');
                        }

                        return {
                            exists: data.exists,
                            is_local: data.is_local,
                            is_common: data.is_common,
                            is_valid_customer: data.is_valid_customer,
                            local_id: data.local_id,
                            customer_name: data.customer_name,
                            ledger_type: data.ledger_type,
                            error_reason: data.error_reason,
                            has_local_copy: data.has_local_copy,
                            common_customer_id: data.common_customer_id
                        };
                    } catch (error) {
                        console.error('💥 Customer type determination failed:', error);
                        throw new Error('Failed to determine customer type: ' + error.message);
                    }
                }

                function verifyCustomerIdIntegrity(customerSelectionLock) {
                    const currentSelection = $('#customerLedgerSelect').val();
                    const currentTimestamp = Date.now();

                    if (currentSelection !== customerSelectionLock.originalId) {
                        console.error('🚨 SECURITY: Customer ID changed during processing', {
                            original: customerSelectionLock.originalId,
                            current: currentSelection
                        });
                        return false;
                    }

                    const timeDiff = currentTimestamp - customerSelectionLock.timestamp;
                    if (timeDiff > 30000) {
                        console.error('🚨 SECURITY: Processing took too long', {
                            timeDiff: timeDiff,
                            maxAllowed: 30000
                        });
                        return false;
                    }

                    return true;
                }

                $('#customerLedgerSelect').on('select2:select', async function(e) {
                    console.log('🎯 Customer selection triggered');

                    selectedCustomerData = null;
                    isCustomerLocked = false;

                    if (isProcessing) {
                        console.log('⏳ Processing in progress, rejecting new selection');
                        Swal.fire({
                            icon: 'warning',
                            title: 'Processing in Progress',
                            text: 'Please wait for the current customer selection to complete.',
                            confirmButtonText: 'OK'
                        });
                        return;
                    }

                    isProcessing = true;

                    const rawLedgerId = this.value;
                    const selectedCustomerName = $(this).find('option:selected').text().trim();
                    const selectionTimestamp = Date.now();

                    console.log('📝 STRICT: Processing customer ID with ledger type validation:', {
                        id: rawLedgerId,
                        name: selectedCustomerName,
                        timestamp: selectionTimestamp
                    });

                    if (!rawLedgerId) {
                        console.log('❌ No ledger selected, resetting UI');
                        resetCustomerSelection();
                        isProcessing = false;
                        return;
                    }

                    setButtonsLoadingState();
                    updateLoadingMessage('Processing customer selection...');

                    try {
                        const customerSelectionLock = {
                            originalId: rawLedgerId,
                            customerName: selectedCustomerName,
                            timestamp: selectionTimestamp,
                            locked: true
                        };

                        console.log('🔒 LOCKED customer ID selection:', customerSelectionLock);

                        console.log('🔍 Step 1: Determining customer type with ledger validation...');
                        updateLoadingMessage('Validating customer type...');
                        const customerType = await determineCustomerType(rawLedgerId);

                        if (!verifyCustomerIdIntegrity(customerSelectionLock)) {
                            throw new Error('Customer ID selection was modified during processing. Operation cancelled for security.');
                        }

                        if (!customerType.exists) {
                            throw new Error(`Customer with ID ${rawLedgerId} does not exist in any database`);
                        }

                        if (!customerType.is_valid_customer) {
                            console.error('❌ Invalid customer ledger type:', {
                                ledger_id: rawLedgerId,
                                ledger_type: customerType.ledger_type,
                                expected_type: 'Sundry Debtors (Customer)',
                                error_reason: customerType.error_reason
                            });

                            throw new Error(customerType.message || `Selected ledger is not a customer ledger. Found: ${customerType.ledger_type}`);
                        }

                        console.log('✅ Customer ledger type validation passed:', {
                            ledger_id: rawLedgerId,
                            ledger_type: customerType.ledger_type,
                            is_valid_customer: customerType.is_valid_customer
                        });

                        updateLoadingMessage('Processing customer data...');
                        let finalLedgerIdForTransactionCheck;

                        if (customerType.is_common) {
                            if (customerType.has_local_copy) {
                                finalLedgerIdForTransactionCheck = customerType.local_id;
                                console.log('🌐 Using existing local copy of common customer:', {
                                    originalCommonId: rawLedgerId,
                                    localId: finalLedgerIdForTransactionCheck,
                                    customerName: customerType.customer_name,
                                    note: 'Transaction check will use local ID, verification will use common ID'
                                });
                            } else {
                                console.log('🌐 Creating local copy for COMMON customer:', {
                                    original: rawLedgerId,
                                    customerName: customerType.customer_name
                                });
                                updateLoadingMessage('AI Generating Your Customers...');

                                if (!verifyCustomerIdIntegrity(customerSelectionLock)) {
                                    throw new Error('Customer selection was modified during processing. Operation cancelled.');
                                }

                                const response = await apiManager.makeRequest('/admin/inventory/create-local-customer', {
                                    method: 'POST',
                                    body: JSON.stringify({
                                        common_customer_id: rawLedgerId,
                                        selection_timestamp: selectionTimestamp,
                                        strict_mode: true
                                    })
                                });

                                const data = await response.json();
                                console.log('📋 Local customer creation response:', data);

                                if (!data.success) {
                                    throw new Error(data.message || 'Failed to create local customer copy');
                                }

                                finalLedgerIdForTransactionCheck = data.local_customer_id || data.customer.id;

                                if (data.customer && data.customer.common_customer_id != rawLedgerId) {
                                    throw new Error(`Created customer relationship verification failed. Expected: ${rawLedgerId}, Got: ${data.customer.common_customer_id}`);
                                }
                            }
                        } else {
                            finalLedgerIdForTransactionCheck = rawLedgerId;
                            console.log('🏠 Processing LOCAL customer (validated):', {
                                ledgerId: rawLedgerId,
                                customerName: customerType.customer_name,
                                ledgerType: customerType.ledger_type
                            });
                        }

                        if (!verifyCustomerIdIntegrity(customerSelectionLock)) {
                            throw new Error('Customer selection was modified during processing. Operation cancelled.');
                        }

                        console.log('🔍 Step 2: Checking for existing transactions with verified customer...');
                        updateLoadingMessage('Checking for existing transactions...');

                        console.log('📞 Making transaction check request with ID:', finalLedgerIdForTransactionCheck);
                        const transactionCheckUrl = `/admin/inventory/check-transactions/${finalLedgerIdForTransactionCheck}?verify_customer_id=${rawLedgerId}`;
                        console.log('🌐 Transaction check URL:', transactionCheckUrl);

                        const transactionResponse = await apiManager.makeRequest(transactionCheckUrl, {
                            method: 'GET'
                        });

                        const transactionData = await transactionResponse.json();
                        console.log('📊 Transaction check response:', transactionData);

                        if (!transactionResponse.ok) {
                            if (transactionData.message) {
                                throw new Error(`Transaction check failed: ${transactionData.message}`);
                            } else {
                                throw new Error(`Transaction check failed with status: ${transactionResponse.status}`);
                            }
                        }

                        if (transactionData.hasOwnProperty('customer_verified') && !transactionData.customer_verified) {
                            console.error('❌ Customer verification failed in transaction check:', {
                                ledger_id: finalLedgerIdForTransactionCheck,
                                verify_customer_id: rawLedgerId,
                                ledger_info: transactionData.ledger_info
                            });

                            let errorMessage = 'Customer verification failed during transaction check.';
                            if (transactionData.ledger_info) {
                                if (transactionData.ledger_info.common_customer_id) {
                                    errorMessage += ` Expected customer ID ${rawLedgerId} to match either local ID ${transactionData.ledger_info.id} or common ID ${transactionData.ledger_info.common_customer_id}.`;
                                } else {
                                    errorMessage += ` Expected customer ID ${rawLedgerId} to match local ID ${transactionData.ledger_info.id}.`;
                                }
                            }
                            throw new Error(errorMessage);
                        }

                        console.log('✅ Customer verification passed in transaction check');

                        selectedCustomerData = {
                            originalCustomerId: rawLedgerId,
                            finalLedgerId: finalLedgerIdForTransactionCheck,
                            customerName: selectedCustomerName,
                            customerType: customerType.is_common ? 'common' : 'local',
                            ledgerType: customerType.ledger_type,
                            selectionTimestamp: selectionTimestamp,
                            verified: true,
                            locked: true
                        };
                        isCustomerLocked = true;

                        console.log('🔒 FINAL: Customer selection locked with verified data:', selectedCustomerData);

                        if (transactionData.exists) {
                            console.log('⚠️ Existing transaction found - showing append option');
                            setButtonsAppendState(
                                transactionData.transaction_id,
                                finalLedgerIdForTransactionCheck,
                                rawLedgerId
                            );
                        } else {
                            console.log('✅ No existing transaction - showing submit option');
                            setButtonsSubmitState();
                        }

                        $(this).data('selected-customer-data', selectedCustomerData);
                        console.log('💾 Stored verified customer data for form submission:', selectedCustomerData);

                    } catch (error) {
                        console.error('💥 STRICT: Customer selection failed:', error);
                        resetCustomerSelection();

                        let errorTitle = 'Customer Selection Failed';
                        let errorText = error.message || 'Failed to process customer selection. Please try again.';

                        if (error.message.includes('not a customer ledger')) {
                            errorTitle = 'Invalid Ledger Type';
                            errorText = error.message + '\n\nPlease select a customer from the customer list, not from other ledger types.';
                        } else if (error.message.includes('selection was modified')) {
                            errorTitle = 'Security Error';
                            errorText = 'Customer selection was modified during processing for security reasons. Please try again.';
                        } else if (error.message.includes('verification failed')) {
                            errorTitle = 'Verification Error';
                            errorText = 'Customer verification failed. Please refresh the page and try again.';
                        }

                        Swal.fire({
                            icon: 'error',
                            title: errorTitle,
                            text: errorText,
                            confirmButtonText: 'OK'
                        });
                    } finally {
                        isProcessing = false;
                    }
                });

                $('#customerLedgerSelect').on('select2:clear', function() {
                    console.log('🧹 Customer selection cleared');
                    resetCustomerSelection();
                });
            });

            if (submitButton) {
                submitButton.addEventListener('click', function(e) {
                    console.log('📤 Submit transaction button clicked');

                    if (!isCustomerLocked || !selectedCustomerData || !selectedCustomerData.verified) {
                        console.error('❌ Customer selection not properly locked for submit');
                        Swal.fire({
                            icon: 'error',
                            title: 'Customer Selection Required',
                            text: 'Please select a customer before submitting the transaction.',
                            confirmButtonText: 'OK'
                        });
                        e.preventDefault();
                        return false;
                    }

                    setSubmitButtonLoading(true);

                    console.log('✅ Form submission proceeding with verified customer data:', selectedCustomerData);

                    return true;
                });
            }

            if (appendButton) {
                appendButton.addEventListener('click', async function(e) {
                    e.preventDefault();
                    console.log('🔗 Append transaction button clicked');

                    const transactionId = this.getAttribute('data-transaction-id');
                    const customerId = this.getAttribute('data-customer-id');
                    const originalCustomerId = this.getAttribute('data-original-customer-id');

                    console.log('📋 Append transaction data:', {
                        transactionId,
                        customerId,
                        originalCustomerId,
                        regularLinesCount: getAppendTransactionLines().length
                    });

                    if (!transactionId || !customerId) {
                        console.error('❌ Missing transaction ID or customer ID for append');
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Missing transaction information. Please refresh and try again.',
                            confirmButtonText: 'OK'
                        });
                        return;
                    }

                    if (!isCustomerLocked || !selectedCustomerData || !selectedCustomerData.verified) {
                        console.error('❌ Customer selection not properly locked for append');
                        Swal.fire({
                            icon: 'error',
                            title: 'Security Error',
                            text: 'Customer selection is not properly verified. Please refresh and try again.',
                            confirmButtonText: 'OK'
                        });
                        return;
                    }

                    setAppendButtonLoading(true);

                    const regularLines = getAppendTransactionLines();
                    const damageLines = getDamageSalesLines();

                    console.log('Collected lines for append:', regularLines);
                    console.log('Damage lines:', damageLines);

                    if (!regularLines.length && !damageLines.length) {
                        console.warn('No products found to append');
                        setAppendButtonLoading(false);
                        Swal.fire({
                            icon: 'warning',
                            title: 'No Products Added',
                            text: 'Please add at least one product to append to the invoice',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        });
                        return;
                    }

                    const formElements = {
                        dateInput: document.querySelector('input[name="transaction_date"]'),
                        ledgerSelect: document.querySelector('select[name="ledger_id"]'),
                        subtotalInput: document.getElementById('subtotalInput'),
                        grandTotalInput: document.getElementById('grandTotalInput')
                    };

                    try {
                        if (regularLines.length > 0) {
                            const appendData = {
                                transaction_id: transactionId,
                                customer_id: customerId,
                                original_customer_id: originalCustomerId,
                                lines: regularLines,
                                subtotal: parseFloat(formElements.subtotalInput.value) || 0,
                                grand_total: parseFloat(formElements.grandTotalInput.value) || 0,
                                customer_verification: {
                                    selection_timestamp: selectedCustomerData.selectionTimestamp,
                                    customer_type: selectedCustomerData.customerType,
                                    ledger_type: selectedCustomerData.ledgerType
                                }
                            };

                            console.log('📤 Submitting append data with customer verification:', appendData);

                            const appendResponse = await apiManager.makeRequest('/admin/inventory/append-transaction', {
                                method: 'POST',
                                body: JSON.stringify(appendData)
                            });

                            const appendResult = await appendResponse.json();
                            console.log('✅ Append response:', appendResult);

                            if (!appendResult.success) {
                                throw new Error(appendResult.message || 'Failed to append products to invoice');
                            }
                        }

                        if (damageLines.length > 0) {
                            const damageData = {
                                inventory_transaction_id: transactionId,
                                transaction_date: formElements.dateInput.value,
                                customer_ledger_id: formElements.ledgerSelect.value,
                                damage_lines: damageLines
                            };

                            console.log('📤 Submitting damage data:', damageData);

                            const damageResponse = await apiManager.makeRequest('{{ route("admin.inventory.damage_transactions.store") }}', {
                                method: 'POST',
                                body: JSON.stringify(damageData)
                            });

                            const damageResult = await damageResponse.json();
                            console.log('📊 Damage response:', damageResult);

                            if (!damageResult.success) {
                                console.warn('⚠️ Damage transaction failed but continuing:', damageResult.message);
                            }
                        }

                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: 'Products have been successfully added to the existing invoice',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.href = '/admin/inventory/inventory_transactions';
                        });

                    } catch (error) {
                        console.error('💥 Error processing append transaction:', error);

                        setAppendButtonLoading(false);

                        let errorMessage = 'An unexpected error occurred while processing the transaction';
                        let errorTitle = 'Transaction Failed';

                        if (error.message) {
                            if (error.message.includes('Validation failed:')) {
                                errorTitle = 'Validation Error';
                                errorMessage = error.message.replace('Validation failed: ', '');
                                if (errorMessage.includes(';')) {
                                    const errors = errorMessage.split(';');
                                    errorMessage = errors.map(err => `• ${err.trim()}`).join('\n');
                                }
                            } else if (error.message.includes('already exists with unit price')) {
                                errorTitle = 'Price Conflict';
                                errorMessage = error.message;
                            } else if (error.message.includes('Insufficient stock')) {
                                errorTitle = 'Insufficient Stock';
                                errorMessage = error.message;
                            } else if (error.message.includes("don't have permission")) {
                                errorTitle = 'Permission Denied';
                                errorMessage = error.message;
                            } else if (error.message.includes('Accounting entry failed')) {
                                errorTitle = 'Accounting Error';
                                errorMessage = error.message.replace('Accounting entry failed: ', '');
                            } else if (error.message.includes('Customer verification failed')) {
                                errorTitle = 'Customer Verification Error';
                                errorMessage = 'Customer verification failed. Please refresh the page and try again.';
                            } else {
                                errorMessage = error.message;
                            }
                        }

                        Swal.fire({
                            icon: 'error',
                            title: errorTitle,
                            text: errorMessage,
                            confirmButtonText: 'OK',
                            customClass: {
                                popup: 'swal-wide'
                            }
                        });
                    }
                });
            }
        }
    });


    // Legacy function support (for backward compatibility)
    function calculateTotal() {
        console.warn('Legacy calculateTotal() called - migrating to new system');
        if (window.transactionCalculator) {
            window.transactionCalculator.recalculate();
        }
    }

    function calculateDamageTotal() {
        console.warn('Legacy calculateDamageTotal() called - migrating to new system');
        if (window.transactionCalculator) {
            window.transactionCalculator.recalculate();
        }
    }

    function calculatePurchaseTotal() {
        console.warn('Legacy calculatePurchaseTotal() called - migrating to new system');
        if (window.transactionCalculator) {
            window.transactionCalculator.recalculate();
        }
    }

    const DAMAGE_STORE_URL = "{{ route('admin.inventory.damage_transactions.store') }}";
</script>

<style>
    @media (max-width: 768px) {
        .container-fluid {
            overflow-x: hidden;
        }

        #transactionForm {
            width: 100%;
            box-shadow: -4px 0 6px -1px rgba(0, 0, 0, 0.1);
        }
    }

    button:disabled {
        opacity: 0.7;
        cursor: not-allowed;
        transform: none !important;
        box-shadow: none !important;
    }

    .swal-wide {
        width: 600px !important;
    }
</style>
@endsection