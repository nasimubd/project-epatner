@extends('admin.layouts.app')

@section('content')
<div class="container mx-auto px-2 sm:px-4 lg:px-6">
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <!-- Main Header -->
        <div class="p-4 sm:p-5 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-gray-200">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2">
                <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Ledger Details</h1>

                <!-- Month Navigation -->
                <div class="flex items-center space-x-2 bg-white px-3 py-1.5 rounded-lg shadow-sm">
                    <a href="{{ route('admin.accounting.ledgers.show', ['ledger' => $ledger->id, 'month' => $previousMonth]) }}"
                        class="prev-month-button text-gray-500 hover:text-gray-700 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </a>

                    <h3 class="text-sm font-medium text-gray-700 px-2">
                        {{ Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }}
                    </h3>

                    <a href="{{ route('admin.accounting.ledgers.show', ['ledger' => $ledger->id, 'month' => $nextMonth]) }}"
                        class="next-month-button text-gray-500 hover:text-gray-700 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>

        <div class="p-3 sm:p-4">
            <!-- Ledger Information Card -->
            <div class="bg-white p-3 rounded-lg shadow-sm mb-4 border border-gray-100">
                <div class="flex flex-col lg:flex-row items-start gap-3">
                    <!-- Left Side Info -->
                    <div class="w-full lg:w-3/4">
                        <div class="flex flex-col sm:flex-row sm:items-center gap-2 mb-2">
                            <h2 class="text-base sm:text-lg font-semibold text-gray-800 truncate">{{ $ledger->name }}</h2>
                            <span class="px-2 py-0.5 text-xs rounded-full bg-blue-100 text-blue-800 font-medium">{{ $ledger->ledger_type }}</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs text-gray-600">
                            <p><span class="font-medium">ID:</span> {{ $ledger->id }}</p>
                            @if($ledger->contact)
                            <p><span class="font-medium">Contact:</span> {{ $ledger->contact }}</p>
                            @endif
                            @if($ledger->location)
                            <p><span class="font-medium">Location:</span> {{ $ledger->location }}</p>
                            @endif
                        </div>
                    </div>

                    <!-- Right Side - Balance & Refresh -->
                    <div class="w-full lg:w-1/4 flex flex-col gap-2">
                        <div class="flex justify-between items-center p-2 bg-gray-50 rounded-md">
                            <span class="text-xs font-medium">Current Balance:</span>
                            <span class="text-sm font-bold {{ $ledger->current_balance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ number_format(abs($ledger->current_balance)) }} {{ $ledger->current_balance >= 0 ? 'Dr' : 'Cr' }}
                            </span>
                        </div>

                        <form action="{{ route('admin.accounting.ledgers.recalculate', $ledger->id) }}" method="POST">
                            @csrf
                            <button type="submit" id="refreshBalanceBtn"
                                class="w-full bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white text-xs font-medium py-1.5 px-3 rounded-md transition flex items-center justify-center gap-1">
                                <svg id="refreshIcon" class="w-3.5 h-3.5 transition-transform group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                <svg id="refreshSpinnerIcon" class="hidden w-3.5 h-3.5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span id="refreshButtonText">Refresh Balance</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            @if(isset($salesBreakdown) && $ledger->ledger_type === 'Sales Accounts')
            <div class="bg-white p-3 rounded-lg shadow-sm mb-4 border-l-4 border-blue-500">
                <h3 class="text-sm font-medium text-gray-700 mb-2">Sales Breakdown</h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="p-2 bg-gray-50 rounded">
                        <span class="text-xs text-gray-500 block">Today's Sales</span>
                        <p class="text-sm font-semibold text-green-600">{{ number_format($salesBreakdown['todaySales']) }}</p>
                    </div>
                    <div class="p-2 bg-gray-50 rounded">
                        <span class="text-xs text-gray-500 block">Today's Adjustments</span>
                        <p class="text-sm font-semibold {{ $salesBreakdown['todayAdjustments'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ number_format(abs($salesBreakdown['todayAdjustments'])) }}
                            <span class="text-xs">({{ $salesBreakdown['todayAdjustmentType'] }})</span>
                        </p>
                        <div class="mt-1 grid grid-cols-2 gap-1 text-xs">
                            <div>
                                <span class="text-gray-500">Cr:</span>
                                <span class="text-red-600">{{ number_format($salesBreakdown['todayAdjustmentCredits']) }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Dr:</span>
                                <span class="text-green-600">{{ number_format($salesBreakdown['todayAdjustmentDebits']) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="p-2 bg-gray-50 rounded">
                        <span class="text-xs text-gray-500 block">Monthly Adjustments</span>
                        <p class="text-sm font-semibold {{ $salesBreakdown['priceAdjustments'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ number_format(abs($salesBreakdown['priceAdjustments'])) }}
                            <span class="text-xs">({{ $salesBreakdown['priceAdjustmentType'] }})</span>
                        </p>
                        <div class="mt-1 grid grid-cols-2 gap-1 text-xs">
                            <div>
                                <span class="text-gray-500">Cr:</span>
                                <span class="text-red-600">{{ number_format($salesBreakdown['monthlyAdjustmentCredits']) }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Dr:</span>
                                <span class="text-green-600">{{ number_format($salesBreakdown['monthlyAdjustmentDebits']) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif


            <!-- Transaction Section -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
                <!-- Opening Balance Display -->
                <div class="p-3 bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <span class="text-xs sm:text-sm font-medium text-gray-700">Opening Balance:</span>
                        <span class="text-xs sm:text-sm font-bold {{ $openingBalance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ number_format(abs($openingBalance)) }} {{ $openingBalance >= 0 ? 'Dr' : 'Cr' }}
                        </span>
                    </div>
                </div>

                <!-- Desktop Transaction History Table -->
                <div class="hidden sm:block overflow-x-auto">
                    <div class="max-h-[500px] overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100">
                        <table class="w-full border-collapse text-xs">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200">
                                    <th class="px-3 py-2 text-left text-gray-600 font-medium">Date</th>
                                    <th class="px-3 py-2 text-left text-gray-600 font-medium">Type</th>
                                    <th class="px-3 py-2 text-left text-gray-600 font-medium">Narration</th>
                                    <th class="px-3 py-2 text-right text-green-600 font-medium">Dr Amount</th>
                                    <th class="px-3 py-2 text-right text-red-600 font-medium">Cr Amount</th>
                                    <th class="px-3 py-2 text-right text-gray-600 font-medium">Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                $runningBalance = $openingBalance;
                                // Use the transaction lines directly without re-sorting
                                // They're already ordered by date and ID in the controller
                                @endphp

                                @foreach($transactionLines as $line)
                                @php
                                // Apply the transaction to the running balance first
                                $runningBalance += ($line->debit_amount - $line->credit_amount);
                                @endphp
                                <tr class="transaction-row border-b border-gray-100 hover:bg-gray-50">
                                    <td class="px-3 py-2 text-gray-700">{{ \Carbon\Carbon::parse($line->transaction->transaction_date)->format('d/m/y') }}</td>
                                    <td class="px-3 py-2 text-gray-700">{{ substr($line->transaction->transaction_type, 0, 4) }}</td>
                                    <td class="px-3 py-2 text-gray-700">
                                        <div class="max-w-[250px] truncate" title="{{ $line->narration }}">{{ $line->narration }}</div>
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <span class="text-green-600 font-medium">
                                            {{ $line->debit_amount > 0 ? number_format($line->debit_amount) : '-' }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <span class="text-red-600 font-medium">
                                            {{ $line->credit_amount > 0 ? number_format($line->credit_amount) : '-' }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <span class="{{ $runningBalance >= 0 ? 'text-green-600' : 'text-red-600' }} font-medium">
                                            {{ number_format(abs($runningBalance)) }} {{ $runningBalance >= 0 ? 'Dr' : 'Cr' }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- Mobile Transaction History Table -->
                <div class="sm:hidden overflow-x-auto -mx-2">
                    <div class="max-h-[500px] overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100">
                        <table class="w-full border-collapse border border-gray-200 text-xs">
                            <thead class="sticky top-0 z-10 bg-gray-50 shadow-sm">
                                <tr>
                                    <th class="px-1 py-1 border border-gray-200 text-center bg-gray-50">Date</th>
                                    <th class="px-1 py-1 border border-gray-200 text-center bg-gray-50">Narration</th>
                                    <th class="px-1 py-1 border border-gray-200 text-center bg-gray-50">Dr/Cr</th>
                                    <th class="px-1 py-1 border border-gray-200 text-center bg-gray-50">Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                $mobileRunningBalance = $openingBalance;
                                // Use the transaction lines directly without re-sorting
                                @endphp

                                @foreach($transactionLines as $line)
                                @php
                                // Apply the transaction to the running balance first
                                $mobileRunningBalance += ($line->debit_amount - $line->credit_amount);
                                @endphp
                                <tr class="transaction-row hover:bg-gray-50">
                                    <td class="px-1 py-1 border border-gray-200 text-center">{{ \Carbon\Carbon::parse($line->transaction->transaction_date)->format('d/m/y') }}</td>
                                    <td class="px-1 py-1 border border-gray-200">
                                        <div class="w-full break-words">{{ $line->narration }}</div>
                                    </td>
                                    <td class="px-1 py-1 border border-gray-200 text-center">
                                        @if($line->debit_amount > 0)
                                        <span class="text-green-600 font-medium">{{ number_format($line->debit_amount) }}</span>
                                        @else
                                        <span class="text-red-600 font-medium">{{ number_format($line->credit_amount) }}</span>
                                        @endif
                                    </td>
                                    <td class="px-1 py-1 border border-gray-200 text-center">
                                        <span class="{{ $mobileRunningBalance >= 0 ? 'text-green-600' : 'text-red-600' }} font-medium">
                                            {{ number_format(abs($mobileRunningBalance)) }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Closing Balance Display -->
                <div class="mt-3 p-3 bg-gray-50 rounded-lg shadow-sm">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium">Closing Balance:</span>
                        <span class="text-sm font-bold {{ $closingBalance['type'] === 'Dr' ? 'text-green-600' : 'text-red-600' }}">
                            {{ number_format($closingBalance['amount']) }} {{ $closingBalance['type'] }}
                        </span>
                    </div>
                </div>

                @role('admin')
                <div class="p-3 mt-4">
                    <a href="{{ route('admin.accounting.ledgers.index') }}" id="backToLedgersBtn"
                        class="block w-full sm:w-auto bg-gradient-to-r from-gray-500 to-gray-600 hover:from-gray-600 hover:to-gray-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 shadow-md flex items-center justify-center group text-xs sm:text-sm">
                        <span class="inline-flex items-center">
                            <svg id="backIcon" class="w-4 h-4 mr-1 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            <svg id="backSpinnerIcon" class="hidden w-4 h-4 mr-1 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span id="backButtonText">Back to Ledgers</span>
                        </span>
                    </a>
                </div>
                @endrole
            </div>
        </div>
    </div>


    @push('styles')
    <style>
        /* Ensure sticky headers work properly */
        .max-h-\[500px\] {
            position: relative;
        }

        .max-h-\[500px\] thead {
            position: sticky;
            top: 0;
            z-index: 20;
            background-color: #f9fafb;
            /* bg-gray-50 equivalent */
        }

        .max-h-\[500px\] thead::after {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            height: 1px;
            background-color: #e5e7eb;
            /* border-gray-200 equivalent */
        }

        /* Add shadow to sticky header */
        .max-h-\[500px\] thead th {
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }
    </style>
    @endpush

    @push('scripts')
    <script>
        $(document).ready(function() {
            // Back to ledgers button functionality
            $('#backToLedgersBtn').on('click', function() {
                const backIcon = document.getElementById('backIcon');
                const spinnerIcon = document.getElementById('backSpinnerIcon');
                const buttonText = document.getElementById('backButtonText');

                backIcon.classList.add('hidden');
                spinnerIcon.classList.remove('hidden');
                buttonText.textContent = 'Going back...';
            });

            // Refresh balance button functionality
            $('#refreshBalanceBtn').on('click', function() {
                const refreshIcon = document.getElementById('refreshIcon');
                const spinnerIcon = document.getElementById('refreshSpinnerIcon');
                const buttonText = document.getElementById('refreshButtonText');

                refreshIcon.classList.add('hidden');
                spinnerIcon.classList.remove('hidden');
                buttonText.textContent = 'Refreshing...';
            });

            // Month navigation buttons
            $('.prev-month-button, .next-month-button').on('click', function() {
                const originalContent = $(this).html();
                $(this).html('<svg class="w-5 h-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>');
            });

            // Highlight transaction rows on hover
            $('.transaction-row').hover(
                function() {
                    $(this).addClass('bg-blue-50');
                },
                function() {
                    $(this).removeClass('bg-blue-50');
                }
            );

            // Keyboard navigation for months
            $(document).keydown(function(e) {
                // Left arrow key
                if (e.keyCode == 37) {
                    $('.prev-month-button').click();
                }
                // Right arrow key
                else if (e.keyCode == 39) {
                    $('.next-month-button').click();
                }
            });

            // Make table headers sticky when scrolling
            const tableContainer = document.querySelector('.max-h-\\[500px\\]');
            if (tableContainer) {
                tableContainer.addEventListener('scroll', function() {
                    const headers = document.querySelectorAll('thead');
                    headers.forEach(header => {
                        header.style.transform = `translateY(${this.scrollTop}px)`;
                    });
                });
            }

            // Add swipe gestures for mobile
            let touchStartX = 0;
            let touchEndX = 0;

            document.addEventListener('touchstart', e => {
                touchStartX = e.changedTouches[0].screenX;
            }, false);

            document.addEventListener('touchend', e => {
                touchEndX = e.changedTouches[0].screenX;
                handleSwipe();
            }, false);

            function handleSwipe() {
                const threshold = 100; // Minimum distance for swipe

                if (touchEndX < touchStartX - threshold) {
                    // Swipe left - go to next month
                    $('.next-month-button').click();
                }

                if (touchEndX > touchStartX + threshold) {
                    // Swipe right - go to previous month
                    $('.prev-month-button').click();
                }
            }
        });
    </script>
    @endpush

    <!-- @push('scripts')
    <script>
        $(document).ready(function() {
            $('#backToLedgersBtn').on('click', function() {
                const backIcon = document.getElementById('backIcon');
                const spinnerIcon = document.getElementById('backSpinnerIcon');
                const buttonText = document.getElementById('backButtonText');

                backIcon.classList.add('hidden');
                spinnerIcon.classList.remove('hidden');
                buttonText.textContent = 'Going back...';
            });

            $('#refreshBalanceBtn').on('click', function() {
                const refreshIcon = document.getElementById('refreshIcon');
                const spinnerIcon = document.getElementById('refreshSpinnerIcon');
                const buttonText = document.getElementById('refreshButtonText');

                refreshIcon.classList.add('hidden');
                spinnerIcon.classList.remove('hidden');
                buttonText.textContent = 'Refreshing...';
            });
        });
    </script>
    @endpush -->
    @endsection