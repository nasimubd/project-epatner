@extends('admin.layouts.app')

@section('content')
@php
use App\Models\Staff;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\DB;

$user = Auth::user();
$hasProducts = false;
$userType = 'staff';

if ($user) {
if ($user->roles->contains('name', 'staff')) {
$staff = Staff::where('user_id', $user->id)->first();
if ($staff) {
$staffCategories = $staff->productCategories;
$hasProducts = $staffCategories->isNotEmpty();
}
} elseif ($user->roles->contains('name', 'admin')) {
$userType = 'admin';
$businessId = $user->businessAdmin->business_id;
$hasActiveCategories = ProductCategory::where('business_id', $businessId)
->where('status', true)
->exists();
$hasProducts = $hasActiveCategories;
}
}
@endphp

<div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 p-2 sm:p-4">
    <div class="max-w-7xl mx-auto">
        {{-- Enhanced Header --}}
        <div class="bg-white/80 backdrop-blur-sm shadow-xl rounded-2xl overflow-hidden border border-white/20 mb-4">
            <!-- Header Section -->
            <div class="bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-700 p-4 sm:p-6">
                <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div class="text-center sm:text-left">
                        <h1 class="text-2xl sm:text-3xl font-bold text-white mb-1">
                            <i class="fas fa-receipt mr-2"></i>Transactions
                        </h1>
                        <p class="text-blue-100 text-sm">Manage your inventory transactions</p>
                    </div>

                    @if($hasProducts)
                    <a href="{{ route('admin.inventory.inventory_transactions.create') }}" id="counterBtn"
                        class="group relative overflow-hidden bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 text-white font-bold py-3 px-6 rounded-xl transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl">
                        <div class="absolute inset-0 bg-white/20 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
                        <span class="relative flex items-center">
                            <svg id="counterIcon" class="w-5 h-5 mr-2 transition-transform group-hover:rotate-90" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" />
                            </svg>
                            <i id="counterSpinner" class="hidden fas fa-spinner fa-spin mr-2"></i>
                            <span id="counterButtonText">New Sale</span>
                        </span>
                    </a>
                    @else
                    <button type="button" onclick="showNoProductsWarning('{{ $userType }}')"
                        class="bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white font-semibold py-3 px-6 rounded-xl transition-all duration-300 transform hover:scale-105 shadow-lg">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Setup Required
                    </button>
                    @endif
                </div>
            </div>

            <!-- Enhanced Filter Section -->
            <div class="p-4 bg-gradient-to-r from-gray-50 to-blue-50 border-t border-gray-200">
                <form id="filterForm" action="{{ route('admin.inventory.inventory_transactions.index') }}" method="GET"
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">

                    <!-- Date Filters -->
                    <div class="relative">
                        <input type="date" id="start_date" name="start_date" value="{{ $filters['start_date'] ?? '' }}"
                            class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                        <i class="fas fa-calendar-alt absolute left-3 top-3 text-gray-400 text-sm"></i>
                    </div>

                    <div class="relative">
                        <input type="date" id="end_date" name="end_date" value="{{ $filters['end_date'] ?? '' }}"
                            class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                        <i class="fas fa-calendar-alt absolute left-3 top-3 text-gray-400 text-sm"></i>
                    </div>

                    <!-- Search by Invoice ID or Customer -->
                    <div class="relative">
                        <input type="text" id="search" name="search" value="{{ $filters['search'] ?? '' }}"
                            placeholder="Invoice ID or Customer..."
                            class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-sm"></i>
                    </div>

                    <!-- Transaction Type Filter -->
                    <div class="relative">
                        <select id="type" name="type" class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                            <option value="">All Types</option>
                            <option value="sale" {{ isset($filters['type']) && $filters['type'] == 'sale' ? 'selected' : '' }}>Sale</option>
                            <option value="purchase" {{ isset($filters['type']) && $filters['type'] == 'purchase' ? 'selected' : '' }}>Purchase</option>
                        </select>
                        <i class="fas fa-filter absolute left-3 top-3 text-gray-400 text-sm"></i>
                    </div>

                    <!-- Due Filter -->
                    <div class="relative">
                        <select id="due_filter" name="due_filter" class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                            <option value="">All Transactions</option>
                            <option value="due_only" {{ isset($filters['due_filter']) && $filters['due_filter'] == 'due_only' ? 'selected' : '' }}>Due Only</option>
                            <option value="paid_only" {{ isset($filters['due_filter']) && $filters['due_filter'] == 'paid_only' ? 'selected' : '' }}>Paid Only</option>
                        </select>
                        <i class="fas fa-money-bill-wave absolute left-3 top-3 text-gray-400 text-sm"></i>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex gap-2 sm:col-span-2 lg:col-span-1">
                        <button type="submit" id="searchBtn"
                            class="flex-1 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-medium py-2.5 px-4 rounded-lg transition-all duration-300 transform hover:scale-105 shadow-md text-sm">
                            <i class="fas fa-search mr-1"></i>
                            <span class="hidden sm:inline">Search</span>
                        </button>

                        <a href="{{ route('admin.inventory.inventory_transactions.index', ['reset_filters' => true]) }}"
                            class="flex-1 bg-gradient-to-r from-gray-500 to-gray-600 hover:from-gray-600 hover:to-gray-700 text-white font-medium py-2.5 px-4 rounded-lg transition-all duration-300 transform hover:scale-105 shadow-md text-sm text-center">
                            <i class="fas fa-undo mr-1"></i>
                            <span class="hidden sm:inline">Reset</span>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Success Message --}}
        @if(session('success'))
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded-r-xl shadow-lg" role="alert">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-500 mr-3 text-lg"></i>
                <span class="font-medium">{{ session('success') }}</span>
            </div>
        </div>
        @endif

        {{-- Compact Mobile Cards --}}
        {{-- Compact Mobile Cards --}}
        <div class="block lg:hidden space-y-2">
            @forelse($transactions as $trx)
            @php
            $canReturn = false;
            if ($trx->entry_type === 'sale' && $trx->grand_total > 0) {
            $transactionLines = $trx->lines()->where('quantity', '>', 0)->get();
            $canReturn = $transactionLines->isNotEmpty();
            }
            @endphp

            <div class="bg-white/90 backdrop-blur-sm rounded-lg shadow-md border border-white/20 overflow-hidden hover:shadow-lg transition-all duration-300">
                <!-- Compact Header -->
                <div class="bg-gradient-to-r from-slate-50 to-blue-50 px-3 py-2 border-b border-gray-100">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center space-x-2">
                            <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center">
                                <i class="fas fa-hashtag text-white text-xs"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 text-xs">{{ $trx->invoice_id ?? 'INV-' . $trx->id }}</h3>
                                <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($trx->transaction_date)->format('M d') }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-bold text-gray-900">৳{{ number_format($trx->grand_total, 0) }}</div>
                            <div class="text-xs text-gray-500">{{ ucfirst($trx->entry_type) }}</div>
                        </div>
                    </div>
                </div>

                <!-- Compact Body -->
                <div class="p-3">
                    <!-- Customer Info -->
                    <div class="mb-3">
                        <div class="text-sm font-medium text-gray-700 truncate">{{ $trx->ledger->name }}</div>
                        <div class="text-xs text-gray-500">{{ $trx->payment_method === 'credit' ? 'Credit' : 'Cash' }}</div>
                    </div>

                    <!-- Compact Action Buttons -->
                    @if(Auth::user()->hasRole('dsr'))
                    <div class="grid grid-cols-4 gap-1">
                        <!-- Return Button -->
                        <button class="return-btn flex flex-col items-center justify-center p-2 rounded-lg {{ (!$canReturn || $trx->grand_total <= 0) ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-amber-50 text-amber-600 hover:bg-amber-100' }} transition-all duration-300"
                            data-id="{{ $trx->id }}"
                            {{ (!$canReturn || $trx->grand_total <= 0) ? 'disabled' : '' }}
                            title="{{ $trx->grand_total <= 0 ? 'Cannot return - transaction amount is zero' : (!$canReturn ? 'All products have been returned' : 'Return Products') }}">
                            <i class="fas fa-undo text-xs mb-1"></i>
                            <span class="text-xs">Return</span>
                        </button>

                        <!-- View Button -->
                        <a href="{{ route('admin.inventory.inventory_transactions.show', $trx->id) }}"
                            class="flex flex-col items-center justify-center p-2 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition-all duration-300">
                            <i class="fas fa-eye text-xs mb-1"></i>
                            <span class="text-xs">View</span>
                        </a>

                        <!-- Collection Button -->
                        <button class="collection-btn flex flex-col items-center justify-center p-2 rounded-lg {{ ($trx->payment_method === 'credit' && $trx->grand_total > 0) ? 'bg-green-50 text-green-600 hover:bg-green-100' : 'bg-gray-100 text-gray-400 cursor-not-allowed' }} transition-all duration-300"
                            data-id="{{ $trx->id }}"
                            data-amount="{{ $trx->grand_total }}"
                            {{ ($trx->payment_method !== 'credit' || $trx->grand_total <= 0) ? 'disabled' : '' }}>
                            <i class="fas fa-money-bill-wave text-xs mb-1"></i>
                            <span class="text-xs">Collect</span>
                        </button>

                        <!-- Share Button -->
                        <button class="flex flex-col items-center justify-center p-2 rounded-lg bg-purple-50 text-purple-600 hover:bg-purple-100 transition-all duration-300">
                            <i class="fas fa-share-alt text-xs mb-1"></i>
                            <span class="text-xs">Share</span>
                        </button>
                    </div>
                    @else
                    <!-- Non-DSR users - only View and Share buttons -->
                    <div class="grid grid-cols-2 gap-1">
                        <!-- View Button -->
                        <a href="{{ route('admin.inventory.inventory_transactions.show', $trx->id) }}"
                            class="flex flex-col items-center justify-center p-2 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition-all duration-300">
                            <i class="fas fa-eye text-xs mb-1"></i>
                            <span class="text-xs">View</span>
                        </a>

                        <!-- Share Button -->
                        <button class="flex flex-col items-center justify-center p-2 rounded-lg bg-purple-50 text-purple-600 hover:bg-purple-100 transition-all duration-300">
                            <i class="fas fa-share-alt text-xs mb-1"></i>
                            <span class="text-xs">Share</span>
                        </button>
                    </div>
                    @endif

                    @role('admin')
                    <!-- Delete Button (Admin Only) -->
                    <div class="mt-2">
                        <button class="delete-btn w-full flex items-center justify-center p-1.5 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition-all duration-300"
                            data-id="{{ $trx->id }}">
                            <i class="fas fa-trash-alt mr-1 text-xs"></i>
                            <span class="text-xs">Delete</span>
                        </button>
                    </div>
                    @endrole
                </div>
            </div>
            @empty
            <div class="bg-white/90 backdrop-blur-sm rounded-xl shadow-lg p-8 text-center">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-receipt text-gray-400 text-2xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No transactions found</h3>
                <p class="text-gray-500">Try adjusting your search filters or create a new transaction.</p>
            </div>
            @endforelse
        </div>


        {{-- Desktop Table --}}
        <div class="hidden lg:block bg-white/90 backdrop-blur-sm rounded-xl shadow-lg border border-white/20 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gradient-to-r from-gray-50 to-blue-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <i class="fas fa-hashtag mr-2"></i>Invoice ID
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <i class="fas fa-user mr-2"></i>Customer
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <i class="fas fa-calendar mr-2"></i>Date
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <i class="fas fa-money-bill mr-2"></i>Amount
                            </th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <i class="fas fa-cogs mr-2"></i>Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($transactions as $trx)
                        @php
                        $canReturnDesktop = false;
                        if ($trx->entry_type === 'sale' && $trx->grand_total > 0) {
                        $transactionLines = $trx->lines()->where('quantity', '>', 0)->get();
                        $canReturnDesktop = $transactionLines->isNotEmpty();
                        }
                        @endphp


                        <tr class="hover:bg-gradient-to-r hover:from-blue-50 hover:to-indigo-50 transition-all duration-300">
                            <!-- Invoice ID Column -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-gradient-to-br from-purple-500 to-pink-600 rounded-full flex items-center justify-center mr-3">
                                        <i class="fas fa-hashtag text-white text-xs"></i>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ $trx->invoice_id ?? 'INV-' . $trx->id }}</div>
                                        <div class="text-xs text-gray-500">{{ ucfirst($trx->entry_type) }} Transaction</div>
                                    </div>
                                </div>
                            </td>

                            <!-- Customer Column -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-user text-white text-sm"></i>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ $trx->ledger->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $trx->payment_method === 'credit' ? 'Credit' : 'Cash' }}</div>
                                    </div>
                                </div>
                            </td>

                            <!-- Date Column -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ \Carbon\Carbon::parse($trx->transaction_date)->format('M d, Y') }}</div>
                                <div class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($trx->transaction_date)->format('h:i A') }}</div>
                            </td>

                            <!-- Amount Column -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-lg font-bold text-gray-900">৳{{ number_format($trx->grand_total, 0) }}</div>
                                @if($trx->grand_total > 0 && $trx->payment_method === 'credit')
                                <div class="text-xs text-red-500">Due</div>
                                @else
                                <div class="text-xs text-green-500">Paid</div>
                                @endif
                            </td>

                            <!-- Actions Column -->
                            {{-- In the Desktop Table section, update the Actions Column --}}
                            <!-- Actions Column -->
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex justify-center space-x-2">
                                    @if(Auth::user()->hasRole('dsr'))
                                    <!-- Return Button -->
                                    <button class="return-btn p-2 rounded-lg {{ (!$canReturnDesktop || $trx->grand_total <= 0) ? 'text-gray-400 bg-gray-100 cursor-not-allowed' : 'text-amber-600 bg-amber-50 hover:bg-amber-100 border border-amber-200' }} transition-all duration-300"
                                        data-id="{{ $trx->id }}"
                                        {{ (!$canReturnDesktop || $trx->grand_total <= 0) ? 'disabled' : '' }}
                                        title="{{ $trx->grand_total <= 0 ? 'Cannot return - transaction amount is zero' : (!$canReturnDesktop ? 'All products have been returned' : 'Return Products') }}">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                    @endif

                                    <!-- View Button -->
                                    <a href="{{ route('admin.inventory.inventory_transactions.show', $trx->id) }}"
                                        class="p-2 rounded-lg text-blue-600 bg-blue-50 hover:bg-blue-100 border border-blue-200 transition-all duration-300"
                                        title="View Transaction">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                                        </svg>
                                    </a>

                                    @if(Auth::user()->hasRole('dsr'))
                                    <!-- Collection Button -->
                                    <button class="collection-btn p-2 rounded-lg {{ ($trx->payment_method === 'credit' && $trx->grand_total > 0) ? 'text-green-600 bg-green-50 hover:bg-green-100 border border-green-200' : 'text-gray-400 bg-gray-100 cursor-not-allowed' }} transition-all duration-300"
                                        data-id="{{ $trx->id }}"
                                        data-amount="{{ $trx->grand_total }}"
                                        {{ ($trx->payment_method !== 'credit' || $trx->grand_total <= 0) ? 'disabled' : '' }}
                                        title="{{ ($trx->payment_method === 'credit' && $trx->grand_total > 0) ? 'Collect Payment' : ($trx->grand_total <= 0 ? 'No amount to collect' : 'Cash Transaction') }}">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z" />
                                            <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                    @endif

                                    <!-- Share Button -->
                                    <button class="p-2 rounded-lg text-purple-600 bg-purple-50 hover:bg-purple-100 border border-purple-200 transition-all duration-300"
                                        title="Share Transaction">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M15 8a3 3 0 10-2.977-2.63l-4.94 2.47a3 3 0 100 4.319l4.94 2.47a3 3 0 10.895-1.789l-4.94-2.47a3.027 3.027 0 000-.74l4.94-2.47C13.456 7.68 14.19 8 15 8z" />
                                        </svg>
                                    </button>

                                    @role('admin')
                                    <!-- Delete Button -->
                                    <button class="delete-btn p-2 rounded-lg text-red-600 bg-red-50 hover:bg-red-100 border border-red-200 transition-all duration-300"
                                        data-id="{{ $trx->id }}"
                                        title="Delete Transaction">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                    @endrole
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-receipt text-gray-400 text-2xl"></i>
                                </div>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">No transactions found</h3>
                                <p class="text-gray-500">Try adjusting your search filters or create a new transaction.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pagination --}}
        @if($transactions->hasPages())
        <div class="mt-6 flex justify-center">
            {{ $transactions->links('vendor.pagination.tailwind') }}
        </div>
        @endif
    </div>

    <!-- Payment Collection Modal -->
    <div id="collectionModal" class="hidden fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50">
        <div class="bg-white p-6 rounded-lg w-full max-w-md mx-4 md:max-w-lg shadow-xl overflow-y-auto max-h-[90vh]">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold">Category-wise Collection</h3>
                <button type="button" id="cancelCollection" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div id="categoryLoading" class="py-8 text-center">
                <i class="fas fa-spinner fa-spin text-blue-500 text-2xl mx-auto mb-4"></i>
                <p class="text-gray-600">Loading categories...</p>
            </div>

            <div id="categoryContent" class="hidden">
                <div class="mb-4">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Customer: <span id="customerName" class="font-semibold"></span></h4>
                    <h4 class="text-sm font-medium text-gray-700">Total Due: <span id="totalDue" class="font-semibold text-red-600"></span></h4>
                </div>

                <div class="border-t border-b py-3 mb-4">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Select categories to collect payment:</h4>
                    <div id="categoryList" class="space-y-3 max-h-60 overflow-y-auto pr-1">
                        <!-- Categories will be loaded here -->
                    </div>
                </div>

                <div class="mb-4">
                    <label for="totalCollectionAmount" class="block text-sm font-medium text-gray-700 mb-1">Total Collection Amount</label>
                    <div class="relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">৳</span>
                        </div>
                        <input
                            type="number"
                            name="total_amount"
                            id="totalCollectionAmount"
                            step="0.01"
                            min="0.01"
                            class="pl-7 w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 text-sm py-2"
                            placeholder="0.00"
                            required>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Enter the total amount you want to collect.</p>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button type="button" id="cancelCollectionBtn" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300 transition duration-200">
                        Cancel
                    </button>
                    <button type="button" id="saveCollectionBtn" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200">
                        Save Collection
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Return Modal -->
    <div id="returnModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 md:items-center md:justify-center">
        <div class="bg-white w-full h-full md:h-auto md:max-h-[90vh] md:w-auto md:max-w-lg md:rounded-lg overflow-hidden shadow-xl flex flex-col">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-amber-500 to-amber-600 px-4 py-3 flex justify-between items-center sticky top-0 z-10">
                <h3 class="text-lg font-semibold text-white flex items-center">
                    <i class="fas fa-undo mr-2"></i>
                    Return Products
                </h3>
                <button id="closeReturnModal" class="text-white hover:text-gray-200 transition-colors">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Instructions -->
            <div class="bg-amber-50 px-4 py-2 border-b border-amber-100">
                <p class="text-sm text-amber-800">
                    Select products to return, adjust quantities if needed, then click "Confirm Return".
                </p>
            </div>

            <!-- Loading State -->
            <div id="returnProductsLoading" class="py-8 flex-grow flex justify-center items-center">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin text-amber-500 text-3xl mx-auto mb-4"></i>
                    <p class="text-gray-500">Loading products...</p>
                </div>
            </div>

            <!-- Empty State -->
            <div id="returnProductsEmpty" class="hidden py-8 flex-grow flex justify-center items-center">
                <div class="text-center p-4">
                    <svg class="h-16 w-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-gray-500 text-lg font-medium">No products available</p>
                    <p class="text-gray-400 text-sm mt-1">All products have already been returned.</p>
                </div>
            </div>

            <!-- Products List -->
            <div id="returnProductsContainer" class="flex-grow overflow-y-auto">
                <div class="divide-y divide-gray-200">
                    <div id="returnProductsList"></div>
                </div>
            </div>

            <!-- Return Summary -->
            <div id="returnSummary" class="hidden bg-gray-50 px-4 py-3 border-t border-gray-200 sticky bottom-0">
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center">
                    <div class="mb-2 sm:mb-0">
                        <span class="text-sm font-medium text-gray-700">Selected:</span>
                        <span id="returnSelectedCount" class="ml-1 text-sm font-semibold text-amber-600">0</span>
                    </div>
                    <div>
                        <span class="text-sm font-medium text-gray-700">Total Amount:</span>
                        <span id="returnTotalAmount" class="ml-1 text-sm font-semibold text-amber-600">৳0.00</span>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="bg-gray-50 px-4 py-3 border-t flex justify-end space-x-3 sticky bottom-0">
                <button type="button" id="cancelReturn"
                    class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 transition-colors">
                    Cancel
                </button>
                <button type="button" id="confirmReturn"
                    class="px-4 py-2.5 text-sm font-medium text-white bg-gray-400 hover:bg-gray-500 border border-transparent rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 transition-colors cursor-not-allowed"
                    disabled>
                    <i id="returnSpinner" class="hidden fas fa-spinner fa-spin mr-2"></i>
                    <span id="confirmReturnText">Confirm Return</span>
                </button>
            </div>
        </div>
    </div>

    @if($products->isEmpty())
    <div class="bg-gradient-to-r from-yellow-50 to-amber-50 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded-r-xl shadow-lg" role="alert">
        <div class="flex items-center">
            <i class="fas fa-exclamation-triangle text-yellow-500 mr-3 text-lg"></i>
            <div>
                <strong class="font-bold">No Products Available!</strong>
                <span class="block sm:inline ml-2">
                    @if($userType === 'staff')
                    No products found in your assigned categories. Please contact an administrator.
                    @else
                    No products found for this business.
                    @endif
                </span>
            </div>
        </div>
    </div>
    @endif
</div>

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    /* Custom scrollbar */
    .overflow-y-auto::-webkit-scrollbar {
        width: 6px;
    }

    .overflow-y-auto::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 3px;
    }

    .overflow-y-auto::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
    }

    .overflow-y-auto::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* Backdrop blur fallback */
    .backdrop-blur-sm {
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
    }

    /* Enhanced button states */
    .disabled\:opacity-50:disabled {
        opacity: 0.5;
    }

    .disabled\:cursor-not-allowed:disabled {
        cursor: not-allowed;
    }

    .cursor-not-allowed {
        cursor: not-allowed;
    }

    /* Button color overrides */
    #confirmReturn.bg-amber-600 {
        background: linear-gradient(to right, #d97706, #f59e0b) !important;
    }

    #confirmReturn.bg-gray-400 {
        background: #9ca3af !important;
    }

    #confirmReturn:hover.hover\:bg-amber-700 {
        background: linear-gradient(to right, #b45309, #d97706) !important;
    }

    #confirmReturn:hover.hover\:bg-gray-500 {
        background: #6b7280 !important;
    }

    /* Mobile responsive improvements */
    @media (max-width: 640px) {
        .container {
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }
    }

    /* Glassmorphism effect */
    .bg-white\/90 {
        background: rgba(255, 255, 255, 0.9);
    }

    .bg-white\/80 {
        background: rgba(255, 255, 255, 0.8);
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@vite('resources/js/admin/inventory-transactions.js')
<script>
    $(document).ready(function() {
        // Enhanced button loading states
        $('#counterBtn').on('click', function() {
            const icon = $('#counterIcon');
            const spinner = $('#counterSpinner');
            const text = $('#counterButtonText');

            icon.addClass('hidden');
            spinner.removeClass('hidden');
            text.text('Loading...');
        });

        $('#searchBtn').on('click', function() {
            const btn = $(this);
            const originalHtml = btn.html();

            btn.html('<i class="fas fa-spinner fa-spin mr-1"></i><span class="hidden sm:inline">Searching...</span>');
            btn.prop('disabled', true);
        });

        // Auto-hide success messages
        setTimeout(function() {
            $('.bg-gradient-to-r.from-green-50').fadeOut('slow');
        }, 5000);

        // Enhanced search functionality with debounce
        let searchTimeout;
        $('#search').on('input', function() {
            clearTimeout(searchTimeout);
            const searchTerm = $(this).val();

            if (searchTerm.length > 2) {
                searchTimeout = setTimeout(() => {
                    $('#filterForm').submit();
                }, 1000);
            } else if (searchTerm.length === 0) {
                searchTimeout = setTimeout(() => {
                    $('#filterForm').submit();
                }, 500);
            }
        });

        // Auto-submit on filter changes
        $('#type, #due_filter, #start_date, #end_date').on('change', function() {
            $('#filterForm').submit();
        });

        // Keyboard shortcuts
        $(document).keydown(function(e) {
            // Ctrl/Cmd + K to focus search
            if ((e.ctrlKey || e.metaKey) && e.keyCode === 75) {
                e.preventDefault();
                $('#search').focus();
            }

            // Escape to close modals
            if (e.keyCode === 27) {
                $('#collectionModal').addClass('hidden');
                $('#returnModal').addClass('hidden');
            }
        });

        // Close modal handlers
        $('#cancelCollection, #closeReturnModal, #cancelReturn').on('click', function() {
            $('#collectionModal').addClass('hidden');
            $('#returnModal').addClass('hidden');
        });
    });
</script>
@endpush
@endsection