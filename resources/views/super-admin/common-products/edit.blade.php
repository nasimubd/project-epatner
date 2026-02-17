@extends('super-admin.layouts.app')

@section('content')
<div class="bg-white shadow-md rounded-lg overflow-hidden">
    <div class="p-6 bg-white border-b border-gray-200">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Edit Product</h2>
            <p class="text-gray-600 mt-1">Update the product information</p>
        </div>

        <form action="{{ route('super-admin.common-products.update', $commonProduct) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="product_name" class="block text-sm font-medium text-gray-700 mb-1">Product Name</label>
                    <input type="text" name="product_name" id="product_name" value="{{ old('product_name', $commonProduct->product_name) }}"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('product_name') border-red-500 @enderror"
                        required>
                    @error('product_name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="barcode" class="block text-sm font-medium text-gray-700 mb-1">Barcode (Optional)</label>
                    <input type="text" name="barcode" id="barcode" value="{{ old('barcode', $commonProduct->barcode) }}"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('barcode') border-red-500 @enderror">
                    @error('barcode')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select name="category_id" id="category_id"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('category_id') border-red-500 @enderror"
                        required>
                        <option value="">Select Category</option>
                        @foreach($categories as $category)
                        <option value="{{ $category->category_id }}" {{ (old('category_id', $commonProduct->category_id) == $category->category_id) ? 'selected' : '' }}>
                            {{ $category->category_name }}
                        </option>
                        @endforeach
                    </select>
                    @error('category_id')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="unit_id" class="block text-sm font-medium text-gray-700 mb-1">Unit</label>
                    <select name="unit_id" id="unit_id"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('unit_id') border-red-500 @enderror"
                        required>
                        <option value="">Select Unit</option>
                        @foreach($units as $unit)
                        <option value="{{ $unit->unit_id }}" {{ (old('unit_id', $commonProduct->unit_id) == $unit->unit_id) ? 'selected' : '' }}>
                            {{ $unit->unit_name }}
                        </option>
                        @endforeach
                    </select>
                    @error('unit_id')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mb-6">
                <label for="image" class="block text-sm font-medium text-gray-700 mb-1">Product Image (Optional)</label>

                @if($commonProduct->image)
                <div class="mb-2">
                    <img src="{{ route('super-admin.common-products.image', $commonProduct) }}" alt="{{ $commonProduct->product_name }}" class="h-32 w-32 object-cover rounded">
                </div>
                @endif

                <input type="file" name="image" id="image"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('image') border-red-500 @enderror"
                    accept="image/*">
                <p class="text-xs text-gray-500 mt-1">Leave empty to keep the current image</p>
                @error('image')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-end space-x-3">
                <a href="{{ route('super-admin.common-products.index') }}" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition">
                    Cancel
                </a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                    Update Product
                </button>
            </div>
        </form>
    </div>
</div>
@endsection