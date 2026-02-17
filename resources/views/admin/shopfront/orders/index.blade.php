@extends('admin.layouts.app')

@section('content')
@php
use App\Models\BusinessAdmin;
use Illuminate\Support\Facades\Auth;

$user = Auth::user();
$currentAdmin = BusinessAdmin::where('user_id', $user->id)->first();
@endphp

<div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 p-2 sm:p-4">
    <div class="max-w-7xl mx-auto">
        {{-- Enhanced Header --}}
        <div class="bg-white/80 backdrop-blur-sm shadow-xl rounded-2xl overflow-hidden border border-white/20 mb-4">
            <!-- Header Section -->
            <div class="bg-gradient-to-r from-green-600 via-green-700 to-emerald-700 p-4 sm:p-6">
                <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div class="text-center sm:text-left">
                        <h1 class="text-2xl sm:text-3xl font-bold text-white mb-1">
                            <i class="fas fa-shopping-cart mr-2"></i>Shopfront Orders
                        </h1>
                        <p class="text-green-100 text-sm">Manage your shopfront orders and sales</p>
                    </div>

                    <a href="{{ route('admin.shopfront.index') }}"
                        class="group relative overflow-hidden bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white font-bold py-3 px-6 rounded-xl transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl">
                        <div class="absolute inset-0 bg-white/20 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
                        <span class="relative flex items-center">
                            <svg class="w-5 h-5 mr-2 transition-transform group-hover:-rotate-45" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                            </svg>
                            Back to Shopfront
                        </span>
                    </a>
                </div>
            </div>

            <!-- Enhanced Filter Section -->
            <div class="p-4 bg-gradient-to-r from-gray-50 to-blue-50 border-t border-gray-200">
                <form id="filterForm" action="{{ route('admin.shopfront.orders.index') }}" method="GET"
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">

                    <!-- Date Filters -->
                    <div class="relative">
                        <input type="date" id="start_date" name="start_date" value="{{ request('start_date') }}"
                            class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent text-sm">
                        <i class="fas fa-calendar-alt absolute left-3 top-3 text-gray-400 text-sm"></i>
                    </div>

                    <div class="relative">
                        <input type="date" id="end_date" name="end_date" value="{{ request('end_date') }}"
                            class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent text-sm">
                        <i class="fas fa-calendar-alt absolute left-3 top-3 text-gray-400 text-sm"></i>
                    </div>

                    <!-- Phone Search -->
                    <div class="relative">
                        <input type="text" id="phone" name="phone" value="{{ request('phone') }}"
                            placeholder="Customer Phone..."
                            class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent text-sm">
                        <i class="fas fa-phone absolute left-3 top-3 text-gray-400 text-sm"></i>
                    </div>

                    <!-- Invoice Search -->
                    <div class="relative">
                        <input type="text" id="invoice" name="invoice" value="{{ request('invoice') }}"
                            placeholder="Invoice Number..."
                            class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent text-sm">
                        <i class="fas fa-receipt absolute left-3 top-3 text-gray-400 text-sm"></i>
                    </div>

                    <!-- Status Filter -->
                    <div class="relative">
                        <select id="status_filter" name="status"
                            class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent text-sm">
                            <option value="">All Status</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>Processing</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="due" {{ request('status') == 'due' ? 'selected' : '' }}>Due</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                        <i class="fas fa-filter absolute left-3 top-3 text-gray-400 text-sm"></i>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex gap-2">
                        <button type="submit" id="searchBtn"
                            class="flex-1 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-medium py-2.5 px-4 rounded-lg transition-all duration-300 transform hover:scale-105 shadow-md text-sm">
                            <i class="fas fa-search mr-1"></i>
                            <span class="hidden sm:inline">Search</span>
                        </button>

                        <a href="{{ route('admin.shopfront.orders.index') }}"
                            class="flex-1 bg-gradient-to-r from-gray-500 to-gray-600 hover:from-gray-600 hover:to-gray-700 text-white font-medium py-2.5 px-4 rounded-lg transition-all duration-300 transform hover:scale-105 shadow-md text-sm text-center">
                            <i class="fas fa-undo mr-1"></i>
                            <span class="hidden sm:inline">Reset</span>
                        </a>
                    </div>
                </form>

                <!-- Quick Filter Buttons -->
                <div class="mt-3 flex flex-wrap gap-2">
                    <button onclick="quickFilter('due')"
                        class="quick-filter-btn px-3 py-1 text-xs rounded-full border transition-all duration-300 
                        {{ request('status') == 'due' ? 'bg-orange-100 text-orange-800 border-orange-300' : 'bg-white text-gray-600 border-gray-300 hover:bg-orange-50' }}">
                        <i class="fas fa-exclamation-triangle mr-1"></i>Due Orders
                    </button>
                    <button onclick="quickFilter('completed')"
                        class="quick-filter-btn px-3 py-1 text-xs rounded-full border transition-all duration-300 
                        {{ request('status') == 'completed' ? 'bg-green-100 text-green-800 border-green-300' : 'bg-white text-gray-600 border-gray-300 hover:bg-green-50' }}">
                        <i class="fas fa-check-circle mr-1"></i>Completed
                    </button>
                    <button onclick="quickFilter('pending')"
                        class="quick-filter-btn px-3 py-1 text-xs rounded-full border transition-all duration-300 
                        {{ request('status') == 'pending' ? 'bg-yellow-100 text-yellow-800 border-yellow-300' : 'bg-white text-gray-600 border-gray-300 hover:bg-yellow-50' }}">
                        <i class="fas fa-clock mr-1"></i>Pending
                    </button>
                    <button onclick="quickFilter('')"
                        class="quick-filter-btn px-3 py-1 text-xs rounded-full border transition-all duration-300 
                        {{ !request('status') ? 'bg-blue-100 text-blue-800 border-blue-300' : 'bg-white text-gray-600 border-gray-300 hover:bg-blue-50' }}">
                        <i class="fas fa-list mr-1"></i>All Orders
                    </button>
                </div>
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

        {{-- Error Message --}}
        @if(session('error'))
        <div class="bg-gradient-to-r from-red-50 to-pink-50 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded-r-xl shadow-lg" role="alert">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle text-red-500 mr-3 text-lg"></i>
                <span class="font-medium">{{ session('error') }}</span>
            </div>
        </div>
        @endif

        {{-- Compact Mobile Cards --}}
        <div class="block lg:hidden space-y-2">
            @forelse($orders as $order)
            <div class="bg-white/90 backdrop-blur-sm rounded-lg shadow-md border border-white/20 overflow-hidden hover:shadow-lg transition-all duration-300">
                <!-- Compact Header -->
                <div class="bg-gradient-to-r from-slate-50 to-green-50 px-3 py-2 border-b border-gray-100">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center space-x-2">
                            <div class="w-8 h-8 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg flex items-center justify-center">
                                <i class="fas fa-shopping-cart text-white text-xs"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 text-xs">{{ $order->order_number }}</h3>
                                <p class="text-xs text-gray-500">{{ $order->created_at->format('M d') }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-bold text-gray-900">৳{{ number_format($order->total_amount, 0) }}</div>
                            <span class="px-2 py-1 inline-flex text-xs leading-4 font-semibold rounded-full 
                                @if($order->status == 'completed') bg-green-100 text-green-800 
                                @elseif($order->status == 'processing') bg-blue-100 text-blue-800 
                                @elseif($order->status == 'due') bg-orange-100 text-orange-800 
                                @elseif($order->status == 'cancelled') bg-red-100 text-red-800 
                                @else bg-yellow-100 text-yellow-800 @endif">
                                {{ ucfirst($order->status) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Compact Body -->
                <div class="p-3">
                    <!-- Customer Info -->
                    <div class="mb-3">
                        <div class="text-sm font-medium text-gray-700 truncate">{{ $order->customer_name }}</div>
                        <div class="text-xs text-gray-500">{{ $order->customer_phone }}</div>
                        <div class="text-xs text-gray-500">Invoice: {{ $order->invoice_number }}</div>
                    </div>

                    <!-- Status Update Dropdown -->
                    <div class="mb-3">
                        <form action="{{ route('admin.shopfront.orders.status', $order) }}" method="POST" class="status-form">
                            @csrf
                            <select name="status" class="status-select w-full text-xs border border-gray-300 rounded-md py-1 px-2 focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                data-current-status="{{ $order->status }}">
                                <option value="pending" {{ $order->status == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="processing" {{ $order->status == 'processing' ? 'selected' : '' }}>Processing</option>
                                <option value="completed" {{ $order->status == 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="due" {{ $order->status == 'due' ? 'selected' : '' }}>Due</option>
                                <option value="cancelled" {{ $order->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </form>
                    </div>

                    <!-- Compact Action Buttons -->
                    <div class="grid grid-cols-3 gap-1">
                        <!-- View Button -->
                        <a href="{{ route('admin.shopfront.orders.show', $order) }}"
                            class="flex flex-col items-center justify-center p-2 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition-all duration-300">
                            <i class="fas fa-eye text-xs mb-1"></i>
                            <span class="text-xs">View</span>
                        </a>

                        <!-- Print Button -->
                        <a href="{{ route('admin.shopfront.orders.print', $order) }}" target="_blank"
                            class="flex flex-col items-center justify-center p-2 rounded-lg bg-green-50 text-green-600 hover:bg-green-100 transition-all duration-300">
                            <i class="fas fa-print text-xs mb-1"></i>
                            <span class="text-xs">Print</span>
                        </a>

                        <!-- Delete Button -->
                        <button class="delete-btn flex flex-col items-center justify-center p-2 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition-all duration-300"
                            data-id="{{ $order->id }}"
                            data-order-number="{{ $order->order_number }}">
                            <i class="fas fa-trash-alt text-xs mb-1"></i>
                            <span class="text-xs">Delete</span>
                        </button>
                    </div>
                </div>
            </div>
            @empty
            <div class="bg-white/90 backdrop-blur-sm rounded-xl shadow-lg p-8 text-center">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-shopping-cart text-gray-400 text-2xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No orders found</h3>
                <p class="text-gray-500">Try adjusting your search filters or check back later for new orders.</p>
            </div>
            @endforelse
        </div>

        {{-- Desktop Table --}}
        <div class="hidden lg:block bg-white/90 backdrop-blur-sm rounded-xl shadow-lg border border-white/20 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gradient-to-r from-gray-50 to-green-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <i class="fas fa-hashtag mr-2"></i>Order Details
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
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <i class="fas fa-info-circle mr-2"></i>Status
                            </th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <i class="fas fa-cogs mr-2"></i>Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($orders as $order)
                        <tr class="hover:bg-gradient-to-r hover:from-green-50 hover:to-emerald-50 transition-all duration-300">
                            <!-- Order Details Column -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center mr-3">
                                        <i class="fas fa-shopping-cart text-white text-xs"></i>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ $order->order_number }}</div>
                                        <div class="text-xs text-gray-500">Invoice: {{ $order->invoice_number }}</div>
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
                                        <div class="text-sm font-medium text-gray-900">{{ $order->customer_name }}</div>
                                        <div class="text-xs text-gray-500">{{ $order->customer_phone }}</div>
                                        @if($order->location)
                                        <div class="text-xs text-gray-500">{{ $order->location }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- Date Column -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $order->created_at->format('M d, Y') }}</div>
                                <div class="text-xs text-gray-500">{{ $order->created_at->format('h:i A') }}</div>
                            </td>

                            <!-- Amount Column -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-lg font-bold text-gray-900">৳{{ number_format($order->total_amount, 2) }}</div>
                                <div class="text-xs text-gray-500">{{ $order->orderLines->count() }} items</div>
                            </td>

                            <!-- Status Column -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <form action="{{ route('admin.shopfront.orders.status', $order) }}" method="POST" class="status-form">
                                    @csrf
                                    <select name="status" class="status-select text-sm border border-gray-300 rounded-md py-1 px-2 focus:ring-2 focus:ring-green-500 focus:border-transparent
                                        @if($order->status == 'completed') bg-green-50 text-green-800 border-green-200
                                        @elseif($order->status == 'processing') bg-blue-50 text-blue-800 border-blue-200
                                        @elseif($order->status == 'due') bg-orange-50 text-orange-800 border-orange-200
                                        @elseif($order->status == 'cancelled') bg-red-50 text-red-800 border-red-200
                                        @else bg-yellow-50 text-yellow-800 border-yellow-200 @endif"
                                        data-current-status="{{ $order->status }}">
                                        <option value="pending" {{ $order->status == 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="processing" {{ $order->status == 'processing' ? 'selected' : '' }}>Processing</option>
                                        <option value="completed" {{ $order->status == 'completed' ? 'selected' : '' }}>Completed</option>
                                        <option value="due" {{ $order->status == 'due' ? 'selected' : '' }}>Due</option>
                                        <option value="cancelled" {{ $order->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                    </select>
                                </form>
                            </td>

                            <!-- Actions Column -->
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex justify-center space-x-2">
                                    <!-- View Button -->
                                    <a href="{{ route('admin.shopfront.orders.show', $order) }}"
                                        class="p-2 rounded-lg text-blue-600 bg-blue-50 hover:bg-blue-100 border border-blue-200 transition-all duration-300"
                                        title="View Order">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                                        </svg>
                                    </a>

                                    <!-- Print Button -->
                                    <a href="{{ route('admin.shopfront.orders.print', $order) }}" target="_blank"
                                        class="p-2 rounded-lg text-green-600 bg-green-50 hover:bg-green-100 border border-green-200 transition-all duration-300"
                                        title="Print Order">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zM5 14H4v-3h1v3zm1 0v2h8v-2H6zm10 0v-3h1v3h-1z" clip-rule="evenodd" />
                                        </svg>
                                    </a>

                                    <!-- Delete Button -->
                                    <button class="delete-btn p-2 rounded-lg text-red-600 bg-red-50 hover:bg-red-100 border border-red-200 transition-all duration-300"
                                        data-id="{{ $order->id }}"
                                        data-order-number="{{ $order->order_number }}"
                                        title="Delete Order">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-shopping-cart text-gray-400 text-2xl"></i>
                                </div>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">No orders found</h3>
                                <p class="text-gray-500">Try adjusting your search filters or check back later for new orders.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pagination --}}
        @if($orders->hasPages())
        <div class="mt-6 flex justify-center">
            {{ $orders->links('vendor.pagination.tailwind') }}
        </div>
        @endif
    </div>
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

    /* Glassmorphism effect */
    .bg-white\/90 {
        background: rgba(255, 255, 255, 0.9);
    }

    .bg-white\/80 {
        background: rgba(255, 255, 255, 0.8);
    }

    /* Status select styling */
    .status-select {
        transition: all 0.3s ease;
    }

    .status-select:focus {
        transform: scale(1.02);
    }

    /* Quick filter button animations */
    .quick-filter-btn {
        transition: all 0.3s ease;
    }

    .quick-filter-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        // Enhanced button loading states
        $('#searchBtn').on('click', function() {
            const btn = $(this);
            const originalHtml = btn.html();

            btn.html('<i class="fas fa-spinner fa-spin mr-1"></i><span class="hidden sm:inline">Searching...</span>');
            btn.prop('disabled', true);
        });

        // Auto-hide success/error messages
        setTimeout(function() {
            $('.bg-gradient-to-r.from-green-50, .bg-gradient-to-r.from-red-50').fadeOut('slow');
        }, 5000);

        // Enhanced search functionality with debounce
        let searchTimeout;
        $('#phone, #invoice').on('input', function() {
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
        $('#start_date, #end_date, #status_filter').on('change', function() {
            $('#filterForm').submit();
        });

        // Status update functionality with proper form submission
        $('.status-select').on('change', function() {
            const select = $(this);
            const form = select.closest('.status-form');
            const currentStatus = select.data('current-status');
            const newStatus = select.val();

            // Update the data attribute immediately to prevent confusion
            select.data('current-status', newStatus);

            if (currentStatus !== newStatus) {
                Swal.fire({
                    title: 'Update Order Status?',
                    text: `Change status from "${currentStatus}" to "${newStatus}"?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#10b981',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Yes, update it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show loading
                        Swal.fire({
                            title: 'Updating Status...',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            showConfirmButton: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        // Submit form directly instead of AJAX
                        form.submit();

                    } else {
                        // Revert the select value if cancelled
                        select.val(currentStatus);
                        select.data('current-status', currentStatus);
                    }
                });
            }
        });


        // Delete functionality
        $('.delete-btn').on('click', function() {
            const orderId = $(this).data('id');
            const orderNumber = $(this).data('order-number');

            console.log('Delete button clicked:', {
                orderId,
                orderNumber
            });

            Swal.fire({
                title: 'Delete Order?',
                text: `Are you sure you want to delete order ${orderNumber}? This action cannot be undone.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading state
                    Swal.fire({
                        title: 'Deleting...',
                        text: 'Please wait while we delete the order.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Create and submit delete form with correct route
                    const form = $('<form>', {
                        'method': 'POST',
                        'action': `{{ route('admin.shopfront.orders.index') }}/${orderId}`
                    });

                    form.append($('<input>', {
                        'type': 'hidden',
                        'name': '_token',
                        'value': $('meta[name="csrf-token"]').attr('content')
                    }));

                    form.append($('<input>', {
                        'type': 'hidden',
                        'name': '_method',
                        'value': 'DELETE'
                    }));

                    console.log('Delete form created:', form[0].outerHTML);

                    $('body').append(form);
                    form.submit();
                }
            });
        });

        // Keyboard shortcuts
        $(document).keydown(function(e) {
            // Ctrl/Cmd + K to focus search
            if ((e.ctrlKey || e.metaKey) && e.keyCode === 75) {
                e.preventDefault();
                $('#phone').focus();
            }

            // Escape to clear search
            if (e.keyCode === 27) {
                $('#phone, #invoice').val('');
                $('#filterForm').submit();
            }
        });

        // Enhanced form validation
        $('#filterForm').on('submit', function() {
            const startDate = $('#start_date').val();
            const endDate = $('#end_date').val();

            if (startDate && endDate && new Date(startDate) > new Date(endDate)) {
                Swal.fire({
                    title: 'Invalid Date Range',
                    text: 'Start date cannot be later than end date.',
                    icon: 'error',
                    confirmButtonColor: '#10b981'
                });
                return false;
            }
        });

        // Enhanced status color updates
        $('.status-select').each(function() {
            updateStatusColors($(this));
        });

        function updateStatusColors(select) {
            const status = select.val();
            select.removeClass('bg-green-50 text-green-800 border-green-200 bg-blue-50 text-blue-800 border-blue-200 bg-orange-50 text-orange-800 border-orange-200 bg-red-50 text-red-800 border-red-200 bg-yellow-50 text-yellow-800 border-yellow-200');

            switch (status) {
                case 'completed':
                    select.addClass('bg-green-50 text-green-800 border-green-200');
                    break;
                case 'processing':
                    select.addClass('bg-blue-50 text-blue-800 border-blue-200');
                    break;
                case 'due':
                    select.addClass('bg-orange-50 text-orange-800 border-orange-200');
                    break;
                case 'cancelled':
                    select.addClass('bg-red-50 text-red-800 border-red-200');
                    break;
                default:
                    select.addClass('bg-yellow-50 text-yellow-800 border-yellow-200');
            }
        }

        // Update colors when status changes
        $('.status-select').on('change', function() {
            updateStatusColors($(this));
        });

        // Print functionality enhancement
        $('a[href*="print"]').on('click', function(e) {
            const link = $(this);
            const originalHtml = link.html();

            link.html('<i class="fas fa-spinner fa-spin text-xs mb-1"></i><span class="text-xs">Printing...</span>');

            setTimeout(() => {
                link.html(originalHtml);
            }, 2000);
        });

        // Enhanced mobile responsiveness
        const handleResize = () => {
            if ($(window).width() < 768) {
                // Mobile specific enhancements
                $('.status-select').addClass('text-xs');
            } else {
                $('.status-select').removeClass('text-xs');
            }
        };

        $(window).on('resize', handleResize);
        handleResize(); // Initial call

        // Loading states for all buttons
        $('button[type="submit"], a[href]').on('click', function() {
            const element = $(this);
            if (!element.hasClass('delete-btn') && !element.hasClass('status-select')) {
                element.prop('disabled', true);
                setTimeout(() => {
                    element.prop('disabled', false);
                }, 3000);
            }
        });

        // Smooth scroll to top functionality
        const scrollToTop = () => {
            $('html, body').animate({
                scrollTop: 0
            }, 600);
        };

        // Add scroll to top button
        $(window).scroll(function() {
            if ($(this).scrollTop() > 300) {
                if (!$('#scrollToTop').length) {
                    $('body').append(`
                        <button id="scrollToTop" class="fixed bottom-6 right-6 bg-green-600 hover:bg-green-700 text-white p-3 rounded-full shadow-lg transition-all duration-300 transform hover:scale-110 z-50">
                            <i class="fas fa-arrow-up"></i>
                        </button>
                    `);

                    $('#scrollToTop').on('click', scrollToTop);
                }
            } else {
                $('#scrollToTop').remove();
            }
        });
    });

    // Quick filter function
    function quickFilter(status) {
        $('#status_filter').val(status);
        $('#filterForm').submit();
    }
</script>

@endpush
@endsection