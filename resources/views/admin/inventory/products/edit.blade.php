@extends('admin.layouts.app')

@section('content')
<div class="max-w-4xl mx-auto bg-white rounded-lg shadow-md p-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Edit Product</h1>

    @if($errors->any())
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('admin.inventory.products.update', $product->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="grid md:grid-cols-2 gap-6">
            <!-- Product Basic Info -->
            <div class="space-y-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Product Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $product->name) }}"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label for="barcode" class="block text-sm font-medium text-gray-700 mb-1">Barcode</label>
                    <input type="text" name="barcode" id="barcode" value="{{ old('barcode', $product->barcode) }}"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select name="category_id" id="category_id"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                        <option value="">Select Category</option>
                        @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="unit_id" class="block text-sm font-medium text-gray-700 mb-1">Unit of Measurement</label>
                    <select name="unit_id" id="unit_id"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                        <option value="">Select Unit</option>
                        @foreach($units as $unit)
                        <option value="{{ $unit->id }}" {{ old('unit_id', $product->unit_id) == $unit->id ? 'selected' : '' }}>
                            {{ $unit->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Product Stock Info -->
            <div class="space-y-6">
                <div>
                    <label for="quantity_alert" class="block text-sm font-medium text-gray-700 mb-1">Quantity Alert</label>
                    <input type="number" name="quantity_alert" id="quantity_alert" value="{{ old('quantity_alert', $product->quantity_alert) }}"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label for="opening_stock" class="block text-sm font-medium text-gray-700 mb-1">Opening Stock</label>
                    <input type="number" step="0.01" name="opening_stock" id="opening_stock" value="{{ old('opening_stock', $product->opening_stock) }}"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="opening_date" class="block text-sm font-medium text-gray-700 mb-1">Opening Date</label>
                        <input type="date" name="opening_date" id="opening_date" value="{{ old('opening_date', $product->opening_date) }}"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="expiry_date" class="block text-sm font-medium text-gray-700 mb-1">Expiry Date</label>
                        <input type="date" name="expiry_date" id="expiry_date" value="{{ old('expiry_date', $product->expiry_date) }}"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
            </div>
        </div>

        <!-- Pricing Section -->
        <div class="grid md:grid-cols-2 gap-6 pt-6 border-t border-gray-200">
            <div>
                <label for="dealer_price" class="block text-sm font-medium text-gray-700 mb-1">Dealer Price</label>
                <input type="number" step="0.01" name="dealer_price" id="dealer_price" value="{{ old('dealer_price', $product->dealer_price) }}"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label for="profit_margin" class="block text-sm font-medium text-gray-700 mb-1">Profit Margin (%)</label>
                <input type="number" step="0.01" name="profit_margin" id="profit_margin" value="{{ old('profit_margin', $product->profit_margin) }}"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label for="trade_price" class="block text-sm font-medium text-gray-700 mb-1">Trade Price</label>
                <input type="number" step="0.01" name="trade_price" id="trade_price" value="{{ old('trade_price', $product->trade_price) }}"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-gray-50" readonly>
            </div>

            <div>
                <label for="tax" class="block text-sm font-medium text-gray-700 mb-1">Tax</label>
                <input type="text" name="tax" id="tax" value="{{ old('tax', $product->tax) }}"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
        </div>

        <!-- Image Section -->
        <div class="pt-6 border-t border-gray-200">
            <label class="block text-sm font-medium text-gray-700 mb-2">Product Image</label>
            <div class="flex items-center space-x-6">
                @if($product->image)
                <div class="shrink-0">
                    <img src="data:image/jpeg;base64,{{ base64_encode($product->image) }}"
                        alt="{{ $product->name }}"
                        class="h-32 w-32 object-cover rounded-lg">
                </div>
                @endif
                <div class="flex-1">
                    <input type="file" name="image" id="image"
                        class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                </div>
            </div>
        </div>

        <div class="flex justify-end pt-6">
            <button type="submit"
                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                Update Product
            </button>
        </div>
    </form>
</div>
@endsection