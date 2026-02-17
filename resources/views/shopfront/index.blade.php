<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $shopfront->business->name ?? 'Online Shop' }} - Your Digital Store</title>
    <meta name="description" content="Shop online at {{ $shopfront->business->name ?? 'our business' }} - Quality products, fast delivery, great prices">

    <!-- Preload critical resources -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">

    <!-- Critical CSS inline -->
    <style>
        /* Critical above-the-fold styles */
        body {
            margin: 0;
            font-family: system-ui, -apple-system, sans-serif;
        }

        .hero-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .glass-effect {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }

        .loading {
            opacity: 0.5;
            pointer-events: none;
        }

        .fade-in {
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% {
                background-position: 200% 0;
            }

            100% {
                background-position: -200% 0;
            }
        }

        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            border-radius: 3px;
        }

        /* Focus styles for accessibility */
        button:focus,
        input:focus,
        select:focus {
            outline: 2px solid #3b82f6;
            outline-offset: 2px;
        }

        /* Loading spinner animation */
        .spinner {
            border: 4px solid #f3f4f6;
            border-top: 4px solid #3b82f6;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>

    <!-- Load Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-gray-50 min-h-screen" x-data="shopfrontApp()" x-init="init()">
    <!-- Main Loading Overlay (Initial Page Load) -->
    <div x-show="isLoading"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-white z-50 flex items-center justify-center">
        <div class="text-center">
            <div class="spinner mx-auto mb-4"></div>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Loading Store</h3>
            <p class="text-gray-600">Please wait while AI is preparing your shopping experience...</p>
        </div>
    </div>

    <!-- Category Loading Overlay (When switching categories) -->
    <div x-show="categoryLoading"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black bg-opacity-50 z-40 flex items-center justify-center">
        <div class="bg-white p-6 rounded-xl shadow-2xl flex flex-col items-center">
            <div class="spinner mb-3"></div>
            <p class="text-gray-700 font-medium">Loading products...</p>
        </div>
    </div>

    <!-- Header -->
    <header class="fixed top-0 left-0 right-0 z-30 bg-white border-b border-gray-200 shadow-lg">
        <div class="px-4 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo/Brand -->
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold text-lg">{{ substr($shopfront->business->name, 0, 1) }}</span>
                    </div>
                    <div>
                        <h1 class="text-lg font-bold text-gray-800">{{ $shopfront->business->name }}</h1>
                        <p class="text-xs text-gray-600 hidden sm:block">Your Trusted Store</p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center space-x-2">
                    <!-- Search Toggle -->
                    <button @click="showSearch = !showSearch" class="p-2 bg-gray-50 hover:bg-gray-100 rounded-lg shadow-sm transition-colors">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>

                    <!-- Cart -->
                    <a href="{{ route('shopfront.cart', ['id' => $shopfront->shopfront_id]) }}" class="relative p-2 bg-gray-50 hover:bg-gray-100 rounded-lg shadow-sm transition-colors">
                        <svg class="w-6 h-6 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span x-show="getCartCount() > 0" x-text="getCartCount()" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center"></span>
                    </a>
                </div>
            </div>

            <!-- Search Bar -->
            <div x-show="showSearch" x-transition class="pb-4">
                <input x-model="searchQuery" type="text" placeholder="Search products..." class="w-full px-4 py-2 rounded-lg border border-gray-300 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>
    </header>


    <!-- Hero Section (Only show on home) -->
    <section x-show="!showProducts && !isLoading" class="relative w-full overflow-hidden mt-16">
        <div class="h-48 md:h-96">
            @if($heroBanner && $heroBanner->image)
            <img src="data:image/jpeg;base64,{{ base64_encode($heroBanner->image) }}" alt="Hero Banner" class="w-full h-full object-cover" loading="eager">
            @else
            <div class="w-full h-full hero-gradient"></div>
            @endif
        </div>
    </section>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 py-8" :class="showProducts ? 'mt-16' : ''" x-show="!isLoading">
        <!-- Categories Grid (Home View) -->
        <div x-show="!showProducts" class="fade-in">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($generalCategories as $key => $category)
                @php
                // First try to get general category image by category name/key
                $categoryImage = isset($categoryImages[$key]) ? $categoryImages[$key] : null;

                // If no general category image, try to get specific category image from first subcategory
                if (!$categoryImage) {
                $firstSubcategoryId = !empty($category['subcategories']) ? $category['subcategories'][0]['id'] : null;
                $categoryImage = $firstSubcategoryId && isset($categoryImages[$firstSubcategoryId]) ? $categoryImages[$firstSubcategoryId] : null;
                }
                @endphp

                <div @click="setActiveGeneralCategory('{{ $key }}')" class="group bg-white rounded-xl shadow-lg hover:shadow-xl cursor-pointer transform hover:-translate-y-1 transition-all duration-300 overflow-hidden">
                    <!-- Category Image/Icon -->
                    <div class="relative h-48 overflow-hidden">
                        @if($categoryImage && $categoryImage->image)
                        <!-- Custom Category Image -->
                        <img src="data:image/jpeg;base64,{{ base64_encode($categoryImage->image) }}"
                            alt="{{ $category['name'] }}"
                            class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                            loading="lazy">
                        <!-- Overlay for better text readability -->
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                        @else
                        <!-- Default Gradient Background with Icon -->
                        <div class="w-full h-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center group-hover:from-blue-600 group-hover:to-purple-700 transition-all duration-300">
                            <svg class="w-16 h-16 text-white group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>

                            <!-- Debug indicator -->
                            <div class="absolute top-2 left-2 bg-red-500 text-white text-xs px-2 py-1 rounded">
                                NO IMG
                            </div>
                        </div>
                        @endif

                        <!-- Product Count Badge -->
                        <div class="absolute top-4 right-4 bg-white/90 backdrop-blur-sm text-gray-800 px-3 py-1 rounded-full text-sm font-semibold shadow-lg">
                            {{ count($category['products']) }}
                        </div>

                        <!-- Category Name Overlay (for image backgrounds) -->
                        @if($categoryImage && $categoryImage->image)
                        <div class="absolute bottom-4 left-4 right-4">
                            <h3 class="text-xl font-bold text-white mb-2 drop-shadow-lg">
                                {{ $category['name'] }}
                            </h3>
                        </div>
                        @endif
                    </div>

                    <!-- Category Details -->
                    <div class="p-6">
                        @if(!($categoryImage && $categoryImage->image))
                        <!-- Category Name (for non-image backgrounds) -->
                        <h3 class="text-xl font-bold text-gray-800 mb-3 group-hover:text-blue-600 transition-colors">
                            {{ $category['name'] }}
                        </h3>
                        @endif

                        <div class="space-y-2 mb-4">
                            <div class="flex items-center text-gray-600">
                                <svg class="w-4 h-4 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                                <span class="text-sm">{{ count($category['subcategories']) }} Categories</span>
                            </div>
                            <div class="flex items-center text-gray-600">
                                <svg class="w-4 h-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                                <span class="text-sm">{{ count($category['products']) }} Products</span>
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-blue-600 font-semibold group-hover:text-blue-700 transition-colors">Explore Now</span>
                            <svg class="w-5 h-5 text-blue-600 group-hover:translate-x-1 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

        </div>

        <!-- Products View (Clean - Only Products and Filters) -->
        <div x-show="showProducts && !categoryLoading" class="fade-in">
            <!-- Back Button & Controls -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 bg-white rounded-xl p-4 shadow-lg">
                <div class="flex items-center space-x-4 mb-4 sm:mb-0">
                    <button @click="goBack()" class="flex items-center space-x-2 text-blue-600 hover:text-blue-700 font-medium">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        <span>Back to Categories</span>
                    </button>
                    <div class="h-6 w-px bg-gray-300"></div>
                    <h2 class="text-xl font-bold text-gray-800" x-text="activeCategory"></h2>
                </div>

                <!-- Filters -->
                <div class="flex flex-col sm:flex-row gap-3">
                    <select x-model="sortOrder" class="text-sm rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                        <option value="name">Sort by Name</option>
                        <option value="price_low">Price: Low to High</option>
                        <option value="price_high">Price: High to Low</option>
                    </select>
                </div>
            </div>

            <!-- Products Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 sm:gap-6">
                <template x-for="product in getFilteredProducts()" :key="product.id">
                    <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 overflow-hidden group">
                        <!-- Product Image -->
                        <div class="relative w-full h-48 bg-gray-100 overflow-hidden">
                            <img :src="product.image ? 'data:image/jpeg;base64,' + product.image : '{{ asset('images/default-product.jpeg') }}'"
                                :alt="product.name"
                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
                                loading="lazy">

                            <!-- Stock Badge -->
                            <div class="absolute top-3 right-3 bg-blue-600 text-white text-xs font-bold px-2 py-1 rounded-full">
                                <span x-text="product.current_stock || 0"></span>
                            </div>

                            <!-- Quick Add Button -->
                            <button @click="addToCart(product)"
                                :disabled="product.current_stock <= 0"
                                :class="{'bg-gray-300 cursor-not-allowed': product.current_stock <= 0, 'bg-blue-600 hover:bg-blue-700': product.current_stock > 0}"
                                class="absolute top-12 right-3 w-8 h-8 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-300 shadow-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                            </button>

                            <!-- Out of Stock Overlay -->
                            <div x-show="product.current_stock <= 0" class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center">
                                <span class="bg-red-500 text-white px-3 py-1 rounded-full text-sm font-bold">Out of Stock</span>
                            </div>
                        </div>

                        <!-- Product Details -->
                        <div class="p-4">
                            <!-- Product Name -->
                            <h3 class="text-sm font-semibold text-gray-800 mb-2 line-clamp-2" x-text="product.name" :title="product.name"></h3>

                            <!-- Batch Selection -->
                            <div class="mb-3" x-show="product.batches && product.batches.length > 0">
                                <select class="w-full text-xs border-gray-300 rounded-lg focus:border-blue-500 focus:ring-blue-500"
                                    @change="updateSelectedBatch(product.id, $event.target.value)">
                                    <template x-for="(batch, index) in product.batches" :key="batch.id">
                                        <option :value="batch.id" :selected="index === 0"
                                            x-text="`Batch: ${batch.batch_number} (${batch.remaining_quantity}) - ৳${parseFloat(batch.trade_price).toFixed(2)}`">
                                        </option>
                                    </template>
                                </select>
                            </div>

                            <!-- Price and Add Button -->
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-lg font-bold text-gray-900">
                                        ৳<span x-text="getProductPrice(product).toFixed(2)"></span>
                                    </p>
                                </div>
                                <button @click="addToCart(product)"
                                    :disabled="product.current_stock <= 0"
                                    :class="{'bg-gray-300 cursor-not-allowed': product.current_stock <= 0, 'bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700': product.current_stock > 0}"
                                    class="text-white px-3 py-2 rounded-lg font-medium transition-all duration-200 transform hover:scale-105 shadow-lg">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Empty State -->
            <div x-show="getFilteredProducts().length === 0" class="text-center py-16 bg-white rounded-xl shadow-lg">
                <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="h-10 w-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">No products found</h3>
                <p class="text-gray-500 mb-4">Try adjusting your search or filters</p>
                <button @click="clearFilters()" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    Clear Filters
                </button>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white mt-20" x-show="!isLoading">
        <div class="max-w-7xl mx-auto px-4 py-12">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Company Info -->
                <div>
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold text-lg">{{ substr($shopfront->business->name, 0, 1) }}</span>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold">{{ $shopfront->business->name }}</h3>
                            <p class="text-gray-400 text-sm">Your Trusted Store</p>
                        </div>
                    </div>
                    <p class="text-gray-300 text-sm">Quality products at competitive prices with excellent customer service.</p>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="font-semibold mb-4">Quick Links</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="text-gray-300 hover:text-white transition-colors">About Us</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-white transition-colors">Contact</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-white transition-colors">Privacy Policy</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-white transition-colors">Terms of Service</a></li>
                    </ul>
                </div>

                <!-- Contact -->
                <div>
                    <h4 class="font-semibold mb-4">Contact Us</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center space-x-2">
                            <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            <span class="text-gray-300">+8801712-113080</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <span class="text-gray-300">support@epatner.com</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="text-gray-300">24/7 Support</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Footer -->
            <div class="border-t border-gray-800 mt-8 pt-6">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <p class="text-gray-400 text-sm mb-4 md:mb-0">
                        © {{ date('Y') }} {{ $shopfront->business->name }}. All rights reserved.
                    </p>
                    <div class="flex items-center space-x-2 text-sm text-gray-400">
                        <span>Powered by</span>
                        <span class="font-semibold text-blue-400">ePATNER</span>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Floating Action Buttons -->
    <div class="fixed bottom-6 right-6 z-40 flex flex-col space-y-3 lg:hidden" x-show="!isLoading">
        <!-- Cart Button -->
        <a href="{{ route('shopfront.cart', ['id' => $shopfront->shopfront_id]) }}"
            class="relative bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-full shadow-2xl w-14 h-14 flex items-center justify-center transition-all duration-300 hover:scale-110">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <span x-show="getCartCount() > 0" x-text="getCartCount()"
                class="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center"></span>
        </a>

        <!-- Navigation Button -->
        <button @click="showNavigation = true"
            class="bg-indigo-500 hover:bg-indigo-600 text-white rounded-full shadow-2xl w-14 h-14 flex items-center justify-center transition-all duration-300 hover:scale-110">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>

        <!-- WhatsApp Button -->
        <a href="https://wa.me/8801712113080?text=Hello! I'm interested in your products from {{ urlencode($shopfront->business->name) }}"
            target="_blank"
            class="bg-green-500 hover:bg-green-600 text-white rounded-full shadow-2xl w-14 h-14 flex items-center justify-center transition-all duration-300 hover:scale-110">
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.488" />
            </svg>
        </a>
    </div>

    <!-- Navigation Panel -->
    <div x-show="showNavigation"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black bg-opacity-50 z-50"
        @click="showNavigation = false">

        <div x-show="showNavigation"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            class="absolute right-0 top-0 h-full w-80 max-w-[85vw] bg-white shadow-2xl"
            @click.stop>

            <!-- Navigation Header -->
            <div class="flex items-center justify-between p-6 border-b bg-gradient-to-r from-indigo-500 to-purple-600 text-white">
                <h2 class="text-xl font-bold">Categories</h2>
                <button @click="showNavigation = false" class="text-white hover:text-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Navigation Content -->
            <div class="flex-1 overflow-y-auto p-6">
                <div class="space-y-3">
                    @foreach($generalCategories as $key => $category)
                    <button @click="navigateToCategory('{{ $key }}')"
                        class="w-full flex items-center justify-between p-4 rounded-lg bg-gray-50 hover:bg-blue-50 hover:text-blue-600 transition-colors text-left">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium">{{ $category['name'] }}</p>
                                <p class="text-sm text-gray-500">{{ count($category['products']) }} products</p>
                            </div>
                        </div>
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                    @endforeach
                </div>
            </div>

            <!-- Navigation Footer -->
            <div class="p-6 border-t bg-gray-50">
                <button @click="goHome()"
                    class="w-full text-blue-600 hover:text-blue-800 flex items-center justify-center space-x-2 py-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span class="font-medium">Back to Home</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Load Alpine.js -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <script>
        function shopfrontApp() {
            return {
                // State
                isLoading: true,
                categoryLoading: false,
                showSearch: false,
                showProducts: false,
                showNavigation: false,
                activeCategory: null,
                searchQuery: '',
                sortOrder: 'name',
                showOnlyInStock: false,
                cartItems: [],
                selectedBatches: {},
                currentCategoryProducts: [],
                categories: @json($generalCategories),

                // Initialize
                init() {
                    // Simulate initial loading
                    setTimeout(() => {
                        this.cartItems = JSON.parse(localStorage.getItem('shopfrontCart_{{ $shopfront->shopfront_id }}') || '[]');
                        this.initializeDefaultBatches();
                        this.isLoading = false;
                    }, 1500);
                },

                // Initialize default batches for products
                initializeDefaultBatches() {
                    Object.keys(this.categories).forEach(key => {
                        if (this.categories[key].products) {
                            this.categories[key].products.forEach(product => {
                                if (product.batches && product.batches.length > 0) {
                                    this.selectedBatches[product.id] = product.batches[0];
                                }
                            });
                        }
                    });
                },

                // Set active general category - THIS WAS MISSING
                setActiveGeneralCategory(categoryKey) {
                    this.categoryLoading = true;

                    // Simulate loading delay
                    setTimeout(() => {
                        this.activeCategory = this.categories[categoryKey].name;
                        this.currentCategoryProducts = this.categories[categoryKey].products;
                        this.showProducts = true;
                        this.categoryLoading = false;
                        window.scrollTo({
                            top: 0,
                            behavior: 'smooth'
                        });
                    }, 800);
                },

                // Alternative method name for compatibility
                setActiveCategory(categoryKey) {
                    this.setActiveGeneralCategory(categoryKey);
                },

                // Navigation methods
                navigateToCategory(categoryKey) {
                    this.setActiveGeneralCategory(categoryKey);
                    this.showNavigation = false;
                },

                goBack() {
                    this.showProducts = false;
                    this.activeCategory = null;
                    this.searchQuery = '';
                    this.currentCategoryProducts = [];
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                },

                goHome() {
                    this.goBack();
                    this.showNavigation = false;
                },

                goToHome() {
                    this.goHome();
                },

                toggleNavigation() {
                    this.showNavigation = !this.showNavigation;
                },

                openSearch() {
                    this.showSearch = true;
                    this.showNavigation = false;
                    setTimeout(() => {
                        const searchInput = document.querySelector('input[x-model="searchQuery"]');
                        if (searchInput) searchInput.focus();
                    }, 100);
                },

                // Product filtering
                getFilteredProducts() {
                    let products = [];

                    if (this.searchQuery) {
                        // Search across all products
                        Object.keys(this.categories).forEach(key => {
                            products = [...products, ...this.categories[key].products];
                        });
                        products = products.filter(product =>
                            product.name.toLowerCase().includes(this.searchQuery.toLowerCase())
                        );
                    } else if (this.currentCategoryProducts.length > 0) {
                        products = this.currentCategoryProducts;
                    }

                    // Filter by stock
                    if (this.showOnlyInStock) {
                        products = products.filter(product => product.current_stock > 0);
                    }

                    // Sort products
                    return this.sortProducts(products);
                },

                sortProducts(products) {
                    return products.sort((a, b) => {
                        switch (this.sortOrder) {
                            case 'name':
                                return a.name.localeCompare(b.name);
                            case 'price_low':
                                return this.getProductPrice(a) - this.getProductPrice(b);
                            case 'price_high':
                                return this.getProductPrice(b) - this.getProductPrice(a);
                            default:
                                return 0;
                        }
                    });
                },

                // Product methods
                getProductPrice(product) {
                    if (product.batches && product.batches.length > 0) {
                        const selectedBatch = this.selectedBatches[product.id] || product.batches[0];
                        return parseFloat(selectedBatch.trade_price) || 0;
                    }
                    return parseFloat(product.trade_price) || 0;
                },

                updateSelectedBatch(productId, batchId) {
                    const product = this.findProductById(productId);
                    if (product && product.batches) {
                        const batch = product.batches.find(b => b.id == batchId);
                        if (batch) {
                            this.selectedBatches[productId] = batch;
                        }
                    }
                },

                findProductById(productId) {
                    for (const categoryKey of Object.keys(this.categories)) {
                        const product = this.categories[categoryKey].products.find(p => p.id == productId);
                        if (product) return product;
                    }
                    return null;
                },

                // Cart methods
                addToCart(product) {
                    if (product.current_stock <= 0) {
                        this.showNotification('Product is out of stock', 'error');
                        return;
                    }

                    const selectedBatch = this.selectedBatches[product.id];
                    const batchId = selectedBatch ? selectedBatch.id : 'default';

                    const existingItem = this.cartItems.find(item =>
                        item.id === product.id && item.batch_id === batchId
                    );

                    if (existingItem) {
                        if (existingItem.quantity < product.current_stock) {
                            existingItem.quantity += 1;
                            this.showNotification(`${product.name} quantity updated in cart`, 'success');
                        } else {
                            this.showNotification('Cannot add more items than available stock', 'warning');
                            return;
                        }
                    } else {
                        this.cartItems.push({
                            id: product.id,
                            name: product.name,
                            price: this.getProductPrice(product),
                            quantity: 1,
                            batch_id: batchId,
                            batch_number: selectedBatch ? selectedBatch.batch_number : 'Default',
                            max_stock: product.current_stock
                        });
                        this.showNotification(`${product.name} added to cart`, 'success');
                    }

                    this.updateLocalStorage();
                },

                getCartCount() {
                    return this.cartItems.reduce((count, item) => count + item.quantity, 0);
                },

                getCartTotal() {
                    return this.cartItems.reduce((total, item) => total + (item.price * item.quantity), 0);
                },

                updateLocalStorage() {
                    localStorage.setItem('shopfrontCart_{{ $shopfront->shopfront_id }}', JSON.stringify(this.cartItems));
                },

                // Utility methods
                clearFilters() {
                    this.searchQuery = '';
                    this.showOnlyInStock = false;
                    this.sortOrder = 'name';
                },

                showNotification(message, type = 'info') {
                    const notification = document.createElement('div');
                    const bgColor = {
                        success: 'bg-green-500',
                        error: 'bg-red-500',
                        warning: 'bg-yellow-500',
                        info: 'bg-blue-500'
                    } [type] || 'bg-blue-500';

                    notification.className = `fixed top-20 right-4 ${bgColor} text-white px-6 py-3 rounded-lg shadow-2xl z-50 transform translate-x-full transition-transform duration-300`;
                    notification.innerHTML = `
                <div class="flex items-center space-x-2">
                    <span class="font-medium">${message}</span>
                </div>
            `;

                    document.body.appendChild(notification);

                    // Slide in
                    setTimeout(() => {
                        notification.style.transform = 'translateX(0)';
                    }, 100);

                    // Slide out and remove
                    setTimeout(() => {
                        notification.style.transform = 'translateX(100%)';
                        setTimeout(() => notification.remove(), 300);
                    }, 3000);
                }
            };
        }
    </script>


    <!-- Additional Styles -->
    <style>
        /* Offline indicator */
        .offline {
            filter: grayscale(0.5);
        }

        .offline::before {
            content: 'Offline Mode';
            position: fixed;
            top: 70px;
            left: 50%;
            transform: translateX(-50%);
            background: #ef4444;
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            z-index: 1000;
        }

        /* Smooth transitions */
        * {
            transition-property: color, background-color, border-color, text-decoration-color, fill, stroke, opacity, box-shadow, transform, filter, backdrop-filter;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            transition-duration: 150ms;
        }

        /* Loading states */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        /* Better focus styles */
        button:focus-visible,
        input:focus-visible,
        select:focus-visible {
            outline: 2px solid #3b82f6;
            outline-offset: 2px;
        }

        /* Improve text rendering */
        body {
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
        }

        /* Optimize for mobile */
        @media (max-width: 768px) {
            .hero-gradient {
                background-attachment: scroll;
            }
        }

        /* Reduce motion for accessibility */
        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* Print styles */
        @media print {
            .fixed {
                display: none !important;
            }
        }

        /* Enhanced loading spinner */
        .spinner {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        /* Notification animations */
        .notification-enter {
            transform: translateX(100%);
            opacity: 0;
        }

        .notification-enter-active {
            transform: translateX(0);
            opacity: 1;
            transition: all 0.3s ease-out;
        }

        .notification-exit {
            transform: translateX(0);
            opacity: 1;
        }

        .notification-exit-active {
            transform: translateX(100%);
            opacity: 0;
            transition: all 0.3s ease-in;
        }

        /* Mobile navigation improvements */
        @media (max-width: 1024px) {
            .floating-buttons {
                bottom: 1.5rem;
                right: 1.5rem;
            }
        }

        /* Skeleton loading for images */
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: skeleton-loading 1.5s infinite;
        }

        @keyframes skeleton-loading {
            0% {
                background-position: 200% 0;
            }

            100% {
                background-position: -200% 0;
            }
        }

        /* Improved hover effects */
        .hover-lift:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        /* Better button states */
        .btn-loading {
            position: relative;
            color: transparent;
        }

        .btn-loading::after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            top: 50%;
            left: 50%;
            margin-left: -8px;
            margin-top: -8px;
            border: 2px solid #ffffff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 1s linear infinite;
        }

        /* Accessibility improvements */
        @media (prefers-color-scheme: dark) {
            .glass-effect {
                background: rgba(0, 0, 0, 0.8);
            }
        }

        /* High contrast mode support */
        @media (prefers-contrast: high) {
            .shadow-lg {
                box-shadow: 0 0 0 2px currentColor;
            }
        }
    </style>
</body>

</html>