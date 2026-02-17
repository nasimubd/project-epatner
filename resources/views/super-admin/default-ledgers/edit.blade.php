@extends('super-admin.layouts.app')

@section('content')
<div class="bg-white shadow-md rounded-lg overflow-hidden">
    <div class="p-6 bg-white border-b border-gray-200">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Edit Default Ledger</h2>
            <p class="text-gray-600 mt-1">Update the default ledger information</p>
        </div>

        <form action="{{ route('super-admin.default-ledgers.update', $defaultLedger) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="ledger_name" class="block text-sm font-medium text-gray-700 mb-1">Ledger Name</label>
                    <input type="text" name="ledger_name" id="ledger_name" value="{{ old('ledger_name', $defaultLedger->ledger_name) }}"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('ledger_name') border-red-500 @enderror"
                        required>
                    @error('ledger_name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Ledger Type</label>
                    <select name="type" id="type"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('type') border-red-500 @enderror"
                        required>
                        <option value="">-- Select Type --</option>
                        <option value="Bank Accounts" {{ old('type', $defaultLedger->type) == 'Bank Accounts' ? 'selected' : '' }}>Bank Accounts</option>
                        <option value="Bank OD A/c" {{ old('type', $defaultLedger->type) == 'Bank OD A/c' ? 'selected' : '' }}>Bank OD A/c</option>
                        <option value="Capital Accounts" {{ old('type', $defaultLedger->type) == 'Capital Accounts' ? 'selected' : '' }}>Capital Accounts</option>
                        <option value="Cash-in-Hand" {{ old('type', $defaultLedger->type) == 'Cash-in-Hand' ? 'selected' : '' }}>Cash-in-Hand</option>
                        <option value="Duties & Taxes" {{ old('type', $defaultLedger->type) == 'Duties & Taxes' ? 'selected' : '' }}>Duties & Taxes</option>
                        <option value="Expenses" {{ old('type', $defaultLedger->type) == 'Expenses' ? 'selected' : '' }}>Expenses</option>
                        <option value="Fixed Assets" {{ old('type', $defaultLedger->type) == 'Fixed Assets' ? 'selected' : '' }}>Fixed Assets</option>
                        <option value="Incomes" {{ old('type', $defaultLedger->type) == 'Incomes' ? 'selected' : '' }}>Incomes</option>
                        <option value="Investments" {{ old('type', $defaultLedger->type) == 'Investments' ? 'selected' : '' }}>Investments</option>
                        <option value="Loans & Advances (Asset)" {{ old('type', $defaultLedger->type) == 'Loans & Advances (Asset)' ? 'selected' : '' }}>Loans & Advances (Asset)</option>
                        <option value="Loans A/c" {{ old('type', $defaultLedger->type) == 'Loans A/c' ? 'selected' : '' }}>Loans A/c</option>
                        <option value="Purchase Accounts" {{ old('type', $defaultLedger->type) == 'Purchase Accounts' ? 'selected' : '' }}>Purchase Accounts</option>
                        <option value="Sales Accounts" {{ old('type', $defaultLedger->type) == 'Sales Accounts' ? 'selected' : '' }}>Sales Accounts</option>
                        <option value="Sundry Creditors (Supplier)" {{ old('type', $defaultLedger->type) == 'Sundry Creditors (Supplier)' ? 'selected' : '' }}>Sundry Creditors (Supplier)</option>
                    </select>
                    @error('type')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="contact_number" class="block text-sm font-medium text-gray-700 mb-1">Contact Number</label>
                    <input type="text" name="contact_number" id="contact_number" value="{{ old('contact_number', $defaultLedger->contact_number) }}"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('contact_number') border-red-500 @enderror">
                    @error('contact_number')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="location" class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                    <input type="text" name="location" id="location" value="{{ old('location', $defaultLedger->location) }}"
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
                    Update Ledger
                </button>
            </div>
        </form>
    </div>
</div>
@endsection