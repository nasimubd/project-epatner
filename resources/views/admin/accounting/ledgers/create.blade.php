@extends('admin.layouts.app')

@section('content')
<div class="py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl mx-auto">
        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-2xl font-bold text-gray-800">Create New Ledger</h2>
            </div>

            <form action="{{ route('admin.accounting.ledgers.store') }}" method="POST" class="p-6 space-y-6">
                @csrf

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Ledger Name</label>
                        <input type="text" name="name" id="name" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label for="ledger_type" class="block text-sm font-medium text-gray-700">Ledger Type</label>
                        <select name="ledger_type" id="ledger_type" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">-- Select Type --</option>
                            <option value="History Accounts">History Accounts</option>
                            <option value="Bank Accounts">Bank Accounts</option>
                            <option value="Bank OD A/c">Bank OD A/c</option>
                            <option value="Capital Accounts">Capital Accounts</option>
                            <option value="Cash-in-Hand">Cash-in-Hand</option>
                            <option value="Duties & Taxes">Duties & Taxes</option>
                            <option value="Expenses">Expenses</option>
                            <option value="Fixed Assets">Fixed Assets</option>
                            <option value="Inventory Loss">Inventory Loss</option>
                            <option value="Incomes">Incomes</option>
                            <option value="Investments">Investments</option>
                            <option value="Loans & Advances (Asset)">Loans & Advances (Asset)</option>
                            <option value="Loans A/c">Loans A/c</option>
                            <option value="Purchase Accounts">Purchase Accounts</option>
                            <option value="Salary Payable">Salary Payable</option>
                            <option value="Sales Accounts">Sales Accounts</option>
                            <option value="Sundry Debtors (Customer)">Sundry Debtors (Customer)</option>
                            <option value="Sundry Creditors (Supplier)">Sundry Creditors (Supplier)</option>
                        </select>
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                        <select name="status" id="status" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">-- Select Status --</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="default">Default</option>
                        </select>
                        @error('status')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="contact" class="block text-sm font-medium text-gray-700">Contact (Optional)</label>
                        <input type="text" name="contact" id="contact"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label for="location" class="block text-sm font-medium text-gray-700">Location (Optional)</label>
                        <input type="text" name="location" id="location"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>

                <div class="flex justify-between items-center pt-4">
                    <a href="{{ route('admin.accounting.ledgers.index') }}" id="backButton"
                        class="flex items-center text-indigo-600 hover:text-indigo-700 font-medium transition duration-300 ease-in-out transform hover:scale-105 group">
                        <svg id="backIcon" class="w-5 h-5 mr-2 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        <span id="backButtonText">Back to Ledger List</span>
                    </a>

                    <button type="submit" id="saveLedgerBtn"
                        class="bg-gradient-to-r from-indigo-500 to-indigo-600 hover:from-indigo-600 hover:to-indigo-700 text-white font-bold py-2.5 px-6 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 shadow-md flex items-center group">
                        <span class="inline-flex items-center">
                            <svg id="saveIcon" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <svg id="saveSpinnerIcon" class="hidden w-5 h-5 mr-2 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span id="saveButtonText">Save Ledger</span>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        $('#backButton').on('click', function() {
            const backIcon = document.getElementById('backIcon');
            const backButtonText = document.getElementById('backButtonText');
            backButtonText.textContent = 'Going back...';
        });

        $('#saveLedgerBtn').on('click', function() {
            const saveIcon = document.getElementById('saveIcon');
            const spinnerIcon = document.getElementById('saveSpinnerIcon');
            const buttonText = document.getElementById('saveButtonText');

            saveIcon.classList.add('hidden');
            spinnerIcon.classList.remove('hidden');
            buttonText.textContent = 'Saving...';
        });
    });
</script>
@endpush
@endsection