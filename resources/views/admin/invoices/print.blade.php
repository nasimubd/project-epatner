<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Invoice</title>
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

        /* Mobile-specific styles */
        @media screen and (max-width: 768px) {
            .print-controls {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background: white;
                box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
                padding: 10px;
                z-index: 100;
            }

            .print-controls button,
            .print-controls a {
                padding: 12px 20px;
                font-size: 16px;
            }

            body {
                padding-bottom: 60px;
            }

            .invoice {
                width: 100%;
                max-width: 80mm;
            }
        }

        /* Print-specific styles remain the same */
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
        <a href="{{ route('admin.invoices.index') }}">Back to List</a>
        <button onclick="window.print()">Print Invoice</button>
    </div>

    <div class="invoice">
        <div class="px-3">
            <!-- Business Header -->
            <div class="text-center pb-1">
                @if($business)
                <h2 class="font-bold text-xl mb-0.5">{{ $business->name }}</h2>
                <p class="text-sm mb-0.5">{{ $business->address }}</p>
                <p class="text-sm">+88{{ $business->contact_number }}</p>
                @endif
                @if(isset($staffName) && $staffName)
                <p class="text-sm">By: {{ $staffName }}</p>
                @endif
            </div>

            <div class="divider"></div>

            <!-- Customer Info -->
            <div class="text-center pb-0.5 pt-0.5">
                <div class="mb-0.25">
                    @if(isset($customer) && $customer)
                    <p class="text-sm font-semibold mb-0.25">{{ $customer->name }}</p>
                    @if($customer->location)
                    <p class="text-xs mb-0.25">{{ $customer->location }}</p>
                    @endif
                    @if($customer->contact)
                    <p class="text-xs mb-0.25">{{ $customer->contact }}</p>
                    @endif
                    @else
                    <p class="text-sm font-semibold mb-0.25">Customer information not available</p>
                    @endif
                </div>
                <div class="text-xs">
                    <p class="mb-0.25">
                        <span class="font-bold">Invoice #:</span> {{ $inventoryTransaction->id }}
                    </p>
                    <p class="mb-0.25">
                        <span class="font-bold">Date:</span> {{ $inventoryTransaction->transaction_date }}
                    </p>
                    <p>
                        <span class="font-bold">Payment:</span> {{ ucfirst($inventoryTransaction->payment_method) }}
                    </p>
                </div>
            </div>

            <div class="divider"></div>

            <!-- Products List -->
            <div class="text-sm pt-1">
                <h3 class="font-bold text-sm mb-0.5">Products</h3>
                @php
                $displayedLines = collect();
                @endphp

                @foreach($inventoryTransaction->lines as $line)
                @if(!$line->is_damaged && !$displayedLines->contains('product_id', $line->product_id))
                <div class="py-1">
                    <div class="font-semibold text-xs">{{ $line->product->name ?? 'Unknown Product' }}</div>
                    <div class="flex justify-between text-xs">
                        <span>
                            @if(isset($line->product) && isset($line->product->unit))
                            {{ $line->product->unit->type === 'unit' ? (floor($line->quantity) == $line->quantity ? number_format($line->quantity, 0) : number_format($line->quantity, 2)) : (floor($line->quantity) == $line->quantity ? number_format($line->quantity, 0) : number_format($line->quantity, 2)) }}
                            @else
                            {{ floor($line->quantity) == $line->quantity ? number_format($line->quantity, 0) : number_format($line->quantity, 2) }}
                            @endif
                            × ৳{{ floor($line->unit_price) == $line->unit_price ? number_format($line->unit_price, 0) : number_format($line->unit_price, 2) }}
                        </span>
                        <span>৳{{ floor($line->line_total) == $line->line_total ? number_format($line->line_total, 0) : number_format($line->line_total, 2) }}</span>
                    </div>
                </div>
                @php
                $displayedLines->push($line);
                @endphp
                @endif
                @endforeach
            </div>

            @if(isset($damagedProducts) && $damagedProducts->count() > 0)
            <div class="divider"></div>
            <div class="mb-1">
                <h3 class="font-bold text-sm">Damaged Products</h3>
                <div class="text-sm">
                    @foreach($damagedProducts as $damaged)
                    <div class="py-1">
                        <div class="font-semibold text-xs">{{ $damaged->product->name ?? 'Unknown Product' }}</div>
                        <div class="flex justify-between text-xs">
                            <span>
                                @if(isset($damaged->product) && isset($damaged->product->unit))
                                {{ $damaged->product->unit->type === 'unit' ? (floor($damaged->quantity) == $damaged->quantity ? number_format($damaged->quantity, 0) : number_format($damaged->quantity, 2)) : (floor($damaged->quantity) == $damaged->quantity ? number_format($damaged->quantity, 0) : number_format($damaged->quantity, 2)) }}
                                @else
                                {{ floor($damaged->quantity) == $damaged->quantity ? number_format($damaged->quantity, 0) : number_format($damaged->quantity, 2) }}
                                @endif
                                × ৳{{ floor($damaged->unit_price) == $damaged->unit_price ? number_format($damaged->unit_price, 0) : number_format($damaged->unit_price, 2) }}
                            </span>
                            <span>৳{{ floor($damaged->total_value) == $damaged->total_value ? number_format($damaged->total_value, 0) : number_format($damaged->total_value, 2) }}</span>
                        </div>
                    </div>
                    @endforeach

                    <div class="flex justify-between font-bold text-sm pt-1">
                        <span>Damage Total:</span>
                        <span>৳{{ floor($damagedTotal) == $damagedTotal ? number_format($damagedTotal, 0) : number_format($damagedTotal, 2) }}</span>
                    </div>
                </div>
            </div>
            @endif

            @if(isset($returnedProducts) && $returnedProducts->count() > 0)
            <div class="divider"></div>
            <div class="mb-1">
                <h3 class="font-bold text-sm">Returned Products</h3>
                <div class="text-sm">
                    @foreach($returnedProducts as $returned)
                    <div class="py-1">
                        <div class="font-semibold text-xs">{{ $returned->product->name ?? 'Unknown Product' }}</div>
                        <div class="flex justify-between text-xs">
                            <span>
                                @if(isset($returned->product) && isset($returned->product->unit))
                                {{ $returned->product->unit->type === 'unit' ? (floor($returned->quantity) == $returned->quantity ? number_format($returned->quantity, 0) : number_format($returned->quantity, 2)) : (floor($returned->quantity) == $returned->quantity ? number_format($returned->quantity, 0) : number_format($returned->quantity, 2)) }}
                                @else
                                {{ floor($returned->quantity) == $returned->quantity ? number_format($returned->quantity, 0) : number_format($returned->quantity, 2) }}
                                @endif
                                × ৳{{ floor($returned->unit_price) == $returned->unit_price ? number_format($returned->unit_price, 0) : number_format($returned->unit_price, 2) }}
                            </span>
                            <span>৳{{ floor($returned->total_amount) == $returned->total_amount ? number_format($returned->total_amount, 0) : number_format($returned->total_amount, 2) }}</span>
                        </div>
                    </div>
                    @endforeach

                    <div class="flex justify-between font-bold text-sm pt-1">
                        <span>Return Total:</span>
                        <span>৳{{ floor($returnedTotal) == $returnedTotal ? number_format($returnedTotal, 0) : number_format($returnedTotal, 2) }}</span>
                    </div>
                </div>
            </div>
            @endif

            <div class="divider"></div>

            <!-- Update Totals Section -->
            <div class="pt-1">
                <div class="flex justify-between text-sm">
                    <span>Subtotal:</span>
                    <span>৳{{ floor($inventoryTransaction->subtotal) == $inventoryTransaction->subtotal ? number_format($inventoryTransaction->subtotal, 0) : number_format($inventoryTransaction->subtotal, 2) }}</span>
                </div>

                @if($inventoryTransaction->paid_amount > 0)
                <div class="flex justify-between text-sm">
                    <span>Paid Amount:</span>
                    <span class="text-green-600">৳{{ floor($inventoryTransaction->paid_amount) == $inventoryTransaction->paid_amount ? number_format($inventoryTransaction->paid_amount, 0) : number_format($inventoryTransaction->paid_amount, 2) }}</span>
                </div>
                @endif

                <div class="flex justify-between font-bold text-sm">
                    <span>Grand Total:</span>
                    <span>৳{{ number_format(round($inventoryTransaction->grand_total)) }}
                        @php
                        $fraction = $inventoryTransaction->grand_total - floor($inventoryTransaction->grand_total);
                        @endphp
                        @if($fraction > 0)
                        <span class="text-xs text-gray-500">({{ number_format($fraction, 2) }})</span>
                        @endif
                    </span>
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
        // Auto print when page loads
        window.onload = function() {
            // Add a small delay to ensure everything is rendered
            setTimeout(function() {
                // Uncomment the line below if you want auto-print
                // window.print();
            }, 500);
        };

        // Auto print when page loads
        window.onload = function() {
            // Check if mobile
            const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

            // Add a small delay to ensure everything is rendered
            setTimeout(function() {
                // For mobile, don't auto-print, let user tap the button
                if (!isMobile) {
                    // Uncomment the line below if you want auto-print on desktop
                    // window.print();
                }

                // Add mobile-specific adjustments
                if (isMobile) {
                    document.body.classList.add('mobile-device');

                    // Make print button more prominent on mobile
                    const printBtn = document.querySelector('.print-controls button');
                    if (printBtn) {
                        printBtn.style.fontSize = '18px';
                        printBtn.style.padding = '12px 24px';
                    }
                }
            }, 500);
        };
    </script>
</body>

</html>