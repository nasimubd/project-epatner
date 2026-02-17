@extends('super-admin.layouts.app')

@section('content')
<div class="bg-white shadow-md rounded-lg overflow-hidden">
    <div class="p-6 bg-white border-b border-gray-200">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Create New Default Ledger</h2>
            <p class="text-gray-600 mt-1">Add a new default ledger to the system</p>
        </div>

        <form action="{{ route('super-admin.default-ledgers.store') }}" method="POST">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Ledger Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-500 @enderror"
                        required>
                    @error('name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="ledger_type" class="block text-sm font-medium text-gray-700 mb-1">Ledger Type</label>
                    <select name="ledger_type" id="ledger_type"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('ledger_type') border-red-500 @enderror"
                        required>
                        <option value="">-- Select Type --</option>
                        <option value="Bank Accounts" {{ old('ledger_type') == 'Bank Accounts' ? 'selected' : '' }}>Bank Accounts</option>
                        <option value="Bank OD A/c" {{ old('ledger_type') == 'Bank OD A/c' ? 'selected' : '' }}>Bank OD A/c</option>
                        <option value="Capital Accounts" {{ old('ledger_type') == 'Capital Accounts' ? 'selected' : '' }}>Capital Accounts</option>
                        <option value="Cash-in-Hand" {{ old('ledger_type') == 'Cash-in-Hand' ? 'selected' : '' }}>Cash-in-Hand</option>
                        <option value="Duties & Taxes" {{ old('ledger_type') == 'Duties & Taxes' ? 'selected' : '' }}>Duties & Taxes</option>
                        <option value="Expenses" {{ old('ledger_type') == 'Expenses' ? 'selected' : '' }}>Expenses</option>
                        <option value="Fixed Assets" {{ old('ledger_type') == 'Fixed Assets' ? 'selected' : '' }}>Fixed Assets</option>
                        <option value="Incomes" {{ old('ledger_type') == 'Incomes' ? 'selected' : '' }}>Incomes</option>
                        <option value="Investments" {{ old('ledger_type') == 'Investments' ? 'selected' : '' }}>Investments</option>
                        <option value="Loans & Advances (Asset)" {{ old('ledger_type') == 'Loans & Advances (Asset)' ? 'selected' : '' }}>Loans & Advances (Asset)</option>
                        <option value="Loans A/c" {{ old('ledger_type') == 'Loans A/c' ? 'selected' : '' }}>Loans A/c</option>
                        <option value="Purchase Accounts" {{ old('ledger_type') == 'Purchase Accounts' ? 'selected' : '' }}>Purchase Accounts</option>
                        <option value="Sales Accounts" {{ old('ledger_type') == 'Sales Accounts' ? 'selected' : '' }}>Sales Accounts</option>
                        <option value="Sundry Creditors (Supplier)" {{ old('ledger_type') == 'Sundry Creditors (Supplier)' ? 'selected' : '' }}>Sundry Creditors (Supplier)</option>
                    </select>
                    @error('ledger_type')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="contact_number" class="block text-sm font-medium text-gray-700 mb-1">Contact Number</label>
                    <input type="text" name="contact_number" id="contact_number" value="{{ old('contact_number') }}"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('contact_number') border-red-500 @enderror">
                    @error('contact_number')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="location" class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                    <input type="text" name="location" id="location" value="{{ old('location') }}"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('location') border-red-500 @enderror">
                    @error('location')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex items-center justify-end space-x-3">
                <a href="{{ route('super-admin.default-ledgers.index') }}" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition">
                    Cancel
                </a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                    Create Ledger
                </button>
            </div>
        </form>
    </div>
</div>
@endsection