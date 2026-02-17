@extends('admin.layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-4xl mx-auto bg-white shadow-lg rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-2xl font-bold text-gray-800">Create New Transaction</h2>
        </div>

        <form action="{{ route('admin.accounting.transactions.store') }}" method="POST" class="p-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Transaction Type</label>
                    <select name="transaction_type" id="transaction_type"
                        class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                        <option value="">-- Select Type --</option>
                        <option value="Payment">Payment</option>
                        <option value="Receipt">Receipt</option>
                        <option value="Journal">Journal</option>
                        <option value="Contra">Contra</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Transaction Date</label>
                    <input type="date" name="transaction_date" required
                        value="{{ now()->format('Y-m-d') }}"
                        class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>

            <!-- Simple Entry (Payment, Receipt, Contra) -->
            <div id="simple-entry" class="hidden">
                <!-- Debit Line -->
                <div class="bg-green-50 p-6 rounded-lg mb-6">
                    <div class="flex items-center mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 50 50" style="fill:#40C057;" class="mr-2">
                            <path d="M25,2C12.317,2,2,12.317,2,25s10.317,23,23,23s23-10.317,23-23S37.683,2,25,2z M37,26H26v11h-2V26H13v-2h11V13h2v11h11V26z"></path>
                        </svg>
                        <h3 class="text-lg font-semibold text-green-700">Debit Line</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-green-700 mb-2">Select Ledger</label>
                            <select name="lines[0][ledger_id]" id="debit_ledger_select" class="ledger-select w-full rounded-lg border-green-300 focus:border-green-500 focus:ring-green-500">
                                <option value="">-- Select Ledger --</option>
                                @foreach($ledgers as $ledger)
                                <option value="{{ $ledger->id }}" data-type="{{ $ledger->ledger_type }}">{{ $ledger->name }} ({{ $ledger->ledger_type }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-green-700 mb-2">Amount</label>
                            <input type="number" step="0.01" name="lines[0][debit_amount]" value="0"
                                class="w-full rounded-lg border-green-300 focus:border-green-500 focus:ring-green-500">
                        </div>
                    </div>
                </div>

                <!-- Credit Line -->
                <div class="bg-red-50 p-6 rounded-lg mb-6">
                    <div class="flex items-center mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 50 50" style="fill:#DC3545;" class="mr-2">
                            <path d="M25,2C12.317,2,2,12.317,2,25s10.317,23,23,23s23-10.317,23-23S37.683,2,25,2z M37,26H13v-2h24V26z"></path>
                        </svg>
                        <h3 class="text-lg font-semibold text-red-700">Credit Line</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-red-700 mb-2">Select Ledger</label>
                            <select name="lines[1][ledger_id]" id="credit_ledger_select" class="ledger-select w-full rounded-lg border-red-300 focus:border-red-500 focus:ring-red-500">
                                <option value="">-- Select Ledger --</option>
                                @foreach($ledgers as $ledger)
                                <option value="{{ $ledger->id }}" data-type="{{ $ledger->ledger_type }}">{{ $ledger->name }} ({{ $ledger->ledger_type }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-red-700 mb-2">Amount</label>
                            <input type="number" step="0.01" name="lines[1][credit_amount]" value="0"
                                class="w-full rounded-lg border-red-300 focus:border-red-500 focus:ring-red-500">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Journal Entry (Multiple Lines) -->
            <div id="journal-entry" class="hidden">
                <!-- Debit Lines Section -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 50 50" style="fill:#40C057;" class="mr-2">
                                <path d="M25,2C12.317,2,2,12.317,2,25s10.317,23,23,23s23-10.317,23-23S37.683,2,25,2z M37,26H26v11h-2V26H13v-2h11V13h2v11h11V26z"></path>
                            </svg>
                            <h3 class="text-lg font-semibold text-green-700">Debit Lines</h3>
                        </div>
                        <button type="button" id="add-debit-line" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Add Debit Line
                        </button>
                    </div>
                    <div id="debit-lines-container">
                        <!-- Debit lines will be added here -->
                    </div>
                    <div class="text-right mt-2">
                        <span class="text-sm font-medium text-green-700">Total Debit: <span id="total-debit">0.00</span></span>
                    </div>
                </div>

                <!-- Credit Lines Section -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 50 50" style="fill:#DC3545;" class="mr-2">
                                <path d="M25,2C12.317,2,2,12.317,2,25s10.317,23,23,23s23-10.317,23-23S37.683,2,25,2z M37,26H13v-2h24V26z"></path>
                            </svg>
                            <h3 class="text-lg font-semibold text-red-700">Credit Lines</h3>
                        </div>
                        <button type="button" id="add-credit-line" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Add Credit Line
                        </button>
                    </div>
                    <div id="credit-lines-container">
                        <!-- Credit lines will be added here -->
                    </div>
                    <div class="text-right mt-2">
                        <span class="text-sm font-medium text-red-700">Total Credit: <span id="total-credit">0.00</span></span>
                    </div>
                </div>

                <!-- Balance Check -->
                <div class="bg-blue-50 p-4 rounded-lg mb-6">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-blue-700">Balance Check:</span>
                        <span id="balance-status" class="text-sm font-bold"></span>
                    </div>
                </div>
            </div>

            <!-- Narration Field -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">AI Recommendations</label>
                <div id="narration-container">
                    <select name="narration" id="narration_select" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 hidden">
                        <option value="">-- Select Narration --</option>
                    </select>
                    <textarea
                        name="narration"
                        id="narration_textarea"
                        rows="3"
                        class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Enter transaction details or notes">{{ old('narration') }}</textarea>
                </div>
            </div>

            <div class="flex justify-between items-center mt-8">
                <a href="{{ route('admin.accounting.transactions.index') }}" class="text-gray-600 hover:text-gray-800">Back to Transactions</a>
                <button type="submit" id="submitButton" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg flex items-center justify-center">
                    <span class="inline-flex items-center">
                        <svg id="defaultIcon" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <svg id="spinnerIcon" class="hidden w-5 h-5 mr-2 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span id="buttonText">Save Transaction</span>
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>
<style>
    .select2-container.is-invalid .select2-selection {
        border-color: #dc3545 !important;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
    }

    .invalid-feedback {
        color: #dc3545;
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }
</style>

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Pass ledger data from PHP to JavaScript
        const ledgersData = @json($ledgers);
        // Fix for ledger type property (Laravel collections use 'ledger_type', not 'type')
        // Map ledgersData to ensure each ledger has a 'type' property for JS use
        ledgersData.forEach(function(ledger) {
            if (!ledger.type && ledger.ledger_type) {
                ledger.type = ledger.ledger_type;
            }
        });
        let debitLineIndex = 0;
        let creditLineIndex = 1000; // Start credit lines from 1000 to avoid conflicts

        // Initialize Select2
        function initializeSelect2() {
            $('.ledger-select').select2({
                placeholder: 'Search and select ledger',
                allowClear: true,
                width: '100%'
            });
        }

        initializeSelect2();

        // Cash/Bank ledger types
        const cashBankTypes = ['Bank Accounts', 'Bank OD A/c', 'Cash-in-Hand'];

        const narrationOptions = {
            'Payment': [
                'CHARGED AUDIT FEE', 'CHARGED BY BANK', 'CHARGED DEPRECIATION', 'CHARGED LOAN INTEREST',
                'CHARGED MAINTENANCE FEE', 'DAMAGE ADJUSTING', 'PRICE ADJUSTMENT-OVER/UNDER',
                'PURCHASE ADJUSTING', 'RETURN ADJUSTING', 'SALE ADJUSTING', 'PAID BY CASH',
                'PAID BY CHEQUE', 'PAID TO ADVANCE & SECURITY', 'PAID TO BANK LOAN',
                'PAID TO DISCOUNT ON PRODUCTS', 'PAID TO EMPLOYEE SALARY', 'PAID TO FNF LOAN',
                'PAID TO INCENTIVE', 'PAID TO MISC. EXPENSES', 'PAID TO NGO LOAN',
                'PAID TO OWNER SALARY', 'PAID TO PERSON'
            ],
            'Receipt': [
                'DAMAGE ADJUSTING', 'PRICE ADJUSTMENT-OVER/UNDER', 'PURCHASE ADJUSTING',
                'RETURN ADJUSTING', 'SALE ADJUSTING', 'RECEIVED CAPITAL', 'RECEIVED CASH',
                'RECEIVED CHEQUE', 'RECEIVED DISCOUNT', 'RECEIVED DIVIDEND', 'RECEIVED FROM BANK',
                'RECEIVED FROM PERSON', 'RECEIVED FURNITURE', 'RECEIVED INCENTIVE',
                'RECEIVED INCOME', 'RECEIVED INTEREST', 'RECEIVED LOAN FROM BANK',
                'RECEIVED LOAN FROM FNF', 'RECEIVED LOAN FROM NGO'
            ],
            'Journal': [
                'Adjustment entry', 'Depreciation entry', 'Provision entry', 'Accrual entry',
                'Correction entry', 'Year-end closing entry', 'Other journal entry'
            ],
            'Contra': [
                'Cash deposit to bank', 'Cash withdrawal from bank', 'Fund transfer between accounts',
                'Other contra entry'
            ]
        };

        // Get ledger options HTML
        function getLedgerOptionsHtml() {
            let options = '<option value="">-- Select Ledger --</option>';
            ledgersData.forEach(function(ledger) {
                options += '<option value="' + ledger.id + '" data-type="' + ledger.type + '">' + ledger.name + ' (' + ledger.type + ')</option>';
            });
            return options;
        }

        // Add debit line
        function addDebitLine() {
            const container = document.getElementById('debit-lines-container');
            const lineHtml = '<div class="bg-green-50 p-4 rounded-lg mb-3 debit-line" data-index="' + debitLineIndex + '">' +
                '<div class="flex items-center justify-between mb-3">' +
                '<span class="text-sm font-medium text-green-700">Debit Line ' + (debitLineIndex + 1) + '</span>' +
                '<button type="button" class="remove-debit-line text-red-600 hover:text-red-800 text-sm">' +
                '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">' +
                '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>' +
                '</svg>' +
                '</button>' +
                '</div>' +
                '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">' +
                '<div>' +
                '<label class="block text-sm font-medium text-green-700 mb-2">Select Ledger</label>' +
                '<select name="lines[' + debitLineIndex + '][ledger_id]" class="ledger-select w-full rounded-lg border-green-300 focus:border-green-500 focus:ring-green-500">' +
                getLedgerOptionsHtml() +
                '</select>' +
                '</div>' +
                '<div>' +
                '<label class="block text-sm font-medium text-green-700 mb-2">Amount</label>' +
                '<input type="number" step="0.01" name="lines[' + debitLineIndex + '][debit_amount]" value="0" class="debit-amount w-full rounded-lg border-green-300 focus:border-green-500 focus:ring-green-500">' +
                '<input type="hidden" name="lines[' + debitLineIndex + '][credit_amount]" value="0">' +
                '</div>' +
                '</div>' +
                '</div>';

            container.insertAdjacentHTML('beforeend', lineHtml);

            // Initialize Select2 for the new line
            $(container).find('.ledger-select').last().select2({
                placeholder: 'Search and select ledger',
                allowClear: true,
                width: '100%'
            });

            debitLineIndex++;
            updateTotals();
        }

        // Add credit line
        function addCreditLine() {
            const container = document.getElementById('credit-lines-container');
            const lineHtml = '<div class="bg-red-50 p-4 rounded-lg mb-3 credit-line" data-index="' + creditLineIndex + '">' +
                '<div class="flex items-center justify-between mb-3">' +
                '<span class="text-sm font-medium text-red-700">Credit Line ' + (creditLineIndex - 999) + '</span>' +
                '<button type="button" class="remove-credit-line text-red-600 hover:text-red-800 text-sm">' +
                '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">' +
                '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>' +
                '</svg>' +
                '</button>' +
                '</div>' +
                '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">' +
                '<div>' +
                '<label class="block text-sm font-medium text-red-700 mb-2">Select Ledger</label>' +
                '<select name="lines[' + creditLineIndex + '][ledger_id]" class="ledger-select w-full rounded-lg border-red-300 focus:border-red-500 focus:ring-red-500">' +
                getLedgerOptionsHtml() +
                '</select>' +
                '</div>' +
                '<div>' +
                '<label class="block text-sm font-medium text-red-700 mb-2">Amount</label>' +
                '<input type="number" step="0.01" name="lines[' + creditLineIndex + '][credit_amount]" value="0" class="credit-amount w-full rounded-lg border-red-300 focus:border-red-500 focus:ring-red-500">' +
                '<input type="hidden" name="lines[' + creditLineIndex + '][debit_amount]" value="0">' +
                '</div>' +
                '</div>' +
                '</div>';

            container.insertAdjacentHTML('beforeend', lineHtml);

            // Initialize Select2 for the new line
            $(container).find('.ledger-select').last().select2({
                placeholder: 'Search and select ledger',
                allowClear: true,
                width: '100%'
            });

            creditLineIndex++;
            updateTotals();
        }

        // Update totals and balance check
        function updateTotals() {
            let totalDebit = 0;
            let totalCredit = 0;

            // Calculate debit total
            document.querySelectorAll('.debit-amount').forEach(input => {
                totalDebit += parseFloat(input.value) || 0;
            });

            // Calculate credit total
            document.querySelectorAll('.credit-amount').forEach(input => {
                totalCredit += parseFloat(input.value) || 0;
            });

            // Update display
            const totalDebitElement = document.getElementById('total-debit');
            const totalCreditElement = document.getElementById('total-credit');

            if (totalDebitElement) totalDebitElement.textContent = totalDebit.toFixed(2);
            if (totalCreditElement) totalCreditElement.textContent = totalCredit.toFixed(2);

            // Balance check
            const balanceStatus = document.getElementById('balance-status');
            if (balanceStatus) {
                const difference = Math.abs(totalDebit - totalCredit);

                if (difference < 0.01) {
                    balanceStatus.textContent = 'Balanced ✓';
                    balanceStatus.className = 'text-sm font-bold text-green-600';
                } else {
                    balanceStatus.textContent = 'Out of Balance: ' + difference.toFixed(2);
                    balanceStatus.className = 'text-sm font-bold text-red-600';
                }
            }
        }

        // Event listeners
        const addDebitBtn = document.getElementById('add-debit-line');
        const addCreditBtn = document.getElementById('add-credit-line');

        if (addDebitBtn) addDebitBtn.addEventListener('click', addDebitLine);
        if (addCreditBtn) addCreditBtn.addEventListener('click', addCreditLine);

        // Remove line event listeners (using event delegation)
        document.addEventListener('click', function(e) {
            if (e.target.closest('.remove-debit-line')) {
                e.target.closest('.debit-line').remove();
                updateTotals();
            }
            if (e.target.closest('.remove-credit-line')) {
                e.target.closest('.credit-line').remove();
                updateTotals();
            }
        });

        // Amount change listeners (using event delegation)
        document.addEventListener('input', function(e) {
            if (e.target.classList.contains('debit-amount') || e.target.classList.contains('credit-amount')) {
                updateTotals();
            }
        });

        // Function to filter ledger options for simple entries
        function filterLedgerOptions(transactionType) {
            if (!window.originalLedgers) {
                window.originalLedgers = {
                    debit: [],
                    credit: []
                };

                $('#debit_ledger_select option').each(function() {
                    if ($(this).val()) {
                        window.originalLedgers.debit.push({
                            value: $(this).val(),
                            text: $(this).text(),
                            type: $(this).data('type')
                        });
                    }
                });

                $('#credit_ledger_select option').each(function() {
                    if ($(this).val()) {
                        window.originalLedgers.credit.push({
                            value: $(this).val(),
                            text: $(this).text(),
                            type: $(this).data('type')
                        });
                    }
                });
            }

            const debitSelect = $('#debit_ledger_select');
            const creditSelect = $('#credit_ledger_select');

            debitSelect.find('option:not(:first)').remove();
            creditSelect.find('option:not(:first)').remove();

            debitSelect.val('').trigger('change');
            creditSelect.val('').trigger('change');

            switch (transactionType) {
                case 'Payment':
                    window.originalLedgers.debit.forEach(function(ledger) {
                        if (!cashBankTypes.includes(ledger.type)) {
                            debitSelect.append(new Option(ledger.text, ledger.value, false, false))
                                .find('option:last-child').data('type', ledger.type);
                        }
                    });

                    window.originalLedgers.credit.forEach(function(ledger) {
                        if (cashBankTypes.includes(ledger.type)) {
                            creditSelect.append(new Option(ledger.text, ledger.value, false, false))
                                .find('option:last-child').data('type', ledger.type);
                        }
                    });
                    break;

                case 'Receipt':
                    window.originalLedgers.debit.forEach(function(ledger) {
                        if (cashBankTypes.includes(ledger.type)) {
                            debitSelect.append(new Option(ledger.text, ledger.value, false, false))
                                .find('option:last-child').data('type', ledger.type);
                        }
                    });

                    window.originalLedgers.credit.forEach(function(ledger) {
                        if (!cashBankTypes.includes(ledger.type)) {
                            creditSelect.append(new Option(ledger.text, ledger.value, false, false))
                                .find('option:last-child').data('type', ledger.type);
                        }
                    });
                    break;

                case 'Contra':
                    window.originalLedgers.debit.forEach(function(ledger) {
                        if (cashBankTypes.includes(ledger.type)) {
                            debitSelect.append(new Option(ledger.text, ledger.value, false, false))
                                .find('option:last-child').data('type', ledger.type);
                        }
                    });

                    window.originalLedgers.credit.forEach(function(ledger) {
                        if (cashBankTypes.includes(ledger.type)) {
                            creditSelect.append(new Option(ledger.text, ledger.value, false, false))
                                .find('option:last-child').data('type', ledger.type);
                        }
                    });
                    break;

                default:
                    window.originalLedgers.debit.forEach(function(ledger) {
                        debitSelect.append(new Option(ledger.text, ledger.value, false, false))
                            .find('option:last-child').data('type', ledger.type);
                    });

                    window.originalLedgers.credit.forEach(function(ledger) {
                        creditSelect.append(new Option(ledger.text, ledger.value, false, false))
                            .find('option:last-child').data('type', ledger.type);
                    });
                    break;
            }

            debitSelect.trigger('change');
            creditSelect.trigger('change');
        }

        // Function to update narration field
        function updateNarrationField(transactionType) {
            const narrationSelect = $('#narration_select');
            const narrationTextarea = $('#narration_textarea');

            narrationSelect.empty().append('<option value="">-- Select Narration --</option>');

            if (transactionType && narrationOptions[transactionType]) {
                narrationOptions[transactionType].forEach(function(option) {
                    narrationSelect.append('<option value="' + option + '">' + option + '</option>');
                });

                narrationSelect.removeClass('hidden');
                narrationTextarea.addClass('hidden');
                narrationTextarea.attr('name', 'narration_text');
                narrationSelect.attr('name', 'narration');
            } else {
                narrationSelect.addClass('hidden');
                narrationTextarea.removeClass('hidden');
                narrationTextarea.attr('name', 'narration');
                narrationSelect.attr('name', 'narration_select');
            }
        }

        // Transaction type change handler
        $('#transaction_type').on('change', function() {
            const transactionType = $(this).val();

            if (transactionType === 'Journal') {
                // Show journal entry section
                document.getElementById('simple-entry').classList.add('hidden');
                document.getElementById('journal-entry').classList.remove('hidden');

                // Add initial lines if none exist
                if (document.querySelectorAll('.debit-line').length === 0) {
                    addDebitLine();
                }
                if (document.querySelectorAll('.credit-line').length === 0) {
                    addCreditLine();
                }
            } else {
                // Show simple entry section
                document.getElementById('journal-entry').classList.add('hidden');
                document.getElementById('simple-entry').classList.remove('hidden');

                // Filter ledger options for simple entries
                filterLedgerOptions(transactionType);
            }

            updateNarrationField(transactionType);
        });

        // Custom narration input when selecting "Other" option
        $('#narration_select').on('change', function() {
            const selectedNarration = $(this).val();
            if (selectedNarration && selectedNarration.includes('Other')) {
                $('#narration_textarea').removeClass('hidden').attr('name', 'narration');
                $(this).attr('name', 'narration_select');
            }
        });

        // Form submission handler with proper validation
        $('form').on('submit', function(e) {
            e.preventDefault(); // Always prevent default first

            const transactionType = $('#transaction_type').val();
            let isValid = true;
            let errorMessages = [];

            // Clear previous validation states
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();

            // Validate transaction type
            if (!transactionType) {
                isValid = false;
                errorMessages.push('Please select a transaction type.');
                $('#transaction_type').addClass('is-invalid');
            }

            if (transactionType === 'Journal') {
                // Validate journal entries
                const debitLines = document.querySelectorAll('.debit-line');
                const creditLines = document.querySelectorAll('.credit-line');

                if (debitLines.length === 0 || creditLines.length === 0) {
                    isValid = false;
                    errorMessages.push('Journal entries must have at least one debit line and one credit line.');
                }

                // Check if balanced
                let totalDebit = 0;
                let totalCredit = 0;
                let hasValidDebit = false;
                let hasValidCredit = false;

                // Validate debit lines
                debitLines.forEach(line => {
                    const ledgerSelect = line.querySelector('select[name*="[ledger_id]"]');
                    const amountInput = line.querySelector('.debit-amount');
                    const amount = parseFloat(amountInput.value) || 0;

                    if (amount > 0) {
                        if (!ledgerSelect.value) {
                            isValid = false;
                            errorMessages.push('Please select a ledger for all debit lines with amounts.');
                        } else {
                            hasValidDebit = true;
                            totalDebit += amount;
                        }
                    }
                });

                // Validate credit lines
                creditLines.forEach(line => {
                    const ledgerSelect = line.querySelector('select[name*="[ledger_id]"]');
                    const amountInput = line.querySelector('.credit-amount');
                    const amount = parseFloat(amountInput.value) || 0;

                    if (amount > 0) {
                        if (!ledgerSelect.value) {
                            isValid = false;
                            errorMessages.push('Please select a ledger for all credit lines with amounts.');
                        } else {
                            hasValidCredit = true;
                            totalCredit += amount;
                        }
                    }
                });

                if (!hasValidDebit || !hasValidCredit) {
                    isValid = false;
                    errorMessages.push('Journal entries must have at least one debit and one credit line with amounts greater than 0.');
                }

                if (Math.abs(totalDebit - totalCredit) > 0.01) {
                    isValid = false;
                    errorMessages.push('Debit and Credit totals must be equal for journal entries.');
                }
            } else {
                // Simple entry validation
                const debitSelect = $('#debit_ledger_select');
                const creditSelect = $('#credit_ledger_select');
                const debitAmount = $('input[name="lines[0][debit_amount]"]');
                const creditAmount = $('input[name="lines[1][credit_amount]"]');

                // Validate debit ledger
                if (!debitSelect.val()) {
                    isValid = false;
                    errorMessages.push('Please select a debit ledger.');
                    debitSelect.next('.select2-container').addClass('is-invalid');
                    debitSelect.next('.select2-container').after('<div class="invalid-feedback d-block">Please select a debit ledger.</div>');
                }

                // Validate credit ledger
                if (!creditSelect.val()) {
                    isValid = false;
                    errorMessages.push('Please select a credit ledger.');
                    creditSelect.next('.select2-container').addClass('is-invalid');
                    creditSelect.next('.select2-container').after('<div class="invalid-feedback d-block">Please select a credit ledger.</div>');
                }

                // Validate amounts
                if (!debitAmount.val() || parseFloat(debitAmount.val()) <= 0) {
                    isValid = false;
                    errorMessages.push('Please enter a valid amount greater than 0.');
                    debitAmount.addClass('is-invalid');
                }
            }

            if (!isValid) {
                // Show error messages
                if (errorMessages.length > 0) {
                    alert(errorMessages.join('\n'));
                }

                // Focus on first invalid element
                const firstInvalid = $('.is-invalid, .select2-container.is-invalid').first();
                if (firstInvalid.hasClass('select2-container')) {
                    firstInvalid.find('.select2-selection').focus();
                } else {
                    firstInvalid.focus();
                }

                return false;
            }

            // If validation passes, submit the form
            showLoadingState();
            this.submit();
        });

        function showLoadingState() {
            const button = document.getElementById('submitButton');
            const defaultIcon = document.getElementById('defaultIcon');
            const spinnerIcon = document.getElementById('spinnerIcon');
            const buttonText = document.getElementById('buttonText');

            if (defaultIcon) defaultIcon.classList.add('hidden');
            if (spinnerIcon) spinnerIcon.classList.remove('hidden');
            if (buttonText) buttonText.textContent = 'Saving...';
            if (button) button.disabled = true;
        }

        // Amount synchronization for simple entries (Payment, Receipt, Contra)
        const debitAmountInput = document.querySelector('input[name="lines[0][debit_amount]"]');
        const creditAmountInput = document.querySelector('input[name="lines[1][credit_amount]"]');

        if (debitAmountInput && creditAmountInput) {
            debitAmountInput.addEventListener('input', function() {
                const debitValue = parseFloat(this.value) || 0;
                creditAmountInput.value = debitValue.toFixed(2);
            });

            creditAmountInput.addEventListener('input', function() {
                const creditValue = parseFloat(this.value) || 0;
                debitAmountInput.value = creditValue.toFixed(2);
            });
        }
    });
</script>
@endpush
@endsection