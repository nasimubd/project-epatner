@extends('super-admin.layouts.app')

@section('content')
<div class="bg-white shadow-md rounded-lg overflow-hidden">
    <div class="p-6 bg-white border-b border-gray-200">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Default Ledgers</h2>
            <a href="{{ route('super-admin.default-ledgers.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                Add New Default Ledger
            </a>
        </div>

        <!-- Search and Filter Form -->
        <div class="mb-6">
            <form action="{{ route('super-admin.default-ledgers.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search ledgers..."
                        class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <select name="ledger_type" class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Types</option>
                        <option value="asset" {{ request('ledger_type') == 'asset' ? 'selected' : '' }}>Asset</option>
                        <option value="liability" {{ request('ledger_type') == 'liability' ? 'selected' : '' }}>Liability</option>
                        <option value="equity" {{ request('ledger_type') == 'equity' ? 'selected' : '' }}>Equity</option>
                        <option value="income" {{ request('ledger_type') == 'income' ? 'selected' : '' }}>Income</option>
                        <option value="expense" {{ request('ledger_type') == 'expense' ? 'selected' : '' }}>Expense</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 transition">
                        Filter
                    </button>
                    @if(request()->hasAny(['search', 'ledger_type']))
                    <a href="{{ route('super-admin.default-ledgers.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition">
                        Clear
                    </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Ledgers Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ledger Name</th>
                        <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ledger Type</th>
                        <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Default Balance</th>
                        <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($defaultLedgers as $ledger)
                    <tr class="hover:bg-gray-50">
                        <td class="py-4 px-6 text-sm text-gray-500">{{ $ledger->id }}</td>
                        <td class="py-4 px-6 text-sm font-medium text-gray-900">{{ $ledger->name }}</td>
                        <td class="py-4 px-6 text-sm text-gray-500">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                {{ $ledger->ledger_type == 'asset' ? 'bg-green-100 text-green-800' : 
                                   ($ledger->ledger_type == 'liability' ? 'bg-red-100 text-red-800' : 
                                   ($ledger->ledger_type == 'equity' ? 'bg-blue-100 text-blue-800' : 
                                   ($ledger->ledger_type == 'income' ? 'bg-purple-100 text-purple-800' : 
                                   'bg-yellow-100 text-yellow-800'))) }}">
                                {{ ucfirst($ledger->ledger_type) }}
                            </span>
                        </td>
                        <td class="py-4 px-6 text-sm text-gray-500">{{ number_format($ledger->opening_balance, 2) }}</td>
                        <td class="py-4 px-6 text-sm font-medium">
                            <div class="flex space-x-2">
                                <a href="{{ route('super-admin.default-ledgers.show', $ledger) }}" class="text-indigo-600 hover:text-indigo-900">View</a>
                                <a href="{{ route('super-admin.default-ledgers.edit', $ledger) }}" class="text-blue-600 hover:text-blue-900">Edit</a>
                                <form action="{{ route('super-admin.default-ledgers.destroy', $ledger) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this ledger?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-4 px-6 text-sm text-center text-gray-500">No default ledgers found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-4">
            {{ $defaultLedgers->links() }}
        </div>
    </div>
</div>
@endsection