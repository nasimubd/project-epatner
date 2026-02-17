@extends('admin.layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="bg-white rounded-lg shadow-lg">
        <!-- Header -->
        <div class="bg-blue-600 text-white p-2 sm:p-4">
            <div class="flex flex-col space-y-2 sm:space-y-0 sm:grid sm:grid-cols-3 sm:items-center">
                <div class="flex items-center justify-between sm:justify-start">
                    <h1 class="text-base sm:text-xl font-bold">Balance Sheet</h1>
                    <span class="text-xs sm:text-sm bg-blue-500 px-2 py-0.5 rounded ml-2">Live</span>
                </div>
                <div class="text-center text-sm sm:text-base">
                    <h2 class="font-semibold truncate">{{ $business->name }}</h2>
                    <p class="text-xs opacity-90">{{ now()->format('d/m/Y') }}</p>
                </div>
                <div class="flex justify-end space-x-2">
                    <button onclick="window.print()" class="p-1.5 sm:px-3 sm:py-1.5 bg-white text-blue-600 rounded-md">
                        <span class="hidden sm:inline">Print</span>
                        <svg class="w-4 h-4 sm:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Balance Sheet Content -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">
            <!-- Liabilities & Equity Section -->
            <div class="bg-red-50 p-4 rounded-lg">
                <h2 class="text-xl font-semibold text-red-700 mb-4 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 50 50" style="fill:#DC3545;" class="mr-2">
                        <path d="M25,2C12.317,2,2,12.317,2,25s10.317,23,23,23s23-10.317,23-23S37.683,2,25,2z M37,26H13v-2h24V26z"></path>
                    </svg>
                    Liabilities & Equity
                </h2>

                @foreach($liabilityLedgers as $type => $group)
                <div class="mb-4" x-data="{ open: false }">
                    <div @click="open = !open" class="flex justify-between items-center cursor-pointer font-semibold text-red-800 border-b pb-2">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-2 transform transition-transform" :class="{'rotate-90': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                            <span>{{ $type }}</span>
                        </div>
                        <span>৳{{ number_format($group['subtotal'], 2) }}</span>
                    </div>
                    <div x-show="open" class="mt-2 pl-6">
                        @foreach($group['ledgers'] as $ledger)
                        <div class="flex justify-between items-center py-1 text-sm">
                            <span>{{ $ledger->name }}</span>
                            <span>৳{{ number_format($ledger->current_balance, 2) }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach

                @if($netProfitLoss > 0)
                <div class="mb-4">
                    <div class="flex justify-between items-center font-semibold text-green-600 border-b pb-2">
                        <span>Current Period Net Profit</span>
                        <span>৳{{ number_format($netProfitLoss, 2) }}</span>
                    </div>
                </div>
                @endif

                <div class="mt-4 pt-4 border-t-2 border-red-200">
                    <div class="flex justify-between items-center font-bold text-red-800">
                        <span>Total Liabilities & Equity</span>
                        <span>৳{{ number_format($totalLiabilities, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Assets Section -->
            <div class="bg-green-50 p-4 rounded-lg">
                <h2 class="text-xl font-semibold text-green-700 mb-4 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 50 50" style="fill:#40C057;" class="mr-2">
                        <path d="M25,2C12.317,2,2,12.317,2,25s10.317,23,23,23s23-10.317,23-23S37.683,2,25,2z M37,26H26v11h-2V26H13v-2h11V13h2v11h11V26z"></path>
                    </svg>
                    Assets
                </h2>

                @foreach($assetLedgers as $type => $group)
                <div class="mb-4" x-data="{ open: false }">
                    <div @click="open = !open" class="flex justify-between items-center cursor-pointer font-semibold text-green-800 border-b pb-2">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-2 transform transition-transform" :class="{'rotate-90': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                            <span>{{ $type }}</span>
                        </div>
                        <span>৳{{ number_format($group['subtotal'], 2) }}</span>
                    </div>
                    @if($type !== 'Stock-in-Hand')
                    <div x-show="open" class="mt-2 pl-6">
                        @foreach($group['ledgers'] as $ledger)
                        <div class="flex justify-between items-center py-1 text-sm">
                            <span>{{ $ledger->name }}</span>
                            <span>৳{{ number_format($ledger->current_balance, 2) }}</span>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
                @endforeach

                @if($netProfitLoss < 0)
                    <div class="mb-4">
                    <div class="flex justify-between items-center font-semibold text-red-600 border-b pb-2">
                        <span>Current Period Net Loss</span>
                        <span>৳{{ number_format(abs($netProfitLoss), 2) }}</span>
                    </div>
            </div>
            @endif

            <div class="mt-4 pt-4 border-t-2 border-green-200">
                <div class="flex justify-between items-center font-bold text-green-800">
                    <span>Total Assets</span>
                    <span>৳{{ number_format($totalAssets, 2) }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection