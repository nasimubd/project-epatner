@extends('admin.layouts.app')

@section('content')
<div class="container mx-auto">
    <!-- Filter Controls - Hidden in Print -->
    <div class="mb-4 print:hidden text-center">
        <div class="flex justify-center gap-3">
            <a href="{{ route('admin.inventory.sales-summary', ['start_date' => $startDate, 'end_date' => $endDate]) }}"
                class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700">
                ← Back
            </a>
            <button type="button" onclick="printReceipt()"
                class="bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600">Print</button>
        </div>
    </div>

    <!-- Staff Filter Buttons -->
    @if($assignedStaff->count() > 0)
    <div class="mb-6 print:hidden bg-white p-4 rounded-lg shadow">
        <div class="mb-3">
            <h3 class="text-sm font-medium text-gray-700 mb-3">Filter by Staff:</h3>
            <div class="flex flex-wrap gap-2">
                <!-- All Staff Button -->
                <a href="{{ route('admin.inventory.category-sales-summary', $category->id) }}?start_date={{ $startDate }}&end_date={{ $endDate }}"
                    class="inline-flex items-center px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200
                          {{ !$staffFilter ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    All Staff
                    @if(!$staffFilter)
                    <span class="ml-2 bg-white bg-opacity-20 text-xs px-2 py-1 rounded-full">Active</span>
                    @endif
                </a>
                <!-- Individual Staff Buttons -->
                @foreach($assignedStaff as $staff)
                <a href="{{ route('admin.inventory.category-sales-summary', $category->id) }}?start_date={{ $startDate }}&end_date={{ $endDate }}&staff_id={{ $staff->id }}"
                    class="inline-flex items-center px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200
                          {{ $staffFilter == $staff->id ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    {{ $staff->user->name }}
                    @if($staffFilter == $staff->id)
                    <span class="ml-2 bg-white bg-opacity-20 text-xs px-2 py-1 rounded-full">Active</span>
                    @endif
                </a>
                @endforeach
            </div>
        </div>
        <!-- Active Filter Display -->
        @if($selectedStaff)
        <div class="mt-3 p-3 bg-green-50 border border-green-200 rounded-md">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-sm font-medium text-green-800">
                        Showing sales for: <strong>{{ $selectedStaff->user->name }}</strong>
                    </span>
                </div>
                <a href="{{ route('admin.inventory.category-sales-summary', $category->id) }}?start_date={{ $startDate }}&end_date={{ $endDate }}"
                    class="text-green-600 hover:text-green-800 text-sm font-medium">
                    Clear Filter
                </a>
            </div>
        </div>
        @endif
    </div>
    @endif

    <!-- 80mm Receipt Design -->
    <div class="w-[80mm] mx-auto bg-white p-2 print:p-0 print:shadow-none shadow">
        <!-- Header -->
        <div class="text-center border-b border-dashed pb-2">
            <h2 class="font-bold text-base">{{ $business->name }}</h2>
            <p class="text-xs">{{ $business->address }}</p>
            <h3 class="text-sm font-semibold mt-1">Sales Report</h3>
            <p class="text-xs">{{ Carbon\Carbon::parse($startDate)->format('d/m/Y') }} - {{ Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p>
            <p class="text-xs font-semibold">{{ $category->name }}</p>
            @if($selectedStaff)
            <p class="text-xs text-blue-600">Staff: {{ $selectedStaff->user->name }}</p>
            @endif
        </div>

        <!-- Products List -->
        <div class="text-sm">
            @php $totalSales = 0; @endphp
            @if($products->count() > 0)
            @foreach($products as $product)
            @php $totalSales += $product['total']; @endphp
            <div class="py-1 border-b border-dashed">
                <div class="font-semibold text-xs">{{ $product['name'] }}</div>
                <div class="flex justify-between text-xs">
                    <span>{{ $product['quantity'] }} × ৳{{ number_format($product['unit_price'], 2) }}</span>
                    <span>৳{{ number_format($product['total'], 2) }}</span>
                </div>
            </div>
            @endforeach
            @else
            <div class="py-4 text-center text-gray-500 text-xs">
                @if($selectedStaff)
                No sales data found for {{ $selectedStaff->user->name }}
                @else
                No sales data found
                @endif
            </div>
            @endif
        </div>

        <!-- Footer -->
        <div class="border-t border-dashed mt-2 pt-2">
            <div class="flex justify-between font-bold text-sm">
                <span>Total Sales:</span>
                <span>৳{{ number_format($totalSales, 2) }}</span>
            </div>
            <div class="mt-2 pt-2 border-t border-dashed text-xs text-gray-600">
                <div class="flex justify-between">
                    <span>Total Products:</span>
                    <span>{{ $products->count() }}</span>
                </div>
                @if($selectedStaff)
                <div class="flex justify-between mt-1">
                    <span>Staff:</span>
                    <span>{{ $selectedStaff->user->name }}</span>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
    @page {
        margin: 0;
        size: 80mm auto;
    }

    body {
        margin: 0;
        padding: 0;
    }

    .print-hidden {
        display: none !important;
    }

    @media print {
        body * {
            visibility: hidden;
        }

        .w-\[80mm\] {
            visibility: visible;
            position: absolute;
            left: 0;
            top: 0;
            width: 80mm;
        }

        .w-\[80mm\] * {
            visibility: visible;
        }
    }
</style>

<script>
    function printReceipt() {
        window.print();
    }
</script>
@endsection