<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $order->invoice_number }} - {{ $business->name }}</title>
    <meta name="description" content="Invoice for your order at {{ $business->name }}">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-md">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">{{ $business->name }}</h1>
                    <p class="text-sm text-gray-600">Invoice</p>
                </div>
                <a href="{{ route('shopfront.show', ['id' => $shopfront->shopfront_id]) }}" class="text-blue-600 hover:text-blue-800">
                    &larr; Back to Shop
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-6">
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h2 class="text-xl font-semibold">Invoice #{{ $order->invoice_number }}</h2>
                    <p class="text-gray-600">Order #{{ $order->order_number }}</p>
                    <p class="text-gray-600">Date: {{ $order->created_at->format('M d, Y h:i A') }}</p>
                </div>
                <div class="text-right">
                    <p class="text-lg font-bold">{{ $business->name }}</p>
                    <p class="text-gray-600">{{ $business->address }}</p>
                    <p class="text-gray-600">{{ $business->phone }}</p>
                </div>
            </div>

            <div class="border-t border-b py-4 mb-4">
                <h3 class="text-lg font-semibold mb-2">Customer Information</h3>
                <p><span class="font-medium">Name:</span> {{ $order->customer_name }}</p>
                <p><span class="font-medium">Phone:</span> {{ $order->customer_phone }}</p>
                @if($order->customer_email)
                <p><span class="font-medium">Email:</span> {{ $order->customer_email }}</p>
                @endif
            </div>

            <h3 class="text-lg font-semibold mb-2">Order Items</h3>
            <table class="min-w-full divide-y divide-gray-200 mb-4">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Product
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Unit Price
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Quantity
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Total
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($order->orderLines as $line)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $line->product_name }}</div>
                            <div class="text-xs text-gray-500">{{ $line->is_common_product ? 'Common Product' : 'Business Product' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">৳{{ number_format($line->unit_price, 2) }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $line->quantity }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">৳{{ number_format($line->line_total, 2) }}</div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr>
                        <td colspan="3" class="px-6 py-4 text-right font-medium">Total:</td>
                        <td class="px-6 py-4 whitespace-nowrap font-medium">৳{{ number_format($order->total_amount, 2) }}</td>
                    </tr>
                </tfoot>
            </table>

            <div class="border-t pt-4">
                <div class="flex justify-between">
                    <div>
                        <h3 class="text-lg font-semibold mb-2">Order Status</h3>
                        <p class="inline-block px-2 py-1 rounded-full text-sm 
                            @if($order->status == 'completed') bg-green-100 text-green-800 
                            @elseif($order->status == 'processing') bg-blue-100 text-blue-800 
                            @elseif($order->status == 'cancelled') bg-red-100 text-red-800 
                            @else bg-yellow-100 text-yellow-800 @endif">
                            {{ ucfirst($order->status) }}
                        </p>
                    </div>
                    <div class="text-right">
                        <button onclick="window.print()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                            Print Invoice
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white shadow-inner mt-8 py-6 print:hidden">
        <div class="container mx-auto px-4">
            <p class="text-center text-gray-600">© {{ date('Y') }} {{ $business->name }}. All rights reserved.</p>
        </div>
    </footer>

    <style>
        @media print {
            body {
                background-color: white;
            }

            .print\:hidden {
                display: none !important;
            }

            .container {
                max-width: 100%;
                padding: 0;
            }

            .shadow-md,
            .shadow-lg {
                box-shadow: none !important;
            }

            .rounded-lg {
                border-radius: 0 !important;
            }
        }
    </style>
</body>

</html>