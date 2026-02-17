@extends('super-admin.layouts.app')

@section('content')
<div class="bg-white shadow-md rounded-lg overflow-hidden">
    <div class="p-6 bg-white border-b border-gray-200">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Common Units</h2>
            <a href="{{ route('super-admin.common-units.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                Add New Unit
            </a>
        </div>

        <!-- Search Form -->
        <div class="mb-6">
            <form action="{{ route('super-admin.common-units.index') }}" method="GET" class="flex gap-4">
                <div class="flex-1">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search units..."
                        class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500">
                </div>
                <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 transition">
                    Search
                </button>
                @if(request()->has('search'))
                <a href="{{ route('super-admin.common-units.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition">
                    Clear
                </a>
                @endif
            </form>
        </div>

        <!-- Units Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Name</th>
                        <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($units as $unit)
                    <tr class="hover:bg-gray-50">
                        <td class="py-4 px-6 text-sm text-gray-500">{{ $unit->unit_id }}</td>
                        <td class="py-4 px-6 text-sm font-medium text-gray-900">{{ $unit->unit_name }}</td>
                        <td class="py-4 px-6 text-sm font-medium">
                            <div class="flex space-x-2">
                                <a href="{{ route('super-admin.common-units.edit', $unit) }}" class="text-blue-600 hover:text-blue-900">Edit</a>
                                <form action="{{ route('super-admin.common-units.destroy', $unit) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this unit?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="py-4 px-6 text-sm text-center text-gray-500">No units found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-4">
            {{ $units->links() }}
        </div>
    </div>
</div>
@endsection