<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Batch Print Invoices</title>
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

        .invoice-wrapper {
            display: none;
        }

        .invoice-wrapper.active {
            display: block;
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

        .pt-1 {
            padding-top: 0.15rem;
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

        .print-status {
            margin-top: 12px;
            padding: 6px;
            background-color: #f3f4f6;
            border-radius: 4px;
            text-align: center;
        }

        .progress-bar {
            height: 6px;
            background-color: #e5e7eb;
            border-radius: 3px;
            margin-top: 6px;
            overflow: hidden;
        }

        .progress-bar-inner {
            height: 100%;
            background-color: #2563eb;
            width: 0%;
            transition: width 0.3s ease;
        }

        @media print {

            .print-controls,
            .print-status {
                display: none;
            }

            .invoice-wrapper {
                display: none;
            }

            .invoice-wrapper.active {
                display: block;
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
                display: block;
                width: 100%;
                margin: 8px 0;
            }

            body {
                padding-bottom: 120px;
            }

            .invoice {
                width: 100%;
                max-width: 80mm;
            }

            .print-status {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                background: white;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                padding: 10px;
                z-index: 100;
            }
        }
    </style>
</head>

<body>
    <div class="print-controls">
        <button id="printAllButton">Print All Invoices</button>
        <a href="{{ route('admin.invoices.index') }}">Back to List</a>
    </div>

    <div class="print-status" id="printStatus">
        <div id="statusText">Ready to print {{ count($transactions) }} invoices</div>
        <div class="progress-bar">
            <div class="progress-bar-inner" id="progressBar"></div>
        </div>
    </div>

    <!-- Container for all invoices -->
    <div id="invoicesContainer">
        @foreach($transactions as $index => $inventoryTransaction)
        <div class="invoice-wrapper" id="invoice-{{ $index }}">
            <div class="invoice">
                <!-- Invoice Content with added padding -->
                <div class="px-3">
                    <!-- Business Header -->
                    <div class="text-center pb-1">
                        @if($business)
                        <h2 class="font-bold text-xl mb-0.5">{{ $business->name }}</h2>
                        <p class="text-sm mb-0.5">{{ $business->address }}</p>
                        <p class="text-sm">+88{{ $business->contact_number }}</p>
                        @endif
                        @if(isset($inventoryTransaction->staff) && $inventoryTransaction->staff)
                        <p class="text-sm">By: {{ $inventoryTransaction->staff->name }}</p>
                        @endif
                    </div>

                    <!-- Line divider after business info -->
                    <div class="divider"></div>
                    <!-- Customer Info -->
                    <div class="text-center pb-0.5 pt-0.5">
                        <div class="mb-0.25">
                            <p class="text-sm font-semibold mb-0.25">{{ $inventoryTransaction->customer->name }}</p>

                            <!-- Debug info - remove after fixing -->
                            <!-- <p class="text-xs">Location exists: {{ isset($inventoryTransaction->customer->location) ? 'Yes' : 'No' }}</p>
        <p class="text-xs">Location value: "{{ $inventoryTransaction->customer->location ?? 'null' }}"</p> -->

                            @if(isset($inventoryTransaction->customer->location) && !empty($inventoryTransaction->customer->location))
                            <p class="text-xs mb-0.25">{{ $inventoryTransaction->customer->location }}</p>
                            @endif

                            @if(isset($inventoryTransaction->customer->contact) && !empty($inventoryTransaction->customer->contact))
                            <p class="text-xs mb-0.25">{{ $inventoryTransaction->customer->contact }}</p>
                            @endif
                        </div>
                        <div class="text-xs">
                            <p class="mb-0.25">
                                <span class="font-bold">Invoice #:</span> {{ $inventoryTransaction->id }}
                            </p>
                            <p class="mb-0.25">
                                <span class="font-bold">Date:</span> {{ \Carbon\Carbon::parse($inventoryTransaction->transaction_date)->timezone('Asia/Dhaka')->format('F d Y - h:i A') }}
                            </p>
                            <p>
                                <span class="font-bold">Payment:</span> {{ ucfirst($inventoryTransaction->payment_method) }}
                            </p>
                        </div>
                    </div>


                    <!-- Line divider after customer info -->
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
                            <div class="font-semibold text-xs">{{ $line->product->name }}</div>
                            <div class="flex justify-between text-xs">
                                <span>
                                    {{ $line->product->unit->type === 'unit' ? (floor($line->quantity) == $line->quantity ? number_format($line->quantity, 0) : number_format($line->quantity, 2)) : (floor($line->quantity) == $line->quantity ? number_format($line->quantity, 0) : number_format($line->quantity, 2)) }}
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

                    @php
                    $damagedProducts = $inventoryTransaction->lines->where('is_damaged', true);
                    $damagedTotal = $damagedProducts->sum('total_value');
                    @endphp

                    @if($damagedProducts->count() > 0)
                    <div class="divider"></div>
                    <div class="mb-1">
                        <h3 class="font-bold text-sm">Damaged Products</h3>
                        <div class="text-sm">
                            @foreach($damagedProducts as $damaged)
                            <div class="py-1">
                                <div class="font-semibold text-xs">{{ $damaged->product->name }}</div>
                                <div class="flex justify-between text-xs">
                                    <span>
                                        {{ $damaged->product->unit->type === 'unit' ? (floor($damaged->quantity) == $damaged->quantity ? number_format($damaged->quantity, 0) : number_format($damaged->quantity, 2)) : (floor($damaged->quantity) == $damaged->quantity ? number_format($damaged->quantity, 0) : number_format($damaged->quantity, 2)) }}
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

                    @php
                    $returnedProducts = collect();
                    $returnedTotal = 0;
                    if(isset($inventoryTransaction->returns)) {
                    $returnedProducts = $inventoryTransaction->returns;
                    $returnedTotal = $returnedProducts->sum('total_amount');
                    }
                    @endphp

                    @if($returnedProducts->count() > 0)
                    <div class="divider"></div>
                    <div class="mb-1">
                        <h3 class="font-bold text-sm">Returned Products</h3>
                        <div class="text-sm">
                            @foreach($returnedProducts as $returned)
                            <div class="py-1">
                                <div class="font-semibold text-xs">{{ $returned->product->name }}</div>
                                <div class="flex justify-between text-xs">
                                    <span>
                                        {{ $returned->product->unit->type === 'unit' ? (floor($returned->quantity) == $returned->quantity ? number_format($returned->quantity, 0) : number_format($returned->quantity, 2)) : (floor($returned->quantity) == $returned->quantity ? number_format($returned->quantity, 0) : number_format($returned->quantity, 2)) }}
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

                    <!-- Single separator before totals -->
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

                    <!-- Footer with just a line divider -->
                    <div class="divider-top"></div>
                    <div class="text-center mt-1 text-sm pt-1">
                        <p class="mb-0.5">Thank You and See You Again!</p>
                        <p class="text-xs">Powered By <span class="font-bold text-blue-600">ePATNER</span></p>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get references to DOM elements
            var printAllButton = document.getElementById('printAllButton');
            var statusText = document.getElementById('statusText');
            var progressBar = document.getElementById('progressBar');
            var invoiceWrappers = document.querySelectorAll('.invoice-wrapper');

            // Store total number of invoices
            var totalInvoices = invoiceWrappers.length;
            var currentInvoiceIndex = 0;
            var isPrinting = false;

            // Check if mobile
            const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

            // Add mobile-specific adjustments
            if (isMobile) {
                document.body.classList.add('mobile-device');

                // Make print button more prominent on mobile
                if (printAllButton) {
                    printAllButton.style.fontSize = '18px';
                    printAllButton.style.padding = '14px 24px';
                }

                // Add individual print buttons for mobile
                if (totalInvoices > 0 && isMobile) {
                    const mobileControls = document.createElement('div');
                    mobileControls.className = 'mobile-individual-controls';
                    mobileControls.style.margin = '10px 0';
                    mobileControls.style.padding = '10px';
                    mobileControls.style.background = '#f9fafb';
                    mobileControls.style.borderRadius = '8px';

                    const heading = document.createElement('h3');
                    heading.textContent = 'Print Individual Invoices';
                    heading.style.marginBottom = '10px';
                    heading.style.fontSize = '16px';
                    heading.style.fontWeight = 'bold';
                    mobileControls.appendChild(heading);

                    for (let i = 0; i < Math.min(totalInvoices, 10); i++) {
                        const btn = document.createElement('button');
                        btn.textContent = `Print Invoice #${i+1}`;
                        btn.style.display = 'block';
                        btn.style.width = '100%';
                        btn.style.padding = '10px';
                        btn.style.margin = '5px 0';
                        btn.style.background = '#e5e7eb';
                        btn.style.border = 'none';
                        btn.style.borderRadius = '4px';
                        btn.style.fontSize = '14px';

                        btn.addEventListener('click', function() {
                            printSingleInvoice(i);
                        });

                        mobileControls.appendChild(btn);
                    }

                    document.querySelector('.print-controls').appendChild(mobileControls);
                }
            }

            // Add click event listener to the print button
            printAllButton.addEventListener('click', function() {
                startSequentialPrinting();
            });

            // Function to print a single invoice (for mobile individual buttons)
            function printSingleInvoice(index) {
                // Hide all invoices
                for (var i = 0; i < invoiceWrappers.length; i++) {
                    invoiceWrappers[i].classList.remove('active');
                }

                // Show only the selected invoice
                invoiceWrappers[index].classList.add('active');

                // Update status
                statusText.textContent = `Printing invoice ${index + 1} of ${totalInvoices}`;

                // Print
                setTimeout(function() {
                    window.print();
                }, 300);
            }

            // Function to start sequential printing
            function startSequentialPrinting() {
                if (isPrinting) return;

                isPrinting = true;
                currentInvoiceIndex = 0;

                // Update status
                statusText.textContent = 'Printing invoice 1 of ' + totalInvoices;
                progressBar.style.width = ((1 / totalInvoices) * 100) + '%';

                // Start the printing sequence
                printNextInvoice();
            }

            // Function to print the next invoice in sequence
            function printNextInvoice() {
                if (currentInvoiceIndex >= totalInvoices) {
                    // All invoices printed
                    statusText.textContent = 'All ' + totalInvoices + ' invoices printed successfully!';
                    progressBar.style.width = '100%';
                    isPrinting = false;
                    return;
                }

                // Hide all invoices
                for (var i = 0; i < invoiceWrappers.length; i++) {
                    invoiceWrappers[i].classList.remove('active');
                }

                // Show only the current invoice
                var currentInvoice = document.getElementById('invoice-' + currentInvoiceIndex);
                currentInvoice.classList.add('active');

                // Update status
                statusText.textContent = 'Printing invoice ' + (currentInvoiceIndex + 1) + ' of ' + totalInvoices;
                progressBar.style.width = (((currentInvoiceIndex + 1) / totalInvoices) * 100) + '%';

                // Print the current invoice
                setTimeout(function() {
                    window.print();

                    // Wait for printing to complete and auto-cut to happen
                    setTimeout(function() {
                        currentInvoiceIndex++;
                        printNextInvoice();
                    }, isMobile ? 2500 : 1500); // Longer delay on mobile
                }, 500); // Small delay before printing starts
            }
        });
    </script>
</body>

</html>