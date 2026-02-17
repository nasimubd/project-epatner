@extends('admin.layouts.app')

@section('content')
<div class="container mx-auto">
    <!-- Controls - Hidden in Print -->
    <div class="flex justify-center mb-4 print:hidden">
        <div class="flex gap-2">
            <a href="{{ route('admin.inventory.stock-summary') }}"
                class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700">Back</a>
            <button onclick="printReceipt()"
                class="bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600">Print</button>
        </div>
    </div>

    <!-- 80mm Receipt Design -->
    <div class="w-[80mm] mx-auto bg-white p-2 print:p-0 print:shadow-none shadow">
        <!-- Header -->
        <div class="text-center border-b border-dashed pb-2">
            <h2 class="font-bold text-base">{{ $business->name }}</h2>
            <p class="text-xs">{{ $business->address }}</p>
            <h3 class="text-sm font-semibold mt-1">Stock Report</h3>
            <p class="text-xs">{{ now()->format('d/m/Y h:i A') }}</p>
            <p class="text-xs font-semibold">{{ $category->name }}</p>
        </div>

        <!-- Products List -->
        <div class="text-sm">
            @php $totalValue = 0; @endphp
            @foreach($products as $product)
            @php $totalValue += $product['total']; @endphp
            <div class="py-1 border-b border-dashed">
                <div class="font-semibold text-xs">{{ $product['name'] }}</div>
                <div class="flex justify-between text-xs">
                    <span>{{ $product['quantity'] }} × ৳{{ number_format($product['unit_price'], 2) }}</span>
                    <span>৳{{ number_format($product['total'], 2) }}</span>
                </div>
                @if($product['is_low_stock'])
                <div class="text-xs text-red-600">Low Stock Alert</div>
                @endif
            </div>
            @endforeach
        </div>

        <!-- Footer -->
        <div class="border-t border-dashed mt-2 pt-2">
            <div class="flex justify-between font-bold text-sm">
                <span>Total Value:</span>
                <span>৳{{ number_format($totalValue, 2) }}</span>
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