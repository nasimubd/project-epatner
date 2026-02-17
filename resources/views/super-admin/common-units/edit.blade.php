@extends('super-admin.layouts.app')

@section('content')
<div class="bg-white shadow-md rounded-lg overflow-hidden">
    <div class="p-6 bg-white border-b border-gray-200">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Edit Unit</h2>
            <p class="text-gray-600 mt-1">Update the unit information</p>
        </div>

        <form action="{{ route('super-admin.common-units.update', $commonUnit) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-6">
                <label for="unit_name" class="block text-sm font-medium text-gray-700 mb-1">Unit Name</label>
                <input type="text" name="unit_name" id="unit_name" value="{{ old('unit_name', $commonUnit->unit_name) }}"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('unit_name') border-red-500 @enderror"
                    required>
                @error('unit_name')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-end space-x-3">
                <a href="{{ route('super-admin.common-units.index') }}" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition">
                    Cancel
                </a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                    Update Unit
                </button>
            </div>
        </form>
    </div>
</div>
@endsection