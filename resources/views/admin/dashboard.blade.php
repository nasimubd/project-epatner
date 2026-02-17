@extends('admin.layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Date Filter Section -->
    <div class="mb-6 bg-white rounded-lg shadow-md p-4 transition-all hover:shadow-lg">
        <form action="{{ route('admin.dashboard') }}" method="GET" id="dashboardForm">
            <div class="flex flex-col gap-3">
                <!-- Date Range Picker -->
                <div class="flex flex-wrap items-center gap-2">
                    <div class="flex-grow min-w-[240px]">
                        <div class="relative">
                            <input type="text"
                                id="dateRangePicker"
                                name="date_range"
                                value="{{ request('start_date', now()->startOfMonth()->format('Y-m-d')) }} to {{ request('end_date', now()->format('Y-m-d')) }}"
                                class="w-full rounded-md border-gray-300 pl-10 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-colors"
                                readonly>
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                        </div>

                        <!-- Hidden inputs to store the actual date values -->
                        <input type="hidden" id="start_date" name="start_date" value="{{ request('start_date', now()->startOfMonth()->format('Y-m-d')) }}">
                        <input type="hidden" id="end_date" name="end_date" value="{{ request('end_date', now()->format('Y-m-d')) }}">
                    </div>

                    <!-- Quick Filter Buttons -->
                    <div class="flex flex-wrap gap-1.5">
                        <button type="button" id="todayBtn"
                            class="px-3 py-1.5 text-xs font-medium rounded-md bg-gradient-to-b from-white to-gray-100 border border-gray-300 shadow-sm hover:shadow transform transition hover:-translate-y-0.5 hover:border-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-300">
                            Today
                        </button>
                        <button type="button" id="yesterdayBtn"
                            class="px-3 py-1.5 text-xs font-medium rounded-md bg-gradient-to-b from-white to-gray-100 border border-gray-300 shadow-sm hover:shadow transform transition hover:-translate-y-0.5 hover:border-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-300">
                            Yesterday
                        </button>
                        <button type="button" id="last7DaysBtn"
                            class="px-3 py-1.5 text-xs font-medium rounded-md bg-gradient-to-b from-white to-gray-100 border border-gray-300 shadow-sm hover:shadow transform transition hover:-translate-y-0.5 hover:border-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-300">
                            Last 7 Days
                        </button>
                        <button type="button" id="thisMonthBtn"
                            class="px-3 py-1.5 text-xs font-medium rounded-md bg-gradient-to-b from-white to-gray-100 border border-gray-300 shadow-sm hover:shadow transform transition hover:-translate-y-0.5 hover:border-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-300">
                            This Month
                        </button>
                        <button type="button" id="thisYearBtn"
                            class="px-3 py-1.5 text-xs font-medium rounded-md bg-gradient-to-b from-white to-gray-100 border border-gray-300 shadow-sm hover:shadow transform transition hover:-translate-y-0.5 hover:border-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-300">
                            This Year
                        </button>
                        <button type="submit"
                            class="px-4 py-1.5 text-xs font-medium rounded-md bg-gradient-to-b from-blue-500 to-blue-600 text-white border border-blue-600 shadow-sm hover:shadow-md transform transition hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-blue-300">
                            <span class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                </svg>
                                Filter
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    @role('admin')
    <!-- Transaction Analytics Section -->
    <div class="mb-8">
        <div class="flex items-center mb-4">
            <div class="flex items-center">
                <div class="w-4 h-4 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full mr-3"></div>
                <h2 class="text-xl font-bold text-gray-800">📊 TRANSACTION ANALYTICS</h2>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <!-- Total Sales Card - Make it clickable -->
            <div id="totalSalesCard" class="dashboard-card bg-gradient-to-br from-white to-green-50 rounded-xl shadow-sm p-5 border border-gray-100 hover:shadow-md transition-all duration-300 transform hover:-translate-y-1 cursor-pointer"
                data-type="sales" data-title="Sales Breakdown">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Total Sales</h3>
                        <p id="totalSales" class="text-2xl font-bold text-green-600 mt-1">৳{{ number_format($totalSales) }}</p>
                    </div>
                </div>
            </div>

            <!-- Total Purchase Card - Make it clickable -->
            <div class="dashboard-card bg-gradient-to-br from-white to-blue-50 rounded-xl shadow-sm p-5 border border-gray-100 hover:shadow-md transition-all duration-300 transform hover:-translate-y-1 cursor-pointer"
                data-type="purchase" data-title="Purchase Breakdown">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Total Purchase</h3>
                        <p id="totalPurchase" class="text-2xl font-bold text-blue-600 mt-1">৳{{ number_format($totalPurchase) }}</p>
                    </div>
                </div>
            </div>

            <!-- Sales Return Card - Make it clickable -->
            <div class="dashboard-card bg-gradient-to-br from-white to-yellow-50 rounded-xl shadow-sm p-5 border border-gray-100 hover:shadow-md transition-all duration-300 transform hover:-translate-y-1 cursor-pointer"
                data-type="sales-return" data-title="Sales Return Breakdown">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Sales Return</h3>
                        <p id="salesReturn" class="text-2xl font-bold text-yellow-600 mt-1">৳{{ number_format($salesReturn) }}</p>
                    </div>
                </div>
            </div>

            <!-- Damage Return Card - Make it clickable -->
            <div class="dashboard-card bg-gradient-to-br from-white to-red-50 rounded-xl shadow-sm p-5 border border-gray-100 hover:shadow-md transition-all duration-300 transform hover:-translate-y-1 cursor-pointer"
                data-type="damage-return" data-title="Damage Return Breakdown">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-red-100 text-red-600">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Damage Return</h3>
                        <p id="damageReturn" class="text-2xl font-bold text-red-600 mt-1">৳{{ number_format($damageReturn) }}</p>
                    </div>
                </div>
            </div>

            <!-- Stock in Hand -->
            <div class="dashboard-card bg-gradient-to-br from-white to-purple-50 rounded-xl shadow-sm p-5 border border-gray-100 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1 cursor-pointer"
                data-type="stock" data-title="Stock Breakdown">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Stock in Hand</h3>
                        <p class="text-2xl font-bold text-purple-600 mt-1">৳{{ number_format($stockInHand) }}</p>
                        <div class="flex items-center mt-1">
                            <span class="text-xs text-gray-500">Click for breakdown</span>
                            <svg class="w-3 h-3 ml-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- UPDATED: DSR Collection -->
            <div class="dashboard-card bg-gradient-to-br from-white to-indigo-50 rounded-xl shadow-sm p-5 border border-gray-100 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1 cursor-pointer"
                data-type="collection" data-title="DSR Collection Breakdown">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-indigo-100 text-indigo-600">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wider">DSR Collections</h3>
                        <p class="text-2xl font-bold text-indigo-600 mt-1">৳{{ number_format($totalCollection ?? 0) }}</p>
                        <div class="flex items-center mt-1">
                            <span class="text-xs text-gray-500">DSR ledger balances</span>
                            <svg class="w-3 h-3 ml-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Financial Balance Section -->
    <div class="mb-8">
        <div class="flex items-center mb-4">
            <div class="flex items-center">
                <div class="w-4 h-4 bg-gradient-to-r from-green-500 to-blue-600 rounded-full mr-3"></div>
                <h2 class="text-xl font-bold text-gray-800">💰 FINANCIAL BALANCES</h2>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <!-- Cash in Hand -->
            <div class="balance-card bg-gradient-to-br from-white to-emerald-50 rounded-xl shadow-sm p-5 border border-gray-100 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1 cursor-pointer"
                data-type="cash" data-title="Cash in Hand Trends">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-emerald-100 text-emerald-600">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Cash in Hand</h3>
                        <p class="text-2xl font-bold text-emerald-600 mt-1">৳{{ number_format($cashInHand) }}</p>
                        <div class="flex items-center mt-1">
                            <span class="text-xs text-gray-500">View trends</span>
                            <svg class="w-3 h-3 ml-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bank Balance -->
            <div class="balance-card bg-gradient-to-br from-white to-indigo-50 rounded-xl shadow-sm p-5 border border-gray-100 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1 cursor-pointer"
                data-type="bank" data-title="Bank Balance Trends">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-indigo-100 text-indigo-600">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Bank Balance</h3>
                        <p class="text-2xl font-bold text-indigo-600 mt-1">৳{{ number_format($bankBalance) }}</p>
                        <div class="flex items-center mt-1">
                            <span class="text-xs text-gray-500">View trends</span>
                            <svg class="w-3 h-3 ml-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Due -->
            <div class="balance-card bg-gradient-to-br from-white to-orange-50 rounded-xl shadow-sm p-5 border border-gray-100 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1 cursor-pointer"
                data-type="customer_due" data-title="Customer Due Trends">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Customer Due</h3>
                        <p class="text-2xl font-bold text-orange-600 mt-1">৳{{ number_format($customerDue) }}</p>
                        <div class="flex items-center mt-1">
                            <span class="text-xs text-gray-500">View trends</span>
                            <svg class="w-3 h-3 ml-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Supplier Due -->
            <div class="balance-card bg-gradient-to-br from-white to-pink-50 rounded-xl shadow-sm p-5 border border-gray-100 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1 cursor-pointer"
                data-type="supplier_due" data-title="Supplier Due Trends">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-pink-100 text-pink-600">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Supplier Due</h3>
                        <p class="text-2xl font-bold text-pink-600 mt-1">৳{{ number_format($supplierDue) }}</p>
                        <div class="flex items-center mt-1">
                            <span class="text-xs text-gray-500">View trends</span>
                            <svg class="w-3 h-3 ml-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Income -->
            <div class="balance-card bg-gradient-to-br from-white to-green-50 rounded-xl shadow-sm p-5 border border-gray-100 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1 cursor-pointer"
                data-type="income" data-title="Total Income Trends">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Total Income</h3>
                        <p class="text-2xl font-bold text-green-600 mt-1">৳{{ number_format($totalIncome) }}</p>
                        <div class="flex items-center mt-1">
                            <span class="text-xs text-gray-500">View trends</span>
                            <svg class="w-3 h-3 ml-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Expense -->
            <div class="balance-card bg-gradient-to-br from-white to-red-50 rounded-xl shadow-sm p-5 border border-gray-100 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1 cursor-pointer"
                data-type="expense" data-title="Total Expense Trends">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-red-100 text-red-600">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Total Expense</h3>
                        <p class="text-2xl font-bold text-red-600 mt-1">৳{{ number_format($totalExpense) }}</p>
                        <div class="flex items-center mt-1">
                            <span class="text-xs text-gray-500">View trends</span>
                            <svg class="w-3 h-3 ml-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Net Profit/Loss -->
            <div class="balance-card bg-gradient-to-br from-white to-blue-50 rounded-xl shadow-sm p-5 border border-gray-100 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1 cursor-pointer"
                data-type="profit_loss" data-title="Net Profit/Loss Trends">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Net Profit/Loss</h3>
                        <p class="text-2xl font-bold {{ $netProfitLoss >= 0 ? 'text-blue-600' : 'text-red-600' }} mt-1">
                            ৳{{ number_format($netProfitLoss) }}
                        </p>
                        <div class="flex items-center mt-1">
                            <span class="text-xs text-gray-500">View trends</span>
                            <svg class="w-3 h-3 ml-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Business Overview Section -->
    <div class="mb-8">
        <div class="flex items-center mb-4">
            <div class="flex items-center">
                <div class="w-4 h-4 bg-gradient-to-r from-purple-500 to-pink-600 rounded-full mr-3"></div>
                <h2 class="text-xl font-bold text-gray-800">🏢 BUSINESS OVERVIEW</h2>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
            <!-- Low Stock Products -->
            <div class="bg-gradient-to-br from-white to-yellow-50 rounded-xl shadow-sm p-5 border border-gray-100 hover:shadow-md transition-all duration-300 transform hover:-translate-y-1">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Low Stock Products</h3>
                        <p class="text-2xl font-bold text-yellow-600 mt-1">{{ $lowStockProducts }}</p>
                    </div>
                </div>
            </div>

            <!-- Staff and Partners -->
            <div class="bg-gradient-to-br from-white to-indigo-50 rounded-xl shadow-sm p-5 border border-gray-100 hover:shadow-md transition-all duration-300 transform hover:-translate-y-1">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-indigo-100 text-indigo-600">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Staff & Partners</h3>
                        <p class="text-2xl font-bold text-indigo-600 mt-1">{{ $staffCount + $partnersCount }}</p>
                    </div>
                </div>
            </div>

            <!-- Total Products -->
            <div class="bg-gradient-to-br from-white to-blue-50 rounded-xl shadow-sm p-5 border border-gray-100 hover:shadow-md transition-all duration-300 transform hover:-translate-y-1">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Total Products</h3>
                        <p class="text-2xl font-bold text-blue-600 mt-1">{{ $totalProducts }}</p>
                    </div>
                </div>
            </div>

            <!-- Total Memo -->
            <div class="bg-gradient-to-br from-white to-amber-50 rounded-xl shadow-sm p-5 border border-gray-100 hover:shadow-md transition-all duration-300 transform hover:-translate-y-1">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-amber-100 text-amber-600">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Total Memo</h3>
                        <p class="text-2xl font-bold text-amber-600 mt-1">{{ $totalMemo }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endrole

    @role('staff')
    <!-- Staff Dashboard Cards -->
    <div class="mb-8">
        <div class="flex items-center mb-4">
            <div class="flex items-center">
                <div class="w-4 h-4 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full mr-3"></div>
                <h2 class="text-xl font-bold text-gray-800">📊 MY PERFORMANCE</h2>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Sales Summary -->
            <div class="bg-gradient-to-br from-white to-green-50 rounded-xl shadow-sm p-5 border border-gray-100 hover:shadow-md transition-all duration-300 transform hover:-translate-y-1">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wider">My Sales</h3>
                        <p class="text-2xl font-bold text-green-600 mt-1">৳{{ number_format($salesSummary) }}</p>
                    </div>
                </div>
            </div>

            <!-- Damage Summary -->
            <div class="bg-gradient-to-br from-white to-red-50 rounded-xl shadow-sm p-5 border border-gray-100 hover:shadow-md transition-all duration-300 transform hover:-translate-y-1">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-red-100 text-red-600">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wider">My Damage</h3>
                        <p class="text-2xl font-bold text-red-600 mt-1">৳{{ number_format($damageSummary) }}</p>
                    </div>
                </div>
            </div>

            <!-- Return Summary -->
            <div class="bg-gradient-to-br from-white to-yellow-50 rounded-xl shadow-sm p-5 border border-gray-100 hover:shadow-md transition-all duration-300 transform hover:-translate-y-1">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wider">My Returns</h3>
                        <p class="text-2xl font-bold text-yellow-600 mt-1">৳{{ number_format($returnSummary) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Staff Stock Report -->
    <div class="mb-8">
        <div class="flex items-center mb-4">
            <div class="flex items-center">
                <div class="w-4 h-4 bg-gradient-to-r from-purple-500 to-pink-600 rounded-full mr-3"></div>
                <h2 class="text-xl font-bold text-gray-800">📦 MY ASSIGNED CATEGORIES STOCK</h2>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($stockReport as $stock)
            <div class="bg-gradient-to-br from-white to-purple-50 rounded-xl shadow-sm p-5 border border-gray-100 hover:shadow-md transition-all duration-300 transform hover:-translate-y-1">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wider">{{ $stock->category->name ?? 'Unknown Category' }}</h3>
                        <p class="text-2xl font-bold text-purple-600 mt-1">৳{{ number_format($stock->total_amount) }}</p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endrole
</div>


<!-- Analytics Modal -->
<div id="analyticsModal" class="fixed top-0 left-0 w-screen h-screen bg-gray-600 bg-opacity-50 z-[9999] hidden">
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-[85vw] max-w-4xl max-h-[90vh] overflow-y-auto">
        <div class="bg-white shadow-xl rounded-lg">
            <!-- Modal Header -->
            <div class="flex items-center justify-between p-5 border-b border-gray-200">
                <h3 id="modalTitle" class="text-lg font-semibold text-gray-900">Analytics</h3>
                <button id="closeModal" class="text-gray-400 hover:text-gray-600 transition-colors p-1 rounded-full hover:bg-gray-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Date Range Display -->
            <div id="dateRangeDisplay" class="mx-5 mt-4 p-3 bg-blue-50 rounded-lg hidden">
                <p class="text-sm text-blue-800">
                    <span class="font-medium">Date Range:</span>
                    <span id="displayStartDate"></span> to <span id="displayEndDate"></span>
                </p>
            </div>

            <!-- Loading Spinner -->
            <div id="loadingSpinner" class="flex justify-center items-center py-12 hidden">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                <span class="ml-3 text-gray-600">Loading analytics...</span>
            </div>

            <!-- Modal Content -->
            <div class="p-5">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                    <!-- Chart Container -->
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <h4 class="text-md font-medium text-gray-900 mb-3">Category Breakdown</h4>
                        <div class="relative h-72">
                            <canvas id="analyticsChart" class="hidden"></canvas>
                        </div>
                    </div>

                    <!-- Trend Chart Container -->
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <h4 class="text-md font-medium text-gray-900 mb-3">Trend Analysis</h4>
                        <div class="relative h-72">
                            <canvas id="trendChart" class="hidden"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Category Breakdown List -->
                <div class="mt-6">
                    <h4 class="text-md font-medium text-gray-900 mb-3">Detailed Breakdown</h4>
                    <div id="categoryBreakdown" class="hidden"></div>
                </div>
            </div>
        </div>
    </div>
</div>



<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- Include Flatpickr CSS and JS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Global chart variables
        let analyticsChart = null;
        let trendChart = null;

        // Initialize date picker
        const dateRangePicker = flatpickr("#dateRangePicker", {
            mode: "range",
            dateFormat: "Y-m-d",
            defaultDate: [
                document.getElementById('start_date').value,
                document.getElementById('end_date').value
            ],
            onChange: function(selectedDates, dateStr) {
                if (selectedDates.length === 2) {
                    const startDate = selectedDates[0].toISOString().split('T')[0];
                    const endDate = selectedDates[1].toISOString().split('T')[0];
                    document.getElementById('start_date').value = startDate;
                    document.getElementById('end_date').value = endDate;
                }
            }
        });

        // Quick filter buttons
        document.getElementById('todayBtn').addEventListener('click', function() {
            const today = new Date();
            dateRangePicker.setDate([today, today]);
            document.getElementById('start_date').value = formatDate(today);
            document.getElementById('end_date').value = formatDate(today);
        });

        document.getElementById('yesterdayBtn').addEventListener('click', function() {
            const yesterday = new Date();
            yesterday.setDate(yesterday.getDate() - 1);
            dateRangePicker.setDate([yesterday, yesterday]);
            document.getElementById('start_date').value = formatDate(yesterday);
            document.getElementById('end_date').value = formatDate(yesterday);
        });

        document.getElementById('last7DaysBtn').addEventListener('click', function() {
            const endDate = new Date();
            const startDate = new Date();
            startDate.setDate(endDate.getDate() - 6);
            dateRangePicker.setDate([startDate, endDate]);
            document.getElementById('start_date').value = formatDate(startDate);
            document.getElementById('end_date').value = formatDate(endDate);
        });

        document.getElementById('thisMonthBtn').addEventListener('click', function() {
            const today = new Date();
            const startDate = new Date(today.getFullYear(), today.getMonth(), 1);
            dateRangePicker.setDate([startDate, today]);
            document.getElementById('start_date').value = formatDate(startDate);
            document.getElementById('end_date').value = formatDate(today);
        });

        document.getElementById('thisYearBtn').addEventListener('click', function() {
            const today = new Date();
            const startDate = new Date(today.getFullYear(), 0, 1);
            dateRangePicker.setDate([startDate, today]);
            document.getElementById('start_date').value = formatDate(startDate);
            document.getElementById('end_date').value = formatDate(today);
        });

        // Dashboard card click handlers
        document.querySelectorAll('.dashboard-card').forEach(card => {
            card.addEventListener('click', function() {
                const type = this.dataset.type;
                const title = this.dataset.title;
                openAnalyticsModal(type, title);
            });
        });

        // Balance card click handlers
        document.querySelectorAll('.balance-card').forEach(card => {
            card.addEventListener('click', function() {
                const type = this.dataset.type;
                const title = this.dataset.title;
                openBalanceTrendsModal(type, title);
            });
        });

        function openAnalyticsModal(type, title) {
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('analyticsModal').classList.remove('hidden');

            showLoading();

            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;

            // Show date range
            document.getElementById('displayStartDate').textContent = startDate;
            document.getElementById('displayEndDate').textContent = endDate;
            document.getElementById('dateRangeDisplay').classList.remove('hidden');

            let endpoint = '';
            switch (type) {
                case 'sales':
                    endpoint = '{{ route("admin.dashboard.sales-breakdown") }}';
                    break;
                case 'purchase':
                    endpoint = '{{ route("admin.dashboard.purchase-breakdown") }}';
                    break;
                case 'sales-return':
                    endpoint = '{{ route("admin.dashboard.sales-return-breakdown") }}';
                    break;
                case 'damage-return':
                    endpoint = '{{ route("admin.dashboard.damage-return-breakdown") }}';
                    break;
                case 'collection':
                    endpoint = '{{ route("admin.dashboard.collection-breakdown") }}';
                    break;
                case 'stock':
                    endpoint = '{{ route("admin.dashboard.stock-breakdown") }}';
                    break;
            }

            fetch(`${endpoint}?start_date=${startDate}&end_date=${endDate}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Analytics data received:', data);
                    hideLoading();
                    renderAnalytics(data, type);
                })
                .catch(error => {
                    console.error('Error:', error);
                    hideLoading();
                    document.getElementById('categoryBreakdown').innerHTML =
                        '<p class="text-red-500">Error loading analytics data. Please try again.</p>';
                    document.getElementById('categoryBreakdown').classList.remove('hidden');
                });
        }

        function openBalanceTrendsModal(type, title) {
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('analyticsModal').classList.remove('hidden');

            showLoading();

            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;

            // Show date range
            document.getElementById('displayStartDate').textContent = startDate;
            document.getElementById('displayEndDate').textContent = endDate;
            document.getElementById('dateRangeDisplay').classList.remove('hidden');

            fetch(`{{ route("admin.dashboard.balance-trends") }}?type=${type}&start_date=${startDate}&end_date=${endDate}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Balance trends data received:', data);
                    hideLoading();
                    renderBalanceTrends(data, type);
                })
                .catch(error => {
                    console.error('Error:', error);
                    hideLoading();
                    document.getElementById('categoryBreakdown').innerHTML =
                        '<p class="text-red-500">Error loading balance trends. Please try again.</p>';
                    document.getElementById('categoryBreakdown').classList.remove('hidden');
                });
        }

        function renderAnalytics(data, type) {
            console.log('Rendering analytics for type:', type, 'Data:', data);

            if (data.categories && data.categories.length > 0) {
                renderPieChart(data.categories, type);
                renderCategoryBreakdown(data.categories, type);
            } else {
                document.getElementById('categoryBreakdown').innerHTML =
                    '<p class="text-gray-500">No data available for the selected date range.</p>';
                document.getElementById('categoryBreakdown').classList.remove('hidden');
            }

            if (data.trend_data && data.trend_data.length > 0) {
                renderTrendChart(data.trend_data, type);
            } else {
                // Hide trend chart if no data
                document.getElementById('trendChart').classList.add('hidden');
            }
        }

        function renderBalanceTrends(data, type) {
            console.log('Rendering balance trends for type:', type, 'Data:', data);

            // Show balance trend description
            document.getElementById('categoryBreakdown').innerHTML =
                `<p class="text-gray-600">Balance trend analysis for <strong>${type.replace('_', ' ').toUpperCase()}</strong></p>`;
            document.getElementById('categoryBreakdown').classList.remove('hidden');

            if (data.trend_data && data.trend_data.length > 0) {
                renderBalanceTrendChart(data.trend_data, type);
                // Hide the second chart for balance trends
                document.getElementById('trendChart').classList.add('hidden');
            } else {
                document.getElementById('categoryBreakdown').innerHTML =
                    '<p class="text-gray-500">No trend data available for the selected date range.</p>';
                document.getElementById('analyticsChart').classList.add('hidden');
            }
        }

        function renderPieChart(categories, type) {
            const ctx = document.getElementById('analyticsChart').getContext('2d');

            // Destroy existing chart if it exists
            if (analyticsChart) {
                analyticsChart.destroy();
            }

            const labels = categories.map(cat => cat.name || 'Unknown');
            const data = categories.map(cat => {
                switch (type) {
                    case 'sales':
                        return cat.total_sales || 0;
                    case 'purchase':
                        return cat.total_purchases || 0;
                    case 'sales-return':
                        return cat.total_returns || 0;
                    case 'damage-return':
                        return cat.total_damage || 0;
                    case 'collection':
                        return cat.total_collection || 0;
                    case 'stock':
                        return cat.total_amount || 0;
                    default:
                        return 0;
                }
            });

            const colors = [
                '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40',
                '#FF6384', '#C9CBCF', '#4BC0C0', '#FF6384', '#36A2EB', '#FFCE56'
            ];

            analyticsChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: colors.slice(0, labels.length),
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const value = context.parsed;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                    return `${context.label}: ৳${value.toLocaleString()} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });

            document.getElementById('analyticsChart').classList.remove('hidden');
        }

        function renderTrendChart(trendData, type) {
            const ctx = document.getElementById('trendChart').getContext('2d');

            // Destroy existing chart if it exists
            if (trendChart) {
                trendChart.destroy();
            }

            const labels = trendData.map(item => {
                const date = new Date(item.date);
                return date.toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric'
                });
            });
            const data = trendData.map(item => item.value || 0);

            // Choose color based on type
            let borderColor = '#36A2EB';
            let backgroundColor = 'rgba(54, 162, 235, 0.1)';

            switch (type) {
                case 'sales':
                    borderColor = '#10B981';
                    backgroundColor = 'rgba(16, 185, 129, 0.1)';
                    break;
                case 'purchase':
                    borderColor = '#3B82F6';
                    backgroundColor = 'rgba(59, 130, 246, 0.1)';
                    break;
                case 'sales-return':
                    borderColor = '#F59E0B';
                    backgroundColor = 'rgba(245, 158, 11, 0.1)';
                    break;
                case 'damage-return':
                    borderColor = '#EF4444';
                    backgroundColor = 'rgba(239, 68, 68, 0.1)';
                    break;
                case 'collection':
                    borderColor = '#8B5CF6';
                    backgroundColor = 'rgba(139, 92, 246, 0.1)';
                    break;
            }

            trendChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Daily Amount',
                        data: data,
                        borderColor: borderColor,
                        backgroundColor: backgroundColor,
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: borderColor,
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `Amount: ৳${context.parsed.y.toLocaleString()}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '৳' + value.toLocaleString();
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        }
                    }
                }
            });

            document.getElementById('trendChart').classList.remove('hidden');
        }

        function renderBalanceTrendChart(trendData, type) {
            const ctx = document.getElementById('analyticsChart').getContext('2d');

            // Destroy existing chart if it exists
            if (analyticsChart) {
                analyticsChart.destroy();
            }

            const labels = trendData.map(item => {
                const date = new Date(item.date);
                return date.toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric'
                });
            });
            const data = trendData.map(item => item.value || 0);

            const colorMap = {
                'cash': {
                    border: '#10B981',
                    background: 'rgba(16, 185, 129, 0.1)'
                },
                'bank': {
                    border: '#3B82F6',
                    background: 'rgba(59, 130, 246, 0.1)'
                },
                'customer_due': {
                    border: '#F59E0B',
                    background: 'rgba(245, 158, 11, 0.1)'
                },
                'supplier_due': {
                    border: '#EF4444',
                    background: 'rgba(239, 68, 68, 0.1)'
                },
                'income': {
                    border: '#10B981',
                    background: 'rgba(16, 185, 129, 0.1)'
                },
                'expense': {
                    border: '#EF4444',
                    background: 'rgba(239, 68, 68, 0.1)'
                },
                'profit_loss': {
                    border: '#8B5CF6',
                    background: 'rgba(139, 92, 246, 0.1)'
                }
            };

            const colors = colorMap[type] || {
                border: '#6B7280',
                background: 'rgba(107, 114, 128, 0.1)'
            };

            analyticsChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Balance Trend',
                        data: data,
                        borderColor: colors.border,
                        backgroundColor: colors.background,
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: colors.border,
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `Balance: ৳${context.parsed.y.toLocaleString()}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            ticks: {
                                callback: function(value) {
                                    return '৳' + value.toLocaleString();
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        }
                    }
                }
            });

            document.getElementById('analyticsChart').classList.remove('hidden');
        }

        function renderCategoryBreakdown(categories, type) {
            let html = '<div class="space-y-3">';

            const total = categories.reduce((sum, cat) => {
                switch (type) {
                    case 'sales':
                        return sum + (cat.total_sales || 0);
                    case 'purchase':
                        return sum + (cat.total_purchases || 0);
                    case 'sales-return':
                        return sum + (cat.total_returns || 0);
                    case 'damage-return':
                        return sum + (cat.total_damage || 0);
                    case 'collection':
                        return sum + (cat.total_collection || 0);
                    case 'stock':
                        return sum + (cat.total_amount || 0);
                    default:
                        return sum;
                }
            }, 0);

            categories.forEach((category, index) => {
                let amount = 0;
                let quantity = 0;

                switch (type) {
                    case 'sales':
                        amount = category.total_sales || 0;
                        quantity = category.total_quantity || 0;
                        break;
                    case 'purchase':
                        amount = category.total_purchases || 0;
                        quantity = category.total_quantity || 0;
                        break;
                    case 'sales-return':
                        amount = category.total_returns || 0;
                        quantity = category.total_quantity || 0;
                        break;
                    case 'damage-return':
                        amount = category.total_damage || 0;
                        quantity = category.total_quantity || 0;
                        break;
                    case 'collection':
                        amount = category.total_collection || 0;
                        break;
                    case 'stock':
                        amount = category.total_amount || 0;
                        quantity = category.total_quantity || 0;
                        break;
                }

                const percentage = total > 0 ? ((amount / total) * 100).toFixed(1) : 0;
                const colors = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40'];
                const color = colors[index % colors.length];

                html += `
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <div class="w-4 h-4 rounded-full" style="background-color: ${color}"></div>
                            <div>
                                <p class="font-medium text-gray-900">${category.name || 'Unknown'}</p>
                                ${quantity > 0 ? `<p class="text-sm text-gray-500">Quantity: ${quantity.toLocaleString()}</p>` : ''}
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-gray-900">৳${amount.toLocaleString()}</p>
                            <p class="text-sm text-gray-500">${percentage}%</p>
                        </div>
                    </div>
                `;
            });

            html += '</div>';

            document.getElementById('categoryBreakdown').innerHTML = html;
            document.getElementById('categoryBreakdown').classList.remove('hidden');
        }

        function showLoading() {
            document.getElementById('loadingSpinner').classList.remove('hidden');
            document.getElementById('analyticsChart').classList.add('hidden');
            document.getElementById('trendChart').classList.add('hidden');
            document.getElementById('categoryBreakdown').classList.add('hidden');
            document.getElementById('dateRangeDisplay').classList.add('hidden');
        }

        function hideLoading() {
            document.getElementById('loadingSpinner').classList.add('hidden');
        }

        // Close modal functionality
        document.getElementById('closeModal').addEventListener('click', function() {
            document.getElementById('analyticsModal').classList.add('hidden');
            if (analyticsChart) {
                analyticsChart.destroy();
                analyticsChart = null;
            }
            if (trendChart) {
                trendChart.destroy();
                trendChart = null;
            }
        });

        // Close modal when clicking outside
        document.getElementById('analyticsModal').addEventListener('click', function(e) {
            if (e.target === this) {
                document.getElementById('analyticsModal').classList.add('hidden');
                if (analyticsChart) {
                    analyticsChart.destroy();
                    analyticsChart = null;
                }
                if (trendChart) {
                    trendChart.destroy();
                    trendChart = null;
                }
            }
        });

        // Helper function to format date as YYYY-MM-DD
        function formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        // Real-time updates
        setInterval(function() {
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;

            fetch(`{{ route('admin.dashboard-data') }}?start_date=${startDate}&end_date=${endDate}`)
                .then(response => response.json())
                .then(data => {
                    // Update dashboard values
                    Object.keys(data).forEach(key => {
                        const element = document.querySelector(`#${key}`);
                        if (element && typeof data[key] === 'number') {
                            element.textContent = '৳' + new Intl.NumberFormat().format(data[key]);
                        }
                    });
                })
                .catch(error => {
                    console.error('Error updating dashboard data:', error);
                });
        }, 30000); // Updates every 30 seconds
    });
</script>
@endsection