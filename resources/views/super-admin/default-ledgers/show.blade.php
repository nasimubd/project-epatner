@extends('super-admin.layouts.app')

@section('content')
<div class="bg-white shadow-md rounded-lg overflow-hidden">
    <div class="p-6 bg-white border-b border-gray-200">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Default Ledger Details</h2>
            <div class="flex space-x-2">
                <a href="{{ route('super-admin.default-ledgers.edit', $defaultLedger) }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                    Edit
                </a>
                <a href="{{ route('super-admin.default-ledgers.index') }}" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition">
                    Back to List
                </a>
            </div>
        </div>

        <div class="bg-gray-50 p-4 rounded-lg">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Ledger ID</p>
                    <p class="text-lg font-semibold">{{ $defaultLedger->id }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Ledger Name</p>
                    <p class="text-lg font-semibold">{{ $defaultLedger->name }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Ledger Type</p>
                    <p class="text-lg font-semibold">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            {{ $defaultLedger->ledger_type == 'asset' ? 'bg-green-100 text-green-800' : 
                               ($defaultLedger->ledger_type == 'liability' ? 'bg-red-100 text-red-800' : 
                               ($defaultLedger->ledger_type == 'equity' ? 'bg-blue-100 text-blue-800' : 
                               ($defaultLedger->ledger_type == 'income' ? 'bg-purple-100 text-purple-800' : 
                               'bg-yellow-100 text-yellow-800'))) }}">
                            {{ ucfirst($defaultLedger->ledger_type) }}
                        </span>
                    </p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Opening Balance</p>
                    <p class="text-lg font-semibold">{{ number_format($defaultLedger->opening_balance, 2) }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Created At</p>
                    <p class="text-lg font-semibold">{{ $defaultLedger->created_at->format('M d, Y H:i A') }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Updated At</p>
                    <p class="text-lg font-semibold">{{ $defaultLedger->updated_at->format('M d, Y H:i A') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection