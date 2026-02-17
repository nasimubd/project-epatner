@extends('admin.layouts.app')

@section('content')
<div class="min-h-screen bg-gray-100 flex items-center justify-center">
    <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-6">
        <div class="text-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Subscription Expired</h2>
            <p class="text-gray-600 mt-2">Please renew your subscription to continue using ePATNER</p>
        </div>

        <!-- Subscription Plans -->
        <div class="space-y-4 mb-6">
            @foreach($plans as $plan)
            <div class="border rounded-lg p-4 cursor-pointer plan-option hover:border-blue-500 transition-colors"
                data-plan-id="{{ $plan->id }}"
                data-price="{{ $plan->price }}"
                data-name="{{ $plan->name }}">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="font-semibold">{{ $plan->name }} Plan</h3>
                        <p class="text-sm text-gray-600">{{ $plan->duration_days }} days</p>
                    </div>
                    <div class="text-right">
                        <span class="text-xl font-bold text-blue-600">₹{{ number_format($plan->price, 2) }}</span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Payment Button -->
        <button id="payNowBtn" class="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors" disabled>
            Select a Plan to Continue
        </button>
    </div>

    <!-- QR Code Payment Modal -->
    <div id="qrModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-sm w-full mx-4 max-h-screen overflow-y-auto">
            <div class="text-center">
                <h3 class="text-lg font-semibold mb-4">Complete Your Payment</h3>

                <!-- QR Code Image -->
                <div class="mb-4 p-4 border-2 border-dashed border-gray-300 rounded-lg">
                    <img id="qrCodeImage" src="{{ asset($qrCodeImage) }}" alt="Payment QR Code" class="mx-auto max-w-full h-auto" style="max-width: 200px;">
                </div>

                <!-- Payment Details -->
                <div class="bg-blue-50 p-4 rounded-lg mb-4 text-left">
                    <div class="flex justify-between items-center mb-2">
                        <span class="font-medium">Amount:</span>
                        <span class="font-bold text-blue-600">₹<span id="paymentAmount">0</span></span>
                    </div>
                    <div class="flex justify-between items-center mb-2">
                        <span class="font-medium">Plan:</span>
                        <span id="selectedPlanName">-</span>
                    </div>
                    <div class="text-sm text-gray-600">
                        <strong>Transaction ID:</strong>
                        <span id="transactionId" class="font-mono text-xs">-</span>
                    </div>
                </div>

                <!-- Payment Instructions -->
                <div class="text-left mb-4 text-sm text-gray-600">
                    <h4 class="font-semibold mb-2">Payment Instructions:</h4>
                    <ol class="list-decimal list-inside space-y-1">
                        @foreach(config('payment.instructions') as $instruction)
                        <li>{{ $instruction }}</li>
                        @endforeach
                    </ol>
                </div>

                <!-- Payment Reference Input -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Payment Reference/UTR Number *
                    </label>
                    <input type="text" id="paymentReference"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Enter your payment reference number">
                    <p class="text-xs text-gray-500 mt-1">Enter the UTR/Reference number from your payment app</p>
                </div>

                <!-- Action Buttons -->
                <div class="flex space-x-3">
                    <button id="cancelPayment" class="flex-1 bg-gray-300 text-gray-700 py-2 rounded hover:bg-gray-400 transition-colors">
                        Cancel
                    </button>
                    <button id="markPaidBtn" class="flex-1 bg-green-600 text-white py-2 rounded hover:bg-green-700 transition-colors disabled:opacity-50">
                        I have Paid
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="successModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-sm w-full mx-4">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-4">
                    <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Payment Successful!</h3>
                <p class="text-gray-600 mb-4">Your subscription has been activated successfully.</p>
                <button id="continueBtn" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition-colors">
                    Continue to Dashboard
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        let selectedPlanId = null;
        let selectedPrice = null;
        let selectedPlanName = null;
        let currentTransactionId = null;

        const qrModal = document.getElementById('qrModal');
        const successModal = document.getElementById('successModal');
        const payNowBtn = document.getElementById('payNowBtn');
        const markPaidBtn = document.getElementById('markPaidBtn');
        const paymentReference = document.getElementById('paymentReference');

        // Plan selection
        document.querySelectorAll('.plan-option').forEach(option => {
            option.addEventListener('click', function() {
                // Remove previous selection
                document.querySelectorAll('.plan-option').forEach(opt => {
                    opt.classList.remove('border-blue-500', 'bg-blue-50');
                });

                // Add selection to current
                this.classList.add('border-blue-500', 'bg-blue-50');

                selectedPlanId = this.dataset.planId;
                selectedPrice = this.dataset.price;
                selectedPlanName = this.dataset.name;

                payNowBtn.disabled = false;
                payNowBtn.textContent = `Pay ₹${selectedPrice} - ${selectedPlanName}`;
            });
        });

        // Pay Now button click
        payNowBtn.addEventListener('click', function() {
            if (!selectedPlanId) return;

            // Show loading
            payNowBtn.disabled = true;
            payNowBtn.innerHTML = '<span class="animate-spin inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full mr-2"></span>Processing...';

            // Initiate payment
            fetch('{{ route("subscription.initiate-payment") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        plan_id: selectedPlanId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        currentTransactionId = data.transaction_id;

                        // Update modal content
                        document.getElementById('paymentAmount').textContent = data.amount;
                        document.getElementById('selectedPlanName').textContent = selectedPlanName;
                        document.getElementById('transactionId').textContent = data.transaction_id;

                        // Show QR modal
                        qrModal.classList.remove('hidden');
                        qrModal.classList.add('flex');

                        // Reset payment reference input
                        paymentReference.value = '';
                        markPaidBtn.disabled = false;
                    } else {
                        alert('Error initiating payment: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                })
                .finally(() => {
                    // Reset button
                    payNowBtn.disabled = false;
                    payNowBtn.textContent = `Pay ₹${selectedPrice} - ${selectedPlanName}`;
                });
        });

        // Cancel payment
        document.getElementById('cancelPayment').addEventListener('click', function() {
            qrModal.classList.add('hidden');
            qrModal.classList.remove('flex');
            currentTransactionId = null;
        });

        // Mark as paid button
        markPaidBtn.addEventListener('click', function() {
            const reference = paymentReference.value.trim();

            if (!reference) {
                alert('Please enter your payment reference number');
                paymentReference.focus();
                return;
            }

            if (!currentTransactionId) {
                alert('Transaction ID not found. Please try again.');
                return;
            }

            // Show loading
            markPaidBtn.disabled = true;
            markPaidBtn.innerHTML = '<span class="animate-spin inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full mr-2"></span>Verifying...';

            // Mark payment as done
            fetch('{{ route("subscription.mark-payment-done") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        transaction_id: currentTransactionId,
                        payment_reference: reference
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Hide QR modal
                        qrModal.classList.add('hidden');
                        qrModal.classList.remove('flex');

                        // Show success modal
                        successModal.classList.remove('hidden');
                        successModal.classList.add('flex');
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                })
                .finally(() => {
                    // Reset button
                    markPaidBtn.disabled = false;
                    markPaidBtn.textContent = 'I have Paid';
                });
        });

        // Continue button in success modal
        document.getElementById('continueBtn').addEventListener('click', function() {
            // Redirect to dashboard or reload page
            window.location.href = '{{ route("admin.dashboard") }}';
        });

        // Close modals when clicking outside
        [qrModal, successModal].forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                }
            });
        });

        // Enable/disable mark paid button based on reference input
        paymentReference.addEventListener('input', function() {
            markPaidBtn.disabled = this.value.trim().length === 0;
        });
    });
</script>

@endsection