@extends('admin.layouts.app')

@section('content')
<div class="py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl mx-auto">
        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-2xl font-bold text-gray-800">Edit Ledger</h2>
            </div>

            <form action="{{ route('admin.accounting.ledgers.update', $ledger->id) }}" method="POST" class="p-6 space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Ledger Name</label>
                        <input type="text" name="name" id="name" value="{{ $ledger->name }}" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label for="ledger_type" class="block text-sm font-medium text-gray-700">Ledger Type</label>
                        <select name="ledger_type" id="ledger_type" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="Bank Accounts" {{ $ledger->ledger_type == "Bank Accounts" ? 'selected' : '' }}>Bank Accounts</option>
                            <option value="Bank OD A/c" {{ $ledger->ledger_type == "Bank OD A/c" ? 'selected' : '' }}>Bank OD A/c</option>
                            <option value="Capital Accounts" {{ $ledger->ledger_type == "Capital Accounts" ? 'selected' : '' }}>Capital Accounts</option>
                            <option value="Cash-in-Hand" {{ $ledger->ledger_type == "Cash-in-Hand" ? 'selected' : '' }}>Cash-in-Hand</option>
                            <option value="Duties & Taxes" {{ $ledger->ledger_type == "Duties & Taxes" ? 'selected' : '' }}>Duties & Taxes</option>
                            <option value="Expenses" {{ $ledger->ledger_type == "Expenses" ? 'selected' : '' }}>Expenses</option>
                            <option value="Fixed Assets" {{ $ledger->ledger_type == "Fixed Assets" ? 'selected' : '' }}>Fixed Assets</option>
                            <option value="Incomes" {{ $ledger->ledger_type == "Incomes" ? 'selected' : '' }}>Incomes</option>
                            <option value="Investments" {{ $ledger->ledger_type == "Investments" ? 'selected' : '' }}>Investments</option>
                            <option value="Loans & Advances (Asset)" {{ $ledger->ledger_type == "Loans & Advances (Asset)" ? 'selected' : '' }}>Loans & Advances (Asset)</option>
                            <option value="Loans A/c" {{ $ledger->ledger_type == "Loans A/c" ? 'selected' : '' }}>Loans A/c</option>
                            <option value="Purchase Accounts" {{ $ledger->ledger_type == "Purchase Accounts" ? 'selected' : '' }}>Purchase Accounts</option>
                            <option value="Stock-in-Hand" {{ $ledger->ledger_type == "Stock-in-Hand" ? 'selected' : '' }}>Stock-in-Hand</option>
                            <option value="Sales Accounts" {{ $ledger->ledger_type == "Sales Accounts" ? 'selected' : '' }}>Sales Accounts</option>
                            <option value="Sundry Debtors (Customer)" {{ $ledger->ledger_type == "Sundry Debtors (Customer)" ? 'selected' : '' }}>Sundry Debtors (Customer)</option>
                            <option value="Sundry Creditors (Supplier)" {{ $ledger->ledger_type == "Sundry Creditors (Supplier)" ? 'selected' : '' }}>Sundry Creditors (Supplier)</option>
                        </select>
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                        <select name="status" id="status" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">-- Select Status --</option>
                            <option value="active" {{ old('status', $ledger->status) == "active" ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $ledger->status) == "inactive" ? 'selected' : '' }}>Inactive</option>
                            <option value="default" {{ old('status', $ledger->status) == "default" ? 'selected' : '' }}>Default</option>
                        </select>
                        @error('status')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="contact" class="block text-sm font-medium text-gray-700">Contact (Optional)</label>
                        <input type="text" name="contact" id="contact" value="{{ $ledger->contact }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label for="location" class="block text-sm font-medium text-gray-700">Location (Optional)</label>
                        <input type="text" name="location" id="location" value="{{ $ledger->location }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>

                <div class="flex items-center justify-between pt-4">
                    <a href="{{ route('admin.accounting.ledgers.index') }}"
                        class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                        Back to Ledger List
                    </a>
                    <button type="submit"
                        class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Update Ledger
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection