@extends('admin.layouts.app')

@section('content')
<div class="container mx-auto">
    <!-- Action buttons outside receipt -->
    <div class="flex justify-center mb-4 print:hidden">
        <div class="flex gap-2">
            <a href="{{ route('admin.accounting.deposit.index') }}"
                class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L4.414 9H17a1 1 0 110 2H4.414l5.293 5.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                </svg>
                Back
            </a>
            <button onclick="printReceipt()" class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">Print</button>
        </div>
    </div>

    <!-- Thermal Receipt -->
    <div class="w-[80mm] mx-auto p-2 bg-white">
        <!-- Business Header -->
        <div class="text-center">
            <h2 class="font-bold text-xl">{{ $depositSlip->business->name }}</h2>
            <p class="text-sm">Deposit Slip #{{ $depositSlip->id }}</p>
            <p class="text-sm">Date: {{ $depositSlip->created_at->format('d/m/Y h:i A') }}</p>
            <p class="text-sm">Created By: {{ $depositSlip->user->name }}</p>
        </div>

        <div class="border-b border-dashed my-2"></div>

        <!-- Collection Amount Section -->
        <div class="mb-2">
            <table class="w-full text-sm">
                <tr class="font-bold">
                    <td class="py-1">Total Collection Amount:</td>
                    <td class="text-right py-1">৳{{ number_format($depositSlip->total_collection) }}</td>
                </tr>
                @if($depositSlip->due_collection > 0)
                <tr class="font-bold">
                    <td class="py-1">Due Collection Amount:</td>
                    <td class="text-right py-1">৳{{ number_format($depositSlip->due_collection) }}</td>
                </tr>
                <tr class="font-bold border-t border-dashed">
                    <td class="py-1">Todays Net Cash:</td>
                    <td class="text-right py-1">৳{{ number_format($depositSlip->total_collection + $depositSlip->due_collection) }}</td>
                </tr>
                @endif
            </table>
        </div>

        <div class="border-b border-dashed my-2"></div>

        <!-- Note Denominations -->
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-dashed">
                    <th class="text-left py-1">Note</th>
                    <th class="text-right py-1">Count</th>
                    <th class="text-right py-1">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($depositSlip->note_denominations as $note => $count)
                @if($count > 0)
                <tr>
                    <td class="py-1">৳{{ number_format($note) }}</td>
                    <td class="text-right py-1">{{ $count }}</td>
                    <td class="text-right py-1">৳{{ number_format($note * $count) }}</td>
                </tr>
                @endif
                @endforeach
            </tbody>
        </table>

        @if($depositSlip->remarks && $depositSlip->remarks != '[]')
        <div class="border-b border-dashed my-2"></div>
        <div class="mb-2">
            <h3 class="font-bold">Expenses</h3>
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-dashed">
                        <th class="text-left py-1">Description</th>
                        <th class="text-right py-1">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(json_decode($depositSlip->remarks, true) as $expense)
                    @if(!empty($expense['description']) && !empty($expense['amount']))
                    <tr>
                        <td class="py-1">{{ $expense['description'] }}</td>
                        <td class="text-right py-1">৳{{ number_format($expense['amount']) }}</td>
                    </tr>
                    @endif
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        @if($depositSlip->damage_lines && count($depositSlip->damage_lines) > 0)
        <div class="border-b border-dashed my-2"></div>
        <div class="mb-2">
            <h3 class="font-bold">Damage</h3>
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-dashed">
                        <th class="text-left py-1">Company</th>
                        <th class="text-right py-1">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($depositSlip->damage_lines as $damage)
                    @if(!empty($damage['amount']) && !empty($damage['supplier_ledger_id']))
                    <tr>
                        <td class="py-1">
                            @php
                            $supplierLedger = \App\Models\Ledger::find($damage['supplier_ledger_id']);
                            @endphp
                            {{ $supplierLedger ? $supplierLedger->name : 'Unknown Supplier' }}
                        </td>
                        <td class="text-right py-1">৳{{ number_format($damage['amount']) }}</td>
                    </tr>
                    @endif
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        @if($depositSlip->market_short > 0)
        <div class="border-b border-dashed my-2"></div>
        <div class="mb-2">
            <h3 class="font-bold">Market Short</h3>
            <table class="w-full text-sm">
                <tr>
                    <td class="py-1">Market Short Amount:</td>
                    <td class="text-right py-1">৳{{ number_format($depositSlip->market_short) }}</td>
                </tr>
            </table>
        </div>
        @endif

        @if($depositSlip->godown_short > 0)
        <div class="border-b border-dashed my-2"></div>
        <div class="mb-2">
            <h3 class="font-bold">Godown Short</h3>
            <table class="w-full text-sm">
                <tr>
                    <td class="py-1">Godown Short Amount:</td>
                    <td class="text-right py-1">৳{{ number_format($depositSlip->godown_short) }}</td>
                </tr>
            </table>
        </div>
        @endif

        <div class="border-b-2 border-black my-2"></div>

        <!-- Totals Section -->
        <table class="w-full text-sm">
            <tr>
                <td class="py-1">Note Count Cash:</td>
                <td class="text-right py-1">৳{{ number_format($depositSlip->total_amount) }}</td>
            </tr>
            @if($depositSlip->remarks && $depositSlip->remarks != '[]')
            <tr>
                <td class="py-1">Total Expenses:</td>
                <td class="text-right py-1">৳{{ number_format(collect(json_decode($depositSlip->remarks, true))->sum('amount')) }}</td>
            </tr>
            @endif
            @if($depositSlip->damage_lines && count($depositSlip->damage_lines) > 0)
            <tr>
                <td class="py-1">Total Damages:</td>
                <td class="text-right py-1">৳{{ number_format(collect($depositSlip->damage_lines)->sum('amount')) }}</td>
            </tr>
            @endif
            @if($depositSlip->market_short > 0)
            <tr>
                <td class="py-1">Market Short:</td>
                <td class="text-right py-1">৳{{ number_format($depositSlip->market_short) }}</td>
            </tr>
            @endif
            @if($depositSlip->godown_short > 0)
            <tr>
                <td class="py-1">Godown Short:</td>
                <td class="text-right py-1">৳{{ number_format($depositSlip->godown_short) }}</td>
            </tr>
            @endif
            @if($depositSlip->due_collection > 0)
            <tr>
                <td class="py-1">Due Collection:</td>
                <td class="text-right py-1">৳{{ number_format($depositSlip->due_collection) }}</td>
            </tr>
            @endif
            <tr class="font-bold">
                <td class="py-1">Collectible:</td>
                <td class="text-right py-1 {{ $depositSlip->net_total <= 0 ? 'text-green-600' : 'text-red-600' }}">
                    ৳{{ number_format($depositSlip->net_total) }}
                </td>
            </tr>
        </table>

        <!-- Status -->
        <div class="text-center mt-4">
            <p class="text-sm">Status:
                <span class="font-bold {{ $depositSlip->status === 'approved' ? 'text-green-600' : ($depositSlip->status === 'rejected' ? 'text-red-600' : 'text-yellow-600') }}">
                    {{ ucfirst($depositSlip->status) }}
                </span>
            </p>
        </div>

        <!-- Footer -->
        <div class="text-center mt-4 text-sm">
            <p>Thank You!</p>
            <p class="text-xs mt-2">Powered By <span class="font-bold text-blue-600">ePATNER</span></p>
        </div>
    </div>
</div>

<style media="print">
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
</style>

<script>
    function printReceipt() {
        window.print();
    }
</script>
@endsection