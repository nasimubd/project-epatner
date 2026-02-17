<!-- Admin Sidebar Navigation -->
<!-- Admin Sidebar Navigation -->
<div x-data="{ sidebarOpen: false }" class="relative">
    <!-- Mobile Toggle SVG -->
    <div @click="sidebarOpen = !sidebarOpen"
        class="lg:hidden fixed bottom-4 right-4 z-50 cursor-pointer transform transition-transform duration-300 hover:scale-105 active:scale-95">
        <div class="flex flex-col gap-1.5 bg-gradient-to-br from-gray-700 to-gray-900 p-3 rounded-lg shadow-lg border border-gray-700 relative overflow-hidden"
            style="transform-style: preserve-3d; transform: perspective(500px) rotateX(10deg);">
            <!-- Top bar -->
            <div class="w-8 h-1.5 bg-gray-200 rounded-full transform transition-all duration-300"></div>

            <!-- Middle bar - blue accent -->
            <div class="w-8 h-1.5 bg-blue-600 rounded-full transform transition-all duration-300"></div>

            <!-- Bottom bar -->
            <div class="w-8 h-1.5 bg-gray-200 rounded-full transform transition-all duration-300"></div>

            <!-- 3D effect elements -->
            <div class="absolute inset-0 bg-gradient-to-t from-black to-transparent opacity-30 rounded-lg"></div>
            <div class="absolute bottom-0 left-0 right-0 h-1/3 bg-black opacity-20 rounded-b-lg"></div>
            <div class="absolute top-0 left-0 w-full h-1/4 bg-white opacity-10 rounded-t-lg"></div>
        </div>
    </div>
    <!-- Sidebar -->
    <div :class="{'translate-x-0': sidebarOpen, 'translate-x-full': !sidebarOpen}"
        class="fixed top-0 right-0 z-40 h-screen w-20 transform transition-transform duration-300 ease-in-out lg:left-0 lg:translate-x-0">
        <!-- Sidebar Content -->
        <div class="h-screen bg-white border-r border-gray-200 shadow-lg overflow-y-auto scrollbar-thin flex flex-col">
            <style>
                .scrollbar-thin::-webkit-scrollbar {
                    width: 2px;
                }

                .scrollbar-thin::-webkit-scrollbar-track {
                    background: transparent;
                }

                .scrollbar-thin::-webkit-scrollbar-thumb {
                    background: rgba(156, 163, 175, 0.5);
                    border-radius: 1px;
                }

                .scrollbar-thin::-webkit-scrollbar-thumb:hover {
                    background: rgba(156, 163, 175, 0.8);
                }

                .scrollbar-thin {
                    scrollbar-width: thin;
                    scrollbar-color: rgba(156, 163, 175, 0.5) transparent;
                }

                .scrollbar-thin:not(:hover)::-webkit-scrollbar-thumb {
                    background: transparent;
                }

                .scrollbar-thin:not(:hover) {
                    scrollbar-color: transparent transparent;
                }

                .nav-item {
                    transform-style: preserve-3d;
                    transition: all 0.3s ease;
                }

                .nav-item:hover {
                    transform: translateY(-2px) scale(1.02);
                    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
                }

                .nav-item:active {
                    transform: translateY(1px) scale(0.98);
                }
            </style>

            <!-- Top Navigation Links -->
            <nav class="flex-1 py-4 space-y-2">

                <!-- Language Selector -->
                <div class="nav-item sidebar-translate-item flex flex-col items-center justify-center p-4 text-gray-700 hover:bg-gray-100 transition-all duration-200 rounded-lg">
                    <div class="w-12 h-12 flex items-center justify-center rounded-lg bg-gray-50 shadow-sm">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
                        </svg>
                    </div>
                    <div class="mt-2 w-full">
                        <div id="google_translate_element"></div>
                    </div>
                </div>

                <!-- Dashboard -->
                <a href="{{ route('admin.dashboard') }}"
                    class="nav-item flex flex-col items-center justify-center p-4 {{ request()->routeIs('admin.dashboard') ? 'text-blue-700 bg-blue-50' : 'text-gray-700' }} hover:bg-gray-100 transition-all duration-200 rounded-lg">
                    <div class="w-12 h-12 flex items-center justify-center rounded-lg bg-gray-50 shadow-sm">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                    </div>
                    <span class="mt-2 text-xs font-medium">CONSOLE</span>
                </a>


                <a href="{{ route('admin.inventory.inventory_transactions.index') }}"
                    class="nav-item flex flex-col items-center justify-center p-4 {{ request()->routeIs('admin.inventory.inventory_transactions.*') ? 'text-blue-700 bg-blue-50' : 'text-gray-700' }} hover:bg-gray-100 transition-all duration-200 rounded-lg">
                    <div class="w-12 h-12 flex items-center justify-center rounded-lg bg-gray-50 shadow-sm">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <span class="mt-2 text-xs font-medium">INVOICE</span>
                </a>

                @role('staff')
                <!-- Add New Customer -->
                <a href="{{ route('super-admin.customer-ledgers.create') }}"
                    class="nav-item flex flex-col items-center justify-center p-4 {{ request()->routeIs('super-admin.customer-ledgers.create') ? 'text-blue-700 bg-blue-50' : 'text-gray-700' }} hover:bg-gray-100 transition-all duration-200 rounded-lg">
                    <div class="w-12 h-12 flex items-center justify-center rounded-lg bg-gray-50 shadow-sm">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2 5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                    </div>
                    <span class="mt-2 text-xs font-medium">ADD CUSTOMER</span>
                </a>
                @endrole

                @role('admin')
                <a href="{{ route('admin.accounting.transactions.index') }}"
                    class="nav-item flex flex-col items-center justify-center p-4 {{ request()->routeIs('admin.accounting.transactions.*') ? 'text-blue-700 bg-blue-50' : 'text-gray-700' }} hover:bg-gray-100 transition-all duration-200 rounded-lg">
                    <div class="w-12 h-12 flex items-center justify-center rounded-lg bg-gray-50 shadow-sm">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <span class="mt-2 text-xs font-medium">VOUCHER</span>
                </a>

                <!-- Shopfront -->
                <a href="{{ route('admin.shopfront.index') }}"
                    class="nav-item flex flex-col items-center justify-center p-4 {{ request()->routeIs('admin.shopfront.*') ? 'text-blue-700 bg-blue-50' : 'text-gray-700' }} hover:bg-gray-100 transition-all duration-200 rounded-lg">
                    <div class="w-12 h-12 flex items-center justify-center rounded-lg bg-gray-50 shadow-sm">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </div>
                    <span class="mt-2 text-xs font-medium">eSTORE</span>
                </a>

                <a href="{{ route('admin.invoices.index') }}"
                    class="nav-item flex flex-col items-center justify-center p-4 {{ request()->routeIs('admin.invoices.*') ? 'text-blue-700 bg-blue-50' : 'text-gray-700' }} hover:bg-gray-100 transition-all duration-200 rounded-lg">
                    <div class="w-12 h-12 flex items-center justify-center rounded-lg bg-gray-50 shadow-sm">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <span class="mt-2 text-xs font-medium">PRINTING</span>
                </a>


                <a href="{{ route('admin.inventory.stock-summary') }}"
                    class="nav-item flex flex-col items-center justify-center p-4 {{ request()->routeIs('admin.inventory.stock-summary') ? 'text-blue-700 bg-blue-50' : 'text-gray-700' }} hover:bg-gray-100 transition-all duration-200 rounded-lg">
                    <div class="w-12 h-12 flex items-center justify-center rounded-lg bg-gray-50 shadow-sm">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                    <span class="mt-2 text-xs font-medium">STOCKS</span>
                </a>


                <!-- Reports -->
                <a href="{{ route('admin.accounting.reports.balance-sheet') }}"
                    class="nav-item flex flex-col items-center justify-center p-4 {{ request()->routeIs('admin.accounting.reports.balance-sheet') ? 'text-blue-700 bg-blue-50' : 'text-gray-700' }} hover:bg-gray-100 transition-all duration-200 rounded-lg">
                    <div class="w-12 h-12 flex items-center justify-center rounded-lg bg-gray-50 shadow-sm">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <span class="mt-2 text-xs font-medium">BALANCE</span>
                </a>

                <a href="{{ route('admin.shopfront.orders.category-report') }}"
                    class="nav-item flex flex-col items-center justify-center p-4 {{ request()->routeIs('admin.shopfront.orders.category-report') ? 'text-blue-700 bg-blue-50' : 'text-gray-700' }} hover:bg-gray-100 transition-all duration-200 rounded-lg">
                    <div class="w-12 h-12 flex items-center justify-center rounded-lg bg-gray-50 shadow-sm">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <span class="mt-2 text-xs font-medium">eREPORT</span>
                </a>

                <a href="{{ route('admin.accounting.reports.profit-loss') }}"
                    class="nav-item flex flex-col items-center justify-center p-4 {{ request()->routeIs('admin.accounting.reports.profit-loss') ? 'text-blue-700 bg-blue-50' : 'text-gray-700' }} hover:bg-gray-100 transition-all duration-200 rounded-lg">
                    <div class="w-12 h-12 flex items-center justify-center rounded-lg bg-gray-50 shadow-sm">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                    <span class="mt-2 text-xs font-medium">EARNINGS</span>
                </a>

                <a href="{{ route('admin.inventory.sales-summary') }}"
                    class="nav-item flex flex-col items-center justify-center p-4 {{ request()->routeIs('admin.inventory.sales-summary') ? 'text-blue-700 bg-blue-50' : 'text-gray-700' }} hover:bg-gray-100 transition-all duration-200 rounded-lg">
                    <div class="w-12 h-12 flex items-center justify-center rounded-lg bg-gray-50 shadow-sm">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <span class="mt-2 text-xs font-medium">SALES</span>
                </a>

                <a href="{{ route('admin.inventory.sales-return-summary') }}"
                    class="nav-item flex flex-col items-center justify-center p-4 {{ request()->routeIs('admin.inventory.return-summary') ? 'text-blue-700 bg-blue-50' : 'text-gray-700' }} hover:bg-gray-100 transition-all duration-200 rounded-lg">
                    <div class="w-12 h-12 flex items-center justify-center rounded-lg bg-gray-50 shadow-sm">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </div>
                    <span class="mt-2 text-xs font-medium">RETURNS</span>
                </a>

                <a href="{{ route('admin.inventory.damage-summary') }}"
                    class="nav-item flex flex-col items-center justify-center p-4 {{ request()->routeIs('admin.inventory.damage-summary') ? 'text-blue-700 bg-blue-50' : 'text-gray-700' }} hover:bg-gray-100 transition-all duration-200 rounded-lg">
                    <div class="w-12 h-12 flex items-center justify-center rounded-lg bg-gray-50 shadow-sm">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <span class="mt-2 text-xs font-medium">DAMAGES</span>
                </a>
                @endrole

                <!-- DEPOSITS -->
                <a href="{{ route('admin.accounting.deposit.index') }}"
                    class="nav-item flex flex-col items-center justify-center p-4 {{ request()->routeIs('admin.accounting.deposit.*') ? 'text-blue-700 bg-blue-50' : 'text-gray-700' }} hover:bg-gray-100 transition-all duration-200 rounded-lg">
                    <div class="w-12 h-12 flex items-center justify-center rounded-lg bg-gray-50 shadow-sm">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                    </div>
                    <span class="mt-2 text-xs font-medium">DEPOSITS</span>
                </a>
                @role('admin')
                <!-- Accounting -->
                <a href="{{ route('admin.accounting.ledgers.index') }}"
                    class="nav-item flex flex-col items-center justify-center p-4 {{ request()->routeIs('admin.accounting.ledgers.*') ? 'text-blue-700 bg-blue-50' : 'text-gray-700' }} hover:bg-gray-100 transition-all duration-200 rounded-lg">
                    <div class="w-12 h-12 flex items-center justify-center rounded-lg bg-gray-50 shadow-sm">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <span class="mt-2 text-xs font-medium">LEDGERS</span>
                </a>

                <!-- Inventory -->
                <a href="{{ route('admin.inventory.products.index') }}"
                    class="nav-item flex flex-col items-center justify-center p-4 {{ request()->routeIs('admin.inventory.products.*') ? 'text-blue-700 bg-blue-50' : 'text-gray-700' }} hover:bg-gray-100 transition-all duration-200 rounded-lg">
                    <div class="w-12 h-12 flex items-center justify-center rounded-lg bg-gray-50 shadow-sm">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </div>
                    <span class="mt-2 text-xs font-medium">PRODUCTS</span>
                </a>


                <a href="{{ route('admin.shopfront.images.index') }}"
                    class="nav-item flex flex-col items-center justify-center p-4 {{ request()->routeIs('admin.shopfront.images.*') ? 'text-blue-700 bg-blue-50' : 'text-gray-700' }} hover:bg-gray-100 transition-all duration-200 rounded-lg">
                    <div class="w-12 h-12 flex items-center justify-center rounded-lg bg-gray-50 shadow-sm">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <span class="mt-2 text-xs font-medium">SIMAGES</span>
                </a>




                <a href="{{ route('admin.batches.index') }}"
                    class="nav-item flex flex-col items-center justify-center p-4 {{ request()->routeIs('admin.batches.*') ? 'text-blue-700 bg-blue-50' : 'text-gray-700' }} hover:bg-gray-100 transition-all duration-200 rounded-lg">
                    <div class="w-12 h-12 flex items-center justify-center rounded-lg bg-gray-50 shadow-sm">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <span class="mt-2 text-xs font-medium">BATCHES</span>
                </a>

                <a href="{{ route('admin.damage.index') }}"
                    class="nav-item flex flex-col items-center justify-center p-4 {{ request()->routeIs('admin.damage.*') ? 'text-blue-700 bg-blue-50' : 'text-gray-700' }} hover:bg-gray-100 transition-all duration-200 rounded-lg">
                    <div class="w-12 h-12 flex items-center justify-center rounded-lg bg-gray-50 shadow-sm">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <span class="mt-2 text-xs font-medium">BREAKAGE</span>
                </a>
                @endrole

                @php
                $assignedLedgers = cache()->remember('user_ledgers_'.Auth::id(), 86400, function() {
                $staffMember = App\Models\Staff::with('ledgers')->where('user_id', Auth::id())->first();
                return $staffMember ? $staffMember->ledgers : collect([]);
                });
                @endphp

                @if($assignedLedgers->count() > 0)

                <!-- Ledger Links -->
                @foreach($assignedLedgers as $ledger)
                <a href="{{ route('admin.accounting.ledgers.show', $ledger->id) }}"
                    class="nav-item flex flex-col items-center justify-center p-4 {{ request()->is('admin/accounting/ledgers/'.$ledger->id) ? 'text-blue-700 bg-blue-50' : 'text-gray-700' }} hover:bg-gray-100 transition-all duration-200 rounded-lg">
                    <div class="w-12 h-12 flex items-center justify-center rounded-lg bg-gray-50 shadow-sm">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <span class="mt-2 text-xs font-medium">CHECK</span>
                </a>
                @endforeach
                @endif

                <!-- EPAY -->
                <a href="#"
                    class="nav-item flex flex-col items-center justify-center p-4 hover:bg-gray-100 transition-all duration-200 rounded-lg">
                    <div class="w-12 h-12 flex items-center justify-center rounded-lg bg-gray-50 shadow-sm">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <span class="mt-2 text-xs font-medium">EPAY</span>
                </a>

                <!-- HELP - WhatsApp Support -->
                <a href="https://wa.me/8801712113080?text=Hello,%20I%20need%20help%20with%20ePatner%20system"
                    target="_blank"
                    class="nav-item flex flex-col items-center justify-center p-4 hover:bg-gray-100 transition-all duration-200 rounded-lg">
                    <div class="w-12 h-12 flex items-center justify-center rounded-lg bg-green-50 shadow-sm">
                        <svg class="w-7 h-7 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.594z" />
                        </svg>
                    </div>
                    <span class="mt-2 text-xs font-medium">HELP</span>
                </a>


                <!-- APPS -->
                <div x-data="{ appsOpen: false }" class="w-full relative">
                    <button @click="appsOpen = !appsOpen"
                        class="nav-item w-full flex flex-col items-center justify-center p-4 hover:bg-gray-100 transition-all duration-200 rounded-lg">
                        <div class="w-12 h-12 flex items-center justify-center rounded-lg bg-gray-50 shadow-sm">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                            </svg>
                        </div>
                        <span class="mt-2 text-xs font-medium">APPS</span>
                    </button>

                    <!-- Apps Dropdown Menu -->
                    <div x-show="appsOpen"
                        @click.away="appsOpen = false"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        style="z-index: 50;"
                        class="fixed lg:left-20 right-20 lg:right-auto bottom-24 w-64 bg-white rounded-lg shadow-xl py-2 border border-gray-100">

                        <!-- Apps Header -->
                        <div class="px-4 py-3 border-b border-gray-100">
                            <p class="text-sm font-semibold text-gray-800">ePATNER UPCOMING</p>
                            <p class="text-xs text-gray-500">Choose an application to access</p>
                        </div>

                        <!-- HMS - Hospital Management System -->
                        <a href="#"
                            class="block w-full px-4 py-3 text-sm text-left text-gray-700 hover:bg-red-50 flex items-center transition-colors duration-150 group">
                            <div class="w-10 h-10 flex items-center justify-center rounded-lg bg-red-100 text-red-600 mr-3 group-hover:bg-red-200">
                                <!-- Medical Cross Icon -->
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" />
                                    <path d="M11 7h2v10h-2V7zm-4 4h10v2H7v-2z" />
                                </svg>
                            </div>
                            <div>
                                <span class="font-medium text-gray-900">HMS</span>
                                <p class="text-xs text-gray-500">Hospital Management System</p>
                            </div>
                        </a>

                        <!-- RMS - Restaurant Management System -->
                        <a href="#"
                            class="block w-full px-4 py-3 text-sm text-left text-gray-700 hover:bg-amber-50 flex items-center transition-colors duration-150 group">
                            <div class="w-10 h-10 flex items-center justify-center rounded-lg bg-amber-100 text-amber-600 mr-3 group-hover:bg-amber-200">
                                <!-- Restaurant/Food Icon -->
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17M17 13v8a2 2 0 01-2 2H9a2 2 0 01-2-2v-8m8 0V9a2 2 0 00-2-2H9a2 2 0 00-2 2v4.01" />
                                </svg>
                            </div>
                            <div>
                                <span class="font-medium text-gray-900">RMS</span>
                                <p class="text-xs text-gray-500">Restaurant Management System</p>
                            </div>
                        </a>

                        <!-- Coming Soon Section -->
                        <div class="px-4 py-2 border-t border-gray-100 mt-2">
                            <p class="text-xs text-gray-400 text-center">More apps coming soon...</p>
                        </div>
                    </div>
                </div>


            </nav>

            <!-- Bottom Section with Settings and Logout -->
            <div class="border-t border-gray-200 py-4 space-y-2">
                @role('admin')

                <a href="{{ route('admin.salary-heads.index') }}"
                    class="nav-item flex flex-col items-center justify-center p-4 {{ request()->routeIs('admin.salary-heads.*') ? 'text-blue-700 bg-blue-50' : 'text-gray-700' }} hover:bg-gray-100 transition-all duration-200 rounded-lg">
                    <div class="w-12 h-12 flex items-center justify-center rounded-lg bg-gray-50 shadow-sm">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <span class="mt-2 text-xs font-medium">SALARY</span>
                </a>



                <a href="{{ route('admin.staff.index') }}"
                    class="nav-item flex flex-col items-center justify-center p-4 {{ request()->routeIs('admin.staff.index') ? 'text-blue-700 bg-blue-50' : 'text-gray-700' }} hover:bg-gray-100 transition-all duration-200 rounded-lg">
                    <div class="w-12 h-12 flex items-center justify-center rounded-lg bg-gray-50 shadow-sm">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <span class="mt-2 text-xs font-medium">USERS</span>
                </a>


                <a href="{{ route('profile.edit') }}"
                    class="nav-item flex flex-col items-center justify-center p-4 {{ request()->routeIs('profile.edit') ? 'text-blue-700 bg-blue-50' : 'text-gray-700' }} hover:bg-gray-100 transition-all duration-200 rounded-lg">
                    <div class="w-12 h-12 flex items-center justify-center rounded-lg bg-gray-50 shadow-sm">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <span class="mt-2 text-xs font-medium">PROFILE</span>
                </a>
                @endrole

                <div x-data="{ profileOpen: false }" class="w-full relative">
                    <button @click="profileOpen = !profileOpen"
                        class="nav-item w-full flex flex-col items-center justify-center p-4 text-gray-700 hover:bg-gray-100 transition-all duration-200 rounded-lg">
                        <div class="w-12 h-12 flex items-center justify-center rounded-lg bg-gradient-to-br from-blue-50 to-blue-100 shadow-sm">
                            <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <span class="mt-2 text-xs font-medium">LOGOUT</span>
                    </button>

                    <!-- Enhanced Dropdown Menu with Responsive Positioning -->
                    <div x-show="profileOpen"
                        @click.away="profileOpen = false"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        style="z-index: 50;"
                        class="fixed lg:left-20 right-20 lg:right-auto bottom-24 w-48 bg-white rounded-lg shadow-xl py-2 border border-gray-100">

                        <!-- User Info Section -->
                        <div class="px-4 py-3 border-b border-gray-100">
                            <p class="text-sm font-semibold text-gray-800">{{ Auth::user()->name }}</p>
                            <p class="text-xs text-gray-500">{{ Auth::user()->email }}</p>
                        </div>

                        <!-- Profile Link -->
                        <a href="{{ route('profile.edit') }}"
                            class="block w-full px-4 py-2 text-sm text-left text-gray-700 hover:bg-gray-50 flex items-center transition-colors duration-150">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <span>Edit Profile</span>
                        </a>

                        <!-- Logout Option -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                class="w-full px-4 py-2 text-sm text-left text-red-600 hover:bg-red-50 flex items-center transition-colors duration-150">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                <span>Logout</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Overlay -->
    <div x-show="sidebarOpen"
        @click="sidebarOpen = false"
        class="fixed inset-0 z-30 bg-black bg-opacity-50 lg:hidden">
    </div>


</div>