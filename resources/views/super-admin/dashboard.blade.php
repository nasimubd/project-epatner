@extends('super-admin.layouts.app')

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8">
    <div class="bg-white rounded-lg shadow-lg p-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Super Admin Dashboard</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Business Stats Card -->
            <div class="bg-blue-50 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-blue-700">Total Businesses</h3>
                <p class="text-3xl font-bold text-blue-800">{{ $stats['businesses'] }}</p>
            </div>

            <!-- Admins Stats Card -->
            <div class="bg-green-50 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-green-700">Business Admins</h3>
                <p class="text-3xl font-bold text-green-800">{{ $stats['admins'] }}</p>
            </div>

            <!-- Staff Stats Card -->
            <div class="bg-purple-50 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-purple-700">Total Staff</h3>
                <p class="text-3xl font-bold text-purple-800">{{ $stats['users'] }}</p>
            </div>
        </div>
    </div>
    @endsection