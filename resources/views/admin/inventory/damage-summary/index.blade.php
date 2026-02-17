@extends('admin.layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-indigo-50">
    <!-- Header Section -->
    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-3 sm:space-y-0">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 flex items-center">
                        <svg class="w-8 h-8 text-red-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        Damage Summary
                    </h1>
                    <p class="text-sm text-gray-600 mt-1">{{ $business->name }}</p>
                </div>

                <!-- Quick Stats Cards - Mobile Optimized -->
                <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-4">
                    <div class="bg-gradient-to-r from-red-500 to-red-600 rounded-lg px-4 py-3 text-white shadow-lg">
                        <div class="text-xs font-medium opacity-90">Total Damage</div>
                        <div class="text-lg sm:text-xl font-bold">৳{{ number_format($totalDamage, 0) }}</div>
                    </div>
                    <div class="bg-gradient-to-r from-gray-500 to-gray-600 rounded-lg px-4 py-3 text-white shadow-lg">
                        <div class="text-xs font-medium opacity-90">Total Records</div>
                        <div class="text-lg sm:text-xl font-bold">{{ number_format($totalInvoices) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                    <svg class="w-5 h-5 text-gray-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.707A1 1 0 013 7V4z"></path>
                    </svg>
                    Date Filter
                </h2>
            </div>

            <div class="p-6">
                <form action="{{ route('admin.inventory.damage-summary') }}" method="GET" class="space-y-4">
                    <!-- Date Range Section -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Start Date</label>
                            <div class="relative">
                                <input type="date" name="start_date" value="{{ $startDate }}"
                                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-colors duration-200">
                                <svg class="absolute left-3 top-3.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">End Date</label>
                            <div class="relative">
                                <input type="date" name="end_date" value="{{ $endDate }}"
                                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-colors duration-200">
                                <svg class="absolute left-3 top-3.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                        </div>

                        <!-- Quick Date Buttons -->
                        <div class="space-y-2 sm:col-span-2 lg:col-span-1">
                            <label class="block text-sm font-medium text-gray-700">Quick Select</label>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" onclick="setDateRange('yesterday')"
                                    class="px-3 py-2 text-xs font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition-colors duration-200">
                                    Yesterday
                                </button>
                                <button type="button" onclick="setDateRange('today')"
                                    class="px-3 py-2 text-xs font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors duration-200">
                                    Today
                                </button>
                                <button type="button" onclick="setDateRange('month')"
                                    class="px-3 py-2 text-xs font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition-colors duration-200">
                                    This Month
                                </button>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Actions</label>
                            <div class="flex flex-col sm:flex-row gap-2">
                                <button type="submit"
                                    class="flex-1 bg-gradient-to-r from-red-600 to-red-700 text-white px-4 py-3 rounded-lg hover:from-red-700 hover:to-red-800 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-all duration-200 font-medium text-sm flex items-center justify-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.707A1 1 0 013 7V4z"></path>
                                    </svg>
                                    Filter
                                </button>
                                <a href="{{ route('admin.inventory.damage-summary') }}"
                                    class="flex-1 bg-gray-500 text-white px-4 py-3 rounded-lg hover:bg-gray-600 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all duration-200 font-medium text-sm flex items-center justify-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                    Reset
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Date Range Display -->
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="text-sm font-medium text-red-800">
                                Showing damage data from {{ Carbon\Carbon::parse($startDate)->format('M d, Y') }} to {{ Carbon\Carbon::parse($endDate)->format('M d, Y') }}
                            </span>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Categories Grid -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-8">
        @if(count($categories) > 0 && collect($categories)->where('products_count', '>', 0)->count() > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @foreach($categories as $category)
            @if($category['products_count'] > 0)
            @php
            $percentage = $totalDamage > 0 ? ($category['total_damage'] / $totalDamage) * 100 : 0;
            $progressWidth = number_format($percentage, 1);
            @endphp
            <a href="{{ route('admin.inventory.category-damage-summary', $category['id']) }}?start_date={{ $startDate }}&end_date={{ $endDate }}"
                class="group bg-white rounded-xl shadow-lg border border-gray-200 hover:shadow-xl hover:border-red-300 transition-all duration-300 transform hover:-translate-y-1 overflow-hidden">

                <!-- Category Header -->
                <div class="bg-gradient-to-r from-red-500 to-red-600 p-4 group-hover:from-red-600 group-hover:to-red-700">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold text-white truncate">{{ $category['name'] }}</h3>
                        <svg class="w-6 h-6 text-red-200 group-hover:text-white transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </div>

                <!-- Category Stats -->
                <div class="p-6 space-y-4">
                    <!-- Damage Amount -->
                    <div class="text-center">
                        <div class="text-3xl font-bold text-red-600 mb-1">
                            ৳{{ number_format($category['total_damage'], 0) }}
                        </div>
                        <div class="text-sm text-gray-500">Total Damage</div>
                    </div>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-100">
                        <div class="text-center">
                            <div class="text-xl font-semibold text-gray-800">{{ $category['products_count'] }}</div>
                            <div class="text-xs text-gray-500">Products</div>
                        </div>
                        <div class="text-center">
                            <div class="text-xl font-semibold text-gray-800">{{ $category['invoices_count'] }}</div>
                            <div class="text-xs text-gray-500">Records</div>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="pt-2">
                        <div class="flex justify-between text-xs text-gray-500 mb-1">
                            <span>Share of Total</span>
                            <span>{{ $progressWidth }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-gradient-to-r from-red-500 to-red-600 h-2 rounded-full progress-bar"
                                data-width="{{ $progressWidth }}"></div>
                        </div>
                    </div>
                </div>
            </a>
            @endif
            @endforeach
        </div>
        @else
        <!-- Empty State -->
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-12 text-center">
            <svg class="w-24 h-24 text-gray-300 mx-auto mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <h3 class="text-xl font-semibold text-gray-600 mb-2">No Damage Data Found</h3>
            <p class="text-gray-500 mb-6">No damage records were found for the selected date range.</p>
            <button onclick="setDateRange('month')"
                class="bg-red-600 text-white px-6 py-3 rounded-lg hover:bg-red-700 transition-colors duration-200 font-medium">
                Try This Month
            </button>
        </div>
        @endif
    </div>
</div>

<script>
    // Set progress bar widths after page load
    document.addEventListener('DOMContentLoaded', function() {
        const progressBars = document.querySelectorAll('.progress-bar');
        progressBars.forEach(function(bar) {
            const width = bar.getAttribute('data-width');
            bar.style.width = width + '%';
        });
    });

    function setDateRange(period) {
        const startDateInput = document.querySelector('input[name="start_date"]');
        const endDateInput = document.querySelector('input[name="end_date"]');
        const today = new Date();

        let startDate, endDate;

        switch (period) {
            case 'yesterday':
                const yesterday = new Date(today);
                yesterday.setDate(yesterday.getDate() - 1);
                startDate = endDate = yesterday;
                break;
            case 'today':
                startDate = endDate = today;
                break;
            case 'month':
                startDate = new Date(today.getFullYear(), today.getMonth(), 1);
                endDate = new Date();
                break;
        }

        startDateInput.value = startDate.toISOString().split('T')[0];
        endDateInput.value = endDate.toISOString().split('T')[0];
    }
</script>
@endsection