@extends('admin.layouts.app')

@section('content')
<div class="container mx-auto">
    <!-- Filter Controls - Hidden in Print -->
    <div class="flex justify-center mb-4 print:hidden">
        <div class="bg-white rounded-lg shadow w-full max-w-3xl p-4">
            <form action="{{ route('admin.inventory.category-sales-return-summary', $category->id) }}" method="GET"
                class="flex flex-wrap gap-4 items-end">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                    <input type="date" name="start_date" value="{{ $startDate }}"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                    <input type="date" name="end_date" value="{{ $endDate }}"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                        Filter
                    </button>
                    <a href="{{ route('admin.inventory.sales-return-summary', ['start_date' => $startDate, 'end_date' => $endDate]) }}"
                        class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700">Back</a>
                    <button type="button" onclick="printReceipt()"
                        class="bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600">Print</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 80mm Receipt Design -->
    <div class="w-[80mm] mx-auto bg-white p-2 print:p-0 print:shadow-none shadow">
        <!-- Header -->
        <div class="text-center border-b border-dashed pb-2">
            <h2 class="font-bold text-base">{{ $business->name }}</h2>
            <p class="text-xs">{{ $business->address }}</p>
            <h3 class="text-sm font-semibold mt-1">Sales Return Report</h3>
            <p class="text-xs">{{ Carbon\Carbon::parse($startDate)->format('d/m/Y') }} - {{ Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p>
            <p class="text-xs font-semibold">{{ $category->name }}</p>
        </div>

        <!-- Products List -->
        <div class="text-sm">
            @php $totalReturn = 0; @endphp
            @foreach($products as $product)
            @php $totalReturn += $product['total']; @endphp
            <div class="py-1 border-b border-dashed">
                <div class="font-semibold text-xs">{{ $product['name'] }}</div>
                <div class="flex justify-between text-xs">
                    <span>{{ $product['quantity'] }} × ৳{{ number_format($product['unit_price'], 2) }}</span>
                    <span>৳{{ number_format($product['total'], 2) }}</span>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Footer -->
        <div class="border-t border-dashed mt-2 pt-2">
            <div class="flex justify-between font-bold text-sm">
                <span>Total Return:</span>
                <span>৳{{ number_format($totalReturn, 2) }}</span>
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