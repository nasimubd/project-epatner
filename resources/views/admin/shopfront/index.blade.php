@extends('admin.layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-indigo-50">
    <div class="container mx-auto px-4 py-6 max-w-7xl">
        <!-- Header Section -->
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-8 space-y-4 sm:space-y-0">
            <div class="flex items-center space-x-3">
                <div class="p-3 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-xl shadow-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">Shopfront Management</h1>
                    <p class="text-gray-600 text-sm sm:text-base">Manage your online store presence</p>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="flex items-center space-x-4">
                <div class="bg-white rounded-lg px-4 py-2 shadow-sm border">
                    <div class="text-xs text-gray-500 uppercase tracking-wide">Status</div>
                    <div class="font-semibold {{ $shopfront && $shopfront->is_active ? 'text-green-600' : 'text-gray-400' }}">
                        {{ $shopfront && $shopfront->is_active ? 'Live' : 'Inactive' }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Success Alert -->
        @if(session('success'))
        <div class="mb-6 transform transition-all duration-300 ease-in-out">
            <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-l-4 border-green-500 rounded-r-lg p-4 shadow-sm">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-green-700 font-medium">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Shopfront Status Card -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
                    <!-- Card Header -->
                    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xl font-bold text-white flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                Shopfront Status
                            </h2>
                            @if($shopfront)
                            <div class="flex items-center space-x-2">
                                <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                                <span class="text-green-100 text-sm font-medium">Active</span>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Card Content -->
                    <div class="p-6">
                        @if($shopfront)
                        <!-- Shopfront Details -->
                        <div class="space-y-6">

                            <!-- Orders Management Card -->
                            <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
                                <!-- Orders Header -->
                                <div class="bg-gradient-to-r from-emerald-600 to-teal-600 px-6 py-4">
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-2 sm:space-y-0">
                                        <h2 class="text-xl font-bold text-white flex items-center">
                                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                            </svg>
                                            Shopfront Orders
                                        </h2>
                                        <a href="{{ route('admin.shopfront.orders.index') }}"
                                            class="inline-flex items-center px-4 py-2 bg-white/20 hover:bg-white/30 text-white font-medium rounded-lg transition-all duration-200 space-x-2 backdrop-blur-sm">
                                            <span>View All Orders</span>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                            </svg>
                                        </a>
                                    </div>
                                </div>

                                <!-- Orders Content -->
                                <div class="p-6">
                                    <div class="flex items-center justify-center py-8">
                                        <div class="text-center max-w-md">
                                            <div class="mx-auto w-16 h-16 bg-emerald-100 rounded-full flex items-center justify-center mb-4">
                                                <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                                                </svg>
                                            </div>
                                            <h3 class="text-lg font-semibold text-gray-800 mb-2">Order Management</h3>
                                            <p class="text-gray-600 mb-6">
                                                Manage all your shopfront orders efficiently from the dedicated orders dashboard.
                                                Track, process, and fulfill customer orders seamlessly.
                                            </p>
                                            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                                                <a href="{{ route('admin.shopfront.orders.index') }}"
                                                    class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200 space-x-2">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                                    </svg>
                                                    <span>Orders Dashboard</span>
                                                </a>
                                                @if($shopfront && $shopfront->is_active)
                                                <a href="{{ $shopfrontUrl }}" target="_blank"
                                                    class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-500 to-indigo-500 hover:from-blue-600 hover:to-indigo-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200 space-x-2">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                    </svg>
                                                    <span>Preview Store</span>
                                                </a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Status and ID Row -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="bg-gray-50 rounded-xl p-4">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-medium text-gray-600">Current Status</span>
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $shopfront->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $shopfront->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                </div>
                                <div class="bg-gray-50 rounded-xl p-4">
                                    <span class="text-sm font-medium text-gray-600">Shopfront ID</span>
                                    <p class="font-mono text-sm text-gray-800 mt-1">{{ $shopfront->shopfront_id }}</p>
                                </div>
                            </div>

                            <!-- Shopfront URL -->
                            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-4 border border-blue-100">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <span class="text-sm font-medium text-blue-800">Shopfront URL</span>
                                        <div class="mt-2 flex items-center space-x-2">
                                            <a href="{{ $shopfrontUrl }}" target="_blank"
                                                class="text-blue-600 hover:text-blue-800 font-medium break-all transition-colors duration-200">
                                                {{ $shopfrontUrl }}
                                            </a>
                                        </div>
                                    </div>
                                    <div class="flex space-x-2 ml-4">
                                        <button onclick="copyToClipboard('{{ $shopfrontUrl }}')"
                                            class="p-2 bg-blue-100 hover:bg-blue-200 rounded-lg transition-colors duration-200 group">
                                            <svg class="w-4 h-4 text-blue-600 group-hover:text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                            </svg>
                                        </button>
                                        <a href="{{ $shopfrontUrl }}" target="_blank"
                                            class="p-2 bg-green-100 hover:bg-green-200 rounded-lg transition-colors duration-200 group">
                                            <svg class="w-4 h-4 text-green-600 group-hover:text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex flex-col sm:flex-row gap-3">
                                <form action="{{ route('admin.shopfront.toggle-status') }}" method="POST" class="flex-1">
                                    @csrf
                                    <button type="submit"
                                        class="w-full px-6 py-3 bg-gradient-to-r {{ $shopfront->is_active ? 'from-red-500 to-red-600 hover:from-red-600 hover:to-red-700' : 'from-green-500 to-green-600 hover:from-green-600 hover:to-green-700' }} text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-center space-x-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            @if($shopfront->is_active)
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L5.636 5.636"></path>
                                            @else
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            @endif
                                        </svg>
                                        <span>{{ $shopfront->is_active ? 'Deactivate' : 'Activate' }} Shopfront</span>
                                    </button>
                                </form>

                                <form action="{{ route('admin.shopfront.generate') }}" method="POST" class="flex-1">
                                    @csrf
                                    <button type="submit"
                                        class="w-full px-6 py-3 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-center space-x-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                        <span>Regenerate QR Code</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                        @else
                        <!-- No Shopfront State -->
                        <div class="text-center py-12">
                            <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-6">
                                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-800 mb-2">No Shopfront Yet</h3>
                            <p class="text-gray-600 mb-6 max-w-md mx-auto">You haven't generated a shopfront for your business yet. Create one to start selling online!</p>

                            <form action="{{ route('admin.shopfront.generate') }}" method="POST">
                                @csrf
                                <button type="submit"
                                    class="px-8 py-4 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200 flex items-center space-x-3 mx-auto">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    <span>Generate Shopfront</span>
                                </button>
                            </form>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- QR Code Card -->
            @if($shopfront)
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden h-fit">
                    <!-- QR Card Header -->
                    <div class="bg-gradient-to-r from-purple-600 to-pink-600 px-6 py-4">
                        <h3 class="text-lg font-bold text-white flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                            </svg>
                            QR Code
                        </h3>
                    </div>

                    <!-- QR Code Content -->
                    <div class="p-6 text-center">
                        <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-2xl p-6 mb-4 inline-block shadow-inner">
                            <div class="bg-white p-4 rounded-xl shadow-sm">
                                {!! $shopfront->qr_code !!}
                            </div>
                        </div>
                        <p class="text-sm text-gray-600 leading-relaxed">
                            <svg class="w-4 h-4 inline mr-1 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Scan this QR code to access your business shopfront
                        </p>

                        <!-- QR Actions -->
                        <div class="mt-4 space-y-2">
                            <button onclick="downloadQR()"
                                class="w-full px-4 py-2 bg-purple-100 hover:bg-purple-200 text-purple-700 font-medium rounded-lg transition-colors duration-200 flex items-center justify-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <span>Download QR</span>
                            </button>
                            <button onclick="printQR()"
                                class="w-full px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors duration-200 flex items-center justify-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                                </svg>
                                <span>Print QR</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>



        <!-- Quick Tips Card -->
        <div class="mt-8 bg-gradient-to-r from-amber-50 to-orange-50 rounded-2xl border border-amber-200 p-6">
            <div class="flex items-start space-x-4">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                    </div>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-amber-800 mb-2">💡 Quick Tips</h3>
                    <ul class="space-y-2 text-sm text-amber-700">
                        <li class="flex items-start space-x-2">
                            <span class="text-amber-500 mt-0.5">•</span>
                            <span>Share your QR code with customers for easy access to your online store</span>
                        </li>
                        <li class="flex items-start space-x-2">
                            <span class="text-amber-500 mt-0.5">•</span>
                            <span>Keep your shopfront active to receive online orders</span>
                        </li>
                        <li class="flex items-start space-x-2">
                            <span class="text-amber-500 mt-0.5">•</span>
                            <span>Monitor your orders regularly through the Orders Dashboard</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Copy to Clipboard Toast -->
<div id="copyToast" class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg transform translate-x-full transition-transform duration-300 z-50">
    <div class="flex items-center space-x-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg>
        <span>URL copied to clipboard!</span>
    </div>
</div>

<script>
    // Copy to clipboard functionality
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(function() {
            showToast();
        }).catch(function(err) {
            console.error('Could not copy text: ', err);
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            showToast();
        });
    }

    // Show toast notification
    function showToast() {
        const toast = document.getElementById('copyToast');
        toast.classList.remove('translate-x-full');
        setTimeout(() => {
            toast.classList.add('translate-x-full');
        }, 3000);
    }

    // Download QR Code functionality
    function downloadQR() {
        const qrCodeElement = document.querySelector('#qr-code svg, #qr-code canvas, #qr-code img');
        if (!qrCodeElement) {
            console.error('QR code element not found');
            return;
        }

        // Create a canvas to draw the QR code
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');

        // Set canvas size
        canvas.width = 300;
        canvas.height = 300;

        // Fill white background
        ctx.fillStyle = 'white';
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        if (qrCodeElement.tagName === 'svg') {
            // Convert SVG to canvas
            const svgData = new XMLSerializer().serializeToString(qrCodeElement);
            const svgBlob = new Blob([svgData], {
                type: 'image/svg+xml;charset=utf-8'
            });
            const svgUrl = URL.createObjectURL(svgBlob);

            const img = new Image();
            img.onload = function() {
                ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                downloadCanvasAsImage(canvas, 'shopfront-qr-code.png');
                URL.revokeObjectURL(svgUrl);
            };
            img.src = svgUrl;
        } else if (qrCodeElement.tagName === 'IMG') {
            // Handle image element
            ctx.drawImage(qrCodeElement, 0, 0, canvas.width, canvas.height);
            downloadCanvasAsImage(canvas, 'shopfront-qr-code.png');
        }
    }

    // Helper function to download canvas as image
    function downloadCanvasAsImage(canvas, filename) {
        const link = document.createElement('a');
        link.download = filename;
        link.href = canvas.toDataURL('image/png');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // Print QR Code functionality
    function printQR() {
        const qrSection = document.querySelector('.bg-white.p-4.rounded-xl.shadow-sm');
        if (!qrSection) {
            console.error('QR code section not found');
            return;
        }

        const printWindow = window.open('', '_blank');

        // Fetch business name via AJAX
        fetch('/admin/get-business-name', {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                const businessName = data.business_name || 'Business';

                printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>QR Code - ${businessName}</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 0;
                    padding: 20px;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    min-height: 100vh;
                    background: white;
                }
                .qr-container {
                    text-align: center;
                    padding: 30px;
                    border: 2px solid #e5e7eb;
                    border-radius: 15px;
                    background: white;
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                }
                .business-name {
                    font-size: 24px;
                    font-weight: bold;
                    color: #1f2937;
                    margin-bottom: 10px;
                }
                .qr-title {
                    font-size: 18px;
                    color: #6b7280;
                    margin-bottom: 20px;
                }
                .qr-code {
                    margin: 20px 0;
                    display: inline-block;
                    padding: 15px;
                    background: #f9fafb;
                    border-radius: 10px;
                }
                .instructions {
                    font-size: 14px;
                    color: #6b7280;
                    margin-top: 15px;
                    max-width: 300px;
                }
                .footer {
                    margin-top: 30px;
                    font-size: 12px;
                    color: #9ca3af;
                }
                @media print {
                    body { margin: 0; }
                    .qr-container { 
                        box-shadow: none; 
                        border: 1px solid #000;
                    }
                }
            </style>
        </head>
        <body>
            <div class="qr-container">
                <div class="business-name">${businessName}</div>
                <div class="qr-title">Shopfront QR Code</div>
                <div class="qr-code">
                    ${qrSection.innerHTML}
                </div>
                <div class="instructions">
                    Scan this QR code with your smartphone camera to access our online store
                </div>
                <div class="footer">
                    Powered by ePATNER
                </div>
            </div>
        </body>
        </html>
    `);

                printWindow.document.close();
                printWindow.focus();

                // Wait for content to load then print
                setTimeout(() => {
                    printWindow.print();
                    printWindow.close();
                }, 500);
            })
            .catch(error => {
                console.error('Error fetching business name:', error);
                // Fallback to default name
                const businessName = 'Business';

                printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>QR Code - ${businessName}</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 0;
                    padding: 20px;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    min-height: 100vh;
                    background: white;
                }
                .qr-container {
                    text-align: center;
                    padding: 30px;
                    border: 2px solid #e5e7eb;
                    border-radius: 15px;
                    background: white;
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                }
                .business-name {
                    font-size: 24px;
                    font-weight: bold;
                    color: #1f2937;
                    margin-bottom: 10px;
                }
                .qr-title {
                    font-size: 18px;
                    color: #6b7280;
                    margin-bottom: 20px;
                }
                .qr-code {
                    margin: 20px 0;
                    display: inline-block;
                    padding: 15px;
                    background: #f9fafb;
                    border-radius: 10px;
                }
                .instructions {
                    font-size: 14px;
                    color: #6b7280;
                    margin-top: 15px;
                    max-width: 300px;
                }
                .footer {
                    margin-top: 30px;
                    font-size: 12px;
                    color: #9ca3af;
                }
                @media print {
                    body { margin: 0; }
                    .qr-container { 
                        box-shadow: none; 
                        border: 1px solid #000;
                    }
                }
            </style>
        </head>
        <body>
            <div class="qr-container">
                <div class="business-name">${businessName}</div>
                <div class="qr-title">Shopfront QR Code</div>
                <div class="qr-code">
                    ${qrSection.innerHTML}
                </div>
                <div class="instructions">
                    Scan this QR code with your smartphone camera to access our online store
                </div>
                <div class="footer">
                    Powered by ePATNER
                </div>
            </div>
        </body>
        </html>
    `);

                printWindow.document.close();
                printWindow.focus();

                setTimeout(() => {
                    printWindow.print();
                    printWindow.close();
                }, 500);
            });
    }


    // Add smooth scroll behavior for mobile
    document.addEventListener('DOMContentLoaded', function() {
        // Add loading states to buttons
        // Add loading states to buttons
        const buttons = document.querySelectorAll('button[type="submit"]');
        buttons.forEach(button => {
            button.addEventListener('click', function(e) {
                // Don't prevent the default form submission
                const originalText = this.innerHTML;

                // Add a small delay before disabling to allow form submission
                setTimeout(() => {
                    this.disabled = true;
                    this.innerHTML = `
                <svg class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Processing...
            `;
                }, 100);

                // Re-enable button after 10 seconds as fallback
                setTimeout(() => {
                    this.disabled = false;
                    this.innerHTML = originalText;
                }, 10000);
            });
        });


        // Add hover effects for cards
        const cards = document.querySelectorAll('.bg-white.rounded-2xl');
        cards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.transition = 'transform 0.2s ease-in-out';
            });

            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });

        // Add ripple effect to buttons
        const rippleButtons = document.querySelectorAll('button, .btn');
        rippleButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                const ripple = document.createElement('span');
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;

                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                ripple.classList.add('ripple');

                this.appendChild(ripple);

                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });
    });

    // Add intersection observer for animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Observe all cards for animation
    document.addEventListener('DOMContentLoaded', function() {
        const animatedElements = document.querySelectorAll('.bg-white.rounded-2xl, .bg-gradient-to-r');
        animatedElements.forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'opacity 0.6s ease-out, transform 0.6s ease-out';
            observer.observe(el);
        });
    });
</script>

<style>
    /* Custom styles for enhanced UI */
    .hero-gradient {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    /* Ripple effect */
    .ripple {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.3);
        transform: scale(0);
        animation: ripple-animation 0.6s linear;
        pointer-events: none;
    }

    @keyframes ripple-animation {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }

    /* Custom scrollbar for webkit browsers */
    ::-webkit-scrollbar {
        width: 8px;
    }

    ::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* Mobile optimizations */
    @media (max-width: 640px) {
        .container {
            padding-left: 1rem;
            padding-right: 1rem;
        }

        .text-2xl {
            font-size: 1.5rem;
        }

        .text-3xl {
            font-size: 1.875rem;
        }
    }

    /* Focus states for accessibility */
    button:focus,
    a:focus {
        outline: 2px solid #3b82f6;
        outline-offset: 2px;
    }

    /* Loading animation */
    @keyframes pulse {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0.5;
        }
    }

    .animate-pulse {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }

    /* Print styles */
    @media print {
        .print-controls {
            display: none !important;
        }

        body {
            background: white !important;
        }

        .shadow-xl,
        .shadow-lg {
            box-shadow: none !important;
        }
    }
</style>
@endsection