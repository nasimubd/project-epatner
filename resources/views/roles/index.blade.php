@extends('super-admin.layouts.app')


@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <div class="mb-4">
                    <a href="{{ route('roles.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Create New Role
                    </a>
                </div>

                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 border-b-2">Name</th>
                            <th class="px-6 py-3 border-b-2">Permissions</th>
                            <th class="px-6 py-3 border-b-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($roles as $role)
                        <tr>
                            <td class="px-6 py-4 border-b">{{ $role->name }}</td>
                            <td class="px-6 py-4 border-b">
                                @foreach($role->permissions as $permission)
                                <span class="inline-block bg-gray-200 rounded-full px-3 py-1 text-sm font-semibold text-gray-700 mr-2">
                                    {{ $permission->name }}
                                </span>
                                @endforeach
                            </td>
                            <td class="px-6 py-4 border-b">
                                <a href="{{ route('roles.edit', $role) }}" class="text-blue-600 hover:text-blue-900">Edit</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection