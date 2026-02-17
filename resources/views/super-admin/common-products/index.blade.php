@extends('super-admin.layouts.app')

@section('content')
<div class="bg-white shadow-md rounded-lg overflow-hidden">
    <div class="p-6 bg-white border-b border-gray-200">
        <div class="flex space-x-2">
            <a href="{{ route('super-admin.common-products.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                Add New Product
            </a>
            <a href="{{ route('super-admin.common-products.import') }}" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition">
                Import Products
            </a>
        </div>

        <!-- Search and Filter Form -->
        <!-- Search and Filter Form -->
        <div class="mb-6">
            <form action="{{ route('super-admin.common-products.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search products..."
                        class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <select name="category_id" class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                        <option value="{{ $category->category_id }}" {{ request('category_id') == $category->category_id ? 'selected' : '' }}>
                            {{ $category->category_name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 transition">
                        Filter
                    </button>
                    @if(request()->hasAny(['search', 'category_id']))
                    <a href="{{ route('super-admin.common-products.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition">
                        Clear
                    </a>
                    @endif
                </div>
            </form>
        </div>


        <!-- Products Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
                        <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product Name</th>
                        <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Barcode</th>
                        <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit</th>
                        <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($products as $product)
                    <tr class="hover:bg-gray-50">
                        <td class="py-4 px-6 text-sm text-gray-500">{{ $product->product_id }}</td>
                        <td class="py-4 px-6 text-sm text-gray-500">
                            @if($product->image)
                            <img src="{{ route('super-admin.common-products.image', $product) }}" alt="{{ $product->product_name }}" class="h-10 w-10 object-cover rounded">
                            @else
                            <div class="h-10 w-10 bg-gray-200 rounded flex items-center justify-center">
                                <span class="text-gray-500 text-xs">No image</span>
                            </div>
                            @endif
                        </td>
                        <td class="py-4 px-6 text-sm font-medium text-gray-900">{{ $product->product_name }}</td>
                        <td class="py-4 px-6 text-sm text-gray-500">{{ $product->barcode ?? 'N/A' }}</td>
                        <td class="py-4 px-6 text-sm text-gray-500">{{ $product->category->category_name ?? 'N/A' }}</td>
                        <td class="py-4 px-6 text-sm text-gray-500">{{ $product->unit->unit_name ?? 'N/A' }}</td>
                        <td class="py-4 px-6 text-sm font-medium">
                            <div class="flex space-x-2">
                                <a href="{{ route('super-admin.common-products.show', $product) }}" class="text-indigo-600 hover:text-indigo-900">View</a>
                                <a href="{{ route('super-admin.common-products.edit', $product) }}" class="text-blue-600 hover:text-blue-900">Edit</a>
                                <form action="{{ route('super-admin.common-products.destroy', $product) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this product?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-4 px-6 text-sm text-center text-gray-500">No products found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <!-- Pagination -->
        <div class="mt-4">
            {{ $products->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection