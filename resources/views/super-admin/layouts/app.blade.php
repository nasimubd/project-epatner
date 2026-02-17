<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Permissions-Policy" content="speculation-rules=(), interest-cohort=(), browsing-topics=()">
    <meta name="theme-color" content="#1f2937">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">

    <title>{{ config('app.name', 'Super Admin') }}</title>

    <!-- Preload critical resources -->
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link rel="dns-prefetch" href="https://fonts.bunny.net">

    <!-- Optimized font loading -->
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript>
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">
    </noscript>

    <!-- Critical CSS inline for faster loading -->
    <style>
        /* Critical above-the-fold styles */
        body {
            font-family: system-ui, -apple-system, sans-serif;
            margin: 0;
        }

        .loading {
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .loaded {
            opacity: 1;
        }

        /* Mobile-first responsive utilities */
        .mobile-hidden {
            display: none;
        }

        @media (min-width: 768px) {
            .mobile-hidden {
                display: block;
            }

            .desktop-hidden {
                display: none;
            }
        }

        /* Touch-friendly sizing */
        .touch-target {
            min-height: 44px;
            min-width: 44px;
        }

        /* Smooth scrolling for mobile */
        .smooth-scroll {
            -webkit-overflow-scrolling: touch;
        }
    </style>

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased bg-gray-50 loading" x-data="{ 
    mobileMenuOpen: false, 
    sidebarCollapsed: false,
    mounted() { 
        this.$el.classList.remove('loading'); 
        this.$el.classList.add('loaded'); 
    }
}" x-init="mounted()">

    <!-- Mobile Menu Overlay -->
    <div x-show="mobileMenuOpen"
        x-transition:enter="transition-opacity ease-linear duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-300"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 md:hidden"
        @click="mobileMenuOpen = false">
        <div class="fixed inset-0 bg-gray-600 bg-opacity-75"></div>
    </div>

    <!-- Mobile Sidebar -->
    <div x-show="mobileMenuOpen"
        x-transition:enter="transition ease-in-out duration-300 transform"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in-out duration-300 transform"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
        class="fixed inset-y-0 left-0 z-50 w-80 bg-white shadow-xl md:hidden smooth-scroll"
        @click.stop>
        @role('super-admin')
        <!-- Mobile Sidebar Header -->
        <div class="flex items-center justify-between h-16 px-4 bg-gray-900">
            <h1 class="text-xl font-bold text-white">Super Admin</h1>
            <button @click="mobileMenuOpen = false"
                class="p-2 text-gray-300 hover:text-white touch-target">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        @endrole
        <!-- Mobile Navigation -->
        <nav class="flex-1 px-4 py-6 overflow-y-auto smooth-scroll" x-data="{ 
            businessOpen: {{ request()->routeIs('super-admin.businesses.*') || request()->routeIs('super-admin.admins.*') ? 'true' : 'false' }}, 
            databaseOpen: {{ request()->routeIs('super-admin.common-products.*') || request()->routeIs('super-admin.default-ledgers.*') || request()->routeIs('super-admin.customer-ledgers.*') || request()->routeIs('super-admin.common-categories.*') || request()->routeIs('super-admin.common-units.*') || request()->routeIs('super-admin.location-data.*') ? 'true' : 'false' }},
            accessOpen: {{ request()->routeIs('roles.*') || request()->routeIs('permissions.*') ? 'true' : 'false' }},
            staffOpen: {{ request()->routeIs('super-admin.staff.*') ? 'true' : 'false' }}
        }">
            @include('super-admin.layouts.partials.navigation')
        </nav>

        <!-- Mobile User Profile -->
        <div class="p-4 border-t bg-gray-50">
            <div class="flex items-center space-x-3">
                <img src="https://ui-avatars.com/api/?name=Admin&size=40"
                    alt="Profile"
                    class="w-10 h-10 rounded-full"
                    loading="lazy">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate">Admin User</p>
                    <p class="text-xs text-gray-500 truncate">admin@example.com</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Desktop Sidebar -->
    <aside class="mobile-hidden fixed inset-y-0 left-0 z-40 bg-white shadow-lg transition-all duration-300"
        :class="sidebarCollapsed ? 'w-16' : 'w-64'">
        <div class="flex flex-col h-full">
            @role('super-admin')
            <!-- Desktop Logo -->
            <div class="h-16 flex items-center justify-between px-4 bg-gray-900">
                <h1 class="text-xl font-bold text-white transition-opacity duration-300"
                    :class="sidebarCollapsed ? 'opacity-0' : 'opacity-100'"
                    x-show="!sidebarCollapsed">Super Admin</h1>
                <h1 class="text-xl font-bold text-white"
                    x-show="sidebarCollapsed">SA</h1>
                <button @click="sidebarCollapsed = !sidebarCollapsed"
                    class="p-1 text-gray-300 hover:text-white touch-target">
                    <svg class="w-5 h-5 transition-transform duration-300"
                        :class="sidebarCollapsed ? 'rotate-180' : ''"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path>
                    </svg>
                </button>
            </div>
            @endrole

            <!-- Desktop Navigation -->
            <nav class="flex-1 overflow-y-auto py-4 smooth-scroll" x-data="{ 
                businessOpen: {{ request()->routeIs('super-admin.businesses.*') || request()->routeIs('super-admin.admins.*') ? 'true' : 'false' }}, 
                databaseOpen: {{ request()->routeIs('super-admin.common-products.*') || request()->routeIs('super-admin.default-ledgers.*') || request()->routeIs('super-admin.customer-ledgers.*') || request()->routeIs('super-admin.common-categories.*') || request()->routeIs('super-admin.common-units.*') || request()->routeIs('super-admin.location-data.*') ? 'true' : 'false' }},
                accessOpen: {{ request()->routeIs('roles.*') || request()->routeIs('permissions.*') ? 'true' : 'false' }},
                staffOpen: {{ request()->routeIs('super-admin.staff.*') ? 'true' : 'false' }}
            }">
                @include('super-admin.layouts.partials.navigation')
            </nav>

            <!-- Desktop User Profile -->
            <div class="p-4 border-t bg-gray-50" x-show="!sidebarCollapsed">
                <div class="flex items-center space-x-3">
                    <img src="https://ui-avatars.com/api/?name=Admin&size=40"
                        alt="Profile"
                        class="w-10 h-10 rounded-full"
                        loading="lazy">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">Admin User</p>
                        <p class="text-xs text-gray-500 truncate">admin@example.com</p>
                    </div>
                </div>
            </div>
        </div>
    </aside>
    @role('super-admin')
    <!-- Mobile Header -->
    <header class="desktop-hidden bg-white shadow-sm border-b sticky top-0 z-30">
        <div class="flex items-center justify-between h-16 px-4">
            <button @click="mobileMenuOpen = true"
                class="p-2 text-gray-600 hover:text-gray-900 touch-target">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
            <h1 class="text-lg font-semibold text-gray-900 truncate">Super Admin</h1>
            <div class="w-10 h-10 flex items-center justify-center">
                <img src="https://ui-avatars.com/api/?name=Admin&size=32"
                    alt="Profile"
                    class="w-8 h-8 rounded-full"
                    loading="lazy">
            </div>
        </div>
    </header>
    @endrole
    <!-- Main Content -->
    <main class="transition-all duration-300 min-h-screen"
        :class="sidebarCollapsed ? 'md:ml-16' : 'md:ml-64'">
        <div class="p-4 md:p-6">
            <!-- Breadcrumb for mobile -->
            <div class="desktop-hidden mb-4">
                <nav class="flex" aria-label="Breadcrumb">
                    <ol class="flex items-center space-x-2 text-sm">
                        <li>
                            <a href="{{ route('super-admin.dashboard') }}" class="text-gray-500 hover:text-gray-700">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                                </svg>
                            </a>
                        </li>
                        @hasSection('breadcrumb')
                        @yield('breadcrumb')
                        @endif
                    </ol>
                </nav>
            </div>

            @yield('content')
        </div>
    </main>

    <!-- Loading indicator -->
    <div x-show="false"
        x-transition
        class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50"
        id="loading-indicator">
        <div class="bg-white rounded-lg p-6 flex items-center space-x-3">
            <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-gray-700">Loading...</span>
        </div>
    </div>

    <!-- Performance optimized scripts -->
    <script>
        // Optimized mobile menu and performance utilities
        document.addEventListener('DOMContentLoaded', function() {
            // Preload critical routes
            const criticalRoutes = [
                '{{ route("super-admin.dashboard") }}',
                '{{ route("super-admin.businesses.index") }}',
                '{{ route("super-admin.admins.index") }}'
            ];

            // Preload on idle
            if ('requestIdleCallback' in window) {
                requestIdleCallback(() => {
                    criticalRoutes.forEach(route => {
                        const link = document.createElement('link');
                        link.rel = 'prefetch';
                        link.href = route;
                        document.head.appendChild(link);
                    });
                });
            }

            // Touch gesture support for mobile
            let touchStartX = 0;
            let touchEndX = 0;

            document.addEventListener('touchstart', function(e) {
                touchStartX = e.changedTouches[0].screenX;
            }, {
                passive: true
            });

            document.addEventListener('touchend', function(e) {
                touchEndX = e.changedTouches[0].screenX;
                handleSwipeGesture();
            }, {
                passive: true
            });

            function handleSwipeGesture() {
                const swipeThreshold = 100;
                const swipeDistance = touchEndX - touchStartX;

                // Swipe right to open menu (only on mobile)
                if (swipeDistance > swipeThreshold && touchStartX < 50 && window.innerWidth < 768) {
                    Alpine.store('mobileMenu', true);
                }

                // Swipe left to close menu
                if (swipeDistance < -swipeThreshold && window.innerWidth < 768) {
                    Alpine.store('mobileMenu', false);
                }
            }

            // Optimize images loading
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.src = img.dataset.src;
                            img.classList.remove('lazy');
                            imageObserver.unobserve(img);
                        }
                    });
                });

                document.querySelectorAll('img[data-src]').forEach(img => {
                    imageObserver.observe(img);
                });
            }

            // Service Worker registration for caching (optional)
            if ('serviceWorker' in navigator && '{{ app()->environment("production") }}') {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('/sw.js')
                        .then(registration => console.log('SW registered'))
                        .catch(error => console.log('SW registration failed'));
                });
            }

            // Keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                // Alt + M to toggle mobile menu
                if (e.altKey && e.key === 'm') {
                    e.preventDefault();
                    if (window.innerWidth < 768) {
                        Alpine.store('mobileMenu', !Alpine.store('mobileMenu'));
                    }
                }

                // Alt + S to toggle sidebar collapse
                if (e.altKey && e.key === 's') {
                    e.preventDefault();
                    if (window.innerWidth >= 768) {
                        Alpine.store('sidebarCollapsed', !Alpine.store('sidebarCollapsed'));
                    }
                }
            });

            // Auto-hide mobile menu on route change
            let currentPath = window.location.pathname;
            const observer = new MutationObserver(() => {
                if (window.location.pathname !== currentPath) {
                    currentPath = window.location.pathname;
                    if (window.innerWidth < 768) {
                        Alpine.store('mobileMenu', false);
                    }
                }
            });
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });

            // Performance monitoring
            if ('PerformanceObserver' in window) {
                const perfObserver = new PerformanceObserver((list) => {
                    for (const entry of list.getEntries()) {
                        if (entry.entryType === 'navigation') {
                            console.log('Page load time:', entry.loadEventEnd - entry.loadEventStart);
                        }
                    }
                });
                perfObserver.observe({
                    entryTypes: ['navigation']
                });
            }
        });

        // Global loading state management
        window.showLoading = function() {
            document.getElementById('loading-indicator').style.display = 'flex';
        };

        window.hideLoading = function() {
            document.getElementById('loading-indicator').style.display = 'none';
        };

        // Auto-hide loading on page load
        window.addEventListener('load', () => {
            setTimeout(hideLoading, 500);
        });
    </script>

    <!-- Alpine.js stores for global state -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('mobileMenu', false);
            Alpine.store('sidebarCollapsed', localStorage.getItem('sidebarCollapsed') === 'true');

            // Persist sidebar state
            Alpine.effect(() => {
                localStorage.setItem('sidebarCollapsed', Alpine.store('sidebarCollapsed'));
            });
        });
    </script>
</body>

</html>