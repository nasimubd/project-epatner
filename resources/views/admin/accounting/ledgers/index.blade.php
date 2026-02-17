@extends('admin.layouts.app')

@section('content')
@php
$user = Auth::user();
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
                            <i class="fas fa-book mr-2"></i>Ledgers Management
                        </h1>
                        <p class="text-blue-100 text-sm">Manage your accounting ledgers</p>
                    </div>

                    <div class="flex flex-col sm:flex-row w-full sm:w-auto space-y-3 sm:space-y-0 sm:space-x-3">
                        <a href="{{ route('admin.accounting.ledgers.create') }}" id="createLedgerBtn"
                            class="group relative overflow-hidden bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 text-white font-bold py-3 px-6 rounded-xl transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl">
                            <div class="absolute inset-0 bg-white/20 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
                            <span class="relative flex items-center justify-center">
                                <svg id="defaultPlusIcon" class="w-5 h-5 mr-2 transition-transform group-hover:rotate-90" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" />
                                </svg>
                                <i id="spinnerIcon" class="hidden fas fa-spinner fa-spin mr-2"></i>
                                <span id="buttonText">New Ledger</span>
                            </span>
                        </a>

                        <a href="{{ route('admin.business.sub-districts.index') }}" id="importSubDistrictsBtn"
                            class="group relative overflow-hidden bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white font-bold py-3 px-6 rounded-xl transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl">
                            <div class="absolute inset-0 bg-white/20 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
                            <span class="relative flex items-center justify-center">
                                <svg id="importIcon" class="w-5 h-5 mr-2 transition-transform group-hover:rotate-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                                </svg>
                                <i id="importSpinnerIcon" class="hidden fas fa-spinner fa-spin mr-2"></i>
                                <span id="importButtonText" class="hidden sm:inline">Import Sub-Districts</span>
                                <span class="sm:hidden">Import</span>
                            </span>
                        </a>

                        <a href="{{ route('admin.accounting.ledgers.index', ['refresh_ledgers' => 1]) }}" id="refreshLedgersBtn"
                            class="group relative overflow-hidden bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-bold py-3 px-6 rounded-xl transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl">
                            <div class="absolute inset-0 bg-white/20 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
                            <span class="relative flex items-center justify-center">
                                <svg id="refreshIcon" class="w-5 h-5 mr-2 transition-transform group-hover:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                <i id="refreshSpinnerIcon" class="hidden fas fa-spinner fa-spin mr-2"></i>
                                <span id="refreshButtonText" class="hidden sm:inline">Refresh Ledgers</span>
                                <span class="sm:hidden">Refresh</span>
                            </span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Enhanced Filter Section -->
            <div class="p-4 bg-gradient-to-r from-gray-50 to-blue-50 border-t border-gray-200">
                <form action="{{ route('admin.accounting.ledgers.index') }}" method="GET"
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">

                    <!-- Search Input -->
                    <div class="relative">
                        <input type="text" id="search" name="search" value="{{ request('search') }}"
                            placeholder="Search by ID or Name..."
                            class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-sm"></i>
                    </div>

                    <!-- Type Filter -->
                    <div class="relative">
                        <select name="type" class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                            <option value="">All Types</option>
                            <option value="Bank Accounts" {{ request('type') == 'Bank Accounts' ? 'selected' : '' }}>Bank Accounts</option>
                            <option value="Bank OD A/c" {{ request('type') == 'Bank OD A/c' ? 'selected' : '' }}>Bank OD A/c</option>
                            <option value="Capital Accounts" {{ request('type') == 'Capital Accounts' ? 'selected' : '' }}>Capital Accounts</option>
                            <option value="Cash-in-Hand" {{ request('type') == 'Cash-in-Hand' ? 'selected' : '' }}>Cash-in-Hand</option>
                            <option value="Duties & Taxes" {{ request('type') == 'Duties & Taxes' ? 'selected' : '' }}>Duties & Taxes</option>
                            <option value="Expenses" {{ request('type') == 'Expenses' ? 'selected' : '' }}>Expenses</option>
                            <option value="Fixed Assets" {{ request('type') == 'Fixed Assets' ? 'selected' : '' }}>Fixed Assets</option>
                            <option value="Incomes" {{ request('type') == 'Incomes' ? 'selected' : '' }}>Incomes</option>
                            <option value="Investments" {{ request('type') == 'Investments' ? 'selected' : '' }}>Investments</option>
                            <option value="Loans & Advances (Asset)" {{ request('type') == 'Loans & Advances (Asset)' ? 'selected' : '' }}>Loans & Advances (Asset)</option>
                            <option value="Loans A/c" {{ request('type') == 'Loans A/c' ? 'selected' : '' }}>Loans A/c</option>
                            <option value="Purchase Accounts" {{ request('type') == 'Purchase Accounts' ? 'selected' : '' }}>Purchase Accounts</option>
                            <option value="Stock-in-Hand" {{ request('type') == 'Stock-in-Hand' ? 'selected' : '' }}>Stock-in-Hand</option>
                            <option value="Sales Accounts" {{ request('type') == 'Sales Accounts' ? 'selected' : '' }}>Sales Accounts</option>
                            <option value="Sundry Debtors (Customer)" {{ request('type') == 'Sundry Debtors (Customer)' ? 'selected' : '' }}>Sundry Debtors (Customer)</option>
                            <option value="Sundry Creditors (Supplier)" {{ request('type') == 'Sundry Creditors (Supplier)' ? 'selected' : '' }}>Sundry Creditors (Supplier)</option>
                        </select>
                        <i class="fas fa-filter absolute left-3 top-3 text-gray-400 text-sm"></i>
                    </div>

                    <!-- Status Filter -->
                    <div class="relative">
                        <select name="status" class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                            <option value="">All Status</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        <i class="fas fa-toggle-on absolute left-3 top-3 text-gray-400 text-sm"></i>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex gap-2">
                        <button type="submit" id="searchBtn"
                            class="flex-1 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-medium py-2.5 px-4 rounded-lg transition-all duration-300 transform hover:scale-105 shadow-md text-sm">
                            <i class="fas fa-search mr-1"></i>
                            <span class="hidden sm:inline">Search</span>
                        </button>

                        <a href="{{ route('admin.accounting.ledgers.index') }}"
                            class="flex-1 bg-gradient-to-r from-gray-500 to-gray-600 hover:from-gray-600 hover:to-gray-700 text-white font-medium py-2.5 px-4 rounded-lg transition-all duration-300 transform hover:scale-105 shadow-md text-sm text-center flex items-center justify-center">
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

        {{-- Mobile Cards - Only show on small screens --}}
        <div class="lg:hidden space-y-3">
            @forelse($ledgers as $ledger)
            <div class="bg-white/90 backdrop-blur-sm rounded-lg shadow-md border border-white/20 overflow-hidden hover:shadow-lg transition-all duration-300">
                <!-- Mobile Card Header -->
                <div class="bg-gradient-to-r from-slate-50 to-blue-50 px-4 py-3 border-b border-gray-100">
                    <div class="flex justify-between items-start">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center">
                                <i class="fas fa-book text-white text-sm"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 text-sm">{{ Str::limit($ledger->name, 20) }}</h3>
                                <p class="text-xs text-gray-500">ID: {{ $ledger->id }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-bold {{ $ledger->current_balance > 0 ? 'text-green-600' : 'text-red-600' }}">
                                ৳{{ number_format(abs($ledger->current_balance), 0) }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mobile Card Body -->
                <div class="p-4">
                    <div class="flex justify-between items-center mb-3">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {{ Str::limit($ledger->ledger_type, 15) }}
                        </span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                            {{ $ledger->status == 'active' ? 'bg-green-100 text-green-800' : 
                               ($ledger->status == 'inactive' ? 'bg-red-100 text-red-800' : 
                               'bg-yellow-100 text-yellow-800') }}">
                            {{ ucfirst($ledger->status) }}
                        </span>
                    </div>

                    <!-- Mobile Action Buttons -->
                    <div class="grid grid-cols-3 gap-2">
                        <!-- View Button -->
                        <a href="{{ route('admin.accounting.ledgers.show', $ledger->id) }}"
                            class="flex flex-col items-center justify-center p-3 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition-all duration-300">
                            <i class="fas fa-eye text-sm mb-1"></i>
                            <span class="text-xs font-medium">View</span>
                        </a>

                        <!-- Edit Button -->
                        <a href="{{ route('admin.accounting.ledgers.edit', $ledger->id) }}"
                            class="flex flex-col items-center justify-center p-3 rounded-lg bg-yellow-50 text-yellow-600 hover:bg-yellow-100 transition-all duration-300">
                            <i class="fas fa-edit text-sm mb-1"></i>
                            <span class="text-xs font-medium">Edit</span>
                        </a>

                        <!-- Delete Button -->
                        <form action="{{ route('admin.accounting.ledgers.destroy', $ledger->id) }}"
                            method="POST"
                            onsubmit="return confirm('Are you sure you want to delete this ledger?');"
                            class="w-full">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="w-full flex flex-col items-center justify-center p-3 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition-all duration-300">
                                <i class="fas fa-trash-alt text-sm mb-1"></i>
                                <span class="text-xs font-medium">Delete</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @empty
            <div class="bg-white/90 backdrop-blur-sm rounded-xl shadow-lg p-8 text-center">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-book text-gray-400 text-2xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No ledgers found</h3>
                <p class="text-gray-500 text-sm">Try adjusting your search filters or create a new ledger.</p>
            </div>
            @endforelse
        </div>

        {{-- Desktop Table - Only show on large screens --}}
        <div class="hidden lg:block bg-white/90 backdrop-blur-sm rounded-xl shadow-lg border border-white/20 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gradient-to-r from-gray-50 to-blue-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <i class="fas fa-hashtag mr-2"></i>ID
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <i class="fas fa-book mr-2"></i>Name
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <i class="fas fa-tag mr-2"></i>Type
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <i class="fas fa-money-bill mr-2"></i>Balance
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <i class="fas fa-toggle-on mr-2"></i>Status
                            </th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <i class="fas fa-cogs mr-2"></i>Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($ledgers as $ledger)
                        <tr class="hover:bg-gradient-to-r hover:from-blue-50 hover:to-indigo-50 transition-all duration-300">
                            <!-- ID Column -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-gradient-to-br from-purple-500 to-pink-600 rounded-full flex items-center justify-center mr-3">
                                        <i class="fas fa-hashtag text-white text-xs"></i>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ $ledger->id }}</div>
                                    </div>
                                </div>
                            </td>

                            <!-- Name Column -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-book text-white text-sm"></i>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ $ledger->name }}</div>
                                    </div>
                                </div>
                            </td>

                            <!-- Type Column -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ $ledger->ledger_type }}
                                </span>
                            </td>

                            <!-- Balance Column -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-lg font-bold {{ $ledger->current_balance > 0 ? 'text-green-600' : 'text-red-600' }}">
                                    ৳{{ number_format(abs($ledger->current_balance), 0) }}
                                </div>
                            </td>

                            <!-- Status Column -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    {{ $ledger->status == 'active' ? 'bg-green-100 text-green-800' : 
                                       ($ledger->status == 'inactive' ? 'bg-red-100 text-red-800' : 
                                       'bg-yellow-100 text-yellow-800') }}">
                                    {{ ucfirst($ledger->status) }}
                                </span>
                            </td>

                            <!-- Actions Column -->
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex justify-center space-x-2">
                                    <!-- View Button -->
                                    <a href="{{ route('admin.accounting.ledgers.show', $ledger->id) }}" id="viewBtn{{ $ledger->id }}"
                                        class="p-2 rounded-lg text-blue-600 bg-blue-50 hover:bg-blue-100 border border-blue-200 transition-all duration-300"
                                        title="View Ledger">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                                        </svg>
                                    </a>

                                    <!-- Edit Button -->
                                    <a href="{{ route('admin.accounting.ledgers.edit', $ledger->id) }}" id="editBtn{{ $ledger->id }}"
                                        class="p-2 rounded-lg text-yellow-600 bg-yellow-50 hover:bg-yellow-100 border border-yellow-200 transition-all duration-300"
                                        title="Edit Ledger">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                        </svg>
                                    </a>

                                    <!-- Delete Button -->
                                    <form action="{{ route('admin.accounting.ledgers.destroy', $ledger->id) }}"
                                        method="POST"
                                        onsubmit="return confirm('Are you sure you want to delete this ledger?');"
                                        class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" id="deleteBtn{{ $ledger->id }}"
                                            class="p-2 rounded-lg text-red-600 bg-red-50 hover:bg-red-100 border border-red-200 transition-all duration-300"
                                            title="Delete Ledger">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-book text-gray-400 text-2xl"></i>
                                </div>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">No ledgers found</h3>
                                <p class="text-gray-500">Try adjusting your search filters or create a new ledger.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pagination --}}
        @if($ledgers->hasPages())
        <div class="mt-6 flex justify-center">
            <div class="bg-white/90 backdrop-blur-sm rounded-lg shadow-lg border border-white/20 p-2">
                {{ $ledgers->appends(request()->query())->links() }}
            </div>
        </div>
        @endif
    </div>
</div>

@push('styles')
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

    /* Glassmorphism effect */
    .bg-white\/90 {
        background: rgba(255, 255, 255, 0.9);
    }

    .bg-white\/80 {
        background: rgba(255, 255, 255, 0.8);
    }

    /* Mobile responsive improvements */
    @media (max-width: 640px) {
        .p-2 {
            padding: 0.25rem;
        }

        .space-y-3>*+* {
            margin-top: 0.75rem;
        }
    }

    /* Ensure proper responsive behavior */
    @media (max-width: 1023px) {
        .lg\:hidden {
            display: block !important;
        }

        .lg\:block {
            display: none !important;
        }
    }

    @media (min-width: 1024px) {
        .lg\:hidden {
            display: none !important;
        }

        .lg\:block {
            display: block !important;
        }
    }

    /* Button hover effects */
    .group:hover .group-hover\:translate-x-full {
        transform: translateX(100%);
    }

    .group:hover .group-hover\:rotate-90 {
        transform: rotate(90deg);
    }

    .group:hover .group-hover\:rotate-12 {
        transform: rotate(12deg);
    }

    /* Loading states */
    .fa-spin {
        animation: fa-spin 2s infinite linear;
    }

    @keyframes fa-spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    /* Enhanced focus states */
    .focus\:ring-2:focus {
        outline: 2px solid transparent;
        outline-offset: 2px;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5);
    }

    /* Smooth transitions */
    * {
        transition-property: color, background-color, border-color, text-decoration-color, fill, stroke, opacity, box-shadow, transform, filter, backdrop-filter;
        transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
        transition-duration: 150ms;
    }
</style>
@endpush

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        // Enhanced button loading states
        $('#createLedgerBtn').on('click', function() {
            const icon = $('#defaultPlusIcon');
            const spinner = $('#spinnerIcon');
            const text = $('#buttonText');

            icon.addClass('hidden');
            spinner.removeClass('hidden');
            text.text('Loading...');
        });

        $('#importSubDistrictsBtn').on('click', function() {
            const importIcon = $('#importIcon');
            const spinnerIcon = $('#importSpinnerIcon');
            const buttonText = $('#importButtonText');

            importIcon.addClass('hidden');
            spinnerIcon.removeClass('hidden');
            buttonText.text('Loading...');
        });

        $('#refreshLedgersBtn').on('click', function() {
            const refreshIcon = $('#refreshIcon');
            const spinnerIcon = $('#refreshSpinnerIcon');
            const buttonText = $('#refreshButtonText');

            refreshIcon.addClass('hidden');
            spinnerIcon.removeClass('hidden');
            buttonText.text('Refreshing...');
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
                    $(this).closest('form').submit();
                }, 1000);
            } else if (searchTerm.length === 0) {
                searchTimeout = setTimeout(() => {
                    $(this).closest('form').submit();
                }, 500);
            }
        });

        // Auto-submit on filter changes
        $('select[name="type"], select[name="status"]').on('change', function() {
            $(this).closest('form').submit();
        });

        // Keyboard shortcuts
        $(document).keydown(function(e) {
            // Ctrl/Cmd + K to focus search
            if ((e.ctrlKey || e.metaKey) && e.keyCode === 75) {
                e.preventDefault();
                $('#search').focus();
            }
        });

        // Button click handlers for individual ledger actions
        $('[id^="viewBtn"]').on('click', function() {
            const btn = $(this);
            const originalHtml = btn.html();
            btn.html('<i class="fas fa-spinner fa-spin"></i>');
            btn.prop('disabled', true);
        });

        $('[id^="editBtn"]').on('click', function() {
            const btn = $(this);
            const originalHtml = btn.html();
            btn.html('<i class="fas fa-spinner fa-spin"></i>');
            btn.prop('disabled', true);
        });

        $('[id^="deleteBtn"]').on('click', function(e) {
            if (!confirm('Are you sure you want to delete this ledger?')) {
                e.preventDefault();
                return false;
            }

            const btn = $(this);
            const originalHtml = btn.html();
            btn.html('<i class="fas fa-spinner fa-spin"></i>');
            btn.prop('disabled', true);
        });

        // Mobile action button handlers
        $('.bg-blue-50, .bg-yellow-50, .bg-red-50').on('click', function() {
            const btn = $(this);
            if (!btn.is('form') && !btn.is('button[type="submit"]')) {
                const icon = btn.find('i').first();
                icon.removeClass().addClass('fas fa-spinner fa-spin text-sm mb-1');
            }
        });

        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        // Focus management for better UX
        const searchInput = document.getElementById('search');
        if (searchInput && searchInput.value === '') {
            searchInput.focus();
        }

        // Responsive table handling
        function handleResponsiveTable() {
            const screenWidth = window.innerWidth;
            const mobileCards = document.querySelector('.lg\\:hidden');
            const desktopTable = document.querySelector('.lg\\:block');

            if (screenWidth < 1024) {
                if (mobileCards) mobileCards.style.display = 'block';
                if (desktopTable) desktopTable.style.display = 'none';
            } else {
                if (mobileCards) mobileCards.style.display = 'none';
                if (desktopTable) desktopTable.style.display = 'block';
            }
        }

        // Handle window resize
        $(window).on('resize', handleResponsiveTable);

        // Initial call
        handleResponsiveTable();

        // Touch-friendly interactions for mobile
        if ('ontouchstart' in window) {
            $('.hover\\:scale-105').addClass('active:scale-95');
        }

        // Smooth scrolling for mobile
        if (window.innerWidth < 768) {
            $('html').css({
                'scroll-behavior': 'smooth',
                '-webkit-overflow-scrolling': 'touch'
            });
        }

        // Performance optimization - lazy load images if any
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        imageObserver.unobserve(img);
                    }
                });
            });

            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        }
    });
</script>
@endpush
@endsection