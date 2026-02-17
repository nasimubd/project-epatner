@extends('admin.layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <h1 class="text-xl font-bold text-gray-800">Transaction #{{ $transaction->id }}</h1>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.accounting.transactions.print', $transaction->id) }}" target="_blank"
                    class="inline-flex items-center px-3 py-2 text-sm bg-green-500 hover:bg-green-600 text-white rounded">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                    </svg>
                    Print Voucher
                </a>
                <a href="{{ route('admin.accounting.transactions.index') }}"
                    class="inline-flex items-center px-3 py-2 text-sm bg-gray-500 hover:bg-gray-600 text-white rounded">
                    Back
                </a>
            </div>
        </div>

        <!-- Transaction Details Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
            <div class="bg-gray-50 p-3 rounded text-sm">
                <div class="flex justify-between">
                    <span class="font-medium">Business ID:</span>
                    <span>{{ $transaction->business_id }}</span>
                </div>
                <div class="flex justify-between mt-2">
                    <span class="font-medium">Type:</span>
                    <span class="px-2 bg-blue-100 text-blue-800 rounded">{{ $transaction->transaction_type }}</span>
                </div>
            </div>
            <div class="bg-gray-50 p-3 rounded text-sm">
                <div class="flex justify-between">
                    <span class="font-medium">Date:</span>
                    <span>{{ $transaction->transaction_date }}</span>
                </div>
                <div class="flex justify-between mt-2">
                    <span class="font-medium">Amount:</span>
                    <span class="text-green-600 font-bold">{{ $transaction->amount }}</span>
                </div>
            </div>
        </div>

        <!-- Narration -->
        <div class="mb-6">
            <h3 class="text-sm font-medium mb-2">Narration</h3>
            <div class="bg-gray-50 p-3 rounded text-sm">{{ $transaction->narration }}</div>
        </div>

        <!-- Transaction Lines -->
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="p-2 text-left text-xs font-medium text-gray-500">Ledger</th>
                        <th class="p-2 text-right text-xs font-medium text-gray-500">Debit</th>
                        <th class="p-2 text-right text-xs font-medium text-gray-500">Credit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($transaction->transactionLines as $line)
                    <tr class="hover:bg-gray-50">
                        <td class="p-2">{{ optional($line->ledger)->name }}</td>
                        <td class="p-2 text-right text-green-600">{{ $line->debit_amount ?: '-' }}</td>
                        <td class="p-2 text-right text-red-600">{{ $line->credit_amount ?: '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection