@extends('admin.layouts.app')

@section('content')
<div class="p-2 sm:p-4">
    <div class="max-w-xl mx-auto bg-white rounded-lg shadow">
        <div class="flex items-center justify-between p-3 border-b">
            <h2 class="text-lg font-medium">New Damage Transaction</h2>
            <a href="{{ route('admin.damage.index') }}" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </a>
        </div>

        <form action="{{ route('admin.damage.store') }}" method="POST" class="p-3 space-y-4">
            @csrf

            <div class="space-y-4">
                <!-- Customer and Date -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Customer</label>
                        <select name="customer_ledger_id" class="w-full rounded border-gray-300 text-sm">
                            <option value="">Select Customer</option>
                            @foreach($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                            @endforeach
                        </select>
                        @error('customer_ledger_id')<span class="text-xs text-red-500">{{ $message }}</span>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Transaction Date</label>
                        <input type="date" name="transaction_date" class="w-full rounded border-gray-300 text-sm">
                        @error('transaction_date')<span class="text-xs text-red-500">{{ $message }}</span>@enderror
                    </div>
                </div>

                <!-- Dynamic Damage Lines -->
                <div id="damage-lines">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Damage Items</label>
                    <div class="space-y-2" id="damage-items">
                        <div class="grid grid-cols-12 gap-2 items-end damage-item">
                            <div class="col-span-4">
                                <select name="damage_lines[0][product_id]" class="w-full rounded border-gray-300 text-sm">
                                    <option value="">Select Product</option>
                                    @foreach($products as $product)
                                    <option value="{{ $product->id }}">{{ $product->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-span-2">
                                <input type="number" name="damage_lines[0][quantity]" placeholder="Qty" step="0.01" class="w-full rounded border-gray-300 text-sm">
                            </div>
                            <div class="col-span-2">
                                <input type="number" name="damage_lines[0][unit_price]" placeholder="Price" step="0.01" class="w-full rounded border-gray-300 text-sm">
                            </div>
                            <div class="col-span-3">
                                <input type="text" name="damage_lines[0][damage_reason]" placeholder="Reason" class="w-full rounded border-gray-300 text-sm">
                            </div>
                            <div class="col-span-1">
                                <button type="button" class="text-red-600 hover:text-red-800 remove-item" onclick="removeItem(this)">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    <button type="button" onclick="addDamageItem()" class="mt-2 text-sm text-blue-600 hover:text-blue-800">+ Add Item</button>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-2 pt-4 border-t">
                <a href="{{ route('admin.damage.index') }}" class="px-3 py-1.5 text-sm border rounded text-gray-600 hover:bg-gray-50">Cancel</a>
                <button type="submit" class="px-3 py-1.5 text-sm rounded bg-blue-600 text-white hover:bg-blue-700">Create</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    let itemCount = 1;

    function addDamageItem() {
        const template = document.querySelector('.damage-item').cloneNode(true);
        template.querySelectorAll('input, select').forEach(input => {
            input.name = input.name.replace('[0]', `[${itemCount}]`);
            input.value = '';
        });
        document.getElementById('damage-items').appendChild(template);
        itemCount++;
    }

    function removeItem(button) {
        if (document.querySelectorAll('.damage-item').length > 1) {
            button.closest('.damage-item').remove();
        }
    }
</script>
@endpush
@endsection