@extends('admin.layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Order Details</h1>
        <a href="{{ route('admin.shopfront.orders.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
            Back to Orders
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
        <p>{{ session('success') }}</p>
    </div>
    @endif

    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
        <div class="p-6 border-b border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800 mb-2">Order Information</h2>
                    <p class="text-sm text-gray-600 mb-1"><span class="font-medium">Order #:</span> {{ $order->order_number }}</p>
                    <p class="text-sm text-gray-600 mb-1"><span class="font-medium">Invoice #:</span> {{ $order->invoice_number }}</p>
                    <p class="text-sm text-gray-600 mb-1"><span class="font-medium">Date:</span> {{ $order->created_at->format('M d, Y h:i A') }}</p>
                    <p class="text-sm text-gray-600 mb-1">
                        <span class="font-medium">Status:</span>
                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                @if($order->status == 'completed') bg-green-100 text-green-800 
                                @elseif($order->status == 'processing') bg-blue-100 text-blue-800 
                                @elseif($order->status == 'cancelled') bg-red-100 text-red-800 
                                @else bg-yellow-100 text-yellow-800 @endif">
                            {{ ucfirst($order->status) }}
                        </span>
                    </p>
                </div>

                <div>
                    <h2 class="text-lg font-semibold text-gray-800 mb-2">Customer Information</h2>
                    <p class="text-sm text-gray-600 mb-1"><span class="font-medium">Name:</span> {{ $order->customer_name }}</p>
                    <p class="text-sm text-gray-600 mb-1"><span class="font-medium">Phone:</span> {{ $order->customer_phone }}</p>
                    <p class="text-sm text-gray-600 mb-1"><span class="font-medium">Email:</span> {{ $order->customer_email ?? 'N/A' }}</p>
                    <p class="text-sm text-gray-600 mb-1"><span class="font-medium">Address:</span> {{ $order->delivery_address ?? 'N/A' }}</p>
                </div>

                <div>
                    <h2 class="text-lg font-semibold text-gray-800 mb-2">Payment Information</h2>
                    <p class="text-sm text-gray-600 mb-1"><span class="font-medium">Total:</span> ৳{{ number_format($order->total_amount, 2) }}</p>
                    <p class="text-sm text-gray-600 mb-1"><span class="font-medium">Payment Method:</span> {{ $order->payment_method ?? 'Cash on Delivery' }}</p>
                    <p class="text-sm text-gray-600 mb-1"><span class="font-medium">Payment Status:</span> {{ $order->payment_status ?? 'Pending' }}</p>
                </div>
            </div>
        </div>

        <div class="p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Order Items</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Product
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Price
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Quantity
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Subtotal
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($order->orderLines as $line)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    @if($line->is_common_product && $line->commonProduct)
                                    {{ $line->commonProduct->name }}
                                    @elseif(!$line->is_common_product && $line->product)
                                    {{ $line->product->name }}
                                    @else
                                    Unknown Product
                                    @endif
                                </div>
                                <div class="text-xs text-gray-500">
                                    @if($line->is_common_product)
                                    Common Product
                                    @else
                                    Business Product
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">৳{{ number_format($line->price, 2) }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $line->quantity }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">৳{{ number_format($line->price * $line->quantity, 2) }}</div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-right text-sm font-medium text-gray-900">
                                Total:
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                ৳{{ number_format($order->total_amount, 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Update Order Status</h2>
            <form action="{{ route('admin.shopfront.orders.status', $order) }}" method="POST">
                @csrf
                @method('PATCH')
                <div class="flex items-center space-x-4">
                    <select name="status" class="rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <option value="pending" {{ $order->status == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="processing" {{ $order->status == 'processing' ? 'selected' : '' }}>Processing</option>
                        <option value="completed" {{ $order->status == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ $order->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection