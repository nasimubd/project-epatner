@extends('super-admin.layouts.app')

@section('content')
<div class="max-w-2xl mx-auto bg-white p-6 rounded-lg shadow">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Edit Business Admin</h2>
        <a href="{{ route('super-admin.admins.index') }}" class="text-blue-500 hover:text-blue-700">
            Back to List
        </a>
    </div>

    <form action="{{ route('super-admin.admins.update', $admin) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Select Business</label>
                <select name="business_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach($businesses as $business)
                    <option value="{{ $business->id }}" {{ $admin->business_id == $business->id ? 'selected' : '' }}>
                        {{ $business->name }}
                    </option>
                    @endforeach
                </select>
                @error('business_id')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Admin Name</label>
                <input type="text"
                    name="name"
                    value="{{ old('name', $admin->user->name) }}"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @error('name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email"
                    name="email"
                    value="{{ old('email', $admin->user->email) }}"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end space-x-3">
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
                    Update Admin
                </button>
            </div>
        </div>
    </form>
</div>

<script>
    // Initialize Select2 for business selection
    $(document).ready(function() {
        $('select[name="business_id"]').select2({
            placeholder: 'Search for a business...',
            minimumInputLength: 2,
            ajax: {
                url: '{{ route("super-admin.businesses.search") }}',
                dataType: 'json',
                delay: 250,
                processResults: function(data) {
                    return {
                        results: data
                    };
                }
            }
        });
    });
</script>
@endsection