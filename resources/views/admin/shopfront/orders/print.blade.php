<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Order #{{ $order->order_number }}</title>
    <style>
        @page {
            margin: 0;
            size: 80mm auto;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        .print-controls {
            text-align: center;
            margin: 12px 0;
        }

        .print-controls button {
            padding: 6px 14px;
            background-color: #2563eb;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 0 4px;
        }

        .print-controls a {
            padding: 6px 14px;
            background-color: #4b5563;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 0 4px;
            text-decoration: none;
            display: inline-block;
        }

        .invoice {
            width: 78mm;
            margin: 0 auto;
            padding: 3px;
            background-color: white;
        }

        .divider {
            border-bottom: 1px solid black;
            margin: 0.3rem 0;
        }

        .divider-top {
            border-top: 1px solid black;
            margin-top: 0.3rem;
        }

        .mb-2 {
            margin-bottom: 0.3rem;
        }

        .mb-0\.5 {
            margin-bottom: 0.08rem;
        }

        .mb-0\.25 {
            margin-bottom: 0.05rem;
        }

        .mb-1 {
            margin-bottom: 0.15rem;
        }

        .mt-4 {
            margin-top: 0.6rem;
        }

        .mt-2 {
            margin-top: 0.3rem;
        }

        .mt-1 {
            margin-top: 0.15rem;
        }

        .py-1 {
            padding-top: 0.15rem;
            padding-bottom: 0.15rem;
        }

        .pb-2 {
            padding-bottom: 0.3rem;
        }

        .pb-1 {
            padding-bottom: 0.15rem;
        }

        .pb-0\.5 {
            padding-bottom: 0.08rem;
        }

        .pt-1 {
            padding-top: 0.15rem;
        }

        .pt-0\.5 {
            padding-top: 0.08rem;
        }

        .pt-2 {
            padding-top: 0.3rem;
        }

        .px-3 {
            padding-left: 0.4rem;
            padding-right: 0.4rem;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-left {
            text-align: left;
        }

        .text-sm {
            font-size: 0.75rem;
        }

        .text-xs {
            font-size: 0.65rem;
        }

        .text-xl {
            font-size: 1rem;
        }

        .font-bold {
            font-weight: bold;
        }

        .font-semibold {
            font-weight: 600;
        }

        .text-green-600 {
            color: #059669;
        }

        .text-blue-600 {
            color: #2563eb;
        }

        .w-full {
            width: 100%;
        }

        .flex {
            display: flex;
        }

        .justify-between {
            justify-content: space-between;
        }

        @media print {
            .print-controls {
                display: none;
            }

            .divider {
                border-bottom: 0.5px solid black;
                margin: 0.2rem 0;
            }

            .divider-top {
                border-top: 0.5px solid black;
                margin-top: 0.2rem;
            }

            body {
                margin: 0;
                padding: 0;
            }

            .invoice {
                width: 100%;
                padding: 0;
            }
        }
    </style>
</head>

<body>
    <div class="print-controls">
        <a href="{{ route('admin.shopfront.orders.index') }}">Back to Orders</a>
        <button onclick="window.print()">Print Order</button>
    </div>

    <div class="invoice">
        <div class="px-3">
            <!-- Business Header -->
            <div class="text-center pb-1">
                <h2 class="font-bold text-xl mb-0.5">{{ $business->name }}</h2>
                <p class="text-sm mb-0.5">{{ $business->address }}</p>
                <p class="text-sm">+88{{ $business->phone }}</p>
            </div>

            <div class="divider"></div>

            <!-- Customer Info -->
            <div class="text-center pb-0.5 pt-0.5">
                <div class="text-xs">
                    <p class="mb-0.25">
                        <span class="font-bold">Order #:</span> {{ $order->order_number }}
                    </p>
                    <p class="mb-0.25">
                        <span class="font-bold">Date:</span> {{ $order->created_at->format('d/m/Y h:i A') }}
                    </p>
                    <p class="mb-0.25">
                        <span class="font-bold">Customer:</span> {{ $order->customer_name }}
                    </p>
                    <p>
                        <span class="font-bold">Phone:</span> {{ $order->customer_phone }}
                    </p>
                </div>
            </div>

            <div class="divider"></div>

            <!-- Products List -->
            <div class="text-sm pt-1">
                <h3 class="font-bold text-sm mb-0.5">Products</h3>
                @foreach($order->orderLines as $line)
                <div class="py-1">
                    <div class="font-semibold text-xs">{{ $line->product_name }}</div>
                    <div class="flex justify-between text-xs">
                        <span>
                            {{ floor($line->quantity) == $line->quantity ? number_format($line->quantity, 0) : number_format($line->quantity, 2) }}
                            × ৳{{ floor($line->unit_price) == $line->unit_price ? number_format($line->unit_price, 0) : number_format($line->unit_price, 2) }}
                        </span>
                        <span>৳{{ floor($line->line_total) == $line->line_total ? number_format($line->line_total, 0) : number_format($line->line_total, 2) }}</span>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="divider"></div>

            <!-- Totals Section -->
            <div class="pt-1">
                <div class="flex justify-between text-sm">
                    <span>Subtotal:</span>
                    <span>৳{{ floor($order->total_amount) == $order->total_amount ? number_format($order->total_amount, 0) : number_format($order->total_amount, 2) }}</span>
                </div>

                <div class="flex justify-between font-bold text-sm">
                    <span>Total Amount:</span>
                    <span>৳{{ floor($order->total_amount) == $order->total_amount ? number_format($order->total_amount, 0) : number_format($order->total_amount, 2) }}</span>
                </div>
            </div>

            <!-- Footer -->
            <div class="divider-top"></div>
            <div class="text-center mt-1 text-sm pt-1">
                <p class="mb-0.5">Thank You and See You Again!</p>
                <p class="text-xs">Powered By <span class="font-bold text-blue-600">ePATNER</span></p>
            </div>
        </div>
    </div>

    <script>
        window.onload = function() {
            setTimeout(function() {
                // window.print();
            }, 500);
        };
    </script>
</body>

</html>