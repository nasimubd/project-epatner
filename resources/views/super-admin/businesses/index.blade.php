@extends('super-admin.layouts.app')

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Businesses</h2>
        <a href="{{ route('super-admin.businesses.create') }}"
            class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
            Add New Business
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        {{ session('success') }}
    </div>
    @endif

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">District</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($businesses as $business)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $business->name }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $business->district }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $business->contact_number }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $business->email }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex space-x-2">
                            <a href="{{ route('super-admin.businesses.edit', $business) }}"
                                class="text-indigo-600 hover:text-indigo-900">Edit</a>
                            <a href="{{ route('super-admin.admins.create', ['business' => $business->id]) }}"
                                class="text-green-600 hover:text-green-900">Add Admin</a>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $businesses->links() }}
    </div>
</div>
@endsection