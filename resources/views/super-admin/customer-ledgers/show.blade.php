@extends('super-admin.layouts.app')

@section('content')
<div class="py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
            <div class="px-6 py-4 bg-blue-600 text-white">
                <div class="flex items-center justify-between">
                    <h1 class="text-xl font-bold">Customer Details</h1>
                </div>
            </div>
        </div>

        <!-- Customer Information -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b">
                <h2 class="text-lg font-semibold text-gray-800">Basic Information</h2>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <!-- Customer Name with Copy -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Customer Name</label>
                        <div class="flex items-center space-x-2">
                            <div class="flex-1 p-3 bg-gray-50 rounded border">
                                <span id="customerName" class="text-gray-900 font-medium">{{ $customerLedger->ledger_name }}</span>
                            </div>
                            <button onclick="copyCustomerName()"
                                class="p-2 bg-blue-100 hover:bg-blue-200 text-blue-600 rounded transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <!-- Phone Number -->
                    @if($customerLedger->contact_number)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                        <div class="p-3 bg-gray-50 rounded border">
                            <span class="text-gray-900">{{ $customerLedger->contact_number }}</span>
                        </div>
                    </div>
                    @endif

                    <!-- Location -->
                    @if($customerLedger->district || $customerLedger->sub_district || $customerLedger->village)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Location</label>
                        <div class="p-3 bg-gray-50 rounded border">
                            <span class="text-gray-900">
                                @php
                                $locationParts = [];
                                if($customerLedger->village) $locationParts[] = $customerLedger->village;
                                if($customerLedger->sub_district) $locationParts[] = $customerLedger->sub_district;
                                if($customerLedger->district) $locationParts[] = $customerLedger->district;
                                echo implode(', ', $locationParts);
                                @endphp
                            </span>
                        </div>
                    </div>
                    @endif

                    <!-- Landmark -->
                    @if($customerLedger->landmark)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Landmark</label>
                        <div class="p-3 bg-gray-50 rounded border">
                            <span class="text-gray-900">{{ $customerLedger->landmark }}</span>
                        </div>
                    </div>
                    @endif


                    <!-- Created Date -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Created Date</label>
                        <div class="p-3 bg-gray-50 rounded border">
                            <span class="text-gray-900">{{ $customerLedger->created_at->format('M d, Y') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="mt-6 flex space-x-3">
                    @role('admin')
                    <a href="{{ route('super-admin.customer-ledgers.edit', $customerLedger->ledger_id) }}"
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
                        Edit Customer
                    </a>
                    @endrole
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Success Message -->
<div id="successMessage" class="fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded shadow-lg hidden">
    Customer name copied to clipboard!
</div>

<script>
    function copyCustomerName() {
        var customerName = document.getElementById('customerName').textContent;

        // Create temporary textarea
        var textarea = document.createElement('textarea');
        textarea.value = customerName;
        document.body.appendChild(textarea);

        // Select and copy
        textarea.select();
        document.execCommand('copy');

        // Remove textarea
        document.body.removeChild(textarea);

        // Show success message
        var message = document.getElementById('successMessage');
        message.classList.remove('hidden');

        // Hide message after 2 seconds
        setTimeout(function() {
            message.classList.add('hidden');
        }, 2000);
    }
</script>

@endsection