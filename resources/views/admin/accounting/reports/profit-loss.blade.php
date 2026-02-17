@extends('admin.layouts.app')

@section('content')
<div class="container mx-auto px-2 sm:px-4 py-4 sm:py-6">
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <div class="bg-blue-600 text-white p-3 sm:p-4 flex flex-col sm:flex-row justify-between items-start sm:items-center">
            <div>
                <h1 class="text-lg sm:text-xl font-bold">Profit & Loss Statement</h1>
                <p class="text-xs sm:text-sm">{{ $business->name }}</p>
            </div>
            <p class="text-xs mt-1 sm:mt-0">{{ now()->format('d M Y') }}</p>
        </div>

        <div class="p-2 sm:p-4 overflow-x-auto">
            <!-- Mobile View -->
            <div class="block sm:hidden">
                <div class="mb-4 bg-gray-100 p-3 rounded-lg">
                    <h2 class="text-base font-semibold mb-2 flex items-center text-green-600">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 50 50" style="fill:#40C057;" class="mr-2">
                            <path d="M25,2C12.317,2,2,12.317,2,25s10.317,23,23,23s23-10.317,23-23S37.683,2,25,2z M37,26H26v11h-2V26H13v-2h11V13h2v11h11V26z"></path>
                        </svg>
                        Debit Side
                    </h2>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-green-600">Opening Stock</span>
                            <span>{{ number_format($openingStock, 2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-green-600">Purchase Accounts</span>
                            <span>{{ number_format($totalPurchases, 2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-green-600">Expenses</span>
                            <span>{{ number_format($totalExpenses, 2) }}</span>
                        </div>
                        @if($isProfit)
                        <div class="flex justify-between font-bold">
                            <span class="text-green-600">Net Profit</span>
                            <span class="text-green-600">{{ number_format($netProfit, 2) }}</span>
                        </div>
                        @endif
                        <div class="flex justify-between font-bold pt-2 border-t border-gray-300">
                            <span class="text-green-600">Total</span>
                            <span>{{ number_format($debitSide + ($isProfit ? $netProfit : 0), 2) }}</span>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-100 p-3 rounded-lg">
                    <h2 class="text-base font-semibold mb-2 flex items-center text-red-600">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 50 50" style="fill:#DC3545;" class="mr-2">
                            <path d="M25,2C12.317,2,2,12.317,2,25s10.317,23,23,23s23-10.317,23-23S37.683,2,25,2z M37,26H13v-2h24V26z"></path>
                        </svg>
                        Credit Side
                    </h2>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-red-600">Sales Accounts</span>
                            <span>{{ number_format($totalSales, 2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-red-600">Income</span>
                            <span>{{ number_format($totalIncome, 2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-red-600">Closing Stock</span>
                            <span>{{ number_format($closingStock, 2) }}</span>
                        </div>
                        @if(!$isProfit)
                        <div class="flex justify-between font-bold">
                            <span class="text-red-600">Net Loss</span>
                            <span class="text-red-600">{{ number_format($netProfit, 2) }}</span>
                        </div>
                        @endif
                        <div class="flex justify-between font-bold pt-2 border-t border-gray-300">
                            <span class="text-red-600">Total</span>
                            <span>{{ number_format($creditSide + (!$isProfit ? $netProfit : 0), 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Desktop View -->
            <table class="hidden sm:table w-full border-collapse border border-gray-200">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="border p-2 text-left text-green-600 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 50 50" style="fill:#40C057;" class="mr-2">
                                <path d="M25,2C12.317,2,2,12.317,2,25s10.317,23,23,23s23-10.317,23-23S37.683,2,25,2z M37,26H26v11h-2V26H13v-2h11V13h2v11h11V26z"></path>
                            </svg>
                            Particulars
                        </th>
                        <th class="border p-2 text-right">Amount (৳)</th>
                        <th class="border p-2 text-left text-red-600 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 50 50" style="fill:#DC3545;" class="mr-2">
                                <path d="M25,2C12.317,2,2,12.317,2,25s10.317,23,23,23s23-10.317,23-23S37.683,2,25,2z M37,26H13v-2h24V26z"></path>
                            </svg>
                            Particulars
                        </th>
                        <th class="border p-2 text-right">Amount (৳)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="border p-2 text-green-600">Opening Stock</td>
                        <td class="border p-2 text-right">{{ number_format($openingStock, 2) }}</td>
                        <td class="border p-2 text-red-600">Sales Accounts</td>
                        <td class="border p-2 text-right">{{ number_format($totalSales, 2) }}</td>
                    </tr>

                    <tr>
                        <td class="border p-2 text-green-600">Purchase Accounts</td>
                        <td class="border p-2 text-right">{{ number_format($totalPurchases, 2) }}</td>
                        <td class="border p-2 text-red-600">Income</td>
                        <td class="border p-2 text-right">{{ number_format($totalIncome, 2) }}</td>
                    </tr>

                    <tr>
                        <td class="border p-2 text-green-600">Expenses</td>
                        <td class="border p-2 text-right">{{ number_format($totalExpenses, 2) }}</td>
                        <td class="border p-2 text-red-600">Closing Stock</td>
                        <td class="border p-2 text-right">{{ number_format($closingStock, 2) }}</td>
                    </tr>

                    @if($isProfit)
                    <tr class="font-bold">
                        <td class="border p-2 text-green-600">Net Profit</td>
                        <td class="border p-2 text-right text-green-600">{{ number_format($netProfit, 2) }}</td>
                        <td class="border p-2"></td>
                        <td class="border p-2"></td>
                    </tr>
                    @else
                    <tr class="font-bold">
                        <td class="border p-2"></td>
                        <td class="border p-2"></td>
                        <td class="border p-2 text-red-600">Net Loss</td>
                        <td class="border p-2 text-right text-red-600">{{ number_format($netProfit, 2) }}</td>
                    </tr>
                    @endif

                    <tr class="font-bold bg-gray-100">
                        <td class="border p-2 text-green-600">Total</td>
                        <td class="border p-2 text-right">{{ number_format($debitSide + ($isProfit ? $netProfit : 0), 2) }}</td>
                        <td class="border p-2 text-red-600">Total</td>
                        <td class="border p-2 text-right">{{ number_format($creditSide + (!$isProfit ? $netProfit : 0), 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection