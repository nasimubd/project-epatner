@extends('admin.layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6 pb-20 sm:pb-6">
    <div class="bg-white rounded-lg shadow-lg">
        <div class="p-3 sm:p-6 border-b border-gray-200">
            <h2 class="text-lg sm:text-2xl font-semibold text-gray-800">Deposit Voucher</h2>
        </div>

        <div class="p-3 sm:p-6">
            <form action="{{ route('admin.accounting.deposit.store') }}" method="POST" id="depositForm">
                @csrf
                <div class="mb-6">
                    <h3 class="text-base sm:text-lg font-medium text-gray-700 mb-3">SELECT LEDGER </h3>
                    <div class="grid grid-cols-1 gap-4">
                        <div class="col-span-1">
                            <select name="ledger_id" id="ledger_id" class="w-full rounded-md border-gray-300 text-sm" required>
                                <option value="">Select a Ledger</option>
                                @foreach($ledgers as $ledger)
                                <option value="{{ $ledger->id }}" data-balance="{{ $ledger->current_balance }}">
                                    {{ $ledger->name }} ({{ $ledger->ledger_type }}) - Balance: ৳{{ number_format($ledger->current_balance, 2) }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-1">
                            <label for="total_collection" class="block text-sm font-medium text-gray-700">TOTAL COLLECTION</label>
                            <input type="number" id="total_collection" name="total_collection" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm bg-gray-50 text-sm" step="0.01" min="0" readonly>
                        </div>
                        <!-- Add Due Collection Field -->
                        <div class="col-span-1">
                            <label for="due_collection" class="block text-sm font-medium text-gray-700">DUE COLLECTION</label>
                            <input type="number" id="due_collection" name="due_collection" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm" step="0.01" min="0" placeholder="Enter due collection amount">
                            <p class="mt-1 text-xs text-gray-500">Enter any additional due amount collected</p>
                        </div>
                    </div>
                </div>
                <!-- Notes Grid -->
                <div class="bg-white rounded-lg">
                    <h3 class="text-base sm:text-lg font-medium text-gray-700 mb-3">NOTE COUNT</h3>
                    <div class="space-y-2">
                        @foreach([1000, 500, 200, 100, 50, 20, 10, 5, 2, 1] as $note)
                        <div class="grid grid-cols-12 gap-2 p-2 hover:bg-gray-50 rounded-md transition items-center">
                            <span class="col-span-3 text-gray-700 font-medium text-sm sm:text-base">৳{{ number_format($note) }}</span>
                            <input type="number"
                                name="count[{{ $note }}]"
                                placeholder="Count"
                                class="note-count col-span-4 rounded-md border-gray-300 text-sm"
                                value="0"
                                min="0">
                            <input type="number"
                                name="amount[{{ $note }}]"
                                class="note-amount col-span-5 rounded-md border-gray-300 bg-gray-50 text-sm"
                                value="0"
                                readonly>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Expense Section -->
                <div class="mt-4 sm:mt-6 space-y-3">
                    <button type="button" id="add-expense" class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700">
                        <span class="mr-1">+</span> Add Expense
                    </button>

                    <div id="expenseList" class="space-y-2">
                        <div class="expense-item grid grid-cols-12 gap-2">
                            <input type="text"
                                name="expense_description[]"
                                placeholder="Description"
                                class="col-span-6 rounded-md border-gray-300 text-sm">
                            <input type="number"
                                name="expense_amount[]"
                                placeholder="Amount"
                                class="expense-amount col-span-5 rounded-md border-gray-300 text-sm"
                                min="0">
                            <button type="button" class="remove-expense col-span-1 text-red-500 hover:text-red-700">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Damage Lines Section -->
                <div class="mt-4 sm:mt-6 space-y-3">
                    <button type="button" id="add-damage" class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-white bg-orange-600 rounded-md hover:bg-orange-700">
                        <span class="mr-1">+</span> Add Damage Line
                    </button>

                    <div id="damageList" class="space-y-2">
                        <div class="damage-item grid grid-cols-12 gap-2">
                            <input type="number"
                                name="damage_amount[]"
                                placeholder="Damage Amount"
                                class="damage-amount col-span-5 rounded-md border-gray-300 text-sm"
                                min="0">
                            <select name="damage_supplier_ledger[]"
                                class="col-span-6 rounded-md border-gray-300 text-sm">
                                <option value="">Select Supplier Ledger</option>
                                @foreach($supplierLedgers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                            <button type="button" class="remove-damage col-span-1 text-red-500 hover:text-red-700">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Market Short Section -->
                <div class="mt-4 sm:mt-6 space-y-3">
                    <h3 class="text-base sm:text-lg font-medium text-gray-700">MARKET SHORT</h3>
                    <div class="grid grid-cols-1 gap-2">
                        <input type="number"
                            name="market_short"
                            id="market_short"
                            placeholder="Market Short Amount"
                            class="rounded-md border-gray-300 text-sm"
                            min="0"
                            step="0.01">
                    </div>
                </div>

                <!-- Godown Short Section -->
                <div class="mt-4 sm:mt-6 space-y-3">
                    <h3 class="text-base sm:text-lg font-medium text-gray-700">GODOWN SHORT</h3>
                    <div class="grid grid-cols-1 gap-2">
                        <input type="number"
                            name="godown_short"
                            id="godown_short"
                            placeholder="Godown Short Amount"
                            class="rounded-md border-gray-300 text-sm"
                            min="0"
                            step="0.01">
                    </div>
                </div>

                <!-- Totals Section -->
                <div class="mt-4 sm:mt-6 space-y-3 border-t pt-3">
                    <div class="grid grid-cols-2 gap-2 items-center">
                        <span class="text-sm font-medium text-gray-700">TOTAL CASH:</span>
                        <input type="number" id="total" name="total" class="rounded-md border-gray-300 bg-gray-50 text-sm" value="0" readonly>
                    </div>
                    <div class="grid grid-cols-2 gap-2 items-center">
                        <span class="text-sm font-medium text-gray-700"><b>COLLECTIBLE:</b></span>
                        <input type="number" id="net-total" name="net_total" class="rounded-md border-gray-300 bg-gray-50 text-sm" value="0" readonly>
                    </div>
                </div>

                <div id="cash-warning" class="mt-2 text-red-600 text-sm font-medium hidden"></div>

                <!-- Action Buttons -->
                <div class="flex justify-end gap-2 mt-4 sm:mt-6 mb-4 sm:mb-6">
                    <button type="reset" class="px-4 py-1.5 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700">
                        Clear
                    </button>
                    <button type="button" id="submitButton" class="px-4 py-1.5 text-sm font-medium text-white bg-gray-600 hover:bg-gray-700 rounded-md transition-colors duration-200">
                        Submit
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get all required elements
        const noteInputs = document.querySelectorAll('.note-count');
        const amountInputs = document.querySelectorAll('.note-amount');
        const totalInput = document.getElementById('total');
        const netTotalInput = document.getElementById('net-total');
        const addExpenseBtn = document.getElementById('add-expense');
        const expenseList = document.getElementById('expenseList');
        const addDamageBtn = document.getElementById('add-damage');
        const damageList = document.getElementById('damageList');
        const ledgerSelect = document.getElementById('ledger_id');
        const totalCollectionInput = document.getElementById('total_collection');
        const dueCollectionInput = document.getElementById('due_collection');
        const marketShortInput = document.getElementById('market_short');
        const godownShortInput = document.getElementById('godown_short');
        const submitButton = document.getElementById('submitButton');
        const depositForm = document.getElementById('depositForm');

        // Function to calculate individual note amount and update totals
        function calculateAmount(noteInput, index) {
            const denomination = parseInt(noteInput.name.match(/\[(\d+)\]/)[1]);
            const count = parseInt(noteInput.value) || 0;
            const lineAmount = denomination * count;
            amountInputs[index].value = lineAmount;
            updateTotals();
        }

        // Function to update total and net total with rounding
        function updateTotals() {
            // Get total collection amount and round it
            let totalCollection = parseFloat(totalCollectionInput.value) || 0;
            totalCollection = Math.round(totalCollection);
            totalCollectionInput.value = totalCollection;

            // Get due collection amount and round it
            let dueCollection = parseFloat(dueCollectionInput.value) || 0;
            dueCollection = Math.round(dueCollection);
            dueCollectionInput.value = dueCollection;

            // Calculate total cash from denominations
            let totalCash = 0;
            amountInputs.forEach(input => {
                totalCash += parseFloat(input.value) || 0;
            });
            totalInput.value = totalCash;

            // Calculate total expenses
            let expenseTotal = 0;
            document.querySelectorAll('.expense-amount').forEach(expense => {
                expenseTotal += parseFloat(expense.value) || 0;
            });

            // Calculate total damage amounts
            let damageTotal = 0;
            document.querySelectorAll('.damage-amount').forEach(damage => {
                damageTotal += parseFloat(damage.value) || 0;
            });

            // Get market short and godown short amounts
            let marketShort = parseFloat(marketShortInput.value) || 0;
            let godownShort = parseFloat(godownShortInput.value) || 0;

            // Net total is: (Total Collection + Due Collection) - Total Cash - Expenses - Damages - Market Short - Godown Short
            let netTotal = (totalCollection - dueCollection) - totalCash - expenseTotal - damageTotal - marketShort - godownShort;
            netTotal = Math.round(netTotal); // Round to nearest integer
            netTotalInput.value = netTotal;

            // Color code the net total based on value and update submit button
            if (netTotal <= 0) {
                // When net total is zero or negative
                netTotalInput.classList.remove('bg-red-100', 'text-red-800');
                netTotalInput.classList.add('bg-green-100', 'text-green-800');

                // Enable and style submit button
                submitButton.disabled = false;
                submitButton.style.cursor = 'pointer';

                submitButton.classList.remove('bg-red-600', 'hover:bg-red-700', 'opacity-60');
                submitButton.classList.remove('bg-gray-600', 'hover:bg-gray-700');
                submitButton.classList.add('bg-green-600', 'hover:bg-green-700');

                // Hide warning when net total is valid
                const warningElement = document.getElementById('cash-warning');
                if (warningElement) {
                    warningElement.classList.add('hidden');
                }
            } else {
                // When net total is positive
                netTotalInput.classList.remove('bg-green-100', 'text-green-800');
                netTotalInput.classList.add('bg-red-100', 'text-red-800');

                // Disable and style submit button
                submitButton.disabled = true;
                submitButton.style.cursor = 'not-allowed';

                submitButton.classList.remove('bg-green-600', 'hover:bg-green-700');
                submitButton.classList.remove('bg-gray-600', 'hover:bg-gray-700');
                submitButton.classList.add('bg-red-600', 'hover:bg-red-700', 'opacity-60');

                // Show warning based on net total
                const warningElement = document.getElementById('cash-warning');
                if (warningElement) {
                    warningElement.textContent = `WARNING: এখনো: ৳${netTotal.toFixed(0)} টাকা দিতে হবে`;
                    warningElement.classList.remove('hidden');
                }
            }
        }

        // Add input event listeners to all note count inputs
        noteInputs.forEach((input, index) => {
            input.addEventListener('input', () => {
                calculateAmount(input, index);
            });
        });

        // Handle single expense addition
        addExpenseBtn.addEventListener('click', () => {
            const newExpenseRow = `
<div class="expense-item grid grid-cols-12 gap-2">
<input type="text" name="expense_description[]" placeholder="Description" class="col-span-6 rounded-md border-gray-300 text-sm">
<input type="number" name="expense_amount[]" placeholder="Amount" class="expense-amount col-span-5 rounded-md border-gray-300 text-sm" min="0">
<button type="button" class="remove-expense col-span-1 text-red-500 hover:text-red-700">
    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
    </svg>
</button>
</div>
`;

            expenseList.insertAdjacentHTML('beforeend', newExpenseRow);
            const newRow = expenseList.lastElementChild;
            attachExpenseListeners(newRow);
        });

        // Handle damage line addition
        addDamageBtn.addEventListener('click', () => {
            // In the JavaScript section, update the newDamageRow template
            const newDamageRow = `
<div class="damage-item grid grid-cols-12 gap-2">
    <input type="number" name="damage_amount[]" placeholder="Damage Amount" class="damage-amount col-span-5 rounded-md border-gray-300 text-sm" min="0">
    <select name="damage_supplier_ledger[]" class="col-span-6 rounded-md border-gray-300 text-sm">
        <option value="">Select Supplier Ledger</option>
        @foreach($supplierLedgers as $supplier)
        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
        @endforeach
    </select>
    <button type="button" class="remove-damage col-span-1 text-red-500 hover:text-red-700">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
    </button>
</div>
`;


            damageList.insertAdjacentHTML('beforeend', newDamageRow);
            const newRow = damageList.lastElementChild;
            attachDamageListeners(newRow);
        });

        // Enhanced expense listeners with proper removal
        function attachExpenseListeners(element) {
            const removeBtn = element.querySelector('.remove-expense');
            const expenseAmount = element.querySelector('.expense-amount');

            removeBtn.addEventListener('click', () => {
                element.remove();
                updateTotals();
            });

            expenseAmount.addEventListener('input', updateTotals);
        }

        // Enhanced damage listeners with proper removal
        function attachDamageListeners(element) {
            const removeBtn = element.querySelector('.remove-damage');
            const damageAmount = element.querySelector('.damage-amount');

            removeBtn.addEventListener('click', () => {
                element.remove();
                updateTotals();
            });

            damageAmount.addEventListener('input', updateTotals);
        }

        // Initialize existing expense rows
        document.querySelectorAll('.expense-item').forEach(item => {
            attachExpenseListeners(item);
        });

        // Initialize existing damage rows
        document.querySelectorAll('.damage-item').forEach(item => {
            attachDamageListeners(item);
        });

        // Ledger selection change handler
        if (ledgerSelect) {
            ledgerSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                if (selectedOption && selectedOption.dataset.balance) {
                    // Get the ledger balance from the data attribute and round it
                    const ledgerBalance = Math.round(parseFloat(selectedOption.dataset.balance));

                    // Update the total collection field with the rounded ledger balance
                    if (totalCollectionInput) {
                        totalCollectionInput.value = ledgerBalance;
                    }

                    // Update totals after setting the collection amount
                    updateTotals();
                }
            });
        }

        // Total collection input handler - in case it changes
        if (totalCollectionInput) {
            totalCollectionInput.addEventListener('input', function() {
                // Round the input value when user changes it
                this.value = Math.round(parseFloat(this.value) || 0);
                updateTotals();
            });
        }

        // Due collection input handler
        if (dueCollectionInput) {
            dueCollectionInput.addEventListener('input', function() {
                // Round the input value when user changes it
                this.value = Math.round(parseFloat(this.value) || 0);
                updateTotals();
            });
        }

        // Market short input handler
        if (marketShortInput) {
            marketShortInput.addEventListener('input', function() {
                // Round the input value when user changes it
                this.value = Math.round(parseFloat(this.value) || 0);
                updateTotals();
            });
        }

        // Godown short input handler
        if (godownShortInput) {
            godownShortInput.addEventListener('input', function() {
                // Round the input value when user changes it
                this.value = Math.round(parseFloat(this.value) || 0);
                updateTotals();
            });
        }

        // Submit button click handler
        submitButton.addEventListener('click', function(e) {
            e.preventDefault();

            // Check if a ledger is selected
            if (!ledgerSelect.value) {
                alert('Please select a ledger first');
                return;
            }

            // Check if there's any cash amount entered
            const totalCash = parseFloat(totalInput.value) || 0;
            if (totalCash <= 0) {
                alert('Please enter some cash amount');
                return;
            }

            // Form is valid, submit it regardless of net total value
            depositForm.submit();
        });

        // Initial calculation
        updateTotals();
    });
</script>

@endpush

@endsection