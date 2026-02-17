@extends('super-admin.layouts.app')

@section('content')
<div class="bg-white shadow-md rounded-lg overflow-hidden">
    <div class="p-6 bg-white border-b border-gray-200">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Unit Details</h2>
            <div class="flex space-x-2">
                <a href="{{ route('super-admin.common-units.edit', $commonUnit) }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                    Edit
                </a>
                <a href="{{ route('super-admin.common-units.index') }}" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition">
                    Back to List
                </a>
            </div>
        </div>

        <div class="bg-gray-50 p-4 rounded-lg">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Unit ID</p>
                    <p class="text-lg font-semibold">{{ $commonUnit->unit_id }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Unit Name</p>
                    <p class="text-lg font-semibold">{{ $commonUnit->unit_name }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Created At</p>
                    <p class="text-lg font-semibold">{{ $commonUnit->created_at->format('M d, Y H:i A') }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Updated At</p>
                    <p class="text-lg font-semibold">{{ $commonUnit->updated_at->format('M d, Y H:i A') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection