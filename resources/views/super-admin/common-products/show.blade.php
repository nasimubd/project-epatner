@extends('super-admin.layouts.app')

@section('content')
<div class="bg-white shadow-md rounded-lg overflow-hidden">
    <div class="p-6 bg-white border-b border-gray-200">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Product Details</h2>
            <div class="flex space-x-2">
                <a href="{{ route('super-admin.common-products.edit', $commonProduct) }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                    Edit
                </a>
                <a href="{{ route('super-admin.common-products.index') }}" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition">
                    Back to List
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="md:col-span-1">
                <div class="bg-gray-50 p-4 rounded-lg">
                    @if($commonProduct->image)
                    <img src="{{ route('super-admin.common-products.image', $commonProduct) }}" alt="{{ $commonProduct->product_name }}" class="w-full h-auto rounded-lg">
                    @else
                    <div class="h-48 bg-gray-200 rounded-lg flex items-center justify-center">
                        <span class="text-gray-500">No image available</span>
                    </div>
                    @endif
                </div>
            </div>

            <div class="md:col-span-2">
                <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Product ID</p>
                            <p class="text-lg font-semibold">{{ $commonProduct->product_id }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Product Name</p>
                            <p class="text-lg font-semibold">{{ $commonProduct->product_name }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Barcode</p>
                            <p class="text-lg font-semibold">{{ $commonProduct->barcode ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Category</p>
                            <p class="text-lg font-semibold">{{ $commonProduct->category->category_name ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Unit</p>
                            <p class="text-lg font-semibold">{{ $commonProduct->unit->unit_name ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Created At</p>
                            <p class="text-lg font-semibold">{{ $commonProduct->created_at->format('M d, Y H:i A') }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Updated At</p>
                            <p class="text-lg font-semibold">{{ $commonProduct->updated_at->format('M d, Y H:i A') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection