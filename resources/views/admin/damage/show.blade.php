@extends('admin.layouts.app')

@section('content')
<div class="p-2 sm:p-4">
    <div class="max-w-3xl mx-auto bg-white rounded-lg shadow">
        <div class="flex items-center justify-between p-4 border-b">
            <h2 class="text-lg font-medium">Damage Transaction Details</h2>
            <div class="flex items-center space-x-2">
                <a href="{{ route('admin.damage.edit', $damage) }}" class="text-sm text-blue-600 hover:text-blue-800">Edit</a>
                <a href="{{ route('admin.damage.index') }}" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </a>
            </div>
        </div>

        <div class="p-4 space-y-6">
            <!-- Transaction Info -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Customer</h3>
                    <p class="mt-1">{{ $damage->customer->name }}</p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Transaction Date</h3>
                    <p class="mt-1">{{ $damage->transaction_date }}</p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Status</h3>
                    <p class="mt-1">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            {{ $damage->status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                            {{ ucfirst($damage->status) }}
                        </span>
                    </p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Created By</h3>
                    <p class="mt-1">{{ $damage->creator->name }}</p>
                </div>
            </div>

            <!-- Damage Items -->
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-3">Damaged Items</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Quantity</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Unit Price</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($damage->lines as $line)
                            <tr>
                                <td class="px-4 py-2 text-sm">{{ $line->product->name }}</td>
                                <td class="px-4 py-2 text-sm text-right">{{ number_format($line->quantity, 2) }}</td>
                                <td class="px-4 py-2 text-sm text-right">₹{{ number_format($line->unit_price, 2) }}</td>
                                <td class="px-4 py-2 text-sm text-right">₹{{ number_format($line->total_value, 2) }}</td>
                                <td class="px-4 py-2 text-sm">{{ $line->damage_reason }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="3" class="px-4 py-2 text-sm font-medium text-right">Total Value:</td>
                                <td class="px-4 py-2 text-sm font-medium text-right">₹{{ number_format($damage->lines->sum('total_value'), 2) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection