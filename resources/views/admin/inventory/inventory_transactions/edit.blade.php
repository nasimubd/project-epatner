@extends('admin.layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Edit Transaction #{{ $inventoryTransaction->id }}</h1>
        <a href="{{ route('admin.inventory.inventory_transactions.show', $inventoryTransaction->id) }}"
            class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
            Back
        </a>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form id="editTransactionForm">
            @csrf
            @method('PUT')

            <!-- Transaction Details -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium mb-2">Entry Type</label>
                    <input type="text" value="{{ ucfirst($inventoryTransaction->entry_type) }}"
                        class="w-full border rounded px-3 py-2" readonly>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Transaction Date</label>
                    <input type="date" name="transaction_date"
                        value="{{ $inventoryTransaction->transaction_date }}"
                        class="w-full border rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Payment Method</label>
                    <select name="payment_method" class="w-full border rounded px-3 py-2">
                        <option value="cash" {{ $inventoryTransaction->payment_method === 'cash' ? 'selected' : '' }}>Cash</option>
                        <option value="credit" {{ $inventoryTransaction->payment_method === 'credit' ? 'selected' : '' }}>Credit</option>
                    </select>
                </div>
            </div>

            <!-- Products Table -->
            <div class="overflow-x-auto mb-6">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left">Product</th>
                            <th class="px-4 py-2 text-right">Quantity</th>
                            <th class="px-4 py-2 text-right">Unit Price</th>
                            <th class="px-4 py-2 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($inventoryTransaction->lines as $line)
                        <tr>
                            <td class="px-4 py-2">{{ $line->product->name }}</td>
                            <td class="px-4 py-2 text-right">
                                <input type="number" name="lines[{{ $loop->index }}][quantity]"
                                    value="{{ $line->quantity }}"
                                    class="border rounded px-2 py-1 w-24 text-right">
                            </td>
                            <td class="px-4 py-2 text-right">
                                <input type="number" name="lines[{{ $loop->index }}][unit_price]"
                                    value="{{ $line->unit_price }}"
                                    class="border rounded px-2 py-1 w-24 text-right">
                            </td>
                            <td class="px-4 py-2 text-right line-total">
                                {{ number_format($line->line_total, 2) }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Totals Section -->
            <div class="flex justify-end space-x-4 mb-6">
                <div>
                    <label class="block text-sm font-medium mb-2">Subtotal</label>
                    <input type="number" name="subtotal" value="{{ $inventoryTransaction->subtotal }}"
                        class="border rounded px-3 py-2" readonly>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Discount</label>
                    <input type="number" name="discount" value="{{ $inventoryTransaction->discount }}"
                        class="border rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Grand Total</label>
                    <input type="number" name="grand_total" value="{{ $inventoryTransaction->grand_total }}"
                        class="border rounded px-3 py-2" readonly>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded">
                    Update Transaction
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('editTransactionForm');

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(form);
            const transactionId = '{{ $inventoryTransaction->id }}';

            fetch(`/admin/inventory/inventory_transactions/${transactionId}`, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = `/admin/inventory/inventory_transactions/${transactionId}`;
                    } else {
                        alert(data.message || 'Failed to update transaction');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to update transaction');
                });
        });

        // Add event listeners for quantity and price changes to update totals
        document.querySelectorAll('input[name^="lines"]').forEach(input => {
            input.addEventListener('change', updateTotals);
        });

        function updateTotals() {
            let subtotal = 0;
            const rows = document.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const quantity = parseFloat(row.querySelector('input[name*="quantity"]').value);
                const unitPrice = parseFloat(row.querySelector('input[name*="unit_price"]').value);
                const lineTotal = quantity * unitPrice;

                row.querySelector('.line-total').textContent = lineTotal.toFixed(2);
                subtotal += lineTotal;
            });

            const discount = parseFloat(document.querySelector('input[name="discount"]').value) || 0;
            const grandTotal = subtotal - discount;

            document.querySelector('input[name="subtotal"]').value = subtotal.toFixed(2);
            document.querySelector('input[name="grand_total"]').value = grandTotal.toFixed(2);
        }
    });
</script>
@endpush
@endsection