@extends('admin.layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-4xl mx-auto bg-white shadow-lg rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-2xl font-bold text-gray-800">Edit Transaction #{{ $transaction->id }}</h2>
        </div>

        <form action="{{ route('admin.accounting.transactions.update', $transaction->id) }}" method="POST" class="p-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Transaction Type</label>
                    <select name="transaction_type" id="transaction_type" required class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                        <option value="Payment" {{ $transaction->transaction_type == 'Payment' ? 'selected' : '' }}>Payment</option>
                        <option value="Receipt" {{ $transaction->transaction_type == 'Receipt' ? 'selected' : '' }}>Receipt</option>
                        <option value="Journal" {{ $transaction->transaction_type == 'Journal' ? 'selected' : '' }}>Journal</option>
                        <option value="Contra" {{ $transaction->transaction_type == 'Contra' ? 'selected' : '' }}>Contra</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Transaction Date</label>
                    <input type="date" name="transaction_date" required value="{{ $transaction->transaction_date }}"
                        class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>

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
                        <select name="lines[0][ledger_id]" id="debit_ledger_select" required class="ledger-select w-full rounded-lg border-green-300 focus:border-green-500 focus:ring-green-500">
                            <option value="">-- Select Ledger --</option>
                            @foreach($ledgers as $ledger)
                            <option value="{{ $ledger->id }}" data-type="{{ $ledger->ledger_type }}" {{ $transaction->transactionLines[0]->ledger_id == $ledger->id ? 'selected' : '' }}>
                                {{ $ledger->name }} ({{ $ledger->ledger_type }})
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-green-700 mb-2">Amount</label>
                        <input type="number" step="0.01" name="lines[0][debit_amount]"
                            value="{{ $transaction->transactionLines[0]->debit_amount }}"
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
                        <select name="lines[1][ledger_id]" id="credit_ledger_select" required class="ledger-select w-full rounded-lg border-red-300 focus:border-red-500 focus:ring-red-500">
                            <option value="">-- Select Ledger --</option>
                            @foreach($ledgers as $ledger)
                            <option value="{{ $ledger->id }}" data-type="{{ $ledger->ledger_type }}" {{ $transaction->transactionLines[1]->ledger_id == $ledger->id ? 'selected' : '' }}>
                                {{ $ledger->name }} ({{ $ledger->ledger_type }})
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-red-700 mb-2">Amount</label>
                        <input type="number" step="0.01" name="lines[1][credit_amount]"
                            value="{{ $transaction->transactionLines[1]->credit_amount }}"
                            class="w-full rounded-lg border-red-300 focus:border-red-500 focus:ring-red-500">
                    </div>
                </div>
            </div>

            <!-- Narration Field -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Transaction Narration</label>
                <div id="narration-container">
                    <select name="narration" id="narration_select" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 hidden">
                        <option value="">-- Select Narration --</option>
                    </select>
                    <textarea
                        name="narration"
                        id="narration_textarea"
                        rows="3"
                        class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Enter transaction details or notes">{{ $transaction->narration }}</textarea>
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
                        <span id="buttonText">Update Transaction</span>
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Select2 initialization
        $('.ledger-select').select2({
            placeholder: 'Search and select ledger',
            allowClear: true,
            width: '100%'
        });

        // Cash/Bank ledger types
        const cashBankTypes = ['Bank Accounts', 'Bank OD A/c', 'Cash-in-Hand'];

        // Narration options by transaction type
        const narrationOptions = {
            'Payment': [
                'Payment for expenses',
                'Payment to supplier',
                'Payment for services',
                'Salary payment',
                'Advance payment',
                'Loan repayment',
                'Tax payment',
                'Utility payment',
                'Other payment'
            ],
            'Receipt': [
                'Receipt from customer',
                'Receipt for services',
                'Loan receipt',
                'Interest received',
                'Advance received',
                'Other receipt'
            ],
            'Journal': [
                'Adjustment entry',
                'Depreciation entry',
                'Provision entry',
                'Accrual entry',
                'Correction entry',
                'Year-end closing entry',
                'Other journal entry'
            ],
            'Contra': [
                'Cash deposit to bank',
                'Cash withdrawal from bank',
                'Fund transfer between accounts',
                'Other contra entry'
            ]
        };

        // Store current selected values
        const currentDebitLedgerId = $('#debit_ledger_select').val();
        const currentCreditLedgerId = $('#credit_ledger_select').val();

        // Function to filter ledger options
        function filterLedgerOptions(transactionType) {
            // Get original ledger data if not already stored
            if (!window.originalLedgers) {
                window.originalLedgers = {
                    debit: [],
                    credit: []
                };

                // Store original debit options
                $('#debit_ledger_select option').each(function() {
                    if ($(this).val()) { // Skip the placeholder option
                        window.originalLedgers.debit.push({
                            value: $(this).val(),
                            text: $(this).text(),
                            type: $(this).data('type'),
                            selected: $(this).is(':selected')
                        });
                    }
                });

                // Store original credit options
                $('#credit_ledger_select option').each(function() {
                    if ($(this).val()) { // Skip the placeholder option
                        window.originalLedgers.credit.push({
                            value: $(this).val(),
                            text: $(this).text(),
                            type: $(this).data('type'),
                            selected: $(this).is(':selected')
                        });
                    }
                });
            }

            // Clear both selects except the placeholder
            const debitSelect = $('#debit_ledger_select');
            const creditSelect = $('#credit_ledger_select');

            debitSelect.find('option:not(:first)').remove();
            creditSelect.find('option:not(:first)').remove();

            // Filter and add options based on transaction type
            switch (transactionType) {
                case 'Payment':
                    // Debit: All except Cash/Bank
                    window.originalLedgers.debit.forEach(function(ledger) {
                        if (!cashBankTypes.includes(ledger.type)) {
                            debitSelect.append(new Option(ledger.text, ledger.value, false, ledger.value === currentDebitLedgerId))
                                .find('option:last-child').data('type', ledger.type);
                        }
                    });

                    // Credit: Only Cash/Bank
                    window.originalLedgers.credit.forEach(function(ledger) {
                        if (cashBankTypes.includes(ledger.type)) {
                            creditSelect.append(new Option(ledger.text, ledger.value, false, ledger.value === currentCreditLedgerId))
                                .find('option:last-child').data('type', ledger.type);
                        }
                    });
                    break;

                case 'Receipt':
                    // Debit: Only Cash/Bank
                    window.originalLedgers.debit.forEach(function(ledger) {
                        if (cashBankTypes.includes(ledger.type)) {
                            debitSelect.append(new Option(ledger.text, ledger.value, false, ledger.value === currentDebitLedgerId))
                                .find('option:last-child').data('type', ledger.type);
                        }
                    });

                    // Credit: All except Cash/Bank
                    window.originalLedgers.credit.forEach(function(ledger) {
                        if (!cashBankTypes.includes(ledger.type)) {
                            creditSelect.append(new Option(ledger.text, ledger.value, false, ledger.value === currentCreditLedgerId))
                                .find('option:last-child').data('type', ledger.type);
                        }
                    });
                    break;

                case 'Contra':
                    // Both Debit and Credit: Only Cash/Bank
                    window.originalLedgers.debit.forEach(function(ledger) {
                        if (cashBankTypes.includes(ledger.type)) {
                            debitSelect.append(new Option(ledger.text, ledger.value, false, ledger.value === currentDebitLedgerId))
                                .find('option:last-child').data('type', ledger.type);
                        }
                    });

                    window.originalLedgers.credit.forEach(function(ledger) {
                        if (cashBankTypes.includes(ledger.type)) {
                            creditSelect.append(new Option(ledger.text, ledger.value, false, ledger.value === currentCreditLedgerId))
                                .find('option:last-child').data('type', ledger.type);
                        }
                    });
                    break;

                case 'Journal':
                case '':
                default:
                    // Both Debit and Credit: All ledgers
                    window.originalLedgers.debit.forEach(function(ledger) {
                        debitSelect.append(new Option(ledger.text, ledger.value, false, ledger.value === currentDebitLedgerId))
                            .find('option:last-child').data('type', ledger.type);
                    });

                    window.originalLedgers.credit.forEach(function(ledger) {
                        creditSelect.append(new Option(ledger.text, ledger.value, false, ledger.value === currentCreditLedgerId))
                            .find('option:last-child').data('type', ledger.type);
                    });
                    break;
            }

            // Refresh Select2 to apply changes
            debitSelect.trigger('change');
            creditSelect.trigger('change');
        }

        // Function to update narration field
        function updateNarrationField(transactionType) {
            const narrationSelect = $('#narration_select');
            const narrationTextarea = $('#narration_textarea');
            const currentNarration = narrationTextarea.val();

            // Clear existing options
            narrationSelect.empty().append('<option value="">-- Select Narration --</option>');

            if (transactionType && narrationOptions[transactionType]) {
                // Add options based on transaction type
                let foundMatch = false;

                narrationOptions[transactionType].forEach(function(option) {
                    const selected = option === currentNarration;
                    if (selected) foundMatch = true;
                    narrationSelect.append(`<option value="${option}" ${selected ? 'selected' : ''}>${option}</option>`);
                });

                // If current narration matches a predefined option, use select
                if (foundMatch) {
                    narrationSelect.removeClass('hidden');
                    narrationTextarea.addClass('hidden');
                    narrationTextarea.attr('name', 'narration_text');
                    narrationSelect.attr('name', 'narration');
                } else if (currentNarration) {
                    // If custom narration, add it as "Other" and keep textarea visible
                    narrationSelect.append(`<option value="Other" selected>Other</option>`);
                    narrationSelect.removeClass('hidden');
                    narrationTextarea.removeClass('hidden');
                    narrationTextarea.attr('name', 'narration');
                    narrationSelect.attr('name', 'narration_select');
                } else {
                    // No narration yet, show dropdown
                    narrationSelect.removeClass('hidden');
                    narrationTextarea.addClass('hidden');
                    narrationTextarea.attr('name', 'narration_text');
                    narrationSelect.attr('name', 'narration');
                }
            } else {
                // Show textarea, hide select
                narrationSelect.addClass('hidden');
                narrationTextarea.removeClass('hidden');
                narrationTextarea.attr('name', 'narration');
                narrationSelect.attr('name', 'narration_select');
            }
        }

        // Initialize form based on current transaction type
        const initialTransactionType = $('#transaction_type').val();
        filterLedgerOptions(initialTransactionType);
        updateNarrationField(initialTransactionType);

        // Transaction type change handler
        $('#transaction_type').on('change', function() {
            const transactionType = $(this).val();
            filterLedgerOptions(transactionType);
            updateNarrationField(transactionType);
        });

        // Custom narration input when selecting "Other" option
        $('#narration_select').on('change', function() {
            const selectedNarration = $(this).val();
            if (selectedNarration && selectedNarration.includes('Other')) {
                // Show textarea for custom narration
                $('#narration_textarea').removeClass('hidden').attr('name', 'narration');
                $(this).attr('name', 'narration_select');
            } else if (selectedNarration) {
                // Hide textarea and use selected narration
                $('#narration_textarea').addClass('hidden').attr('name', 'narration_text');
                $('#narration_textarea').val(selectedNarration);
                $(this).attr('name', 'narration');
            }
        });

        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function() {
                const button = document.getElementById('submitButton');
                const defaultIcon = document.getElementById('defaultIcon');
                const spinnerIcon = document.getElementById('spinnerIcon');
                const buttonText = document.getElementById('buttonText');

                defaultIcon.classList.add('hidden');
                spinnerIcon.classList.remove('hidden');
                buttonText.textContent = 'Updating...';
                button.disabled = true;
            });
        }
    });
</script>
@endpush
@endsection