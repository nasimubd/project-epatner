@extends('admin.layouts.app')

@section('content')
<div class="container mx-auto px-2 sm:px-4 py-4 sm:py-6 h-screen overflow-y-auto">
    <div class="bg-white shadow-lg rounded-xl overflow-hidden">
        <!-- Header Section -->
        <div class="sticky top-0 z-10 flex flex-col sm:flex-row justify-between items-center bg-gradient-to-r from-blue-600 to-blue-700 text-white p-4 sm:px-6">
            <h1 class="text-xl sm:text-2xl font-bold mb-4 sm:mb-0">Deposit Slips</h1>
            <a href="{{ route('admin.accounting.deposit.create') }}"
                class="w-full sm:w-auto bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 flex items-center justify-center space-x-2">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" />
                </svg>
                <span>New Deposit</span>
            </a>
        </div>

        @if(session('success'))
        <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
            {{ session('success') }}
        </div>
        @endif

        <!-- Mobile Card Layout -->
        <div class="space-y-4 md:hidden px-4 py-4">
            @forelse($deposits as $deposit)
            <div class="bg-white shadow-md rounded-lg p-4 border border-gray-100 hover:shadow-lg transition duration-300">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm font-semibold text-blue-600">{{ $deposit->user->name }}</span>
                    <span class="text-xs text-gray-500">৳{{ number_format($deposit->total_amount) }}</span>
                </div>
                <div class="flex justify-between items-center mb-3">
                    <div>
                        <p class="text-lg font-bold text-gray-800">৳{{ number_format($deposit->net_total) }}</p>
                        <p class="text-xs text-gray-500">Net Total</p>
                    </div>
                    <div>
                        @role('admin')
                        <form action="{{ route('admin.accounting.deposit.status', $deposit) }}" method="POST" class="inline">
                            @csrf
                            @method('PATCH')
                            <select name="status" onchange="this.form.submit()"
                                class="rounded-md border-gray-300 text-sm {{ 
                                    $deposit->status === 'pending' ? 'bg-yellow-50 text-yellow-700' : 
                                    ($deposit->status === 'approved' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700') 
                                }}">
                                <option value="pending" {{ $deposit->status === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="approved" {{ $deposit->status === 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="rejected" {{ $deposit->status === 'rejected' ? 'selected' : '' }}>Rejected</option>
                            </select>
                        </form>
                        @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ 
                            $deposit->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                            ($deposit->status === 'approved' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') 
                        }}">
                            {{ ucfirst($deposit->status) }}
                        </span>
                        @endrole
                    </div>
                </div>
                <div class="flex justify-end">
                    <div class="flex justify-end space-x-2">
                        <a href="{{ route('admin.accounting.deposit.show', $deposit->id) }}"
                            class="text-blue-500 hover:text-blue-700">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </a>
                        <form action="{{ route('admin.accounting.deposit.destroy', $deposit->id) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-700" onclick="return confirm('Are you sure you want to delete this deposit slip and its accounting entries?')">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center text-gray-500 py-4">
                No deposit slips found
            </div>
            @endforelse
        </div>

        <!-- Desktop Table Layout -->
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Staff</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Amount</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Net Total</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($deposits as $deposit)
                    <tr class="hover:bg-gray-50 transition duration-150">
                        <td class="px-3 py-3 text-sm text-gray-500">{{ $deposit->user->name }}</td>
                        <td class="px-3 py-3 text-sm font-medium text-gray-900">৳{{ number_format($deposit->total_amount) }}</td>
                        <td class="px-3 py-3 text-sm text-gray-900">৳{{ number_format($deposit->net_total) }}</td>
                        <td class="px-3 py-3">
                            @role('admin')
                            <form action="{{ route('admin.accounting.deposit.status', $deposit) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <select name="status" onchange="this.form.submit()"
                                    class="rounded-md border-gray-300 text-sm {{ 
                                        $deposit->status === 'pending' ? 'bg-yellow-50 text-yellow-700' : 
                                        ($deposit->status === 'approved' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700') 
                                    }}">
                                    <option value="pending" {{ $deposit->status === 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="approved" {{ $deposit->status === 'approved' ? 'selected' : '' }}>Approved</option>
                                    <option value="rejected" {{ $deposit->status === 'rejected' ? 'selected' : '' }}>Rejected</option>
                                </select>
                            </form>
                            @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ 
                                $deposit->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                ($deposit->status === 'approved' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') 
                            }}">
                                {{ ucfirst($deposit->status) }}
                            </span>
                            @endrole
                        </td>
                        <td class="px-3 py-3">
                            <!-- In the desktop table actions column -->
                            <div class="flex justify-center space-x-1">
                                <a href="{{ route('admin.accounting.deposit.show', $deposit->id) }}"
                                    class="text-blue-500 hover:text-blue-700 p-1">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </a>
                                <form action="{{ route('admin.accounting.deposit.destroy', $deposit->id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 p-1" onclick="return confirm('Are you sure you want to delete this deposit slip and its accounting entries?')">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-3 py-8 text-center text-gray-500">
                            <div class="flex flex-col items-center">
                                <svg class="h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span class="font-medium">No deposit slips found</span>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($deposits->hasPages())
        <div class="px-3 py-3 bg-gray-50 border-t">
            {{ $deposits->links() }}
        </div>
        @endif
    </div>
</div>
@endsection