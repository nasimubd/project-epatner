@extends('super-admin.layouts.app')

@section('content')
<div class="bg-white shadow-md rounded-lg overflow-hidden">
    <div class="p-6 bg-white border-b border-gray-200">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Category Details</h2>
            <div class="flex space-x-2">
                <a href="{{ route('super-admin.common-categories.edit', $commonCategory) }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                    Edit
                </a>
                <a href="{{ route('super-admin.common-categories.index') }}" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition">
                    Back to List
                </a>
            </div>
        </div>

        <div class="bg-gray-50 p-4 rounded-lg">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Category ID</p>
                    <p class="text-lg font-semibold">{{ $commonCategory->category_id }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Category Name</p>
                    <p class="text-lg font-semibold">{{ $commonCategory->category_name }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Slug</p>
                    <p class="text-lg font-semibold">{{ $commonCategory->slug }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Created At</p>
                    <p class="text-lg font-semibold">{{ $commonCategory->created_at->format('M d, Y H:i A') }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Updated At</p>
                    <p class="text-lg font-semibold">{{ $commonCategory->updated_at->format('M d, Y H:i A') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection