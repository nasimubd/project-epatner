@extends('super-admin.layouts.app')

@section('content')
<div class="bg-white shadow-md rounded-lg overflow-hidden">
    <div class="p-6 bg-white border-b border-gray-200">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Location Details</h2>
                <p class="text-gray-600 mt-1">View location information and usage statistics</p>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('super-admin.location-data.edit', $location_datum) }}"
                    class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition">
                    <i class="fas fa-edit mr-2"></i>Edit Location
                </a>
                <a href="{{ route('super-admin.location-data.index') }}"
                    class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition">
                    <i class="fas fa-arrow-left mr-2"></i>Back to List
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Location Information -->
            <div class="lg:col-span-2">
                <div class="bg-gray-50 rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Location Information</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500">District</label>
                            <p class="mt-1 text-sm text-gray-900 font-medium">{{ $location_datum->district }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-500">Sub District</label>
                            <p class="mt-1 text-sm text-gray-900 font-medium">{{ $location_datum->sub_district }}</p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-500">Village</label>
                            <p class="mt-1 text-sm text-gray-900 font-medium">{{ $location_datum->village }}</p>
                        </div>
                    </div>

                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <label class="block text-sm font-medium text-gray-500">Full Location</label>
                        <p class="mt-1 text-lg text-gray-900 font-medium">{{ $location_datum->full_location }}</p>
                    </div>
                </div>

                <!-- Timestamps -->
                <div class="mt-6 bg-gray-50 rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Record Information</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Created At</label>
                            <p class="mt-1 text-sm text-gray-900">
                                {{ $location_datum->created_at->format('F j, Y \a\t g:i A') }}
                                <span class="text-gray-500">({{ $location_datum->created_at->diffForHumans() }})</span>
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-500">Last Updated</label>
                            <p class="mt-1 text-sm text-gray-900">
                                {{ $location_datum->updated_at->format('F j, Y \a\t g:i A') }}
                                <span class="text-gray-500">({{ $location_datum->updated_at->diffForHumans() }})</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Usage Statistics -->
            <div class="lg:col-span-1">
                <div class="bg-blue-50 rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Usage Statistics</h3>

                    <div class="text-center">
                        <div class="text-3xl font-bold text-blue-600 mb-2">{{ $customerCount }}</div>
                        <p class="text-sm text-gray-600">Customer(s) using this location</p>
                    </div>

                    @if($customerCount > 0)
                    <div class="mt-4">
                        <a href="{{ route('super-admin.customer-ledgers.index', [
                                'district' => $location_datum->district,
                                'sub_district' => $location_datum->sub_district,
                                'village' => $location_datum->village
                            ]) }}"
                            class="w-full inline-flex justify-center items-center px-4 py-2 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 transition">
                            <i class="fas fa-users mr-2"></i>View Customers
                        </a>
                    </div>
                    @endif
                </div>

                <!-- Location Hierarchy -->
                <div class="mt-6 bg-green-50 rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Location Hierarchy</h3>

                    <div class="space-y-3">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-green-500 rounded-full mr-3"></div>
                            <span class="text-sm text-gray-700">{{ $location_datum->district }}</span>
                        </div>
                        <div class="flex items-center ml-6">
                            <div class="w-2 h-2 bg-green-400 rounded-full mr-3"></div>
                            <span class="text-sm text-gray-700">{{ $location_datum->sub_district }}</span>
                        </div>
                        <div class="flex items-center ml-12">
                            <div class="w-1 h-1 bg-green-200 rounded-full mr-3"></div>
                            <span class="text-sm text-gray-700 font-medium">{{ $location_datum->village }}</span>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="mt-6 bg-gray-50 rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Actions</h3>

                    <div class="space-y-3">
                        <a href="{{ route('super-admin.location-data.edit', $location_datum) }}"
                            class="w-full inline-flex justify-center items-center px-4 py-2 bg-indigo-600 text-white text-sm rounded hover:bg-indigo-700 transition">
                            <i class="fas fa-edit mr-2"></i>Edit Location
                        </a>

                        @if($customerCount == 0)
                        <form action="{{ route('super-admin.location-data.destroy', $location_datum) }}"
                            method="POST"
                            onsubmit="return confirm('Are you sure you want to delete this location? This action cannot be undone.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="w-full inline-flex justify-center items-center px-4 py-2 bg-red-600 text-white text-sm rounded hover:bg-red-700 transition">
                                <i class="fas fa-trash mr-2"></i>Delete Location
                            </button>
                        </form>
                        @else
                        <div class="p-3 bg-yellow-100 border border-yellow-300 rounded">
                            <p class="text-xs text-yellow-800">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                Cannot delete: Location is in use by {{ $customerCount }} customer(s)
                            </p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection